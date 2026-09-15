# Agent README · MGD WordPress MCP

Diese Datei ist die zentrale Arbeits- und Übergabedokumentation für KI-Agenten und Entwickler, die an **MGD WordPress MCP** weiterarbeiten.

Repository: `MichaelGahnDESIGN/MGD_WordPress-MCP`  
Projekt: **MGD WordPress MCP**  
Aktueller Entwicklungsstand im Repository: **0.2.2**  
Lizenz: **GPL-2.0-or-later**  
Autor: **Michael Gahn DESIGN**

> Diese Checkliste unterscheidet bewusst zwischen implementiert, technisch geprüft und real auf einer WordPress-Installation verifiziert. Ein vorhandener Codepfad ist nicht automatisch ein bestandener End-to-End-Test.

## 1. Projektziel

- [x] Öffentliche, kostenlos nutzbare WordPress-MCP-Lösung entwickeln.
- [x] WordPress über standardisierte Abilities und den offiziellen WordPress MCP Adapter mit MCP-kompatiblen Agenten verbinden.
- [x] Claude Code, Codex und andere MCP-Clients als Zielsysteme berücksichtigen.
- [x] Keine proprietäre parallele MCP-Schnittstelle erfinden, wenn WordPress bereits passende Standards bereitstellt.
- [x] Agenten nur die Fähigkeiten geben, die der WordPress-Benutzer und die Plugin-Freigaben erlauben.
- [x] Sicherheit, Nachvollziehbarkeit und Privacy by Default als Kernprinzipien behandeln.
- [x] Plugin für Agenturen, Entwickler und normale Website-Betreiber verständlich machen.
- [ ] Nach erfolgreichem End-to-End-Test den Status von frühem Release/MVP auf produktionsreif anheben.

## 2. Basis und Kompatibilität

- [x] Plugin-Name: `MGD WordPress MCP`.
- [x] Plugin-Slug: `mgd-wordpress-mcp`.
- [x] Repository fest auf `MichaelGahnDESIGN/MGD_WordPress-MCP` ausgerichtet.
- [x] WordPress 6.9+ als Mindestversion festgelegt.
- [x] PHP 7.4+ als Mindestversion festgelegt.
- [x] WordPress 7.1 als getestete Metadaten-Version eingetragen.
- [x] GitHub CI prüft PHP 7.4, 8.1, 8.3 und 8.4.
- [x] Plugin wurde real auf WordPress 7.1 und PHP 8.5.x aktiviert und die Admin-Oberfläche geladen.
- [x] Abilities API wurde auf der Testinstallation erkannt.
- [x] offizieller WordPress MCP Adapter wurde auf der Testinstallation erkannt.
- [x] HTTPS wurde auf der Testinstallation erkannt.
- [ ] PHP 8.5 explizit in CI aufnehmen, sobald das verwendete GitHub-Runner-Setup dies stabil unterstützt.
- [ ] Multisite separat testen.
- [ ] Windows-/IIS-Hosting separat testen, falls relevant.

## 3. MCP und WordPress Abilities

- [x] eigene Ability-Kategorie registriert.
- [x] eigener MGD-MCP-Server über den offiziellen WordPress MCP Adapter vorbereitet.
- [x] MCP-Endpunkt wird in WordPress angezeigt.
- [x] Serverzugriff erfordert authentifizierten WordPress-Benutzer.
- [x] WordPress-Capabilities bleiben maßgeblich.
- [x] Read- und Write-Funktionen werden getrennt behandelt.
- [x] riskante Wartungsaktionen benötigen zusätzliche Freigaben.
- [x] direkter Base64-Dateiupload vor Release fail-closed aus der externen MCP-Oberfläche entfernt.
- [ ] echten MCP-Handshake mit Claude Code durchführen.
- [ ] echten MCP-Handshake mit Codex durchführen.
- [ ] Tool Discovery des vollständigen Ability-Sets real prüfen.
- [ ] Fehlerverhalten bei abgelaufenem/widerrufenem Application Password testen.
- [ ] Verhalten bei fehlendem MCP Adapter testen.
- [ ] Verhalten bei deaktivierter Abilities API beziehungsweise zu alter WordPress-Version testen.

## 4. Inhalte

- [x] Beiträge/Seiten/öffentliche Post Types auflisten.
- [x] einzelne Inhalte lesen.
- [x] Inhalte als Entwurf erstellen.
- [x] Titel, Inhalt, Auszug, Status und Slug aktualisieren.
- [x] optionales optimistisches Locking über Änderungszeitpunkt vorgesehen.
- [x] Inhalte nur in den Papierkorb verschieben, nicht permanent löschen.
- [x] Löschaktion benötigt expliziten Bestätigungstoken.
- [ ] End-to-End-Test: fünf Seiten über MCP auflisten.
- [ ] End-to-End-Test: Testbeitrag als Entwurf erstellen.
- [ ] End-to-End-Test: Testbeitrag aktualisieren.
- [ ] End-to-End-Test: Testbeitrag in Papierkorb verschieben.
- [ ] Rechte mit Editor-/Autor-Rolle separat prüfen.

## 5. Medien

- [x] Mediathek lesen.
- [x] externe Medienimporte als getrennte Freigabe konzipiert.
- [x] Beitragsbild setzen/entfernen vorgesehen.
- [x] Größenlimit für MCP-Medien konfigurierbar.
- [x] unsicherer Base64-Direktupload extern deaktiviert.
- [ ] Base64-Upload nur dann reaktivieren, wenn tatsächlicher Dateityp, Dateiendung, MIME-Typ und WordPress-Allowlist zuverlässig gegeneinander validiert werden.
- [ ] Medienimport von URL gegen SSRF-/Private-IP-Szenarien nochmals gezielt auditieren.
- [ ] reale Bildübertragung und Featured-Image-Zuweisung testen.
- [ ] SVG-Upload nicht eigenmächtig freischalten.

## 6. Divi 5

- [x] Divi 5 / Divi Builder automatisch erkennen.
- [x] Divi-Status über MCP verfügbar machen.
- [x] gespeichertes Divi-Layout lesen.
- [x] konservative Raw-Layout-Speicherung vorgesehen.
- [x] WordPress-Revision vor Divi-Schreiboperationen vorgesehen.
- [x] exakte Textersetzung innerhalb eines Divi-Layouts vorgesehen.
- [x] Divi-Schreibrechte besitzen eigenen Freigabeschalter.
- [x] `MGD_Divi5-Dev_SKILL` im Setup verknüpft.
- [x] Wizard erklärt, dass ein WordPress-Server nicht ungefragt lokale Skills auf dem Benutzerrechner installieren soll.
- [ ] realen Divi-5-Lesezugriff über MCP testen.
- [ ] reale Textersetzung in einer Testseite durchführen.
- [ ] Revision danach kontrollieren.
- [ ] Visual Builder nach MCP-Änderung öffnen und Layoutintegrität prüfen.
- [ ] komplexe verschachtelte Divi-5-Strukturen testen.
- [ ] Presets/Global Styles später als eigene sichere Abilities untersuchen.
- [ ] Theme Builder/Header/Footer nur nach separatem Sicherheitsdesign unterstützen.

## 7. Andere Builder

- [x] Elementor erkennen.
- [x] Gutenberg erkennen.
- [x] Bricks erkennen.
- [x] Beaver Builder erkennen.
- [x] anderes System manuell auswählbar.
- [ ] Elementor-spezifische sichere Adapter entwickeln.
- [ ] Gutenberg Block-Strukturen als strukturierte Tools anbieten.
- [ ] Bricks-Integration evaluieren.
- [ ] Beaver-Builder-Integration evaluieren.
- [ ] keine Builder-Daten blind manipulieren, bevor deren Datenmodell ausreichend verstanden und getestet ist.

## 8. WPForms

- [x] WPForms-Integration erkennen/über offizielle Abilities delegieren.
- [x] ausgewählte Aktionen wie Formulare lesen/erstellen und Felder bearbeiten konzeptionell angebunden.
- [x] WPForms-eigene Berechtigungen bleiben maßgeblich.
- [ ] auf realer WPForms-Installation Tool Discovery testen.
- [ ] Testformular erstellen.
- [ ] Felder hinzufügen/ändern.
- [ ] Formular anschließend in Divi-Seite einbinden.
- [ ] sicherstellen, dass keine Formular-Einträge mit personenbezogenen Daten unnötig an Agenten übertragen werden.

## 9. SEO

- [x] Rank Math erkennen.
- [x] Yoast erkennen.
- [x] SEO-Titel lesen/schreiben vorgesehen.
- [x] Meta-Description lesen/schreiben vorgesehen.
- [ ] Rank Math real über MCP testen.
- [ ] Yoast real testen.
- [ ] Verhalten ohne SEO-Plugin testen.
- [ ] Fokus-Keyword/Schema nur nach separater Prüfung ergänzen.

## 10. UpdraftPlus und Backups

- [x] UpdraftPlus erkennen.
- [x] vollständiges Backup über dokumentierten Hook anstoßen.
- [x] expliziter `BACKUP_NOW`-Bestätigungstoken vorgesehen.
- [x] dokumentiert, dass gestartetes Backup nicht automatisch abgeschlossen bedeutet.
- [ ] Backup real über MCP starten.
- [ ] Abschlussstatus zuverlässig ermitteln.
- [ ] Cloud-/Remote-Backup-Konfiguration testen.
- [ ] vor Wartungsaktionen optionalen Backup-Workflow automatisieren.
- [ ] Restore niemals ohne separates Sicherheits- und Bestätigungskonzept implementieren.

## 11. Plugin- und Theme-Wartung

- [x] installierte Plugins auflisten.
- [x] verfügbare Updates prüfen.
- [x] einzelnes Plugin aktualisieren.
- [x] einzelnes Theme aktualisieren.
- [x] Maintenance-Funktionen standardmäßig deaktiviert.
- [x] einzelne Updates benötigen Bestätigungstoken.
- [x] bewusst kein unkontrolliertes `update everything` als Standardwerkzeug.
- [ ] Update eines ungefährlichen Testplugins real über MCP testen.
- [ ] Fehlerfall eines fehlgeschlagenen Updates testen.
- [ ] Backup-vor-Update als orchestrierten Agentenworkflow dokumentieren und testen.
- [ ] Frontend-/Backend-Healthcheck nach Update ergänzen.

## 12. Frontend-Schutz und Zugang

- [x] typische Security-/Shield-Plugins heuristisch erkennen.
- [x] Passwortschutz erkennen.
- [x] Maintenance-/Coming-Soon-Lösungen erkennen.
- [x] Restricted-Access-Lösungen erkennen.
- [x] Agentenanweisung hinterlegt: bei tatsächlicher Blockade legitimen Entsperrweg erfragen.
- [x] PIN nur bei tatsächlichem Bedarf erfragen.
- [x] PINs und Passwörter nicht im Audit-Log speichern.
- [x] keine Funktion zum Umgehen eines Schutzes implementiert.
- [x] Testinstallation hat einen möglichen Frontend-Schutz erkannt.
- [ ] im UI anzeigen, welches konkrete Plugin die Warnung ausgelöst hat.
- [ ] False-Positive-Rate anhand realer Installationen reduzieren.
- [ ] HTTP Basic Auth, Cloudflare Access und Hosting-WAF als externe, nicht sicher automatisch erkennbare Sperren dokumentiert halten.

## 13. Sicherheit

- [x] Schreibzugriff standardmäßig deaktiviert.
- [x] Divi-Schreibzugriff separat deaktiviert.
- [x] Wartung separat deaktiviert.
- [x] externe Medienimporte separat deaktiviert.
- [x] WordPress Capability Checks.
- [x] Nonces für Admin-Aktionen.
- [x] Bestätigungstoken für riskante MCP-Aktionen.
- [x] Divi-Revisionen.
- [x] Audit-Log.
- [x] keine permanente Content-Löschung über Standardtool.
- [x] Security Policy vorhanden.
- [x] Security Audit vorhanden.
- [x] Base64-Risikofunktion fail-closed deaktiviert.
- [ ] vollständigen manuellen Code-Security-Review vor 1.0 durchführen.
- [ ] SSRF-Audit für alle URL-basierten Importfunktionen.
- [ ] XSS-/HTML-Sanitization-Test für Agenteninhalte.
- [ ] Capability-Matrix für Subscriber, Contributor, Author, Editor, Administrator automatisiert testen.
- [ ] Rate Limiting beziehungsweise Schutz gegen missbräuchlich viele MCP-Aufrufe evaluieren.
- [ ] Audit-Log-Retention konfigurierbar machen.

## 14. Datenschutz / Privacy by Default

- [x] keine Telemetrie.
- [x] keine Werbung.
- [x] keine Tracking-Pixel.
- [x] keine extern geladenen Google Fonts.
- [x] keine Font-CDNs.
- [x] keine externen Icon-CDNs.
- [x] keine JavaScript-CDNs für die Plugin-UI.
- [x] lokale SVG-Branding-Assets im Plugin.
- [x] WordPress-/System-Fontstack.
- [x] externe Links werden nur nach Benutzeraktion geöffnet.
- [x] GitHub-Updateprüfung als getrennte optionale Server-zu-Server-Verbindung dokumentiert.
- [x] GitHub-Updateprüfung deaktivierbar.
- [x] Application Passwords werden nicht vom Plugin gespeichert.
- [x] optional vollständige Datenlöschung bei Deinstallation über `MGD_WPMCP_PURGE_ON_UNINSTALL`.
- [x] Datenschutzdokumentation vorhanden.
- [ ] Netzwerkprüfung im Browser durchführen und bestätigen, dass die Admin-UI beim normalen Laden keine externen UI-Ressourcen abruft.
- [ ] Datenflussdiagramm MCP-Client ↔ WordPress für Dokumentation ergänzen.

## 15. Admin UI und UX

- [x] eigenes MGD-Branding vorgesehen.
- [x] Schwarz/Weiß/Rot als visuelle Richtung.
- [x] dezente KI-/Vibecoding-Optik.
- [x] lokale Headergrafik als SVG angelegt.
- [x] lokales Plugin-Icon als SVG angelegt.
- [x] Admin-CSS modernisiert.
- [x] Status, Wizard, Sicherheit, Audit, Verbindung und Über-das-Plugin als getrennte Bereiche.
- [x] Statusseite zeigt WordPress, PHP, Abilities API, MCP Adapter, HTTPS, Builder und Frontend-Schutz.
- [x] direkte Links zu Website, GitHub und Wiki vorgesehen.
- [x] Plugin-Detailansicht über WordPress `plugins_api` vorgesehen.
- [x] Plugin-Zeile um Ressourcenlinks erweitert beziehungsweise vorgesehen.
- [ ] UI der 0.2.1/0.2.2-Version real nach Update kontrollieren.
- [ ] Headergrafik visuell weiter an das echte Michael-Gahn-DESIGN-Logo angleichen.
- [ ] Plugin-Icon in WordPress-Pluginliste real prüfen.
- [ ] Detailmodal `Details anzeigen` real prüfen.
- [ ] responsive Darstellung auf schmalen WordPress-Adminfenstern prüfen.
- [ ] Accessibility: Tastaturnavigation, Fokuszustände, Kontrast und Screenreader-Texte prüfen.
- [ ] Wizard stärker als echte Schritt-für-Schritt-Führung mit Fortschrittsanzeige gestalten.
- [ ] bereits installierten MCP Adapter im Wizard eindeutig als erledigt markieren.
- [ ] Application-Password-Schritt noch verständlicher erklären.

## 16. Einrichtungs-Assistent

- [x] startet nach Erstaktivierung.
- [x] Builder wird erkannt.
- [x] bevorzugter Builder kann gespeichert werden.
- [x] alternatives System kann angegeben werden.
- [x] Divi Skill wird empfohlen.
- [x] Frontend-Schutz wird erwähnt.
- [x] Application-Password-Bereich verlinkt.
- [x] MCP-Endpunkt wird angezeigt.
- [x] Auswahl wird gespeichert.
- [x] Assistent kann abgeschlossen werden.
- [ ] MCP-Adapter-Status in allen Wizard-Zuständen real prüfen.
- [ ] fehlenden Adapter mit klarer Installationsanleitung und direktem offiziellen Download erklären.
- [ ] Erfolgsschritte visuell abhaken.
- [ ] optional Verbindungstest aus dem Wizard entwickeln, ohne Secrets im Plugin zu speichern.

## 17. Audit-Log

- [x] lokale eigene WordPress-Tabelle.
- [x] Zeitpunkt speichern.
- [x] WordPress-Benutzer-ID speichern.
- [x] Ability speichern.
- [x] Risikoklasse speichern.
- [x] Erfolg/Fehler speichern.
- [x] technische Kurzbeschreibung speichern.
- [x] keine PINs/Passwörter als beabsichtigte Logdaten.
- [x] Admin-Ansicht für letzte Einträge.
- [ ] Filter nach Ability, Benutzer, Risiko und Zeitraum.
- [ ] Exportfunktion datenschutzbewusst evaluieren.
- [ ] automatische Aufbewahrungsdauer konfigurierbar machen.

## 18. GitHub Updates und Releases

- [x] GitHub-basierter eigener WordPress-Updater.
- [x] Release-Asset muss exakt `mgd-wordpress-mcp.zip` heißen.
- [x] stabiler Plugin-Ordner innerhalb der ZIP.
- [x] Release-Workflow vorhanden.
- [x] Tag, Plugin-Version und `Stable tag` werden vor Release verglichen.
- [x] PHP-Syntax wird vor Release geprüft.
- [x] `v0.2.0` erfolgreich gebaut.
- [x] `v0.2.1` erfolgreich gebaut und `mgd-wordpress-mcp.zip` angehängt.
- [x] Fehler im ersten WordPress-Update-Discovery-Mechanismus erkannt.
- [x] Update-Discovery für