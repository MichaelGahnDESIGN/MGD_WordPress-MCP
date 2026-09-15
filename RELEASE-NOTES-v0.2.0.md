# MGD WordPress MCP 0.2.0

Erster öffentlicher Release Candidate von MGD WordPress MCP.

## Highlights

* WordPress Abilities API ab WordPress 6.9
* eigener MCP-Server über den offiziellen WordPress MCP Adapter
* Inhalte und Seiten lesen, erstellen, ändern und in den Papierkorb verschieben
* Medienverwaltung und sicherer URL-Import
* Featured Images
* Rank Math und Yoast SEO
* Divi-5-Integration mit Revisionen und Konfliktprüfung
* WPForms-Bridge
* UpdraftPlus-Backup-Trigger
* Plugin- und Theme-Update-Werkzeuge mit separater Wartungsfreigabe
* Audit-Log
* Einrichtungs-Assistent
* Builder-Erkennung für Divi, Elementor, Gutenberg, Bricks und Beaver Builder
* Erkennung typischer Frontend-Sperren wie Shield-, Passwort-, Maintenance- und Coming-Soon-Lösungen
* GitHub-basierte WordPress-Updates
* ausführliche README, Wiki, Datenschutz- und Sicherheitsdokumentation

## Sicherheit

Schreib-, Divi- und Wartungsrechte sind standardmäßig deaktiviert beziehungsweise getrennt freizuschalten. WordPress-Capabilities bleiben maßgeblich. Kritische Wartungsaktionen verlangen explizite Bestätigungswerte.

Der Base64-Medienupload ist in 0.2.0 fail-closed deaktiviert und wird nicht als MCP-Tool angeboten, bis eine vollständige Dateitypvalidierung implementiert ist.

## Voraussetzungen

* WordPress 6.9 oder neuer
* PHP 7.4 oder neuer
* HTTPS für produktive Installationen empfohlen
* offizieller WordPress MCP Adapter, empfohlen 0.6.1 oder neuer

## Release-Asset

Der Tag muss exakt `v0.2.0` heißen. Der GitHub-Actions-Release-Workflow validiert Tag, Plugin-Version und `Stable tag`, führt einen PHP-Syntaxcheck aus und erzeugt anschließend das WordPress-Installationspaket:

`mgd-wordpress-mcp.zip`

Dieses Asset wird vom integrierten WordPress-Updater erwartet.
