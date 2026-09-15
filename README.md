# MGD WordPress MCP

**WordPress trifft KI. Sicher, verständlich und kontrollierbar.**

MGD WordPress MCP verbindet WordPress mit MCP-kompatiblen KI-Agenten wie Claude Code, OpenAI Codex und anderen MCP-Clients. Das Plugin stellt klar begrenzte WordPress-Abilities für Inhalte, Medien, SEO, Divi 5, WPForms, UpdraftPlus, Updates und die Website-Umgebung bereit.

## Version 0.2.1

Version 0.2.1 überarbeitet die Benutzeroberfläche grundlegend. Das Backend ist übersichtlicher, verständlicher für Einsteiger und optisch an Michael Gahn DESIGN angelehnt: Schwarz, Weiß und Rot mit einer dezenten KI- und Vibecoding-Anmutung.

Neu sind ein lokales Plugin-Icon, eine lokale Headergrafik, eine modernisierte Übersicht, verständlichere Statusanzeigen, Schnellzugriffe, eine ausführlichere Plugin-Detailansicht sowie direkte Links zu Website, GitHub und Wiki.

### Privacy by Default

Die Admin-Oberfläche benötigt für ihre Darstellung **keine externen Server**. Es werden keine Google Fonts, Font-CDNs, Icon-CDNs, JavaScript-CDNs oder extern eingebettete Bilder geladen. Schrift wird über den lokalen System-/WordPress-Fontstack dargestellt. Plugin-Icon und Headergrafik liegen im Plugin selbst.

Externe Ziele wie GitHub, Wiki oder Michael-Gahn.de werden nur geöffnet, wenn ein Benutzer den entsprechenden Link bewusst anklickt. Eine Ausnahme ist die optionale GitHub-Release-Prüfung für Plugin-Updates. Sie ist eine funktionale Server-zu-Server-Abfrage und kann in den Einstellungen deaktiviert werden.

## Sicherheitsmodell

Schreibzugriffe sind nach der Installation standardmäßig deaktiviert. Divi-Schreibzugriff und Wartungsaktionen besitzen zusätzliche Freigaben. WordPress-Benutzerrechte bleiben maßgeblich. Inhalte werden über das Content-Tool nicht endgültig gelöscht, sondern in den Papierkorb verschoben. Agenten-Aktionen können lokal im Audit-Log nachvollzogen werden.

## Unterstützte Bereiche

WordPress Beiträge und Seiten, Mediathek, Rank Math und Yoast, Divi 5, WPForms, UpdraftPlus, Plugin- und Theme-Updates, Builder-Erkennung und Erkennung typischer Frontend-Sperren.

Der direkte Base64-Medienupload bleibt sicherheitsbedingt deaktiviert, bis die Dateitypvalidierung vollständig gehärtet ist.

## Voraussetzungen

WordPress 6.9 oder neuer, PHP 7.4 oder neuer, HTTPS für Remote-Verbindungen und der offizielle WordPress MCP Adapter.

## Installation

Installiere die Release-ZIP `mgd-wordpress-mcp.zip` über **Plugins → Plugin hinzufügen → Plugin hochladen**. Nach der Aktivierung führt der Einrichtungs-Assistent durch Builder-Erkennung, MCP Adapter, Verbindung und Freigaben.

Der MCP-Endpunkt lautet:

```text
https://DEINE-DOMAIN.TLD/wp-json/mgd-wordpress-mcp/v1/mcp
```

Für Agenten sollte ein separater WordPress-Benutzer mit minimal notwendigen Rechten und ein ausschließlich für diese Verbindung verwendeter Zugang eingerichtet werden. Zugangsdaten gehören niemals in GitHub-Repositories oder öffentliche Dokumentation.

## Divi 5

Für Divi 5 ergänzt der kostenlose [MGD Divi 5 Dev Skill](https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL) das MCP-Plugin um Arbeitsregeln und Divi-Wissen für Claude Code und Codex.

## Updates über WordPress

MGD WordPress MCP prüft optional die GitHub Releases dieses Repositorys. Enthält ein neuerer Release das Asset `mgd-wordpress-mcp.zip`, kann WordPress die neue Version im normalen Plugin-Update-System anzeigen und installieren.

Der Release-Workflow prüft vor Veröffentlichung die Versionskonsistenz und PHP-Syntax. Git-Tag, Plugin-Version und `Stable tag` müssen übereinstimmen.

## Dokumentation

Die ausführliche Dokumentation liegt im Ordner [wiki/](wiki/).

Wichtige Seiten sind [Einrichtungs-Assistent](wiki/Einrichtungs-Assistent.md), [Sicherheit und Berechtigungen](wiki/Sicherheit-und-Berechtigungen.md), [MCP und Architektur](wiki/MCP-und-Architektur.md), [Divi 5 Integration](wiki/Divi-5-Integration.md), [Updates und Wartung](wiki/Updates-und-Wartung.md) sowie [Datenschutz und Rechtliches](wiki/Datenschutz-und-Rechtliches.md).

## Weitere MGD-Projekte

[MGD AI Kennzeichnung WordPress](https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-WordPress) · [MGD Divi 5 Dev Skill](https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL) · [MGD Blogpost Skill](https://github.com/MichaelGahnDESIGN/MGD_Blogpost-Skill)

## Lizenz

GPL-2.0-or-later. Siehe [LICENSE](LICENSE).

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
