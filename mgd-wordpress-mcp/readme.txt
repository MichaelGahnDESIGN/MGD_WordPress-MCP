=== MGD WordPress MCP ===
Contributors: MichaelGahnDESIGN
Tags: mcp, ai, abilities-api, automation, divi, wpforms, updraftplus
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.5.1
License: GPL-2.0-or-later
License URI: https://spdx.org/licenses/GPL-2.0-or-later.html

Sichere WordPress-Abilities für MCP-kompatible KI-Agenten.

== Changelog ==

= 0.5.1 - 2026-09-16 =
* Connection-Doctor-Kern für WordPress, PHP, HTTPS, REST, Permalinks, Abilities API, MCP Adapter, Frontend-Schutz und Endpoint ergänzt.
* Berechtigungsprofile `read_only`, `content_editor` und `admin` als zentrale Sicherheitsgrundlage ergänzt; bestehende Installationen starten konservativ in Read-only.
* Read-only-Profil entfernt schreibende und administrative Abilities bereits aus der MCP-Tool-Liste.
* Transport-Rate-Limit mit HTTP 429 ergänzt.
* Kurzlebige, aktions- und objektgebundene Single-Use Approval Tokens als Infrastruktur ergänzt.
* Zentrale Secret-Redaction-Hilfe für Audit-/Diagnosedaten ergänzt.
* Client-Konfigurationsgenerator als Infrastruktur für Claude Code, Codex und generische HTTP-MCP-Clients ergänzt.
* Vorhandene Fail-closed-Sperre für unsicheren Base64-Direktupload beibehalten.
* Laufzeit-Erkennung des offiziellen MCP Adapters bleibt bestehen, keine fehleranfällige harte WordPress-Plugin-Abhängigkeit.
* 0.5.1 ist ein Integrations-/Härtungsrelease. Drittanbieter-Workflows wie Divi, WPForms, SEO und UpdraftPlus müssen weiterhin auf der realen Testsite End-to-End validiert werden.

= 0.3.1 - 2026-09-15 =
* Falsche harte WordPress-Abhängigkeit zum MCP Adapter entfernt.

= 0.3.0 - 2026-09-15 =
* MCP-Authentifizierungsmodell gehärtet und Read/Write-Validierungsphase gestartet.

= 0.2.8 - 2026-09-15 =
* Self-Updater erfolgreich End-to-End verifiziert.
