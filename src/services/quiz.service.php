<?php 

function getSetup($context) {

  try {
    $modules = query('
      SELECT DISTINCT
        modul_name AS module,
        lektion    AS lection
      FROM
        content
      '
    );
  } catch (\Throwable $th) {
    http_response_code(500);
    exit;
  }
 
  $selected = $context->query('module') ?: null;
  
  if ($selected) {
    
    foreach ($modules as &$m) {
      $m['is_selected'] = $m['module'] === $selected ? true : false;
    }

    $lections = array_unique(
      array_column(
        array_filter($modules, fn($s) => $s['module'] === $selected),
        'lection'
      )
    ) ?? null;

    sort($lections);
    $context->bind('selected', fn() => $context->query('module'));
    $context->bind('lections', fn() => $lections);
  }

  return $modules;
}

function createSession($context) {
  
  $module  = !empty($_POST['module'])  ? $_POST['module']  : null;
  $lection = !empty($_POST['lection']) ? $_POST['lection'] : null;
  $amount  = !empty($_POST['amount'])  ? $_POST['amount']  : 3;
  $user    = $context->use('user');

  $rows = query('
    SELECT 
      id
    FROM 
      content 
    WHERE 
       modul_name = :module
    AND 
      (:lection IS NULL OR lektion = :lection)
    ORDER BY
      RANDOM()
    LIMIT :amount
    ', [
      'module'  => $module,
      'lection' => $lection,
      'amount'  => (int) $amount
    ]
  );

  $time = time();

  $id = substr(md5("$user $time"), 0, 8);

  query('
    INSERT INTO 
      sessions (
        id, 
        session,
        user_id, 
        progress)
    VALUES (
      :id, 
      :session,
      :user, 
      :progress)
  ', [
    'id'       => $id,
    'session'  => json_encode($rows),
    'user'     => $context->use('user'),
    'progress' => 0
    ]
  );

  $expire = time() + 60 * 60;

  $context->setCookie('session', $id, $expire);

  header("Location: /session?id=$id");
  exit;
}

function getQuestion($context) {

  $id   = $context->cookie('session');
  $user = $context->use('user') ?: null;

  try {
    $row = query('
      SELECT
        session,
        progress
      FROM
        sessions
      WHERE
        id = :id
      AND
        user_id = :user
      ', [
        'id'   => $id,
        'user' => $user
      ]
    )[0];
  } catch (\Throwable $th) {

    http_response_code(500);
    exit;
  }

  if (empty($row['session']) || !is_string($row['session'])) {
    $context->setCookie('session', '', time() -3600);

    header("Location: /setup");
    exit;
  }

  
  $progress = $row['progress'] ?? 0;
  $session  = json_decode($row['session'], true);
  $total    = count($session);

  if ($progress >= count($session)) {
    $context->setCookie('session', '', time() - 3600);

    header("Location: /setup");
    exit;
  }

  try {
    $data = query('
      SELECT
        *
      FROM
        content
      WHERE
        id = :id
      LIMIT 
        1
      ', [
        'id' => $session[$progress]['id']
      ]
    )[0] ?? null;
  } catch (\Throwable $th) {
    http_response_code(500);
    exit;
  }

  if (empty($data)) {
    http_response_code(500);
    exit;
  }

  $answers = json_decode($data['answers'], true);

  shuffle($answers);

  $data['answers'] = $answers;

  $context->bind('offset',   fn() => offset($progress, $total));
  $context->bind('progress', fn() => $progress);
  $context->bind('total',    fn() => $total);

  return $data;
}

function evaluate($context) {

  $id         = $context->cookie('session');
  $userAnswer = $_POST['answer'] ?? null;
  $decoded    = [];

  if (!$userAnswer) {
    http_response_code(400);
    exit;
  }

  $row = query('
    SELECT 
      session,
      progress
    FROM 
      sessions
    WHERE 
      id = :id
  ', [
    'id' => $id
  ])[0] ?? null;
  
  if (!$row) {
    http_response_code(404);
    exit;
  }
  
  $session  = json_decode($row['session'], true);

  $progress = (int) $row['progress'];

  $answers = query('
    SELECT 
      answers
    FROM 
      content
    WHERE 
      id = :id
  ', [
    'id' => $session[$progress]['id']
  ])[0] ?? null;

  if (!$answers || !isset($answers['answers'])) {
    http_response_code(500);
    exit;
  }

  $answers = json_decode($answers['answers'], true);

  if (!is_array($answers)) {
    http_response_code(500);
    exit;
  }

  $match = array_filter($answers, fn($a) => $a['text'] === $userAnswer);

  if (!$match) {
    http_response_code(404);
    exit;
  } else {

    $correct = array_values($match)[0]['correct'];

    if ($correct) {
      $existingRow = query('
        SELECT 
          result 
        FROM 
          sessions 
        WHERE 
          id = :id', [
          'id' => $id
        ]
      )[0] ?? [];

      $decoded = !empty($existingRow['result']) ? json_decode($existingRow['result'], true) : [];

      $decoded[] = $session[$progress];
    
      query('
        UPDATE
          sessions
        SET
          progress = progress + 1,
          result = :result
        WHERE
          id = :id
        ', [
          'id' => $id,
          'result' => json_encode($decoded)
        ]
      );
    } else {
      query('
        UPDATE
          sessions
        SET
          progress = progress + 1
        WHERE
          id = :id
        ', [
          'id' => $id
        ]
      );
    }
  }
  
  if (!isset($session[$progress + 1])) {


    if (!$decoded) {
      $existingRow = query('
        SELECT 
          result 
        FROM 
          sessions 
        WHERE 
          id = :id', [
          'id' => $id
        ]
      )[0] ?? [];

      $decoded = !empty($existingRow['result']) ? json_decode($existingRow['result'], true) : [];
    }

    $total = max(count($session ?? []), 1);
    $score = round(count($decoded ?? []) / $total * 100);

    $context->setCookie('session', '', time() -3600);
    
    header("Location: /result$score");
    exit;
  }
  
  header("Location: /session");
  exit;
}

function offset($progress, $total) {

  $circumference = 2 * pi() * 45;
  $percentage    = $progress / $total;

  return $circumference * (1 - $percentage);
}