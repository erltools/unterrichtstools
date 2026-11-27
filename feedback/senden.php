<?php
// Feedback-Formular: schickt die Nachricht als E-Mail an die Feedback-Adresse. Es wird nichts gespeichert,
// außer den Zeitpunkten der letzten Sendungen (für die Mengengrenze), ohne Inhalt und ohne IP-Adresse.
// Empfänger steht NICHT im Repo, sondern in kontakt.php auf dem Server: 'feedback' => '...' (sonst 'email').
declare(strict_types=1);
require __DIR__ . '/../gemeinsam/kontakt.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const MAX_PRO_STUNDE = 20;
const MAX_TEXT = 3000;
const MIN_MS = 3000;          // schneller abgeschickt = Programm
const ZAEHLER = __DIR__ . '/daten/zaehler.json';

function out(array $d, int $status = 200): void { http_response_code($status); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function fail(string $msg, int $status = 400): void { out(['ok' => false, 'error' => $msg], $status); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Nur per POST.', 405);
$in = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($in)) fail('Ungültige Anfrage.');

// Falle 1: unsichtbares Feld. Ausgefüllt = Bot. Antwort sieht wie Erfolg aus, damit der Bot nichts lernt.
if (trim((string)($in['webseite'] ?? '')) !== '') out(['ok' => true]);
// Falle 2: zu schnell abgeschickt
if ((int)($in['ms'] ?? 0) < MIN_MS) out(['ok' => true]);

$text = trim(str_replace("\r", '', (string)($in['nachricht'] ?? '')));
$text = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/u', '', $text) ?? '';
if (mb_strlen($text) < 3) fail('Bitte schreiben Sie eine Nachricht.');
if (mb_strlen($text) > MAX_TEXT) fail('Die Nachricht ist zu lang (höchstens ' . MAX_TEXT . ' Zeichen).');
// Falle 3: Links
if (preg_match_all('~https?://|www\.~i', $text) > 1) fail('Bitte höchstens einen Link in die Nachricht schreiben.');

$antwort = trim((string)($in['email'] ?? ''));
if ($antwort !== '' && (!filter_var($antwort, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $antwort))) fail('Die E-Mail-Adresse sieht nicht richtig aus. Sie können das Feld auch leer lassen.');
$bereich = mb_substr(preg_replace('/[^\p{L}\p{N} \-]/u', '', (string)($in['bereich'] ?? '')) ?? '', 0, 40);

$k = kontakt();
$an = trim((string)(($k['feedback'] ?? '') ?: ($k['email'] ?? '')));
if (!filter_var($an, FILTER_VALIDATE_EMAIL)) fail('Das Feedback-Formular ist noch nicht eingerichtet.', 503);

// Falle 4: Mengengrenze für alle zusammen
$h = @fopen(ZAEHLER, 'c+');
if (!$h) fail('Speicherfehler.', 500);
flock($h, LOCK_EX);
$zeiten = json_decode(stream_get_contents($h) ?: '[]', true);
$zeiten = array_values(array_filter(is_array($zeiten) ? $zeiten : [], fn($t) => is_int($t) && $t > time() - 3600));
if (count($zeiten) >= MAX_PRO_STUNDE) { flock($h, LOCK_UN); fclose($h); fail('Gerade kommen sehr viele Nachrichten an. Bitte versuchen Sie es in einer Stunde noch einmal.', 429); }
$zeiten[] = time();
ftruncate($h, 0); rewind($h); fwrite($h, json_encode($zeiten)); fflush($h);
flock($h, LOCK_UN); fclose($h);

$betreff = '=?UTF-8?B?' . base64_encode('Feedback klassenkit.de' . ($bereich !== '' ? ': ' . $bereich : '')) . '?=';
$inhalt = $text . "\n\n---\n" . ($bereich !== '' ? "Bereich: $bereich\n" : '') . ($antwort !== '' ? "Antwort an: $antwort\n" : "Keine Antwortadresse angegeben.\n");
$kopf = "From: klassenkit.de <$an>\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\nMIME-Version: 1.0\r\n";
if ($antwort !== '') $kopf .= "Reply-To: $antwort\r\n";

if (!@mail($an, $betreff, $inhalt, $kopf, '-f' . $an)) fail('Die Nachricht konnte gerade nicht verschickt werden. Bitte später noch einmal versuchen.', 500);
out(['ok' => true]);
