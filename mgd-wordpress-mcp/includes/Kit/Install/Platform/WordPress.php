<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Install\Platform;

use Mgd\WordPressMcp\Kit\Install\{CallbackStore, Factory, InstallClient, Settings};

// WordPress/WooCommerce-Anbindung. Option ohne Autoload, Meldung nur im Admin (admin_init), max 1x am Tag.
// Update-Prüfung des Plugins: client()->fetchUpdates() statt der festen Datei. Fehler hier dürfen den WP-Admin nie killen
final class WordPress
{
    /** null = Produkt/System ungültig (Tippfehler im Plugin), dann eben keine Meldung */
    public static function client(string $product, string $platform, string $version): ?InstallClient
    {
        try {
            $opt = 'mgd_install_' . str_replace('-', '_', $product);
            $store = new CallbackStore(
                static fn (): mixed => \get_option($opt, ''),
                static function (string $v) use ($opt): void {
                    \update_option($opt, $v, false);
                },
            );

            return Factory::create($store, $product, $platform, $version, strtolower((string) \wp_parse_url(\home_url(), PHP_URL_HOST)));
        } catch (\Throwable) {
            return null;
        }
    }

    /** Einmal im Hauptplugin aufrufen. $settingsUrl = Seite mit Settings::render (für den Hinweis nach dem Aktivieren) */
    public static function boot(string $pluginFile, string $product, string $platform, string $version, string $settingsUrl): void
    {
        $flag = 'mgd_install_notice_' . str_replace('-', '_', $product);
        \register_activation_hook($pluginFile, static function () use ($flag): void {
            \set_transient($flag, 1, 86400);
        });
        \add_action('admin_init', static function () use ($product, $platform, $version): void {
            if (!\current_user_can('manage_options') || \wp_doing_ajax()) {
                return;
            }
            $c = self::client($product, $platform, $version);
            if ($c === null) {
                return;
            }
            try {
                // nur unser eigenes Formular, Nonce still prüfen (kein wp_die)
                if (($_POST[Settings::actionName($c)] ?? '') === '1' && \wp_verify_nonce((string) ($_POST['_mgd_install_nonce'] ?? ''), 'mgd_install_' . $product)) {
                    Settings::handle($c, \wp_unslash($_POST));
                }
                $c->ping();
            } catch (\Throwable) {
            }
        });
        \add_action('admin_notices', static function () use ($flag, $settingsUrl): void {
            if (!\current_user_can('manage_options') || !\get_transient($flag)) {
                return;
            }
            \delete_transient($flag);
            echo '<div class="notice notice-info is-dismissible"><p>' . \esc_html(Settings::HINT)
                . ' <a href="' . \esc_url($settingsUrl) . '">' . \esc_html(Settings::LABEL) . '</a></p></div>';
        });
    }

    public static function settingsHtml(string $product, string $platform, string $version): string
    {
        $c = self::client($product, $platform, $version);

        return $c === null ? '' : Settings::render($c, \wp_nonce_field('mgd_install_' . $product, '_mgd_install_nonce', true, false));
    }
}
