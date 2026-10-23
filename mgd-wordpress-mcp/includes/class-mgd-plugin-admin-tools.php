<?php
/** Gemeinsame Verwaltung für die WordPress.org-Ausgaben. */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MGD_WordPress_Org_Admin_Tools' ) ) {
    final class MGD_WordPress_Org_Admin_Tools {
        private static array $plugins = array();

        public static function register( array $plugin ): void {
            if ( ! self::$plugins ) {
                add_action( 'admin_notices', array( self::class, 'notice' ) );
                add_action( 'admin_post_mgd_org_check_updates', array( self::class, 'check' ) );
                add_filter( 'plugin_row_meta', array( self::class, 'links' ), 99, 2 );
                add_action( 'admin_enqueue_scripts', array( self::class, 'styles' ) );
            }
            self::$plugins[ plugin_basename( $plugin['file'] ) ] = $plugin;
        }

        public static function notice(): void {
            global $pagenow;
            if ( ! in_array( $pagenow, array( 'plugins.php', 'update-core.php' ), true ) || ! current_user_can( 'update_plugins' ) ) {
                return;
            }
            echo '<div class="notice notice-info is-dismissible"><p>WordPress.org-Updates erneut prüfen. Es wird kein Update installiert.</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            wp_nonce_field( 'mgd_org_check_updates' );
            echo '<input type="hidden" name="action" value="mgd_org_check_updates"><p><button class="button">Updates jetzt prüfen</button></p></form></div>';
        }

        public static function check(): void {
            if ( 'POST' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || ! current_user_can( 'update_plugins' ) ) {
                wp_die( esc_html__( 'Keine Berechtigung.', 'mgd-mcp-bridge' ), '', array( 'response' => 403 ) );
            }
            check_admin_referer( 'mgd_org_check_updates' );
            $state = get_site_transient( 'update_plugins' );
            if ( is_object( $state ) ) {
                $state->last_checked = 0;
                set_site_transient( 'update_plugins', $state );
            }
            // WordPress selbst bestimmt Quelle, Berechtigungen und verfügbare Versionen.
            wp_update_plugins();
            wp_safe_redirect( admin_url( 'update-core.php' ) );
            exit;
        }

        public static function links( array $links, string $file ): array {
            if ( ! isset( self::$plugins[ $file ] ) ) {
                return $links;
            }
            $p = self::$plugins[ $file ];
            $targets = array(
                'Einstellungen' => admin_url( $p['settings'] ),
                'Dokumentation' => 'https://github.com/MichaelGahnDESIGN/' . $p['repo'] . '/blob/codex/wordpress-org-preparation/docs/WORDPRESS-ORG.md',
                'Support' => 'https://github.com/MichaelGahnDESIGN/' . $p['repo'] . '/issues',
            );
            foreach ( $targets as $label => $url ) {
                $found = false;
                foreach ( $links as $link ) {
                    if ( wp_strip_all_tags( $link ) === $label ) { $found = true; }
                }
                if ( ! $found ) { $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>'; }
            }
            return $links;
        }

        public static function styles( string $hook ): void {
            if ( 'plugins.php' !== $hook ) { return; }
            foreach ( self::$plugins as $p ) {
                wp_enqueue_style( 'mgd-org-card-' . $p['slug'], plugins_url( 'assets/mgd-plugin-card.css', $p['file'] ), array(), $p['version'] );
            }
        }
    }
}
