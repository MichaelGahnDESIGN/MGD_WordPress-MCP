<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Zentrale zusätzliche Sicherheitsregeln.
 */
final class MGD_WordPress_MCP_Security {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Läuft nach der normalen Ability-Registrierung und entfernt Funktionen,
        // die bis zu einer vollständig validierten Implementierung nicht extern
        // erreichbar sein sollen.
        add_action( 'wp_abilities_api_init', array( $this, 'harden_abilities' ), 1000 );
    }

    public function harden_abilities() {
        $ability = 'mgd-wordpress-mcp/upload-media-base64';

        if ( function_exists( 'wp_has_ability' ) && function_exists( 'wp_unregister_ability' ) && wp_has_ability( $ability ) ) {
            wp_unregister_ability( $ability );
        }
    }

    /**
     * Entfernt deaktivierte Abilities zusätzlich aus der MCP-Tool-Liste.
     */
    public static function filter_mcp_tools( $tools ) {
        return array_values(
            array_diff(
                (array) $tools,
                array( 'mgd-wordpress-mcp/upload-media-base64' )
            )
        );
    }
}
