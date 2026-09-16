=== MGD WordPress MCP ===
Contributors: MichaelGahnDESIGN
Tags: mcp, ai, abilities-api, automation, divi, wpforms, updraftplus
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.5.4
License: GPL-2.0-or-later
License URI: https://spdx.org/licenses/GPL-2.0-or-later.html

Sichere WordPress-Abilities für MCP-kompatible KI-Agenten.

== Changelog ==

= 0.5.4 - 2026-09-16 =
* Client Setup Generator auf den von WordPress dokumentierten Remote-Proxy `@automattic/mcp-wordpress-remote@latest` umgestellt.
* Konfigurationen verwenden `WP_API_URL`, `WP_API_USERNAME` und `WP_API_PASSWORD` statt selbstgebauter direkter Authorization-Header.
* Connection Doctor prüft den tatsächlichen MCP-Endpunkt jetzt per unauthentifiziertem Netzwerk-Request.
* Erwartete 400/401/403/405-Antworten werden als Beleg gewertet, dass die Route erreichbar ist und Authentifizierung erzwingt.
* Cache-Header bekannter Full-Page-Caches werden erkannt und als Sicherheits-/Zuverlässigkeitshinweis ausgegeben.
* Frontend-Schutz wird nicht mehr als MCP-Fehler gewertet, sondern als separater Hinweis für visuelle Browser-Prüfungen.
* Doctor unterscheidet lokale Voraussetzungen, Serverregistrierung und echte Endpoint-Erreichbarkeit klarer.
* Authentifizierung, MCP `initialize` und `tools/list` werden bewusst erst mit einem echten Client und einem frischen Application Password als validiert markiert.

= 0.5.3 - 2026-09-16 =
* Kritischen Laufzeitfehler im 0.5.2 Einrichtungs-Assistenten behoben.
* Wizard an die tatsächlich vorhandenen Doctor-, Client-Config- und Security-APIs angepasst.

= 0.5.2 - 2026-09-16 =
* Sicherheits- und Verbindungsfunktionen in die Admin-Oberfläche integriert.

= 0.5.1 - 2026-09-16 =
* Connection Doctor, Sicherheitsprofile, Rate Limit, Approval Tokens, Secret Redaction und Client Setup Infrastruktur ergänzt.

= 0.2.8 - 2026-09-15 =
* Self-Updater erfolgreich End-to-End verifiziert.
