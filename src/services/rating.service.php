<?php

function getReview($context): array {
  $user   = $context->use('user');
  $amount = (int)($context->query('amount') ?? 3);

  $sessions = query("
    SELECT id AS session_id, session, result, created_at
    FROM sessions
    WHERE user_id = :user
    ORDER BY created_at DESC
    LIMIT :amount
  ", [
    'user' => $user,
    'amount' => $amount
  ]);

  $result = [];

  foreach ($sessions as $session) {

    $sessionDataRaw = is_string($session['result']) ? json_decode($session['result'], true) : [];

    $contentIds = array_column($sessionDataRaw, 'id');
    $correctIds = array_column(
      array_filter($sessionDataRaw, fn($q) => !empty($q['correct'])),
      'id'
    );

    if (empty($contentIds)) continue;

    $placeholders = implode(', ', array_fill(0, count($contentIds), '?'));
    $contentRows  = query("SELECT * FROM content WHERE id IN ($placeholders)", $contentIds);

    $sessionData = [
      'created_at' => date('d.m.Y H:i', strtotime($session['created_at'])),
      'questions'  => []
    ];

    foreach ($contentRows as $row) {
      $voted = null;

      if (is_string($row['voted'])) {
        $votes = json_decode($row['voted'], true) ?: [];
        $voted = $votes[$user] ?? null;
      }

      $answers = is_string($row['answers']) ? json_decode($row['answers'], true) : [];

      $sessionData['questions'][] = [
        'id'       => (string) $row['id'],
        'question' => $row['question'],
        'correct'  => in_array((string)$row['id'], array_map('strval', $correctIds), true) ? 'correct' : 'incorrect',
        'voted'    => $voted,
        'answers'  => $answers
      ];
    }

    $result[] = $sessionData;
  }

  return $result;
}

function vote($context) {

  $user = $context->use('user');
  $id   = $_POST['id'];
  $vote = $_POST['vote'];

  $row = query("
    SELECT 
      voted 
    FROM 
      content 
    WHERE 
      id = :id", 
    ['id' => $id]
  )[0];

  if (!$row) {
    http_response_code(404);
    exit;
  }

  $voted = [];

  if (is_String($row['voted'])) {

    $voted = json_decode($row['voted'], true) ?: [];
  }

  if (isset($voted[$user]) && $voted[$user] === (bool) $vote) {

    http_response_code(204);
    exit;
  }

  $voted[$user] = (bool) $vote;

  try {
    query("
    UPDATE
      content
    SET
      voted = :voted
    WHERE
      id = :id
    ", 
    [
      'voted' => json_encode($voted),
      'id'    => $id
    ]
  );
  } catch (err) {

    http_response_code(500);
    exit;
  }
  
  header("Location: /review");
  exit;
}