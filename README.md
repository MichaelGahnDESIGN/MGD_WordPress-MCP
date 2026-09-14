# MGD WordPress MCP

Copyright (c) 2026 Michael Gahn DESIGN · https://Michael-Gahn.de

[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)](LICENSE)
[![WordPress: 6.9+](https://img.shields.io/badge/WordPress-6.9%2B-21759B.svg)](https://wordpress.org/)
[![PHP: 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net/)
[![MCP](https://img.shields.io/badge/MCP-WordPress%20Abilities-111827.svg)](https://modelcontextprotocol.io/)

**MGD WordPress MCP** macht WordPress kontrolliert für MCP-kompatible KI-Agenten erreichbar. Das Plugin registriert sichere WordPress-Abilities für Inhalte, Medien, SEO, Divi 5, WPForms, UpdraftPlus und Wartungsaufgaben und stellt sie über den offiziellen WordPress MCP Adapter als Werkzeuge bereit.

Das Ziel ist nicht „KI darf alles“, sondern ein nachvollziehbarer Agentur-Workflow mit WordPress-Berechtigungen, getrennten Freigabestufen, WordPress-Revisionen, Bestätigungen für riskante Aktionen und lokalem Audit-Log.

> [!IMPORTANT]
> Das Plugin baut bewusst auf der **WordPress Abilities API** und dem offiziellen **WordPress MCP Adapter** auf. Der Transport wird nicht als proprietärer Parallelstandard neu erfunden. Dadurch bleibt MGD WordPress MCP mit dem WordPress-Ökosystem und MCP-Clients kompatibel.

## Was damit möglich ist

Nach Installation und Freigabe kann ein geeigneter Agent zum Beispiel:

- Beiträge und Seiten suchen, lesen, als Entwurf anlegen und aktualisieren
- Beitragsbilder setzen und Medien importieren oder als Base64 hochladen
- Rank-Math- oder Yoast-Metatitel und Meta-Descriptions lesen und setzen
- Divi erkennen, gespeicherte Divi-Inhalte lesen, sicher revisionieren und kontrolliert speichern
- exakte Textstellen in Divi-Inhalten austauschen, ohne die umgebende Struktur neu aufzubauen
- WPForms-Abilities aufrufen, sofern WPForms die jeweilige Ability bereitstellt und die dortigen Schreibrechte aktiviert sind
- über den dokumentierten UpdraftPlus-Hook ein vollständiges Backup starten
- verfügbare Plugin- und Theme-Updates prüfen
- **ein einzelnes** Plugin oder Theme nach expliziter Freigabe aktualisieren
- alle MGD-MCP-Aktionen in einem lokalen Audit-Log nachvollziehen

Beispiel für einen Agentenauftrag:

```text
Erstelle auf der Website einen neuen Blogbeitrag als Entwurf.
Nutze den Stil der vorhandenen Beiträge, setze das Beitragsbild,
trage Rank-Math-Titel und Meta-Description ein und veröffentliche nichts.
```

Oder mit Divi und WPForms:

```text
Lies zuerst die Zielseite und prüfe Divi.
Erstelle vor Änderungen einen Rückfallpunkt.
Füge das vorhandene WPForms-Anfrageformular in den vorgesehenen Divi-Bereich ein.
Verändere Header und Footer nicht.
```

## Sicherheitsmodell

MGD WordPress MCP startet absichtlich restriktiv.

| Bereich | Standard | Zusätzliche Sicherung |
|---|---:|---|
| Lesen | aktiv | WordPress-Capability des verbundenen Benutzers |
| Inhalte/Medien/SEO schreiben | aus | Schalter **Schreibzugriff erlauben** |
| Divi schreiben | aus | eigener Divi-Schalter + Revisionen + optionales Optimistic Locking |
| externe Medien importieren | aus | eigener Schalter + WordPress HTTP-Validierung + Größenlimit |
| Plugin-/Theme-Updates | aus | eigener Wartungsschalter + exakte Bestätigungsstrings |
| Löschen | nur Papierkorb | `TRASH_CONTENT`, kein permanentes Löschen |
| Audit-Log | an | lokal in eigener WordPress-Tabelle |

Der MCP-Transport übernimmt zusätzlich die WordPress-Authentifizierung und die jeweiligen Capability-Checks. Für HTTP-Verbindungen empfiehlt sich HTTPS und ein eigener WordPress-Benutzer mit minimal notwendigen Rechten sowie ein separates Application Password.

## Architektur

```text
Claude Code / Codex / anderer MCP-Client
                  │
                  ▼
       WordPress MCP Adapter
                  │
          MCP HTTP Transport
                  │
                  ▼
      WordPress Abilities API
                  │
                  ▼
        MGD WordPress MCP
      ┌───────────┼───────────┐
      ▼           ▼           ▼
 WordPress     Divi 5     Integrationen
 Inhalte       Bridge     WPForms / SEO /
 Medien                   UpdraftPlus / Updates
```

Das Plugin erstellt zusätzlich einen eigenen MCP-Server mit direkter Tool-Registrierung:

```text
/wp-json/mgd-wordpress-mcp/v1/mcp
```

Wenn der offizielle MCP Adapter fehlt, bleiben die WordPress-Abilities registrierbar, eine MCP-Verbindung ist dann jedoch nicht verfügbar.

## Voraussetzungen

- WordPress **6.9 oder neuer**
- PHP **7.4 oder neuer**
- für MCP-Verbindungen: offizieller [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter)
- für Remote-Verbindungen: HTTPS dringend empfohlen
- ein WordPress-Benutzer mit den tatsächlich benötigten Rechten

### Optionale Integrationen

- Divi / Divi Builder
- WPForms Lite oder Pro mit Abilities-Unterstützung
- UpdraftPlus
- Rank Math SEO
- Yoast SEO
- WooCommerce wird erkannt; eigene WooCommerce-Schreibwerkzeuge sind für eine spätere Version vorgesehen

## Installation

### 1. Plugin installieren

Lade eine Release-ZIP `mgd-wordpress-mcp.zip` von GitHub herunter und installiere sie über. Für die erste öffentliche MVP-Version liegt zusätzlich eine direkt installierbare ZIP unter [`dist/mgd-wordpress-mcp-v0.1.0.zip`](dist/mgd-wordpress-mcp-v0.1.0.zip):

**WordPress → Plugins → Installieren → Plugin hochladen**

Alternativ den Ordner `mgd-wordpress-mcp` nach `wp-content/plugins/` kopieren.

### 2. Offiziellen MCP Adapter installieren

Der WordPress MCP Adapter wird getrennt gepflegt und absichtlich **nicht gebündelt**, damit keine Paket- oder Versionskonflikte mit anderen Plugins entstehen.

Download:

https://github.com/WordPress/mcp-adapter/releases/latest

Nach der Aktivierung steht der MGD-Server zur Verfügung.

### 3. Freigaben konfigurieren

Öffne:

**Werkzeuge → MGD WordPress MCP**

Aktiviere nur die Funktionen, die der Agent wirklich benötigt.

### 4. Application Password anlegen

Erstelle für den WordPress-Benutzer ein eigenes Application Password. Verwende dafür nach Möglichkeit einen separaten Benutzer mit minimalen Rollenrechten.

Das Passwort gehört **niemals** in ein Git-Repository, eine Skill-Datei oder einen öffentlichen Chat.

## Claude Code / Claude Desktop über den offiziellen WordPress-Proxy

Der offizielle WordPress MCP Adapter dokumentiert `@automattic/mcp-wordpress-remote` als Remote-Proxy für HTTP-Verbindungen mit Application Passwords.

Beispiel:

```json
{
  "mcpServers": {
    "meine-wordpress-seite": {
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

## Codex

Codex kann ebenfalls MCP-Server per STDIO-Prozess nutzen. Verwende denselben Remote-Proxy und den MGD-Endpunkt. Die genaue Client-Konfiguration kann sich zwischen Codex CLI, Desktop und IDE-Erweiterung ändern; halte dich für den Client-Teil an die aktuelle OpenAI-Dokumentation und für den WordPress-Teil an dieses Wiki.

> [!WARNING]
> ChatGPT.com und mobile ChatGPT-Oberflächen können je nach Tarif und Produktstand andere Einschränkungen für benutzerdefinierte MCP-Schreibzugriffe haben. Das ist eine Client-/Tarifgrenze und keine Einschränkung des WordPress-Plugins.

## Divi 5

Die Divi-Integration in Version `0.1.0` ist bewusst konservativ:

- Divi-Erkennung
- Layout lesen
- gespeichertes `post_content` mit Revision und Konfliktprüfung speichern
- exakte Textstellen ersetzen
- Builder-Meta `_et_pb_use_builder` kontrolliert aktivieren

Die rohe Divi-Schreibbrücke ist in v0.1.0 zusätzlich auf Administratoren (`manage_options`) begrenzt. MGD WordPress MCP **erfindet keine undokumentierte Divi-5-Server-API**. Ein Agent, der komplette Divi-Layouts erzeugt oder strukturell umbaut, muss das tatsächlich verwendete Divi-Layoutformat der Zielseite verstehen. Für Claude Code und Codex empfiehlt sich dazu der öffentliche [MGD Divi 5 Dev Skill](https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL).

Vor jedem strukturellen Divi-Eingriff sollte ein Backup bzw. mindestens eine WordPress-Revision vorhanden sein.

## WPForms

MGD WordPress MCP stellt die Ability `wpforms-run` bereit. Sie delegiert an die **offiziellen WPForms Abilities** und umgeht deren Rechte- oder Schreibschutz nicht.

Unterstützte Aktionen in Version `0.1.0`:

- `list-forms`
- `get-form`
- `describe-editing-schema`
- `create-form`
- `add-field`
- `update-field`
- `update-form-settings`

Wenn WPForms seine Schreib-Abilities nicht freigibt, kann MGD WordPress MCP sie ebenfalls nicht erzwingen.

## UpdraftPlus

`updraft-backup` startet ein vollständiges Backup über den von UpdraftPlus dokumentierten Action-Hook. Die Sicherung läuft asynchron. Das Tool meldet deshalb bewusst nur, dass der Backup-Job angestoßen wurde.

**Vor Updates muss der Agent den erfolgreichen Abschluss des Backups separat prüfen.** Version `0.1.0` behauptet nicht, einen fertigen UpdraftPlus-Backupstatus zu kennen, wenn dieser nicht zuverlässig über eine öffentliche Schnittstelle vorliegt.

## GitHub-Updates

Das Plugin besitzt einen eigenen GitHub-Updater. Er prüft das öffentliche Repository auf das neueste Release und akzeptiert ausschließlich das Release-Asset:

```text
mgd-wordpress-mcp.zip
```

Das verhindert, dass ein beliebiges GitHub-Zipball-Verzeichnis mit wechselndem Ordnernamen als WordPress-Plugin installiert wird.

Der Workflow `.github/workflows/release.yml` erzeugt bei Tags wie `v0.1.1` automatisch eine installierbare ZIP und hängt sie an das GitHub Release.

## Wiki

Die ausführliche Dokumentation liegt versioniert im Ordner [`wiki/`](wiki/). `_Sidebar.md` und `_Footer.md` sind bereits so vorbereitet, dass dieselben Seiten zusätzlich in ein GitHub Wiki gespiegelt werden können.

| Seite | Inhalt |
|---|---|
| [Home](wiki/Home.md) | Einstieg, Funktionsumfang und Grundprinzipien |
| [Installation und Voraussetzungen](wiki/Installation-und-Voraussetzungen.md) | WordPress, MCP Adapter, Installation und HTTPS |
| [Schnellstart](wiki/Schnellstart.md) | erste sichere Verbindung |
| [MCP und Architektur](wiki/MCP-und-Architektur.md) | technische Schichten und Multi-Site-Prinzip |
| [Sicherheit und Berechtigungen](wiki/Sicherheit-und-Berechtigungen.md) | Freigaben, Rollen, Confirmation-Tokens und Konfliktschutz |
| [Claude Code](wiki/Verbindung-Claude-Code.md) | Remote-Proxy und Client-Beispiel |
| [Codex](wiki/Verbindung-Codex.md) | Codex-Verbindung und Secret-Hinweise |
| [ChatGPT](wiki/Verbindung-ChatGPT.md) | Endpoint und clientseitige Einschränkungen |
| [WordPress Inhalte und Medien](wiki/WordPress-Inhalte-und-Medien.md) | Beiträge, Seiten, Uploads und Beitragsbilder |
| [Divi 5](wiki/Divi-5-Integration.md) | sichere Divi-Arbeitsweise und Grenzen |
| [WPForms](wiki/WPForms-Integration.md) | Delegation an offizielle WPForms-Abilities |
| [UpdraftPlus](wiki/UpdraftPlus-und-Backups.md) | Backup-Trigger und Verifikationsgrenzen |
| [Updates und Wartung](wiki/Updates-und-Wartung.md) | Plugin-/Theme-Updates mit Freigaben |
| [SEO](wiki/SEO-Rank-Math-und-Yoast.md) | Rank Math und Yoast |
| [Audit-Log](wiki/Audit-Log.md) | lokale Aktionshistorie |
| [Ability-Referenz](wiki/Ability-Referenz.md) | kompletter Werkzeugüberblick |
| [GitHub Updates und Releases](wiki/GitHub-Updates-und-Releases.md) | Updater, ZIP-Asset und Release-Workflow |
| [Entwickler-Dokumentation](wiki/Entwickler-Dokumentation.md) | neue Abilities und Projektstruktur |
| [Fehlerbehebung](wiki/Fehlerbehebung.md) | typische Verbindungs- und Rechteprobleme |
| [Datenschutz und Rechtliches](wiki/Datenschutz-und-Rechtliches.md) | Datenflüsse, Zugangsdaten und Verantwortung |
| [Roadmap](wiki/Roadmap.md) | geplante Ausbaustufen |

## Entwicklung und Qualität

Das Plugin folgt den Grundprinzipien der anderen MGD-WordPress-Projekte:

- Capability-Checks vor jeder geschützten Aktion
- Eingaben sanitizen und validieren
- Ausgaben im Backend escapen
- keine Zugangsdaten im Plugin speichern
- keine externen Fonts, Tracker oder Analytics
- keine automatische Veröffentlichung oder Massenupdates als Standard
- revisionsfähige Inhaltsänderungen
- explizite Bestätigungen für destruktive/administrative Aktionen
- offene Dokumentation von Grenzen statt versteckter „Magie“

## Lizenz

**GPL-2.0-or-later**

Diese Lizenz ist mit WordPress kompatibel und erlaubt Nutzung, Änderung und Weitergabe unter den Bedingungen der GPL. Siehe [`LICENSE`](LICENSE).

Der offizielle WordPress MCP Adapter ist ebenfalls GPL-2.0-or-later und wird als getrennte Abhängigkeit installiert.

## Sicherheit

Bitte veröffentliche Sicherheitslücken **nicht** als öffentliches Issue. Hinweise stehen in [`SECURITY.md`](SECURITY.md).

## Mitwirken

Pull Requests und nachvollziehbare Issues sind willkommen. Siehe [`CONTRIBUTING.md`](CONTRIBUTING.md).

## Impressum

Angaben gemäß § 5 DDG befinden sich in [`IMPRESSUM.md`](IMPRESSUM.md).

Online: https://Michael-Gahn.de/impressum

---

## Verwandte MGD-Projekte

| Projekt | Beschreibung |
|---|---|
| [MGD Divi 5 Dev Skill](https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL) | Divi-5-Entwicklungs- und Builder-Workflow für Claude Code und Codex |
| [MGD Blogpost Skill](https://github.com/MichaelGahnDESIGN/MGD_Blogpost-Skill) | Recherche, Faktencheck, WordPress, SEO, Beitragsbild und Social Media |
| [MGD Claude-Codex MCP](https://github.com/MichaelGahnDESIGN/MGD_Claude-Codex_MCP) | MCP-System für lokale Agenten-Workflows |

→ Weitere Projekte: https://github.com/MichaelGahnDESIGN
