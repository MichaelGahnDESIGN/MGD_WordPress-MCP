# Agent Instructions

Dieses Repository enthält das öffentliche WordPress-Plugin **MGD WordPress MCP**.

## Verbindliche Regeln

- Sprache für Dokumentation und Benutzertexte: Deutsch.
- Öffentliche APIs und Herstellerdokumentation haben Vorrang vor Annahmen.
- Keine Passwörter, Application Passwords, Tokens, Kundendaten, `.env`-Dateien oder Backups committen.
- Sicherheitskritische Änderungen brauchen Capability-Checks, Validierung, Sanitization und Escaping.
- Schreibfähigkeiten bleiben standardmäßig deaktiviert.
- Neue destruktive oder administrative Abilities brauchen einen expliziten Bestätigungswert und einen Audit-Eintrag.
- Inhalte werden nicht permanent gelöscht, wenn ein sicherer Papierkorb-Workflow möglich ist.
- Divi 5 niemals über unbestätigte interne Datenstrukturen „erraten“. Vor Änderungen Revision erzeugen und Konflikte über Änderungszeit prüfen.
- Der offizielle WordPress MCP Adapter bleibt externe Plugin-Abhängigkeit und wird nicht gebündelt.
- Versionen synchron halten in `mgd-wordpress-mcp/mgd-wordpress-mcp.php`, `mgd-wordpress-mcp/readme.txt`, `CHANGELOG.md` und README.
- Vor Release alle PHP-Dateien mit `php -l` prüfen.
- Release-ZIP muss als `mgd-wordpress-mcp.zip` bereitgestellt werden, weil der GitHub-Updater gezielt dieses Asset akzeptiert.

## Relevante Dokumentation

Lies vor größeren Änderungen mindestens:

- `README.md`
- `wiki/Sicherheit-und-Berechtigungen.md`
- `wiki/Entwickler-Dokumentation.md`
- `wiki/Ability-Referenz.md`
- bei Divi: `wiki/Divi-5-Integration.md`
