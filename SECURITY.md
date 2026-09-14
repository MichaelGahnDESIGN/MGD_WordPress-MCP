# Security Policy

MGD WordPress MCP kann mit administrativen WordPress-Funktionen verbunden werden. Sicherheitsmeldungen werden deshalb ernst genommen.

## Unterstützte Versionen

Während der frühen Entwicklung wird ausschließlich die jeweils aktuelle Release-Version unterstützt.

## Sicherheitslücke melden

Bitte **kein öffentliches GitHub Issue** für eine noch nicht behobene Sicherheitslücke eröffnen.

Kontakt:

- Website: https://Michael-Gahn.de
- E-Mail: Anfrage@Michael-Gahn.de
- Impressum: https://Michael-Gahn.de/impressum

Bitte beschreibe nach Möglichkeit:

- betroffene Plugin-Version
- WordPress- und PHP-Version
- erforderliche Benutzerrolle/Berechtigung
- reproduzierbare Schritte
- erwartetes und tatsächliches Verhalten
- mögliche Auswirkung

Keine echten Kundenzugangsdaten, Application Passwords oder personenbezogenen Daten mitsenden.

## Sicherheitsprinzipien

- Schreibzugriff standardmäßig deaktiviert
- Maintenance-/Update-Zugriff separat deaktiviert
- Divi-Schreibzugriff separat deaktiviert
- WordPress Capability Checks bleiben maßgeblich
- Application Passwords werden nicht durch dieses Plugin gespeichert
- keine permanente Lösch-Ability in Version 0.1.x
- riskante Update-Tools verlangen explizite Bestätigungswerte
- Audit-Log lokal in WordPress
- vor Divi-Schreibzugriffen werden Revisionen angelegt
