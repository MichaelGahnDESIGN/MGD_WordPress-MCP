<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MGD_WordPress_MCP_Updater {
    private static $instance = null;
    private $repo = MGD_WPMCP_GITHUB_REPO;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
        add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );
    }

    private function release() {
        if ( ! MGD_WordPress_MCP::setting( 'github_updates', true ) ) {
            return false;
        }
        $cache_key = 'mgd_wpmcp_release_' . md5( $this->repo );
        $cached = get_site_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }
        $response = wp_safe_remote_get(
            'https://api.github.com/repos/' . $this->repo . '/releases/latest',
            array(
                'timeout' => 10,
                'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'MGD-WordPress-MCP/' . MGD_WPMCP_VERSION ),
            )
        );
        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            set_site_transient( $cache_key, array(), HOUR_IN_SECONDS );
            return array();
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) ) {
            return array();
        }
        set_site_transient( $cache_key, $data, 6 * HOUR_IN_SECONDS );
        return $data;
    }

    private function package_url( $release ) {
        if ( empty( $release['assets'] ) || ! is_array( $release['assets'] ) ) {
            return '';
        }
        foreach ( $release['assets'] as $asset ) {
            if ( isset( $asset['name'], $asset['browser_download_url'] ) && 'mgd-wordpress-mcp.zip' === $asset['name'] ) {
                return esc_url_raw( $asset['browser_download_url'] );
            }
        }
        return '';
    }

    public function inject_update( $transient ) {
        if ( ! is_object( $transient ) || empty( $transient->checked[ MGD_WPMCP_BASENAME ] ) ) {
            return $transient;
        }
        $release = $this->release();
        if ( empty( $release['tag_name'] ) ) {
            return $transient;
        }
        $version = ltrim( $release['tag_name'], 'vV' );
        $package = $this->package_url( $release );
        if ( ! $package || version_compare( MGD_WPMCP_VERSION, $version, '>=' ) ) {
            return $transient;
        }
        $transient->response[ MGD_WPMCP_BASENAME ] = (object) array(
            'slug'        => 'mgd-wordpress-mcp',
            'plugin'      => MGD_WPMCP_BASENAME,
            'new_version' => $version,
            'url'         => 'https://github.com/' . $this->repo,
            'package'     => $package,
            'icons'       => array(),
        );
        return $transient;
    }

    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) || 'mgd-wordpress-mcp' !== $args->slug ) {
            return $result;
        }
        $release = $this->release();
        if ( empty( $release['tag_name'] ) ) {
            return $result;
        }
        return (object) array(
            'name'          => 'MGD WordPress MCP',
            'slug'          => 'mgd-wordpress-mcp',
            'version'       => ltrim( $release['tag_name'], 'vV' ),
            'author'        => '<a href="https://Michael-Gahn.de">Michael Gahn DESIGN</a>',
            'homepage'      => 'https://github.com/' . $this->repo,
            'requires'      => '6.9',
            'requires_php'  => '7.4',
            'download_link' => $this->package_url( $release ),
            'sections'      => array(
                'description' => 'Sichere MCP- und Abilities-Bridge für WordPress.',
                'changelog'   => ! empty( $release['body'] ) ? wp_kses_post( nl2br( $release['body'] ) ) : '',
            ),
        );
    }

    public function clear_cache( $upgrader, $options ) {
        if ( isset( $options['type'] ) && 'plugin' === $options['type'] ) {
            delete_site_transient( 'mgd_wpmcp_release_' . md5( $this->repo ) );
        }
    }
}
