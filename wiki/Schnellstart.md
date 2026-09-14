# Schnellstart

Nach Installation und Aktivierung öffnest du **Werkzeuge → MGD WordPress MCP**.

## 1. Endpoint kopieren

Im Reiter **Verbindung** zeigt das Plugin den MCP-Endpoint. Standardmäßig lautet er:

```text
https://DEINE-DOMAIN.TLD/wp-json/mgd-wordpress-mcp/v1/mcp
```

## 2. Application Password erstellen

Öffne das WordPress-Profil des Benutzers, mit dem der Agent arbeiten soll. Erstelle im Bereich Application Passwords ein neues Passwort, zum Beispiel `Claude Code`, `Codex` oder `Agentur MCP`.

Das erzeugte Passwort wird nur einmal angezeigt. Behandle es wie ein echtes Passwort und speichere es nicht im Git-Repository.

## 3. Erst nur lesen

Lass die Plugin-Einstellungen zunächst unverändert. Schreibzugriffe sind standardmäßig deaktiviert.

Teste zuerst Abilities wie:

```text
mgd-wordpress-mcp/site-status
mgd-wordpress-mcp/integrations
mgd-wordpress-mcp/list-content
mgd-wordpress-mcp/get-content
```

## 4. Schreibzugriff bewusst freigeben

Erst wenn die Verbindung korrekt auf die richtige Website zeigt, aktivierst du unter **Sicherheit & Freigaben** den allgemeinen Schreibzugriff.

Divi-Schreibzugriff und Wartung bleiben eigene Freigaben. So kann ein Redaktions-Agent Beiträge erstellen, ohne gleichzeitig Plugins aktualisieren zu dürfen.

## 5. Erster sicherer Auftrag

Ein sinnvoller erster Test ist:

```text
Erstelle einen neuen WordPress-Beitrag als Entwurf mit dem Titel "MCP Test" und einem kurzen Absatz. Veröffentliche ihn nicht.
```

Kontrolliere danach den Entwurf im WordPress-Backend und den Audit-Log.
