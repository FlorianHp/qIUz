<?php

include_once 'context.php';

function route(
  string   $method      = 'GET', 
  string   $path        = '', 
  string   $contentType = '*/*', 
  string   $accept      = '*/*', 
  callable $fetch       = null, 
  array    $routes      = null,
  array    $middlewares = []
) {
  $pattern = preg_replace('/:([^\/]+)/i', '(?<$1>[^/]+)', $path);
  $pattern = str_replace('/', '\/', $pattern);
  $pattern = str_replace('.', '\.', $pattern);
  $pattern = preg_replace('/\*/i', '(?<path>.*)', $pattern);

  if (is_array($routes)) {
    usort($routes, fn($a, $b) => $a['order'] - $b['order']);

    foreach ($routes as &$route) {
      $route['pattern'] = $pattern . $route['pattern'];
    }
  }

  return [
    'method'      => $method,
    'path'        => $path,
    'fetch'       => $fetch,
    'routes'      => $routes,
    'contentType' => $contentType,
    'accept'      => $accept,
    'middlewares' => $middlewares,
    'pattern'     => $pattern,
    'order'       => substr_count($path, ':') + 
                     substr_count($contentType, '*') * 10   + 
                     substr_count($accept, '*')      * 100  + 
                     substr_count($path, '*')        * 1000
  ];
}

function mimeTypesMatch(string $mimeTypes, string $mimeType): bool {
  foreach (explode(',', $mimeTypes) as $type) {
    list($t1, $st1) = explode('/', explode(';', $type)[0] . '/');
    list($t2, $st2) = explode('/', $mimeType . '/');

    if (($t1 === $t2 || $t1 === '*' || $t2 === '*') && ($st1 === $st2 || $st1 === '*' || $st2 === '*')) {
      return true;
    }
  }
  return false;
}

function router(callable $prepare, array $routes) {
  $context = new Context();
  $routes  = $routes;

  $prepare($context);

  usort($routes, fn($a, $b) => $a['order'] - $b['order']);

  return function(
    string $path    = null,
    string $method  = null,
    array  $headers = null,
    array  $query   = null,
    string $host    = null
  ) use (&$routes, $context) {
    $context->path   = $path   ?? explode('?', urldecode($_SERVER['REQUEST_URI']))[0];
    $context->method = $method ?? $_SERVER['REQUEST_METHOD'];
    $context->host   = $host   ?? $_SERVER['HTTP_HOST'];

    $query ??= $_GET;

    foreach ($query as $k => $v) {

      if (str_contains($k, '_')) {
        $p = explode('_', $k);

        $query[$p[0]] ??= [];
        $query[$p[0]][] = $p[1];

        unset($query[$k]);
      }
    }

    $context->with(headers: $headers ?? getallheaders(), query: $query);

    $found = 0;

    function findRoute(&$routes, &$context, &$found) {
      foreach ($routes as $route) {
        $pattern = $route['pattern'];
        $params  = [];

        if (empty($pattern) || preg_match('/^' . $pattern . '$/', $context->path, $params)) {
          $isParent = is_array($route['routes']);
  
          if (!$isParent) {
            $found |= 1;
          }
  
          if ($route['method'] != $context->method) {
            continue;
          }
  
          if (!$isParent) {
            $found |= 2;
          }
  
          if (!mimeTypesMatch($context->headers['accept'] ?? '*/*', $route['contentType'])) {
            continue;
          }
  
          if (!$isParent) {
            $found |= 4;
          }
  
          if (!mimeTypesMatch($route['accept'], $context->headers['content-type'] ?? '*/*')) {
            continue;
          }
          
          $context->with(params: $params);

          foreach ($route['middlewares'] as $middleware) {
            $middleware($context);
          }

          if (isset($route['fetch'])) {
            $route['fetch']($context) !== false ? exit : $found = 0;
          } else if ($isParent) {
            findRoute($route['routes'], $context, $found);
          }
        }
      }
    }

    findRoute($routes, $context, $found);

    switch ($found) {
      case 0: http_response_code(404); break;
      case 1: http_response_code(405); break;
      case 3: http_response_code(406); break;
      case 7: http_response_code(415); break;
    }

  };
}