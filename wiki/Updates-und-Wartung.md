# Updates und Wartung

Wartungsaktionen gehören zu den riskanteren MCP-Werkzeugen und sind deshalb standardmäßig deaktiviert.

## Self-Updates von MGD WordPress MCP

Der GitHub-basierte Self-Updater ist seit dem erfolgreichen Test **0.2.7 → 0.2.8 real End-to-End verifiziert**.

MGD WordPress MCP fragt optional den neuesten stabilen Release von `MichaelGahnDESIGN/MGD_WordPress-MCP` ab. Ein gültiger Release benötigt das Asset:

```text
mgd-wordpress-mcp.zip
```

Die ZIP enthält den stabilen Ordner `mgd-wordpress-mcp/`, damit WordPress die vorhandene Installation aktualisiert und keine zweite Plugin-Kopie erzeugt.

## Sicherheitsprüfung des Updatepakets

Der Updater akzeptiert nicht blind irgendeine URL aus einer GitHub-Antwort. Erwartet werden das fest konfigurierte Repository, GitHub als Downloadhost und der definierte Assetname `mgd-wordpress-mcp.zip`.

Fehlen valide Release-Daten oder das erwartete Paket, wird kein Update angeboten.

## Erfolgreicher Live-Test vom 15. September 2026

Ausgangslage:

```text
WordPress 7.1
PHP 8.5.x
MGD WordPress MCP 0.2.7 installiert
```

Danach wurde GitHub Release `v0.2.8` veröffentlicht. GitHub Actions erzeugte das Asset `mgd-wordpress-mcp.zip`.

Anschließend wurde in WordPress **Dashboard → Aktualisierungen → Erneut überprüfen** ausgeführt.

WordPress meldete daraufhin korrekt:

```text
MGD WordPress MCP
Du hast Version 0.2.7 installiert.
Aktualisiere auf Version 0.2.8.
```

Zusätzlich wurden das Plugin-Icon, der Link zu den Versionsdetails und die Kompatibilitätsinformation für WordPress 7.1 dargestellt.

Damit ist die automatische Update-Discovery zwischen GitHub Releases und WordPress praktisch bestätigt.

## Entwicklung des Updaters

### Frühe 0.2.x-Versionen

Der erste Ansatz arbeitete direkt mit `pre_set_site_transient_update_plugins`, setzte aber zu enge Annahmen über den Zustand des WordPress-Update-Transients voraus. Der erste Live-Test zeigte deshalb trotz vorhandenem GitHub Release kein Update.

### 0.2.5 und 0.2.6

Danach wurde zusätzlich der WordPress-Mechanismus für externe `Update URI`-Provider verwendet. Ein weiterer Live-Test deckte jedoch einen zweiten Fehler auf: Ein gecachter Release konnte identisch zur installierten Version sein und einen unmittelbar danach veröffentlichten neuen Release für mehrere Stunden verdecken.

### 0.2.7

Der Updater wurde am bereits funktionierenden GitHub-Updater von **MGD AI Kennzeichnung WordPress** ausgerichtet.

Seitdem gilt:

1. der klassische WordPress-Update-Transient wird unterstützt,
2. die Update-URI-Integration bleibt ergänzend vorhanden,
3. ein gecachter Release wird nur weiterverwendet, wenn er tatsächlich neuer als die installierte Version ist,
4. andernfalls wird GitHub beim Updatecheck erneut abgefragt,
5. Release-Version und Paket werden validiert,
6. nur das definierte Release-Asset wird als Updatepaket angeboten.

### 0.2.8

0.2.8 änderte bewusst nichts am Updateprinzip. Es war ein reiner Verifikationsrelease. Dass eine installierte 0.2.7 den später veröffentlichten 0.2.8-Release automatisch erkannte, bestätigt die Korrektur.

## Release-Workflow

Tags folgen Semantic Versioning, zum Beispiel:

```text
v0.2.8
v0.2.9
v0.3.0
v1.0.0
```

Vor dem Release müssen Git-Tag, Plugin-Version und `Stable tag` synchron sein.

Der Workflow prüft die Release-Metadaten und PHP-Syntax und baut anschließend:

```text
mgd-wordpress-mcp.zip
```

Die automatisch von GitHub angebotenen allgemeinen `Source code (zip)`- und `Source code (tar.gz)`-Archive sind nicht das definierte WordPress-Updatepaket.

## CI

Auf `main` werden PHP 7.4, 8.1, 8.3 und 8.4 geprüft. Die reale Testinstallation läuft zusätzlich mit PHP 8.5.x und WordPress 7.1.

## Updateprüfung für andere Plugins und Themes

Die MCP-Ability zur reinen Prüfung verändert die Website nicht. Tatsächliche Wartungsaktionen müssen separat freigegeben werden.

## Einzelupdates über MCP

MGD WordPress MCP soll jeweils genau ein Plugin oder Theme kontrolliert aktualisieren. Dafür müssen der Wartungsschalter aktiv sein, der WordPress-Benutzer die erforderliche Capability besitzen und der vorgesehene Bestätigungstoken übergeben werden.

Dieser MCP-Wartungsworkflow ist implementiert, aber noch nicht End-to-End auf der Testwebsite verifiziert.

## Empfohlener Agentur-Workflow

1. verfügbare Updates lesen,
2. Kompatibilität bewerten,
3. Backup anstoßen,
4. Backup-Abschluss soweit möglich verifizieren,
5. genau ein Update durchführen,
6. Backend prüfen,
7. Frontend prüfen,
8. erst danach das nächste Update durchführen.

Ein universelles unkontrolliertes `update everything` ist bewusst nicht Teil des Standarddesigns.

## Frontend-Sperren bei Wartungsprüfungen

PIN, Maintenance Mode, Shield-Plugins oder externe Zugangskontrollen können eine visuelle Prüfung nach einem Update blockieren. Der Agent soll in diesem Fall den autorisierten Nutzer nach dem legitimen Zugang fragen.

Zugangsdaten dürfen nicht im Repository, in Skills oder im Audit-Log gespeichert werden.

## Noch offen

Der Self-Updater ist abgeschlossen und real verifiziert. Noch offen sind insbesondere der MCP-Handshake, Content Read/Write, Divi 5, WPForms, SEO, UpdraftPlus und Wartungsaktionen über MCP.

Die vollständige Liste steht in [`../agent-readme.md`](../agent-readme.md).
