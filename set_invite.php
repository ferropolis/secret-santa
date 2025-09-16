<?php
require __DIR__ . '/db.php';
session_start();
if (!isset($_SESSION['admin'])) { http_response_code(403); exit('Forbidden'); }

$code = trim($_POST['invite_code'] ?? '');

// Optional: Länge/Zeichen prüfen
if (strlen($code) > 64) {
  http_response_code(400);
  exit('Code ist zu lang (max. 64 Zeichen).');
}

setting('invite_code', $code);
header('Location: admin.php');
exit;
