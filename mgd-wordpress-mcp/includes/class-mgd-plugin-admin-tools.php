<?php
defined('ABSPATH') || exit;

// Jede Erweiterung liefert dieselbe eigenständige Komponente mit. Gemeinsam nur einmal laden.
if (!class_exists('MGD_Plugin_Admin_Tools_20261023')) {
    final class MGD_Plugin_Admin_Tools_20261023 {
        private static array $plugins = [];
        public static function register(array $plugin): void {
            if (!self::$plugins) {
                add_action('admin_notices', [self::class, 'notice']);
                add_action('admin_post_mgd_check_plugin_updates', [self::class, 'check']);
                add_filter('plugin_row_meta', [self::class, 'links'], 99, 2);
                add_filter('plugins_api', [self::class, 'information'], 99, 3);
                add_action('admin_enqueue_scripts', [self::class, 'styles']);
                add_filter('update_plugins_github.com', [self::class, 'offer'], 30, 3);
            }
            self::$plugins[plugin_basename($plugin['file'])] = $plugin;
        }
        public static function notice(): void {
            global $pagenow;
            if (!in_array($pagenow, ['plugins.php', 'update-core.php'], true) || !current_user_can('update_plugins')) { return; }
            echo '<div class="notice notice-info"><p><strong>MGD Plugin-Updates</strong></p><p>Gespeicherte GitHub-Quellen erneut prüfen. Es wird kein Update installiert. GitHub erhält die Server-IP; abgeschaltete Prüfungen bleiben abgeschaltet.</p>';
            if (isset($_GET['mgd_checked'])) { echo '<p role="status">Prüfung ausgeführt. Verfügbare Updates stehen in der WordPress-Liste. Ein fehlendes Angebot kann auch eine deaktivierte oder nicht erreichbare Quelle bedeuten.</p>'; }
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('mgd_check_plugin_updates');
            echo '<input type="hidden" name="action" value="mgd_check_plugin_updates"><p><button class="button">MGD-Updates jetzt prüfen</button></p></form></div>';
        }
        public static function check(): void {
            if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '') || !current_user_can('update_plugins')) { wp_die('Keine Berechtigung.', '', ['response' => 403]); }
            check_admin_referer('mgd_check_plugin_updates');
            // Nur die benannten Release-Caches löschen; Einstellungen werden nicht verändert.
            foreach (self::$plugins as $plugin) {
                foreach ($plugin['caches'] as $key) { delete_site_transient($key); }
                if ($plugin['slug'] === 'mg-maintenance-studio') { delete_transient('mgms_release'); }
            }
            // Prüfdaten behalten: Einige Updater benötigen checked. Der Zeitstempel erzwingt die neue Prüfung.
            $state = get_site_transient('update_plugins');
            if (is_object($state)) { $state->last_checked = 0; set_site_transient('update_plugins', $state); }
            wp_update_plugins();
            wp_safe_redirect(admin_url('update-core.php?mgd_checked=1'));
            exit;
        }
        public static function links(array $links, string $file): array {
            if (!isset(self::$plugins[$file])) { return $links; }
            $p = self::$plugins[$file];
            $targets = [
                'Einstellungen' => admin_url($p['settings']),
                'Details anzeigen' => admin_url('plugin-install.php?tab=plugin-information&plugin=' . $p['slug'] . '&TB_iframe=true&width=772&height=680'),
                'Dokumentation' => 'https://github.com/MichaelGahnDESIGN/' . $p['repo'] . '/tree/main/docs',
                'Support' => 'https://github.com/MichaelGahnDESIGN/' . $p['repo'] . '/issues',
                'GitHub' => 'https://github.com/MichaelGahnDESIGN/' . $p['repo'],
            ];
            foreach ($targets as $label => $url) {
                $found = false;
                foreach ($links as $link) { if (strip_tags($link) === $label) { $found = true; } }
                if (!$found) { $links[] = '<a ' . ($label === 'Details anzeigen' ? 'class="thickbox open-plugin-details-modal" ' : '') . 'href="' . esc_url($url) . '">' . esc_html($label) . '</a>'; }
            }
            return $links;
        }
        public static function information($result, $action, $args) {
            if ($action !== 'plugin_information') { return $result; }
            foreach (self::$plugins as $p) {
                if (($args->slug ?? '') !== $p['slug']) { continue; }
                $info = is_object($result) && !is_wp_error($result) ? $result : (object) [];
                $info->name = $p['name']; $info->slug = $p['slug'];
                $info->version = $info->version ?? $p['version'];
                $info->author = '<a href="https://michael-gahn.de">Michael Gahn DESIGN</a>';
                $info->homepage = 'https://github.com/MichaelGahnDESIGN/' . $p['repo'];
                $info->requires = $info->requires ?? $p['requires'];
                $info->requires_php = $info->requires_php ?? $p['php'];
                $info->external = true;
                $info->banners = $info->banners ?? ['low' => plugins_url('assets/mgd-details-banner.svg', $p['file'])];
                $info->icons = $info->icons ?? ['svg' => plugins_url('assets/mgd-plugin-icon.svg', $p['file'])];
                $info->sections = ($info->sections ?? []) + [
                    'description' => '<h2>' . esc_html($p['name']) . '</h2><p>' . esc_html($p['description']) . '</p><h3>Dokumentation und Hilfe</h3><p><a href="' . esc_url($info->homepage . '/blob/main/README.md') . '">Anleitung öffnen</a> · <a href="' . esc_url($info->homepage . '/issues') . '">Support</a></p>',
                    'installation' => '<p>Das passende ZIP aus dem GitHub-Release unter Plugins → Installieren → Plugin hochladen auswählen. Bestehende Einstellungen vor dem Update sichern.</p>',
                    'faq' => '<h3>Wie prüfe ich Updates?</h3><p>Unter Dashboard → Aktualisierungen den Button „MGD-Updates jetzt prüfen“ verwenden. Er installiert nichts und ändert keine Einstellungen.</p>',
                    'changelog' => '<h3>' . esc_html($p['version']) . '</h3><p>Separate Update-Prüfung, Plugin-Links, Icon und ergänzte Detailansicht.</p>',
                ];
                return $info;
            }
            return $result;
        }
        public static function styles(string $hook): void {
            if ($hook !== 'plugins.php') { return; }
            foreach (self::$plugins as $p) {
                wp_enqueue_style('mgd-plugin-card-' . $p['slug'], plugins_url('assets/mgd-plugin-card.css', $p['file']), [], $p['version']);
            }
        }
        // Nur für Plugins ohne bisherigen Updater. Quelle und Paketname bleiben fest im Code.
        public static function offer($update, array $data, string $file) {
            $p = self::$plugins[$file] ?? null;
            if (!$p || empty($p['package'])) { return $update; }
            $key = 'mgd_admin_release_' . $p['slug'];
            $release = get_site_transient($key);
            if ($release === false) {
                $response = wp_safe_remote_get('https://api.github.com/repos/MichaelGahnDESIGN/' . $p['repo'] . '/releases/latest', ['timeout' => 8, 'redirection' => 0, 'limit_response_size' => 262144, 'headers' => ['Accept' => 'application/vnd.github+json']]);
                $release = [];
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    if (is_array($body) && empty($body['draft']) && empty($body['prerelease']) && preg_match('/^v?((?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*))$/D', $body['tag_name'] ?? '', $m)) {
                        $name = str_replace('{version}', $m[1], $p['package']);
                        $url = 'https://github.com/MichaelGahnDESIGN/' . $p['repo'] . '/releases/download/' . $body['tag_name'] . '/' . $name;
                        foreach (($body['assets'] ?? []) as $asset) {
                            if (($asset['name'] ?? '') === $name && ($asset['browser_download_url'] ?? '') === $url) { $release = ['version' => $m[1], 'package' => $url, 'slug' => $p['slug']]; }
                        }
                    }
                }
                set_site_transient($key, $release, $release ? 6 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS);
            }
            return $release ?: false;
        }
    }
}
