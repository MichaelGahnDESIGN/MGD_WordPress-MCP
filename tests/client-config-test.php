<?php
/**
 * Regressionstest für lokale Client-Konfigurationen.
 *
 * Der Test schützt vor dem alten Fehler, bei dem Codex dieselbe JSON-Datei
 * wie Cursor erhielt, obwohl Codex seine MCP-Server als TOML verwaltet.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

function wp_json_encode( $value, $options = 0 ) {
	return json_encode( $value, $options );
}

final class MGD_WordPress_MCP {
	public static function adapter_endpoint_url(): string {
		return 'https://example.test/wp-json/mgd-wordpress-mcp/v1/mcp';
	}
}

require_once dirname( __DIR__ ) . '/mgd-wordpress-mcp/includes/class-mgd-wordpress-mcp-client-config.php';

$configs = MGD_WordPress_MCP_Client_Config::configs();

if ( false === strpos( $configs['codex'], '[mcp_servers.mgd_wordpress]' ) ) {
	throw new RuntimeException( 'Die Codex-Konfiguration muss TOML mit einem eigenen MCP-Serverabschnitt liefern.' );
}

if ( false === strpos( $configs['codex'], 'WP_API_USERNAME = "<WORDPRESS-BENUTZERNAME>"' ) ) {
	throw new RuntimeException( 'Die Codex-Konfiguration muss einen lokalen Benutzerplatzhalter enthalten.' );
}

if ( false !== strpos( $configs['codex'], '@latest' ) ) {
	throw new RuntimeException( 'Die Verbindungskonfiguration darf keine ungeprüfte @latest-Abhängigkeit verwenden.' );
}

if ( false === strpos( $configs['claude-code'], '"mcpServers"' ) ) {
	throw new RuntimeException( 'Die Claude-Code-Konfiguration muss weiterhin JSON liefern.' );
}

echo "PASS: Client-Konfigurationen trennen Codex-TOML und JSON-Clients sicher.\n";
