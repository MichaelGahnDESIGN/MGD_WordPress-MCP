<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MGD_WordPress_MCP_Admin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_init', array( $this, 'settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_filter( 'plugin_action_links_' . MGD_WPMCP_BASENAME, array( $this, 'action_links' ) );
    }

    public function menu() {
        add_management_page(
            __( 'MGD WordPress MCP', 'mgd-wordpress-mcp' ),
            __( 'MGD WordPress MCP', 'mgd-wordpress-mcp' ),
            'manage_options',
            'mgd-wordpress-mcp',
            array( $this, 'render' )
        );
    }

    public function settings() {
        register_setting(
            'mgd_wordpress_mcp',
            MGD_WordPress_MCP::OPTION_SETTINGS,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize' ),
                'default'           => MGD_WordPress_MCP::defaults(),
            )
        );
    }

    public function sanitize( $input ) {
        $defaults = MGD_WordPress_MCP::defaults();
        $clean = array();
        foreach ( array( 'writes_enabled', 'maintenance_enabled', 'divi_writes_enabled', 'media_imports_enabled', 'github_updates', 'audit_enabled' ) as $key ) {
            $clean[ $key ] = ! empty( $input[ $key ] );
        }
        $clean['max_upload_mb'] = max( 1, min( 64, absint( isset( $input['max_upload_mb'] ) ? $input['max_upload_mb'] : $defaults['max_upload_mb'] ) ) );
        return $clean;
    }

    public function assets( $hook ) {
        if ( 'tools_page_mgd-wordpress-mcp' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'mgd-wordpress-mcp-admin', MGD_WPMCP_URL . 'assets/admin.css', array(), MGD_WPMCP_VERSION );
    }

    public function action_links( $links ) {
        array_unshift( $links, '<a href="' . esc_url( admin_url( 'tools.php?page=mgd-wordpress-mcp&tab=wizard' ) ) . '">' . esc_html__( 'Einrichten', 'mgd-wordpress-mcp' ) . '</a>' );
        return $links;
    }

    private function adapter_version() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        foreach ( get_plugins() as $data ) {
            if ( 'MCP Adapter' === $data['Name'] ) {
                return $data['Version'];
            }
        }
        return '';
    }

    public function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $settings = MGD_WordPress_MCP::get_settings();
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'status';
        $base = admin_url( 'tools.php?page=mgd-wordpress-mcp' );
        $environment = MGD_WordPress_MCP_Environment::summary();
        ?>
        <div class="wrap mgd-wpmcp-wrap">
            <h1><?php esc_html_e( 'MGD WordPress MCP', 'mgd-wordpress-mcp' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Sichere WordPress-Abilities für MCP-kompatible KI-Agenten.', 'mgd-wordpress-mcp' ); ?></p>

            <nav class="nav-tab-wrapper">
                <a class="nav-tab <?php echo 'status' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'status', $base ) ); ?>"><?php esc_html_e( 'Status', 'mgd-wordpress-mcp' ); ?></a>
                <a class="nav-tab <?php echo 'wizard' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'wizard', $base ) ); ?>"><?php esc_html_e( 'Einrichtungs-Assistent', 'mgd-wordpress-mcp' ); ?></a>
                <a class="nav-tab <?php echo 'settings' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'settings', $base ) ); ?>"><?php esc_html_e( 'Sicherheit & Freigaben', 'mgd-wordpress-mcp' ); ?></a>
                <a class="nav-tab <?php echo 'audit' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'audit', $base ) ); ?>"><?php esc_html_e( 'Audit-Log', 'mgd-wordpress-mcp' ); ?></a>
                <a class="nav-tab <?php echo 'help' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'help', $base ) ); ?>"><?php esc_html_e( 'Verbindung', 'mgd-wordpress-mcp' ); ?></a>
                <a class="nav-tab <?php echo 'about' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'about', $base ) ); ?>"><?php esc_html_e( 'Über das Plugin', 'mgd-wordpress-mcp' ); ?></a>
            </nav>

            <?php if ( 'wizard' === $tab ) : ?>
                <?php MGD_WordPress_MCP_Wizard::render(); ?>
            <?php elseif ( 'settings' === $tab ) : ?>
                <form method="post" action="options.php" class="mgd-wpmcp-card">
                    <?php settings_fields( 'mgd_wordpress_mcp' ); ?>
                    <h2><?php esc_html_e( 'Freigaben', 'mgd-wordpress-mcp' ); ?></h2>
                    <?php $this->checkbox( 'writes_enabled', __( 'Schreibzugriff erlauben', 'mgd-wordpress-mcp' ), __( 'Erlaubt das Erstellen und Ändern von Inhalten, Medien und SEO-Daten.', 'mgd-wordpress-mcp' ), $settings ); ?>
                    <?php $this->checkbox( 'divi_writes_enabled', __( 'Experimentelle Divi-5-Schreibwerkzeuge erlauben', 'mgd-wordpress-mcp' ), __( 'Erlaubt das Speichern von Divi-kompatiblem post_content. Vor jeder Änderung wird eine WordPress-Revision angelegt.', 'mgd-wordpress-mcp' ), $settings ); ?>
                    <?php $this->checkbox( 'media_imports_enabled', __( 'Medienimport von externen URLs erlauben', 'mgd-wordpress-mcp' ), __( 'Erlaubt Downloads über die WordPress-HTTP-API. Standardmäßig deaktiviert.', 'mgd-wordpress-mcp' ), $settings ); ?>
                    <?php $this->checkbox( 'maintenance_enabled', __( 'Wartungsaktionen erlauben', 'mgd-wordpress-mcp' ), __( 'Erlaubt einzelne Plugin- und Theme-Updates. Die Tools verlangen zusätzlich eine explizite Bestätigung.', 'mgd-wordpress-mcp' ), $settings ); ?>
                    <?php $this->checkbox( 'audit_enabled', __( 'Audit-Log aktivieren', 'mgd-wordpress-mcp' ), __( 'Protokolliert MGD-MCP-Aktionen lokal in der WordPress-Datenbank.', 'mgd-wordpress-mcp' ), $settings ); ?>
                    <?php $this->checkbox( 'github_updates', __( 'Updates über GitHub Releases prüfen', 'mgd-wordpress-mcp' ), __( 'Prüft die öffentliche GitHub-Release-API auf neue Plugin-Versionen.', 'mgd-wordpress-mcp' ), $settings ); ?>
                    <p><label><strong><?php esc_html_e( 'Maximale MCP-Mediendatei', 'mgd-wordpress-mcp' ); ?></strong><br><input type="number" min="1" max="64" name="<?php echo esc_attr( MGD_WordPress_MCP::OPTION_SETTINGS ); ?>[max_upload_mb]" value="<?php echo esc_attr( $settings['max_upload_mb'] ); ?>"> MB</label></p>
                    <?php submit_button(); ?>
                </form>
            <?php elseif ( 'audit' === $tab ) : ?>
                <div class="mgd-wpmcp-card"><h2><?php esc_html_e( 'Letzte Aktionen', 'mgd-wordpress-mcp' ); ?></h2><?php $entries = MGD_WordPress_MCP_Audit::latest( 100 ); ?><div class="mgd-wpmcp-table-wrap"><table class="widefat striped"><thead><tr><th>Zeit (UTC)</th><th>Benutzer</th><th>Ability</th><th>Risiko</th><th>Status</th><th>Zusammenfassung</th></tr></thead><tbody><?php if ( empty( $entries ) ) : ?><tr><td colspan="6"><?php esc_html_e( 'Noch keine Audit-Einträge.', 'mgd-wordpress-mcp' ); ?></td></tr><?php endif; ?><?php foreach ( $entries as $entry ) : ?><tr><td><?php echo esc_html( $entry['created_at'] ); ?></td><td><?php echo esc_html( $entry['user_id'] ); ?></td><td><code><?php echo esc_html( $entry['ability'] ); ?></code></td><td><?php echo esc_html( $entry['risk'] ); ?></td><td><?php echo $entry['success'] ? '✓' : '✕'; ?></td><td><?php echo esc_html( $entry['summary'] ); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
            <?php elseif ( 'help' === $tab ) : ?>
                <div class="mgd-wpmcp-card"><h2><?php esc_html_e( 'MCP-Endpunkt', 'mgd-wordpress-mcp' ); ?></h2><p><code><?php echo esc_html( MGD_WordPress_MCP::adapter_endpoint_url() ); ?></code></p><p><?php esc_html_e( 'Für Claude Code und Codex kann der offizielle Remote-Proxy mit einem WordPress Application Password verwendet werden.', 'mgd-wordpress-mcp' ); ?></p><p><a class="button" href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>"><?php esc_html_e( 'Application Passwords öffnen', 'mgd-wordpress-mcp' ); ?></a> <a class="button" target="_blank" rel="noopener" href="https://github.com/MichaelGahnDESIGN/MGD_WordPress-MCP/tree/main/wiki"><?php esc_html_e( 'Dokumentation', 'mgd-wordpress-mcp' ); ?></a></p></div>
            <?php elseif ( 'about' === $tab ) : ?>
                <div class="mgd-wpmcp-card"><h2>Michael Gahn DESIGN</h2><p><?php esc_html_e( 'MGD WordPress MCP ist freie Software unter GPL-2.0-or-later.', 'mgd-wordpress-mcp' ); ?></p><p><a target="_blank" rel="noopener" href="https://Michael-Gahn.de">Michael-Gahn.de</a><br><a target="_blank" rel="noopener" href="https://Michael-Gahn.de/impressum">Impressum</a><br><a target="_blank" rel="noopener" href="https://github.com/MichaelGahnDESIGN/MGD_WordPress-MCP">GitHub Repository</a></p></div>
            <?php else : ?>
                <div class="mgd-wpmcp-grid">
                    <section class="mgd-wpmcp-card"><h2><?php esc_html_e( 'Verbindungsstatus', 'mgd-wordpress-mcp' ); ?></h2><ul><li><strong>WordPress:</strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></li><li><strong>PHP:</strong> <?php echo esc_html( PHP_VERSION ); ?></li><li><strong>Abilities API:</strong> <?php echo function_exists( 'wp_register_ability' ) ? '✓' : '✕'; ?></li><li><strong>MCP Adapter:</strong> <?php echo class_exists( '\\WP\\MCP\\Core\\McpAdapter' ) ? '✓ ' . esc_html( $this->adapter_version() ) : '✕'; ?></li><li><strong>HTTPS:</strong> <?php echo is_ssl() ? '✓' : '⚠'; ?></li><li><strong>Builder:</strong> <?php echo esc_html( $environment['builder']['label'] ); ?></li><li><strong>Frontend-Schutz:</strong> <?php echo $environment['frontend_locks']['potentially_locked'] ? '⚠ erkannt' : '○ nicht erkannt'; ?></li></ul></section>
                    <section class="mgd-wpmcp-card"><h2><?php esc_html_e( 'Aktive Freigaben', 'mgd-wordpress-mcp' ); ?></h2><ul><li><?php echo $settings['writes_enabled'] ? '✓' : '○'; ?> <?php esc_html_e( 'Schreibzugriff', 'mgd-wordpress-mcp' ); ?></li><li><?php echo $settings['divi_writes_enabled'] ? '✓' : '○'; ?> <?php esc_html_e( 'Divi-Schreibzugriff', 'mgd-wordpress-mcp' ); ?></li><li><?php echo $settings['maintenance_enabled'] ? '✓' : '○'; ?> <?php esc_html_e( 'Wartung', 'mgd-wordpress-mcp' ); ?></li><li><?php echo $settings['media_imports_enabled'] ? '✓' : '○'; ?> <?php esc_html_e( 'Externe Medienimporte', 'mgd-wordpress-mcp' ); ?></li></ul></section>
                </div>
                <?php if ( $environment['frontend_locks']['potentially_locked'] ) : ?><div class="notice notice-warning inline"><p><?php echo esc_html( $environment['frontend_locks']['agent_instruction'] ); ?></p></div><?php endif; ?>
                <div class="mgd-wpmcp-card"><h2><?php esc_html_e( 'Direkter MCP-Endpunkt', 'mgd-wordpress-mcp' ); ?></h2><p><code><?php echo esc_html( MGD_WordPress_MCP::adapter_endpoint_url() ); ?></code></p><p><?php esc_html_e( 'Im sicheren Standardzustand sind Schreib- und Wartungsfunktionen deaktiviert.', 'mgd-wordpress-mcp' ); ?></p></div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function checkbox( $key, $label, $description, $settings ) {
        ?><label class="mgd-wpmcp-toggle"><input type="checkbox" name="<?php echo esc_attr( MGD_WordPress_MCP::OPTION_SETTINGS ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?>><span><strong><?php echo esc_html( $label ); ?></strong><small><?php echo esc_html( $description ); ?></small></span></label><?php
    }
}
