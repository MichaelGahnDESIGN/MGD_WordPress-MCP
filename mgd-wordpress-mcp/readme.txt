=== MGD WordPress MCP ===
Contributors: MichaelGahnDESIGN
Tags: mcp, ai, abilities-api, automation, divi, wpforms, updraftplus
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.5.5
License: GPL-2.0-or-later
License URI: https://spdx.org/licenses/GPL-2.0-or-later.html

Sichere WordPress-Abilities für MCP-kompatible KI-Agenten.

== Changelog ==

= 0.5.5 - 2026-09-16 =
* Neuer Tab „Start-Prompt“ direkt an zweiter Position der Plugin-Navigation.
* Start-Prompt enthält Repository-Link, aktuellen Website-MCP-Endpunkt, aktives Sicherheitsprofil, Sicherheitsregeln und einen Platzhalter für den konkreten Arbeitsauftrag.
* Start-Prompt kann mit einem Klick kopiert werden und enthält bewusst keine Zugangsdaten.
* Sicherheitsprofile im Einrichtungs-Assistenten sind jetzt echte auswählbare Formulare statt nur optischer Karten/Links.
* Profil „Nur lesen“ deaktiviert Schreib-, Divi-, Medienimport- und Wartungsrechte automatisch.
* Profil „Inhalte bearbeiten“ aktiviert Schreib-, Divi- und Medienrechte, lässt Wartung deaktiviert.
* Profil „Administration“ aktiviert zusätzlich Wartungsrechte; kritische Aktionen bleiben an weitere Schutzmechanismen gebunden.
* Einzelrechte bleiben unter „Sicherheit & Freigaben“ nachträglich fein einstellbar.
* Connection-Doctor-Link im Wizard auf den tatsächlich vorhandenen Verbindungstab korrigiert.

= 0.5.4 - 2026-09-16 =
* Client Setup Generator auf den dokumentierten WordPress Remote-Proxy umgestellt.
* Connection Doctor prüft den tatsächlichen MCP-Endpunkt und Cache-Hinweise.

= 0.5.3 - 2026-09-16 =
* Kritischen Laufzeitfehler im 0.5.2 Einrichtungs-Assistenten behoben.

= 0.5.1 - 2026-09-16 =
* Connection Doctor, Sicherheitsprofile, Rate Limit, Approval Tokens, Secret Redaction und Client Setup Infrastruktur ergänzt.

= 0.2.8 - 2026-09-15 =
* Self-Updater erfolgreich End-to-End verifiziert.
