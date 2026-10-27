# Changelog

## 0.5.15 – 2026-10-27

- Icons und Hintergründe der Karten unter „Weitere Plugins“ kommen jetzt aus theme.michael-gahn.de. Der Server holt sie einmal und speichert sie lokal, der Browser lädt nichts von extern. Ohne Bild bleibt das mitgelieferte Motiv. Versionen werden ohne führendes „v“ angezeigt.

## 0.5.14 – 2026-10-26

- Plugin-Navigation in die vereinbarte Reihenfolge gebracht und um den Link zur gemeinsamen Seite „Plugin-Empfehlungen“ ergänzt.
- Einrichtungsassistent von einer langen Seite zu einem geführten Ablauf mit sechs einzelnen Schritten, Fortschrittsanzeige sowie Weiter- und Zurück-Navigation umgebaut.
- Progressive Enhancement: Ohne JavaScript bleiben alle Informationen und Formulare erreichbar; mit JavaScript wird jeweils nur der aktuelle Schritt angezeigt.
- Formular-Weiterleitungen für Builder und Sicherheitsprofil führen zurück zu Schritt 5.

## 0.5.13 – 2026-10-26

- Geführter Einrichtungs-Assistent für Agent und Betriebssystem ergänzt.
- Separate Auswahl für Codex, Claude Code, Cursor / VS Code und andere MCP-Clients sowie macOS, Windows und Linux.
- Die lokale Konfiguration entsteht ausschließlich im Browser: Application Password und Benutzername werden weder an WordPress gesendet noch gespeichert und nach dem Kopieren entfernt.
- Optionaler Codex-Terminalweg für macOS und Linux fragt das Kennwort verdeckt erst im lokalen Terminal ab; es erscheint weder im kopierten Befehl noch im Plugin.
- Assistent kann nach Abschluss bewusst neu gestartet werden.

## 0.5.12 – 2026-10-25

- Codex erhält eine echte TOML-Konfiguration statt einer unpassenden JSON-Kopie.
- Der lokale WordPress-Remote-Proxy wird auf eine getestete Version festgelegt; `@latest` wird nicht mehr verwendet.
- Einrichtungsassistent trennt Website-Voraussetzungen klar vom echten Client-Handshake.
- Connection Doctor liefert der Admin-Oberfläche eindeutige Prüfzustände.
- Regressionstest für Client-Konfigurationen ergänzt.

## 0.5.10 – 2026-10-22

- GitHub-Prüfungen auf verfügbare GitHub-Runner umgestellt.
- Plugin-Icon lokal eingebunden und im Release-Paket enthalten.
- Darstellung in der Erweiterungsverwaltung ergänzt beziehungsweise verbessert.
- Keine Änderung an Fachlogik, Einstellungen oder Datenbank.

Alle wesentlichen Änderungen an **MGD WordPress MCP** werden hier dokumentiert.

## [0.2.1] - 2026-09-15

### Hinzugefügt

- lokales MGD WordPress MCP Plugin-Icon
- lokale KI-/Vibecoding-Herografik in Schwarz, Weiß und Rot
- modernisierte Übersichtsseite mit Funktionskarten, Systemstatus und Ressourcen
- direkte Links zu Michael-Gahn.de, GitHub und Wiki in der WordPress-Pluginliste
- ausführlichere WordPress-Plugin-Detailansicht
- Privacy-by-Default-Dokumentation

### Geändert

- Admin-Oberfläche deutlich vereinfacht und für Einsteiger verständlicher aufgebaut
- Plugin-Metadaten und Ressourcenlinks verbessert
- Plugin-Version und Stable Tag auf 0.2.1 angehoben
- UI verwendet ausschließlich lokale Assets und den System-/WordPress-Fontstack

### Datenschutz

- keine extern geladenen Fonts
- keine Icon-CDNs
- keine JavaScript- oder CSS-CDNs für die Plugin-Oberfläche
- keine extern eingebetteten UI-Bilder
- GitHub wird automatisch nur für die optional aktivierte Release-/Updateprüfung kontaktiert

## [0.2.0] - 2026-09-15

- Einrichtungs-Assistent
- Builder-Erkennung
- Frontend-Sperren-Erkennung
- Divi 5 Dev Skill Verknüpfung
- GitHub-Update-System
- Security- und Datenschutz-Härtung

## [0.1.0] - 2026-09-14

- erster öffentlicher Plugin-Kern auf Basis der WordPress Abilities API
- Inhalte, Medien, SEO, Divi, WPForms, UpdraftPlus und Wartung
- Audit-Log und GitHub Release Updater
