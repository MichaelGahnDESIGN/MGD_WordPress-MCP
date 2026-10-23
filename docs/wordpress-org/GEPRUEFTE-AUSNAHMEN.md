# Geprüfte statische Einzel-Ausnahmen

Die nachfolgende Liste zeigt alle PHPCS-Einzel-Ausnahmen im tatsächlich gebauten Paket, einschließlich schon zuvor vorhandener Stellen. Kein phpcs:disable und keine globale Abschaltung von Plugin-Check-Prüfgruppen. Die konkreten Gründe stehen direkt am betroffenen Zugriff. Die Code-Ausnahmen müssen bei künftigen Änderungen erneut geprüft werden; sie sind keine pauschale Freigabe ähnlicher neuer Zugriffe.

- `includes/class-mgd-wordpress-mcp-admin.php:16` – WordPress.Security.NonceVerification.Recommended -- Nur lesende Tab-Auswahl nach manage_options; Formulare haben eigene Nonces.
- `includes/class-mgd-wordpress-mcp-audit.php:58` – WordPress.DB.DirectDatabaseQuery.DirectQuery -- Eigene Audit-Tabelle; vorhandene Anfrageansichten sind oben verworfen.
- `includes/class-mgd-wordpress-mcp-audit.php:80` – WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Frischer Zähler für die begrenzte Aufbewahrung, auch bei parallelen MCP-Anfragen.
- `includes/class-mgd-wordpress-mcp-audit.php:86` – WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Begrenzte Bereinigung eigener Audit-Zeilen; log() hat den Anfragecache invalidiert.
- `includes/class-mgd-wordpress-mcp-audit.php:99` – WordPress.DB.DirectDatabaseQuery.DirectQuery -- Vorbereitete Abfrage der eigenen Audit-Tabelle.
- `includes/class-mgd-wordpress-mcp-wizard.php:9` – WordPress.Security.NonceVerification.Recommended -- Mehrfachaktivierung nur erkennen und automatische Weiterleitung unterlassen.
- `includes/class-mgd-wordpress-mcp.php:15` – WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Nur WP_DEBUG: feste technische Diagnose ohne Exception-Inhalt oder Zugangsdaten.
- `uninstall.php:34` – WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Ausschließlich bei ausdrücklich aktivierter Datenlöschung.
