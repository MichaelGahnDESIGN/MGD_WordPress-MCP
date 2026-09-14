# Changelog

Alle wesentlichen Änderungen an **MGD WordPress MCP** werden hier dokumentiert.

## [0.2.0] - 2026-09-15

### Hinzugefügt

- Einrichtungs-Assistent nach der Aktivierung
- Erkennung von Divi, Elementor, Gutenberg, Bricks und Beaver Builder
- frei benennbarer alternativer Builder
- Empfehlung des MGD Divi 5 Dev Skill bei Divi-Projekten
- Erkennung typischer Security-, Shield-, Passwort-, Maintenance- und Restricted-Access-Plugins
- eigener read-only MCP-Kontext für Builder und Frontend-Schutz
- klare Agentenanweisung, bei blockierter visueller Prüfung den berechtigten Nutzer nach Entsperrweg oder bei Bedarf PIN zu fragen
- keine Speicherung oder Protokollierung von PINs und Frontend-Passwörtern
- neue Wiki-Seiten für Assistent, Frontend-Sperren und Builder/Skills

### Geändert

- README umfassend um Einrichtung, Builder, Frontend-Schutz und passende MGD-Projekte erweitert
- Statusseite zeigt Builder und potenziellen Frontend-Schutz
- Plugin-Version auf 0.2.0 angehoben

## [0.1.0] - 2026-09-14

### Hinzugefügt

- erster öffentlicher Plugin-Kern auf Basis der WordPress Abilities API
- direkter MGD-MCP-Server über den offiziellen WordPress MCP Adapter
- WordPress-Inhalte lesen, erstellen, aktualisieren und in den Papierkorb verschieben
- Medien lesen, importieren, hochladen und als Beitragsbild setzen
- Rank Math und Yoast SEO lesen und schreiben
- konservative Divi-Bridge mit Revisionen und Konfliktschutz
- WPForms-Bridge zu offiziellen WPForms-Abilities
- UpdraftPlus-Backup-Trigger
- Plugin- und Theme-Updateprüfung sowie Einzelupdates mit Bestätigung
- lokales Audit-Log
- GitHub Release Updater
- GPL-2.0-or-later
