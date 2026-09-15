=== MGD WordPress MCP ===
Contributors: MichaelGahnDESIGN
Tags: mcp, ai, abilities-api, automation, divi, wpforms, updraftplus
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.3.1
License: GPL-2.0-or-later
License URI: https://spdx.org/licenses/GPL-2.0-or-later.html

Sichere WordPress-Abilities für MCP-kompatible KI-Agenten.

== Changelog ==

= 0.3.1 - 2026-09-15 =
* WordPress-Header `Requires Plugins: mcp-adapter` wieder entfernt, da WordPress die vorhandene MCP-Adapter-Installation auf der Testsite nicht zuverlässig diesem Dependency-Slug zuordnete.
* Falsche rote WordPress-Warnung „erforderliche Plugins fehlen“ damit beseitigt.
* Bewährte Laufzeit-Erkennung des offiziellen MCP Adapters bleibt bestehen.
* Fehlt der Adapter tatsächlich, zeigt MGD WordPress MCP weiterhin einen verständlichen Admin-Hinweis mit Installationslink.
* MCP-Server und Abilities werden nur verwendet, wenn die Adapter-API tatsächlich verfügbar ist.
* Keine Änderung am gehärteten 0.3.x Authentifizierungsmodell.

= 0.3.0 - 2026-09-15 =
* Start der realen MCP Read/Write-Validierungsphase.
* MCP-Transport-Permission mit expliziten Authentifizierungs-/Berechtigungsfehlern gehärtet.
* Vorbereitung für echten Read-only Smoke-Test und kontrollierten Draft-Write-Test.
* 0.2.x abgeschlossen: Admin-UI, Branding, native Details, Release-Automation und GitHub-Self-Updater stehen.

= 0.2.12 - 2026-09-15 =
* Native WordPress-Detailansicht verdichtet und informativer aufgebaut.

= 0.2.11 - 2026-09-15 =
* Premium-Tech-Banner und Roboter-Icon in der Pluginliste.

= 0.2.10 - 2026-09-15 =
* „Details ansehen“ öffnet das native WordPress Plugin-Information-Modal.

= 0.2.8 - 2026-09-15 =
* Self-Updater erfolgreich End-to-End verifiziert.
