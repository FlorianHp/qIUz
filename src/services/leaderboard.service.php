<?php

function getLeaderboard($amount = 5) {

  $sessions = query("
    SELECT 
      s.user_id,
      s.session,
      s.result,
      u.username
    FROM 
      sessions s
    JOIN 
      users u ON u.id = s.user_id
  ");

  $userStats = [];

  foreach ($sessions as $session) {
    $userId   = $session['user_id'];
    $username = $session['username'];

    $sessionData   = is_string($session['session']) ? json_decode($session['session'], true) : [];
    $questionCount = is_array($sessionData) ? count($sessionData) : 0;

    $resultData   = is_string($session['result']) ? json_decode($session['result'], true) : [];
    $correctCount = is_array($resultData) ? count($resultData) : 0;

    if (!isset($userStats[$userId])) {
      $userStats[$userId] = [
        'user_id' => $userId,
        'username' => $username,
        'total_questions' => 0,
        'total_correct' => 0
      ];
    }

    $userStats[$userId]['total_questions'] += $questionCount;
    $userStats[$userId]['total_correct']   += $correctCount;
  }

  $leaderboard = array_map(function($stats) {
    $score = $stats['total_questions'] > 0
      ? round(($stats['total_correct'] / $stats['total_questions']) * 100)
      : 0;

    return [
      'user_id'   => $stats['user_id'],
      'username'  => $stats['username'],
      'score'     => $score,
      'questions' => $stats['total_questions'],
    ];
  }, $userStats);

  usort($leaderboard, function($a, $b) {
    if ($b['score'] === $a['score']) {
      return $b['questions'] <=> $a['questions'];
    }
    return $b['score'] <=> $a['score'];
  });

  return array_slice($leaderboard, 0, $amount);
}