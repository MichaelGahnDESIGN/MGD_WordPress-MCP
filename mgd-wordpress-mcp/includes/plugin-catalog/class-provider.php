<?php
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-media.php';

/** Öffentliche Metadaten im Cache; lokale Installation und Rechte immer frisch ermitteln. */
final class MGD_Catalog_V4_Provider {
    private const OWNER = 'MichaelGahnDESIGN';
    private const FEED = 'https://theme.michael-gahn.de/api/catalog/plugins.json';
    private const CACHE = 'mgd_wordpress_catalog_v3';

    public function catalog( bool $refresh = false ): array {
        $cached = get_transient( self::CACHE );
        if ( ! $refresh && is_array( $cached ) && isset( $cached['items'], $cached['source'], $cached['checked_at'] ) ) { return $cached; }
        $defaults = json_decode( file_get_contents( __DIR__ . '/plugins.json' ), true );
        $response = wp_remote_get( self::FEED, array( 'timeout' => 4, 'redirection' => 0, 'limit_response_size' => 524288, 'headers' => array( 'Accept' => 'application/json' ) ) );
        $feed = ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ? json_decode( wp_remote_retrieve_body( $response ), true ) : null;
        $central = is_array( $feed ) && 1 === ( $feed['schemaVersion'] ?? null ) && is_array( $feed['items'] ?? null );
        $entries = $central ? $feed['items'] : array_values( $defaults );
        $items = array();
        $fetch_failed = 0;
        foreach ( array_slice( $entries, 0, 80 ) as $entry ) {
            if ( ! is_array( $entry ) || ( $central && ( 'wordpress' !== ( $entry['platform'] ?? '' ) || 'live' !== ( $entry['status'] ?? '' ) ) ) ) { continue; }
            $repo = $central ? $this->repository( $entry['repositoryUrl'] ?? '' ) : $entry['repository'];
            if ( ! $repo ) { continue; }
            $local = $defaults[ $repo ] ?? array();
            $tier = 'free' === ( $entry['tier'] ?? '' ) ? 'free' : 'paid';
            if ( ! $central && is_array( $cached ) ) {
                foreach ( $cached['items'] as $old ) {
                    if ( $repo === $old['repository'] && 'paid' === $old['tier'] ) { $tier = 'paid'; break; }
                }
            }
            $release = 'free' === $tier ? $this->release( $repo, $refresh ) : array();
            // Icon + Hintergrund aus dem Feed lokal holen, sonst bleibt das mitgelieferte Motiv
            $icon_file = $central ? MGD_Catalog_V4_Media::sync( $entry['iconUrl'] ?? null, $fetch_failed ) : '';
            $image_file = $central ? MGD_Catalog_V4_Media::sync( $entry['imageUrl'] ?? null, $fetch_failed ) : '';
            $items[ $repo ] = array(
                'repository' => $repo,
                'name' => sanitize_text_field( $entry['name'] ?? $local['name'] ?? $repo ),
                'description' => sanitize_text_field( ( $entry['description'] ?? '' ) ?: ( $local['description'] ?? 'Erweiterung aus der MGD Plugin-Kollektion.' ) ),
                'category' => sanitize_text_field( $local['category'] ?? 'Weitere' ),
                'icon' => $local['icon'] ?? 'generic.svg',
                'tone' => $local['tone'] ?? 'mint',
                'features' => $local['features'] ?? array( 'Für WordPress entwickelt', 'Von Michael Gahn DESIGN' ),
                'basenames' => $local['basenames'] ?? array(),
                'tier' => $tier,
                'version' => ltrim( sanitize_text_field( $release['version'] ?? ( $central ? ( $entry['version'] ?? '' ) : '' ) ), 'vV' ),
                'download' => $release['download'] ?? '',
                'url' => 'https://github.com/' . self::OWNER . '/' . $repo,
                'details_url' => $this->details_url( $entry['detailsUrl'] ?? '' ),
                'icon_file' => $icon_file,
                'image_file' => $image_file,
                'image_alt' => $image_file ? mb_substr( sanitize_text_field( $entry['imageAlt'] ?? '' ), 0, 160 ) : '',
                'accent' => MGD_Catalog_V4_Media::accent( $central ? ( $entry['accent'] ?? '' ) : '' ),
            );
        }
        // Eine leere Zentralantwort ist ein echter Leerzustand; bei Ausfall bleibt ein letzter Stand nutzbar.
        if ( ! $central && is_array( $cached ) && ! empty( $cached['items'] ) ) {
            foreach ( $cached['items'] as $old ) { if ( ! isset( $items[ $old['repository'] ] ) ) { $items[ $old['repository'] ] = $old; } }
        }
        // nicht mehr genutzte Bilder aufräumen
        if ( $central ) { MGD_Catalog_V4_Media::prune( array_merge( array_column( $items, 'icon_file' ), array_column( $items, 'image_file' ) ) ); }
        $catalog = array( 'items' => array_values( $items ), 'source' => $central ? 'central' : 'fallback', 'checked_at' => time() );
        set_transient( self::CACHE, $catalog, 6 * HOUR_IN_SECONDS );
        return $catalog;
    }

    private function repository( $url ): string {
        if ( ! is_string( $url ) || ! preg_match( '~^https://github\.com/' . self::OWNER . '/(MGD[-_][A-Za-z0-9._-]+)/*$~D', $url, $match ) ) { return ''; }
        return $match[1];
    }

    private function details_url( $url ): string {
        return is_string( $url ) && preg_match( '~^https://theme\.michael-gahn\.de/(?:plugins|plugin/)[^\s]*$~D', $url ) ? esc_url_raw( $url ) : 'https://theme.michael-gahn.de/plugins';
    }

    private function release( string $repo, bool $refresh ): array {
        $key = 'mgd_catalog_v3_release_' . md5( $repo );
        $cached = get_transient( $key );
        if ( ! $refresh && is_array( $cached ) ) { return $cached; }
        $response = wp_remote_get( 'https://api.github.com/repos/' . self::OWNER . '/' . rawurlencode( $repo ) . '/releases/latest', array( 'timeout' => 3, 'redirection' => 0, 'limit_response_size' => 524288, 'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'MGD-Plugin-Catalog' ) ) );
        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return is_array( $cached ) ? $cached : array(); }
        $release = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $release ) || ! empty( $release['draft'] ) || ! empty( $release['prerelease'] ) ) { return array(); }
        foreach ( (array) ( $release['assets'] ?? array() ) as $asset ) {
            $url = is_array( $asset ) ? ( $asset['browser_download_url'] ?? '' ) : '';
            if ( $this->valid_download( $url, $repo ) ) {
                $item = array( 'download' => $url, 'version' => sanitize_text_field( $release['tag_name'] ?? '' ) );
                set_transient( $key, $item, 6 * HOUR_IN_SECONDS );
                return $item;
            }
        }
        return array();
    }

    public function valid_download( $url, string $repo ): bool {
        if ( ! is_string( $url ) || 0 !== strpos( $url, 'https://github.com/' . self::OWNER . '/' . $repo . '/releases/download/' ) || ! preg_match( '~^https://github\.com/[^?#\s]+\.zip$~iD', $url ) ) { return false; }
        foreach ( explode( '/', rawurldecode( wp_parse_url( $url, PHP_URL_PATH ) ) ) as $part ) {
            if ( in_array( $part, array( '.', '..' ), true ) || preg_match( '/[\x00-\x1f\x7f]/', $part ) ) { return false; }
        }
        return true;
    }

    public function decorate( array $items ): array {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugins = get_plugins();
        $updates = get_site_transient( 'update_plugins' );
        foreach ( $items as &$item ) {
            // lokale Feed-Bilder (alter Cache hat die Felder noch nicht)
            $item['icon_url'] = ! empty( $item['icon_file'] ) ? MGD_Catalog_V4_Media::url( $item['icon_file'] ) : '';
            $item['image_url'] = ! empty( $item['image_file'] ) ? MGD_Catalog_V4_Media::url( $item['image_file'] ) : '';
            $item['image_alt'] = $item['image_url'] ? (string) ( $item['image_alt'] ?? '' ) : '';
            $item['accent'] = MGD_Catalog_V4_Media::accent( $item['accent'] ?? '' );
            $item['installed'] = false;
            $item['active'] = false;
            $item['update_url'] = '';
            $item['installed_version'] = '';
            foreach ( $plugins as $file => $plugin ) {
                if ( ! in_array( $file, $item['basenames'], true ) && ! in_array( $item['url'], array( untrailingslashit( $plugin['UpdateURI'] ?? '' ), untrailingslashit( $plugin['PluginURI'] ?? '' ) ), true ) ) { continue; }
                $item['installed'] = true;
                $item['active'] = is_plugin_active( $file );
                $item['installed_version'] = $plugin['Version'];
                $offered_version = $updates->response[ $file ]->new_version ?? '';
                // WordPress kann direkt nach dem Update noch das vorige Angebot im Cache halten.
                if ( current_user_can( 'update_plugins' ) && is_string( $offered_version ) && version_compare( $offered_version, $plugin['Version'], '>' ) ) {
                    $item['update_url'] = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $file ) ), 'upgrade-plugin_' . $file );
                }
                break;
            }
            $item['state'] = $item['update_url'] ? 'update' : ( $item['installed'] ? 'installed' : 'available' );
        }
        unset( $item );
        return $items;
    }
}
