=== MGD WordPress MCP ===
Contributors: MichaelGahnDESIGN
Tags: mcp, ai, abilities-api, automation, divi, wpforms, updraftplus
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.5.9
License: GPL-2.0-or-later
License URI: https://spdx.org/licenses/GPL-2.0-or-later.html

Sichere WordPress-Abilities für MCP-kompatible KI-Agenten.

== Changelog ==

= 0.5.9 - 2026-09-17 =
* Plugin-Info-Modal ("Details ansehen") robuster gebaut: die Feature-Übersicht in der Beschreibung nutzt jetzt eine einfache Liste statt einer `table.widefat`-Tabelle. Auf Websites mit vielen aktiven Plugins/Themes kollidierte diese Klasse teils mit fremdem, global geladenem CSS (z. B. responsive Tabellen-Hacks) und ließ die Seitenleiste/den Beschreibungsbereich im "Details ansehen"-Dialog auf über 100.000px Höhe aufblähen. Real auf einer Live-Installation mit vielen aktiven Plugins reproduziert und verifiziert.

= 0.5.8 - 2026-09-17 =
* Kritischen Bug im Einrichtungs-Assistenten behoben: Der über register_setting() registrierte Sanitize-Callback der Sicherheitsfreigaben überschrieb das Feld „permission_profile“ bei jedem update_option()-Aufruf wieder mit dem alten Wert. Dadurch änderten sich beim Klick auf „Auswählen“ zwar die zugrunde liegenden Schreib-/Divi-/Medien-/Wartungsrechte, die Profilauswahl („Nur lesen“/„Inhalte bearbeiten“/„Administration“/„Vollzugriff“) blieb aber sichtbar unverändert. Real auf einer Live-Installation reproduziert und verifiziert.

= 0.5.7 - 2026-09-17 =
* Echte, zeitlich befristete Bestätigungstoken (Ability „request-approval") für trash-content, update-plugin, update-theme und updraft-backup verdrahtet; erforderlich, sofern nicht das Profil „Vollzugriff" aktiv ist.
* Tool-Filterung im Profil „Nur lesen" nutzt jetzt eine explizite, autoritative Liste aller Schreib-Abilities statt einer lückenhaften Regex-Erkennung.
* Neuer Plugin-Info-Banner (Higgsfield) im WordPress „Details ansehen“-Modal.

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
