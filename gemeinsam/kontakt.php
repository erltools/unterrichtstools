<?php
// Lädt Name, Anschrift und E-Mail für Impressum und Datenschutz.
// Die echten Angaben stehen NICHT im Repo, sondern in kontakt.php auf dem Server.
// Gesucht wird eine Ebene über httpdocs, zwei Ebenen darüber und zuletzt direkt in httpdocs
// (dort gibt die Datei beim Aufruf nichts aus). Fehlt sie, erscheinen gelb markierte Platzhalter.
declare(strict_types=1);

function kontakt(): array {
  static $k = null;
  if ($k !== null) return $k;
  $leer = ['name' => '', 'anschrift' => '', 'email' => '', 'aufsicht' => ''];
  $daten = [];
  foreach ([dirname(__DIR__, 2), dirname(__DIR__, 3), dirname(__DIR__)] as $ordner) {
    $datei = $ordner . '/kontakt.php';
    if (@is_file($datei) && @is_readable($datei)) { $daten = include $datei; break; }
  }
  $k = array_merge($leer, is_array($daten) ? $daten : []);
  return $k;
}
function k_text(string $feld, string $platzhalter): string {
  $w = trim((string)kontakt()[$feld]);
  if ($w === '') return '<mark>' . htmlspecialchars($platzhalter) . '</mark>';
  return nl2br(htmlspecialchars($w), false);
}
// E-Mail als Zeichen-Codes, damit einfache Adress-Sammler sie nicht lesen.
function k_email(): string {
  $w = trim((string)kontakt()['email']);
  if ($w === '') return '<mark>adresse@beispiel.de</mark>';
  $enc = '';
  foreach (mb_str_split($w) as $c) $enc .= '&#' . mb_ord($c) . ';';
  return '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;' . $enc . '">' . $enc . '</a>';
}
