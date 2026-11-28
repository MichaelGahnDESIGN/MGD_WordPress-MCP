<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\License\LicenseClient;

// WP-Kleber für die Seiten von MGD Tools (nur der Besitzer ruft das auf). Fehler > kurze Meldung, nie den Admin killen
final class ToolsPages
{
    // Menü-Symbol: eigenes SVG, lokal (kein externes Bild)
    public const ICON = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCI+PHBhdGggZmlsbD0iYmxhY2siIGQ9Ik0yIDE2VjRoMi42TDEwIDExbDUuNC03SDE4djEyaC0yLjVWOC4zTDEwIDE1LjQgNC41IDguM1YxNnoiLz48L3N2Zz4=';
    public const POSITION = 81;
    public const LEGACY_RE = '/^MGD_(?:[A-Za-z0-9]+_)*Plugin_Admin_Tools(?:_\d+)?$/D';

    public static function menu(string $owner): void
    {
        \add_menu_page(ToolsTexts::MENU, ToolsTexts::MENU, 'manage_options', Tools::PARENT, [self::class, 'overview'], self::ICON, self::POSITION);
        \add_submenu_page(Tools::PARENT, ToolsTexts::OVERVIEW, ToolsTexts::OVERVIEW, 'manage_options', Tools::PARENT, [self::class, 'overview']);
    }

    public static function menuTail(string $owner): void
    {
        // immer da: ohne Pro-Plugin freundlicher Satz statt "nicht berechtigt" (Befund share)
        \add_submenu_page(Tools::PARENT, ToolsTexts::LICENSE, ToolsTexts::LICENSE, 'manage_options', Tools::PAGE_LICENSE, static fn () => self::license($owner));
        \add_submenu_page(Tools::PARENT, ToolsTexts::SETTINGS_PAGE, ToolsTexts::SETTINGS_PAGE, 'manage_options', Tools::PAGE_SETTINGS, [ToolsSettings::class, 'page']);
        if (Tools::local($owner) !== null) {
            \add_submenu_page(Tools::PARENT, ToolsTexts::CATALOG, ToolsTexts::CATALOG, 'manage_options', Tools::PAGE_CATALOG, static fn () => self::catalog($owner));
        }
    }

    public static function overview(): void
    {
        try {
            if (!\current_user_can('manage_options')) {
                return;
            }
            $msg = PluginToggle::message($_GET, CatalogState::wpPlugins());
            $fresh = false;
            $lic = self::central(Tools::owner());
            if (($_POST['mgd_tools_do'] ?? '') === 'check') {
                if (\wp_verify_nonce((string) ($_POST[Tools::NONCE] ?? ''), Tools::CHECK_ACTION) === false) {
                    $msg = ToolsTexts::RETRY;
                } else {
                    $fresh = true;
                    \delete_site_transient('update_plugins');
                    // Lizenz gleich mit neu fragen
                    $lic->refresh();
                    $msg = ToolsTexts::CHECKED;
                }
            }
            $installs = [];
            foreach (Tools::entries() as $e) {
                $h = self::call($e['install']);
                if ($h !== '') {
                    $installs[(string) $e['name']] = $h;
                }
            }
            $nonce = static fn (string $a): string => (string) \wp_create_nonce($a);
            $rows = [...self::rows($fresh, $lic, $nonce), ...self::inactiveRows(Tools::owner(), $nonce)];
            $at = $lic->enabled() ? $lic->checkedAt() : 0;
            echo ToolsView::overview($rows, (string) \wp_nonce_field(Tools::CHECK_ACTION, Tools::NONCE, true, false), $msg, $installs, $at > 0 ? (string) \wp_date('d.m.Y H:i', $at) : '');
        } catch (\Throwable) {
            echo '<div class="wrap"><p>' . ToolsTexts::ERROR . '</p></div>';
        }
    }

    /** @return list<array<string,mixed>> */
    public static function rows(bool $fresh = false, ?LicenseClient $lic = null, ?\Closure $nonce = null): array
    {
        $out = [];
        $state = $lic?->state();
        $end = $lic?->periodEnd() ?? 0;
        foreach (Tools::entries() as $e) {
            $st = [];
            try {
                $st = $e['status'] instanceof \Closure ? (array) ($e['status'])($fresh) : [];
            } catch (\Throwable) {
            }
            $s = $e['settings'];
            $out[] = [
                'name' => $e['name'],
                'icon' => $e['icon'],
                'installed' => $e['version'],
                'latest' => is_string($st['latest'] ?? null) ? $st['latest'] : '',
                'newer' => ($st['newer'] ?? false) === true,
                'state' => self::state($e['status'] !== null, $st),
                'ok' => (int) ($st['ok'] ?? 0),
                'fail' => (int) ($st['fail'] ?? 0),
                'notice' => is_string($st['notice'] ?? null) ? $st['notice'] : '',
                'settings' => is_array($s) ? Tools::url($s['page']) : '',
                'details' => $e['slug'] !== '' ? Tools::DETAILS_URL . $e['slug'] : '',
                // Lizenz je Plugin: Pro-Berechtigungen meldet das Plugin selbst
                'license' => $state !== null ? LicenseBadge::of(array_keys($e['pro']), $state, $end) : null,
                // in der Übersicht nur aktive Plugins > nur Deaktivieren
                'toggle' => PluginToggle::form((string) $e['basename'], (string) $e['name'], true, Tools::PARENT, $nonce),
            ];
        }

        return $out;
    }

    /**
     * installierte, aber inaktive MGD-Plugins (melden sich nicht selbst) > aus Katalog + get_plugins (Befund lfk)
     * @return list<array<string,mixed>>
     */
    public static function inactiveRows(string $owner, ?\Closure $nonce): array
    {
        $rec = Tools::local($owner);
        if ($rec === null) {
            return [];
        }
        $active = array_map(static fn (array $e): string => (string) $e['basename'], Tools::entries());
        $installed = CatalogState::wpPlugins();

        return self::inactiveFrom(CatalogState::fromWp($rec->load()['items']), $active, $installed, $nonce);
    }

    /**
     * rein, testbar
     * @param list<array<string,mixed>> $items CatalogState::apply
     * @param list<string> $active Basenames aus MGD Tools
     * @param array<string,mixed> $installed get_plugins()
     * @return list<array<string,mixed>>
     */
    public static function inactiveFrom(array $items, array $active, array $installed, ?\Closure $nonce): array
    {
        $out = [];
        foreach ($items as $it) {
            $file = is_string($it['file'] ?? null) ? $it['file'] : '';
            if ($file === '' || ($it['active'] ?? false) === true || in_array($file, $active, true) || !PluginToggle::mgdHeader($installed[$file] ?? null)) {
                continue;
            }
            $name = is_string($it['name'] ?? null) ? $it['name'] : '';
            $slug = is_string($it['slug'] ?? null) ? $it['slug'] : '';
            $out[] = [
                'name' => $name,
                'icon' => '',
                'installed' => (string) ($it['installedVersion'] ?? ''),
                'latest' => is_string($it['version'] ?? null) ? ltrim($it['version'], 'vV') : '',
                'newer' => false,
                'state' => 'off',
                'inactive' => true,
                'settings' => '',
                'details' => $slug !== '' ? Tools::DETAILS_URL . $slug : '',
                'license' => null,
                'toggle' => PluginToggle::form($file, $name, false, Tools::PARENT, $nonce),
            ];
        }
        usort($out, static fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name']));

        return $out;
    }

    /** @param array<string,mixed> $st */
    public static function state(bool $has, array $st): string
    {
        if (!$has) {
            return 'off';
        }
        if (is_string($st['latest'] ?? null) && $st['latest'] !== '') {
            return 'ok';
        }

        // schon mal Daten gehabt > Fehler, sonst gibts die Datei halt noch nicht
        return (int) ($st['ok'] ?? 0) > 0 ? 'fail' : 'none';
    }

    public static function license(string $owner): void
    {
        try {
            if (!\current_user_can('manage_options')) {
                return;
            }
            $l = self::central($owner);
            $action = LicenseView::nonceAction(Tools::PARENT);
            $post = \wp_unslash($_POST);
            $ok = \wp_verify_nonce((string) (is_array($post) ? ($post[LicenseView::NONCE] ?? '') : ''), $action) !== false;
            $msg = (string) LicenseView::handle($l, Tools::PARENT, is_array($post) ? $post : [], $ok);
            $labels = [];
            foreach (Tools::entries() as $e) {
                $labels += $e['pro'];
            }
            echo ToolsView::license($l, (string) \wp_nonce_field($action, LicenseView::NONCE, true, false), $msg, $labels);
        } catch (\Throwable) {
            echo '<div class="wrap"><p>' . ToolsTexts::ERROR . '</p></div>';
        }
    }

    public static function catalog(string $owner): void
    {
        try {
            $lic = self::central($owner);
            echo ToolsView::wrap(ToolsTexts::CATALOG, PluginToggle::message($_GET, CatalogState::wpPlugins()), RecommendationsView::render(Tools::local($owner), $owner, $lic));
        } catch (\Throwable) {
            echo '<div class="wrap"><p>' . ToolsTexts::ERROR . '</p></div>';
        }
    }

    // einmalig nach dem Umzug, schließbar, danach nie wieder
    public static function movedNotice(): void
    {
        try {
            if (!\current_user_can('manage_options') || \get_option(Tools::MOVED_OPT, '') === '1' || Tools::entries() === []) {
                return;
            }
            \update_option(Tools::MOVED_OPT, '1', false);
            echo '<div class="notice notice-info is-dismissible"><p>' . ToolsTexts::MOVED . ' <a href="' . \esc_url(Tools::url()) . '">' . ToolsTexts::MENU . '</a></p></div>';
        } catch (\Throwable) {
        }
    }

    /**
     * alte Admin-Tools aus Plugins vor 2.1.1 stumm schalten (Dauer-Hinweis "MGD Plugin-Updates" + doppelte Links)
     * Klassen: MGD_Plugin_Admin_Tools_<datum>, MGD_WP_SEO_Plugin_Admin_Tools. Rückgabe = Anzahl entfernt
     */
    public static function muteLegacy(): int
    {
        $n = 0;
        foreach (['admin_notices', 'plugin_row_meta'] as $hook) {
            $h = $GLOBALS['wp_filter'][$hook] ?? null;
            $all = is_object($h) ? (array) ($h->callbacks ?? []) : (is_array($h) ? $h : []);
            foreach ($all as $prio => $cbs) {
                foreach ((array) $cbs as $cb) {
                    $fn = is_array($cb) ? ($cb['function'] ?? null) : null;
                    $cls = is_array($fn) && is_string($fn[0] ?? null) ? $fn[0] : (is_array($fn) && is_object($fn[0] ?? null) ? get_class($fn[0]) : '');
                    if ($cls !== '' && preg_match(self::LEGACY_RE, $cls) === 1) {
                        \remove_filter($hook, $fn, (int) $prio);
                        ++$n;
                    }
                }
            }
        }

        return $n;
    }

    public static function licenseOn(): bool
    {
        foreach (Tools::entries() as $e) {
            if ($e['license'] === true) {
                return true;
            }
        }

        return false;
    }

    // ein gemeinsamer Schlüssel, egal welches Produkt
    public static function central(string $owner): LicenseClient
    {
        return Setup::license($owner, 'wordpress', self::licenseOn());
    }

    private static function call(mixed $fn): string
    {
        try {
            return $fn instanceof \Closure ? (string) $fn() : '';
        } catch (\Throwable) {
            return '';
        }
    }
}
