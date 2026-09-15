# Datenschutz und Rechtliches

MGD WordPress MCP enthält **keine Telemetrie, keine Werbung und keine externen Tracker**. Das Plugin sendet nicht selbstständig Website-Inhalte an einen KI-Anbieter.

## Welche Daten verarbeitet das Plugin lokal?

Das Plugin speichert seine Konfiguration in WordPress-Optionen. Zusätzlich kann ein lokales Audit-Log in einer eigenen WordPress-Datenbanktabelle geführt werden.

Das Audit-Log enthält insbesondere Zeitpunkt, WordPress-Benutzer-ID, aufgerufene Ability, Risikoklasse, betroffenen Objekttyp beziehungsweise Objekt-ID, Erfolgsstatus und eine technische Kurzbeschreibung.

MGD WordPress MCP ist ausdrücklich nicht dafür vorgesehen, Prompts, vollständige Seiteninhalte, Application Passwords, API-Keys, Frontend-PINs oder andere Geheimnisse im Audit-Log abzulegen.

## Externe Verbindungen des Plugins

Wenn GitHub-Updates aktiviert sind, ruft WordPress periodisch die öffentliche GitHub Releases API für `MichaelGahnDESIGN/MGD_WordPress-MCP` ab. Dabei gelten die Datenschutzbedingungen des jeweiligen Hosters und von GitHub. Diese Prüfung kann in den Plugin-Einstellungen deaktiviert werden.

Der MCP-Verkehr selbst erfolgt zwischen dem vom Betreiber ausgewählten MCP-Client und der WordPress-Installation. Der offizielle WordPress MCP Adapter ist eine getrennte Komponente.

## KI-Anbieter und MCP-Clients

Welche Website-Daten an OpenAI, Anthropic oder einen anderen Anbieter übertragen werden, hängt vom verwendeten MCP-Client, Modell, konkreten Auftrag und dessen Datenschutzkonfiguration ab. Das WordPress-Plugin kann diese externe Verarbeitung nicht pauschal kontrollieren.

Bei Kundenwebsites sollte deshalb vor der Freigabe geprüft werden:

1. welcher WordPress-Benutzer für MCP verwendet wird,
2. welche Inhalte dieser Benutzer lesen darf,
3. welche Schreibfunktionen im Plugin aktiviert sind,
4. ob personenbezogene oder vertrauliche Kundendaten verarbeitet werden,
5. welche Verträge und Datenschutzbedingungen für den eingesetzten KI-Dienst gelten.

Das Prinzip der Datenminimierung sollte auch für Agenten gelten. Ein Agent sollte nur die Inhalte abrufen, die für den konkreten Auftrag erforderlich sind.

## Zugangsdaten

Application Passwords, API-Keys, PINs und Passwörter gehören nicht in GitHub-Repositories, Skills oder Audit-Logs. Verwende nach Möglichkeit den Secret-Speicher des jeweiligen MCP-Clients.

Für MCP empfiehlt sich ein eigener WordPress-Benutzer mit minimal erforderlichen Rechten und ein ausschließlich dafür verwendetes Application Password. Wird ein Client nicht mehr benötigt, sollte das Application Password in WordPress widerrufen werden.

## Frontend-Sperren

MGD WordPress MCP kann typische aktive Security-, Passwort-, Restricted-Access-, Maintenance- und Coming-Soon-Plugins heuristisch erkennen. Die Erkennung liest keine PIN oder kein Passwort aus.

Wenn ein Agent bei einer visuellen Prüfung auf eine Sperre stößt, soll er den Nutzer nach dem legitimen Entsperrweg oder nur bei tatsächlichem Bedarf nach einer PIN fragen. Solche Zugangsdaten dürfen nicht im Audit-Log gespeichert werden.

## Löschen der Plugin-Daten

Standardmäßig bleiben Konfiguration und Audit-Log bei einer Deinstallation erhalten, um Sicherheitsnachweise nicht versehentlich zu vernichten.

Für eine vollständige Löschung kann vor der Deinstallation in `wp-config.php` gesetzt werden:

```php
define( 'MGD_WPMCP_PURGE_ON_UNINSTALL', true );
```

Dann entfernt `uninstall.php` die MGD-Optionen und die lokale Audit-Tabelle.

## Lizenz

MGD WordPress MCP steht unter **GPL-2.0-or-later**. Siehe `LICENSE` im Repository.

## Impressum gemäß § 5 DDG

**Michael Gahn DESIGN**  
Inhaber: Michael Gahn  
Dr.-Theodor-Brugsch-Str. 12  
08529 Plauen  
Deutschland

Telefon: +49 (0) 151 59156639  
E-Mail: Anfrage@Michael-Gahn.de  
Website: https://Michael-Gahn.de

Umsatzsteuer-Identifikationsnummer gemäß § 27a Umsatzsteuergesetz: **DE288143343**  
Steuernummer: **223/222/02451**

## Keine Rechtsberatung

Diese Dokumentation beschreibt technische Sicherheits- und Datenschutzprinzipien. Sie ersetzt keine individuelle rechtliche Prüfung einer konkreten Kundeninstallation oder der Nutzung eines bestimmten KI-Anbieters.
