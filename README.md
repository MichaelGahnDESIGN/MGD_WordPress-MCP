# MGD WordPress MCP

**WordPress trifft KI. Sicher, verständlich und kontrollierbar.**

MGD WordPress MCP verbindet WordPress mit MCP-kompatiblen KI-Agenten wie Claude Code, OpenAI Codex und anderen MCP-Clients. Das Plugin stellt klar begrenzte WordPress-Abilities für Inhalte, Medien, SEO, Divi 5, WPForms, UpdraftPlus, Updates und die Website-Umgebung bereit.

## Aktueller Entwicklungsstand: 0.2.2

Version **0.2.2** korrigiert insbesondere die WordPress-Updateerkennung. Der Updater wartet nicht mehr darauf, dass WordPress beim Filteraufruf bereits eine vollständige `checked`-Liste aufgebaut hat, sondern liefert ein vollständiges Update- beziehungsweise `no_update`-Objekt mit Versions-, Paket-, Kompatibilitäts- und Icon-Metadaten.

Version 0.2.1 brachte die neue Benutzeroberfläche: Schwarz, Weiß und Rot im Stil von Michael Gahn DESIGN, lokale Branding-Assets und eine dezente KI-/Vibecoding-Anmutung.

> **Projektstatus:** Der Plugin-Kern, Release-Build und die Admin-Grundfunktionen sind vorhanden. Der reale End-to-End-Test der MCP-Verbindung und der korrigierten WordPress-Updateanzeige ist noch Teil der Release-Validierung. Eine sehr ausführliche Arbeitscheckliste steht in [`agent-readme.md`](agent-readme.md).

## Privacy by Default

Die Admin-Oberfläche benötigt für ihre Darstellung **keine externen UI-Server**. Es werden keine Google Fonts, Font-CDNs, Icon-CDNs, JavaScript-CDNs oder extern eingebettete Bilder geladen. Schrift wird über den lokalen System-/WordPress-Fontstack dargestellt. Plugin-Icon und Headergrafik liegen im Plugin selbst.

Externe Ziele wie GitHub, Wiki oder Michael-Gahn.de werden nur geöffnet, wenn ein Benutzer den entsprechenden Link bewusst anklickt. Eine Ausnahme ist die optionale GitHub-Release-Prüfung für Plugin-Updates. Sie ist eine funktionale Server-zu-Server-Abfrage und kann deaktiviert werden.

## Sicherheitsmodell

Schreibzugriffe sind nach der Installation standardmäßig deaktiviert. Divi-Schreibzugriff, externe Medienimporte und Wartungsaktionen besitzen zusätzliche Freigaben. WordPress-Benutzerrechte bleiben maßgeblich. Inhalte werden über das Content-Tool nicht endgültig gelöscht, sondern in den Papierkorb verschoben. Agenten-Aktionen können lokal im Audit-Log nachvollzogen werden.

Der direkte Base64-Medienupload bleibt **fail-closed deaktiviert**, bis tatsächlicher Dateityp, Dateiendung und MIME-Typ ausreichend gehärtet validiert werden können.

## Unterstützte Bereiche

| Bereich | Stand |
|---|---|
| WordPress Inhalte | implementiert |
| Mediathek / Beitragsbild | implementiert, Base64-Direktupload deaktiviert |
| Rank Math / Yoast | implementiert |
| Divi 5 | konservative Integration implementiert, Praxistests laufen |
| WPForms | Bridge zu offiziellen Abilities implementiert |
| UpdraftPlus | Backup-Trigger implementiert |
| Plugin-/Theme-Wartung | Einzelupdates mit Freigabe implementiert |
| Builder-Erkennung | Divi, Elementor, Gutenberg, Bricks, Beaver Builder |
| Frontend-Schutz | heuristische Erkennung implementiert |
| Audit-Log | implementiert |
| GitHub Releases | automatischer ZIP-Build implementiert |
| WordPress Self-Updates | 0.2.2 enthält korrigierte Discovery, End-to-End-Test offen |

## Voraussetzungen

WordPress 6.9 oder neuer, PHP 7.4 oder neuer, HTTPS für Remote-Verbindungen und der offizielle WordPress MCP Adapter.

## Installation

Installiere die Release-ZIP `mgd-wordpress-mcp.zip` über **Plugins → Plugin hinzufügen → Plugin hochladen**. Nach der Aktivierung führt der Einrichtungs-Assistent durch Builder-Erkennung, MCP Adapter, Verbindung und Freigaben.

Offizieller WordPress MCP Adapter:
https://github.com/WordPress/mcp-adapter

## Verbindung

Der Endpoint folgt diesem Schema:

```text
https://DEINE-DOMAIN.TLD/wp-json/mgd-wordpress-mcp/v1/mcp
```

Für Remote-Zugriffe sollte ein separater WordPress-Benutzer mit minimal erforderlichen Rechten und ein eigenes Application Password verwendet werden. Zugangsdaten gehören in den Secret-/Environment-Speicher des MCP-Clients und nicht in GitHub, Skills oder Dokumentationen.

## Divi 5

Für Divi-Projekte empfiehlt das Plugin zusätzlich den kostenlosen **MGD Divi 5 Dev Skill**:
https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL

Der Skill liefert Agenten Divi-Wissen und Arbeitsregeln. Das WordPress-Plugin stellt die kontrollierten Werkzeuge bereit.

## Frontend-Sperren

MGD WordPress MCP erkennt typische aktive Shield-, Passwort-, Restricted-Access-, Maintenance- und Coming-Soon-Plugins heuristisch. Wird eine visuelle Prüfung blockiert, soll der Agent den autorisierten Nutzer nach dem legitimen Entsperrweg fragen. PINs und Passwörter werden nicht als Plugin-Konfiguration oder Audit-Daten gespeichert.

## Updates über WordPress

MGD WordPress MCP prüft optional die GitHub Releases dieses Repositorys. Ein gültiger Release enthält das Asset:

```text
mgd-wordpress-mcp.zip
```

Ist die GitHub-Version neuer als die installierte Version, soll WordPress sie über sein normales Plugin-Updatesystem anbieten. Version 0.2.2 enthält eine Korrektur für die Discovery dieses Updates. Der nächste reale Test ist eine ältere installierte Version gegen einen veröffentlichten 0.2.2-Release.

Der Release-Workflow kontrolliert Git-Tag, Plugin-Version, `Stable tag` und PHP-Syntax, bevor die ZIP gebaut wird.

## Qualitätssicherung

CI prüft aktuell PHP 7.4, 8.1, 8.3 und 8.4. Das Plugin wurde außerdem bereits auf einer realen WordPress-7.1-/PHP-8.5-Testinstallation aktiviert. Dort wurden Abilities API, MCP Adapter, HTTPS und Divi 5 erkannt.

Noch ausstehende reale Funktionsprüfungen sind transparent in [`agent-readme.md`](agent-readme.md) dokumentiert.

## Dokumentation

Die ausführliche Dokumentation liegt im Ordner [`wiki/`](wiki/). Wichtige Einstiege:

* [Wiki Home](wiki/Home.md)
* [Installation und Voraussetzungen](wiki/Installation-und-Voraussetzungen.md)
* [Einrichtungs-Assistent](wiki/Einrichtungs-Assistent.md)
* [MCP und Architektur](wiki/MCP-und-Architektur.md)
* [Sicherheit und Berechtigungen](wiki/Sicherheit-und-Berechtigungen.md)
* [Divi 5 Integration](wiki/Divi-5-Integration.md)
* [Frontend-Sperren und Zugang](wiki/Frontend-Sperren-und-Zugang.md)
* [Updates und Wartung](wiki/Updates-und-Wartung.md)
* [Datenschutz und Rechtliches](wiki/Datenschutz-und-Rechtliches.md)
* [Roadmap](wiki/Roadmap.md)
* [Agenten-/Entwicklercheckliste](agent-readme.md)

## Passende MGD-Projekte

* MGD Divi 5 Dev Skill: https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL
* MGD AI Kennzeichnung WordPress: https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-WordPress
* MGD Blogpost Skill: https://github.com/MichaelGahnDESIGN/MGD_Blogpost-Skill

## Lizenz

**GPL-2.0-or-later**. Siehe [`LICENSE`](LICENSE).

## Sicherheit

Sicherheitslücken bitte nicht als öffentliches Issue mit Exploitdetails veröffentlichen. Siehe [`SECURITY.md`](SECURITY.md) und [`SECURITY-AUDIT.md`](SECURITY-AUDIT.md).

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

Die Anbieterkennzeichnung steht zusätzlich vollständig in [`IMPRESSUM.md`](IMPRESSUM.md).

---

**MGD WordPress MCP** ist ein Open-Source-Projekt von Michael Gahn DESIGN. Ziel ist eine leistungsfähige WordPress-Schnittstelle für moderne KI-Agenten, ohne Kontrolle, Datenschutz und Nachvollziehbarkeit aufzugeben.
