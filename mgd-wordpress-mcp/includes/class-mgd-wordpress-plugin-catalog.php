<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MGD_WordPress_Plugin_Catalog_V4' ) ) {
    require_once __DIR__ . '/plugin-catalog/class-provider.php';

    /** Gemeinsame Oberfläche, unabhängig von der Ladereihenfolge älterer MGD-Plugins. */
    final class MGD_WordPress_Plugin_Catalog_V4 {
        private static array $parents = array();
        private static bool $registered = false;

        public function register( string $parent_slug ): void {
            self::$parents[ $parent_slug ] = true;
            if ( self::$registered ) { return; }
            self::$registered = true;
            add_action( 'admin_menu', array( $this, 'menus' ), 200 );
            add_action( 'admin_menu', array( $this, 'legacy_pages' ), 9999 );
            add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
            add_action( 'admin_post_mgd_catalog_v3_install', array( $this, 'install' ) );
            add_action( 'admin_post_mgd_catalog_v3_refresh', array( $this, 'refresh' ) );
        }

        public function menus(): void {
            foreach ( array_keys( self::$parents ) as $parent ) {
                add_submenu_page( $parent, 'MGD Plugin-Kollektion', 'Weitere Plugins', 'install_plugins', 'mgd-wordpress-plugin-catalog', array( $this, 'page' ) );
            }
        }

        /** Alte Katalogseiten erhalten dieselbe Ansicht; fremde Hooks bleiben unangetastet. */
        public function legacy_pages(): void {
            global $wp_filter;
            // Ein aktualisiertes Plugin übernimmt auch dann, wenn ältere MGD-Plugins zuerst laden.
            foreach ( ( $wp_filter['admin_enqueue_scripts']->callbacks ?? array() ) as $priority => $callbacks ) {
                foreach ( $callbacks as $entry ) {
                    $callback = $entry['function'];
                    if ( is_array( $callback ) && is_object( $callback[0] ) && in_array( get_class( $callback[0] ), array( 'MGD_WordPress_Plugin_Catalog', 'MGD_WordPress_Plugin_Catalog_V3' ), true ) && 'assets' === $callback[1] ) { remove_action( 'admin_enqueue_scripts', $callback, $priority ); }
                }
            }
            foreach ( $wp_filter as $hook => $filter ) {
                if ( false === strpos( $hook, '_page_mgd-wordpress-plugin-catalog' ) ) { continue; }
                foreach ( $filter->callbacks as $priority => $callbacks ) {
                    foreach ( $callbacks as $entry ) {
                        $callback = $entry['function'];
                        if ( is_array( $callback ) && is_object( $callback[0] ) && in_array( get_class( $callback[0] ), array( 'MGD_WordPress_Plugin_Catalog', 'MGD_WordPress_Plugin_Catalog_V3' ), true ) && 'page' === $callback[1] ) {
                            remove_action( $hook, $callback, $priority );
                            add_action( $hook, array( $this, 'page' ) );
                        }
                    }
                }
            }
        }

        public function assets(): void {
            // Nur die eigene, lesende Verwaltungsseite lädt diese Dateien.
            if ( 'mgd-wordpress-plugin-catalog' !== sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ) || ! current_user_can( 'install_plugins' ) ) { return; }
            $base = plugins_url( 'assets/plugin-catalog/', dirname( __FILE__ ) );
            wp_enqueue_style( 'mgd-catalog-v3', $base . 'catalog.css', array(), '4.0.0' );
            wp_enqueue_script( 'mgd-catalog-v3', $base . 'catalog.js', array(), '4.0.0', true );
        }

        public function page(): void {
            if ( ! current_user_can( 'install_plugins' ) ) { wp_die( 'Nicht berechtigt.', '', array( 'response' => 403 ) ); }
            $provider = new MGD_Catalog_V4_Provider();
            $catalog = $provider->catalog();
            $items = $provider->decorate( $catalog['items'] );
            $asset_url = plugins_url( 'assets/plugin-catalog/', dirname( __FILE__ ) );
            $notice = sanitize_key( wp_unslash( $_GET['mgd_catalog_result'] ?? '' ) );
            require __DIR__ . '/plugin-catalog/view.php';
        }

        public function refresh(): void {
            $this->guard( 'mgd_catalog_v3_refresh' );
            ( new MGD_Catalog_V4_Provider() )->catalog( true );
            $this->redirect( 'refreshed' );
        }

        public function install(): void {
            $repository = isset( $_POST['repository'] ) && is_string( $_POST['repository'] ) ? sanitize_text_field( wp_unslash( $_POST['repository'] ) ) : '';
            $this->guard( 'mgd_catalog_v3_install_' . $repository );
            $provider = new MGD_Catalog_V4_Provider();
            foreach ( $provider->decorate( $provider->catalog()['items'] ) as $item ) {
                if ( $repository !== $item['repository'] || $item['installed'] || 'free' !== $item['tier'] || ! $provider->valid_download( $item['download'], $repository ) ) { continue; }
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                $result = ( new Plugin_Upgrader( new Automatic_Upgrader_Skin() ) )->install( $item['download'] );
                $this->redirect( true === $result ? 'installed' : 'failed' );
            }
            $this->redirect( 'unavailable' );
        }

        private function guard( string $nonce ): void {
            if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'install_plugins' ) ) { wp_die( 'Nicht berechtigt.', '', array( 'response' => 403 ) ); }
            check_admin_referer( $nonce );
        }

        private function redirect( string $result ): void {
            wp_safe_redirect( add_query_arg( array( 'page' => 'mgd-wordpress-plugin-catalog', 'mgd_catalog_result' => $result ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }
}
