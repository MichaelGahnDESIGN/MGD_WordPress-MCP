# WPForms Integration

MGD WordPress MCP delegiert unterstützte Formularaktionen an die offiziellen WPForms-Abilities der installierten WPForms-Version.

## Unterstützte Bridge-Aktionen

- Formulare auflisten
- Formular lesen
- Editing-Schema beschreiben
- Formular erstellen
- Feld hinzufügen
- Feld aktualisieren
- sichere Formulareinstellungen aktualisieren

## Rechte

MGD WordPress MCP umgeht weder WPForms-Capabilities noch einen dort deaktivierten Schreibzugriff. Wenn WPForms eine Aktion nicht freigibt, kann die Bridge sie nicht erzwingen.

## Zusammenspiel mit Buildern

Ein Agent kann zunächst ein Formular erstellen und danach dessen ID oder Shortcode in einen passenden Inhaltsbereich einbauen. Bei Divi sollte dafür zusätzlich der Divi-Workflow verwendet werden. Bei anderen Buildern muss der Agent die tatsächlich unterstützte Einbettungsmethode prüfen.

## Sicherheit

Formularänderungen können geschäftskritische Kontaktstrecken betreffen. Vor dem Ersetzen eines bestehenden Formulars sollte der Agent das aktuelle Formular lesen und nach der Änderung Pflichtfelder, Datenschutzfeld, Empfänger und Frontend-Darstellung prüfen.
