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
> MGD WordPress MCP baut auf der **WordPress Abilities API** und dem offiziellen **WordPress MCP Adapter** auf. Der MCP-Transport wird nicht als proprietärer Parallelstandard neu erfunden.

## Version 0.2.0

Version **0.2.0** ist als erster öffentlicher Release Candidate vorbereitet. Der Code wird über GitHub Actions mit PHP 7.4, 8.1, 8.3 und 8.4 geprüft. Plugin-Version und WordPress `Stable tag` müssen übereinstimmen.

Der direkte Base64-Medienupload ist in 0.2.0 bewusst **fail-closed deaktiviert**. Er wird weder als MCP-Tool angeboten noch extern registriert, solange die Dateitypvalidierung nicht vollständig gehärtet ist. Sicherheit geht hier vor Funktionsumfang.

## Was soll damit möglich sein?

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
| Medien | Mediathek lesen, sichere Medienwege nutzen, Beitragsbild setzen |
| SEO | Rank Math und Yoast erkennen, Meta-Titel und Meta-Description lesen/schreiben |
| Divi | Divi erkennen, Layout lesen, revisioniert speichern, exakte Texte ersetzen |
| WPForms | ausgewählte offizielle WPForms-Abilities delegieren |
| Backups | UpdraftPlus-Backup über dokumentierten Hook anstoßen |
| Wartung | Updates prüfen und einzelne Plugins/Themes nach Bestätigung aktualisieren |
| Umgebung | Builder und typische Frontend-Sperren erkennen |
| Sicherheit | Freigabeschalter, WordPress-Capabilities, Bestätigungen, Audit-Log |
| Updates | MGD WordPress MCP über GitHub Releases aktuell halten |

## Einrichtungs-Assistent

Nach der Aktivierung startet der Assistent unter **Werkzeuge → MGD WordPress MCP → Einrichtungs-Assistent**. Er erkennt Divi 5 / Divi Builder, Elementor, Gutenberg, Bricks und Beaver Builder. Alternativ kann ein anderes System angegeben werden.

### Divi 5

Bei Divi empfiehlt der Assistent den kostenlosen **MGD Divi 5 Dev Skill**:

https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL

Der Skill enthält Arbeitsregeln und Divi-Wissen für Agenten. MGD WordPress MCP stellt die kontrollierten WordPress-Werkzeuge bereit. Ein WordPress-Server installiert aus Sicherheitsgründen keine lokalen Claude- oder Codex-Skills ungefragt auf einem Benutzerrechner.

## Frontend-Schutz erkennen

MGD WordPress MCP erkennt heuristisch typische Security-/Shield-Lösungen, Passwortschutz, Restricted Site Access, Maintenance Mode und Coming-Soon-Plugins. Wird ein möglicher Schutz erkannt, erhält der Agent die Anweisung, bei einer tatsächlich blockierten visuellen Prüfung nach dem legitimen Entsperrweg oder nur bei Bedarf nach einer PIN zu fragen.

**PINs, Passwörter und Zugangsdaten werden nicht gespeichert und nicht ins Audit-Log geschrieben.** Externe Sperren wie HTTP Basic Auth, Cloudflare Access oder Hosting-Firewalls können außerhalb von WordPress liegen und deshalb nicht immer automatisch erkannt werden.

## Sicherheitsmodell

| Bereich | Standard | Schutz |
|---|---:|---|
| Lesen | aktiv | WordPress-Capability des verbundenen Benutzers |
| Inhalte/Medien/SEO schreiben | aus | separater Schreibschalter |
| Divi schreiben | aus | eigener Schalter, Administratorrecht, Revisionen |
| externe Medien importieren | aus | eigener Schalter und Größenlimit |
| Base64-Direktupload | deaktiviert | fail-closed, nicht als MCP-Tool exponiert |
| Plugin-/Theme-Updates | aus | Wartungsschalter und Bestätigungsstring |
| Löschen | Papierkorb | kein permanentes Löschen über das Content-Tool |
| Audit-Log | an | lokale WordPress-Tabelle |
| Zugangsdaten | nicht gespeichert | Application Password bleibt beim MCP-Client |

Für Kundenwebsites empfiehlt sich ein separater WordPress-Benutzer mit minimal notwendigen Rechten und ein eigenes Application Password pro Verbindung.

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

Der Endpoint lautet:

```text
/wp-json/mgd-wordpress-mcp/v1/mcp
```

## Voraussetzungen

* WordPress 6.9 oder neuer
* PHP 7.4 oder neuer
* HTTPS für Remote-Verbindungen dringend empfohlen
* offizieller WordPress MCP Adapter
* WordPress-Benutzer mit den benötigten Rechten

Offizieller MCP Adapter:

https://github.com/WordPress/mcp-adapter

## Installation

1. Offiziellen WordPress MCP Adapter installieren und aktivieren.
2. Die Release-ZIP `mgd-wordpress-mcp.zip` installieren.
3. MGD WordPress MCP aktivieren.
4. Einrichtungs-Assistent durchlaufen.
5. Einen separaten WordPress-Benutzer für den Agenten verwenden.
6. Ein eigenes Application Password erstellen.
7. MCP-Client mit dem angezeigten Endpoint verbinden.
8. Schreib-, Divi- und Wartungsrechte nur bei Bedarf aktivieren.

## Claude Code und Codex

Für Remote-Verbindungen kann der vom WordPress MCP Adapter vorgesehene Remote-Proxy verwendet werden. Zugangsdaten gehören in den Secret-/Environment-Speicher des jeweiligen Clients und niemals ins Repository.

Beispielkonfiguration:

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

## ChatGPT

MGD WordPress MCP stellt einen standardisierten MCP-Endpunkt bereit. Ob eine konkrete ChatGPT-Oberfläche benutzerdefinierte MCP-Server und Schreibaktionen unterstützt, hängt vom jeweiligen OpenAI-Produkt, Tarif und Client ab. Diese Clientgrenze kann das WordPress-Plugin nicht umgehen.

## Divi 5: Skill + MCP

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

Direkte Divi-Schreiboperationen bleiben bewusst konservativ. Vor strukturellen Änderungen sollten Revision oder Backup vorhanden sein.

## WPForms

Die WPForms-Bridge delegiert an offizielle WPForms-Abilities, sofern diese in der installierten Version verfügbar sind. MGD WordPress MCP umgeht keine WPForms-Rechte oder Schreibschutzschalter.

## UpdraftPlus und Wartung

Das Backup-Tool kann ein UpdraftPlus-Backup anstoßen. Ein gestarteter Job bedeutet nicht automatisch, dass das Backup bereits vollständig abgeschlossen ist. Vor riskanten Updates sollte der Abschluss soweit möglich verifiziert werden.

Updates werden absichtlich einzeln ausgeführt. Ein universelles `update everything` gehört nicht zum Standardumfang.

## GitHub Releases und WordPress-Updates

Das Plugin wird über dieses Repository gepflegt:

https://github.com/MichaelGahnDESIGN/MGD_WordPress-MCP

Der integrierte Updater prüft GitHub Releases. Eine veröffentlichte Version muss das Asset `mgd-wordpress-mcp.zip` enthalten. Die ZIP enthält den stabilen Ordner `mgd-wordpress-mcp/`, damit WordPress das bestehende Plugin aktualisiert und keine zweite Plugin-Kopie anlegt.

Der Release-Workflow prüft vor Veröffentlichung, dass Git-Tag, Plugin-Version und `Stable tag` identisch sind. Zusätzlich müssen alle PHP-Dateien den Syntaxcheck bestehen. Nach Veröffentlichung einer neueren Version kann WordPress sie über das normale Update-System anzeigen.

## Qualitätssicherung

Auf `main` läuft eine CI-Prüfung mit PHP 7.4, 8.1, 8.3 und 8.4. Zusätzlich wird die Versionskonsistenz geprüft. Der Release-Workflow führt vor dem Bau der ZIP erneut Validierungen durch.

Das aktuelle Security- und Release-Readiness-Audit liegt in [`SECURITY-AUDIT.md`](SECURITY-AUDIT.md).

## Wiki

Die ausführliche Dokumentation liegt versioniert im Ordner [`wiki/`](wiki/).

* [Home](wiki/Home.md)
* [Einrichtungs-Assistent](wiki/Einrichtungs-Assistent.md)
* [Frontend-Sperren und Zugang](wiki/Frontend-Sperren-und-Zugang.md)
* [Builder und Agent Skills](wiki/Builder-und-Agent-Skills.md)
* [Installation und Voraussetzungen](wiki/Installation-und-Voraussetzungen.md)
* [Sicherheit und Berechtigungen](wiki/Sicherheit-und-Berechtigungen.md)
* [MCP und Architektur](wiki/MCP-und-Architektur.md)
* [Divi 5 Integration](wiki/Divi-5-Integration.md)
* [WPForms Integration](wiki/WPForms-Integration.md)
* [UpdraftPlus und Backups](wiki/UpdraftPlus-und-Backups.md)
* [Updates und Wartung](wiki/Updates-und-Wartung.md)
* [SEO](wiki/SEO-Rank-Math-und-Yoast.md)
* [Fehlerbehebung](wiki/Fehlerbehebung.md)
* [Datenschutz und Rechtliches](wiki/Datenschutz-und-Rechtliches.md)
* [Roadmap](wiki/Roadmap.md)

## Passende MGD-Projekte

| Projekt | Zweck |
|---|---|
| [MGD Divi 5 Dev Skill](https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL) | Divi-5-Wissen und Arbeitsregeln für Claude Code und Codex |
| [MGD AI Kennzeichnung WordPress](https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-WordPress) | Kennzeichnung KI-bezogener Bilder in WordPress |
| [MGD Blogpost Skill](https://github.com/MichaelGahnDESIGN/MGD_Blogpost-Skill) | recherchierter Blog-Workflow mit SEO, Bild und Social Media |
| [MGD Backup Skill](https://github.com/MichaelGahnDESIGN/MGD_Backup_SKILL) | Backup-Workflow für Agentenprojekte |
| [MGD Software Updater Skill](https://github.com/MichaelGahnDESIGN/MGD_Software-Updater_SKILL) | kontrollierte Update-Workflows |

## Datenschutz

Das Plugin enthält keine Telemetrie, Werbung oder externen Tracker. Das Audit-Log wird lokal in WordPress gespeichert. Die optionale GitHub-Updateprüfung kontaktiert GitHub. Welche Website-Daten ein verbundener KI-Dienst verarbeitet, hängt vom MCP-Client, KI-Anbieter und konkreten Auftrag ab.

Weitere Details stehen unter [`wiki/Datenschutz-und-Rechtliches.md`](wiki/Datenschutz-und-Rechtliches.md).

## Lizenz

**GPL-2.0-or-later**. Siehe [`LICENSE`](LICENSE).

## Sicherheit

Sicherheitslücken bitte nicht als öffentliches Issue veröffentlichen. Siehe [`SECURITY.md`](SECURITY.md).

## Mitwirken

Pull Requests, nachvollziehbare Bugreports und Integrationsvorschläge sind willkommen. Siehe [`CONTRIBUTING.md`](CONTRIBUTING.md).

## Impressum gemäß § 5 DDG

**Michael Gahn DESIGN**  
Inhaber: Michael Gahn  
Dr.-Theodor-Brugsch-Str. 12  
08529 Plauen  
Deutschland

Telefon: +49 (0) 151 59156639  
E-Mail: Anfrage@Michael-Gahn.de  
Website: https://Michael-Gahn.de

Umsatzsteuer-Identifikationsnummer gemäß § 27a Umsatzsteuergesetz: **DE288143343**  
Steuernummer: **223/222/02451**

Die vollständige Anbieterkennzeichnung liegt zusätzlich in [`IMPRESSUM.md`](IMPRESSUM.md).

---

**MGD WordPress MCP** ist ein Open-Source-Projekt von Michael Gahn DESIGN. Ziel ist eine leistungsfähige Agenten-Schnittstelle für WordPress, ohne Sicherheit, Nachvollziehbarkeit und Kontrolle aus der Hand zu geben.
