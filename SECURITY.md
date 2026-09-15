# Security Policy

MGD WordPress MCP kann mit administrativen WordPress-Funktionen verbunden werden. Sicherheitsmeldungen werden deshalb ernst genommen.

## Unterstützte Versionen

Während der frühen Entwicklung wird ausschließlich die jeweils aktuelle Release-Version unterstützt.

## Sicherheitslücke melden

Bitte **kein öffentliches GitHub Issue** für eine noch nicht behobene Sicherheitslücke eröffnen.

Kontakt:

E-Mail: Anfrage@Michael-Gahn.de  
Website: https://Michael-Gahn.de

Bitte beschreibe nach Möglichkeit die betroffene Plugin-Version, WordPress- und PHP-Version, erforderliche Benutzerrolle, reproduzierbare Schritte und die mögliche Auswirkung. Sende keine echten Kundenzugangsdaten, Application Passwords, API-Keys oder personenbezogenen Daten mit.

## Sicherheitsprinzipien

Schreibzugriff ist standardmäßig deaktiviert. Maintenance-/Update-Zugriff und Divi-Schreibzugriff sind zusätzlich separat deaktiviert. WordPress Capability Checks bleiben maßgeblich.

Application Passwords werden nicht durch MGD WordPress MCP gespeichert. Für produktive Installationen wird HTTPS und ein separater WordPress-Benutzer mit minimal erforderlichen Rechten empfohlen.

Destruktive Inhaltsaktionen verschieben Inhalte nur in den Papierkorb. Riskante Wartungsaktionen benötigen explizite Bestätigungswerte. Vor Divi-Schreibzugriffen werden WordPress-Revisionen angelegt und Konfliktprüfungen können über den Änderungszeitpunkt erfolgen.

Das Audit-Log speichert technische Aktionsinformationen lokal. PINs, Passwörter und API-Schlüssel dürfen nicht protokolliert werden.

## Frontend-Sperren

Die Erkennung von Shield-, Passwort-, Maintenance- und ähnlichen Plugins ist ausschließlich eine Diagnosefunktion. Sie versucht nicht, Zugangsschutz zu umgehen oder Zugangsdaten auszulesen. Ein Agent soll bei einer tatsächlichen Blockade den autorisierten Nutzer nach dem legitimen Entsperrweg fragen.

## Updates

Der integrierte GitHub-Updater akzeptiert ausschließlich ein Release-Asset mit dem exakten Namen `mgd-wordpress-mcp.zip`. Releases sollten über den versionierten GitHub-Actions-Workflow gebaut werden. Vor Veröffentlichung eines Releases müssen Plugin-Version, `Stable tag` und Git-Tag übereinstimmen.

## Datenschutz

Weitere Hinweise stehen in `wiki/Datenschutz-und-Rechtliches.md`.
