=== MGD WordPress MCP ===
Contributors: MichaelGahnDESIGN
Tags: mcp, ai, abilities-api, automation, divi, wpforms, updraftplus
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.5.2
License: GPL-2.0-or-later
License URI: https://spdx.org/licenses/GPL-2.0-or-later.html

Sichere WordPress-Abilities für MCP-kompatible KI-Agenten.

== Changelog ==

= 0.5.2 - 2026-09-16 =
* 0.5.1 Sicherheits- und Verbindungsfunktionen vollständig in die Admin-Oberfläche integriert.
* Einrichtungs-Assistent als verständlichen 5-Schritte-Workflow neu aufgebaut.
* Bereits installierter MCP Adapter wird erkannt und nicht mehr unnötig zur Installation angeboten.
* Connection Doctor als eigene, sichtbare Verbindungsseite mit Einzelprüfungen und Gesamtstatus integriert.
* Sicherheitsprofile „Nur lesen“, „Inhalte bearbeiten“ und „Administration“ direkt bedienbar gemacht.
* Feingranulare Berechtigungen bleiben zusätzlich verfügbar.
* Client Setup Generator für Claude Code, Codex, Cursor/VS Code und generische MCP-Clients integriert.
* Application-Password-Erstellung verständlich als separater Agentenzugang erklärt.
* Endpoint, Profil, Builder und Frontend-Schutz verständlicher dargestellt.
* Rate-Limit-Einstellung in die Sicherheitsoberfläche integriert.
* Responsive, lokale Admin-UI für Profile, Connection Doctor, Setup-Schritte und Client-Konfiguration ergänzt.
* Keine externen Fonts, Icon-CDNs oder JavaScript-CDNs hinzugefügt.

= 0.5.1 - 2026-09-16 =
* Connection Doctor, Sicherheitsprofile, Rate Limit, Approval Tokens, Secret Redaction und Client Setup Infrastruktur ergänzt.
* Read-only entfernt schreibende Abilities aus der MCP-Tool-Liste.
* Unsicherer Base64-Direktupload bleibt fail-closed deaktiviert.

= 0.3.1 - 2026-09-15 =
* Falsche harte WordPress-Abhängigkeit zum MCP Adapter entfernt.

= 0.2.8 - 2026-09-15 =
* Self-Updater erfolgreich End-to-End verifiziert.
