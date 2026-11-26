<?php require __DIR__ . '/../gemeinsam/kontakt.php'; ?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Datenschutz · Unterrichtstools</title>
<link rel="stylesheet" href="../schrift/schrift.css">
<link rel="stylesheet" href="../gemeinsam/zurueck.css">
<link rel="stylesheet" href="../gemeinsam/textseite.css">
</head>
<body>
<a class="zurueck" href="../" aria-label="Zurück zur Übersicht">‹ Übersicht</a>
<main>
  <h1>Datenschutzerklärung</h1>

  <h2>Kurz gesagt</h2>
  <ul>
    <li>Keine Anmeldung, keine Cookies, kein Tracking, keine Werbung.</li>
    <li>Keine Verbindungen zu fremden Diensten. Auch die Schrift liegt auf diesem Server.</li>
    <li>Namenslisten, Sitzpläne und Einstellungen bleiben nur auf Ihrem Gerät.</li>
    <li>Nur Wortwolke und Abstimmung speichern Eingaben auf dem Server, ohne Namen, und löschen sie nach 24 Stunden.</li>
  </ul>

  <h2>1. Verantwortlicher</h2>
  <p>
    <?= k_text('name', 'Vorname Nachname') ?><br>
    <?= k_text('anschrift', 'c/o Name, Straße Hausnummer, PLZ Ort') ?><br>
    E-Mail: <?= k_email() ?>
  </p>
  <p>Ein Datenschutzbeauftragter ist für dieses private Angebot nicht bestellt und nicht vorgeschrieben.</p>

  <h2>2. Hosting und Server-Protokolle</h2>
  <p>Die Website liegt bei der netcup GmbH, Daimlerstraße 25, 76185 Karlsruhe, auf Servern in Deutschland. Mit netcup besteht ein Vertrag zur Auftragsverarbeitung nach Art. 28 DSGVO.</p>
  <p>Beim Aufruf jeder Seite speichert der Webserver automatisch technische Angaben in Protokolldateien:</p>
  <ul>
    <li>IP-Adresse des aufrufenden Geräts</li>
    <li>Datum und Uhrzeit des Aufrufs</li>
    <li>aufgerufene Adresse und übertragene Datenmenge</li>
    <li>Browser und Betriebssystem (User-Agent)</li>
    <li>zuvor besuchte Seite (Referrer), falls übermittelt</li>
  </ul>
  <p>Diese Angaben sind nötig, um die Seite auszuliefern und den Betrieb sicher zu halten, etwa um Angriffe zu erkennen. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO. Die Protokolle werden nach <mark>7 Tagen</mark> automatisch gelöscht und nicht mit anderen Daten zusammengeführt.</p>

  <h2>3. Speicherung auf Ihrem Gerät</h2>
  <p>Einige Werkzeuge merken sich Einstellungen und Listen im lokalen Speicher Ihres Browsers (localStorage), zum Beispiel:</p>
  <ul>
    <li>Lautstärke-Ampel und Timer: Einstellungen</li>
    <li>Glücksrad: eingetragene Namen oder Aufgaben</li>
    <li>Sitzplan-Würfel: Klassen, Namen und Sitzpläne</li>
    <li>Mathefußball: Spielstand und Einstellungen</li>
    <li>Wortwolke und Abstimmung: laufende Runde und eine zufällige Geräte-Kennung (siehe Abschnitt 5)</li>
  </ul>
  <p>Diese Daten werden nicht an den Server übertragen und sind nur auf diesem Gerät in diesem Browser sichtbar. Die Speicherung ist für die von Ihnen gewünschte Funktion unbedingt erforderlich (§ 25 Abs. 2 Nr. 2 TDDDG). Sie können sie jederzeit löschen, indem Sie die Website-Daten im Browser entfernen (auf dem iPad: Einstellungen › Apps › Safari › Erweitert › Website-Daten).</p>

  <h2>4. Mikrofon (Lautstärke-Ampel)</h2>
  <p>Die Lautstärke-Ampel nutzt das Mikrofon nur, wenn Sie es im Browser erlauben. Der Ton wird ausschließlich im Browser ausgewertet, um die Lautstärke zu messen. Es wird nichts aufgenommen, gespeichert oder übertragen. Die Erlaubnis können Sie in den Browser-Einstellungen jederzeit zurücknehmen.</p>

  <h2>5. Wortwolke und Abstimmung</h2>
  <p>Damit die Klasse von eigenen Geräten mitmachen kann, werden bei diesen beiden Werkzeugen Daten auf dem Server gespeichert:</p>
  <ul>
    <li>die Frage und die Antwortmöglichkeiten der Lehrkraft</li>
    <li>die abgeschickten Wörter bzw. Stimmen</li>
    <li>ein vierstelliger Runden-Code</li>
    <li>pro Gerät ein Prüfwert, der aus einer zufälligen Kennung gebildet wird. Er dient nur dazu, die Zahl der Eingaben pro Gerät zu begrenzen, und lässt keinen Rückschluss auf Person oder Gerät zu.</li>
  </ul>
  <p>Namen und IP-Adressen werden in den Runden nicht gespeichert. Jede Runde wird nach 24 Stunden automatisch gelöscht, auf Wunsch der Lehrkraft auch sofort. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO, das berechtigte Interesse ist die Bereitstellung der gewünschten Funktion.</p>
  <p>Bitte geben Sie in Wortwolke und Abstimmung keine Namen oder andere persönliche Angaben ein.</p>

  <h2>6. Aufruf über GitHub Pages</h2>
  <p>Eine Vorschau der Seite ist zusätzlich unter erltools.github.io erreichbar. Wer diese Adresse aufruft, nutzt die Server von GitHub Inc. (USA). Dabei gilt die Datenschutzerklärung von GitHub, und es kann eine Übermittlung in die USA stattfinden. Für den Unterricht nutzen Sie bitte die Hauptadresse klassenkit.de.</p>

  <h2>7. Ihre Rechte</h2>
  <p>Sie haben das Recht auf Auskunft (Art. 15 DSGVO), Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung der Verarbeitung (Art. 18) und Widerspruch gegen die Verarbeitung (Art. 21). Wenden Sie sich dafür an die oben genannte E-Mail-Adresse.</p>
  <p>Sie können sich außerdem bei einer Datenschutz-Aufsichtsbehörde beschweren, zum Beispiel bei <?= k_text('aufsicht', 'der oder dem Landesbeauftragten für Datenschutz') ?>.</p>

  <p class="stand">Stand: November 2026 · <a href="../impressum/">Impressum</a></p>
</main>
</body>
</html>
