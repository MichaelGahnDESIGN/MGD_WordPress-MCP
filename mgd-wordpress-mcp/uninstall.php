<?php
/**
 * Uninstall handler for MGD WordPress MCP.
 *
 * Standardmäßig bleiben Einstellungen und Audit-Log erhalten, damit eine
 * versehentliche Deinstallation keine Sicherheitsnachweise löscht.
 *
 * Wer eine vollständige Datenlöschung wünscht, kann vor der Deinstallation in
 * wp-config.php setzen:
 *
 * define( 'MGD_WPMCP_PURGE_ON_UNINSTALL', true );
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Abmelden bei theme.michael-gahn.de (Baukasten), läuft immer - auch ohne PURGE
if ( PHP_VERSION_ID >= 80100 ) {
    spl_autoload_register( static function ( $class ) {
        $prefix = 'Mgd\\WordPressMcp\\Kit\\';
        if ( 0 !== strpos( $class, $prefix ) ) { return; }
        $file = __DIR__ . '/includes/Kit/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
        if ( is_file( $file ) ) { require $file; }
    } );
    \Mgd\WordPressMcp\Kit\Platform\WordPress\Setup::uninstall( 'mgd-mcp-wordpress', 'wordpress' );
}

if ( ! defined( 'MGD_WPMCP_PURGE_ON_UNINSTALL' ) || true !== MGD_WPMCP_PURGE_ON_UNINSTALL ) {
    return;
}

delete_option( 'mgd_wordpress_mcp_settings' );
delete_option( 'mgd_wordpress_mcp_wizard' );
delete_transient( 'mgd_wpmcp_wizard_redirect' );

if ( is_multisite() ) {
    delete_site_transient( 'mgd_wpmcp_release_' . md5( 'MichaelGahnDESIGN/MGD_WordPress-MCP' ) );
} else {
    delete_transient( 'mgd_wpmcp_release_' . md5( 'MichaelGahnDESIGN/MGD_WordPress-MCP' ) );
}

global $wpdb;
$table = $wpdb->prefix . 'mgd_wordpress_mcp_audit';
$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
