# MGD WordPress MCP Wiki

Willkommen im Wiki von **MGD WordPress MCP**.

MGD WordPress MCP verbindet WordPress über die WordPress Abilities API und den offiziellen WordPress MCP Adapter mit MCP-kompatiblen Agenten wie Claude Code, Codex und anderen Clients. Ziel ist eine nachvollziehbare, granular freigegebene Werkzeugschicht statt eines unkontrollierten Administratorzugriffs.

## Aktueller Entwicklungsstand: 0.2.8

Der Plugin-Kern läuft auf einer realen WordPress-7.1-/PHP-8.5.x-Testinstallation. Abilities API, MCP Adapter, HTTPS und Divi 5 werden dort erkannt.

Besonders wichtig: Die komplette **GitHub → WordPress Self-Update-Kette ist erfolgreich End-to-End getestet**. Eine installierte Version 0.2.7 erkannte den nachträglich veröffentlichten Release 0.2.8 automatisch über WordPress.

WordPress zeigte dabei Plugin-Icon, installierte Version, neue Version, Versionsdetails und Kompatibilitätsinformation korrekt an.

## Bereits real verifiziert

- [x] Installation und Aktivierung.
- [x] WordPress 7.1.
- [x] PHP 8.5.x auf der Testinstallation.
- [x] Abilities API erkannt.
- [x] offizieller MCP Adapter erkannt.
- [x] HTTPS erkannt.
- [x] Divi 5 erkannt.
- [x] möglicher Frontend-Schutz erkannt.
- [x] automatischer GitHub Release-Build.
- [x] Release-Asset `mgd-wordpress-mcp.zip`.
- [x] WordPress erkennt einen nachträglich veröffentlichten neuen Release.
- [x] Self-Update Discovery 0.2.7 → 0.2.8.

## Nächste reale Tests

- [ ] MCP-Handshake mit Claude Code.
- [ ] MCP-Handshake mit Codex.
- [ ] Read-only Website-/Content-Abfrage.
- [ ] kontrollierter Content-Schreibtest.
- [ ] Audit-Log des Schreibtests kontrollieren.
- [ ] Divi-5-Lese-/Schreibworkflow inklusive Revision.
- [ ] WPForms.
- [ ] Rank Math/Yoast.
- [ ] UpdraftPlus-Backup und Abschlusskontrolle.
- [ ] kontrolliertes Plugin-Update über MCP.
- [ ] Netzwerkprüfung der Admin-UI auf externe UI-Ressourcen.

Die vollständige technische Checkliste steht in [`../agent-readme.md`](../agent-readme.md).

## Privacy by Default

Die Admin-Oberfläche lädt keine Google Fonts, Font-CDNs, Icon-CDNs, JavaScript-CDNs oder extern eingebetteten UI-Bilder. Schrift wird über lokale System-/WordPress-Fonts dargestellt. Branding-Assets liegen im Plugin.

Es gibt keine Telemetrie, keine Werbung und kein Tracking.

Die optionale GitHub-Release-Prüfung ist eine funktionale Server-zu-Server-Verbindung und kann deaktiviert werden.

## Sicherheitsprinzip

Schreibzugriffe sind standardmäßig deaktiviert. Divi-Schreibzugriff, externe Medienimporte und Wartungsaktionen besitzen zusätzliche Freigaben. WordPress-Capabilities bleiben maßgeblich.

Riskante Aktionen werden nicht allein deshalb erlaubt, weil ein MCP-Client verbunden ist. Das Plugin kombiniert WordPress-Rechte, eigene Freigaben, Bestätigungstoken und Auditierung.

Der Base64-Direktupload bleibt fail-closed deaktiviert, bis die Dateitypvalidierung vollständig gehärtet ist.

## Technischer Unterbau

MGD WordPress MCP setzt WordPress 6.9 oder neuer und PHP 7.4 oder neuer voraus.

Der Endpoint lautet:

```text
https://DEINE-DOMAIN.TLD/wp-json/mgd-wordpress-mcp/v1/mcp
```

Für Remote-Zugriffe werden HTTPS, ein separater WordPress-Benutzer mit minimal erforderlichen Rechten und ein ausschließlich für den jeweiligen MCP-Client verwendetes Application Password empfohlen.

Secrets dürfen nicht in GitHub, Dokumentationen, Screenshots oder Chats gespeichert werden.

## Builder

Der Einrichtungs-Assistent berücksichtigt Divi 5, Elementor, Gutenberg, Bricks, Beaver Builder und ein manuell definierbares anderes System.

Für Divi 5 empfiehlt MGD WordPress MCP zusätzlich den MGD Divi 5 Dev Skill. Das Plugin installiert lokale Agent-Skills nicht ungefragt auf einem Benutzerrechner.

## Frontend-Schutz

Typische Shield-, Passwort-, Restricted-Access-, Maintenance- und Coming-Soon-Lösungen können heuristisch erkannt werden. Wird ein Agent bei einer visuellen Prüfung tatsächlich blockiert, soll er den autorisierten Nutzer nach dem legitimen Entsperrweg fragen.

MGD WordPress MCP umgeht keine Schutzmechanismen und speichert keine PINs oder Passwörter im Audit-Log.

## Self-Updater: erfolgreich verifiziert

Ein veröffentlichter GitHub Release muss das Asset

```text
mgd-wordpress-mcp.zip
```

enthalten.

Der Release-Workflow prüft Versionsmetadaten und PHP-Syntax und baut anschließend die installierbare ZIP mit stabilem Plugin-Ordner.

### Der erfolgreiche Live-Test

Am 15. September 2026 lief Version 0.2.7 auf der WordPress-Testinstallation. Anschließend wurde v0.2.8 auf GitHub veröffentlicht und das Release-Asset automatisch erzeugt. Nach **Dashboard → Aktualisierungen → Erneut überprüfen** erkannte WordPress 0.2.8 automatisch als verfügbares Plugin-Update.

Damit ist die Discovery-Kette real bestätigt.

### Die Fehler der frühen Versionen

Die ersten Implementierungen verließen sich zu stark auf bestimmte Zustände des WordPress-Update-Transients. Danach zeigte ein weiterer Test, dass ein Release-Cache eine gerade neu veröffentlichte Version verdecken konnte.

0.2.7 wurde deshalb am bereits funktionierenden Updater von MGD AI Kennzeichnung WordPress ausgerichtet. Der klassische WordPress-Update-Transient wird unterstützt, die Update-URI-Integration bleibt ergänzend bestehen und ein Cache wird neu abgefragt, wenn er keine Version enthält, die neuer als die installierte Version ist.

Details: [Updates und Wartung](Updates-und-Wartung.md).

## Dokumentationsstruktur

Neue Nutzer beginnen mit:

1. [Installation und Voraussetzungen](Installation-und-Voraussetzungen.md)
2. [Einrichtungs-Assistent](Einrichtungs-Assistent.md)
3. [Sicherheit und Berechtigungen](Sicherheit-und-Berechtigungen.md)
4. [MCP und Architektur](MCP-und-Architektur.md)

Danach stehen folgende Themen bereit:

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

GPL-2.0-or-later.

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
