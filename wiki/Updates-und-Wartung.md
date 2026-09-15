# Updates und Wartung

Wartungsaktionen gehören zu den riskanteren MCP-Werkzeugen und sind deshalb standardmäßig deaktiviert.

## Updates von MGD WordPress MCP selbst

MGD WordPress MCP besitzt einen eigenen GitHub-Updater. WordPress fragt dabei die Releases des öffentlichen Repositorys `MichaelGahnDESIGN/MGD_WordPress-MCP` ab.

Damit eine Version in WordPress als Update angeboten werden kann, muss auf GitHub ein Release existieren, dessen Versionsnummer höher als die installierte Plugin-Version ist. Der Release muss ein Asset mit exakt diesem Namen enthalten:

```text
mgd-wordpress-mcp.zip
```

Die ZIP enthält den stabilen Plugin-Ordner:

```text
mgd-wordpress-mcp/
```

Dadurch bleibt der Plugin-Pfad bei Updates unverändert.

## Automatischer Release-Workflow

Das Repository enthält einen GitHub-Actions-Workflow für Tags nach dem Schema:

```text
v0.2.0
v0.2.1
v0.3.0
```

Vor dem Erstellen eines Releases prüft der Workflow:

1. Die Versionsnummer des Git-Tags.
2. Die `Version` im WordPress-Plugin-Header.
3. Den `Stable tag` in `readme.txt`.
4. Die PHP-Syntax aller Plugin-Dateien.
5. Den Aufbau der installierbaren ZIP.

Tag, Plugin-Version und `Stable tag` müssen identisch sein. Bei einer Abweichung wird kein gültiger Release gebaut.

## Kontinuierliche Code-Prüfung

Zusätzlich läuft auf `main` eine CI-Prüfung mit PHP 7.4, 8.1, 8.3 und 8.4. Dadurch werden PHP-Syntaxfehler bereits vor einem Release erkannt. Die CI kontrolliert außerdem den Versionsabgleich zwischen Plugin-Header und `Stable tag`.

## WordPress-Updateanzeige

Nach Veröffentlichung eines neueren GitHub Releases kann WordPress die neue Version im normalen Update-System anzeigen, unter anderem unter **Dashboard → Aktualisierungen** und in der Plugin-Liste.

Die Updateprüfung kann in den Einstellungen von MGD WordPress MCP deaktiviert werden. Ist sie deaktiviert, kontaktiert das Plugin GitHub nicht für seine eigene Versionsprüfung.

## Updateprüfung für andere Plugins und Themes

Die MCP-Ability zur reinen Prüfung auf verfügbare Plugin- und Theme-Updates verändert die Website nicht.

## Einzelupdates über MCP

MGD WordPress MCP aktualisiert über seine Wartungswerkzeuge jeweils genau ein Plugin oder Theme. Zusätzlich müssen Wartungsaktionen im Plugin freigegeben sein, der verbundene WordPress-Benutzer die passende Update-Capability besitzen und der Agent den vorgesehenen Bestätigungsstring mitsenden.

## Empfohlener Ablauf auf Kundenwebsites

1. Verfügbare Updates lesen.
2. Kompatibilität prüfen.
3. Bei produktiven Websites ein Backup erstellen.
4. Backup-Status soweit möglich verifizieren.
5. Genau ein Update ausführen.
6. Backend und Frontend prüfen.
7. Erst danach das nächste Update starten.

## Kein automatisches `update everything`

Das Projekt vermeidet bewusst ein universelles Massenupdate-Werkzeug. Ein Agent soll nachvollziehbare und einzeln prüfbare Änderungen durchführen.

## Frontend-Sperren

Wenn die Website durch einen PIN, Maintenance Mode oder eine andere Zugangssperre geschützt ist, kann die visuelle Prüfung nach dem Update blockiert sein. Der Agent soll dann den berechtigten Nutzer nach dem vorgesehenen Zugang fragen. Siehe [Frontend-Sperren und Zugang](Frontend-Sperren-und-Zugang.md).

PINs, Passwörter und Application Passwords dürfen nicht im Repository oder Audit-Log gespeichert werden.

## Release 0.2.0

Version 0.2.0 ist als erster öffentlicher Release Candidate vorbereitet. Der direkte Base64-Medienupload ist für diesen Release sicherheitsbedingt deaktiviert und wird nicht als externes MCP-Tool angeboten.

Vor dem Einsatz auf produktiven Kundenwebsites empfiehlt sich weiterhin ein realer Integrationstest der konkreten Kombination aus WordPress, Hosting, MCP Adapter, Builder und installierten Drittanbieter-Plugins.
