<?php
require __DIR__.'/db.php'; session_start();
if (!isset($_SESSION['admin'])) { http_response_code(403); exit; }

$pdo = db();
$year = (int) setting('current_round_year');

$sql = 'SELECT a.giver_id, a.receiver_id,
               g.name gname, g.email gemail,
               r.name rname, r.email remail
        FROM assignments a
        JOIN participants g ON g.id = a.giver_id
        JOIN participants r ON r.id = a.receiver_id
        WHERE a.round_year = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([$year]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$rows) { exit('Keine Zuweisungen fÃƒÂ¼r dieses Jahr.'); }

function send_simple_mail(string $to, string $subject, string $body): array {
  // Stelle sicher, dass die Datei als UTF-8 (ohne BOM) gespeichert ist!
  if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
  }

  $fromName  = SITE_FROM_NAME;   // z.B. "Secret Santa"
  $fromEmail = SITE_FROM_EMAIL;  // z.B. "wichteln-noreply@example.com"

  // Betreff und Absender-Name MIME-konform (UTF-8, Base64) kodieren
  $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
  $fromNameEnc = function_exists('mb_encode_mimeheader')
    ? mb_encode_mimeheader($fromName, 'UTF-8', 'B', "\r\n")
    : ('=?UTF-8?B?' . base64_encode($fromName) . '?=');

  $headers = [];
  $headers[] = 'MIME-Version: 1.0';
  $headers[] = 'From: ' . $fromNameEnc . ' <' . $fromEmail . '>';
  $headers[] = 'Reply-To: ' . $fromEmail;
  $headers[] = 'Content-Type: text/plain; charset=UTF-8';
  $headers[] = 'Content-Transfer-Encoding: base64';

  // Body als Base64 (verhindert kaputte Umlaute/Emojis)
  $bodyB64 = base64_encode($body);

  // Optional: Envelope-From setzen (bessere Zustellbarkeit; falls Hoster meckert, Parameter entfernen)
  $params = '-f ' . $fromEmail;

  $ok = @mail($to, $subjectEnc, $bodyB64, implode("\r\n", $headers), $params);
  return [$ok, $ok ? '' : 'mail() returned false'];
}


$okAll = true;
$log = $pdo->prepare('INSERT INTO mail_log (recipient, subject, body, ok, error) VALUES (?, ?, ?, ?, ?)');

foreach ($rows as $row) {
$subj = 'ðŸŽ… Dein Secret Santa Los fÃ¼r ' . $year;
$body = "Hallo {$row['gname']},\n\n"
      . "die Wichtel haben gesprochen â€“ dein Secret-Santa-Los fÃ¼r {$year} ist gezogen! ðŸŽ\n\n"
      . "Du darfst in diesem Jahr {$row['rname']} ({$row['remail']}) beschenken.\n\n"
      . "Bitte behalte dieses Geheimnis fÃ¼r dich und sorge fÃ¼r eine kleine weihnachtliche Ãœberraschung. âœ¨\n\n"
      . "Frohes Schenken und eine besinnliche Adventszeit!\n\n"
      . "Herzliche GrÃ¼ÃŸe\n"
      . "Dein Secret-Santa-Team ðŸŽ„";

  [$ok, $err] = send_simple_mail($row['gemail'], $subj, $body);
  $log->execute([$row['gemail'], $subj, $body, $ok ? 1 : 0, $ok ? null : $err]);
  if (!$ok) $okAll = false;
}


if ($okAll) {
  $upd = $pdo->prepare('UPDATE assignments SET notified_at = NOW() WHERE round_year = ?');
  $upd->execute([$year]);
}

echo $okAll ? 'E-Mails versendet.' : 'Versand abgeschlossen, aber es gab Fehler. Siehe mail_log.';
