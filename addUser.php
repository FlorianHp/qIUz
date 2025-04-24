<?php

$dbFile = __DIR__ . '/database.sqlite';

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$username = 'name';
$password = 'password';

$hash = password_hash($password, PASSWORD_DEFAULT);
$id   = substr(md5($username), 0, 8);

$register = $pdo->prepare("INSERT OR IGNORE INTO users (id, username, password_hash) VALUES (:id, :username, :hash)");
$register->execute([
  ':id'       => $id,
  ':username' => $username,
  ':hash'     => $hash
]);

echo "✅ Benutzer '$username' wurde hinzugefügt (oder war schon vorhanden).\n";