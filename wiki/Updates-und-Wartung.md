# Updates und Wartung

Wartungsaktionen gehören zu den riskanteren MCP-Werkzeugen und sind deshalb standardmäßig deaktiviert.

## Self-Updates von MGD WordPress MCP

MGD WordPress MCP besitzt einen eigenen GitHub-Updater. Die optionale Prüfung fragt die Releases von `MichaelGahnDESIGN/MGD_WordPress-MCP` serverseitig ab.

Ein gültiger Release benötigt ein Asset mit exakt diesem Namen:

```text
mgd-wordpress-mcp.zip
```

Die ZIP enthält den stabilen Ordner `mgd-wordpress-mcp/`, damit WordPress die vorhandene Installation ersetzt und keine zweite Plugin-Kopie erzeugt.

## Erkenntnis aus dem ersten Live-Test

Der erste reale Test mit installierter Version 0.2.0 und veröffentlichtem Release 0.2.1 zeigte: GitHub Release und ZIP waren korrekt vorhanden, WordPress meldete trotzdem „Alle Plugins sind auf dem neuesten Stand“.

Die Ursache lag in der ersten Updater-Implementierung. Sie verlangte, dass `update_plugins->checked[MGD_WPMCP_BASENAME]` beim Filter `pre_set_site_transient_update_plugins` bereits vorhanden war. Diese Annahme ist für den WordPress-Updateablauf zu restriktiv.

## Korrektur in 0.2.2

Version 0.2.2 entfernt diese Abhängigkeit. Der Updater:

1. akzeptiert ein noch nicht vollständig aufgebautes Update-Transient,
2. initialisiert `response` und `no_update` bei Bedarf,
3. liest den neuesten GitHub Release,
4. verlangt weiterhin das definierte Release-Asset,
5. vergleicht Release-Version und installierte Plugin-Version,
6. setzt bei einer neueren Version ein vollständiges `response`-Objekt,
7. setzt bei gleicher/älterer Version ein `no_update`-Objekt,
8. liefert zusätzliche Metadaten wie Plugin-ID, getestete WordPress-Version, PHP-Anforderung und Icon.

Der nächste End-to-End-Test lautet deshalb bewusst: **installierte ältere Version → Release v0.2.2 → Dashboard → Aktualisierungen → Erneut überprüfen**.

Erst wenn WordPress das Update dort tatsächlich anbietet und die Installation erfolgreich durchführt, wird die Self-Update-Kette als real verifiziert markiert.

## Release-Workflow

Tags folgen dem Schema:

```text
v0.2.0
v0.2.1
v0.2.2
v0.3.0
```

Vor dem Release werden Git-Tag, Plugin-Header-Version und `Stable tag` verglichen. Außerdem werden die PHP-Dateien syntaktisch geprüft und anschließend `mgd-wordpress-mcp.zip` gebaut.

## CI

Auf `main` werden PHP 7.4, 8.1, 8.3 und 8.4 geprüft. Die reale Testinstallation läuft zusätzlich auf PHP 8.5.x und WordPress 7.1.

## Updateprüfung für andere Plugins und Themes

Die MCP-Ability zur reinen Prüfung verändert die Website nicht. Tatsächliche Wartungsaktionen müssen separat freigegeben werden.

## Einzelupdates über MCP

MGD WordPress MCP aktualisiert jeweils genau ein Plugin oder Theme. Zusätzlich müssen der Wartungsschalter aktiv sein, der WordPress-Benutzer die erforderliche Capability besitzen und der vorgesehene Bestätigungstoken gesendet werden.

## Empfohlener Agentur-Workflow

1. Updates lesen.
2. Kompatibilität bewerten.
3. Backup anstoßen.
4. Backup-Abschluss soweit möglich verifizieren.
5. genau ein Update durchführen.
6. Backend prüfen.
7. Frontend prüfen.
8. erst danach das nächste Update durchführen.

Ein universelles unkontrolliertes `update everything` ist bewusst nicht Teil des Standarddesigns.

## Frontend-Sperren

PIN, Maintenance Mode, Shield-Plugins oder externe Zugangskontrollen können eine visuelle Prüfung blockieren. In diesem Fall soll der Agent den autorisierten Nutzer nach dem vorgesehenen Zugang fragen. Zugangsdaten dürfen nicht im Repository oder Audit-Log gespeichert werden.

## Noch offen

Die vollständige Liste aller bereits erledigten und noch offenen Aufgaben steht in [`../agent-readme.md`](../agent-readme.md).
