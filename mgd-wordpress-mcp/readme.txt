=== MGD WordPress MCP ===
Contributors: MichaelGahnDESIGN
Tags: mcp, ai, abilities-api, automation, divi, wpforms, updraftplus
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://spdx.org/licenses/GPL-2.0-or-later.html

Sichere WordPress-Abilities für MCP-kompatible KI-Agenten. Inhalte, Medien, SEO, Divi 5, WPForms, Backups und Wartung mit expliziten Freigaben.

== Description ==

MGD WordPress MCP registriert klar begrenzte WordPress Abilities und stellt sie über den offiziellen WordPress MCP Adapter für MCP-kompatible Clients bereit.

Die erste Version unterstützt unter anderem:

* WordPress-Beiträge, Seiten und öffentliche Inhaltstypen lesen und bearbeiten
* Medien lesen, hochladen und als Beitragsbild zuweisen
* Rank Math und Yoast SEO-Titel/Meta-Description lesen und schreiben
* experimentelle, revisionsgesicherte Divi-5-Layout-Brücke
* ausgewählte offizielle WPForms-Abilities weiterreichen
* UpdraftPlus-Backups anstoßen
* Plugin- und Theme-Updates nach separater Freigabe ausführen
* Audit-Log für wichtige Aktionen
* GitHub-basierte Plugin-Updates

Schreibzugriffe sind nach der Aktivierung standardmäßig deaktiviert. Divi-Schreibzugriff und Wartungsaktionen besitzen zusätzliche Schalter.

MGD WordPress MCP benötigt WordPress 6.9 oder neuer und den offiziellen WordPress MCP Adapter als separates Plugin.

== Installation ==

1. Installiere und aktiviere den offiziellen WordPress MCP Adapter.
2. Lade die Release-ZIP `mgd-wordpress-mcp.zip` hoch und aktiviere MGD WordPress MCP.
3. Öffne Werkzeuge > MGD WordPress MCP.
4. Prüfe Status, Endpoint und erkannte Integrationen.
5. Erstelle für den gewünschten WordPress-Benutzer ein Application Password.
6. Verbinde einen MCP-kompatiblen Client mit dem angezeigten Endpoint.
7. Aktiviere Schreib-, Divi- oder Wartungsrechte nur, wenn sie wirklich benötigt werden.

== Frequently Asked Questions ==

= Ist das Plugin kostenlos? =

Ja. MGD WordPress MCP steht unter GPL-2.0-or-later. Externe KI-Anbieter, Hosting oder andere Plugins können eigene Kosten verursachen.

= Warum ist der MCP Adapter nicht eingebaut? =

Der offizielle WordPress MCP Adapter soll als separates Plugin installiert werden. Dadurch bleibt genau eine kanonische Adapter-Version aktiv und Versionskonflikte werden vermieden.

= Kann ein Agent sofort alles verändern? =

Nein. Schreibzugriffe, Divi-Schreibzugriffe und Wartungsaktionen sind getrennt und standardmäßig deaktiviert. Zusätzlich gelten die normalen WordPress-Benutzerrechte.

= Kann das Plugin Divi 5 bearbeiten? =

Ja, in Version 0.1.0 über eine bewusst konservative Raw-Layout-Brücke. Vor jedem Speichern wird eine WordPress-Revision angelegt; optional kann der Agent mit `expected_modified_gmt` Konflikte vermeiden. Die Integration gilt zunächst als experimentell, weil keine undokumentierten Divi-Interna als stabile API vorausgesetzt werden.

= Kann das Plugin WPForms verwenden? =

Wenn eine aktuelle WPForms-Version passende WordPress Abilities registriert, kann MGD WordPress MCP ausgewählte offizielle WPForms-Abilities aufrufen. Die Berechtigungen und Schreibfreigaben von WPForms bleiben maßgeblich.

= Kann das Plugin ein UpdraftPlus-Backup erstellen? =

Es kann ein vollständiges Backup über den von UpdraftPlus bereitgestellten Hook anstoßen. Das Backup läuft asynchron; der erfolgreiche Start bedeutet nicht automatisch, dass das Backup bereits vollständig abgeschlossen ist.

= Werden Seiten endgültig gelöscht? =

Nein. Die v0.1.0-Ability für Löschen verschiebt Inhalte nur in den WordPress-Papierkorb und verlangt zusätzlich einen Bestätigungstoken.

== Changelog ==

= 0.1.0 - 2026-09-14 =

* Erste öffentliche MVP-Version.
* WordPress Abilities API und eigener MCP-Server.
* Inhalte, Medien, SEO, Divi 5, WPForms, UpdraftPlus und Wartung.
* Audit-Log und Sicherheitsfreigaben.
* GitHub-Update-Mechanismus und Release-Workflow.
