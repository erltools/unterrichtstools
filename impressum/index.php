<?php require __DIR__ . '/../gemeinsam/kontakt.php'; ?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex">
<title>Impressum · Unterrichtstools</title>
<link rel="stylesheet" href="../schrift/schrift.css">
<link rel="stylesheet" href="../gemeinsam/zurueck.css">
<link rel="stylesheet" href="../gemeinsam/textseite.css">
</head>
<body>
<a class="zurueck" href="../" aria-label="Zurück zur Übersicht">‹ Übersicht</a>
<main>
  <h1>Impressum</h1>

  <h2>Angaben gemäß § 5 DDG</h2>
  <p>
    <?= k_text('name', 'Vorname Nachname') ?><br>
    <?= k_text('anschrift', 'c/o Name, Straße Hausnummer, PLZ Ort') ?>
  </p>

  <h2>Kontakt</h2>
  <p>E-Mail: <?= k_email() ?></p>

  <h2>Über dieses Angebot</h2>
  <p>Diese Seite ist ein privates, nicht kommerzielles Angebot. Die Werkzeuge sind kostenlos, ohne Anmeldung und ohne Werbung nutzbar.</p>

  <h2>Datenschutz</h2>
  <p>Wie mit Daten umgegangen wird, steht in der <a href="../datenschutz/">Datenschutzerklärung</a>.</p>
</main>
</body>
</html>
