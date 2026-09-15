# Security & Release Readiness Audit

Stand: 15. September 2026

## Ergebnis

MGD WordPress MCP 0.2.0 ist auf Code-Ebene als **Release Candidate** vorbereitet. Der zuvor beanstandete Base64-Medienupload ist fail-closed deaktiviert und wird weder als WordPress Ability noch als MCP-Tool angeboten. Die GitHub-CI prüft die PHP-Syntax auf PHP 7.4, 8.1, 8.3 und 8.4 sowie die Konsistenz von Plugin-Version und `Stable tag`.

Ein realer End-to-End-Test auf einer WordPress-Testinstallation bleibt zusätzlich empfohlen, weil Hosting, WordPress MCP Adapter, Security-Plugins und Builder-Plugins nicht vollständig durch statische Codeprüfung simuliert werden können.

## Sicherheitsstatus

Schreibzugriff ist standardmäßig deaktiviert. Divi-Schreibzugriff und Wartungsaktionen besitzen eigene Freigaben. WordPress-Capabilities werden zusätzlich geprüft. Inhaltslöschung verwendet nur den Papierkorb. Plugin- und Theme-Updates verlangen explizite Bestätigungswerte. Divi-Schreibvorgänge verwenden Revisionen und unterstützen Konfliktprüfung.

MGD WordPress MCP speichert keine Application Passwords. Die Authentifizierung wird an WordPress und den offiziellen WordPress MCP Adapter delegiert. Für produktive Websites gelten HTTPS, ein eigener MCP-Benutzer, minimale Rollenrechte und ein widerrufbares Application Password als empfohlener Mindeststandard.

Die Erkennung typischer Shield-, Passwort-, Restricted-Access-, Maintenance- und Coming-Soon-Plugins ist rein diagnostisch. PINs und Passwörter werden nicht ausgelesen oder protokolliert.

Das Audit-Log ist lokal und größenbegrenzt. Das Plugin enthält keine Telemetrie, Werbung oder Tracker. GitHub wird nur für die optionale Updateprüfung kontaktiert. Eine vollständige Datenlöschung bei Deinstallation ist über `MGD_WPMCP_PURGE_ON_UNINSTALL` möglich.

## Base64-Medienupload

Die Ability `mgd-wordpress-mcp/upload-media-base64` ist in Version 0.2.0 aus Sicherheitsgründen vollständig deaktiviert. Die zentrale Security-Klasse deregistriert sie nach der Ability-Registrierung und entfernt sie zusätzlich aus der MCP-Tool-Liste. Damit ist der bekannte MIME-/Dateityp-Risikopfad fail-closed und extern nicht erreichbar.

Eine spätere Version darf diese Ability erst wieder aktivieren, wenn tatsächlicher Dateityp, Dateiendung und erlaubter MIME-Typ über die WordPress-Upload-Pipeline verifiziert werden.

## CI

Der Workflow `.github/workflows/ci.yml` läuft bei Änderungen an `main` und bei Pull Requests. Er prüft PHP 7.4, 8.1, 8.3 und 8.4. Zusätzlich werden Plugin-Version und `Stable tag` abgeglichen.

Der erste vollständige CI-Lauf für 0.2.0 wurde erfolgreich abgeschlossen.

## Release- und Update-System

Der WordPress-Updater prüft `releases/latest` im öffentlichen GitHub-Repository und akzeptiert ausschließlich das Release-Asset `mgd-wordpress-mcp.zip`.

Der Release-Workflow prüft vor dem Bau:

* Git-Tag entspricht Plugin-Version
* Git-Tag entspricht `Stable tag`
* alle PHP-Dateien bestehen `php -l`
* die installierbare ZIP enthält den stabilen Ordner `mgd-wordpress-mcp`

Für Version 0.2.0 muss der Release-Tag `v0.2.0` heißen. Der Workflow erzeugt daraus das Asset `mgd-wordpress-mcp.zip`. Erst ein veröffentlichtes GitHub Release ermöglicht dem integrierten Updater, diese Version über WordPress zu verteilen.

## Empfohlener End-to-End-Test

Vor dem Einsatz auf vielen Kundenwebsites sollte mindestens eine echte WordPress-6.9+-Installation folgende Punkte prüfen: Aktivierung, Setup-Assistent, MCP Adapter 0.6.1 oder neuer, Application Password, Read-only-Zugriff, Schreibfreigabe, Entwurf erstellen, Medienimport per URL, SEO, Divi-Lesen und revisionsgesichertes Schreiben, WPForms, UpdraftPlus, Updateprüfung, Frontend-Sperren und Audit-Log.

Der offizielle WordPress MCP Adapter 0.6.1 ist zum Zeitpunkt dieses Audits die aktuelle stabile Version und setzt WordPress 6.9 oder neuer voraus.

## Einordnung

Dieses Audit ist ein Code-, Architektur- und CI-Review. Es ist keine Garantie gegen jede Sicherheitslücke und ersetzt keinen Penetrationstest. Sicherheitsrelevante Änderungen sollten weiterhin über `SECURITY.md` gemeldet werden.
