=== MGD WordPress MCP ===
Contributors: MichaelGahnDESIGN
Tags: mcp, ai, abilities-api, automation, divi, wpforms, updraftplus
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPL-2.0-or-later
License URI: https://spdx.org/licenses/GPL-2.0-or-later.html

Sichere WordPress-Abilities für MCP-kompatible KI-Agenten. Inhalte, Medien, SEO, Divi 5, WPForms, Backups, Builder-Erkennung und Wartung mit expliziten Freigaben.

== Description ==

MGD WordPress MCP registriert klar begrenzte WordPress Abilities und stellt sie über den offiziellen WordPress MCP Adapter für MCP-kompatible Clients bereit.

Version 0.2.0 unterstützt unter anderem:

* WordPress-Beiträge, Seiten und öffentliche Inhaltstypen lesen und bearbeiten
* Medien lesen, hochladen und als Beitragsbild zuweisen
* Rank Math und Yoast SEO-Titel/Meta-Description lesen und schreiben
* experimentelle, revisionsgesicherte Divi-5-Layout-Brücke
* ausgewählte offizielle WPForms-Abilities weiterreichen
* UpdraftPlus-Backups anstoßen
* Plugin- und Theme-Updates nach separater Freigabe ausführen
* Audit-Log für wichtige Aktionen
* GitHub-basierte Plugin-Updates im normalen WordPress-Update-System
* Einrichtungs-Assistent nach Aktivierung
* Builder-Erkennung für Divi, Elementor, Gutenberg, Bricks und Beaver Builder
* Erkennung typischer Frontend-Sperren, ohne PINs oder Passwörter auszulesen oder zu speichern

Schreibzugriffe sind nach der Aktivierung standardmäßig deaktiviert. Divi-Schreibzugriff und Wartungsaktionen besitzen zusätzliche Schalter.

MGD WordPress MCP benötigt WordPress 6.9 oder neuer und den offiziellen WordPress MCP Adapter als separates Plugin.

== Installation ==

1. Installiere und aktiviere den offiziellen WordPress MCP Adapter.
2. Lade die Release-ZIP `mgd-wordpress-mcp.zip` hoch und aktiviere MGD WordPress MCP.
3. Folge dem Einrichtungs-Assistenten unter Werkzeuge > MGD WordPress MCP.
4. Prüfe Builder, mögliche Frontend-Sperren, Status und Endpoint.
5. Erstelle für den gewünschten WordPress-Benutzer ein separates Application Password.
6. Verbinde einen MCP-kompatiblen Client mit dem angezeigten Endpoint.
7. Aktiviere Schreib-, Divi- oder Wartungsrechte nur, wenn sie wirklich benötigt werden.

== Frequently Asked Questions ==

= Ist das Plugin kostenlos? =

Ja. MGD WordPress MCP steht unter GPL-2.0-or-later. Externe KI-Anbieter, Hosting oder andere Plugins können eigene Kosten verursachen.

= Kann ein Agent sofort alles verändern? =

Nein. Schreibzugriffe, Divi-Schreibzugriffe und Wartungsaktionen sind getrennt und standardmäßig deaktiviert. Zusätzlich gelten die normalen WordPress-Benutzerrechte.

= Kann das Plugin Divi 5 bearbeiten? =

Ja, über eine bewusst konservative Raw-Layout-Brücke. Vor dem Speichern wird eine WordPress-Revision angelegt. Die Integration bleibt experimentell und setzt keine undokumentierten Divi-Interna als stabile API voraus.

= Was passiert bei WP Shield oder einer anderen Frontend-Sperre? =

MGD WordPress MCP erkennt typische aktive Security-, Passwort-, Restricted-Access-, Maintenance- und Coming-Soon-Plugins heuristisch. Ein Agent erhält nur den Hinweis, bei einer tatsächlich blockierten visuellen Prüfung nach dem legitimen Entsperrweg oder bei Bedarf nach einer PIN zu fragen. PINs und Passwörter werden nicht ausgelesen oder im Audit-Log gespeichert.

= Kann das Plugin WPForms verwenden? =

Wenn eine aktuelle WPForms-Version passende WordPress Abilities registriert, kann MGD WordPress MCP ausgewählte offizielle WPForms-Abilities aufrufen. Die Berechtigungen und Schreibfreigaben von WPForms bleiben maßgeblich.

= Kann das Plugin ein UpdraftPlus-Backup erstellen? =

Es kann ein vollständiges Backup über den von UpdraftPlus bereitgestellten Hook anstoßen. Das Backup läuft asynchron; der erfolgreiche Start bedeutet nicht automatisch, dass das Backup bereits vollständig abgeschlossen ist.

= Werden Seiten endgültig gelöscht? =

Nein. Die Lösch-Ability verschiebt Inhalte nur in den WordPress-Papierkorb und verlangt zusätzlich einen Bestätigungstoken.

== Changelog ==

= 0.2.0 - 2026-09-15 =

* Einrichtungs-Assistent.
* Builder-Erkennung für Divi, Elementor, Gutenberg, Bricks und Beaver Builder.
* Frontend-Sperren-Erkennung und Agentenhinweise ohne Speicherung von Zugangsdaten.
* MGD Divi 5 Dev Skill im Setup verknüpft.
* Dokumentation, Wiki und rechtliche Angaben erweitert.

= 0.1.0 - 2026-09-14 =

* Erste öffentliche MVP-Version.
* WordPress Abilities API und eigener MCP-Server.
* Inhalte, Medien, SEO, Divi 5, WPForms, UpdraftPlus und Wartung.
* Audit-Log und Sicherheitsfreigaben.
* GitHub-Update-Mechanismus und Release-Workflow.
