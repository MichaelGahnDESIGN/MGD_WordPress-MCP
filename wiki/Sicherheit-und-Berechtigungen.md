# Sicherheit und Berechtigungen

MGD WordPress MCP ist bewusst **deny by default** aufgebaut.

## Drei Freigabestufen

Nach Aktivierung sind normale Schreibaktionen deaktiviert. Zusätzlich existieren eigene Schalter für Divi-Schreibzugriffe und Wartungsaktionen.

| Bereich | Standard | Zusätzliche WordPress-Rechte |
| --- | --- | --- |
| Lesen | aktiv | `read` bzw. objektbezogene Leserechte |
| Inhalte schreiben | aus | `edit_posts`, `edit_post`, CPT-Rechte |
| Medien importieren | aus | Schreibzugriff + `upload_files` + eigene Importfreigabe |
| Divi schreiben | aus | Schreibzugriff + eigene Divi-Freigabe + `manage_options` |
| Plugins/Themes aktualisieren | aus | eigene Wartungsfreigabe + Update-Capability |
| Audit lesen | Admin | `manage_options` |

## Explizite Bestätigung

Riskantere Aktionen verlangen zusätzlich einen festen Confirmation-Wert. Beispiele:

```text
TRASH_CONTENT
UPDATE_PLUGIN
UPDATE_THEME
BACKUP_NOW
```

Der Token ersetzt keine Benutzerrechte. Er verhindert vor allem, dass ein Agent eine riskante Funktion durch eine missverständliche Formulierung versehentlich ausführt.

## Keine permanente Inhaltslöschung

Version 0.1.0 bietet keine Ability zum endgültigen Löschen von Beiträgen oder Seiten. `trash-content` verwendet den WordPress-Papierkorb.

## Konfliktschutz

Schreibfähigkeiten können `expected_modified_gmt` verwenden. Der Agent liest zuerst den aktuellen Stand und sendet den Änderungszeitpunkt beim Schreiben zurück. Wurde die Seite zwischenzeitlich durch einen Menschen oder einen anderen Agenten verändert, lehnt das Plugin die Änderung mit einem Konflikt ab.

## Application Passwords

Für Remote-MCP empfiehlt sich ein separater WordPress-Benutzer mit minimalen Rechten und einem eigenen Application Password pro Client. Wird ein Gerät oder Token kompromittiert, kann genau dieses Application Password widerrufen werden.

## Audit

MGD WordPress MCP schreibt wichtige Aktionen in eine eigene Audit-Tabelle. Es werden keine Passwörter und keine vollständigen Inhaltskopien protokolliert.

## Was das Plugin nicht absichern kann

Das Plugin ersetzt kein Server-Hardening, keine Updates, keine Backups, keine Zwei-Faktor-Strategie und keine sorgfältige Vergabe von WordPress-Rollen. Wer einem MCP-Benutzer Administratorrechte gibt, erweitert auch den möglichen Aktionsradius des Agents entsprechend.
