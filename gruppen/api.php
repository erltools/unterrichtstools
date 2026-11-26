<?php
// Gruppeneinteilung: kleine Schnittstelle ohne Datenbank.
// Speichert pro Runde eine JSON-Datei in ./daten/: Anzahl Gruppen, Gruppennamen, Plätze,
// Zahl der vergebenen Plätze und für jedes Gerät (nur als Hash) die Nummer seiner Gruppe.
// Keine Namen von Personen, keine IP-Adressen. Runden werden nach 24 Stunden gelöscht.
// Gemeinsamer Teil: ../gemeinsam/runden.php

declare(strict_types=1);
const DIR = __DIR__ . '/daten';
require __DIR__ . '/../gemeinsam/runden.php';

const MIN_GROUPS = 2;
const MAX_GROUPS = 10;
const MAX_SEATS = 200;      // Plätze pro Runde höchstens
const MAX_NAME = 30;        // Zeichen pro Gruppenname

// Alle dürfen sehen: Gruppen, Plätze, wie viele vergeben sind. Nicht: wie viele in jeder Gruppe sind
// (sonst ließe sich ablesen, welche Gruppe als Nächstes kommt).
function publicView(array $d, array $in, string $code): array {
  $v = ['ok' => true, 'names' => $d['names'], 'seats' => $d['seats'], 'taken' => $d['taken'],
        'open' => $d['open'], 'version' => $d['version']];
  if (isset($in['device'])) {
    $dev = deviceHash($code, $in['device']);
    $v['mine'] = is_array($d['devices']) && isset($d['devices'][$dev]) ? $d['devices'][$dev] : -1;
  }
  return $v;
}

function names(array $raw, int $n): array {
  $out = [];
  for ($i = 0; $i < $n; $i++) {
    $s = clean((string)($raw[$i] ?? ''), MAX_NAME);
    $out[] = $s !== '' ? $s : 'Gruppe ' . ($i + 1);
  }
  return $out;
}

// Nächste Gruppe ziehen: reihum in Blöcken. Jeder Block enthält jede Gruppe genau einmal in zufälliger
// Reihenfolge. So sind die Gruppen immer gleich groß (höchstens 1 Unterschied). Am Übergang zwischen zwei
// Blöcken wird verhindert, dass dieselbe Gruppe zweimal hintereinander kommt.
function nextGroup(array &$d): int {
  if (empty($d['queue'])) {
    $n = count($d['names']);
    $q = range(0, $n - 1);
    for ($i = $n - 1; $i > 0; $i--) { $j = random_int(0, $i); [$q[$i], $q[$j]] = [$q[$j], $q[$i]]; }
    if ($q[0] === ($d['last'] ?? -1)) { $j = random_int(1, $n - 1); [$q[0], $q[$j]] = [$q[$j], $q[0]]; }
    $d['queue'] = $q;
  }
  $g = array_shift($d['queue']);
  $d['last'] = $g;
  return $g;
}

$in = eingabe();
$action = (string)($in['action'] ?? '');
runden_standard($action, $in, 'publicView');

switch ($action) {
  case 'create': {
    $n = (int)($in['groups'] ?? 0);
    if ($n < MIN_GROUPS || $n > MAX_GROUPS) fail('Bitte 2 bis 10 Gruppen wählen.');
    $seats = (int)($in['seats'] ?? 0);
    if ($seats < 1 || $seats > MAX_SEATS) fail('Bitte 1 bis 200 Plätze wählen.');
    [$code, $key] = neueRunde(['names' => names((array)($in['names'] ?? []), $n), 'seats' => $seats, 'taken' => 0,
                               'queue' => [], 'last' => -1, 'devices' => new stdClass()]);
    out(['ok' => true, 'code' => $code, 'key' => $key]);
  }

  case 'join': {
    // Gerät bekommt eine Gruppe. Hat es schon eine, bekommt es dieselbe wieder.
    $code = validCode($in['r'] ?? '');
    $dev = deviceHash($code, $in['device'] ?? '');
    [, $g] = withRound($code, function (array &$d) use ($dev) {
      if (!is_array($d['devices'])) $d['devices'] = [];
      if (isset($d['devices'][$dev])) return $d['devices'][$dev];
      if (!$d['open']) fail('Die Lehrkraft hat die Einteilung beendet.', 409);
      if ($d['taken'] >= $d['seats']) fail('Alle Plätze sind vergeben. Sag deiner Lehrkraft Bescheid.', 409);
      $g = nextGroup($d);
      $d['devices'][$dev] = $g;
      $d['taken']++;
      $d['version']++;
      return $g;
    }, true);
    out(['ok' => true, 'mine' => $g]);
  }

  case 'seats': {
    // Plätze nachlegen
    $code = validCode($in['r'] ?? '');
    $add = (int)($in['add'] ?? 0);
    withRound($code, function (array &$d) use ($in, $add) {
      checkKey($d, (string)($in['key'] ?? ''));
      if ($add < 1) fail('Ungültige Anzahl.');
      if ($d['seats'] + $add > MAX_SEATS) fail('Mehr als 200 Plätze gehen nicht.');
      $d['seats'] += $add;
      $d['version']++;
    }, true);
    out(['ok' => true]);
  }

  case 'rename': {
    $code = validCode($in['r'] ?? '');
    withRound($code, function (array &$d) use ($in) {
      checkKey($d, (string)($in['key'] ?? ''));
      $d['names'] = names((array)($in['names'] ?? []), count($d['names']));
      $d['version']++;
    }, true);
    out(['ok' => true]);
  }

  default:
    fail('Unbekannte Aktion.');
}
