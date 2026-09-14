# MGD WordPress MCP Wiki

Willkommen im Wiki von **MGD WordPress MCP**.

Das Plugin verbindet WordPress über standardisierte **WordPress Abilities** mit dem **Model Context Protocol (MCP)**. Ziel ist nicht, einem KI-Agenten unkontrollierten Administratorzugriff zu geben. Ziel ist eine nachvollziehbare, granular freigegebene Werkzeugschicht, über die ein Agent genau die Aufgaben erledigen kann, die der jeweilige WordPress-Benutzer und die Plugin-Einstellungen erlauben.

MGD WordPress MCP ist für Agenturen, Entwicklerinnen und Entwickler, Website-Betreiber und technisch versierte Anwender gedacht. Es kann auf einer einzelnen Website eingesetzt werden oder auf mehreren Kunden-Websites, wobei jede Installation separat authentifiziert und freigegeben wird.

## Was Version 0.1.0 kann

Die erste öffentliche Version deckt fünf große Bereiche ab: WordPress-Inhalte und Medien, SEO, eine konservative Divi-5-Brücke, Integrationen mit WPForms und UpdraftPlus sowie kontrollierte Wartungsaktionen für Plugins und Themes.

Schreibzugriffe sind standardmäßig ausgeschaltet. Auch wenn ein MCP-Client verbunden ist, kann er deshalb nach der Installation zunächst nur die erlaubten Leseaktionen verwenden. Divi-Schreibzugriffe und Wartungsaktionen müssen zusätzlich separat freigeschaltet werden.

## Technischer Unterbau

MGD WordPress MCP setzt auf die WordPress Abilities API ab WordPress 6.9 und verwendet den offiziellen WordPress MCP Adapter als Transport- und Protokollschicht. Der Adapter wird absichtlich nicht mitgeliefert, sondern als eigenes WordPress-Plugin installiert und aktuell gehalten.

Der eigene Endpoint lautet standardmäßig:

```text
https://DEINE-DOMAIN.TLD/wp-json/mgd-wordpress-mcp/v1/mcp
```

Für die Remote-Authentifizierung empfiehlt sich ein eigener WordPress-Benutzer mit den minimal benötigten Rechten und ein Application Password.

## Einstieg

Neue Nutzer beginnen mit [Installation und Voraussetzungen](Installation-und-Voraussetzungen). Danach führt der [Schnellstart](Schnellstart) durch die erste Verbindung.

Wer das Plugin produktiv auf Kunden-Websites einsetzen möchte, sollte vor der ersten Schreibfreigabe unbedingt [Sicherheit und Berechtigungen](Sicherheit-und-Berechtigungen) lesen.

## Projekt

Quellcode: https://github.com/MichaelGahnDESIGN/MGD_WordPress-MCP

Impressum: https://Michael-Gahn.de/impressum

Lizenz: GPL-2.0-or-later
