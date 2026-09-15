# MGD WordPress MCP Wiki

Willkommen im Wiki von **MGD WordPress MCP**.

MGD WordPress MCP verbindet WordPress über die **WordPress Abilities API** und den offiziellen **WordPress MCP Adapter** mit MCP-kompatiblen Agenten wie Claude Code, Codex und anderen Clients. Ziel ist eine nachvollziehbare, granular freigegebene Werkzeugschicht statt eines unkontrollierten Administratorzugriffs.

## Aktueller Entwicklungsstand: 0.2.2

Der Plugin-Kern ist implementiert und wurde bereits auf einer realen WordPress-7.1-/PHP-8.5-Installation aktiviert. Dort wurden Abilities API, MCP Adapter, HTTPS und Divi 5 erfolgreich erkannt.

Version 0.2.1 brachte die modernisierte, lokal ausgelieferte Admin-Oberfläche. Version **0.2.2** korrigiert die WordPress-Updateerkennung, nachdem der erste reale Update-Test gezeigt hatte, dass WordPress den vorhandenen GitHub-Release noch nicht als Update angeboten hat.

Die aktuelle Implementierung umfasst WordPress-Inhalte und Medien, Rank Math/Yoast, eine konservative Divi-5-Integration, WPForms-Bridge, UpdraftPlus-Backup-Trigger, kontrollierte Plugin- und Theme-Updates, Builder-Erkennung, Frontend-Schutz-Erkennung, Audit-Log und GitHub-basierte Self-Updates.

Schreibzugriffe sind standardmäßig deaktiviert. Divi-Schreibzugriff, externe Medienimporte und Wartungsaktionen besitzen zusätzliche Freigaben.

Der Base64-Direktupload bleibt **fail-closed deaktiviert**, bis die Dateitypvalidierung vollständig gehärtet ist.

## Was ist bereits real geprüft?

- Plugin-Installation und Aktivierung auf WordPress 7.1.
- PHP 8.5.x auf der Testinstallation.
- Abilities API erkannt.
- offizieller MCP Adapter erkannt.
- HTTPS erkannt.
- Divi 5 erkannt.
- möglicher Frontend-Schutz erkannt.
- GitHub Release 0.2.0 gebaut.
- GitHub Release 0.2.1 inklusive `mgd-wordpress-mcp.zip` gebaut.
- CI für mehrere PHP-Versionen eingerichtet.

## Was muss noch real getestet werden?

- korrigierte WordPress-Updateerkennung aus Version 0.2.2.
- MCP-Handshake mit Claude Code.
- MCP-Handshake mit Codex.
- Content-Lese-/Schreibworkflow.
- Divi-5-Lese-/Schreibworkflow inklusive Revision.
- WPForms.
- Rank Math/Yoast.
- UpdraftPlus-Backup und Abschlusskontrolle.
- kontrolliertes Plugin-Update über MCP.
- Netzwerkprüfung der Admin-UI auf externe Ressourcen.

Die vollständige, sehr detaillierte Checkliste steht in [`../agent-readme.md`](../agent-readme.md).

## Privacy by Default

Die Admin-Oberfläche lädt keine Google Fonts, Font-CDNs, Icon-CDNs, JavaScript-CDNs oder extern eingebetteten UI-Bilder. Schrift wird über lokale System-/WordPress-Fonts dargestellt. Branding-Assets liegen im Plugin.

Die optionale GitHub-Release-Prüfung ist eine getrennte Server-zu-Server-Funktion und kann deaktiviert werden.

## Technischer Unterbau

MGD WordPress MCP setzt WordPress 6.9 oder neuer voraus. Der Endpoint lautet:

```text
https://DEINE-DOMAIN.TLD/wp-json/mgd-wordpress-mcp/v1/mcp
```

Für Remote-Zugriffe werden HTTPS, ein eigener WordPress-Benutzer mit minimal erforderlichen Rechten und ein ausschließlich für MCP verwendetes Application Password empfohlen.

## Releases und WordPress-Updates

Ein veröffentlichter GitHub Release muss das Asset `mgd-wordpress-mcp.zip` enthalten. Der Release-Workflow prüft Git-Tag, Plugin-Version, `Stable tag` und PHP-Syntax.

Der Updater von 0.2.2 erzeugt vollständige WordPress-Update- und `no_update`-Metadaten unabhängig davon, ob WordPress seine interne `checked`-Liste beim Filteraufruf bereits vollständig aufgebaut hat.

Details: [Updates und Wartung](Updates-und-Wartung.md).

## Einstieg

Neue Nutzer beginnen mit [Installation und Voraussetzungen](Installation-und-Voraussetzungen.md), danach [Einrichtungs-Assistent](Einrichtungs-Assistent.md) und [Sicherheit und Berechtigungen](Sicherheit-und-Berechtigungen.md).

Weitere Dokumentation:

* [MCP und Architektur](MCP-und-Architektur.md)
* [Divi 5 Integration](Divi-5-Integration.md)
* [Builder und Agent Skills](Builder-und-Agent-Skills.md)
* [Frontend-Sperren und Zugang](Frontend-Sperren-und-Zugang.md)
* [WPForms Integration](WPForms-Integration.md)
* [UpdraftPlus und Backups](UpdraftPlus-und-Backups.md)
* [SEO mit Rank Math und Yoast](SEO-Rank-Math-und-Yoast.md)
* [Updates und Wartung](Updates-und-Wartung.md)
* [Datenschutz und Rechtliches](Datenschutz-und-Rechtliches.md)
* [Fehlerbehebung](Fehlerbehebung.md)
* [Roadmap](Roadmap.md)
* [Agenten-/Entwicklercheckliste](../agent-readme.md)

## Lizenz

**GPL-2.0-or-later**.

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
