<?php

function result($context) {
  $score = (int) $context->param('score');

  if ($score > $_ENV['THRESHOLD']) {
    $context->bind('hero', fn() => '/img/hero/win.webp');

    $result = [
      'h1'      => 'Gewonnen',
      'p'       => 'Lernen zahlt sich aus!',
      'message' => 'Herzlichen GLückwunsch zu deinem Ergebnis. Zur Feier ein Zitat.',
      'cite'    => ">>Veni, vidi, vici.<<<br> - Julius Caesar -",
      'score'   => $score,
      'gif'     => 'win'
    ];
  } else {
    $context->bind('hero', fn() => '/img/hero/lose.webp');

    $result = [
      'h1'      => 'Verloren',
      'p'       => 'Da fehlt noch etwas.',
      'message' => 'Du hast leider verloren, aber in solchen Momenten hilft vielleicht ein Zitat.',
      'cite'    => ">>Erfolg ist nicht endgültig, Misserfolg ist nicht fatal; was zählt, ist der Mut weiterzumachen.<<<br> - Winston Curchill -",
      'score'   => $score,
      'gif'     => 'lose'
    ];
  }

  $context->bind('result', fn() => $result);
}
