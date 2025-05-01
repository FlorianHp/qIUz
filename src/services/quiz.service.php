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

  $id = substr(md5("$module $user"), 0, 8);

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

function getQuestion($id, $user) {

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

  $progress = $row['progress'] ?? 0;
  $session  = json_decode($row['session'], true);

  try {
    $question = query('
      SELECT
        *
      FROM
        content
      WHERE
        id = :id
      LIMIT 
        1
      ', [
        'id' => $session[$progress]
      ]
    )[0] ?? null;
  } catch (\Throwable $th) {
    http_response_code(500);
    exit;
  }

  shuffle($question['answers']);

  return $question;
}

function evaluate($context) {

  $id = $context->cookie('session');

  $row = query('
    SELECT 
      session,
      done
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
  
  $session = json_decode($row['session'], true);
  $done    = (int) $row['done'];
  
  if (isset($session[$done])) {

    $existing = query('
      SELECT 
        result 
      FROM 
        sessions 
      WHERE 
        id = :id', [
        'id' => $id
      ]
    )[0]['result'] ?? [];

    $decoded = json_decode($existing, true) ?? [];

    $decoded[] = $session[$done];

    query('
      UPDATE
        sessions
      SET
        done = done + 1,
        result = :result
      WHERE
        id = :id
      ', [
        'id' => $id,
        'result' => json_encode($decoded)
      ]
    );
  }
  
  if (!isset($session[$done + 1])) {

    $score = (count($decoded) / count($session) * 100);
    
    header("Location: /result:$score");
    exit;
  }
  
  header("Location: /session:$id");
  exit;
}

function result() {

}