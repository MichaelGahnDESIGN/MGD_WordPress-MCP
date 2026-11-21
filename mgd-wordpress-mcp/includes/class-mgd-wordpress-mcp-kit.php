<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Brücke zum Baukasten - Datei selbst bleibt PHP 7.4 tauglich, Kit-Klassen erst ab 8.1 laden
final class MGD_WordPress_MCP_Kit {
    const PRODUCT = 'mgd-mcp-wordpress';
    const PLATFORM = 'wordpress';
    const PAGE = 'mgd-wordpress-mcp-plugins';
    const CATALOG_ID = 'plugin-wordpress-mcp';
    const REPO = 'MGD_WordPress-MCP';

    // Katalog-IDs zu den Repos aus plugin-catalog/plugins.json
    const CATALOG_IDS = array(
        'MGD-AI-Kennzeichnung-WordPress' => 'plugin-ki-kennzeichnung-wordpress',
        'MGD-Maintenance-WordPress'      => 'plugin-maintenance-wordpress',
        'MGD-Barrierefrei-WordPress'     => 'plugin-barrierefrei-wordpress',
        'MGD-Share-WordPress'            => 'plugin-share-wordpress',
        'MGD-Notification-WordPress'     => 'plugin-notification-wordpress',
        'MGD_Giveaway_WP-Plugin'         => 'plugin-giveaway-wordpress',
        'MGD_WordPress-MCP'              => 'plugin-wordpress-mcp',
    );

    /** @var \Mgd\WordPressMcp\Kit\Platform\WordPress\Updater|null */
    public static $updater = null;

    public static function boot( $plugin_file ) {
        spl_autoload_register( array( self::class, 'autoload' ) );
        // Updater vom Baukasten, startet auch den Install-Melder
        self::$updater = \Mgd\WordPressMcp\Kit\Platform\WordPress\Setup::updater( $plugin_file, self::PRODUCT, self::PLATFORM, MGD_WPMCP_VERSION, 'MGD WordPress MCP', admin_url( 'tools.php?page=' . self::PAGE ) );
        // Reste vom alten GitHub-Updater weg, 1x pro Version
        \Mgd\WordPressMcp\Kit\Platform\WordPress\Setup::cleanupLegacy( self::PRODUCT, MGD_WPMCP_VERSION, array( 'mgd_wpmcp_release_*' ) );
        add_action( 'admin_menu', array( self::class, 'menu' ) );
    }

    public static function autoload( $class ) {
        $prefix = 'Mgd\\WordPressMcp\\Kit\\';
        if ( 0 !== strpos( $class, $prefix ) ) { return; }
        $file = MGD_WPMCP_DIR . 'includes/Kit/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
        if ( is_file( $file ) ) {
            require $file;
        }
    }

    // Info und Updates unter Werkzeuge
    public static function menu() {
        add_management_page( 'Info und Updates', 'Info und Updates', 'manage_options', self::PAGE, array( self::class, 'page' ) );
    }

    public static function page() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Nicht berechtigt.', '', array( 'response' => 403 ) ); }
        $list = \Mgd\WordPressMcp\Kit\Platform\WordPress\Setup::recommendations( self::PRODUCT, self::PLATFORM, self::CATALOG_ID, self::fallback_plugins(), self::REPO );
        // parent leer = kein eigener Menüpunkt, Lizenz bleibt aus
        $dash = \Mgd\WordPressMcp\Kit\Platform\WordPress\Setup::dashboard( self::PRODUCT, self::PLATFORM, MGD_WPMCP_VERSION, 'MGD WordPress MCP', self::$updater, $list, false, '' );
        if ( null === $dash ) { echo '<div class="wrap"><p>Die Seite konnte gerade nicht geladen werden.</p></div>'; return; }
        $dash->page();
    }

    /** Ersatzliste aus plugin-catalog/plugins.json, falls theme.michael-gahn.de nicht antwortet */
    public static function fallback_plugins() {
        $raw  = file_get_contents( MGD_WPMCP_DIR . 'includes/plugin-catalog/plugins.json' );
        $data = is_string( $raw ) ? json_decode( $raw, true ) : null;
        $out  = array();
        foreach ( is_array( $data ) ? $data : array() as $repo => $row ) {
            if ( ! is_array( $row ) || ! isset( self::CATALOG_IDS[ $repo ] ) ) { continue; }
            $out[] = array(
                'slug'        => self::CATALOG_IDS[ $repo ],
                'name'        => (string) ( $row['name'] ?? '' ),
                'description' => (string) ( $row['description'] ?? '' ),
                'version'     => '',
                'repo'        => (string) $repo,
            );
        }
        return $out;
    }
}
