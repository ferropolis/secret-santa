<?php
require __DIR__.'/db.php';
session_start();
if (!isset($_SESSION['admin'])) { http_response_code(403); exit('Forbidden'); }

$pdo  = db();
$year = (int) setting('current_round_year');

// 1) Versuche Session
$assign = $_SESSION['draw_preview_ss'] ?? null;

// 2) Fallback: POST-Hidden-Felder (falls Session leer)
if (!$assign && !empty($_POST['giver_ids']) && !empty($_POST['receiver_ids'])) {
  $gArr = array_map('intval', (array)$_POST['giver_ids']);
  $rArr = array_map('intval', (array)$_POST['receiver_ids']);
  if (count($gArr) === count($rArr)) {
    $assign = [];
    for ($i=0; $i<count($gArr); $i++) {
      $g = $gArr[$i]; $r = $rArr[$i];
      if ($g > 0 && $r > 0) $assign[$g] = $r;
    }
  }
}

// Wenn immer noch nichts: sauberer Fehler
if (!$assign || !is_array($assign) || empty($assign)) {
  http_response_code(400);
  exit('Keine Vorschau vorhanden.');
}

// Prüfen: existiert schon eine Runde?
$exists = $pdo->prepare('SELECT COUNT(*) FROM assignments WHERE round_year=?');
$exists->execute([$year]);
if ((int)$exists->fetchColumn() > 0) { exit('Für dieses Jahr existieren bereits Zuweisungen.'); }

// Validieren: keine Selbstzuweisungen, keine Duplikate
$receiversSeen = [];
foreach ($assign as $g=>$r) {
  if ($g == $r) exit('Ungültig: Selbstzuweisung entdeckt.');
  if (isset($receiversSeen[$r])) exit('Ungültig: Empfänger doppelt zugewiesen.');
  $receiversSeen[$r] = true;
}

$pdo->beginTransaction();
try {
  $ins = $pdo->prepare('INSERT INTO assignments (round_year, giver_id, receiver_id) VALUES (?, ?, ?)');
  foreach ($assign as $g=>$r) {
    $ins->execute([$year, (int)$g, (int)$r]);
  }
  $pdo->commit();
  unset($_SESSION['draw_preview_ss']);
  header('Location: admin.php');
  exit;
} catch (Throwable $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo 'Fehler beim Speichern: '.htmlspecialchars($e->getMessage());
}
