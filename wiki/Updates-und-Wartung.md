# Updates und Wartung

Wartungsaktionen gehören zu den riskanteren MCP-Werkzeugen und sind deshalb standardmäßig deaktiviert.

## Updateprüfung

Die reine Prüfung auf verfügbare Plugin- und Theme-Updates verändert die Website nicht.

## Einzelupdates

MGD WordPress MCP aktualisiert über seine Wartungswerkzeuge jeweils genau ein Plugin oder Theme. Zusätzlich müssen:

- Wartungsaktionen im Plugin freigegeben sein
- der WordPress-Benutzer die passende Update-Capability besitzen
- der Agent den vorgesehenen Bestätigungsstring mitsenden

## Empfohlener Ablauf

1. verfügbare Updates lesen
2. Kompatibilität prüfen
3. bei produktiven Websites Backup erstellen
4. genau ein Update ausführen
5. Frontend und Backend prüfen
6. erst danach das nächste Update starten

## Frontend-Sperren

Wenn die Website durch einen PIN, Maintenance Mode oder eine andere Zugangssperre geschützt ist, kann die visuelle Prüfung nach dem Update blockiert sein. Der Agent soll dann den berechtigten Nutzer nach dem vorgesehenen Zugang fragen. Siehe [Frontend-Sperren und Zugang](Frontend-Sperren-und-Zugang.md).

## Keine automatischen Massenupdates

Das Projekt vermeidet bewusst ein universelles `update-everything`-Werkzeug. Ein Agent soll nachvollziehbare, einzeln prüfbare Änderungen durchführen.
