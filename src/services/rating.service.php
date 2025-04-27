<?php

function review($user) {
  $rows = query("
    SELECT
      review.id AS session_id,
      review.session,
      content.id AS content_id,
      content.question,
      content.answers,
      content.voted
    FROM
      review
    LEFT JOIN
      content c ON JSON_CONTAINS(review.session, JSON_QUOTE(content.id), '$')
    WHERE
      review.user_id = :user
  ",
  ['user' => $user]);

  $sessions = [];

  foreach ($rows as $row) {
    $sessionId = $row['session_id'];

    $voted = null;
    if (!empty($row['voted'])) {
      $votes = json_decode($row['voted'], true);
      $voted = $votes[$user] ?? null;
    }

    $sessions[$sessionId][] = [
      'voted'    => $voted,
      'id'       => (int) $row['content_id'],
      'question' => $row['question'],
      'answers'  => json_decode($row['answers'], true) ?: []
    ];
  }

  return $sessions;
}

function vote($id, $vote, $user) {

  $row = query("
    SELECT 
      voted 
    FROM 
      content 
    WHERE 
      id = :id", 
    ['id' => $id]
  );

  if (!$row) {
    http_response_code(404);
    exit;
  }

  $voted = [];

  if (!empty($row[0]['voted'])) {
    $voted = json_decode($row[0]['voted'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $voted = [];
    }
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
  
  http_response_code(201);
  exit;
}