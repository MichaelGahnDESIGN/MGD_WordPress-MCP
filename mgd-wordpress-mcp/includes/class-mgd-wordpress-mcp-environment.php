<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Erkennt den verwendeten Builder und typische Frontend-/Maintenance-Sperren.
 *
 * Wichtig: Das Plugin speichert niemals PINs oder Passwörter. Die Erkennung
 * liefert einem Agenten nur den Hinweis, dass ein Frontend-Test blockiert sein
 * kann und dass er den Nutzer nach dem legitimen Entsperrweg fragen soll.
 */
final class MGD_WordPress_MCP_Environment {
    public static function builder() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $theme = wp_get_theme();
        $template = (string) $theme->get_template();
        $stylesheet = (string) $theme->get_stylesheet();

        if ( 'Divi' === $template || 'Divi' === $stylesheet || defined( 'ET_BUILDER_VERSION' ) || is_plugin_active( 'divi-builder/divi-builder.php' ) ) {
            return array(
                'type'       => 'divi',
                'label'      => 'Divi 5 / Divi Builder',
                'detected'   => true,
                'skill_repo' => 'https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL',
            );
        }

        if ( defined( 'ELEMENTOR_VERSION' ) || is_plugin_active( 'elementor/elementor.php' ) ) {
            return array( 'type' => 'elementor', 'label' => 'Elementor', 'detected' => true );
        }

        if ( defined( 'BRICKS_VERSION' ) || is_plugin_active( 'bricks/bricks.php' ) || 'bricks' === strtolower( $template ) ) {
            return array( 'type' => 'bricks', 'label' => 'Bricks', 'detected' => true );
        }

        if ( defined( 'FL_BUILDER_VERSION' ) || is_plugin_active( 'bb-plugin/fl-builder.php' ) ) {
            return array( 'type' => 'beaver-builder', 'label' => 'Beaver Builder', 'detected' => true );
        }

        return array( 'type' => 'gutenberg', 'label' => 'WordPress Block Editor / Gutenberg', 'detected' => true );
    }

    public static function frontend_locks() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $plugins = get_plugins();
        $active = (array) get_option( 'active_plugins', array() );
        if ( is_multisite() ) {
            $active = array_unique( array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) ) );
        }

        $locks = array();
        foreach ( $active as $file ) {
            if ( empty( $plugins[ $file ] ) ) {
                continue;
            }

            $name = (string) $plugins[ $file ]['Name'];
            $haystack = strtolower( $file . ' ' . $name );
            $kind = '';

            if ( false !== strpos( $haystack, 'shield' ) ) {
                $kind = 'security-or-access-shield';
            } elseif ( false !== strpos( $haystack, 'password protected' ) || false !== strpos( $haystack, 'password-protected' ) ) {
                $kind = 'password-protection';
            } elseif ( false !== strpos( $haystack, 'maintenance' ) || false !== strpos( $haystack, 'coming soon' ) || false !== strpos( $haystack, 'coming-soon' ) ) {
                $kind = 'maintenance-or-coming-soon';
            } elseif ( false !== strpos( $haystack, 'restricted site access' ) || false !== strpos( $haystack, 'restricted-site-access' ) ) {
                $kind = 'restricted-access';
            }

            if ( '' !== $kind ) {
                $locks[] = array(
                    'plugin'       => $file,
                    'name'         => $name,
                    'type'         => $kind,
                    'may_block_ui' => true,
                );
            }
        }

        $blog_public = (int) get_option( 'blog_public', 1 );

        return array(
            'potentially_locked' => ! empty( $locks ),
            'detected_plugins'   => $locks,
            'search_visibility'  => $blog_public ? 'public' : 'discourage_search_engines',
            'agent_instruction'  => ! empty( $locks )
                ? __( 'Ein Frontend-Schutz wurde erkannt. Wenn eine visuelle Prüfung oder Browser-Automation blockiert wird, frage den Nutzer nach dem legitimen Entsperrweg oder einer temporären Freigabe. Frage nur bei Bedarf nach einer PIN. Speichere oder protokolliere keine PINs oder Passwörter.', 'mgd-wordpress-mcp' )
                : __( 'Kein typischer Frontend-Schutz wurde anhand aktiver Plugins erkannt. Eine externe WAF, Basic-Auth oder Hosting-Sperre kann trotzdem existieren.', 'mgd-wordpress-mcp' ),
        );
    }

    public static function summary() {
        return array(
            'builder'        => self::builder(),
            'frontend_locks' => self::frontend_locks(),
        );
    }
}
