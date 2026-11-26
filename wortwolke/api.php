<?php
// Wortwolke: kleine Schnittstelle ohne Datenbank.
// Speichert pro Runde eine JSON-Datei in ./daten/ (nur Wörter, keine Namen, keine IP-Adressen).
// Runden werden nach 24 Stunden automatisch gelöscht. Gemeinsamer Teil: ../gemeinsam/runden.php

declare(strict_types=1);
const DIR = __DIR__ . '/daten';
require __DIR__ . '/../gemeinsam/runden.php';

const MAX_WORDS = 2000;     // Einträge pro Runde
const MAX_LEN = 30;         // Zeichen pro Wort
const MAX_Q = 120;          // Zeichen der Frage

function wordKey(string $w): string {
  $w = mb_strtolower($w);
  $w = preg_replace('/^[\p{P}\p{S}\s]+|[\p{P}\p{S}\s]+$/u', '', $w) ?? '';
  return $w;
}
function publicView(array $d): array {
  $words = [];
  foreach ($d['words'] as $w) $words[] = ['w' => $w['d'], 'c' => $w['c']];
  return ['ok' => true, 'question' => $d['question'], 'open' => $d['open'], 'limit' => $d['limit'],
          'version' => $d['version'], 'words' => $words];
}

$in = eingabe();
$action = (string)($in['action'] ?? '');
runden_standard($action, $in, fn(array $d) => publicView($d));

switch ($action) {
  case 'create': {
    $limit = (int)($in['limit'] ?? 3);
    if (!in_array($limit, [1, 3, 5, 0], true)) $limit = 3;
    [$code, $key] = neueRunde(['question' => clean((string)($in['question'] ?? ''), MAX_Q), 'limit' => $limit,
                               'words' => new stdClass(), 'devices' => new stdClass(), 'total' => 0]);
    out(['ok' => true, 'code' => $code, 'key' => $key]);
  }

  case 'add': {
    $code = validCode($in['r'] ?? '');
    $word = clean((string)($in['word'] ?? ''), MAX_LEN);
    $k = wordKey($word);
    if ($k === '') fail('Bitte ein Wort eingeben.');
    $dev = deviceHash($code, $in['device'] ?? '');
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

  default:
    fail('Unbekannte Aktion.');
}
