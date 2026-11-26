<?php
// Abstimmung: kleine Schnittstelle ohne Datenbank.
// Speichert pro Runde eine JSON-Datei in ./daten/: Frage, Antworten und Stimmen je Antwort.
// Keine Namen, keine IP-Adressen; Geräte nur als Hash, damit jedes Handy eine Stimme hat.
// Runden werden nach 24 Stunden automatisch gelöscht. Gemeinsamer Teil: ../gemeinsam/runden.php

declare(strict_types=1);
const DIR = __DIR__ . '/daten';
require __DIR__ . '/../gemeinsam/runden.php';

const MAX_Q = 160;          // Zeichen der Frage
const MAX_OPT_LEN = 60;     // Zeichen pro Antwort
const MAX_OPTS = 8;         // Antworten pro Frage
const MAX_VOTERS = 500;     // Geräte pro Runde

function publicView(array $d, array $in, string $code): array {
  $v = ['ok' => true, 'question' => $d['question'], 'options' => $d['options'], 'counts' => $d['counts'],
        'total' => array_sum($d['counts']), 'open' => $d['open'], 'version' => $d['version']];
  if (isset($in['device'])) {
    $dev = deviceHash($code, $in['device']);
    $v['mine'] = is_array($d['votes']) && isset($d['votes'][$dev]) ? $d['votes'][$dev] : -1;
  }
  return $v;
}

$in = eingabe();
$action = (string)($in['action'] ?? '');
runden_standard($action, $in, 'publicView');

switch ($action) {
  case 'create': {
    $opts = [];
    foreach ((array)($in['options'] ?? []) as $o) {
      $o = clean((string)$o, MAX_OPT_LEN);
      if ($o !== '') $opts[] = $o;
    }
    $opts = array_slice($opts, 0, MAX_OPTS);
    if (count($opts) < 2) fail('Bitte mindestens zwei Antworten eintragen.');
    [$code, $key] = neueRunde(['question' => clean((string)($in['question'] ?? ''), MAX_Q), 'options' => $opts,
                               'counts' => array_fill(0, count($opts), 0), 'votes' => new stdClass()]);
    out(['ok' => true, 'code' => $code, 'key' => $key]);
  }

  case 'vote': {
    $code = validCode($in['r'] ?? '');
    $choice = (int)($in['choice'] ?? -1);
    $dev = deviceHash($code, $in['device'] ?? '');
    withRound($code, function (array &$d) use ($choice, $dev) {
      if (!$d['open']) fail('Die Abstimmung ist beendet.', 409);
      if ($choice < 0 || $choice >= count($d['options'])) fail('Diese Antwort gibt es nicht.');
      if (!is_array($d['votes'])) $d['votes'] = [];
      $prev = $d['votes'][$dev] ?? -1;
      if ($prev === $choice) return;
      if ($prev < 0 && count($d['votes']) >= MAX_VOTERS) fail('Diese Runde ist voll.', 409);
      if ($prev >= 0) $d['counts'][$prev] = max(0, $d['counts'][$prev] - 1);
      $d['counts'][$choice]++;
      $d['votes'][$dev] = $choice;
      $d['version']++;
    }, true);
    out(['ok' => true, 'mine' => $choice]);
  }

  case 'reset': {
    // Gleiche Frage noch einmal: alle Stimmen löschen und Abstimmung wieder öffnen.
    $code = validCode($in['r'] ?? '');
    withRound($code, function (array &$d) use ($in) {
      checkKey($d, (string)($in['key'] ?? ''));
      $d['counts'] = array_fill(0, count($d['options']), 0);
      $d['votes'] = new stdClass();
      $d['open'] = true;
      $d['version']++;
    }, true);
    out(['ok' => true]);
  }

  default:
    fail('Unbekannte Aktion.');
}
