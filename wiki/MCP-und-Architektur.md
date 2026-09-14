# MCP und Architektur

MGD WordPress MCP trennt bewusst drei Schichten voneinander.

```text
MCP Client
Claude Code / Codex / kompatibler Agent
        │
        ▼
WordPress MCP Adapter
Transport, MCP-Protokoll, Authentifizierung
        │
        ▼
MGD WordPress MCP
Abilities, Sicherheitsregeln, Integrationen, Audit
        │
        ▼
WordPress / Divi / WPForms / UpdraftPlus / SEO
```

## WordPress Abilities

Jede Funktion wird als klar benannte Ability registriert. Eine Ability besitzt Eingabeschema, Beschreibung, Berechtigungsprüfung und Ausführungsfunktion. Der Agent erhält damit keine beliebige PHP-Konsole, sondern einen begrenzten Werkzeugkasten.

## Eigener MCP-Server

MGD WordPress MCP registriert einen eigenen Server beim offiziellen MCP Adapter. Dadurch werden nur die für dieses Plugin vorgesehenen Abilities an diesem Endpoint angeboten.

Standard-Endpoint:

```text
/wp-json/mgd-wordpress-mcp/v1/mcp
```

## Warum diese Architektur?

Ein generischer Fernzugriff auf WordPress wäre für Kunden-Websites zu riskant. Die Ability-Schicht macht jede Aktion prüfbar. Ein Tool zum Verschieben eines Beitrags in den Papierkorb kann zum Beispiel einen festen Bestätigungstoken verlangen und zugleich im Audit-Log protokolliert werden.

## Mehrere Websites

Jede Kunden-Website installiert das Plugin separat und besitzt ihren eigenen Endpoint sowie ihre eigenen WordPress-Benutzer und Application Passwords. Ein Client kann mehrere Server konfigurieren und sie anhand verständlicher Namen unterscheiden.

Damit bleiben Berechtigungen und Kundendaten sauber je Installation getrennt.
