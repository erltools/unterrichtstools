<?php
// Gemeinsamer Server-Teil für Tools mit Runden (Wortwolke, Abstimmung, Gruppen).
// Kein Datenbank-Server: Jede Runde ist eine JSON-Datei im Ordner DIR des Tools.
// Gespeichert werden keine Namen und keine IP-Adressen. Runden werden nach 24 Stunden gelöscht.
//
// Einbinden in einem Tool (api.php):
//   const DIR = __DIR__ . '/daten';
//   require __DIR__ . '/../gemeinsam/runden.php';
// Danach: $in = eingabe(); runden_standard($action, $in, 'publicView'); ... eigene Aktionen ...

declare(strict_types=1);

// Direkter Aufruf dieser Datei bringt nichts.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
if (!defined('DIR')) { http_response_code(500); exit; }

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const MAX_AGE = 86400;      // 24 Stunden
const MAX_ROUNDS = 1000;    // Schutz vor Missbrauch

function out(array $d, int $status = 200): void { http_response_code($status); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function fail(string $msg, int $status = 400): void { out(['ok' => false, 'error' => $msg], $status); }

// Text säubern: Steuerzeichen raus, Leerraum zusammenfassen, kürzen.
function clean(string $s, int $max): string {
  $s = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $s) ?? '';
  $s = preg_replace('/\s+/u', ' ', $s) ?? '';
  $s = trim($s);
  return mb_substr($s, 0, $max);
}
function validCode($c): string { $c = (string)$c; if (!preg_match('/^\d{4}$/', $c)) fail('Code ungültig.'); return $c; }
function file_for(string $code): string { return DIR . '/' . $code . '.json'; }

// Geräte-Kennung nur als Hash pro Runde speichern: nicht rückverfolgbar, nicht über Runden verknüpfbar.
function deviceHash(string $code, $raw): string {
  $dev = preg_replace('/[^a-z0-9]/', '', (string)$raw) ?: 'x';
  return substr(hash('sha256', $code . $dev), 0, 16);
}

function cleanup(): void {
  foreach (glob(DIR . '/*.json') ?: [] as $f) {
    if (filemtime($f) !== false && time() - filemtime($f) > MAX_AGE) @unlink($f);
  }
}

// Eingabe lesen: POST als JSON, GET als Parameter.
function eingabe(): array {
  return $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input') ?: '', true) ?: [])
    : $_GET;
}

// Neue Runde anlegen. $data enthält die tool-eigenen Felder. Gibt [Code, Schlüssel der Lehrkraft] zurück.
function neueRunde(array $data): array {
  cleanup();
  if (count(glob(DIR . '/*.json') ?: []) >= MAX_ROUNDS) fail('Gerade sind zu viele Runden offen. Bitte später erneut versuchen.', 503);
  do { $code = (string)random_int(1000, 9999); } while (is_file(file_for($code)));
  $key = bin2hex(random_bytes(16));
  $data = ['created' => time(), 'open' => true, 'version' => 1, 'keyHash' => hash('sha256', $key)] + $data;
  if (file_put_contents(file_for($code), json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX) === false) fail('Speicherfehler.', 500);
  return [$code, $key];
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

// Aktionen, die alle Runden-Tools gleich haben: ping, get, open, delete.
// $publicView(array $runde, array $in, string $code): array liefert, was alle sehen dürfen.
function runden_standard(string $action, array $in, callable $publicView): void {
  switch ($action) {
    case 'ping':
      out(['ok' => true]);

    case 'get': {
      $code = validCode($in['r'] ?? '');
      [$d] = withRound($code, null, false);
      if (isset($in['v']) && (int)$in['v'] === $d['version']) out(['ok' => true, 'changed' => false, 'version' => $d['version']]);
      out($publicView($d, $in, $code) + ['changed' => true]);
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
  }
}

if (!is_dir(DIR)) @mkdir(DIR, 0750, true);
