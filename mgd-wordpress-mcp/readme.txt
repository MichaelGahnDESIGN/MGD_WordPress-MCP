=== MGD WordPress MCP ===
Contributors: MichaelGahnDESIGN
Tags: mcp, ai, abilities-api, automation, divi, wpforms, updraftplus
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPL-2.0-or-later
License URI: https://spdx.org/licenses/GPL-2.0-or-later.html

Sichere WordPress-Abilities für MCP-kompatible KI-Agenten. Inhalte, Medien, SEO, Divi 5, WPForms, Backups und Wartung mit verständlicher Einrichtung und expliziten Freigaben.

== Description ==

MGD WordPress MCP verbindet WordPress kontrolliert mit MCP-kompatiblen KI-Agenten. Die Admin-Oberfläche arbeitet privacy-first: keine extern geladenen Fonts, Icon-CDNs, JavaScript-CDNs oder eingebetteten Drittanbieter-Ressourcen.

Schreibzugriffe sind standardmäßig deaktiviert. Divi-Schreibzugriff und Wartungsaktionen besitzen zusätzliche Freigaben.

== Installation ==

1. Release-ZIP `mgd-wordpress-mcp.zip` installieren und aktivieren.
2. Dem Einrichtungs-Assistenten folgen.
3. Falls der offizielle WordPress MCP Adapter fehlt, erklärt der Assistent Download, Upload und Aktivierung Schritt für Schritt.
4. Separates Application Password im WordPress-Benutzerprofil erstellen.
5. MCP-Client verbinden.
6. Schreib-, Divi- oder Wartungsrechte nur bei Bedarf aktivieren.

== Frequently Asked Questions ==

= Lädt die Plugin-Oberfläche externe Ressourcen? =

Nein. Die Darstellung nutzt lokale Plugin-Assets und den WordPress-System-Fontstack. Externe Links werden erst geöffnet, wenn der Benutzer sie anklickt. Die optionale GitHub-Updateprüfung ist eine getrennte Server-zu-Server-Funktion und kann deaktiviert werden.

= Kann ein Agent sofort alles verändern? =

Nein. Schreibzugriffe, Divi-Schreibzugriffe und Wartungsaktionen sind getrennt und standardmäßig deaktiviert. Zusätzlich gelten die WordPress-Benutzerrechte.

== Changelog ==

= 0.2.1 - 2026-09-15 =

* Modernisierte und vereinfachte Admin-Oberfläche vorbereitet.
* Lokales MGD-MCP-Plugin-Icon und lokale KI-/Vibecoding-Herografik.
* Privacy-by-default: keine extern geladenen UI-Fonts, Icon-CDNs oder eingebetteten Drittanbieter-Ressourcen.
* Plugin-Metadaten und Ressourcenlinks verbessert.
* Einrichtungsablauf und MCP-Adapter-Hinweise werden verständlicher dargestellt.

= 0.2.0 - 2026-09-15 =

* Einrichtungs-Assistent.
* Builder-Erkennung für Divi, Elementor, Gutenberg, Bricks und Beaver Builder.
* Frontend-Sperren-Erkennung und Agentenhinweise ohne Speicherung von Zugangsdaten.
* MGD Divi 5 Dev Skill im Setup verknüpft.
* Dokumentation, Wiki und rechtliche Angaben erweitert.
