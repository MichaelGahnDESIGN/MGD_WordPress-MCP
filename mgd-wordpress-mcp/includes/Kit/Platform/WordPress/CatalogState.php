<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

// Status pro Karte: installiert / aktiv / Update verfügbar. Immer frisch aus WP, nie gecacht
final class CatalogState
{
    /**
     * @param list<array<string,mixed>> $items bereinigte Einträge aus Recommendations
     * @param array<string,mixed> $plugins wie get_plugins() (Datei => Header)
     * @param list<string> $active aktive Plugin-Dateien
     * @param mixed $updates site_transient update_plugins
     * @return list<array<string,mixed>> + installed, active, installedVersion, updateFile, state
     */
    public static function apply(array $items, array $plugins, array $active, mixed $updates, bool $canUpdate): array
    {
        $offers = is_object($updates) && is_array($updates->response ?? null) ? $updates->response : [];
        $out = [];
        foreach ($items as $it) {
            $it += ['installed' => false, 'active' => false, 'installedVersion' => '', 'updateFile' => ''];
            $file = self::match($it, $plugins);
            if ($file !== '') {
                $ver = is_array($plugins[$file]) && is_string($plugins[$file]['Version'] ?? null) ? $plugins[$file]['Version'] : '';
                $offer = is_object($offers[$file] ?? null) ? ($offers[$file]->new_version ?? '') : '';
                $it['installed'] = true;
                $it['active'] = in_array($file, $active, true);
                $it['installedVersion'] = $ver;
                // WP hält direkt nach dem Update evtl noch das alte Angebot > Version vergleichen
                if ($canUpdate && is_string($offer) && $offer !== '' && version_compare($offer, $ver, '>')) {
                    $it['updateFile'] = $file;
                }
            }
            $it['state'] = $it['updateFile'] !== '' ? 'update' : ($it['installed'] ? 'installed' : 'available');
            $out[] = $it;
        }

        return $out;
    }

    /** @param list<array<string,mixed>> $items */
    public static function fromWp(array $items): array
    {
        try {
            $plugins = self::wpPlugins();
            $active = [];
            foreach (array_keys($plugins) as $f) {
                if (function_exists('is_plugin_active') && \is_plugin_active((string) $f)) {
                    $active[] = (string) $f;
                }
            }
            $updates = function_exists('get_site_transient') ? \get_site_transient('update_plugins') : null;

            return self::apply($items, $plugins, $active, $updates, \current_user_can('update_plugins'));
        } catch (\Throwable) {
            return self::apply($items, [], [], null, false);
        }
    }

    /** get_plugins() mit Nachladen, ohne WP leer @return array<string,mixed> */
    public static function wpPlugins(): array
    {
        if (!function_exists('get_plugins') && defined('ABSPATH') && is_file(ABSPATH . 'wp-admin/includes/plugin.php')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return function_exists('get_plugins') ? (array) \get_plugins() : [];
    }

    // erst über basenames aus dem Katalog, sonst Plugin-URI = Detailseite
    private static function match(array $it, array $plugins): string
    {
        foreach ((array) ($it['basenames'] ?? []) as $b) {
            if (is_string($b) && isset($plugins[$b])) {
                return $b;
            }
        }
        $url = is_string($it['url'] ?? null) ? $it['url'] : '';
        foreach ($plugins as $file => $h) {
            if ($url !== '' && is_array($h) && in_array($url, [rtrim((string) ($h['UpdateURI'] ?? ''), '/'), rtrim((string) ($h['PluginURI'] ?? ''), '/')], true)) {
                return (string) $file;
            }
        }

        return '';
    }
}
