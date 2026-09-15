# MGD WordPress MCP Wiki

Willkommen im Wiki von **MGD WordPress MCP**.

MGD WordPress MCP verbindet WordPress über die **WordPress Abilities API** und den offiziellen **WordPress MCP Adapter** mit MCP-kompatiblen Agenten. Ziel ist eine nachvollziehbare, granular freigegebene Werkzeugschicht statt eines unkontrollierten Administratorzugriffs.

Das Plugin richtet sich an Agenturen, Entwickler, Website-Betreiber und technisch versierte Anwender. Jede WordPress-Installation wird separat authentifiziert und kann eigene Freigaben erhalten.

## Aktueller Stand: Version 0.2.0

Version **0.2.0** ist als erster öffentlicher Release Candidate vorbereitet. Der Quellcode wird automatisch über GitHub Actions mit PHP 7.4, 8.1, 8.3 und 8.4 geprüft. Plugin-Version und WordPress `Stable tag` müssen übereinstimmen.

Die Version umfasst:

* WordPress-Inhalte und Medien
* Rank Math und Yoast SEO
* konservative Divi-5-Integration
* WPForms-Bridge
* UpdraftPlus-Backup-Trigger
* kontrollierte Plugin- und Theme-Updates
* Einrichtungs-Assistent
* Builder-Erkennung
* Erkennung typischer Frontend-Sperren
* lokales Audit-Log
* GitHub-basiertes WordPress-Update-System

Schreibzugriffe sind standardmäßig deaktiviert. Divi-Schreibzugriffe und Wartungsaktionen besitzen zusätzliche Freigaben.

Der Base64-Direktupload wurde vor dem ersten Release bewusst **fail-closed deaktiviert**. Er wird weder als MCP-Tool angeboten noch extern registriert, solange die Dateitypvalidierung nicht vollständig gehärtet ist. Medien können weiterhin über die freigegebenen sicheren Wege verarbeitet werden.

## Technischer Unterbau

MGD WordPress MCP setzt WordPress 6.9 oder neuer voraus und verwendet den offiziellen WordPress MCP Adapter als Transport- und Protokollschicht.

Der Endpoint lautet standardmäßig:

```text
https://DEINE-DOMAIN.TLD/wp-json/mgd-wordpress-mcp/v1/mcp
```

Für Remote-Zugriffe empfiehlt sich HTTPS, ein eigener WordPress-Benutzer mit minimal erforderlichen Rechten und ein ausschließlich für MCP verwendetes Application Password.

## Release und Updates

Die installierte Plugin-Version prüft GitHub Releases des Repositorys `MichaelGahnDESIGN/MGD_WordPress-MCP`. Ein veröffentlichter Release muss das Asset `mgd-wordpress-mcp.zip` enthalten.

Der Release-Workflow akzeptiert einen Tag nur, wenn Git-Tag, Plugin-Version und `Stable tag` übereinstimmen und alle PHP-Dateien den Syntaxcheck bestehen. Dadurch können neue Versionen anschließend über das normale WordPress-Update-System angeboten werden.

Details stehen unter [Updates und Wartung](Updates-und-Wartung.md) und im Security Audit des Repositorys.

## Einstieg

Neue Nutzer beginnen mit [Installation und Voraussetzungen](Installation-und-Voraussetzungen.md) und anschließend mit dem [Einrichtungs-Assistenten](Einrichtungs-Assistent.md).

Vor der ersten Schreibfreigabe sollte [Sicherheit und Berechtigungen](Sicherheit-und-Berechtigungen.md) gelesen werden.

Weitere wichtige Seiten:

* [MCP und Architektur](MCP-und-Architektur.md)
* [Divi 5 Integration](Divi-5-Integration.md)
* [Builder und Agent Skills](Builder-und-Agent-Skills.md)
* [Frontend-Sperren und Zugang](Frontend-Sperren-und-Zugang.md)
* [WPForms Integration](WPForms-Integration.md)
* [UpdraftPlus und Backups](UpdraftPlus-und-Backups.md)
* [SEO mit Rank Math und Yoast](SEO-Rank-Math-und-Yoast.md)
* [Datenschutz und Rechtliches](Datenschutz-und-Rechtliches.md)
* [Fehlerbehebung](Fehlerbehebung.md)
* [Roadmap](Roadmap.md)

## Projekt und Lizenz

Quellcode: https://github.com/MichaelGahnDESIGN/MGD_WordPress-MCP

Lizenz: **GPL-2.0-or-later**

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
