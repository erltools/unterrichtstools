# Unterrichtstools

Kleine Web-Tools für den Unterricht, optimiert für iPad (Safari) und Beamer/Smartboard.

Website: https://klassenkit.de (netcup Webhosting, PHP).
Vorschau ohne Server-Funktionen: https://erltools.github.io/unterrichtstools/

| Tool | Ordner |
| --- | --- |
| Lautstärke-Ampel | `ampel/` |
| Wortwolke | `wortwolke/` (braucht PHP, speichert nur Wörter, löscht nach 24 h) |
| Abstimmung | `abstimmung/` (braucht PHP, speichert nur Stimmen je Antwort, löscht nach 24 h) |
| Gruppen einteilen | `gruppen/` (braucht PHP, speichert nur Zahlen und Gruppennamen, löscht nach 24 h) |
| Timer | `timer/` |
| Glücksrad | `gluecksrad/` (Listen nur lokal im Browser) |
| Mathefußball | `mathefussball/` |
| Vokabelfußball | `vokabelfussball/` (Listen lokal im Browser; geteilte Listen per 5-stelligem Code in `vokabelfussball/listen/`, braucht PHP, Löschung nach 5 Jahren ohne Abruf) |
| Sitzplan-Würfel | `sitzplan/` (Klassen und Pläne nur lokal im Browser) |

Impressum und Datenschutzerklärung liegen in `impressum/` und `datenschutz/` (Stil: `gemeinsam/textseite.css`), verlinkt unten auf der Startseite. Name, Anschrift und E-Mail stehen absichtlich nicht im Repo: `gemeinsam/kontakt.php` liest sie aus `kontakt.php` eine Ebene über `httpdocs` auf dem Server. Fehlt die Datei, zeigen die Seiten gelbe Platzhalter. Auf GitHub Pages laufen diese beiden PHP-Seiten nicht. Neue Tools, die Daten auf dem Server speichern oder neue Gerätefunktionen nutzen, müssen in der Datenschutzerklärung ergänzt werden.

Jedes Tool ist eine einzelne HTML-Datei ohne Build-Schritt. Neue Tools bekommen einen eigenen Ordner und eine Kachel auf der Startseite (`index.html`).

Zurück-Link: Jedes Tool bindet `gemeinsam/zurueck.css` ein und setzt `<a class="zurueck" href="../" aria-label="Zurück zur Übersicht">‹ Übersicht</a>` (blass oben links). In einer Kopfzeile stattdessen `class="zurueck im-fluss"`. Während ein Tool läuft, darf das Tool den Link ausblenden (siehe Timer).

Schrift: Atkinson Hyperlegible liegt lokal in `schrift/` (SIL Open Font License), damit keine Verbindung zu Google Fonts entsteht. Neue Tools binden `schrift/schrift.css` ein.

Runden-Tools (Wortwolke, Abstimmung, Gruppen einteilen): Gemeinsamer Server-Teil in `gemeinsam/runden.php` (Runden als JSON-Dateien im `daten/`-Ordner des Tools, 4-stelliger Code, Löschung nach 24 h), gemeinsamer Browser-Teil in `gemeinsam/runde.js` und `gemeinsam/qrcode.js`, gemeinsames Aussehen in `gemeinsam/runde.css`. Jedes Tool hat eine eigene `api.php` mit seinen Aktionen und einen `daten/`-Ordner mit `.htaccess` (kein direkter Zugriff).

Hochladen: netcup holt `main` über die Git-Verbindung in Plesk automatisch nach `/klassenkit.de/httpdocs` (Webhook in GitHub). Rundendaten (`*/daten/*.json`) liegen nicht im Repo und bleiben dabei erhalten.
