# Plugin-Verwaltung

Unter Dashboard → Aktualisierungen und Plugins erscheint einmal „MGD-Updates jetzt prüfen“, auch wenn mehrere MGD-Plugins aktiv sind. Der Button leert nur die Release-Caches der aktiven Plugins und führt die native WordPress-Prüfung aus. Es wird kein Paket installiert und keine Einstellung gespeichert. GitHub erhält die Server-IP. Abgeschaltete Prüfungen bleiben abgeschaltet. Eine fehlende Update-Meldung beweist bei Netzfehlern nicht, dass die Installation aktuell ist.

Die Plugin-Zeile bietet Einstellungen, Details, Dokumentation, Support und GitHub. Die Detailansicht bleibt lokal verfügbar.

Maintenance Studio merkt sich eine letzte geprüfte Quelle erst nach einem passenden stabilen Release-Paket. Im Bereich Updates kann diese Quelle oder das Original wiederhergestellt werden. Die Zustimmung zu HTTP-Prüfungen bleibt dabei erhalten. Sein Menü wird im letzten Menüfilter ans Ende gesetzt. Andere Plugins können die Reihenfolge später trotzdem verändern.

## Rückfall

Vor Updates die Datenbank sichern. Bei Problemen das vorherige Release-ZIP wieder installieren.
