# WordPress.org-Vorbereitung

Stand: 23.10.2026. Dies ist ein separater Einreichungskandidat auf `codex/wordpress-org-preparation`. Es wurde nichts eingereicht und kein produktiver Shop verändert.

## Paket und Updatekanal

Vorgeschlagener Verzeichnis-Slug: `mgd-mcp-bridge`. WordPress.org bestätigt den endgültigen Namen. Mitwirkender: `michaelgahndesign`.

`python3 scripts/build-wordpress-org.py` erzeugt ausschließlich das freigegebene Plugin unter `artifacts/wordpress-org/mgd-mcp-bridge.zip` mit SHA-256. Die getestete Arbeitskopie liegt unter `build/wordpress-org/mgd-mcp-bridge`. Beide Ordner werden nicht versioniert. Die eigenen GitHub-Updater, externen Plugin-Installer und überschreibenden Detail-API-Hooks sind aus dieser Ausgabe entfernt. Die native WordPress-Verwaltung übernimmt Updates und Details erst nach tatsächlicher Veröffentlichung im Verzeichnis.

Die GitHub-Ausgabe auf main und deren vorhandene Releases bleiben eigenständig. Während der Vorbereitung sind die alten GitHub-Update-Tests fachlich nicht anwendbar; die neue Prüfung `tests/wordpress-org-contract.php` kontrolliert den nativen Kanal und Metadaten. Die CI führt zusätzlich den offiziellen WordPress Plugin Check auf dem gebauten Paket aus. Keine WordPress.org-Zugangsdaten oder SVN-Passwörter sind gespeichert.

## Grafiken und Readme

Die englische `readme.txt` folgt dem Verzeichnisformat und beschreibt externe Dienste und Grenzen. Die übrige technische Dokumentation bleibt deutsch. Vier PNG-Dateien unter `wordpress-org/assets` haben die offiziellen Maße 128×128, 256×256, 772×250 und 1544×500. Sie basieren auf den vorhandenen eigenen Plugin-Grafiken; neue Textbanner und Symbole stammen aus den SVG-Quelldateien des Projekts. Die PNGs wurden mit Sharp exportiert. Für SVN gehören sie in den separaten obersten assets-Ordner, nicht in trunk/assets. Es gibt keine erfundenen Screenshots und keine eingebauten externen Bilddienste.

## Herstellerlinks und Datenschutz

Hilfe- und Dokumentationslinks im Backend sind zurückhaltend und werden erst beim Anklicken geöffnet. Es gibt keine automatische Abfrage des MGD-Empfehlungsfeeds. Beim Barrierefrei-Plugin ist der öffentliche Herstellerlink ausdrücklich wählbar und standardmäßig ausgeschaltet; alle Funktionen bleiben ohne den Link verfügbar. Der Link öffnet mit `noopener noreferrer` in einem neuen Tab. Die Bedienhilfe und der Textgenerator sind keine Zusicherung rechtlicher Konformität.

## Sicherheitsänderungen und Wechsel der Ausgabe

Giveaway erhält eine WordPress-Nonce im öffentlichen Formular, prüft den Veröffentlichungsstatus und lehnt nicht bestätigte Pflicht-Checkboxen ab. Suchabfragen binden Werte und Tabellenbezeichner über Platzhalter. CSV-Import, CSV-Ausgabe und autorisierte Dateidownloads behalten gezielt dokumentierte Streams; nur hierfür sind einzelne Dateisystemhinweise mit einer begründeten PHPCS-Zeile versehen. SQL- und Sicherheitsprüfungen werden nicht pauschal abgeschaltet.

SMTP-Passwörter werden in der Giveaway-Verzeichnisfassung weder im Backend angezeigt noch aus Optionen verwendet. Beim Aktivieren entfernt sie den alten Passwortwert aus den Plugin-Einstellungen. Vor einem Wechsel vorhandene SMTP-Zugangsdaten in ein geeignetes Mail-Plugin oder die geschützte Serverkonstante `MGD_GIVEAWAY_SMTP_PASSWORD` übertragen. Keine echten Zugangsdaten gehören in diese Dokumentation. Formulare mit Nonce müssen aus dauerhaftem Vollseiten-Cache ausgenommen werden.

Die bisherigen GitHub- und neuen Verzeichnisordner können unterschiedliche Namen haben. Beide Fassungen dürfen wegen gemeinsamer PHP-Klassen und Einstellungen nicht gleichzeitig aktiviert werden. Vor einem Wechsel: vollständige Datenbanksicherung, bisherige Fassung deaktivieren, den Kandidaten in einer Testkopie installieren, Funktionen und Einstellungen prüfen. Die bisherige Fassung nicht mit aktivierter Datenlöschung deinstallieren. Rückfall: Kandidat deaktivieren und das vorherige GitHub-ZIP wieder aktivieren. Keine produktive Migration wurde ausgeführt.

## Prüfung und Grenzen

Geprüft mit WordPress 7.1.3, PHP 8.3 und Plugin Check 2.1.0 in einer lokalen Docker-Installation, inklusive CLI-Runtime-Bootstrap. Die Ergebnisse unter `docs/wordpress-org/plugin-check.txt` enthalten die verbliebenen Warnungen unverändert. Ein fehlerfreier automatischer Lauf ersetzt die manuelle Prüfung des Plugin-Teams nicht. Keine Freigabe von WordPress.org wird behauptet.

Die Warnungen betreffen vor allem eigene Tabellen, fehlende Query-Caches, Präfixe in Maintenance-Templates und Eingaben, deren Prüfung über mehrere Methoden erfolgt. Tabellenbezeichner werden aus dem WordPress-Präfix und festen Plugin-Namen aufgebaut; Werte werden vorbereitet oder vor Speicherung gefiltert. GET-Filter und Hinweiskennungen lösen keine schreibenden Aktionen aus. Schreibende Admin-Endpunkte besitzen Capability- und Nonce-Prüfungen. Die Quelle bleibt für die manuelle Sicherheitsprüfung offen; einzelne Warnungen dürfen bei späteren Änderungen nicht ungeprüft übernommen werden. Vollständige Screenreader-, Multisite-, Hosting- und Theme-Kompatibilität sind nicht belegt.

Vor Einreichung: Berichte und verbliebene Warnungen fachlich abnehmen, endgültigen Slug und Profil prüfen, reale Bildschirmfotos ergänzen und den Kandidaten manuell einreichen. Die Einreichung ist nicht Bestandteil dieses automatischen Vorbereitungsschritts.
