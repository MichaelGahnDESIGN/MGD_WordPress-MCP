# UpdraftPlus und Backups

MGD WordPress MCP kann ein vollständiges UpdraftPlus-Backup über den dokumentierten WordPress-Action-Hook anstoßen.

## Sicherheitsprinzip

Ein ausgelöster Backup-Job ist noch kein nachgewiesen erfolgreich abgeschlossenes Backup. Das Tool behauptet deshalb nur, dass der Job gestartet wurde.

Vor riskanten Updates oder umfangreichen Layoutänderungen soll ein Agent:

1. Backup anstoßen.
2. Abschluss verifizieren, soweit die installierte UpdraftPlus-Version eine zuverlässige Schnittstelle dafür bereitstellt.
3. Erst danach die riskante Änderung beginnen.

## Bestätigung

Der Backup-Aufruf verlangt eine explizite Bestätigung. Dadurch soll ein Agent nicht bei jeder kleinen Textänderung unnötig vollständige Backups erzeugen.

## Externe Speicher

Ob Backups lokal oder in einem Cloud-Ziel gespeichert werden, wird von UpdraftPlus konfiguriert. MGD WordPress MCP speichert keine Zugangsdaten zu Backup-Zielen.
