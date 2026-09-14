<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MGD_WordPress_MCP_Environment_Ability {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_abilities_api_init', array( $this, 'register' ) );
    }

    public function register() {
        if ( ! function_exists( 'wp_register_ability' ) ) {
            return;
        }

        wp_register_ability(
            'mgd-wordpress-mcp/environment-status',
            array(
                'label'               => __( 'Environment status', 'mgd-wordpress-mcp' ),
                'description'         => __( 'Detect page builder and common frontend locks. Never exposes PINs or passwords.', 'mgd-wordpress-mcp' ),
                'category'            => 'mgd-wordpress-mcp',
                'input_schema'        => array( 'type' => 'object', 'properties' => array(), 'additionalProperties' => false ),
                'execute_callback'    => array( $this, 'execute' ),
                'permission_callback' => array( $this, 'permission' ),
                'meta'                => array(
                    'public'       => true,
                    'show_in_rest' => true,
                    'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
                    'mcp'          => array( 'public' => true, 'type' => 'tool' ),
                ),
            )
        );
    }

    public function permission() {
        return is_user_logged_in() && current_user_can( 'read' );
    }

    public function execute() {
        return MGD_WordPress_MCP_Environment::summary();
    }
}
