<?php
require __DIR__ . '/db.php';
session_start();
if (!isset($_SESSION['admin'])) { http_response_code(403); exit('Forbidden'); }

// doppelte absicherung
$confirm = trim($_POST['confirm'] ?? '');
if ($confirm !== 'RESET') {
  http_response_code(400);
  exit('Bestätigung fehlgeschlagen. Bitte genau "RESET" eingeben.');
}

$pdo = db();
$year = (int) setting('current_round_year');

$pdo->beginTransaction();
try {
  // assignments und mail_log nur für das aktuelle jahr löschen
  $del1 = $pdo->prepare('DELETE FROM assignments WHERE round_year = ?');
  $del1->execute([$year]);

  $del2 = $pdo->prepare('DELETE FROM mail_log WHERE subject LIKE ?');
  // alle mails, die den aktuellen jahrgang im betreff enthalten
  $del2->execute(['%'.$year.'%']);

  $pdo->commit();
} catch (Throwable $e) {
  $pdo->rollBack();
  http_response_code(500);
  exit('Fehler beim Zurücksetzen: ' . htmlspecialchars($e->getMessage()));
}

header('Location: admin.php');
exit;
