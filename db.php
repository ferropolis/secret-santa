<?php
require __DIR__ . '/config.php';

function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO(
      'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
      DB_USER, DB_PASS,
      [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
    );
  }
  return $pdo;
}

function setting(string $key, ?string $val=null): string {
  $pdo=db();
  if ($val===null){
    $stmt=$pdo->prepare('SELECT svalue FROM settings WHERE skey=?');
    $stmt->execute([$key]);
    return (string)($stmt->fetchColumn() ?: '');
  } else {
    $stmt=$pdo->prepare('REPLACE INTO settings (skey,svalue) VALUES (?,?)');
    $stmt->execute([$key,$val]);
    return $val;
  }
}
