# Frontend-Sperren und Zugang

Ein MCP-Zugriff auf WordPress und ein visueller Zugriff auf das Frontend sind zwei unterschiedliche Dinge.

Ein Agent kann beispielsweise über MCP einen Beitrag bearbeiten, während die öffentliche Website gleichzeitig durch einen PIN, ein Passwort, einen Maintenance-Modus, eine WAF oder eine Hosting-Sperre geschützt ist. Für eine visuelle Qualitätskontrolle muss der Agent diese Situation erkennen.

## Erkennung in MGD WordPress MCP

Das Plugin prüft die aktiven WordPress-Plugins heuristisch auf typische Hinweise für:

- Security-/Shield-Lösungen
- Passwortschutz
- Restricted Site Access
- Maintenance Mode
- Coming Soon

Dadurch werden auch Lösungen erfasst, deren Name Begriffe wie `Shield`, `Password Protected`, `Maintenance` oder `Restricted Site Access` enthält.

Die Erkennung ist bewusst allgemein gehalten, damit nicht für jedes Schutzplugin eine harte Abhängigkeit eingebaut werden muss.

## Verhalten des Agenten

Wenn ein Schutz erkannt wird, erhält der Agent sinngemäß folgende Anweisung:

> Wenn eine visuelle Prüfung oder Browser-Automation blockiert wird, frage den Nutzer nach dem legitimen Entsperrweg oder einer temporären Freigabe. Frage nur bei Bedarf nach einer PIN. Speichere oder protokolliere keine PINs oder Passwörter.

Das ist wichtig: Der Agent soll eine Sperre nicht umgehen oder erraten. Er soll den berechtigten Website-Betreiber nach dem vorgesehenen Zugang fragen.

## Keine Secret-Speicherung

MGD WordPress MCP besitzt kein Feld zum dauerhaften Speichern einer Frontend-PIN. PINs und Passwörter gehören ebenfalls nicht ins Audit-Log.

Wenn ein Client eine temporäre PIN für eine Browser-Sitzung benötigt, liegt die Secret-Verarbeitung beim jeweiligen Client und muss dessen Sicherheitsregeln folgen.

## Grenzen der Erkennung

Nicht jeder Schutz läuft innerhalb von WordPress. Nicht automatisch erkennbar sind unter anderem:

- HTTP Basic Authentication auf Apache/Nginx-Ebene
- Cloudflare Access
- Hosting-Firewalls
- VPN-only Websites
- IP-Allowlisting
- individuelle Reverse-Proxies
- eigene Login-Gates außerhalb von WordPress

Deshalb bedeutet `kein Schutz erkannt` nicht automatisch `Frontend garantiert öffentlich erreichbar`.

## MCP bleibt getrennt

Ein Frontend-PIN sollte niemals als Ersatz für die MCP-Authentifizierung verwendet werden. MCP-Zugriffe benötigen weiterhin eine eigene, sichere WordPress-Authentifizierung und passende Benutzerrechte.
