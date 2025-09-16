<?php
require __DIR__ . '/db.php';

if (setting('registration_open') !== '1') {
  http_response_code(403);
  exit('Anmeldung geschlossen.');
}

// Eingaben prüfen
$name  = trim($_POST['name']  ?? '');
$email = trim($_POST['email'] ?? '');
$invite= trim($_POST['invite'] ?? '');
$honeypot = trim($_POST['website'] ?? '');

// Spam-Check
if ($honeypot !== '') {
  exit('Spam erkannt.');
}

// Einladungscode prüfen, wenn gesetzt
$expected = setting('invite_code');
if ($expected !== '') {
  if ($invite === '' || !hash_equals($expected, $invite)) {
    http_response_code(400);
    exit('Ungültiger oder fehlender Einladungscode.');
  }
}

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  exit('Bitte gültigen Namen und eine gültige E-Mail angeben.');
}

try {
  $stmt = db()->prepare('INSERT INTO participants (name, email) VALUES (?, ?)');
  $stmt->execute([$name, strtolower($email)]);
} catch (PDOException $e) {
  if (($e->errorInfo[1] ?? null) == 1062) {
    exit('Diese E-Mail ist bereits angemeldet.');
  }
  throw $e;
}

header('Location: index.php', true, 303);
exit;
