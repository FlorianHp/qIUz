<?php

function getReview($context) {
  $user   = $context->use('user');
  $amount = $context->query('amount') ?? 3;

  $sessions = query("
    SELECT id AS session_id, session, result, created_at
    FROM sessions
    WHERE user_id = :user
    ORDER BY created_at DESC
    LIMIT :amount
  ", [
    'user' => $user,
    'amount' => (int)$amount
  ]);

  $result = [];

  foreach ($sessions as $session) {

    $sessionDataRaw = json_decode($session['session'], true) ?: [];
    $contentIds     = is_array($sessionDataRaw[0]) 
      ? array_column($sessionDataRaw, 'id') 
      : $sessionDataRaw;


    $correctRaw = json_decode($session['result'], true) ?: [];
    $correctIds = is_array($correctRaw[0]) 
      ? array_column($correctRaw, 'id') 
      : $correctRaw;

    if (empty($contentIds)) continue;

    $placeholders = implode(', ', array_fill(0, count($contentIds), '?'));
    $contentRows  = query("SELECT * FROM content WHERE id IN ($placeholders)", $contentIds);

    $sessionData = [
      'created_at' => date('d.m.Y H:i', strtotime($session['created_at'])),
      'questions'  => []
    ];

    foreach ($contentRows as $row) {

      $votes = json_decode($row['voted'], true) ?: [];


      $voted = $votes[$user] ?? null;

      $answers = json_decode($row['answers'], true) ?: [];

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

  if (!empty($row['voted'])) {

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