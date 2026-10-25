# Einrichtungs-Assistent

Der Einrichtungs-Assistent führt nach der Aktivierung durch die wichtigsten Entscheidungen, bevor ein KI-Agent Zugriff erhält.

## Warum ein Assistent?

WordPress-Installationen unterscheiden sich stark. Manche Websites verwenden Divi 5, andere Elementor oder Gutenberg. Einige Kundenwebsites sind öffentlich, andere durch Security-, Passwort- oder Maintenance-Lösungen geschützt. Außerdem soll ein Agent nicht automatisch Schreib- oder Update-Rechte erhalten.

Der Assistent sammelt deshalb zunächst ungefährliche Kontextinformationen und führt anschließend Schritt für Schritt durch die notwendige lokale Verbindung.

## Start

Nach der Aktivierung versucht das Plugin Administratoren einmalig zu folgendem Bereich weiterzuleiten:

`Werkzeuge → MGD WordPress MCP → Einrichtungs-Assistent`

Nach Abschluss steht im Assistenten der Button **„Assistent neu starten“** bereit. Er startet die Anleitung erneut, ohne Sicherheitsprofil oder Website-Kontext heimlich zu verändern.

## Schritt 1: Umgebung erkennen

MGD WordPress MCP prüft den aktiven Theme-/Plugin-Kontext und versucht den verwendeten Builder zu erkennen.

Aktuell berücksichtigt:

- Divi / Divi Builder
- Elementor
- Bricks
- Beaver Builder
- Gutenberg als sicherer Fallback

Die Erkennung ist eine Hilfestellung. Der Administrator kann die Auswahl überschreiben.

## Schritt 2: KI-Agent und Betriebssystem auswählen

Zur Auswahl stehen Codex, Claude Code, Cursor / VS Code und ein anderer MCP-Client sowie macOS, Windows und Linux. Diese Auswahl wird nicht in WordPress gespeichert. Sie steuert nur die lokale, verständliche Anleitung.

Für Codex verwendet der Assistent TOML. Claude Code, Cursor und andere Stdio-kompatible Clients erhalten eine JSON-Struktur. Bei Codex unter macOS oder Linux kann zusätzlich ein Terminal-Befehl kopiert werden. Er enthält kein Kennwort, sondern fragt Benutzername und Application Password erst verdeckt im eigenen Terminal ab.

## Schritt 3: Builder auswählen

Zur Auswahl stehen Divi, Elementor, Gutenberg, Bricks, Beaver Builder und „Anderes System“.

Bei „Anderes System“ kann ein freier Name eingetragen werden. Dadurch kann das Plugin auch auf Installationen eingesetzt werden, für die noch kein eigener Adapter existiert.

## Schritt 4: Divi 5 Skill

Bei Divi wird der öffentliche MGD Divi 5 Dev Skill empfohlen:

https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL

Der Skill wird nicht ungefragt vom WordPress-Server auf einen lokalen Computer kopiert. Das wäre technisch und sicherheitlich die falsche Richtung. Stattdessen öffnet der Assistent die offizielle GitHub-Quelle. Claude Code oder Codex können den Skill anschließend auf dem Agentenrechner installieren.

## Schritt 5: MCP-Verbindung

Der Assistent verweist auf den offiziellen WordPress MCP Adapter und auf WordPress Application Passwords.

Empfohlen wird:

1. MCP Adapter installieren.
2. Separaten WordPress-Benutzer für Agenten verwenden.
3. Nur notwendige WordPress-Rollenrechte vergeben.
4. Eigenes Application Password erstellen.
5. Client verbinden.
6. Erst danach benötigte MGD-Schreibfreigaben aktivieren.

## Schritt 6: Lokale Konfiguration erzeugen

Der Assistent erzeugt den passenden Block erst lokal im Browser. Benutzername und Application Password werden dabei nicht per WordPress-Formular übertragen, nicht in WordPress gespeichert und nicht im Audit-Log erfasst. Nach dem Kopieren leert der Assistent seine Felder und blendet die Konfiguration wieder aus.

Für Codex ist der Block ein **TOML-Block** für `~/.codex/config.toml`. Claude Code, Cursor und andere Clients erhalten jeweils ihre passende JSON-Struktur. Diese Unterschiede sind wichtig: Ein JSON-Block für Codex wird nicht automatisch als MCP-Server erkannt.

Der verwendete lokale Remote-Proxy ist bewusst auf eine getestete Version festgelegt. Dadurch kann ein künftiges, unbemerktes `@latest`-Update nicht ohne Prüfung die Verbindung verändern.

Nach dem Einfügen der Codex-Konfiguration:

1. Konfiguration lokal kopieren oder auf macOS/Linux den Terminal-Befehl verwenden.
2. `codex mcp list` ausführen.
3. Einen neuen Codex-Chat starten und zuerst eine reine Leseaufgabe erteilen.
4. Erst nach erfolgreichem Handshake das Sicherheitsprofil bei Bedarf erweitern.

## Frontend-Schutz

Bereits im Assistenten wird auf typische aktive Schutzplugins hingewiesen. Wird ein möglicher Schutz erkannt, soll ein Agent bei einer blockierten visuellen Prüfung den Nutzer nach dem legitimen Entsperrweg fragen.

Siehe [Frontend-Sperren und Zugang](Frontend-Sperren-und-Zugang.md).

## Was der Assistent nicht macht

Er speichert keine WordPress-Passwörter, PINs, API-Keys oder Application Passwords. Er veröffentlicht nichts, führt keine Updates aus und aktiviert keine Schreibrechte automatisch. Der Connection Doctor prüft nur die WordPress-Seite; der tatsächliche Handshake wird bewusst im jeweiligen MCP-Client geprüft.
