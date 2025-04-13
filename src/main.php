<?php

if (str_contains(getcwd(), 'src')) {
  chdir('../');
}

include_once 'vendor/bq/php/index.php';

router(function ( $context ) {
  
}, [

  route(
    method: 'GET', 
    middlewares: [
      function ($context) {
        
        $context->bind('menus',    fn($p)  => [
          ['href' => '/game',         'text' => 'Game'],
          ['href' => '/upload',       'text' => 'Upload'],
          ['href' => '/leaderboard',  'text' => 'Leaderboard'],
          ['href' => '/help',         'text' => 'Hilfe']
        ]);
      }
    ],
    routes: [
    
      route(
        path: '/example', 
        fetch: function ($context) {

          $context->bind('pipe', fn($a) => $a * 2);
          $context->bind('list', fn()   => [ 'Mann', 'Frau' ]);
          $context->bind('prop', fn()   => [ 'text' => 'Bin ein String!' ]);
          $context->bind('bool', fn()   => (bool) random_int(0, 1));
          $context->bind('obj',  fn()   => [ 'list' => [1,2], 'bool' => false ]);

          render('example', $context);
        }
      ),
      route(
        path: '/', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Home');
          $context->bind('site',  fn()   => 'home');

          render('page', $context);
        }
      ),
      route(
        path: '/game', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Game');

          render('page', $context);
        }
      ),
      route(
        path: '/leaderboard', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Leaderboard');

          render('page', $context);
        }
      ),
      route(
        path: '/upload', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Upload');

          render('page', $context);
        }
      ),
      route(
        path: '/help', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Hilfe');

          render('page', $context);
        }
      )
    ]
  )
])();