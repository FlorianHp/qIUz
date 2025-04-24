<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function createJWT(array $payload): string {

  return JWT::encode($payload, $_ENV['SECRET'], 'HS256');
}

function decodeJWT(string $jwt): ?array {
  try {
    $decoded = JWT::decode($jwt, new Key($_ENV['SECRET'], 'HS256'));

    return (array) $decoded;

  } catch (Exception $e) {

    return null;
  }
}

function handleLogin($context) {
  
  $username = $_POST['user'];
  $password = $_POST['password'];

  $user = query('
    SELECT
      *
    FROM 
      users 
    WHERE 
      username = :username 
    LIMIT 
      1', 
    ['username' => $username]
  )[0];


  if ($user && password_verify($password, $user['password_hash'])) {

    $expire = time() + 2 * 60 * 60;

    $payload = [
      'exp'  => $expire,
      'user' => $username
    ];

    $jwt = createJWT($payload);

    date_default_timezone_set('Europe/Berlin');

    file_put_contents('login.log', "Login: " . $username . " - success - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    $context->setCookie('token', $jwt, $expire);

    header('Location: /');
    exit;

  } else {
    file_put_contents('login.log', "Login: " . $username . " - failed - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    header("Location: /login?failed=0");
    http_response_code(303);
    exit;
  }
}

function handleLogout($context) {

  $expire = time() -3600;

  $context->setCookie('token', '', $expire);

  header("Location: /login");
  exit;
}
