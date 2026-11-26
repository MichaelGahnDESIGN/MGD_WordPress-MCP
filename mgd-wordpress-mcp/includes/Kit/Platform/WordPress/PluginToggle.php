<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Support\Text;

// MGD-Plugins in MGD Tools an/aus. Nur activate_plugins, Nonce je Datei, nur MGD-Plugins (nie fremde)
final class PluginToggle
{
    public const ACTION = 'mgd_tools_toggle';
    public const QUERY = 'mgd_tools_toggled';
    public const FILE_RE = '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+\.php$#D';
    public const BACK = [Tools::PARENT, Tools::PAGE_CATALOG];

    public static function nonceAction(string $file): string
    {
        return self::ACTION . '_' . $file;
    }

    public static function can(): bool
    {
        return function_exists('current_user_can') && \current_user_can('activate_plugins');
    }

    /** Knopf als Mini-Formular. '' = kein Recht, kaputte Datei oder Netzwerk-aktiv */
    public static function form(string $file, string $name, bool $active, string $back, ?\Closure $nonce): string
    {
        if ($nonce === null || !self::can() || preg_match(self::FILE_RE, $file) !== 1 || str_contains($file, '..') || self::network($file)) {
            return '';
        }
        $back = in_array($back, self::BACK, true) ? $back : Tools::PARENT;
        $label = $active ? ToolsTexts::DEACTIVATE : ToolsTexts::ACTIVATE;

        return '<form method="post" action="admin-post.php" class="mgd-tools-toggle"><input type="hidden" name="action" value="' . self::ACTION . '">'
            . '<input type="hidden" name="plugin" value="' . Text::e($file) . '"><input type="hidden" name="do" value="' . ($active ? 'off' : 'on') . '">'
            . '<input type="hidden" name="back" value="' . Text::e($back) . '"><input type="hidden" name="_wpnonce" value="' . Text::e((string) $nonce(self::nonceAction($file))) . '">'
            . '<button type="submit" class="button' . ($active ? '' : ' button-primary') . '" aria-label="' . Text::e($name . ' ' . mb_strtolower($label)) . '">' . Text::e($label) . '</button></form>';
    }

    /**
     * nur Dateien, die wir als MGD-Plugin kennen und die installiert sind
     * @param list<string> $known Basenames aus MGD Tools + Katalog
     * @param array<string,mixed> $installed get_plugins()
     */
    public static function allowed(string $file, array $known, array $installed): bool
    {
        return preg_match(self::FILE_RE, $file) === 1 && !str_contains($file, '..') && in_array($file, $known, true) && isset($installed[$file]);
    }

    /** Meldung nach dem Zurückleiten, Name kommt aus get_plugins(), nie aus der Adresse */
    public static function message(array $get, array $installed): string
    {
        $code = is_string($get[self::QUERY] ?? null) ? $get[self::QUERY] : '';
        $file = is_string($get['plugin'] ?? null) ? $get['plugin'] : '';
        $name = is_array($installed[$file] ?? null) && is_string($installed[$file]['Name'] ?? null) ? Text::plain($installed[$file]['Name'], 80) : '';
        if ($name === '') {
            return $code === 'retry' ? ToolsTexts::RETRY : '';
        }

        return match ($code) {
            'on' => sprintf(ToolsTexts::ACTIVATED, $name),
            'off' => sprintf(ToolsTexts::DEACTIVATED, $name),
            'fail' => sprintf(ToolsTexts::ACTIVATE_FAIL, $name),
            'retry' => ToolsTexts::RETRY,
            default => '',
        };
    }

    // admin-post Handler, nur beim Besitzer von MGD Tools registriert
    public static function handle(string $owner): void
    {
        $post = function_exists('wp_unslash') ? \wp_unslash($_POST) : $_POST;
        $post = is_array($post) ? $post : [];
        $file = is_string($post['plugin'] ?? null) ? $post['plugin'] : '';
        $do = $post['do'] ?? '';
        $back = in_array($post['back'] ?? '', self::BACK, true) ? (string) $post['back'] : Tools::PARENT;
        $code = 'retry';
        try {
            $installed = CatalogState::wpPlugins();
            $ok = self::can() && in_array($do, ['on', 'off'], true)
                && \wp_verify_nonce((string) ($post['_wpnonce'] ?? ''), self::nonceAction($file)) !== false
                && self::allowed($file, self::known($owner), $installed) && !self::network($file);
            // andere aktive Plugins brauchen das hier (Requires Plugins, WP 6.5) > nicht abschalten
            if ($ok && $do === 'off' && class_exists('WP_Plugin_Dependencies') && \WP_Plugin_Dependencies::has_active_dependents($file)) {
                $code = 'fail';
            } elseif ($ok && $do === 'off') {
                \deactivate_plugins([$file]);
                $code = 'off';
                // letztes MGD-Plugin aus > MGD Tools gibts nicht mehr
                $left = array_filter(Tools::entries(), static fn (array $e): bool => $e['basename'] !== $file);
                if ($left === []) {
                    self::go('plugins.php');
                }
            } elseif ($ok) {
                \activate_plugin($file);
                // Ausgabe beim Aktivieren gibt WP_Error, Plugin ist trotzdem an > Zustand zählt
                $code = \is_plugin_active($file) ? 'on' : 'fail';
            }
        } catch (\Throwable) {
            $code = 'retry';
        }
        self::go('admin.php?' . http_build_query(['page' => $back, self::QUERY => $code, 'plugin' => $file]));
    }

    /** @return list<string> Basenames: aktive aus MGD Tools + installierte aus dem Katalog */
    public static function known(string $owner): array
    {
        $out = array_values(array_map(static fn (array $e): string => (string) $e['basename'], Tools::entries()));
        $rec = Tools::local($owner);
        if ($rec !== null) {
            foreach (CatalogState::fromWp($rec->load()['items']) as $it) {
                if (is_string($it['file'] ?? null) && $it['file'] !== '') {
                    // Katalog nicht signiert > nur Dateien mit MGD-Kopf, nie fremde Plugins
                    if (self::mgdHeader(CatalogState::wpPlugins()[$it['file']] ?? null)) {
                        $out[] = $it['file'];
                    }
                }
            }
        }

        return array_values(array_unique($out));
    }

    /** Plugin-Kopf gehört zu MGD: Update-/Plugin-URI auf theme.michael-gahn.de oder Autor MGD */
    public static function mgdHeader(mixed $h): bool
    {
        if (!is_array($h)) {
            return false;
        }
        foreach (['UpdateURI', 'PluginURI', 'AuthorURI'] as $k) {
            if (is_string($h[$k] ?? null) && str_starts_with($h[$k], 'https://theme.michael-gahn.de/')) {
                return true;
            }
        }

        return is_string($h['Author'] ?? null) && str_contains(strip_tags($h['Author']), 'Michael Gahn DESIGN');
    }

    private static function network(string $file): bool
    {
        if (!function_exists('is_multisite') || !\is_multisite()) {
            return false;
        }

        // netzwerkweit aktiv oder nur fürs Netzwerk gedacht (Network: true)
        return (function_exists('is_plugin_active_for_network') && \is_plugin_active_for_network($file))
            || (function_exists('is_network_only_plugin') && \is_network_only_plugin($file));
    }

    private static function go(string $to): never
    {
        \wp_safe_redirect(\admin_url($to));
        exit;
    }
}
