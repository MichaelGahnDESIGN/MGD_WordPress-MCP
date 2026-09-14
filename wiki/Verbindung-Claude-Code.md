# Verbindung mit Claude Code

Claude Code kann einen lokalen STDIO-Prozess als MCP-Server starten. Für eine entfernte WordPress-Website kann der offizielle WordPress-Remote-Proxy verwendet werden, der STDIO auf den HTTP-Endpoint der Website übersetzt.

Beispielkonfiguration:

```json
{
  "mcpServers": {
    "kunde-wordpress": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_API_URL": "https://example.com/wp-json/mgd-wordpress-mcp/v1/mcp",
        "WP_API_USERNAME": "mcp-agent",
        "WP_API_PASSWORD": "DEIN-APPLICATION-PASSWORD"
      }
    }
  }
}
```

Speichere Passwörter nicht im Git-Repository. Nutze lokale Konfiguration, Secret Stores oder Umgebungsvariablen.

## Mehrere Kunden-Websites

Lege pro Website einen eigenen Servernamen an, zum Beispiel:

```text
kunde-mueller
kunde-shop
michael-gahn
```

So kann der Agent explizit auswählen, auf welcher Website er arbeitet.

## Erster Test

Bitte Claude zuerst nur um den Site-Status und die Integrationen. Aktiviere WordPress-Schreibrechte erst danach.
