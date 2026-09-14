# Einrichtungs-Assistent

Der Einrichtungs-Assistent führt nach der Aktivierung durch die wichtigsten Entscheidungen, bevor ein KI-Agent Zugriff erhält.

## Warum ein Assistent?

WordPress-Installationen unterscheiden sich stark. Manche Websites verwenden Divi 5, andere Elementor oder Gutenberg. Einige Kundenwebsites sind öffentlich, andere durch Security-, Passwort- oder Maintenance-Lösungen geschützt. Außerdem soll ein Agent nicht automatisch Schreib- oder Update-Rechte erhalten.

Der Assistent sammelt deshalb zunächst ungefährliche Kontextinformationen und verweist anschließend auf die notwendigen Verbindungsschritte.

## Start

Nach der Aktivierung versucht das Plugin Administratoren einmalig zu folgendem Bereich weiterzuleiten:

`Werkzeuge → MGD WordPress MCP → Einrichtungs-Assistent`

Der Assistent kann später jederzeit erneut geöffnet werden.

## Schritt 1: Umgebung erkennen

MGD WordPress MCP prüft den aktiven Theme-/Plugin-Kontext und versucht den verwendeten Builder zu erkennen.

Aktuell berücksichtigt:

- Divi / Divi Builder
- Elementor
- Bricks
- Beaver Builder
- Gutenberg als sicherer Fallback

Die Erkennung ist eine Hilfestellung. Der Administrator kann die Auswahl überschreiben.

## Schritt 2: Builder auswählen

Zur Auswahl stehen Divi, Elementor, Gutenberg, Bricks, Beaver Builder und „Anderes System“.

Bei „Anderes System“ kann ein freier Name eingetragen werden. Dadurch kann das Plugin auch auf Installationen eingesetzt werden, für die noch kein eigener Adapter existiert.

## Schritt 3: Divi 5 Skill

Bei Divi wird der öffentliche MGD Divi 5 Dev Skill empfohlen:

https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL

Der Skill wird nicht ungefragt vom WordPress-Server auf einen lokalen Computer kopiert. Das wäre technisch und sicherheitlich die falsche Richtung. Stattdessen öffnet der Assistent die offizielle GitHub-Quelle. Claude Code oder Codex können den Skill anschließend auf dem Agentenrechner installieren.

## Schritt 4: MCP-Verbindung

Der Assistent verweist auf den offiziellen WordPress MCP Adapter und auf WordPress Application Passwords.

Empfohlen wird:

1. MCP Adapter installieren.
2. Separaten WordPress-Benutzer für Agenten verwenden.
3. Nur notwendige WordPress-Rollenrechte vergeben.
4. Eigenes Application Password erstellen.
5. Client verbinden.
6. Erst danach benötigte MGD-Schreibfreigaben aktivieren.

## Frontend-Schutz

Bereits im Assistenten wird auf typische aktive Schutzplugins hingewiesen. Wird ein möglicher Schutz erkannt, soll ein Agent bei einer blockierten visuellen Prüfung den Nutzer nach dem legitimen Entsperrweg fragen.

Siehe [Frontend-Sperren und Zugang](Frontend-Sperren-und-Zugang.md).

## Was der Assistent nicht macht

Er speichert keine WordPress-Passwörter, PINs, API-Keys oder Application Passwords. Er veröffentlicht nichts, führt keine Updates aus und aktiviert keine Schreibrechte automatisch.
