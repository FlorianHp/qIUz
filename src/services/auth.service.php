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

  /**
   * @todo 
   */
  try {
    $rows = query('
      SELECT
        *
      FROM 
        users 
      WHERE 
        username = :username 
      LIMIT 
        1', 
      ['username' => $username]
    );
  } catch (err) {
    file_put_contents('login.log', "Login: " . $username . " - failed - " . date('Y-m-d H:i:s') . "[unknown user]\n", FILE_APPEND);

    header("Location: /login?failed=0");
    exit;
  }

  $user = $rows[0] ?? null;


  if ($user && password_verify($password, $user['password_hash'])) {

    $expire = time() + 2 * 60 * 60;

    $payload = [
      'exp'  => $expire,
      'user' => $username
    ];

    $jwt = createJWT($payload);

    date_default_timezone_set('Europe/Berlin');

    file_put_contents('login.log', "Login: " . $username . " - success - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    try {
      query("
        UPDATE
          users 
        SET 
          token = :token, 
          token_expires = :exp 
        WHERE 
          id = :id", [
        'token' => $jwt,
        'exp'   => $expire,
        'id'    => $user['id']
      ]);
    } catch (err) {
      http_response_code(500);
      exit;
    }

    $context->setCookie('token', $jwt, $expire);

    header('Location: /');
    http_response_code(303);
    exit;

  } else {
    file_put_contents('login.log', "Login: " . $username . " - failed - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    header("Location: /login?failed=0");
    exit;
  }
}

function handleAuth($context, $jwt) {

  $user = query("SELECT * FROM users WHERE token = :token LIMIT 1", ['token' => $jwt])[0] ?? null;

  if (!$user || $user['token_expires'] < time()) {
    header('Location: /login');
    exit;
  }
  $context->bind('user', fn() => $user->id);
}

function handleLogout($context) {

  $expire = time() -3600;

  $context->setCookie('token', '', $expire);

  header("Location: /login");
  exit;
}
