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
            'mgd-wordpress-mcp/site-status',
            'mgd-wordpress-mcp/integrations',
            'mgd-wordpress-mcp/list-content',
            'mgd-wordpress-mcp/get-content',
            'mgd-wordpress-mcp/create-content',
            'mgd-wordpress-mcp/update-content',
            'mgd-wordpress-mcp/trash-content',
            'mgd-wordpress-mcp/list-media',
            'mgd-wordpress-mcp/import-media',
            'mgd-wordpress-mcp/upload-media-base64',
            'mgd-wordpress-mcp/set-featured-image',
            'mgd-wordpress-mcp/seo-get',
            'mgd-wordpress-mcp/seo-set',
            'mgd-wordpress-mcp/divi-status',
            'mgd-wordpress-mcp/divi-get-layout',
            'mgd-wordpress-mcp/divi-save-layout',
            'mgd-wordpress-mcp/divi-replace-text',
            'mgd-wordpress-mcp/wpforms-run',
            'mgd-wordpress-mcp/updraft-backup',
            'mgd-wordpress-mcp/list-plugins',
            'mgd-wordpress-mcp/check-updates',
            'mgd-wordpress-mcp/update-plugin',
            'mgd-wordpress-mcp/update-theme',
            'mgd-wordpress-mcp/audit-log',
        );
    }

    public function register_category() {
        if ( function_exists( 'wp_register_ability_category' ) ) {
            wp_register_ability_category(
                'mgd-wordpress-mcp',
                array(
                    'label'       => __( 'MGD WordPress MCP', 'mgd-wordpress-mcp' ),
                    'description' => __( 'Sichere WordPress-Werkzeuge für MCP-kompatible KI-Agenten.', 'mgd-wordpress-mcp' ),
                )
            );
        }
    }

    public function register_abilities() {
        if ( ! function_exists( 'wp_register_ability' ) ) {
            return;
        }

        $this->register( 'site-status', 'Site status', 'Read WordPress, PHP, theme, MCP and integration status.', array(), array( $this, 'site_status' ), array( $this, 'can_read' ), true, false, true );
        $this->register( 'integrations', 'Integrations', 'Detect supported plugins and integrations such as Divi, WPForms, UpdraftPlus, Rank Math, Yoast and WooCommerce.', array(), array( $this, 'integrations' ), array( $this, 'can_read' ), true, false, true );

        $this->register( 'list-content', 'List content', 'List posts, pages or another public post type with filters.', array(
            'post_type' => array( 'type' => 'string', 'default' => 'post' ),
            'status'    => array( 'type' => 'string', 'default' => 'publish' ),
            'search'    => array( 'type' => 'string', 'default' => '' ),
            'page'      => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
            'per_page'  => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ),
        ), array( $this, 'list_content' ), array( $this, 'can_read' ), true, false, true );

        $this->register( 'get-content', 'Get content', 'Read a WordPress post or page including content, excerpt, slug, status, featured image and modified time.', array(
            'post_id' => array( 'type' => 'integer', 'minimum' => 1 ),
        ), array( $this, 'get_content' ), array( $this, 'can_read_post' ), true, false, true, array( 'post_id' ) );

        $this->register( 'create-content', 'Create content', 'Create a post/page/CPT. Defaults to draft. Can optionally mark the content as using the Divi builder.', array(
            'post_type'    => array( 'type' => 'string', 'default' => 'post' ),
            'title'        => array( 'type' => 'string' ),
            'content'      => array( 'type' => 'string', 'default' => '' ),
            'excerpt'      => array( 'type' => 'string', 'default' => '' ),
            'status'       => array( 'type' => 'string', 'enum' => array( 'draft', 'pending', 'private', 'publish' ), 'default' => 'draft' ),
            'slug'         => array( 'type' => 'string', 'default' => '' ),
            'parent'       => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ),
            'divi_builder' => array( 'type' => 'boolean', 'default' => false ),
        ), array( $this, 'create_content' ), array( $this, 'can_create_content' ), false, false, false, array( 'title' ) );

        $this->register( 'update-content', 'Update content', 'Update title, content, excerpt, status or slug. Uses optional optimistic locking through expected_modified_gmt.', array(
            'post_id'               => array( 'type' => 'integer', 'minimum' => 1 ),
            'title'                 => array( 'type' => 'string' ),
            'content'               => array( 'type' => 'string' ),
            'excerpt'               => array( 'type' => 'string' ),
            'status'                => array( 'type' => 'string', 'enum' => array( 'draft', 'pending', 'private', 'publish' ) ),
            'slug'                  => array( 'type' => 'string' ),
            'expected_modified_gmt' => array( 'type' => 'string' ),
        ), array( $this, 'update_content' ), array( $this, 'can_edit_post' ), false, false, false, array( 'post_id' ) );

        $this->register( 'trash-content', 'Trash content', 'Move a post/page to the WordPress trash. Never permanently deletes.', array(
            'post_id'      => array( 'type' => 'integer', 'minimum' => 1 ),
            'confirmation' => array( 'type' => 'string', 'enum' => array( 'TRASH_CONTENT' ) ),
        ), array( $this, 'trash_content' ), array( $this, 'can_delete_post' ), false, true, false, array( 'post_id', 'confirmation' ) );

        $this->register( 'list-media', 'List media', 'List WordPress media attachments with URLs, titles, alt text and MIME types.', array(
            'search'   => array( 'type' => 'string', 'default' => '' ),
            'page'     => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
            'per_page' => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ),
        ), array( $this, 'list_media' ), array( $this, 'can_upload' ), true, false, true );

        $this->register( 'import-media', 'Import media from URL', 'Safely download an HTTP(S) image/file and import it into the WordPress media library. Disabled by default in plugin settings.', array(
            'url'      => array( 'type' => 'string', 'format' => 'uri' ),
            'title'    => array( 'type' => 'string', 'default' => '' ),
            'alt_text' => array( 'type' => 'string', 'default' => '' ),
            'post_id'  => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ),
        ), array( $this, 'import_media' ), array( $this, 'can_import_media' ), false, false, false, array( 'url' ) );

        $this->register( 'upload-media-base64', 'Upload media as Base64', 'Upload a Base64 encoded image/file to the WordPress media library. Maximum size is configurable.', array(
            'filename'       => array( 'type' => 'string' ),
            'mime_type'      => array( 'type' => 'string' ),
            'content_base64' => array( 'type' => 'string' ),
            'title'          => array( 'type' => 'string', 'default' => '' ),
            'alt_text'       => array( 'type' => 'string', 'default' => '' ),
            'post_id'        => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ),
        ), array( $this, 'upload_media_base64' ), array( $this, 'can_write_media' ), false, false, false, array( 'filename', 'mime_type', 'content_base64' ) );

        $this->register( 'set-featured-image', 'Set featured image', 'Set or remove the featured image of a post/page.', array(
            'post_id'       => array( 'type' => 'integer', 'minimum' => 1 ),
            'attachment_id' => array( 'type' => 'integer', 'minimum' => 0 ),
        ), array( $this, 'set_featured_image' ), array( $this, 'can_edit_post' ), false, false, true, array( 'post_id', 'attachment_id' ) );

        $this->register( 'seo-get', 'Read SEO metadata', 'Read Rank Math or Yoast SEO title and description for a post if supported.', array(
            'post_id' => array( 'type' => 'integer', 'minimum' => 1 ),
        ), array( $this, 'seo_get' ), array( $this, 'can_read_post' ), true, false, true, array( 'post_id' ) );

        $this->register( 'seo-set', 'Set SEO metadata', 'Set SEO title and meta description for Rank Math or Yoast SEO if detected.', array(
            'post_id'     => array( 'type' => 'integer', 'minimum' => 1 ),
            'title'       => array( 'type' => 'string' ),
            'description' => array( 'type' => 'string' ),
        ), array( $this, 'seo_set' ), array( $this, 'can_edit_post' ), false, false, true, array( 'post_id' ) );

        $this->register( 'divi-status', 'Divi status', 'Detect Divi theme/builder and report safe integration status.', array(), array( $this, 'divi_status' ), array( $this, 'can_read' ), true, false, true );
        $this->register( 'divi-get-layout', 'Read Divi layout', 'Read the stored post_content and relevant Divi builder metadata for a page/post.', array(
            'post_id' => array( 'type' => 'integer', 'minimum' => 1 ),
        ), array( $this, 'divi_get_layout' ), array( $this, 'can_read_post' ), true, false, true, array( 'post_id' ) );

        $this->register( 'divi-save-layout', 'Save Divi layout', 'Save Divi-compatible post_content with revision backup and optimistic locking. Experimental and disabled by default.', array(
            'post_id'               => array( 'type' => 'integer', 'minimum' => 1 ),
            'content'               => array( 'type' => 'string' ),
            'expected_modified_gmt' => array( 'type' => 'string' ),
            'enable_builder'        => array( 'type' => 'boolean', 'default' => true ),
        ), array( $this, 'divi_save_layout' ), array( $this, 'can_write_divi_post' ), false, false, false, array( 'post_id', 'content' ) );

        $this->register( 'divi-replace-text', 'Replace text in Divi layout', 'Replace exact text inside stored Divi post_content without changing the surrounding structure. Supports expected occurrence count.', array(
            'post_id'               => array( 'type' => 'integer', 'minimum' => 1 ),
            'search'                => array( 'type' => 'string' ),
            'replace'               => array( 'type' => 'string' ),
            'expected_count'        => array( 'type' => 'integer', 'minimum' => 0 ),
            'expected_modified_gmt' => array( 'type' => 'string' ),
        ), array( $this, 'divi_replace_text' ), array( $this, 'can_write_divi_post' ), false, false, true, array( 'post_id', 'search', 'replace' ) );

        $this->register( 'wpforms-run', 'WPForms bridge', 'Execute selected official WPForms Abilities. The underlying WPForms capability checks and write toggle remain authoritative.', array(
            'action' => array( 'type' => 'string', 'enum' => array( 'list-forms', 'get-form', 'describe-editing-schema', 'create-form', 'add-field', 'update-field', 'update-form-settings' ) ),
            'parameters' => array( 'type' => 'object', 'default' => array(), 'additionalProperties' => true ),
        ), array( $this, 'wpforms_run' ), array( $this, 'can_read' ), false, false, false, array( 'action' ) );

        $this->register( 'updraft-backup', 'Start UpdraftPlus backup', 'Start a full UpdraftPlus backup using the documented backup hook. Does not wait for completion.', array(
            'confirmation' => array( 'type' => 'string', 'enum' => array( 'BACKUP_NOW' ) ),
            'include_cloud' => array( 'type' => 'boolean', 'default' => true ),
        ), array( $this, 'updraft_backup' ), array( $this, 'can_manage' ), false, false, false, array( 'confirmation' ) );

        $this->register( 'list-plugins', 'List plugins', 'List installed plugins, versions, activation state and available updates.', array(), array( $this, 'list_plugins' ), array( $this, 'can_manage' ), true, false, true );
        $this->register( 'check-updates', 'Check updates', 'Refresh and report WordPress plugin/theme update information without installing anything.', array(), array( $this, 'check_updates' ), array( $this, 'can_manage' ), true, false, true );

        $this->register( 'update-plugin', 'Update one plugin', 'Update exactly one installed plugin. Maintenance writes must be enabled and an explicit confirmation string is required.', array(
            'plugin'       => array( 'type' => 'string', 'description' => 'Plugin basename, e.g. akismet/akismet.php' ),
            'confirmation' => array( 'type' => 'string', 'enum' => array( 'UPDATE_PLUGIN' ) ),
        ), array( $this, 'update_plugin' ), array( $this, 'can_maintain_plugin' ), false, true, false, array( 'plugin', 'confirmation' ) );

        $this->register( 'update-theme', 'Update one theme', 'Update exactly one installed theme. Maintenance writes must be enabled and an explicit confirmation string is required.', array(
            'theme'        => array( 'type' => 'string', 'description' => 'Theme stylesheet directory, e.g. Divi' ),
            'confirmation' => array( 'type' => 'string', 'enum' => array( 'UPDATE_THEME' ) ),
        ), array( $this, 'update_theme' ), array( $this, 'can_maintain_theme' ), false, true, false, array( 'theme', 'confirmation' ) );

        $this->register( 'audit-log', 'Read MCP audit log', 'Read recent MGD WordPress MCP audit entries.', array(
            'limit' => array( 'type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 200 ),
        ), array( $this, 'audit_log' ), array( $this, 'can_manage' ), true, false, true );
    }

    private function register( $slug, $label, $description, $properties, $callback, $permission, $readonly, $destructive, $idempotent, $required = array() ) {
        $schema = array(
            'type'                 => 'object',
            'properties'           => $properties,
            'additionalProperties' => false,
        );
        if ( ! empty( $required ) ) {
            $schema['required'] = $required;
        }

        wp_register_ability(
            'mgd-wordpress-mcp/' . $slug,
            array(
                'label'               => __( $label, 'mgd-wordpress-mcp' ),
                'description'         => __( $description, 'mgd-wordpress-mcp' ),
                'category'            => 'mgd-wordpress-mcp',
                'input_schema'        => $schema,
                'execute_callback'    => $callback,
                'permission_callback' => $permission,
                'meta'                => array(
                    'public'       => true,
                    'show_in_rest' => true,
                    'annotations'  => array(
                        'readonly'    => (bool) $readonly,
                        'destructive' => (bool) $destructive,
                        'idempotent'  => (bool) $idempotent,
                    ),
                    'mcp' => array(
                        'public' => true,
                        'type'   => 'tool',
                    ),
                ),
            )
        );
    }

    public function can_read() {
        return is_user_logged_in() && current_user_can( 'read' );
    }

    public function can_manage() {
        return is_user_logged_in() && current_user_can( 'manage_options' );
    }

    public function can_upload() {
        return is_user_logged_in() && current_user_can( 'upload_files' );
    }

    public function can_write() {
        return MGD_WordPress_MCP::setting( 'writes_enabled', false ) && is_user_logged_in() && current_user_can( 'edit_posts' );
    }

    public function can_write_media() {
        return $this->can_write() && current_user_can( 'upload_files' );
    }

    public function can_import_media() {
        return $this->can_write_media() && MGD_WordPress_MCP::setting( 'media_imports_enabled', false );
    }

    public function can_write_divi() {
        return $this->can_write() && MGD_WordPress_MCP::setting( 'divi_writes_enabled', false ) && current_user_can( 'manage_options' );
    }

    public function can_write_divi_post( $input ) {
        $post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
        return $this->can_write_divi() && $post_id && current_user_can( 'edit_post', $post_id );
    }

    public function can_maintain_plugin() {
        return MGD_WordPress_MCP::setting( 'maintenance_enabled', false ) && is_user_logged_in() && current_user_can( 'update_plugins' );
    }

    public function can_maintain_theme() {
        return MGD_WordPress_MCP::setting( 'maintenance_enabled', false ) && is_user_logged_in() && current_user_can( 'update_themes' );
    }

    public function can_read_post( $input ) {
        $post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
        return $post_id && current_user_can( 'read_post', $post_id );
    }

    public function can_edit_post( $input ) {
        $post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
        return $this->can_write() && $post_id && current_user_can( 'edit_post', $post_id );
    }

    public function can_delete_post( $input ) {
        $post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
        return $this->can_write() && $post_id && current_user_can( 'delete_post', $post_id );
    }

    public function can_create_content( $input ) {
        if ( ! $this->can_write() ) {
            return false;
        }
        $post_type = isset( $input['post_type'] ) ? sanitize_key( $input['post_type'] ) : 'post';
        $obj = get_post_type_object( $post_type );
        return $obj && current_user_can( $obj->cap->create_posts );
    }

    private function write_error() {
        return new WP_Error( 'mgd_mcp_writes_disabled', __( 'Schreibzugriff ist in MGD WordPress MCP deaktiviert.', 'mgd-wordpress-mcp' ), array( 'status' => 403 ) );
    }

    private function maintenance_error() {
        return new WP_Error( 'mgd_mcp_maintenance_disabled', __( 'Wartungsaktionen sind in MGD WordPress MCP deaktiviert.', 'mgd-wordpress-mcp' ), array( 'status' => 403 ) );
    }

    private function normalize_post( $post ) {
        return array(
            'id'                 => (int) $post->ID,
            'post_type'          => $post->post_type,
            'status'             => $post->post_status,
            'title'              => get_the_title( $post ),
            'slug'               => $post->post_name,
            'excerpt'            => $post->post_excerpt,
            'content'            => $post->post_content,
            'url'                => get_permalink( $post ),
            'edit_url'           => get_edit_post_link( $post->ID, 'raw' ),
            'featured_image_id'  => (int) get_post_thumbnail_id( $post->ID ),
            'modified_gmt'       => get_post_modified_time( 'c', true, $post ),
        );
    }

    public function site_status() {
        $theme = wp_get_theme();
        return array(
            'site_name'        => get_bloginfo( 'name' ),
            'site_url'         => home_url( '/' ),
            'wordpress'        => get_bloginfo( 'version' ),
            'php'              => PHP_VERSION,
            'theme'            => $theme->get( 'Name' ),
            'theme_version'    => $theme->get( 'Version' ),
            'mcp_adapter'      => class_exists( '\\WP\\MCP\\Core\\McpAdapter' ),
            'mcp_endpoint'     => MGD_WordPress_MCP::adapter_endpoint_url(),
            'writes_enabled'   => (bool) MGD_WordPress_MCP::setting( 'writes_enabled', false ),
            'divi_writes'      => (bool) MGD_WordPress_MCP::setting( 'divi_writes_enabled', false ),
            'maintenance'      => (bool) MGD_WordPress_MCP::setting( 'maintenance_enabled', false ),
            'https'            => is_ssl(),
        );
    }

    public function integrations() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $theme = wp_get_theme();
        return array(
            'divi' => array(
                'active' => ( 'Divi' === $theme->get_template() || 'Divi' === $theme->get_stylesheet() || defined( 'ET_BUILDER_VERSION' ) || is_plugin_active( 'divi-builder/divi-builder.php' ) ),
                'version' => defined( 'ET_BUILDER_VERSION' ) ? ET_BUILDER_VERSION : $theme->get( 'Version' ),
            ),
            'wpforms' => array(
                'active' => defined( 'WPFORMS_VERSION' ) || is_plugin_active( 'wpforms-lite/wpforms.php' ) || is_plugin_active( 'wpforms/wpforms.php' ),
                'version' => defined( 'WPFORMS_VERSION' ) ? WPFORMS_VERSION : '',
            ),
            'updraftplus' => array(
                'active' => is_plugin_active( 'updraftplus/updraftplus.php' ) || has_action( 'updraft_backupnow_backup_all' ),
            ),
            'rank_math' => array(
                'active' => defined( 'RANK_MATH_VERSION' ) || is_plugin_active( 'seo-by-rank-math/rank-math.php' ),
                'version' => defined( 'RANK_MATH_VERSION' ) ? RANK_MATH_VERSION : '',
            ),
            'yoast' => array(
                'active' => defined( 'WPSEO_VERSION' ) || is_plugin_active( 'wordpress-seo/wp-seo.php' ),
                'version' => defined( 'WPSEO_VERSION' ) ? WPSEO_VERSION : '',
            ),
            'woocommerce' => array(
                'active' => defined( 'WC_VERSION' ) || is_plugin_active( 'woocommerce/woocommerce.php' ),
                'version' => defined( 'WC_VERSION' ) ? WC_VERSION : '',
            ),
        );
    }

    public function list_content( $input ) {
        $post_type = isset( $input['post_type'] ) ? sanitize_key( $input['post_type'] ) : 'post';
        $obj = get_post_type_object( $post_type );
        if ( ! $obj || ! $obj->show_ui ) {
            return new WP_Error( 'mgd_mcp_invalid_post_type', __( 'Ungültiger oder nicht unterstützter Inhaltstyp.', 'mgd-wordpress-mcp' ) );
        }
        $page = max( 1, absint( isset( $input['page'] ) ? $input['page'] : 1 ) );
        $per_page = max( 1, min( 100, absint( isset( $input['per_page'] ) ? $input['per_page'] : 20 ) ) );
        $status = isset( $input['status'] ) ? sanitize_key( $input['status'] ) : 'publish';
        $query = new WP_Query( array(
            'post_type'      => $post_type,
            'post_status'    => $status,
            's'              => isset( $input['search'] ) ? sanitize_text_field( $input['search'] ) : '',
            'paged'          => $page,
            'posts_per_page' => $per_page,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ) );
        $items = array();
        foreach ( $query->posts as $post ) {
            if ( current_user_can( 'read_post', $post->ID ) ) {
                $items[] = $this->normalize_post( $post );
            }
        }
        return array( 'items' => $items, 'total' => (int) $query->found_posts, 'pages' => (int) $query->max_num_pages, 'page' => $page );
    }

    public function get_content( $input ) {
        $post = get_post( absint( $input['post_id'] ) );
        if ( ! $post ) {
            return new WP_Error( 'mgd_mcp_not_found', __( 'Inhalt nicht gefunden.', 'mgd-wordpress-mcp' ) );
        }
        return $this->normalize_post( $post );
    }

    public function create_content( $input ) {
        if ( ! $this->can_write() ) {
            return $this->write_error();
        }
        $post_type = isset( $input['post_type'] ) ? sanitize_key( $input['post_type'] ) : 'post';
        $status = isset( $input['status'] ) ? sanitize_key( $input['status'] ) : 'draft';
        $post_type_object = get_post_type_object( $post_type );
        $publish_cap = $post_type_object && isset( $post_type_object->cap->publish_posts ) ? $post_type_object->cap->publish_posts : 'publish_posts';
        if ( in_array( $status, array( 'publish', 'private' ), true ) && ! current_user_can( $publish_cap ) ) {
            $status = 'draft';
        }
        $postarr = array(
            'post_type'    => $post_type,
            'post_title'   => sanitize_text_field( $input['title'] ),
            'post_content' => isset( $input['content'] ) ? wp_kses_post( $input['content'] ) : '',
            'post_excerpt' => isset( $input['excerpt'] ) ? sanitize_textarea_field( $input['excerpt'] ) : '',
            'post_status'  => $status,
            'post_name'    => isset( $input['slug'] ) ? sanitize_title( $input['slug'] ) : '',
            'post_parent'  => isset( $input['parent'] ) ? absint( $input['parent'] ) : 0,
        );
        if ( ! empty( $input['divi_builder'] ) ) {
            if ( ! $this->can_write_divi() ) {
                return new WP_Error( 'mgd_mcp_divi_writes_disabled', __( 'Divi-Schreibzugriff ist deaktiviert.', 'mgd-wordpress-mcp' ), array( 'status' => 403 ) );
            }
            $postarr['post_content'] = isset( $input['content'] ) ? (string) $input['content'] : '';
        }
        $post_id = wp_insert_post( wp_slash( $postarr ), true );
        if ( is_wp_error( $post_id ) ) {
            MGD_WordPress_MCP_Audit::log( 'create-content', 'write', false, $post_id->get_error_message(), $post_type );
            return $post_id;
        }
        if ( ! empty( $input['divi_builder'] ) ) {
            update_post_meta( $post_id, '_et_pb_use_builder', 'on' );
        }
        MGD_WordPress_MCP_Audit::log( 'create-content', 'write', true, 'Created ' . $post_type . ' #' . $post_id, $post_type, $post_id );
        return $this->normalize_post( get_post( $post_id ) );
    }

    private function check_modified( $post, $expected ) {
        if ( empty( $expected ) ) {
            return true;
        }
        $current = get_post_modified_time( 'c', true, $post );
        return hash_equals( (string) $current, (string) $expected );
    }

    public function update_content( $input ) {
        if ( ! $this->can_write() ) {
            return $this->write_error();
        }
        $post = get_post( absint( $input['post_id'] ) );
        if ( ! $post ) {
            return new WP_Error( 'mgd_mcp_not_found', __( 'Inhalt nicht gefunden.', 'mgd-wordpress-mcp' ) );
        }
        if ( ! $this->check_modified( $post, isset( $input['expected_modified_gmt'] ) ? $input['expected_modified_gmt'] : '' ) ) {
            return new WP_Error( 'mgd_mcp_conflict', __( 'Der Inhalt wurde zwischenzeitlich geändert. Bitte zuerst neu laden.', 'mgd-wordpress-mcp' ), array( 'status' => 409 ) );
        }
        wp_save_post_revision( $post->ID );
        $data = array( 'ID' => $post->ID );
        if ( isset( $input['title'] ) ) { $data['post_title'] = sanitize_text_field( $input['title'] ); }
        if ( isset( $input['content'] ) ) { $data['post_content'] = wp_kses_post( $input['content'] ); }
        if ( isset( $input['excerpt'] ) ) { $data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] ); }
        if ( isset( $input['slug'] ) ) { $data['post_name'] = sanitize_title( $input['slug'] ); }
        if ( isset( $input['status'] ) ) {
            $status = sanitize_key( $input['status'] );
            if ( in_array( $status, array( 'publish', 'private' ), true ) ) {
                $post_type_object = get_post_type_object( $post->post_type );
                $publish_cap = $post_type_object && isset( $post_type_object->cap->publish_posts ) ? $post_type_object->cap->publish_posts : 'publish_posts';
                if ( ! current_user_can( $publish_cap ) ) {
                    $status = 'draft';
                }
            }
            $data['post_status'] = $status;
        }
        $result = wp_update_post( wp_slash( $data ), true );
        if ( is_wp_error( $result ) ) {
            MGD_WordPress_MCP_Audit::log( 'update-content', 'write', false, $result->get_error_message(), $post->post_type, $post->ID );
            return $result;
        }
        MGD_WordPress_MCP_Audit::log( 'update-content', 'write', true, 'Updated #' . $post->ID, $post->post_type, $post->ID );
        return $this->normalize_post( get_post( $post->ID ) );
    }

    public function trash_content( $input ) {
        if ( 'TRASH_CONTENT' !== $input['confirmation'] ) {
            return new WP_Error( 'mgd_mcp_confirmation', __( 'Bestätigung fehlt.', 'mgd-wordpress-mcp' ) );
        }
        $post_id = absint( $input['post_id'] );
        $post = get_post( $post_id );
        if ( ! $post ) { return new WP_Error( 'mgd_mcp_not_found', __( 'Inhalt nicht gefunden.', 'mgd-wordpress-mcp' ) ); }
        $result = wp_trash_post( $post_id );
        if ( ! $result ) { return new WP_Error( 'mgd_mcp_trash_failed', __( 'Inhalt konnte nicht in den Papierkorb verschoben werden.', 'mgd-wordpress-mcp' ) ); }
        MGD_WordPress_MCP_Audit::log( 'trash-content', 'destructive', true, 'Trashed #' . $post_id, $post->post_type, $post_id );
        return array( 'post_id' => $post_id, 'status' => 'trash' );
    }

    public function list_media( $input ) {
        $query = new WP_Query( array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            's'              => isset( $input['search'] ) ? sanitize_text_field( $input['search'] ) : '',
            'paged'          => max( 1, absint( isset( $input['page'] ) ? $input['page'] : 1 ) ),
            'posts_per_page' => max( 1, min( 100, absint( isset( $input['per_page'] ) ? $input['per_page'] : 20 ) ) ),
        ) );
        $items = array();
        foreach ( $query->posts as $attachment ) {
            if ( ! current_user_can( 'read_post', $attachment->ID ) ) {
                continue;
            }
            $items[] = array(
                'id' => (int) $attachment->ID,
                'title' => get_the_title( $attachment ),
                'url' => wp_get_attachment_url( $attachment->ID ),
                'mime_type' => get_post_mime_type( $attachment->ID ),
                'alt_text' => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
            );
        }
        return array( 'items' => $items, 'total' => (int) $query->found_posts, 'pages' => (int) $query->max_num_pages );
    }

    public function import_media( $input ) {
        if ( ! $this->can_import_media() ) {
            return new WP_Error( 'mgd_mcp_media_import_disabled', __( 'Externe Medienimporte sind deaktiviert.', 'mgd-wordpress-mcp' ), array( 'status' => 403 ) );
        }
        $post_parent = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
        if ( $post_parent && ! current_user_can( 'edit_post', $post_parent ) ) {
            return new WP_Error( 'mgd_mcp_forbidden_parent', __( 'Keine Berechtigung für den angegebenen übergeordneten Inhalt.', 'mgd-wordpress-mcp' ), array( 'status' => 403 ) );
        }
        $url = esc_url_raw( $input['url'] );
        if ( ! wp_http_validate_url( $url ) ) {
            return new WP_Error( 'mgd_mcp_invalid_url', __( 'Ungültige oder unsichere URL.', 'mgd-wordpress-mcp' ) );
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $tmp = download_url( $url, 30 );
        if ( is_wp_error( $tmp ) ) { return $tmp; }
        $max = max( 1, absint( MGD_WordPress_MCP::setting( 'max_upload_mb', 8 ) ) ) * MB_IN_BYTES;
        if ( filesize( $tmp ) > $max ) {
            @unlink( $tmp );
            return new WP_Error( 'mgd_mcp_file_too_large', __( 'Die Datei überschreitet das konfigurierte Größenlimit.', 'mgd-wordpress-mcp' ) );
        }
        $path = wp_parse_url( $url, PHP_URL_PATH );
        $name = sanitize_file_name( basename( $path ) );
        if ( ! $name ) { $name = 'import-' . time(); }
        $file = array( 'name' => $name, 'tmp_name' => $tmp );
        $attachment_id = media_handle_sideload( $file, $post_parent, isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : '' );
        if ( is_wp_error( $attachment_id ) ) { @unlink( $tmp ); return $attachment_id; }
        if ( ! empty( $input['alt_text'] ) ) { update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt_text'] ) ); }
        MGD_WordPress_MCP_Audit::log( 'import-media', 'write', true, 'Imported media #' . $attachment_id, 'attachment', $attachment_id );
        return array( 'attachment_id' => (int) $attachment_id, 'url' => wp_get_attachment_url( $attachment_id ), 'mime_type' => get_post_mime_type( $attachment_id ) );
    }

    public function upload_media_base64( $input ) {
        if ( ! $this->can_write_media() ) { return $this->write_error(); }
        $data = base64_decode( preg_replace( '#^data:[^;]+;base64,#', '', (string) $input['content_base64'] ), true );
        if ( false === $data ) { return new WP_Error( 'mgd_mcp_invalid_base64', __( 'Ungültige Base64-Daten.', 'mgd-wordpress-mcp' ) ); }
        $max = max( 1, absint( MGD_WordPress_MCP::setting( 'max_upload_mb', 8 ) ) ) * MB_IN_BYTES;
        if ( strlen( $data ) > $max ) { return new WP_Error( 'mgd_mcp_file_too_large', __( 'Die Datei überschreitet das konfigurierte Größenlimit.', 'mgd-wordpress-mcp' ) ); }
        $post_parent = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
        if ( $post_parent && ! current_user_can( 'edit_post', $post_parent ) ) {
            return new WP_Error( 'mgd_mcp_forbidden_parent', __( 'Keine Berechtigung für den angegebenen übergeordneten Inhalt.', 'mgd-wordpress-mcp' ), array( 'status' => 403 ) );
        }
        $filename = sanitize_file_name( $input['filename'] );
        $mime = sanitize_mime_type( $input['mime_type'] );
        $allowed = get_allowed_mime_types();
        if ( ! in_array( $mime, $allowed, true ) ) { return new WP_Error( 'mgd_mcp_mime_not_allowed', __( 'Dieser MIME-Typ ist nicht erlaubt.', 'mgd-wordpress-mcp' ) ); }
        $upload = wp_upload_bits( $filename, null, $data );
        if ( ! empty( $upload['error'] ) ) { return new WP_Error( 'mgd_mcp_upload_failed', $upload['error'] ); }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_id = wp_insert_attachment( array(
            'post_mime_type' => $mime,
            'post_title' => ! empty( $input['title'] ) ? sanitize_text_field( $input['title'] ) : sanitize_text_field( pathinfo( $filename, PATHINFO_FILENAME ) ),
            'post_status' => 'inherit',
            'post_parent' => $post_parent,
        ), $upload['file'] );
        if ( is_wp_error( $attachment_id ) ) { return $attachment_id; }
        $metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
        wp_update_attachment_metadata( $attachment_id, $metadata );
        if ( ! empty( $input['alt_text'] ) ) { update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt_text'] ) ); }
        MGD_WordPress_MCP_Audit::log( 'upload-media-base64', 'write', true, 'Uploaded media #' . $attachment_id, 'attachment', $attachment_id );
        return array( 'attachment_id' => (int) $attachment_id, 'url' => wp_get_attachment_url( $attachment_id ), 'mime_type' => $mime );
    }

    public function set_featured_image( $input ) {
        if ( ! $this->can_write() ) { return $this->write_error(); }
        $post_id = absint( $input['post_id'] );
        $attachment_id = absint( $input['attachment_id'] );
        if ( 0 === $attachment_id ) {
            delete_post_thumbnail( $post_id );
        } else {
            if ( 'attachment' !== get_post_type( $attachment_id ) ) { return new WP_Error( 'mgd_mcp_invalid_attachment', __( 'Ungültige Medien-ID.', 'mgd-wordpress-mcp' ) ); }
            set_post_thumbnail( $post_id, $attachment_id );
        }
        MGD_WordPress_MCP_Audit::log( 'set-featured-image', 'write', true, 'Featured image changed', get_post_type( $post_id ), $post_id );
        return array( 'post_id' => $post_id, 'attachment_id' => $attachment_id );
    }

    private function seo_provider() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        if ( defined( 'RANK_MATH_VERSION' ) || is_plugin_active( 'seo-by-rank-math/rank-math.php' ) ) { return 'rank_math'; }
        if ( defined( 'WPSEO_VERSION' ) || is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) { return 'yoast'; }
        return '';
    }

    public function seo_get( $input ) {
        $post_id = absint( $input['post_id'] );
        $provider = $this->seo_provider();
        if ( 'rank_math' === $provider ) {
            return array( 'provider' => $provider, 'title' => get_post_meta( $post_id, 'rank_math_title', true ), 'description' => get_post_meta( $post_id, 'rank_math_description', true ) );
        }
        if ( 'yoast' === $provider ) {
            return array( 'provider' => $provider, 'title' => get_post_meta( $post_id, '_yoast_wpseo_title', true ), 'description' => get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ) );
        }
        return array( 'provider' => '', 'title' => '', 'description' => '' );
    }

    public function seo_set( $input ) {
        if ( ! $this->can_write() ) { return $this->write_error(); }
        $post_id = absint( $input['post_id'] );
        $provider = $this->seo_provider();
        if ( ! $provider ) { return new WP_Error( 'mgd_mcp_seo_missing', __( 'Kein unterstütztes SEO-Plugin erkannt.', 'mgd-wordpress-mcp' ) ); }
        $title = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : null;
        $description = isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : null;
        if ( 'rank_math' === $provider ) {
            if ( null !== $title ) { update_post_meta( $post_id, 'rank_math_title', $title ); }
            if ( null !== $description ) { update_post_meta( $post_id, 'rank_math_description', $description ); }
        } else {
            if ( null !== $title ) { update_post_meta( $post_id, '_yoast_wpseo_title', $title ); }
            if ( null !== $description ) { update_post_meta( $post_id, '_yoast_wpseo_metadesc', $description ); }
        }
        MGD_WordPress_MCP_Audit::log( 'seo-set', 'write', true, 'SEO metadata updated via ' . $provider, get_post_type( $post_id ), $post_id );
        return $this->seo_get( array( 'post_id' => $post_id ) );
    }

    private function is_divi_available() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $theme = wp_get_theme();
        return 'Divi' === $theme->get_template() || 'Divi' === $theme->get_stylesheet() || defined( 'ET_BUILDER_VERSION' ) || is_plugin_active( 'divi-builder/divi-builder.php' );
    }

    public function divi_status() {
        $theme = wp_get_theme();
        return array(
            'available' => $this->is_divi_available(),
            'theme' => $theme->get( 'Name' ),
            'theme_version' => $theme->get( 'Version' ),
            'builder_version' => defined( 'ET_BUILDER_VERSION' ) ? ET_BUILDER_VERSION : '',
            'writes_enabled' => (bool) MGD_WordPress_MCP::setting( 'divi_writes_enabled', false ),
            'mode' => 'raw-layout-bridge',
            'warning' => __( 'Divi 5 writes operate on stored post_content with WordPress revisions. The agent must understand the site-specific Divi layout format.', 'mgd-wordpress-mcp' ),
        );
    }

    public function divi_get_layout( $input ) {
        if ( ! $this->is_divi_available() ) { return new WP_Error( 'mgd_mcp_divi_missing', __( 'Divi wurde nicht erkannt.', 'mgd-wordpress-mcp' ) ); }
        $post = get_post( absint( $input['post_id'] ) );
        if ( ! $post ) { return new WP_Error( 'mgd_mcp_not_found', __( 'Inhalt nicht gefunden.', 'mgd-wordpress-mcp' ) ); }
        return array(
            'post_id' => (int) $post->ID,
            'title' => get_the_title( $post ),
            'content' => $post->post_content,
            'use_builder' => get_post_meta( $post->ID, '_et_pb_use_builder', true ),
            'page_layout' => get_post_meta( $post->ID, '_et_pb_page_layout', true ),
            'modified_gmt' => get_post_modified_time( 'c', true, $post ),
        );
    }

    public function divi_save_layout( $input ) {
        if ( ! $this->can_write_divi() ) { return new WP_Error( 'mgd_mcp_divi_writes_disabled', __( 'Divi-Schreibzugriff ist deaktiviert.', 'mgd-wordpress-mcp' ), array( 'status' => 403 ) ); }
        if ( ! $this->is_divi_available() ) { return new WP_Error( 'mgd_mcp_divi_missing', __( 'Divi wurde nicht erkannt.', 'mgd-wordpress-mcp' ) ); }
        $post = get_post( absint( $input['post_id'] ) );
        if ( ! $post ) { return new WP_Error( 'mgd_mcp_not_found', __( 'Inhalt nicht gefunden.', 'mgd-wordpress-mcp' ) ); }
        if ( ! current_user_can( 'edit_post', $post->ID ) ) { return new WP_Error( 'mgd_mcp_forbidden', __( 'Keine Berechtigung.', 'mgd-wordpress-mcp' ), array( 'status' => 403 ) ); }
        if ( ! $this->check_modified( $post, isset( $input['expected_modified_gmt'] ) ? $input['expected_modified_gmt'] : '' ) ) {
            return new WP_Error( 'mgd_mcp_conflict', __( 'Das Divi-Layout wurde zwischenzeitlich geändert.', 'mgd-wordpress-mcp' ), array( 'status' => 409 ) );
        }
        wp_save_post_revision( $post->ID );
        $result = wp_update_post( wp_slash( array( 'ID' => $post->ID, 'post_content' => (string) $input['content'] ) ), true );
        if ( is_wp_error( $result ) ) { return $result; }
        if ( ! isset( $input['enable_builder'] ) || $input['enable_builder'] ) { update_post_meta( $post->ID, '_et_pb_use_builder', 'on' ); }
        MGD_WordPress_MCP_Audit::log( 'divi-save-layout', 'write', true, 'Divi layout saved with revision', $post->post_type, $post->ID );
        return $this->divi_get_layout( array( 'post_id' => $post->ID ) );
    }

    public function divi_replace_text( $input ) {
        if ( ! $this->can_write_divi() ) { return new WP_Error( 'mgd_mcp_divi_writes_disabled', __( 'Divi-Schreibzugriff ist deaktiviert.', 'mgd-wordpress-mcp' ), array( 'status' => 403 ) ); }
        $post = get_post( absint( $input['post_id'] ) );
        if ( ! $post ) { return new WP_Error( 'mgd_mcp_not_found', __( 'Inhalt nicht gefunden.', 'mgd-wordpress-mcp' ) ); }
        if ( ! current_user_can( 'edit_post', $post->ID ) ) { return new WP_Error( 'mgd_mcp_forbidden', __( 'Keine Berechtigung.', 'mgd-wordpress-mcp' ), array( 'status' => 403 ) ); }
        if ( ! $this->check_modified( $post, isset( $input['expected_modified_gmt'] ) ? $input['expected_modified_gmt'] : '' ) ) {
            return new WP_Error( 'mgd_mcp_conflict', __( 'Das Layout wurde zwischenzeitlich geändert.', 'mgd-wordpress-mcp' ), array( 'status' => 409 ) );
        }
        $count = substr_count( $post->post_content, (string) $input['search'] );
        if ( isset( $input['expected_count'] ) && (int) $input['expected_count'] !== $count ) {
            return new WP_Error( 'mgd_mcp_occurrence_mismatch', sprintf( __( 'Erwartet: %1$d Treffer. Gefunden: %2$d.', 'mgd-wordpress-mcp' ), (int) $input['expected_count'], $count ), array( 'status' => 409 ) );
        }
        if ( 0 === $count ) { return new WP_Error( 'mgd_mcp_text_not_found', __( 'Text wurde nicht gefunden.', 'mgd-wordpress-mcp' ) ); }
        wp_save_post_revision( $post->ID );
        $new = str_replace( (string) $input['search'], (string) $input['replace'], $post->post_content, $replacements );
        $result = wp_update_post( wp_slash( array( 'ID' => $post->ID, 'post_content' => $new ) ), true );
        if ( is_wp_error( $result ) ) { return $result; }
        MGD_WordPress_MCP_Audit::log( 'divi-replace-text', 'write', true, 'Replaced ' . $replacements . ' occurrence(s)', $post->post_type, $post->ID );
        return array( 'post_id' => (int) $post->ID, 'replacements' => (int) $replacements, 'modified_gmt' => get_post_modified_time( 'c', true, get_post( $post->ID ) ) );
    }

    public function wpforms_run( $input ) {
        if ( ! function_exists( 'wp_get_ability' ) ) { return new WP_Error( 'mgd_mcp_abilities_missing', __( 'Abilities API nicht verfügbar.', 'mgd-wordpress-mcp' ) ); }
        $action = sanitize_key( $input['action'] );
        $write_actions = array( 'create-form', 'add-field', 'update-field', 'update-form-settings' );
        if ( in_array( $action, $write_actions, true ) && ! $this->can_write() ) { return $this->write_error(); }
        $ability = wp_get_ability( 'wpforms/' . $action );
        if ( ! $ability ) { return new WP_Error( 'mgd_mcp_wpforms_ability_missing', __( 'Die angeforderte WPForms Ability ist nicht verfügbar. Prüfe WPForms-Version und AI/MCP-Einstellungen.', 'mgd-wordpress-mcp' ) ); }
        $params = isset( $input['parameters'] ) && is_array( $input['parameters'] ) ? $input['parameters'] : array();
        $result = $ability->execute( $params );
        MGD_WordPress_MCP_Audit::log( 'wpforms-run:' . $action, in_array( $action, $write_actions, true ) ? 'write' : 'read', ! is_wp_error( $result ), is_wp_error( $result ) ? $result->get_error_message() : 'WPForms ability executed' );
        return $result;
    }

    public function updraft_backup( $input ) {
        if ( 'BACKUP_NOW' !== $input['confirmation'] ) { return new WP_Error( 'mgd_mcp_confirmation', __( 'Bestätigung fehlt.', 'mgd-wordpress-mcp' ) ); }
        if ( ! has_action( 'updraft_backupnow_backup_all' ) ) { return new WP_Error( 'mgd_mcp_updraft_missing', __( 'UpdraftPlus oder der Backup-Hook wurde nicht erkannt.', 'mgd-wordpress-mcp' ) ); }
        $nocloud = ( isset( $input['include_cloud'] ) && ! $input['include_cloud'] ) ? 1 : 0;
        do_action( 'updraft_backupnow_backup_all', array( 'nocloud' => $nocloud ) );
        MGD_WordPress_MCP_Audit::log( 'updraft-backup', 'admin', true, 'Backup triggered' );
        return array( 'triggered' => true, 'include_cloud' => 0 === $nocloud, 'note' => __( 'UpdraftPlus arbeitet asynchron. Prüfe den Backup-Status in UpdraftPlus, bevor du riskante Updates startest.', 'mgd-wordpress-mcp' ) );
    }

    public function list_plugins() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        wp_update_plugins();
        $updates = get_site_transient( 'update_plugins' );
        $items = array();
        foreach ( get_plugins() as $basename => $data ) {
            $items[] = array(
                'plugin' => $basename,
                'name' => $data['Name'],
                'version' => $data['Version'],
                'active' => is_plugin_active( $basename ),
                'network_active' => is_multisite() && is_plugin_active_for_network( $basename ),
                'update_version' => isset( $updates->response[ $basename ]->new_version ) ? $updates->response[ $basename ]->new_version : '',
            );
        }
        return array( 'plugins' => $items );
    }

    public function check_updates() {
        wp_update_plugins();
        wp_update_themes();
        $plugins = get_site_transient( 'update_plugins' );
        $themes = get_site_transient( 'update_themes' );
        $plugin_updates = array();
        if ( is_object( $plugins ) && ! empty( $plugins->response ) ) {
            foreach ( $plugins->response as $plugin => $data ) { $plugin_updates[] = array( 'plugin' => $plugin, 'new_version' => $data->new_version ); }
        }
        $theme_updates = array();
        if ( is_object( $themes ) && ! empty( $themes->response ) ) {
            foreach ( $themes->response as $theme => $data ) { $theme_updates[] = array( 'theme' => $theme, 'new_version' => isset( $data['new_version'] ) ? $data['new_version'] : '' ); }
        }
        return array( 'plugins' => $plugin_updates, 'themes' => $theme_updates );
    }

    public function update_plugin( $input ) {
        if ( ! $this->can_maintain_plugin() ) { return $this->maintenance_error(); }
        if ( 'UPDATE_PLUGIN' !== $input['confirmation'] ) { return new WP_Error( 'mgd_mcp_confirmation', __( 'Bestätigung fehlt.', 'mgd-wordpress-mcp' ) ); }
        $plugin = sanitize_text_field( $input['plugin'] );
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        if ( ! array_key_exists( $plugin, get_plugins() ) ) { return new WP_Error( 'mgd_mcp_plugin_missing', __( 'Plugin nicht gefunden.', 'mgd-wordpress-mcp' ) ); }
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        wp_update_plugins();
        $skin = new Automatic_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );
        $result = $upgrader->upgrade( $plugin );
        if ( is_wp_error( $result ) || false === $result ) {
            $message = is_wp_error( $result ) ? $result->get_error_message() : __( 'Update fehlgeschlagen.', 'mgd-wordpress-mcp' );
            MGD_WordPress_MCP_Audit::log( 'update-plugin', 'admin', false, $message, 'plugin' );
            return is_wp_error( $result ) ? $result : new WP_Error( 'mgd_mcp_update_failed', $message );
        }
        MGD_WordPress_MCP_Audit::log( 'update-plugin', 'admin', true, 'Updated ' . $plugin, 'plugin' );
        return array( 'plugin' => $plugin, 'updated' => true );
    }

    public function update_theme( $input ) {
        if ( ! $this->can_maintain_theme() ) { return $this->maintenance_error(); }
        if ( 'UPDATE_THEME' !== $input['confirmation'] ) { return new WP_Error( 'mgd_mcp_confirmation', __( 'Bestätigung fehlt.', 'mgd-wordpress-mcp' ) ); }
        $theme = isset( $input['theme'] ) ? sanitize_text_field( wp_unslash( $input['theme'] ) ) : '';
        if ( '' === $theme || basename( $theme ) !== $theme || false !== strpos( $theme, '..' ) ) {
            return new WP_Error( 'mgd_mcp_theme_invalid', __( 'Ungültiger Theme-Slug.', 'mgd-wordpress-mcp' ) );
        }
        if ( ! wp_get_theme( $theme )->exists() ) { return new WP_Error( 'mgd_mcp_theme_missing', __( 'Theme nicht gefunden.', 'mgd-wordpress-mcp' ) ); }
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        wp_update_themes();
        $skin = new Automatic_Upgrader_Skin();
        $upgrader = new Theme_Upgrader( $skin );
        $result = $upgrader->upgrade( $theme );
        if ( is_wp_error( $result ) || false === $result ) {
            $message = is_wp_error( $result ) ? $result->get_error_message() : __( 'Update fehlgeschlagen.', 'mgd-wordpress-mcp' );
            MGD_WordPress_MCP_Audit::log( 'update-theme', 'admin', false, $message, 'theme' );
            return is_wp_error( $result ) ? $result : new WP_Error( 'mgd_mcp_update_failed', $message );
        }
        MGD_WordPress_MCP_Audit::log( 'update-theme', 'admin', true, 'Updated ' . $theme, 'theme' );
        return array( 'theme' => $theme, 'updated' => true );
    }

    public function audit_log( $input ) {
        return array( 'entries' => MGD_WordPress_MCP_Audit::latest( isset( $input['limit'] ) ? $input['limit'] : 50 ) );
    }
}
