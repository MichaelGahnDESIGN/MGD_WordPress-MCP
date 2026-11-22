<?php
/**
 * Client-Konfigurationen für die lokale MCP-Verbindung.
 *
 * Zugangsdaten bleiben bewusst Platzhalter. Sie werden ausschließlich im
 * lokalen MCP-Client hinterlegt, nie in WordPress, im Plugin oder in Git.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MGD_WordPress_MCP_Client_Config {

	/** Getestete Version des offiziellen lokalen Remote-Proxys. */
	private const REMOTE_PROXY_VERSION = '0.5.4';

	/**
	 * Baut die gemeinsame Stdio-Proxyeinstellung.
	 *
	 * Der Proxy verbindet den lokalen Agenten sicher mit dem geschützten
	 * WordPress-Endpunkt. Das WordPress-Passwort wird nicht verwendet.
	 *
	 * @return array<string, mixed>
	 */
	private static function proxy(): array {
		return array(
			'command' => 'npx',
			'args'    => array( '-y', '@automattic/mcp-wordpress-remote@' . self::REMOTE_PROXY_VERSION ),
			'env'     => array(
				'WP_API_URL'      => MGD_WordPress_MCP::adapter_endpoint_url(),
				'WP_API_USERNAME' => '<WORDPRESS-BENUTZERNAME>',
				'WP_API_PASSWORD' => '<APPLICATION-PASSWORD>',
				'OAUTH_ENABLED'   => 'false',
			),
		);
	}

	/** @return array<string, string> */
	public static function configs(): array {
		$proxy = self::proxy();

		return array(
			'codex'       => self::codex_toml( $proxy ),
			'claude-code' => self::json_config( array( 'mcpServers' => array( 'mgd-wordpress' => $proxy ) ) ),
			'cursor'      => self::json_config( array( 'mcpServers' => array( 'mgd-wordpress' => $proxy ) ) ),
			'generic'     => self::json_config( array( 'mcpServers' => array( 'mgd-wordpress' => $proxy ) ) ),
		);
	}

	/**
	 * Codex nutzt TOML, nicht die JSON-Struktur anderer MCP-Clients.
	 *
	 * @param array<string, mixed> $proxy Proxyeinstellung.
	 */
	private static function codex_toml( array $proxy ): string {
		$arguments = isset( $proxy['args'] ) && is_array( $proxy['args'] ) ? $proxy['args'] : array();
		$environment = isset( $proxy['env'] ) && is_array( $proxy['env'] ) ? $proxy['env'] : array();
		$quoted_arguments = array_map(
			static function( $argument ): string {
				return '"' . str_replace( '"', '\\"', (string) $argument ) . '"';
			},
			$arguments
		);
		$lines = array(
			'# In ~/.codex/config.toml einfügen.',
			'[mcp_servers.mgd_wordpress]',
			'command = "' . str_replace( '"', '\\"', (string) $proxy['command'] ) . '"',
			'args = [' . implode( ', ', $quoted_arguments ) . ']',
			'',
			'[mcp_servers.mgd_wordpress.env]',
		);

		foreach ( $environment as $key => $value ) {
			$lines[] = (string) $key . ' = "' . str_replace( '"', '\\"', (string) $value ) . '"';
		}

		return implode( "\n", $lines );
	}

	/** @param array<string, mixed> $config */
	private static function json_config( array $config ): string {
		return (string) wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/** @return array<string, string> */
	public static function instructions(): array {
		return array(
			'codex'       => 'In ~/.codex/config.toml einfügen, die zwei Platzhalter nur lokal ersetzen und anschließend „codex mcp list“ ausführen.',
			'claude-code' => 'Als lokalen MCP-Server in Claude Code hinterlegen. Die zwei Platzhalter nur lokal ersetzen und anschließend „claude mcp list“ ausführen.',
			'cursor'      => 'In die lokale MCP-Konfiguration von Cursor einfügen. Die zwei Platzhalter nur lokal ersetzen und Cursor anschließend neu starten.',
			'generic'     => 'Die Struktur ist für MCP-Clients mit lokalem Stdio-Server gedacht. Nur die beiden Platzhalter lokal ersetzen.',
		);
	}
}
