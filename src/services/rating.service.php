<?php

function review($user) {

  $rows = query("
    SELECT
      session
    FROM
      review
    WHERE
      user_id = :user
  ", [
    'user' => $user
  ]);

  foreach ($rows as &$row) {
    $session = json_decode($row['session'], true);

    json_last_error() === JSON_ERROR_NONE ? $row['session'] = $session : $row['session'] = [];
  }

  return $rows;
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