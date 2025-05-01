<?php

function result($context) {
  $score = (int) $context->param('score');

  if ($score > $_ENV['THRESHOLD']) {
    $context->bind('hero', fn() => '/img/hero/win.webp');

    $result = [
      'h1'    => 'Gewonnen',
      'p'     => 'Lernen zahlt sich aus!',
      'score' => $score
    ];
  } else {
    $context->bind('hero', fn() => '/img/hero/lose.webp');

    $result = [
      'h1'    => 'Verloren',
      'p'     => 'Es kann nur besser werden.',
      'score' => $score
    ];
  }

  $context->bind('h1',    fn() => $result['h1']);
  $context->bind('p',     fn() => $result['p']);
  $context->bind('score', fn() => $result['score']);
}
