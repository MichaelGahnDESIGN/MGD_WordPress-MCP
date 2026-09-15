<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MGD_WordPress_MCP_Abilities {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
        add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
    }

    public static function ability_names() {
        return array(
            'mgd-wordpress-mcp/site-status', 'mgd-wordpress-mcp/integrations', 'mgd-wordpress-mcp/list-content', 'mgd-wordpress-mcp/get-content',
            'mgd-wordpress-mcp/create-content', 'mgd-wordpress-mcp/update-content', 'mgd-wordpress-mcp/trash-content', 'mgd-wordpress-mcp/list-media',
            'mgd-wordpress-mcp/import-media', 'mgd-wordpress-mcp/upload-media-base64', 'mgd-wordpress-mcp/set-featured-image', 'mgd-wordpress-mcp/seo-get',
            'mgd-wordpress-mcp/seo-set', 'mgd-wordpress-mcp/divi-status', 'mgd-wordpress-mcp/divi-get-layout', 'mgd-wordpress-mcp/divi-save-layout',
            'mgd-wordpress-mcp/divi-replace-text', 'mgd-wordpress-mcp/wpforms-run', 'mgd-wordpress-mcp/updraft-backup', 'mgd-wordpress-mcp/list-plugins',
            'mgd-wordpress-mcp/check-updates', 'mgd-wordpress-mcp/update-plugin', 'mgd-wordpress-mcp/update-theme', 'mgd-wordpress-mcp/audit-log',
        );
    }

    public function register_category() {
        if ( function_exists( 'wp_register_ability_category' ) ) {
            wp_register_ability_category( 'mgd-wordpress-mcp', array( 'label' => __( 'MGD WordPress MCP', 'mgd-wordpress-mcp' ), 'description' => __( 'Sichere WordPress-Werkzeuge für MCP-kompatible KI-Agenten.', 'mgd-wordpress-mcp' ) ) );
        }
    }

    public function register_abilities() {
        if ( ! function_exists( 'wp_register_ability' ) ) { return; }
        $this->register( 'site-status', 'Site status', 'Read WordPress, PHP, theme, MCP and integration status.', array(), array( $this, 'site_status' ), array( $this, 'can_read' ), true, false, true );
        $this->register( 'integrations', 'Integrations', 'Detect supported plugins and integrations such as Divi, WPForms, UpdraftPlus, Rank Math, Yoast and WooCommerce.', array(), array( $this, 'integrations' ), array( $this, 'can_read' ), true, false, true );
        $this->register( 'list-content', 'List content', 'List posts, pages or another public post type with filters.', array( 'post_type' => array( 'type' => 'string', 'default' => 'post' ), 'status' => array( 'type' => 'string', 'default' => 'publish' ), 'search' => array( 'type' => 'string', 'default' => '' ), 'page' => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ), 'per_page' => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ) ), array( $this, 'list_content' ), array( $this, 'can_read' ), true, false, true );
        $this->register( 'get-content', 'Get content', 'Read a WordPress post or page including content, excerpt, slug, status, featured image and modified time.', array( 'post_id' => array( 'type' => 'integer', 'minimum' => 1 ) ), array( $this, 'get_content' ), array( $this, 'can_read_post' ), true, false, true, array( 'post_id' ) );
        $this->register( 'create-content', 'Create content', 'Create a post/page/CPT. Defaults to draft. Can optionally mark the content as using the Divi builder.', array( 'post_type' => array( 'type' => 'string', 'default' => 'post' ), 'title' => array( 'type' => 'string' ), 'content' => array( 'type' => 'string', 'default' => '' ), 'excerpt' => array( 'type' => 'string', 'default' => '' ), 'status' => array( 'type' => 'string', 'enum' => array( 'draft', 'pending', 'private', 'publish' ), 'default' => 'draft' ), 'slug' => array( 'type' => 'string', 'default' => '' ), 'parent' => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ), 'divi_builder' => array( 'type' => 'boolean', 'default' => false ) ), array( $this, 'create_content' ), array( $this, 'can_create_content' ), false, false, false, array( 'title' ) );
        $this->register( 'update-content', 'Update content', 'Update title, content, excerpt, status or slug. Uses optional optimistic locking through expected_modified_gmt.', array( 'post_id' => array( 'type' => 'integer', 'minimum' => 1 ), 'title' => array( 'type' => 'string' ), 'content' => array( 'type' => 'string' ), 'excerpt' => array( 'type' => 'string' ), 'status' => array( 'type' => 'string', 'enum' => array( 'draft', 'pending', 'private', 'publish' ) ), 'slug' => array( 'type' => 'string' ), 'expected_modified_gmt' => array( 'type' => 'string' ) ), array( $this, 'update_content' ), array( $this, 'can_edit_post' ), false, false, false, array( 'post_id' ) );
        $this->register( 'trash-content', 'Trash content', 'Move a post/page to the WordPress trash. Never permanently deletes.', array( 'post_id' => array( 'type' => 'integer', 'minimum' => 1 ), 'confirmation' => array( 'type' => 'string', 'enum' => array( 'TRASH_CONTENT' ) ) ), array( $this, 'trash_content' ), array( $this, 'can_delete_post' ), false, true, false, array( 'post_id', 'confirmation' ) );
        $this->register( 'list-media', 'List media', 'List WordPress media attachments with URLs, titles, alt text and MIME types.', array( 'search' => array( 'type' => 'string', 'default' => '' ), 'page' => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ), 'per_page' => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ) ), array( $this, 'list_media' ), array( $this, 'can_read' ), true, false, true );
        $this->register( 'import-media', 'Import media from URL', 'Safely download an HTTP(S) image/file and import it into the WordPress media library. Disabled by default.', array( 'url' => array( 'type' => 'string', 'format' => 'uri' ), 'title' => array( 'type' => 'string', 'default' => '' ), 'alt_text' => array( 'type' => 'string', 'default' => '' ), 'post_id' => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ) ), array( $this, 'import_media' ), array( $this, 'can_import_media' ), false, false, false, array( 'url' ) );
        $this->register( 'upload-media-base64', 'Upload media as Base64', 'Upload a Base64 encoded image/file to the WordPress media library.', array( 'filename' => array( 'type' => 'string' ), 'mime_type' => array( 'type' => 'string' ), 'content_base64' => array( 'type' => 'string' ), 'title' => array( 'type' => 'string', 'default' => '' ), 'alt_text' => array( 'type' => 'string', 'default' => '' ), 'post_id' => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ) ), array( $this, 'upload_media_base64' ), array( $this, 'can_write_media' ), false, false, false, array( 'filename', 'mime_type', 'content_base64' ) );
        $this->register( 'set-featured-image', 'Set featured image', 'Set or remove the featured image of a post/page.', array( 'post_id' => array( 'type' => 'integer', 'minimum' => 1 ), 'attachment_id' => array( 'type' => 'integer', 'minimum' => 0 ) ), array( $this, 'set_featured_image' ), array( $this, 'can_edit_post' ), false, false, true, array( 'post_id', 'attachment_id' ) );
        $this->register( 'seo-get', 'Read SEO metadata', 'Read Rank Math or Yoast SEO title and description for a post if supported.', array( 'post_id' => array( 'type' => 'integer', 'minimum' => 1 ) ), array( $this, 'seo_get' ), array( $this, 'can_read_post' ), true, false, true, array( 'post_id' ) );
        $this->register( 'seo-set', 'Set SEO metadata', 'Set SEO title and meta description for Rank Math or Yoast SEO if detected.', array( 'post_id' => array( 'type' => 'integer', 'minimum' => 1 ), 'title' => array( 'type' => 'string' ), 'description' => array( 'type' => 'string' ) ), array( $this, 'seo_set' ), array( $this, 'can_edit_post' ), false, false, true, array( 'post_id' ) );
        $this->register( 'divi-status', 'Divi status', 'Detect Divi theme/builder and report safe integration status.', array(), array( $this, 'divi_status' ), array( $this, 'can_read' ), true, false, true );
        $this->register( 'divi-get-layout', 'Read Divi layout', 'Read stored Divi post content and metadata.', array( 'post_id' => array( 'type' => 'integer', 'minimum' => 1 ) ), array( $this, 'divi_get_layout' ), array( $this, 'can_read_post' ), true, false, true, array( 'post_id' ) );
        $this->register( 'divi-save-layout', 'Save Divi layout', 'Save Divi-compatible post_content with revision backup and optimistic locking.', array( 'post_id' => array( 'type' => 'integer', 'minimum' => 1 ), 'content' => array( 'type' => 'string' ), 'expected_modified_gmt' => array( 'type' => 'string' ), 'enable_builder' => array( 'type' => 'boolean', 'default' => true ) ), array( $this, 'divi_save_layout' ), array( $this, 'can_write_divi_post' ), false, false, false, array( 'post_id', 'content' ) );
        $this->register( 'divi-replace-text', 'Replace text in Divi layout', 'Replace exact text inside stored Divi post_content.', array( 'post_id' => array( 'type' => 'integer', 'minimum' => 1 ), 'search' => array( 'type' => 'string' ), 'replace' => array( 'type' => 'string' ), 'expected_count' => array( 'type' => 'integer', 'minimum' => 0 ), 'expected_modified_gmt' => array( 'type' => 'string' ) ), array( $this, 'divi_replace_text' ), array( $this, 'can_write_divi_post' ), false, false, true, array( 'post_id', 'search', 'replace' ) );
        $this->register( 'wpforms-run', 'WPForms bridge', 'Execute selected official WPForms Abilities.', array( 'action' => array( 'type' => 'string', 'enum' => array( 'list-forms', 'get-form', 'describe-editing-schema', 'create-form', 'add-field', 'update-field', 'update-form-settings' ) ), 'parameters' => array( 'type' => 'object', 'default' => array(), 'additionalProperties' => true ) ), array( $this, 'wpforms_run' ), array( $this, 'can_read' ), false, false, false, array( 'action' ) );
        $this->register( 'updraft-backup', 'Start UpdraftPlus backup', 'Start a full UpdraftPlus backup.', array( 'confirmation' => array( 'type' => 'string', 'enum' => array( 'BACKUP_NOW' ) ), 'include_cloud' => array( 'type' => 'boolean', 'default' => true ) ), array( $this, 'updraft_backup' ), array( $this, 'can_manage' ), false, false, false, array( 'confirmation' ) );
        $this->register( 'list-plugins', 'List plugins', 'List installed plugins and update state.', array(), array( $this, 'list_plugins' ), array( $this, 'can_manage' ), true, false, true );
        $this->register( 'check-updates', 'Check updates', 'Refresh and report plugin/theme updates.', array(), array( $this, 'check_updates' ), array( $this, 'can_manage' ), true, false, true );
        $this->register( 'update-plugin', 'Update one plugin', 'Update exactly one installed plugin.', array( 'plugin' => array( 'type' => 'string' ), 'confirmation' => array( 'type' => 'string', 'enum' => array( 'UPDATE_PLUGIN' ) ) ), array( $this, 'update_plugin' ), array( $this, 'can_maintain_plugin' ), false, true, false, array( 'plugin', 'confirmation' ) );
        $this->register( 'update-theme', 'Update one theme', 'Update exactly one installed theme.', array( 'theme' => array( 'type' => 'string' ), 'confirmation' => array( 'type' => 'string', 'enum' => array( 'UPDATE_THEME' ) ) ), array( $this, 'update_theme' ), array( $this, 'can_maintain_theme' ), false, true, false, array( 'theme', 'confirmation' ) );
        $this->register( 'audit-log', 'Read MCP audit log', 'Read recent audit entries.', array( 'limit' => array( 'type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 200 ) ), array( $this, 'audit_log' ), array( $this, 'can_manage' ), true, false, true );
    }

    private function register( $slug, $label, $description, $properties, $callback, $permission, $readonly, $destructive, $idempotent, $required = array() ) {
        $schema = array( 'type' => 'object', 'properties' => $properties, 'additionalProperties' => false );
        if ( ! empty( $required ) ) { $schema['required'] = $required; }
        wp_register_ability( 'mgd-wordpress-mcp/' . $slug, array( 'label' => __( $label, 'mgd-wordpress-mcp' ), 'description' => __( $description, 'mgd-wordpress-mcp' ), 'category' => 'mgd-wordpress-mcp', 'input_schema' => $schema, 'execute_callback' => $callback, 'permission_callback' => $permission, 'meta' => array( 'public' => true, 'show_in_rest' => true, 'annotations' => array( 'readonly' => (bool) $readonly, 'destructive' => (bool) $destructive, 'idempotent' => (bool) $idempotent ), 'mcp' => array( 'public' => true, 'type' => 'tool' ) ) ) );
    }

    public function can_read() { return is_user_logged_in() && current_user_can( 'read' ); }
    public function can_manage() { return is_user_logged_in() && current_user_can( 'manage_options' ); }
    public function can_upload() { return is_user_logged_in() && current_user_can( 'upload_files' ); }
    public function can_write() { return MGD_WordPress_M