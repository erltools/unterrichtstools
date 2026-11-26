<?php
// Wortwolke: kleine Schnittstelle ohne Datenbank.
// Speichert pro Runde eine JSON-Datei in ./daten/ (nur Wörter, keine Namen, keine IP-Adressen).
// Runden werden nach 24 Stunden automatisch gelöscht.

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const DIR = __DIR__ . '/daten';
const MAX_AGE = 86400;      // 24 Stunden
const MAX_ROUNDS = 1000;    // Schutz vor Missbrauch
const MAX_WORDS = 2000;     // Einträge pro Runde
const MAX_LEN = 30;         // Zeichen pro Wort
const MAX_Q = 120;          // Zeichen der Frage

function out(array $d, int $status = 200): void { http_response_code($status); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function fail(string $msg, int $status = 400): void { out(['ok' => false, 'error' => $msg], $status); }

function clean(string $s, int $max): string {
  $s = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $s) ?? '';
  $s = preg_replace('/\s+/u', ' ', $s) ?? '';
  $s = trim($s);
  return mb_substr($s, 0, $max);
}
function wordKey(string $w): string {
  $w = mb_strtolower($w);
  $w = preg_replace('/^[\p{P}\p{S}\s]+|[\p{P}\p{S}\s]+$/u', '', $w) ?? '';
  return $w;
}
function validCode($c): string { $c = (string)$c; if (!preg_match('/^\d{4}$/', $c)) fail('Code ungültig.'); return $c; }
function file_for(string $code): string { return DIR . '/' . $code . '.json'; }

function cleanup(): void {
  foreach (glob(DIR . '/*.json') ?: [] as $f) {
    if (filemtime($f) !== false && time() - filemtime($f) > MAX_AGE) @unlink($f);
  }
}

// Liest eine Runde, ruft $fn (darf ändern) und schreibt zurück. Mit Sperre gegen gleichzeitige Zugriffe.
function withRound(string $code, ?callable $fn, bool $write) {
  $path = file_for($code);
  if (!is_file($path)) fail('Diese Runde gibt es nicht (mehr).', 404);
  $h = fopen($path, 'c+');
  if (!$h) fail('Speicherfehler.', 500);
  flock($h, $write ? LOCK_EX : LOCK_SH);
  $data = json_decode(stream_get_contents($h) ?: '', true);
  if (!is_array($data) || time() - ($data['created'] ?? 0) > MAX_AGE) {
    flock($h, LOCK_UN); fclose($h); @unlink($path); fail('Diese Runde ist abgelaufen.', 404);
  }
  $result = $fn ? $fn($data) : null;
  if ($write) {
    ftruncate($h, 0); rewind($h);
    fwrite($h, json_encode($data, JSON_UNESCAPED_UNICODE));
    fflush($h);
  }
  flock($h, LOCK_UN); fclose($h);
  return [$data, $result];
}
function checkKey(array $d, string $key): void {
  if (!hash_equals($d['keyHash'] ?? '', hash('sha256', $key))) fail('Keine Berechtigung.', 403);
}
function publicView(array $d): array {
  $words = [];
  foreach ($d['words'] as $w) $words[] = ['w' => $w['d'], 'c' => $w['c']];
  return ['ok' => true, 'question' => $d['question'], 'open' => $d['open'], 'limit' => $d['limit'],
          'version' => $d['version'], 'words' => $words];
}

if (!is_dir(DIR)) @mkdir(DIR, 0750, true);

$in = $_SERVER['REQUEST_METHOD'] === 'POST'
  ? (json_decode(file_get_contents('php://input') ?: '', true) ?: [])
  : $_GET;
$action = (string)($in['action'] ?? '');

switch ($action) {
  case 'ping':
    out(['ok' => true]);

  case 'create': {
    cleanup();
    if (count(glob(DIR . '/*.json') ?: []) >= MAX_ROUNDS) fail('Gerade sind zu viele Runden offen. Bitte später erneut versuchen.', 503);
    $limit = (int)($in['limit'] ?? 3);
    if (!in_array($limit, [1, 3, 5, 0], true)) $limit = 3;
    do { $code = (string)random_int(1000, 9999); } while (is_file(file_for($code)));
    $key = bin2hex(random_bytes(16));
    $data = ['created' => time(), 'question' => clean((string)($in['question'] ?? ''), MAX_Q),
             'limit' => $limit, 'open' => true, 'version' => 1, 'keyHash' => hash('sha256', $key),
             'words' => new stdClass(), 'devices' => new stdClass(), 'total' => 0];
    if (file_put_contents(file_for($code), json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX) === false) fail('Speicherfehler.', 500);
    out(['ok' => true, 'code' => $code, 'key' => $key]);
  }

  case 'get': {
    $code = validCode($in['r'] ?? '');
    [$d] = withRound($code, null, false);
    if (isset($in['v']) && (int)$in['v'] === $d['version']) out(['ok' => true, 'changed' => false, 'version' => $d['version']]);
    out(publicView($d) + ['changed' => true]);
  }

  case 'add': {
    $code = validCode($in['r'] ?? '');
    $word = clean((string)($in['word'] ?? ''), MAX_LEN);
    $k = wordKey($word);
    if ($k === '') fail('Bitte ein Wort eingeben.');
    $dev = preg_replace('/[^a-z0-9]/', '', (string)($in['device'] ?? '')) ?: 'x';
    $dev = substr(hash('sha256', $code . $dev), 0, 16); // nicht rückverfolgbar
    [, $left] = withRound($code, function (array &$d) use ($k, $word, $dev) {
      if (!$d['open']) fail('Die Lehrkraft hat die Eingabe beendet.', 409);
      if ($d['total'] >= MAX_WORDS) fail('Diese Runde ist voll.', 409);
      $used = $d['devices'][$dev] ?? 0;
      if ($d['limit'] > 0 && $used >= $d['limit']) fail('Du hast schon alle Wörter abgeschickt.', 409);
      if (!is_array($d['words'])) $d['words'] = [];
      if (!is_array($d['devices'])) $d['devices'] = [];
      if (isset($d['words'][$k])) $d['words'][$k]['c']++;
      else $d['words'][$k] = ['d' => trim(preg_replace('/^[\p{P}\p{S}\s]+|[\p{P}\p{S}\s]+$/u', '', $word) ?? $word), 'c' => 1];
      $d['devices'][$dev] = $used + 1;
      $d['total']++;
      $d['version']++;
      return $d['limit'] > 0 ? $d['limit'] - $used - 1 : -1;
    }, true);
    out(['ok' => true, 'left' => $left]);
  }

  case 'remove': {
    $code = validCode($in['r'] ?? '');
    $k = wordKey(clean((string)($in['word'] ?? ''), MAX_LEN));
    withRound($code, function (array &$d) use ($in, $k) {
      checkKey($d, (string)($in['key'] ?? ''));
      if (is_array($d['words']) && isset($d['words'][$k])) { unset($d['words'][$k]); $d['version']++; }
    }, true);
    out(['ok' => true]);
  }

  case 'open': {
    $code = validCode($in['r'] ?? '');
    withRound($code, function (array &$d) use ($in) {
      checkKey($d, (string)($in['key'] ?? ''));
      $d['open'] = (bool)($in['open'] ?? true);
      $d['version']++;
    }, true);
    out(['ok' => true]);
  }

  case 'delete': {
    $code = validCode($in['r'] ?? '');
    withRound($code, function (array &$d) use ($in) { checkKey($d, (string)($in['key'] ?? '')); }, false);
    @unlink(file_for($code));
    out(['ok' => true]);
  }

  default:
    fail('Unbekannte Aktion.');
}
