# Unterrichtstools

Kleine Web-Tools für den Unterricht, optimiert für iPad (Safari) und Beamer/Smartboard.

Website: https://klassenkit.de (netcup Webhosting, PHP).
Vorschau ohne Server-Funktionen: https://erltools.github.io/unterrichtstools/

| Tool | Ordner |
| --- | --- |
| Lautstärke-Ampel | `ampel/` |
| Wortwolke | `wortwolke/` (braucht PHP, speichert nur Wörter, löscht nach 24 h) |
| Timer | `timer/` |
| Glücksrad | `gluecksrad/` (Listen nur lokal im Browser) |
| Mathefußball | `mathefussball/` |

Jedes Tool ist eine einzelne HTML-Datei ohne Build-Schritt. Neue Tools bekommen einen eigenen Ordner und eine Kachel auf der Startseite (`index.html`).

Zurück-Link: Jedes Tool bindet `gemeinsam/zurueck.css` ein und setzt `<a class="zurueck" href="../" aria-label="Zurück zur Übersicht">‹ Übersicht</a>` (blass oben links). In einer Kopfzeile stattdessen `class="zurueck im-fluss"`. Während ein Tool läuft, darf das Tool den Link ausblenden (siehe Timer).

Schrift: Atkinson Hyperlegible liegt lokal in `schrift/` (SIL Open Font License), damit keine Verbindung zu Google Fonts entsteht. Neue Tools binden `schrift/schrift.css` ein.

Hochladen: netcup holt `main` über die Git-Verbindung in Plesk automatisch nach `/klassenkit.de/httpdocs` (Webhook in GitHub). Rundendaten der Wortwolke (`wortwolke/daten/*.json`) liegen nicht im Repo und bleiben dabei erhalten.
