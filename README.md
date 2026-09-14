# MGD WordPress MCP

Copyright (c) 2026 Michael Gahn DESIGN · https://Michael-Gahn.de

[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)](LICENSE)
[![WordPress: 6.9+](https://img.shields.io/badge/WordPress-6.9%2B-21759B.svg)](https://wordpress.org/)
[![PHP: 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net/)
[![MCP](https://img.shields.io/badge/MCP-WordPress%20Abilities-111827.svg)](https://modelcontextprotocol.io/)
[![Version](https://img.shields.io/badge/version-0.2.0-4c1.svg)](CHANGELOG.md)

**MGD WordPress MCP** verbindet WordPress kontrolliert mit MCP-kompatiblen KI-Agenten wie Claude Code, OpenAI Codex und anderen MCP-Clients. Das Plugin stellt sichere WordPress-Abilities für Inhalte, Medien, SEO, Divi 5, WPForms, UpdraftPlus, Updates und die Website-Umgebung bereit.

Die Grundidee lautet ausdrücklich nicht „KI darf alles“. MGD WordPress MCP setzt auf WordPress-Berechtigungen, getrennte Freigabestufen, Revisionen, Bestätigungen für riskante Aktionen, ein lokales Audit-Log und einen Einrichtungs-Assistenten.

> [!IMPORTANT]
> MGD WordPress MCP baut auf der **WordPress Abilities API** und dem offiziellen **WordPress MCP Adapter** auf. Der MCP-Transport wird nicht als proprietärer Parallelstandard neu erfunden. Das Plugin ergänzt WordPress um Werkzeuge und Sicherheitslogik.

## Was soll damit möglich sein?

Ein verbundener Agent kann abhängig von deinen Freigaben beispielsweise:

```text
Erstelle einen neuen Blogbeitrag als Entwurf, lade das Beitragsbild hoch,
setze Rank Math Titel und Description und veröffentliche noch nichts.
```

```text
Analysiere die Seite „Leistungen“. Sie verwendet Divi 5.
Ändere nur den angegebenen Textbereich und lass Header und Footer unverändert.
```

```text
Erstelle in WPForms ein Anfrageformular und füge es anschließend
in den vorgesehenen Bereich der Seite ein.
```

```text
Prüfe die verfügbaren Updates. Erstelle zuerst ein UpdraftPlus-Backup.
Aktualisiere danach nur das ausdrücklich freigegebene Plugin.
```

## Funktionsumfang

| Bereich | Funktionen |
|---|---|
| WordPress | Beiträge, Seiten und öffentliche Post Types lesen, erstellen und bearbeiten |
| Medien | Mediathek lesen, Dateien hochladen/importieren, Beitragsbild setzen |
| SEO | Rank Math und Yoast erkennen, Meta-Titel und Meta-Description lesen/schreiben |
| Divi | Divi erkennen, Layout lesen, revisioniert speichern, exakte Texte ersetzen |
| WPForms | ausgewählte offizielle WPForms-Abilities delegieren |
| Backups | UpdraftPlus-Backup über dokumentierten Hook anstoßen |
| Wartung | Updates prüfen und einzelne Plugins/Themes nach Bestätigung aktualisieren |
| Umgebung | Builder und typische Frontend-Sperren erkennen |
| Sicherheit | Freigabeschalter, WordPress-Capabilities, Bestätigungen, Audit-Log |
| Updates | MGD WordPress MCP über GitHub Releases aktuell halten |

## Einrichtungs-Assistent

Nach der Aktivierung startet ein Assistent unter **Werkzeuge → MGD WordPress MCP → Einrichtungs-Assistent**.

Er erkennt zunächst den wahrscheinlich verwendeten Builder und fragt, womit Agenten auf der Website arbeiten sollen. Unterstützte Erkennung in Version 0.2.0:

- Divi 5 / Divi Builder
- Elementor
- WordPress Block Editor / Gutenberg
- Bricks
- Beaver Builder
- ein frei benennbares anderes System

### Divi 5

Wenn Divi erkannt oder ausgewählt wird, empfiehlt der Assistent den kostenlosen öffentlichen **MGD Divi 5 Dev Skill**:

https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL

Der Skill enthält die Arbeitsregeln und das Wissen für professionelle Divi-5-Arbeit mit Claude Code und Codex. MGD WordPress MCP stellt dagegen die kontrollierten WordPress-Werkzeuge bereit.

> [!NOTE]
> Der WordPress-Server kann einen lokalen Claude-/Codex-Skill nicht heimlich in deinen Computer installieren. Der Assistent bietet deshalb die Installation bzw. Verwendung an und führt zur offiziellen GitHub-Quelle. Das vermeidet Remote-Code-Ausführung und hält die Trennung zwischen Website und Agentenrechner sauber.

### Andere Builder

Wenn kein Divi verwendet wird, fragt der Assistent nach Elementor, Gutenberg, Bricks, Beaver Builder oder einem anderen System. Die Auswahl wird als Projektkontext gespeichert. Spezifische Schreibadapter für weitere Builder können darauf aufbauend ergänzt werden.

## Frontend-Schutz erkennen

Kundenwebsites sind häufig nicht frei sichtbar. Security-Plugins, Passwortschutz, Coming-Soon-Modi, Maintenance-Plugins, Hosting-Sperren oder externe Firewalls können verhindern, dass ein Agent die Seite visuell kontrolliert.

MGD WordPress MCP prüft deshalb aktive Plugins auf typische Hinweise für:

- Security-/Shield-Lösungen, darunter auch Plugins mit „Shield“ im Namen
- Passwortschutz
- Restricted Site Access
- Maintenance Mode
- Coming Soon

Wird ein möglicher Schutz erkannt, erhält der Agent die Anweisung:

> Wenn eine visuelle Prüfung oder Browser-Automation blockiert wird, frage den Nutzer nach dem legitimen Entsperrweg oder einer temporären Freigabe. Frage nur bei Bedarf nach einer PIN.

**PINs, Passwörter und Zugangsdaten werden von MGD WordPress MCP nicht gespeichert und nicht ins Audit-Log geschrieben.**

Die Erkennung ist absichtlich heuristisch. Eine serverseitige HTTP-Basic-Auth, Cloudflare Access, eine Hosting-Firewall oder eine individuelle Sperre kann außerhalb von WordPress liegen und deshalb nicht zuverlässig automatisch erkannt werden.

## Sicherheitsmodell

MGD WordPress MCP startet restriktiv.

| Bereich | Standard | Schutz |
|---|---:|---|
| Lesen | aktiv | WordPress-Capability des verbundenen Benutzers |
| Inhalte/Medien/SEO schreiben | aus | separater Schreibschalter |
| Divi schreiben | aus | eigener Schalter, Administratorrecht, Revisionen |
| externe Medien importieren | aus | eigener Schalter und Größenlimit |
| Plugin-/Theme-Updates | aus | Wartungsschalter und Bestätigungsstring |
| Löschen | Papierkorb | kein permanentes Löschen über das Content-Tool |
| Audit-Log | an | lokale WordPress-Tabelle |
| Zugangsdaten | nicht gespeichert | Application Password bleibt beim MCP-Client |

Für Kundenwebsites empfehlen wir einen separaten WordPress-Benutzer mit minimal notwendigen Rechten und ein eigenes Application Password pro Verbindung.

## Architektur

```text
Claude Code / Codex / MCP-Client
              │
              ▼
      WordPress MCP Adapter
              │
              ▼
       Abilities API
              │
              ▼
      MGD WordPress MCP
       │      │       │
       │      │       ├── Umgebung / Frontend-Schutz
       │      ├────────── Divi / WPForms / SEO
       └──────────────── WordPress / Medien / Wartung
```

Der MGD-MCP-Endpunkt lautet:

```text
/wp-json/mgd-wordpress-mcp/v1/mcp
```

## Voraussetzungen

- WordPress 6.9 oder neuer
- PHP 7.4 oder neuer
- HTTPS für Remote-Verbindungen dringend empfohlen
- offizieller WordPress MCP Adapter für die MCP-Verbindung
- WordPress-Benutzer mit den benötigten Rechten

Offizieller MCP Adapter:

https://github.com/WordPress/mcp-adapter

## Installation

1. Repository bzw. Release-ZIP herunterladen.
2. Den Ordner `mgd-wordpress-mcp` als WordPress-Plugin installieren.
3. Plugin aktivieren.
4. Einrichtungs-Assistent durchlaufen.
5. Offiziellen WordPress MCP Adapter installieren und aktivieren.
6. Einen separaten WordPress-Benutzer für den Agenten wählen oder anlegen.
7. Für diesen Benutzer ein eigenes Application Password erstellen.
8. MCP-Client verbinden.
9. Schreib-, Divi- und Wartungsrechte nur bei Bedarf aktivieren.

## Verbindung mit Claude Code

Der offizielle WordPress MCP Adapter stellt mit `@automattic/mcp-wordpress-remote` einen Remote-Proxy bereit. Beispiel:

```json
{
  "mcpServers": {
    "kundenwebsite": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_API_URL": "https://example.org/wp-json/mgd-wordpress-mcp/v1/mcp",
        "WP_API_USERNAME": "mcp-agent",
        "WP_API_PASSWORD": "APPLICATION-PASSWORD"
      }
    }
  }
}
```

Speichere das Application Password niemals im Repository.

## Verbindung mit Codex

Codex kann MCP-Server ebenfalls über einen passenden MCP-Prozess anbinden. Der WordPress-Teil bleibt identisch. Client-Konfigurationen können sich mit Codex-Versionen ändern, deshalb dokumentiert das Wiki das WordPress-seitige Verfahren getrennt von der jeweiligen Codex-Oberfläche.

## ChatGPT

MGD WordPress MCP stellt einen standardisierten MCP-Endpunkt bereit. Ob ChatGPT.com oder eine bestimmte ChatGPT-App benutzerdefinierte MCP-Server mit Schreibzugriff akzeptiert, hängt vom aktuellen OpenAI-Produkt, Tarif und Client ab. Diese Clientgrenze kann das WordPress-Plugin nicht umgehen.

## Divi 5: Skill + MCP

MGD WordPress MCP und der Divi Skill haben unterschiedliche Aufgaben:

```text
MGD Divi 5 Dev Skill
        │
        │ Arbeitsregeln / Divi-Wissen
        ▼
Claude Code / Codex
        │
        │ MCP Tools
        ▼
MGD WordPress MCP
        │
        ▼
WordPress + Divi
```

Version 0.2.0 bleibt bei direkten Divi-Schreiboperationen bewusst konservativ. Sie erfindet keine undokumentierte Divi-Server-API. Vor strukturellen Änderungen sollten Revision bzw. Backup vorhanden sein.

## WPForms

Die WPForms-Bridge delegiert an offizielle WPForms-Abilities, sofern diese in der installierten WPForms-Version verfügbar sind. MGD WordPress MCP umgeht keine WPForms-Rechte oder Schreibschutzschalter.

## UpdraftPlus und Updates

Das Backup-Tool stößt ein UpdraftPlus-Backup an. Ein gestarteter Job ist nicht automatisch ein erfolgreich abgeschlossenes Backup. Vor riskanten Updates soll der Agent deshalb den Abschluss separat verifizieren, soweit die installierte UpdraftPlus-Version dafür eine verlässliche Schnittstelle bietet.

Updates werden absichtlich einzeln ausgeführt. Massenupdates sind kein Standardwerkzeug.

## GitHub-Updates

Das Plugin wird über dieses Repository gepflegt:

https://github.com/MichaelGahnDESIGN/MGD_WordPress-MCP

Der integrierte Updater prüft GitHub Releases. Releases sollen eine installierbare Datei namens `mgd-wordpress-mcp.zip` enthalten.

## Wiki

Die ausführliche Dokumentation liegt versioniert im Ordner [`wiki/`](wiki/). Damit bleibt sie auch außerhalb der GitHub-Wiki-Funktion nachvollziehbar und kann später automatisiert gespiegelt werden.

Wichtige Einstiege:

- [Home](wiki/Home.md)
- [Einrichtungs-Assistent](wiki/Einrichtungs-Assistent.md)
- [Frontend-Sperren und Zugang](wiki/Frontend-Sperren-und-Zugang.md)
- [Builder und Agent Skills](wiki/Builder-und-Agent-Skills.md)
- [Installation und Voraussetzungen](wiki/Installation-und-Voraussetzungen.md)
- [Sicherheit und Berechtigungen](wiki/Sicherheit-und-Berechtigungen.md)
- [MCP und Architektur](wiki/MCP-und-Architektur.md)
- [Divi 5 Integration](wiki/Divi-5-Integration.md)
- [WPForms Integration](wiki/WPForms-Integration.md)
- [UpdraftPlus und Backups](wiki/UpdraftPlus-und-Backups.md)
- [Updates und Wartung](wiki/Updates-und-Wartung.md)
- [SEO](wiki/SEO-Rank-Math-und-Yoast.md)
- [Fehlerbehebung](wiki/Fehlerbehebung.md)
- [Datenschutz und Rechtliches](wiki/Datenschutz-und-Rechtliches.md)
- [Roadmap](wiki/Roadmap.md)

## Passende MGD-Projekte

| Projekt | Zweck |
|---|---|
| [MGD Divi 5 Dev Skill](https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL) | Divi-5-Wissen und Arbeitsregeln für Claude Code und Codex |
| [MGD AI Kennzeichnung WordPress](https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-WordPress) | Kennzeichnung KI-bezogener Bilder in WordPress |
| [MGD Blogpost Skill](https://github.com/MichaelGahnDESIGN/MGD_Blogpost-Skill) | recherchierter Blog-Workflow mit SEO, Bild und Social Media |
| [MGD Backup Skill](https://github.com/MichaelGahnDESIGN/MGD_Backup_SKILL) | Backup-Workflow für Agentenprojekte |
| [MGD Software Updater Skill](https://github.com/MichaelGahnDESIGN/MGD_Software-Updater_SKILL) | kontrollierte Update-Workflows |

Alle öffentlichen Projekte:

https://github.com/MichaelGahnDESIGN

## Datenschutz

Das Plugin enthält keine Telemetrie, keine Werbung und keine externen Tracker. Das Audit-Log wird lokal in WordPress gespeichert. Welche Daten ein verbundener KI-Dienst verarbeitet, hängt vom verwendeten MCP-Client, KI-Anbieter und konkreten Auftrag ab. Website-Betreiber bleiben für ihre eigene Datenschutzkonfiguration verantwortlich.

## Lizenz

**GPL-2.0-or-later**

Diese Lizenz passt zum WordPress-Ökosystem und erlaubt Nutzung, Änderung und Weitergabe unter den Bedingungen der GNU General Public License. Siehe [`LICENSE`](LICENSE).

## Sicherheit

Sicherheitslücken bitte nicht als öffentliches Issue veröffentlichen. Siehe [`SECURITY.md`](SECURITY.md).

## Mitwirken

Pull Requests, nachvollziehbare Bugreports und Integrationsvorschläge sind willkommen. Siehe [`CONTRIBUTING.md`](CONTRIBUTING.md).

## Impressum

Angaben gemäß § 5 DDG: [`IMPRESSUM.md`](IMPRESSUM.md)

Online-Impressum:

https://Michael-Gahn.de/impressum

---

**MGD WordPress MCP** ist ein Open-Source-Projekt von Michael Gahn DESIGN und soll WordPress-Agenten leistungsfähig machen, ohne Sicherheit, Nachvollziehbarkeit und Kontrolle aus der Hand zu geben.
