<?php

if (str_contains(getcwd(), 'src')) {
  chdir('../');
}

include_once 'vendor/bq/php/index.php';
include_once 'src/services/quiz.service.php';
include_once 'src/services/leaderboard.service.php';
include_once 'src/services/upload.service.php';
include_once 'src/services/auth.service.php';
include_once 'src/services/faq.service.php';
include_once 'src/services/search.service.php';
include_once 'src/services/result.service.php';
include_once 'src/services/rating.service.php';

router(function ( $context ) {
  $context->method = $_SERVER['REQUEST_METHOD'];
  $context->path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

  $context->with(
    headers: getallheaders(),
    query: $_GET,
    params: $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : []
  );
}, [

  route(
    method: 'GET', 
    middlewares: [
      function ($context) {
        $context->bind('rand', fn() => rand(0,1000));

        $skipPaths = ['/login', '/bq.js'];

        if (!in_array($context->path, $skipPaths)) { 
          handleAuth($context);
        }

        $context->bind('search', function (array $e, string $p) use (&$context) {
          $q = $context->query('q');

          return empty($q) ? $e : search($e, $q, fn($e) => $e['id'], $p,);
        });

        $context->bind('group', function (array $e, string $p) {
          $groups = [];

          foreach ($e as &$v) {
            foreach ($groups as &$g) {
              if ($g[0][$p] == $v[$p]) {
                $g[] = $v;

                continue 2;
              }
            }

            $groups[] = [$v];
          }

          return $groups;
        });
        
        $context->bind('menus', fn($p)  => [
          ['href' => '/setup',       'text' => 'Game'],
          ['href' => '/review',      'text' => 'Review'],
          ['href' => '/leaderboard', 'text' => 'Leaderboard'],
          ['href' => '/upload',      'text' => 'Upload'],
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

          $context->bind('title', fn($a) => 'Login');
          $context->bind('site',  fn()   => 'login');
          $context->bind('hero',  fn()   => '/img/hero/login.webp');

          $context->query('failed') == '0' ? $context->bind('failed',   fn()   => true): null;
          
          
          render('page', $context);
        }
      ),
      route(
        path: '/logout', 
        fetch: 'handleLogout'
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
        path: '/setup', 
        fetch: function ($context) {

          if (!empty($context->cookie('session'))) {
            $id = $context->cookie('session');

            header("Location: /session?id=$id");
            exit;
          }

          $context->bind('title', fn($a) => 'Setup');
          $context->bind('site',  fn()   => 'setup');
          $context->bind('hero',  fn()   => '/img/hero/setup.webp');
          $context->bind('setup', fn()   => getSetup($context));
          
          render($context->query('fragment') ?? 'page', $context);
        }
      ),
      route(
        path: '/session',
        fetch: function ($context) {
          if (empty($context->cookie('session'))) {
            if (!headers_sent()) {

              header('Location: /setup');
              exit;
            }
            exit;
          }

          $question = getQuestion($context);
          
          if (empty($question) || !isset($question['question'])) {
            if (!headers_sent()) {

              header('Location: /setup');
              exit;
            }
            exit;
          }

          $context->bind('title',    fn($a) => 'Game');
          $context->bind('site',     fn()   => 'session');
          $context->bind('hero',     fn()   => '/img/hero/game.webp');
          $context->bind('question', fn()   => $question);

          render($context->query('fragment') ?? 'page', $context);
        }
      ),
      route(
        path: '/result:score', 
        fetch: function ($context) {

          $context->bind('title',  fn($a) => 'Ergebnis');
          $context->bind('site',   fn()   => 'result');
          result($context);

          render('page', $context);
        }
      ),
      route(
        path: '/review', 
        fetch: function ($context) {

          $context->bind('title',  fn($a) => 'Review');
          $context->bind('site',   fn($a) => 'review');
          $context->bind('hero',   fn()   => '/img/hero/review.webp');
          $context->bind('review', fn($a) => getReview($context));
          $context->bind('length', fn($a) => count($a));
          
          render('page', $context);
        }
      ),
      route(
        path: '/upload', 
        fetch: function ($context) {

          $context->bind('title',   fn($a) => 'Upload');
          $context->bind('site',    fn()   => 'upload');
          $context->bind('hero',    fn()   => '/img/hero/upload.webp');
          $context->bind('modules', fn()   => modules());
          
          switch( $context->query('success')) {
            case '1':
              $context->bind('success',  fn()   => 'Upload erfolgreich ✅');
              break;
            case '0':
              $context->bind('success',  fn()   => 'Upload fehlgeschlagen ❌');
              break;
            default:
          }

          render('page', $context);
        }
      ),
      route(
        path: '/leaderboard', 
        fetch: function ($context) {

          $context->bind('title',       fn($a) => 'Leaderboard');
          $context->bind('site',        fn($a) => 'leaderboard');
          $context->bind('hero',        fn()   => '/img/hero/leaderboard.webp');
          $context->bind('leaderboard', fn($a) => getLeaderboard($a = 5));
          $context->bind('add',         fn($a) => $a + 1);
          
          render('page', $context);
        }
      ),
      route(
        path: '/help', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Hilfe');
          $context->bind('site',  fn()   => 'help');
          $context->bind('hero',  fn($a) => '/img/hero/help.webp');
          $context->bind('faq',   fn()   => faq());

          render('page', $context);
        }
      ),
      route(
        path: '/contact', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Impressum');
          $context->bind('site',  fn($a) => 'contact');
          $context->bind('hero',  fn()   => 'img/hero/contact.webp');

          render('page', $context);
        }
      ),
      route(
        path: '/legal', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Haftungsausschluss');
          $context->bind('site',  fn()   => 'legal');

          render('page', $context);
        }
      ),
      route(
        path: '/privacy-policy', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Datenschutz');
          $context->bind('site',  fn()   => 'privacy-policy');
          $context->bind('hero',  fn()   => '/img/hero/policy.webp');

          render('page', $context);
        }
      ),
      route(
        path: '/sitemap', 
        fetch: function ($context) {

          $context->bind('title', fn($a) => 'Sitemap');
          $context->bind('site',  fn()   => 'sitemap');
          $context->bind('hero',  fn()   => '/img/hero/sitemap.webp');

          render('page', $context);
        }
      ),
    ]
  ),

  route(
    method: 'GET',
    path: '/bq.js', 
    fetch: function() {
      header('content-type: application/javascript');
      
      readfile('vendor/bq/js/bq.js');
      exit;
    }
  ),

  route(
    method: 'GET',
    path: '/login', 
    fetch: function ($context) {

      $context->bind('title',  fn($a) => 'Login');
      $context->bind('site',   fn()   => 'login');
      $context->query('failed') == '0' ? $context->bind('failed',   fn()   => true): null;
      
      
      render('page', $context);
    }
  ),

  route(
    method: 'POST',
    path: '/login',
    fetch: function ($context) {
      
      handleLogin($context);
    }
  ),

  route(
    method: 'POST', 
    middlewares : [
      function ($context) {

        $skipPaths = ['/login'];

        if (!in_array($context->path, $skipPaths)) { 

          handleAuth($context);
        }
      }
    ],

    routes: [

      route(
        path: '/rating',
        fetch: fn($context) => vote($context)
      ),

      route(
        path: '/create',
        fetch: fn($context) => createSession($context)
      ),

      route(
        path: '/session',
        fetch: fn($context) => evaluate($context)
      ),

      route(
        path: '/upload',
        fetch: fn($context) => handleUpload($context)
      ),
    ] 
  )  

])();