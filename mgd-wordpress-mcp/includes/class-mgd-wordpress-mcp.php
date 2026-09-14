<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MGD_WordPress_MCP {
    const OPTION_SETTINGS = 'mgd_wordpress_mcp_settings';

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        MGD_WordPress_MCP_Audit::instance();
        MGD_WordPress_MCP_Abilities::instance();
        MGD_WordPress_MCP_Admin::instance();
        MGD_WordPress_MCP_Updater::instance();

        add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
        add_action( 'mcp_adapter_init', array( $this, 'register_custom_mcp_server' ) );
    }

    public static function defaults() {
        return array(
            'writes_enabled'        => false,
            'maintenance_enabled'   => false,
            'divi_writes_enabled'   => false,
            'media_imports_enabled' => false,
            'github_updates'        => true,
            'audit_enabled'         => true,
            'max_upload_mb'         => 8,
        );
    }

    public static function get_settings() {
        $saved = get_option( self::OPTION_SETTINGS, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }
        return wp_parse_args( $saved, self::defaults() );
    }

    public static function setting( $key, $default = null ) {
        $settings = self::get_settings();
        return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
    }

    public static function activate() {
        if ( version_compare( get_bloginfo( 'version' ), '6.9', '<' ) ) {
            deactivate_plugins( plugin_basename( MGD_WPMCP_FILE ) );
            wp_die( esc_html__( 'MGD WordPress MCP benötigt WordPress 6.9 oder neuer.', 'mgd-wordpress-mcp' ) );
        }

        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            deactivate_plugins( plugin_basename( MGD_WPMCP_FILE ) );
            wp_die( esc_html__( 'MGD WordPress MCP benötigt PHP 7.4 oder neuer.', 'mgd-wordpress-mcp' ) );
        }

        if ( false === get_option( self::OPTION_SETTINGS, false ) ) {
            add_option( self::OPTION_SETTINGS, self::defaults(), '', false );
        }

        MGD_WordPress_MCP_Audit::install_table();
    }

    public static function deactivate() {
        // Einstellungen und Audit-Log bleiben absichtlich erhalten.
    }

    public function dependency_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! function_exists( 'wp_register_ability' ) ) {
            echo '<div class="notice notice-error"><p><strong>MGD WordPress MCP:</strong> ';
            echo esc_html__( 'Die WordPress Abilities API ist nicht verfügbar. Bitte WordPress 6.9 oder neuer verwenden.', 'mgd-wordpress-mcp' );
            echo '</p></div>';
            return;
        }

        if ( ! class_exists( '\\WP\\MCP\\Core\\McpAdapter' ) ) {
            $url = 'https://github.com/WordPress/mcp-adapter/releases/latest';
            echo '<div class="notice notice-warning"><p><strong>MGD WordPress MCP:</strong> ';
            echo esc_html__( 'Für MCP-Verbindungen wird der offizielle WordPress MCP Adapter benötigt.', 'mgd-wordpress-mcp' );
            echo ' <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'MCP Adapter herunterladen', 'mgd-wordpress-mcp' ) . '</a>';
            echo '</p></div>';
        }
    }

    public function register_custom_mcp_server( $adapter ) {
        if ( ! is_object( $adapter ) || ! method_exists( $adapter, 'create_server' ) ) {
            return;
        }

        $tools = MGD_WordPress_MCP_Abilities::ability_names();

        try {
            $result = $adapter->create_server(
                'mgd-wordpress-mcp',
                'mgd-wordpress-mcp/v1',
                'mcp',
                'MGD WordPress MCP',
                'WordPress management tools by Michael Gahn DESIGN.',
                MGD_WPMCP_VERSION,
                array( \WP\MCP\Transport\HttpTransport::class ),
                \WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
                \WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
                $tools,
                array(),
                array(),
                static function () {
                    return is_user_logged_in() && current_user_can( 'read' );
                }
            );
            if ( is_wp_error( $result ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'MGD WordPress MCP server registration failed: ' . $result->get_error_message() );
            }
        } catch ( Throwable $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'MGD WordPress MCP server registration failed: ' . $e->getMessage() );
            }
        }
    }

    public static function adapter_endpoint_url() {
        return rest_url( MGD_WPMCP_MCP_ENDPOINT );
    }
}
