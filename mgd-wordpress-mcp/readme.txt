=== MGD WordPress MCP ===
Contributors: MichaelGahnDESIGN
Tags: mcp, ai, abilities-api, automation, divi, wpforms, updraftplus
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.5.3
License: GPL-2.0-or-later
License URI: https://spdx.org/licenses/GPL-2.0-or-later.html

Sichere WordPress-Abilities für MCP-kompatible KI-Agenten.

== Changelog ==

= 0.5.3 - 2026-09-16 =
* Kritischen Laufzeitfehler im neuen 0.5.2 Einrichtungs-Assistenten behoben.
* Wizard verwendet jetzt die tatsächlich vorhandene Connection-Doctor-API `summary()` statt einer nicht existierenden `run()`-Methode.
* Wizard nutzt den vorhandenen Client-Konfigurationsgenerator `MGD_WordPress_MCP_Client_Config` statt einer nicht existierenden Klasse.
* Sicherheitsprofil-Bezeichnungen werden kompatibel zur bestehenden Security-API dargestellt.
* Release bleibt fail-safe über PHP-Syntaxprüfung geschützt.

= 0.5.2 - 2026-09-16 =
* 0.5.1 Sicherheits- und Verbindungsfunktionen in die Admin-Oberfläche integriert.
* Einrichtungs-Assistent als verständlichen 5-Schritte-Workflow neu aufgebaut.
* Connection Doctor, Sicherheitsprofile und Client Setup UX ergänzt.

= 0.5.1 - 2026-09-16 =
* Connection Doctor, Sicherheitsprofile, Rate Limit, Approval Tokens, Secret Redaction und Client Setup Infrastruktur ergänzt.
* Read-only entfernt schreibende Abilities aus der MCP-Tool-Liste.
* Unsicherer Base64-Direktupload bleibt fail-closed deaktiviert.

= 0.3.1 - 2026-09-15 =
* Falsche harte WordPress-Abhängigkeit zum MCP Adapter entfernt.

= 0.2.8 - 2026-09-15 =
* Self-Updater erfolgreich End-to-End verifiziert.
