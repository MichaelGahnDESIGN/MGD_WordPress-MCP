# Divi 5 Integration

MGD WordPress MCP erkennt Divi und stellt eine bewusst konservative Bridge bereit.

## Aktuelle Werkzeuge

- Divi-Status lesen
- gespeichertes Layout bzw. `post_content` lesen
- Layout mit vorheriger WordPress-Revision speichern
- exakte Textstellen austauschen
- optional `_et_pb_use_builder` für einen Inhalt aktivieren

Direkte Divi-Schreibwerkzeuge sind standardmäßig deaktiviert und zusätzlich auf Administratoren begrenzt.

## Arbeitsregel

Ein Agent soll vor Änderungen zuerst Zielseite, aktuellen Inhalt, `modified_gmt` und Divi-Status lesen. Bei strukturellen Änderungen sollte zusätzlich ein Backup vorhanden sein.

## MGD Divi 5 Dev Skill

Für Divi-Wissen und Builder-Workflows wird der öffentliche Skill empfohlen:

https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL

MGD WordPress MCP erfindet keine undokumentierte Divi-Server-API. Neue strukturierte Divi-Werkzeuge werden erst ergänzt, wenn das verwendete Divi-5-Datenmodell zuverlässig und versionsfest angesprochen werden kann.

## Konfliktschutz

Schreibwerkzeuge können einen erwarteten Änderungszeitpunkt verwenden. Wurde die Seite zwischen Lesen und Schreiben verändert, soll der Agent neu laden statt fremde Änderungen zu überschreiben.
