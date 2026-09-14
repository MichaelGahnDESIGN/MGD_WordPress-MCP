# Installation und Voraussetzungen

## Voraussetzungen

MGD WordPress MCP 0.1.0 benötigt WordPress 6.9 oder neuer und PHP 7.4 oder neuer. Zusätzlich wird der offizielle **WordPress MCP Adapter** benötigt.

Der MCP Adapter ist bewusst eine separate Abhängigkeit. Dadurch kann WordPress genau eine kanonische Adapter-Version verwenden und andere Plugins können denselben Adapter mitnutzen.

## Installation des MCP Adapters

Installiere den offiziellen Adapter als WordPress-Plugin. Wenn er über WordPress.org verfügbar ist, kann er über **Plugins → Installieren** gesucht werden. Alternativ kann die aktuelle Release-ZIP aus dem offiziellen Repository installiert werden.

Projekt: https://github.com/WordPress/mcp-adapter

## Installation von MGD WordPress MCP

1. Lade aus dem GitHub-Release die Datei `mgd-wordpress-mcp.zip` herunter.
2. Öffne **Plugins → Installieren → Plugin hochladen**.
3. Lade die ZIP hoch und aktiviere **MGD WordPress MCP**.
4. Öffne **Werkzeuge → MGD WordPress MCP**.
5. Prüfe im Status-Reiter, ob WordPress, PHP und MCP Adapter erkannt werden.

## Warum kein eingebauter MCP Adapter?

Das Plugin folgt der Empfehlung des offiziellen MCP-Adapter-Projekts und bündelt den Adapter nicht im eigenen `vendor`-Ordner. Das vermeidet doppelte Klassen, Versionskonflikte und voneinander abweichende Transportimplementierungen.

## HTTPS

Für produktive Remote-Verbindungen soll die Website ausschließlich über HTTPS erreichbar sein. Application Passwords sind kein Ersatz für TLS.

## Benutzerkonto für MCP

Für Agentur-Websites ist ein eigener WordPress-Benutzer pro Verbindung empfehlenswert. Gib diesem Konto nur die Rechte, die der Agent wirklich benötigt. Ein Agent, der ausschließlich Blogbeiträge als Entwurf erstellen soll, braucht keinen Administratorzugriff.
