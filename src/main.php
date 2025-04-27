<?php

if (str_contains(getcwd(), 'src')) {
  chdir('../');
}

include_once 'vendor/bq/php/index.php';
include_once 'src/services/quiz.service.php';
include_once 'src/services/leaderboard.service.php';
include_once 'src/services/upload.service.php';
include_once 'src/services/auth.service.php';

router(function ( $context ) {
  
}, [

  route(
    method: 'GET', 
    middlewares: [
      function ($context) {
        $context->bind('rand', fn() => rand(0,1000));

        $skipPaths = ['/login', '/bq.js'];

        if (!in_array($context->path, $skipPaths)) { 
          $jwt = $context->cookie('token') ?? null;

          handleAuth($jwt);
        }
        
        $context->bind('menus', fn($p)  => [
          ['href' => '/game',        'text' => 'Game'],
          ['href' => '/upload',      'text' => 'Upload'],
          ['href' => '/leaderboard', 'text' => 'Leaderboard'],
          ['href' => '/help',        'text' => 'Hilfe'],
          ['href' => '/logout',      'text' => 'Ausloggen']
        ]);
      }
    ],
    routes: [

      route(path: '/bq.js', fetch: function() {
        header('content-type: application/javascript');
        
        readfile('vendor/bq/js/bq.js');
        exit;
      }),
      route(
        path: '/login', 
        fetch: function ($context) {

          $context->bind('title',  fn($a) => 'Login');
          $context->bind('site',   fn()   => 'login');
          $context->query('failed') == '0' ? $context->bind('failed',   fn()   => true): null;
          
          
          render('page', $context);
        }
      ),
      route(
        path: '/logout', 
        fetch: 'handleLogout'
      ),
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
          $context->bind('site',  fn()   => 'game');
          $context->bind('hero',  fn()   => 'score');
          $context->bind('quiz',  fn($amount, $settings) => getQuiz($amount ?? 3, $settings));

          render('page', $context);
        }
      ),
      route(
        path: '/upload', 
        fetch: function ($context) {

          $context->bind('title',   fn($a) => 'Upload');
          $context->bind('site',    fn()   => 'upload');
          $context->bind('hero',    fn()   => '/img/hero/upload.webp');
          $context->query('success') == '1' ? $context->bind('success',   fn()   => true) : null;

          render('page', $context);
        }
      ),
      route(
        path: '/leaderboard', 
        fetch: function ($context) {

          $context->bind('title',       fn($a) => 'Leaderboard');
          $context->bind('site',        fn($a) => 'leaderboard');
          $context->bind('hero',        fn()   => '/img/hero/score.webp');
          $context->bind('leaderboard', fn($a) => getLeaderboard($a = 5));
          $context->bind('add',         fn($a) => $a + 1);
          
          render('page', $context);
        }
      ),
      route(
        path: '/help', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Hilfe');
          $context->bind('site',  fn($a) => 'help');
          $context->bind('hero',  fn()   => '/img/hero/help.webp');

          render('page', $context);
        }
      ),
      route(
        path: '/contact', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Kontakt');
          $context->bind('site',  fn($a) => 'contact');
          $context->bind('hero',  fn()   => '/img/hero/contact.webp');

          render('page', $context);
        }
      ),
      route(
        path: '/legal', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Haftungsausschluss');
          $context->bind('site',  fn()   => 'legal');
          $context->bind('hero',  fn()   => '');

          render('page', $context);
        }
      ),
      route(
        path: '/privacy-policy', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Datenschutzerklärung');
          $context->bind('site',  fn()   => 'privacy-policy');
          $context->bind('hero',  fn()   => '');

          render('page', $context);
        }
      ),
    ]
  ),
  route(
    method: 'POST', 

    routes: [

      route(
        path: '/upload',
        fetch: 'handleUpload'
      ),
      route(
        path: '/login',
        fetch: function ($context) {
          
          handleLogin($context);
        }
      )
    ] 
  )  

])();