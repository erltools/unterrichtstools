<?php
// Lädt Name, Anschrift und E-Mail für Impressum und Datenschutz.
// Die echten Angaben stehen NICHT im Repo, sondern in einer Datei auf dem Server
// außerhalb des Web-Ordners: eine Ebene über httpdocs, also /klassenkit.de/kontakt.php.
// Fehlt sie, erscheinen gelb markierte Platzhalter.
declare(strict_types=1);

function kontakt(): array {
  static $k = null;
  if ($k !== null) return $k;
  $leer = ['name' => '', 'anschrift' => '', 'email' => '', 'aufsicht' => ''];
  $datei = dirname(__DIR__, 2) . '/kontakt.php';
  $daten = is_file($datei) ? (include $datei) : [];
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
