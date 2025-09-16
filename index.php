<?php require __DIR__.'/db.php'; ?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars(setting('site_title')) ?></title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="style.css">
</head>
	
<canvas id="snow-canvas" aria-hidden="true"></canvas>
<script src="snow.js"></script>
	
<body>
<header class="header">
  <div class="container">
    <h1><span class="fl">🎅</span><?= htmlspecialchars(setting('site_title')) ?></h1>
  </div>
</header>
<div class="container" style="margin:1.2rem auto 2rem;">
  <div class="card">
    <p class="meta">Jahr: <strong><?= htmlspecialchars(setting('current_round_year')) ?></strong> •
      <span class="badge"><?= setting('registration_open')==='1' ? 'Anmeldung offen' : 'Anmeldung geschlossen' ?></span>
    </p>

    <?php if (setting('registration_open')==='1'): ?>
      <h2>Anmeldung</h2>
      <form class="row row-2" method="post" action="do_register.php" autocomplete="on">
        <input type="text" name="name" placeholder="Dein Name" required>
        <input type="email" name="email" placeholder="Deine E-Mail" required>
		<?php if (setting('invite_code') !== ''): ?>
  			<input type="text" name="invite" placeholder="Einladungscode" required>
			<?php else: ?>
  			<input type="hidden" name="invite" value="">
		<?php endif; ?>
        
		<!-- Honeypot: für Menschen unsichtbar -->
  		<div style="display:none;">
    	<input type="text" name="website" value="">
  		</div>  
		 
		<button type="submit">Eintragen</button>
      </form>
      <p class="note small">Nur Name & E-Mail werden gespeichert. E-Mail wird in der Liste verkürzt angezeigt.</p>
    <?php else: ?>
      <p><strong>Die Anmeldung ist geschlossen.</strong></p>
    <?php endif; ?>

    <h2>Bereits angemeldet</h2>
    <table>
      <thead><tr><th>Name</th><th class="meta">E-Mail (verkürzt)</th><th class="meta">Seit</th></tr></thead>
      <tbody>
      <?php
        $stmt = db()->query('SELECT name,email,registered_at FROM participants ORDER BY registered_at ASC');
        foreach ($stmt as $row) {
          $safe = htmlspecialchars($row['email']);
          if (strpos($safe,'@')!==false) { [$u,$d]=explode('@',$safe,2); $short=substr($u,0,2).'…@'.$d; }
          else { $short='—'; }
          echo '<tr><td>'.htmlspecialchars($row['name']).'</td><td class="meta">'.$short.'</td><td class="meta">'.htmlspecialchars($row['registered_at']).'</td></tr>';
        }
      ?>
      </tbody>
    </table>

    <hr>
    <p class="footer">🔒 Nicht indexierbar. Daten werden für die 2-Jahres-Sperre vorgehalten und spätestens nach 3 Jahren gelöscht.</p>
  </div>

  <p class="meta" style="text-align:center;margin-top:.8rem;">
    <a class="btn" href="admin.php">Admin</a>
  </p>
</div>
</body>
</html>
