<?php

class Context {
  private array $bindings    = [];
  private array $cache       = [];
  private array $headers     = [];
  private array $cookies     = [];
  private array $queryParams = [];
  private array $params      = [];
  
  public string $path = '/';
  public string $method = 'GET';
  public string $host;

  public function with($headers = null, $query = null, $params = null, $bindings = null) {
    $this->headers     = isset($headers)  ? array_merge(array_change_key_case($headers)) : $this->headers;
    $this->queryParams = isset($query)    ? array_merge(array_change_key_case($query))   : $this->queryParams;
    $this->params      = isset($params)   ? array_merge(array_change_key_case($params))  : $this->params;
    $this->bindings    = isset($bindings) ? array_merge($bindings) : $this->bindings;

    if (isset($this->headers['cookie'])) {
      
      $this->cookies = array_reduce(explode('; ', $this->headers['cookie']), function ($p, $e) {
        [$k, $v] = explode('=', $e, 2);
        
        $p[strtolower($k)] = $v;

        return $p;
      }, []);
      
    }
  }

  public function header($key) {
    $key = strtolower($key);
    
    return isset($this->headers[$key]) ? $this->headers[$key] : null;
  }

  public function param($key) {
    $key = strtolower($key);
    
    return isset($this->params[$key]) ? $this->params[$key] : null;
  }

  public function query($key) {
    $key = strtolower($key);
    
    return isset($this->queryParams[$key]) ? $this->queryParams[$key] : null;
  }

  public function cookie($key) {
    $key = strtolower($key);
    
    return isset($this->cookies[$key]) ? $this->cookies[$key] : null;
  }

  public function setCookie(string $key, string $value, int $expire) {

    setcookie($key, $value, [
      'expires'  => $expire,
      'path'     => '/',
      'httponly' => true,
      'secure'   => isset($_SERVER['HTTPS']),
      'samesite' => 'Strict'
    ]);
  
    $this->cookies[strtolower($key)] = $value;
  }


  public function bind(string $key, callable $fetch) {
    $this->bindings[$key] ??= [];
    $this->bindings[$key][] = $fetch;

    unset($this->cache[$key]);
  }

  public function unbind(string $key) {
    array_pop($this->bindings[$key]);

    if (count($this->bindings[$key]) <= 0) {
      unset($this->bindings[$key]);
    }

    unset($this->cache[$key]);
  }

  public function use($key, $p0 = null, $p1 = null) {
    $cacheKey = $key;
    
    if ($key[0] == '@' && strlen($key) > 4) {
      $prop = explode('.', $key)[0];

      switch ($prop) {
        case '@query':
          return $this->queryParams;
        case '@path':
          return $this->path;
        case '@params':
          return $this->params;
      }
            
    }

    if (!isset($this->bindings[$key])) {
      return null;
    }

    if (isset($p0) && !is_array($p0)) {
      $cacheKey .= $p0;
    }

    if (isset($p1) && !is_array($p1)) {
      $cacheKey .= $p1;
    }

    if (!isset($this->cache[$cacheKey]) && !is_array($p0) && !is_array($p1)) {
      $this->cache[$cacheKey] = end($this->bindings[$key]);
      $this->cache[$cacheKey] = isset($this->cache[$cacheKey]) ? $this->cache[$cacheKey]($p0, $p1) : null;
    } else {
      return end($this->bindings[$key])($p0, $p1);
    }

    return $this->cache[$cacheKey];
  }

}