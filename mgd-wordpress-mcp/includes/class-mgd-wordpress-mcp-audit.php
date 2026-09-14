<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MGD_WordPress_MCP_Audit {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'mgd_wordpress_mcp_audit';
    }

    public static function install_table() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table   = self::table_name();
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            ability varchar(191) NOT NULL,
            risk varchar(32) NOT NULL DEFAULT 'read',
            object_type varchar(64) NOT NULL DEFAULT '',
            object_id bigint(20) unsigned NOT NULL DEFAULT 0,
            success tinyint(1) NOT NULL DEFAULT 1,
            summary text NULL,
            PRIMARY KEY  (id),
            KEY created_at (created_at),
            KEY ability (ability),
            KEY user_id (user_id)
        ) {$charset};";

        dbDelta( $sql );
    }

    public static function log( $ability, $risk, $success, $summary = '', $object_type = '', $object_id = 0 ) {
        if ( ! MGD_WordPress_MCP::setting( 'audit_enabled', true ) ) {
            return;
        }

        global $wpdb;

        $wpdb->insert(
            self::table_name(),
            array(
                'created_at'  => current_time( 'mysql', true ),
                'user_id'     => get_current_user_id(),
                'ability'     => sanitize_text_field( $ability ),
                'risk'        => sanitize_key( $risk ),
                'object_type' => sanitize_key( $object_type ),
                'object_id'   => absint( $object_id ),
                'success'     => $success ? 1 : 0,
                'summary'     => sanitize_textarea_field( wp_strip_all_tags( (string) $summary ) ),
            ),
            array( '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s' )
        );

        self::trim();
    }

    private static function trim() {
        global $wpdb;
        $table = self::table_name();
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        if ( $count <= 2000 ) {
            return;
        }
        $delete = $count - 1500;
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} ORDER BY id ASC LIMIT %d", $delete ) );
    }

    public static function latest( $limit = 50 ) {
        global $wpdb;
        $limit = max( 1, min( 200, absint( $limit ) ) );
        return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' ORDER BY id DESC LIMIT %d', $limit ), ARRAY_A );
    }
}
