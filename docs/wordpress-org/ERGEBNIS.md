# Plugin Check – aktualisierter lokaler Lauf

Stand: 23.10.2026. Kandidat 0.5.11; WordPress 7.1.3, PHP 8.3, offizieller Plugin Check 2.1.0 mit Runtime-Bootstrap.

**0 Fehler, 12 Warnungen.** Alle Prüfgruppen aktiv, keine Fehlercodes ausgeschlossen. Vollständiger Bericht: `plugin-check.txt`.

- `WordPress.DB.DirectDatabaseQuery.DirectQuery`: 5
- `WordPress.DB.DirectDatabaseQuery.NoCaching`: 4
- `WordPress.Security.NonceVerification.Recommended`: 2
- `WordPress.PHP.DevelopmentFunctions.error_log_error_log`: 1

Die CI läuft zusätzlich mit einer eigenen wp-env-Installation und kann andere Laufzeit-/Performance-Hinweise liefern. Die Gesamtübergabe bewahrt beide Rohberichte getrennt auf. Ein grüner Lauf ist keine WordPress.org-Zulassung.

## Prüfung der Resthinweise

Eigene Plugin-Tabellen werden über das WordPress-Präfix und feste Namen erzeugt. Die Laufzeitabfragen binden Tabellenbezeichner mit `%i` und Werte mit `%s`/`%d`. Direkte Abfragen bleiben für eigene Tabellen notwendig. Ein Cache für Empfänger-, Lese-, Audit- und Downloadstatus wurde nicht ergänzt: Änderungen müssen unmittelbar sichtbar bleiben. Hinweise zu Schemaänderungen gehören zur Installation bzw. ausdrücklich gewählten Datenlöschung. Die SQL-Änderungen wurden mit echten Datenbankzugriffen in der lokalen Testkopie geprüft.

Die zwei Nonce-Hinweise betreffen ausschließlich die lesende Tab-Auswahl und die Erkennung der Mehrfachaktivierung. Schreibende Assistenten-/Einstellungsaktionen prüfen Nonce und Administratorrecht. Der Debug-Hinweis betrifft eine feste technische Fehlermeldung nur bei `WP_DEBUG`; Exception-Inhalte werden nicht mehr geloggt. Der separate offizielle MCP Adapter bleibt Voraussetzung. Ein echter Client mit Adapter und Application Password wurde in diesem Lauf nicht verbunden.

## Weitere Belege

Sieben Backends tatsächlich im Browser geöffnet, keine JavaScript-Ausnahmen; echte Screenshots mit künstlichen Daten. 22 lokale Integrationstests zu Formularschutz, Herstellerlink, Entwurf, Menü und privaten/öffentlichen Daten. PHP-Syntax und Verzeichnis-Paketvertrag geprüft. Kein Produktivsystem verändert und keine Einreichung ausgelöst.
