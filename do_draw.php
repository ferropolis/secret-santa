<?php
require __DIR__.'/db.php';
session_start();
if (!isset($_SESSION['admin'])) { http_response_code(403); exit('Forbidden'); }

$pdo  = db();
$year = (int) setting('current_round_year');

function html_start($title='Vorschau'){
  echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><link rel="stylesheet" href="style.css"><title>'.$title.'</title></head><body>';
  echo '<header class="header"><div class="container"><h1>🎁 '.$title.'</h1></div></header><div class="container" style="margin:1.2rem auto 2rem;"><div class="card">';
}
function html_end(){ echo '</div></div></body></html>'; }

// Teilnehmer laden
$rows = $pdo->query('SELECT id, name, email FROM participants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$n = count($rows);
if ($n < 2) { html_start('Hinweis'); echo '<p class="badge">Mindestens 2 Teilnehmer benötigt.</p>'; html_end(); exit; }

// Verbotsliste: self + (J-1, J-2)
$forbidden = [];
foreach ($rows as $r) { $forbidden[(int)$r['id']] = [ (int)$r['id'] => true ]; }
$stmt = $pdo->prepare('SELECT giver_id, receiver_id FROM assignments WHERE round_year IN (?, ?)');
$stmt->execute([$year-1, $year-2]);
foreach ($stmt as $as) {
  $g=(int)$as['giver_id']; $rc=(int)$as['receiver_id'];
  if (!isset($forbidden[$g])) $forbidden[$g] = [ $g => true ];
  $forbidden[$g][$rc] = true;
}

// Backtracking
$givers = array_map(fn($r)=>(int)$r['id'], $rows);
$receivers = $givers;
usort($givers, function($a,$b) use($forbidden){
  $fa = isset($forbidden[$a]) ? count($forbidden[$a]) : 0;
  $fb = isset($forbidden[$b]) ? count($forbidden[$b]) : 0;
  return $fb <=> $fa;
});

function solve(array $givers, array $receiverOrder, array $forbidden, array &$assign): bool {
  $pos = count($assign);
  if ($pos === count($givers)) return true;
  $giver = $givers[$pos];
  foreach ($receiverOrder as $rId) {
    if (in_array($rId, $assign, true)) continue;
    if (isset($forbidden[$giver][$rId])) continue;
    $assign[$giver] = $rId;
    if (solve($givers, $receiverOrder, $forbidden, $assign)) return true;
    unset($assign[$giver]);
  }
  return false;
}

$ok = false; $tries = 0; $assign = [];
while ($tries < 60 && !$ok) {
  $tries++;
  $order = $receivers; shuffle($order);
  $assign = [];
  $ok = solve($givers, $order, $forbidden, $assign);
}

if (!$ok) {
  html_start('Keine gültige Zuordnung');
  echo '<p class="badge">Leider keine gültige Secret-Santa-Permutation gefunden.</p>';
  echo '<ul class="meta"><li>Teilnehmerzahl erhöhen (eine Person mehr hilft oft sofort).</li><li>Sperre testweise auf 1 Jahr reduzieren.</li></ul>';
  html_end(); exit;
}

// Vorschau im Session-Cache
$_SESSION['draw_preview_ss'] = $assign;

// Namenstabelle
$byId = []; foreach ($rows as $r) { $byId[(int)$r['id']] = $r; }

html_start('Vorschau Secret-Santa-Zuweisungen');
echo '<table><thead><tr><th>Schenker:in</th><th>Beschenkte:r</th></tr></thead><tbody>';
foreach ($assign as $g=>$r) {
  echo '<tr><td>'.htmlspecialchars($byId[$g]['name']).'</td><td>'.htmlspecialchars($byId[$r]['name']).'</td></tr>';
}
echo '</tbody></table>';

// 🔒 Zusätzlich alle Paare als Hidden-Felder mitsenden (Fallback ohne Session)
echo '<form method="post" action="do_draw_commit.php" onsubmit="return confirm(\'Zuweisungen endgültig speichern?\');" style="margin-top:1rem;">';
foreach ($assign as $g=>$r) {
  echo '<input type="hidden" name="giver_ids[]" value="'.(int)$g.'">';
  echo '<input type="hidden" name="receiver_ids[]" value="'.(int)$r.'">';
}
echo '<button type="submit">Speichern</button></form>';

echo '<p class="footer">Hinweis: Erst nach dem Speichern kann der E-Mail-Versand ausgelöst werden.</p>';
html_end();
