# Unterrichtstools

Kleine Web-Tools für den Unterricht, optimiert für iPad (Safari) und Beamer/Smartboard.

Website: https://klassenkit.de (netcup Webhosting, PHP).
Vorschau ohne Server-Funktionen: https://erltools.github.io/unterrichtstools/

| Tool | Ordner |
| --- | --- |
| Lautstärke-Ampel | `ampel/` |
| Wortwolke | `wortwolke/` (braucht PHP, speichert nur Wörter, löscht nach 24 h) |
| Abstimmung | `abstimmung/` (braucht PHP, speichert nur Stimmen je Antwort, löscht nach 24 h) |
| Timer | `timer/` |

Jedes Tool ist eine einzelne HTML-Datei ohne Build-Schritt. Neue Tools bekommen einen eigenen Ordner und eine Kachel auf der Startseite (`index.html`).

Zurück-Link: Jedes Tool bindet `gemeinsam/zurueck.css` ein und setzt `<a class="zurueck" href="../" aria-label="Zurück zur Übersicht">‹ Übersicht</a>` (blass oben links). In einer Kopfzeile stattdessen `class="zurueck im-fluss"`. Während ein Tool läuft, darf das Tool den Link ausblenden (siehe Timer).

Schrift: Atkinson Hyperlegible liegt lokal in `schrift/` (SIL Open Font License), damit keine Verbindung zu Google Fonts entsteht. Neue Tools binden `schrift/schrift.css` ein.

Runden-Tools (Wortwolke, Abstimmung): Gemeinsamer Server-Teil in `gemeinsam/runden.php` (Runden als JSON-Dateien im `daten/`-Ordner des Tools, 4-stelliger Code, Löschung nach 24 h), gemeinsamer Browser-Teil in `gemeinsam/runde.js` und `gemeinsam/qrcode.js`, gemeinsames Aussehen in `gemeinsam/runde.css`. Jedes Tool hat eine eigene `api.php` mit seinen Aktionen und einen `daten/`-Ordner mit `.htaccess` (kein direkter Zugriff).

Hochladen: Jeder Push auf `main` lädt die Seite per SFTP auf den Webspace (`.github/workflows/webspace.yml`). Dafür sind in GitHub die Secrets `SFTP_HOST`, `SFTP_USER`, `SFTP_PASSWORD` und `SFTP_DIR` (`/`, bei netcup ist der Dokumentstamm das Hauptverzeichnis) hinterlegt. Rundendaten (`*/daten/*.json`) werden dabei nie überschrieben.
