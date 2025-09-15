<?php
require __DIR__ . '/db.php';

// Anmeldung offen?
if (setting('registration_open') !== '1') {
  http_response_code(403);
  exit('Anmeldung geschlossen.');
}

// Eingaben prüfen
$name  = trim($_POST['name']  ?? '');
$email = trim($_POST['email'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  exit('Bitte gültigen Namen und eine gültige E-Mail angeben.');
}

try {
  // E-Mail klein speichern, UNIQUE schützt vor Doppelanmeldung
  $stmt = db()->prepare('INSERT INTO participants (name, email) VALUES (?, ?)');
  $stmt->execute([$name, strtolower($email)]);
} catch (PDOException $e) {
  // 1062 = Duplicate entry (wegen UNIQUE uq_email)
  if (($e->errorInfo[1] ?? null) == 1062) {
    // sanfte Rückmeldung
    // Optional: du kannst hier auch ein Redirect mit Querystring machen (?dupe=1)
    exit('Diese E-Mail ist bereits angemeldet.');
  }
  throw $e; // andere DB-Fehler weiterwerfen
}

// zurück zur Startseite
header('Location: index.php', true, 303);
exit;
