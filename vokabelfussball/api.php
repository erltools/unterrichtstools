<?php
// Vokabelfußball: Vokabellisten per Code teilen. Ohne Datenbank.
// Jede geteilte Liste ist eine JSON-Datei in ./listen/ (nur Name, Sprachen und Wortpaare; keine Namen von Personen, keine IP-Adressen).
// Eine Liste wird gelöscht, wenn sie 5 Jahre lang nicht abgerufen wurde. Jeder Abruf setzt die Frist neu.
//
// Aktionen:
//   POST {action:"teilen", name, a, b, paare:[[links, rechts], ...]}  -> {ok, code}
//   GET  ?action=laden&code=K7M4P                                    -> {ok, liste:{name, a, b, paare}}

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const DIR = __DIR__ . '/listen';
const MAX_ALTER = 5 * 365 * 86400;   // 5 Jahre ohne Abruf
const MAX_LISTEN = 20000;            // Schutz vor Missbrauch
const MAX_GESAMT = 1024 * 1024 * 1024; // höchstens 1 GB für alle Listen zusammen (Webspace: 25 GB)
const MAX_PAARE = 500;
const MAX_WORT = 80;
const ZEICHEN = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // ohne 0/O und 1/I, 32 Zeichen
const LAENGE = 5;

function out(array $d, int $status = 200): void { http_response_code($status); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function fail(string $msg, int $status = 400): void { out(['ok' => false, 'error' => $msg], $status); }
function clean($s, int $max): string {
  $s = preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string)$s) ?? '';
  $s = preg_replace('/\s+/u', ' ', $s) ?? '';
  return mb_substr(trim($s), 0, $max);
}
function datei(string $code): string { return DIR . '/' . $code . '.json'; }
function codeLesen($c): string {
  $c = strtoupper(preg_replace('/[\s\-]/', '', (string)$c) ?? '');
  if (!preg_match('/^[' . ZEICHEN . ']{' . LAENGE . '}$/', $c)) fail('Diesen Code gibt es nicht. Bitte noch einmal prüfen.', 404);
  return $c;
}
function aufraeumen(): void {
  foreach (array_merge(glob(DIR . '/*.json') ?: [], glob(DIR . '/index-*') ?: []) as $f) {
    $t = filemtime($f);
    if ($t !== false && time() - $t > MAX_ALTER) @unlink($f);
  }
}

if (!is_dir(DIR)) @mkdir(DIR, 0750, true);
$in = $_SERVER['REQUEST_METHOD'] === 'POST' ? (json_decode(file_get_contents('php://input') ?: '', true) ?: []) : $_GET;
$action = (string)($in['action'] ?? '');

switch ($action) {
  case 'teilen': {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Nur per POST.', 405);
    $paare = [];
    foreach (array_slice(is_array($in['paare'] ?? null) ? $in['paare'] : [], 0, MAX_PAARE) as $p) {
      if (!is_array($p)) continue;
      $x = clean($p[0] ?? '', MAX_WORT); $y = clean($p[1] ?? '', MAX_WORT);
      if ($x !== '' && $y !== '') $paare[] = [$x, $y];
    }
    if (!$paare) fail('Die Liste ist leer.');
    $liste = [
      'name' => clean($in['name'] ?? '', 60) ?: 'Geteilte Liste',
      'a' => clean($in['a'] ?? '', 20) ?: 'Deutsch',
      'b' => clean($in['b'] ?? '', 20) ?: 'Englisch',
      'paare' => $paare,
    ];
    $json = json_encode($liste, JSON_UNESCAPED_UNICODE);

    // Gleiche Liste schon einmal geteilt? Dann denselben Code zurückgeben.
    $index = DIR . '/index-' . substr(hash('sha256', $json), 0, 24);
    if (is_file($index)) {
      $alt = trim((string)file_get_contents($index));
      if ($alt !== '' && is_file(datei($alt))) { @touch(datei($alt)); @touch($index); out(['ok' => true, 'code' => $alt]); }
    }

    aufraeumen();
    $dateien = glob(DIR . '/*.json') ?: [];
    $gesamt = strlen($json);
    foreach ($dateien as $f) $gesamt += (int)@filesize($f);
    if (count($dateien) >= MAX_LISTEN || $gesamt > MAX_GESAMT) fail('Der Speicherplatz für geteilte Listen ist voll. Bitte wenden Sie sich an den Betreiber dieser Seite (Kontakt im Impressum). Ihre Listen auf diesem Gerät funktionieren weiterhin.', 507);
    $versuche = 0;
    do {
      $code = '';
      for ($i = 0; $i < LAENGE; $i++) $code .= ZEICHEN[random_int(0, strlen(ZEICHEN) - 1)];
      if (++$versuche > 20) fail('Speicherfehler.', 500);
    } while (is_file(datei($code)));
    if (file_put_contents(datei($code), $json, LOCK_EX) === false) fail('Speicherfehler.', 500);
    @file_put_contents($index, $code, LOCK_EX);
    out(['ok' => true, 'code' => $code]);
  }

  case 'laden': {
    $code = codeLesen($in['code'] ?? '');
    $f = datei($code);
    if (!is_file($f) || (filemtime($f) !== false && time() - filemtime($f) > MAX_ALTER)) fail('Diesen Code gibt es nicht. Bitte noch einmal prüfen.', 404);
    $liste = json_decode((string)file_get_contents($f), true);
    if (!is_array($liste)) fail('Diese Liste ist beschädigt.', 500);
    @touch($f); // Frist neu starten
    out(['ok' => true, 'code' => $code, 'liste' => $liste]);
  }

  default:
    fail('Unbekannte Aktion.');
}
