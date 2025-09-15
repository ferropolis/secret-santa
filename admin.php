<?php
require __DIR__.'/db.php';
session_start();

$hash = setting('admin_password_hash');
if ($hash === '') {
  if (!empty($_POST['new_admin_pwd'])) {
    setting('admin_password_hash', password_hash($_POST['new_admin_pwd'], PASSWORD_DEFAULT));
    header('Location: admin.php'); exit;
  }
  ?>
  <!doctype html><html lang="de"><head><meta charset="utf-8"><link rel="stylesheet" href="style.css"><meta name="robots" content="noindex,nofollow"></head>
  <body><header class="header"><div class="container"><h1>🎅 Secret Santa – Admin</h1></div></header>
  <div class="container" style="margin:1.2rem auto 2rem;"><div class="card">
  <h2>Erstes Admin-Passwort setzen</h2>
  <form method="post" class="row row-2"><input type="password" name="new_admin_pwd" placeholder="Neues Admin-Passwort" required>
  <button type="submit" class="secondary">Speichern</button></form>
  </div></div></body></html>
  <?php exit;
}

if (!isset($_SESSION['admin'])) {
  if (!empty($_POST['pwd']) && password_verify($_POST['pwd'], $hash)) { $_SESSION['admin']=true; header('Location: admin.php'); exit; }
  ?>
  <!doctype html><html lang="de"><head><meta charset="utf-8"><link rel="stylesheet" href="style.css"><meta name="robots" content="noindex,nofollow"></head>
  <body><header class="header"><div class="container"><h1>🎅 Secret Santa – Admin Login</h1></div></header>
  <div class="container" style="margin:1.2rem auto 2rem;"><div class="card">
  <form method="post" class="row row-2"><input type="password" name="pwd" placeholder="Passwort" required>
  <button type="submit">Login</button></form>
  </div></div></body></html>
  <?php exit;
}

$year = (int) setting('current_round_year');
$cnt  = (int) db()->query('SELECT COUNT(*) FROM participants')->fetchColumn();
?>
<!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><link rel="stylesheet" href="style.css"></head>
<body>
<header class="header"><div class="container"><h1>🎄 Secret Santa – Admin</h1></div></header>
<div class="container" style="margin:1.2rem auto 2rem;">
  <div class="card">
    <p class="meta">Teilnehmer: <strong><?= $cnt ?></strong> • Jahr: <strong><?= $year ?></strong></p>

    <form method="post" action="toggle_reg.php" style="margin-bottom:1rem;">
      <input type="hidden" name="to" value="<?= setting('registration_open')==='1' ? '0' : '1' ?>">
      <button type="submit" class="secondary"><?= setting('registration_open')==='1' ? 'Anmeldung schließen' : 'Anmeldung öffnen' ?></button>
    </form>

    <h2>Ziehung (Permutation ohne Wiederholung der letzten 2 Jahre)</h2>
    <?php if ($cnt < 2): ?>
      <p class="badge">Mindestens 2 Teilnehmer benötigt.</p>
    <?php endif; ?>
    <form method="post" action="do_draw.php">
      <button type="submit" <?= $cnt>=2 ? '' : 'disabled' ?>>Zuweisungen erzeugen (Vorschau)</button>
    </form>

    <form method="post" action="do_notify.php" onsubmit="return confirm('E-Mails an alle Schenker:innen senden?');" style="margin-top:1rem;">
      <button type="submit">E-Mails versenden</button>
    </form>

	<h2>Werkzeuge</h2>
<form method="post" action="advance_year.php" onsubmit="return confirm('Jahr fortschreiben?');">
  <button type="submit" class="secondary">Jahr +1 setzen</button>
</form>

<h2 style="margin-top:1.2rem;">Zurücksetzen</h2>
<p class="note small">
  <strong>Achtung:</strong> Löscht alle Zuordnungen und Mail-Logs für das aktuelle Jahr (<?= htmlspecialchars(setting('current_round_year')) ?>).
  Teilnehmer bleiben erhalten.
</p>
<form method="post" action="reset_all.php" onsubmit="return confirm('Wirklich alle Daten für dieses Jahr löschen?');" class="row row-2">
  <input type="text" name="confirm" placeholder="Zum Bestätigen bitte RESET eingeben" required>
  <button type="submit" class="danger">Aktuelles Jahr löschen (Reset)</button>
</form>

  </div>
</div>
</body></html>
