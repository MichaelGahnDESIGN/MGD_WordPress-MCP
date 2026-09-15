<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MGD_WordPress_MCP_Updater {
    private static $instance = null;
    private $repo = MGD_WPMCP_GITHUB_REPO;

    public static function instance() {
        if ( null === self::$instance ) { self::$instance = new self(); }
        return self::$instance;
    }

    private function __construct() {
        // Keep the proven transient integration used by MGD AI Kennzeichnung.
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
        // Also support WordPress' Update URI provider hook. Both paths use the same validated release data.
        add_filter( 'update_plugins_github.com', array( $this, 'update_uri_response' ), 10, 4 );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
        add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );
    }

    private function cache_key() { return 'mgd_wpmcp_release_' . md5( $this->repo ); }

    private function normalize_version( $tag ) {
        if ( ! is_string( $tag ) ) { return ''; }
        $version = ltrim( trim( $tag ), 'vV' );
        return preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ? $version : '';
    }

    private function should_refresh_cached_release( $cached ) {
        if ( ! is_array( $cached ) || empty( $cached['tag_name'] ) ) { return true; }
        $cached_version = $this->normalize_version( $cached['tag_name'] );
        // Critical: a cache containing the currently installed or an older release
        // must never hide a release that was published after the cache was created.
        return '' === $cached_version || ! version_compare( $cached_version, MGD_WPMCP_VERSION, '>' );
    }

    private function release() {
        if ( ! MGD_WordPress_MCP::setting( 'github_updates', true ) ) { return array(); }

        $cached = get_site_transient( $this->cache_key() );
        if ( is_array( $cached ) && ! $this->should_refresh_cached_release( $cached ) ) {
            return $cached;
        }

        $response = wp_safe_remote_get(
            'https://api.github.com/repos/' . $this->repo . '/releases/latest',
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/vnd.github+json',
                    'User-Agent' => 'MGD-WordPress-MCP/' . MGD_WPMCP_VERSION,
                ),
            )
        );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return array();
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) { return array(); }

        $version = $this->normalize_version( $data['tag_name'] );
        $package = $this->package_url( $data );
        if ( '' === $version || '' === $package ) { return array(); }

        set_site_transient( $this->cache_key(), $data, 6 * HOUR_IN_SECONDS );
        return $data;
    }

    private function package_url( $release ) {
        if ( empty( $release['assets'] ) || ! is_array( $release['assets'] ) ) { return ''; }
        foreach ( $release['assets'] as $asset ) {
            if ( ! isset( $asset['name'], $asset['browser_download_url'] ) || 'mgd-wordpress-mcp.zip' !== $asset['name'] ) { continue; }
            $url = esc_url_raw( $asset['browser_download_url'] );
            $parts = wp_parse_url( $url );
            if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) || 'github.com' !== ( $parts['host'] ?? '' ) ) { return ''; }
            $expected = '/MichaelGahnDESIGN/MGD_WordPress-MCP/releases/download/';
            if ( 0 !== strpos( $parts['path'] ?? '', $expected ) ) { return ''; }
            return $url;
        }
        return '';
    }

    private function build_update( $release ) {
        if ( empty( $release['tag_name'] ) ) { return null; }
        $version = $this->normalize_version( $release['tag_name'] );
        $package = $this->package_url( $release );
        if ( '' === $version || '' === $package || ! version_compare( $version, MGD_WPMCP_VERSION, '>' ) ) { return null; }

        return (object) array(
            'id' => 'https://github.com/' . $this->repo,
            'slug' => 'mgd-wordpress-mcp',
            'plugin' => MGD_WPMCP_BASENAME,
            'new_version' => $version,
            'version' => $version,
            'url' => 'https://github.com/' . $this->repo,
            'package' => $package,
            'tested' => '7.1',
            'requires' => '6.9',
            'requires_php' => '7.4',
            'icons' => array(
                '1x' => MGD_WPMCP_URL . 'assets/branding/plugin-icon.svg',
                '2x' => MGD_WPMCP_URL . 'assets/branding/plugin-icon.svg',
            ),
        );
    }

    public function inject_update( $transient ) {
        if ( ! is_object( $transient ) || empty( $transient->checked ) ) { return $transient; }
        $update = $this->build_update( $this->release() );
        if ( null === $update ) { return $transient; }
        if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) { $transient->response = array(); }
        $transient->response[ MGD_WPMCP_BASENAME ] = $update;
        return $transient;
    }

    public function update_uri_response( $update, $plugin_data, $plugin_file, $locales ) {
        if ( MGD_WPMCP_BASENAME !== $plugin_file ) { return $update; }
        $candidate = $this->build_update( $this->release() );
        if ( null === $candidate ) { return false; }
        return array(
            'id' => $candidate->id,
            'slug' => $candidate->slug,
            'version' => $candidate->new_version,
            'new_version' => $candidate->new_version,
            'url' => $candidate->url,
            'package' => $candidate->package,
            'tested' => $candidate->tested,
            'requires' => $candidate->requires,
            'requires_php' => $candidate->requires_php,
            'icons' => $candidate->icons,
        );
    }

    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || ! is_object( $args ) || 'mgd-wordpress-mcp' !== ( $args->slug ?? '' ) ) { return $result; }
        $release = $this->release();
        $version = ! empty( $release['tag_name'] ) ? $this->normalize_version( $release['tag_name'] ) : MGD_WPMCP_VERSION;
        return (object) array(
            'name' => 'MGD WordPress MCP',
            'slug' => 'mgd-wordpress-mcp',
            'version' => $version ?: MGD_WPMCP_VERSION,
            'author' => '<a href="https://Michael-Gahn.de">Michael Gahn DESIGN</a>',
            'homepage' => 'https://Michael-Gahn.de',
            'requires' => '6.9',
            'tested' => '7.1',
            'requires_php' => '7.4',
            'download_link' => $this->package_url( $release ),
            'icons' => array( '1x' => MGD_WPMCP_URL . 'assets/branding/plugin-icon.svg', '2x' => MGD_WPMCP_URL . 'assets/branding/plugin-icon.svg' ),
            'sections' => array(
                'description' => '<h2>WordPress trifft KI</h2><p>MGD WordPress MCP verbindet WordPress kontrolliert mit MCP-kompatiblen KI-Agenten.</p><h3>Privacy by Default</h3><p>Die Admin-Oberfläche lädt keine externen Fonts, Icon-CDNs oder JavaScript-CDNs.</p>',
                'installation' => '<p>Plugin installieren, Einrichtungs-Assistent starten, MCP Adapter prüfen und nur benötigte Rechte aktivieren.</p>',
                'changelog' => ! empty( $release['body'] ) ? wp_kses_post( nl2br( $release['body'] ) ) : '<p>Siehe GitHub Releases.</p>',
            ),
        );
    }

    public function clear_cache( $upgrader, $options ) {
        if ( isset( $options['type'] ) && 'plugin' === $options['type'] ) { delete_site_transient( $this->cache_key() ); }
    }
}
