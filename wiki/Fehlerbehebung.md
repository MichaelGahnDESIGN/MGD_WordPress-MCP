# Fehlerbehebung

## MCP Adapter wird nicht erkannt

Prüfe, ob der offizielle WordPress MCP Adapter installiert und aktiviert ist. MGD WordPress MCP registriert Werkzeuge, ersetzt aber nicht die komplette Adapter-Infrastruktur.

## Endpoint antwortet nicht

Prüfe HTTPS, Permalinks, REST-API, Security-Plugins und die Authentifizierung des verwendeten Clients.

## Codex zeigt keine WordPress-Tools

Prüfe zunächst mit `codex mcp list`, ob `mgd_wordpress` erscheint. Codex benötigt einen TOML-Block in `~/.codex/config.toml`; die JSON-Konfiguration anderer Clients reicht nicht aus. Danach Codex neu starten und zuerst eine reine Leseaufgabe testen.

Wenn der Endpoint ohne Anmeldung mit `401` oder `403` antwortet, ist das nicht automatisch ein Fehler: Security-Plugins wie Shield sollen anonyme REST-Anfragen blockieren. Entscheidend ist, ob der lokale MCP-Proxy mit dem separaten WordPress-Benutzer und dessen Application Password einen Handshake aufbauen kann.

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
