# Mitwirken an MGD WordPress MCP

Beiträge sind willkommen, sofern sie die Sicherheits- und Kompatibilitätsziele des Projekts respektieren.

## Grundregeln

1. Keine Zugangsdaten, Kundendaten, Dumps oder `.env`-Dateien committen.
2. Neue schreibende Abilities müssen einen klaren WordPress Capability Check besitzen.
3. Jede neue riskante Ability braucht eine Risikoanalyse und nachvollziehbare Dokumentation.
4. Keine automatische Veröffentlichung, Massenlöschung oder ungeprüfte Massenupdates als Standardverhalten.
5. WordPress-Core-APIs und offizielle Plugin-APIs bevorzugen.
6. Undokumentierte Datenbank-Manipulationen nur, wenn es keine sichere API gibt und die Einschränkung klar dokumentiert ist.
7. Neue Integrationen müssen ohne das Zielplugin sauber fehlschlagen.
8. Alle öffentlich sichtbaren Texte müssen übersetzbar bzw. sicher escaped sein.

## Entwicklung

- WordPress 6.9+ testen
- PHP 7.4+ Syntax beibehalten
- `php -l` für alle PHP-Dateien ausführen
- mit aktiviertem `WP_DEBUG` testen
- Read-only, Write und Admin-Rollen separat prüfen
- MCP-Tool-Schemas auf eindeutige Beschreibungen prüfen

## Pull Request

Ein Pull Request sollte enthalten:

- Problem/Ziel
- technische Lösung
- Sicherheitsauswirkung
- Testschritte
- Dokumentationsänderungen

Für größere Integrationen bitte vorher ein Issue anlegen.
