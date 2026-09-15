# Security & Release Readiness Audit

Stand: 15. September 2026

## Ergebnis

MGD WordPress MCP besitzt bereits ein solides Sicherheitsgrundgerüst, ist in Version 0.2.0 aber **noch nicht als produktionsreifes Release freigegeben**. Vor dem ersten öffentlichen Release müssen die unten als Blocker markierten Punkte abgeschlossen und auf einer echten WordPress-Testinstallation geprüft werden.

## Geprüfte Bereiche

### Berechtigungen

Positiv:

- Schreibzugriff ist standardmäßig deaktiviert.
- Divi-Schreibzugriff und Wartungsaktionen besitzen eigene Freigaben.
- WordPress-Capabilities werden zusätzlich geprüft.
- Inhaltslöschung nutzt nur den Papierkorb.
- Plugin- und Theme-Updates verlangen explizite Bestätigungswerte.
- Divi-Schreibvorgänge verwenden Revisionen und unterstützen Konfliktprüfung.

### Authentifizierung

MGD WordPress MCP speichert keine Application Passwords. Die Authentifizierung wird an WordPress beziehungsweise den offiziellen WordPress MCP Adapter delegiert.

Für produktive Websites gelten als Mindeststandard HTTPS, ein eigener MCP-Benutzer, minimale Rollenrechte und ein eigenes widerrufbares Application Password.

### Frontend-Sperren

Die Erkennung typischer Shield-, Passwort-, Restricted-Access-, Maintenance- und Coming-Soon-Plugins ist rein diagnostisch. PINs und Passwörter werden nicht ausgelesen. Agenten sollen bei einer tatsächlichen Blockade nach dem legitimen Entsperrweg fragen.

### Audit-Log

Das Audit-Log ist lokal und größenbegrenzt. Es protokolliert technische Aktionsdaten. Geheimnisse dürfen nicht in Zusammenfassungen geschrieben werden.

### Datenschutz

Das Plugin enthält keine Telemetrie, Werbung oder Tracker. GitHub wird nur für die optionale Updateprüfung kontaktiert. Welche Website-Daten an einen KI-Anbieter übertragen werden, hängt vom verbundenen MCP-Client und dessen Auftrag ab.

Eine optionale vollständige Datenlöschung bei Deinstallation ist über `MGD_WPMCP_PURGE_ON_UNINSTALL` vorgesehen.

## Release- und Update-System

Der WordPress-Updater prüft `releases/latest` im öffentlichen GitHub-Repository und akzeptiert ausschließlich das Release-Asset `mgd-wordpress-mcp.zip`.

Der Release-Workflow prüft vor dem Bau:

- Git-Tag entspricht Plugin-Version,
- Git-Tag entspricht `Stable tag`,
- alle PHP-Dateien bestehen `php -l`,
- die installierbare ZIP enthält den stabilen Ordner `mgd-wordpress-mcp`.

**Wichtig:** Ohne veröffentlichtes GitHub Release kann WordPress kein neues Update finden. Zum Zeitpunkt dieses Audits existiert noch kein Release. Der erste produktive Release muss deshalb als Tag/Release `v0.2.0` mit dem Asset `mgd-wordpress-mcp.zip` veröffentlicht werden.

## BLOCKER 1: Base64-Medienupload härten

Die Ability `upload-media-base64` prüft aktuell den vom Client angegebenen MIME-Typ gegen die WordPress-Liste erlaubter MIME-Typen. Vor einer produktiven Freigabe muss zusätzlich der tatsächliche Dateityp anhand der Dateidaten beziehungsweise der erzeugten temporären Datei geprüft und gegen Dateiendung und erlaubten MIME-Typ abgeglichen werden.

Bis diese Prüfung implementiert und getestet ist, sollte `upload-media-base64` auf produktiven Kundenwebsites **nicht verwendet werden**.

Empfohlene technische Lösung:

1. Base64 dekodieren.
2. Daten in eine temporäre Datei schreiben.
3. `wp_check_filetype_and_ext()` beziehungsweise die WordPress-Upload-Pipeline verwenden.
4. erkannte Endung und MIME-Typ gegen `get_allowed_mime_types()` prüfen.
5. bei Abweichung oder unbekanntem Typ abbrechen.
6. erst danach in die Mediathek übernehmen.

## BLOCKER 2: Realer Integrationstest

Vor dem ersten produktiven Release muss eine frische Testinstallation mindestens folgende Matrix durchlaufen:

- Aktivierung WordPress 6.9+
- Deaktivierung und Reaktivierung
- Setup-Assistent
- MCP Adapter vorhanden / nicht vorhanden
- Read-only MCP-Benutzer
- Editor mit Schreibfreigabe
- Administrator mit Wartungsfreigabe
- falsches und widerrufenes Application Password
- HTTP statt HTTPS Warnfall
- Beitrag lesen, Entwurf erstellen und ändern
- Papierkorb-Aktion
- Medienimport per URL
- Base64-Upload nach dessen Härtung
- Rank Math und Yoast getrennt
- Divi lesen und revisionsgesichert ändern
- WPForms read/write abhängig von dessen Freigabe
- UpdraftPlus Backup-Trigger
- Plugin-Update mit und ohne Bestätigung
- Theme-Update mit und ohne Bestätigung
- Frontend-Sperre
- Audit-Log
- Update von einer älteren Testversion auf 0.2.0

## BLOCKER 3: Updateanzeige praktisch testen

Nach Veröffentlichung von `v0.2.0` muss mit einer installierten älteren Testversion geprüft werden, dass WordPress unter **Dashboard → Aktualisierungen** und **Plugins** die neue Version meldet und die ZIP korrekt über den integrierten WordPress-Upgrader installiert.

## Nicht als Sicherheitsgarantie verstehen

Dieses Audit ist ein Code- und Architekturreview des aktuellen Repository-Stands. Es ersetzt keinen Penetrationstest und keinen realen WordPress-Integrationstest. Insbesondere die Kombination aus WordPress-Version, MCP Adapter, Hosting, Security-Plugins und externem MCP-Client muss vor dem Einsatz auf Kundenwebsites praktisch getestet werden.
