# Fehlerbehebung

## MCP Adapter wird nicht erkannt

Prüfe, ob der offizielle WordPress MCP Adapter installiert und aktiviert ist. MGD WordPress MCP registriert Werkzeuge, ersetzt aber nicht die komplette Adapter-Infrastruktur.

## Endpoint antwortet nicht

Prüfe HTTPS, Permalinks, REST-API, Security-Plugins und die Authentifizierung des verwendeten Clients.

## Lesen funktioniert, Schreiben nicht

Das ist häufig beabsichtigt. Prüfe unter `Werkzeuge → MGD WordPress MCP → Sicherheit & Freigaben`, ob Schreibzugriff aktiviert ist und ob der WordPress-Benutzer die benötigte Capability besitzt.

## Divi kann gelesen, aber nicht geändert werden

Divi-Schreibzugriff besitzt einen eigenen Schalter und ist in Version 0.2.0 zusätzlich auf Administratoren begrenzt.

## Frontend ist für den Agenten gesperrt

Öffne den Status oder den Einrichtungs-Assistenten. Wird ein möglicher Frontend-Schutz erkannt, gib dem Agenten den legitimen Entsperrweg. PINs oder Passwörter nicht dauerhaft im Plugin speichern.

## WPForms-Schreibaktion wird abgelehnt

Die WPForms-Bridge respektiert die offiziellen WPForms-Rechte. Prüfe die installierte WPForms-Version und deren Schreibfreigaben.

## Update schlägt fehl

Prüfe Dateirechte, WordPress-Updateberechtigungen, freien Speicher, Wartungsmodus und das WordPress-Debug-Log. Führe Updates einzeln aus.

## GitHub-Update erscheint nicht

Der MGD-Updater erwartet ein GitHub Release mit einer installierbaren Datei `mgd-wordpress-mcp.zip`. Ein Quellcode-Zipball allein ist nicht zwingend als Plugin-Paket geeignet.
