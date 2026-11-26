<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Catalog\Recommendations;
use Mgd\WordPressMcp\Kit\KitInfo;
use Mgd\WordPressMcp\Kit\Update\UpdateInfo;

// MGD Tools: ein Menü für alle MGD-Plugins. Jedes Plugin hat seine eigene Baukasten-Kopie (anderer Namensraum)
// > gemeinsame Liste in $GLOBALS, nur Skalare + Closures. Neuester Baukasten-Stand besitzt Menü + Seiten
final class Tools
{
    public const PARENT = 'mgd-tools';
    public const PAGE_LICENSE = 'mgd-tools-license';
    public const PAGE_CATALOG = 'mgd-tools-plugins';
    public const SCHEMA = 1;
    public const GLOBAL_KEY = 'mgd_tools';
    public const NONCE = '_mgd_tools_nonce';
    public const CHECK_ACTION = 'mgd_tools_check';
    public const MOVED_OPT = 'mgd_tools_moved_seen';
    public const DETAILS_URL = 'https://theme.michael-gahn.de/plugins/';
    public const PAGE_RE = '/^[a-z0-9_-]{1,80}$/D';
    public const CAP_RE = '/^[a-z_]{1,40}$/D';
    public const ENTITLEMENT_RE = '/^[a-z0-9]+(?:[-:][a-z0-9]+)*$/D';

    /** @var array<string,array{rec:?Recommendations,platform:string}> Objekte nur aus dieser Kopie */
    private static array $local = [];

    /**
     * Plugin in die gemeinsame Liste. Pflicht: product, name, version, basename. Rest siehe docs/plugin-baukasten/wordpress.md
     * @param array<string,mixed> $entry
     */
    public static function add(array $entry, ?Recommendations $rec = null, string $platform = 'wordpress'): bool
    {
        $e = self::clean($entry + ['schema' => self::SCHEMA, 'kit' => KitInfo::VERSION]);
        if ($e === null) {
            return false;
        }
        $GLOBALS[self::GLOBAL_KEY]['plugins'][$e['product']] = $e;
        $first = !isset(self::$local[$e['product']]);
        self::$local[$e['product']] = ['rec' => $rec, 'platform' => $platform];
        if ($first && function_exists('add_action')) {
            self::hooks($e['product']);
        }

        return true;
    }

    /**
     * @param array<string,mixed> $e
     * @return array<string,mixed>|null
     */
    public static function clean(array $e): ?array
    {
        $str = static fn (string $k): string => is_string($e[$k] ?? null) ? trim($e[$k]) : '';
        if (($e['schema'] ?? null) !== self::SCHEMA || preg_match(Setup::PRODUCT_RE, $str('product')) !== 1
            || $str('name') === '' || mb_strlen($str('name')) > 80
            || preg_match(UpdateInfo::VERSION_RE, $str('version')) !== 1 || preg_match(UpdateInfo::VERSION_RE, $str('kit')) !== 1
            || preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+\.php$#D', $str('basename')) !== 1) {
            return null;
        }
        $s = is_array($e['settings'] ?? null) ? $e['settings'] : null;
        $settings = null;
        if ($s !== null && is_string($s['page'] ?? null) && preg_match(self::PAGE_RE, $s['page']) === 1 && is_callable($s['render'] ?? null)) {
            $cap = is_string($s['cap'] ?? null) && preg_match(self::CAP_RE, $s['cap']) === 1 ? $s['cap'] : 'manage_options';
            $title = is_string($s['title'] ?? null) && trim($s['title']) !== '' ? trim($s['title']) : self::shortName($str('name'));
            // alte Seiten-Kennungen (umbenannt), leiten auf die neue Seite um
            $legacy = array_values(array_filter(is_array($s['legacy'] ?? null) ? $s['legacy'] : [], static fn ($p): bool => is_string($p) && preg_match(self::PAGE_RE, $p) === 1));
            $settings = ['page' => $s['page'], 'cap' => $cap, 'title' => mb_substr($title, 0, 60), 'render' => $s['render'], 'legacy' => array_slice($legacy, 0, 5)];
        }
        $pro = [];
        foreach (is_array($e['pro'] ?? null) ? $e['pro'] : [] as $k => $label) {
            if (is_string($k) && preg_match(self::ENTITLEMENT_RE, $k) === 1 && is_string($label) && $label !== '' && count($pro) < 20) {
                $pro[$k] = mb_substr($label, 0, 80);
            }
        }
        $icon = $str('icon');
        $slug = $str('slug');

        return [
            'schema' => self::SCHEMA,
            'kit' => $str('kit'),
            'product' => $str('product'),
            'name' => $str('name'),
            'version' => $str('version'),
            'basename' => $str('basename'),
            'icon' => preg_match('#^https?://#', $icon) === 1 ? $icon : '',
            'slug' => preg_match(Setup::PRODUCT_RE, $slug) === 1 ? $slug : '',
            'settings' => $settings,
            'status' => ($e['status'] ?? null) instanceof \Closure ? $e['status'] : null,
            'install' => ($e['install'] ?? null) instanceof \Closure ? $e['install'] : null,
            'license' => ($e['license'] ?? false) === true,
            'pro' => $pro,
        ];
    }

    /** @return array<string,array<string,mixed>> gültige Einträge, sortiert nach Name */
    public static function entries(): array
    {
        $out = [];
        foreach ((array) ($GLOBALS[self::GLOBAL_KEY]['plugins'] ?? []) as $p => $e) {
            if (is_array($e) && ($e['schema'] ?? null) === self::SCHEMA && is_string($p)) {
                $out[$p] = $e;
            }
        }
        uasort($out, static fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name']));

        return $out;
    }

    // neuester Kit-Stand gewinnt, bei Gleichstand das alphabetisch erste Produkt. Einmal festlegen, alle Kopien lesen das
    public static function owner(): string
    {
        $o = $GLOBALS[self::GLOBAL_KEY]['owner'] ?? null;
        if (is_string($o) && $o !== '') {
            return $o;
        }
        $best = '';
        $bestKit = '0.0.0';
        foreach (self::entries() as $p => $e) {
            $c = version_compare((string) $e['kit'], $bestKit);
            if ($best === '' || $c > 0 || ($c === 0 && strcmp($p, $best) < 0)) {
                $best = $p;
                $bestKit = (string) $e['kit'];
            }
        }
        if ($best !== '' && self::didAction('admin_menu')) {
            $GLOBALS[self::GLOBAL_KEY]['owner'] = $best;
        }

        return $best;
    }

    /** nur Tests */
    public static function reset(): void
    {
        self::$local = [];
        unset($GLOBALS[self::GLOBAL_KEY]);
    }

    /**
     * Ziel für alte Adressen. null = nix tun
     * @param array<string,mixed> $get
     * @param array<string,array<string,mixed>> $entries
     */
    public static function redirectTarget(string $pagenow, array $get, array $entries): ?string
    {
        $page = is_string($get['page'] ?? null) ? $get['page'] : '';
        if ($page === '') {
            return null;
        }
        // alte Kit-Seite unter Einstellungen > Übersicht
        if (str_starts_with($page, 'mgd-kit-') && isset($entries[substr($page, 8)])) {
            return 'admin.php?page=' . self::PARENT;
        }
        $keep = static fn (mixed $v, mixed $k): bool => is_string($k) && $k !== 'page' && preg_match('/^[a-z0-9_-]{1,40}$/D', $k) === 1 && is_scalar($v);
        foreach ($entries as $e) {
            $st = is_array($e['settings'] ?? null) ? $e['settings'] : null;
            if ($st === null) {
                continue;
            }
            // umbenannte Seite: immer umleiten, auch auf admin.php
            if (in_array($page, $st['legacy'] ?? [], true)) {
                return 'admin.php?' . http_build_query(['page' => $st['page']] + array_filter($get, $keep, ARRAY_FILTER_USE_BOTH));
            }
            // gleiche Kennung, alter Elternpunkt (options-general.php, tools.php, upload.php …)
            if ($st['page'] === $page && $pagenow !== 'admin.php') {
                return 'admin.php?' . http_build_query(['page' => $page] + array_filter($get, $keep, ARRAY_FILTER_USE_BOTH));
            }
        }

        return null;
    }

    /**
     * Reihenfolge im Untermenü: Übersicht, Plugins nach Titel, Lizenz, Weitere Plugins
     * @param list<array<int,mixed>> $items WP $submenu[PARENT]
     * @return list<array<int,mixed>>
     */
    public static function sortItems(array $items): array
    {
        $rank = static fn (array $i): int => match ((string) ($i[2] ?? '')) {
            self::PARENT => 0,
            self::PAGE_LICENSE => 2,
            self::PAGE_CATALOG => 3,
            default => 1,
        };
        usort($items, static fn (array $a, array $b): int => [$rank($a), strcasecmp((string) ($a[0] ?? ''), (string) ($b[0] ?? ''))] <=> [$rank($b), 0]);

        return array_values($items);
    }

    public static function shortName(string $name): string
    {
        $s = preg_replace('/^MGD\s+/u', '', $name);

        return is_string($s) && $s !== '' ? $s : $name;
    }

    public static function url(string $page = self::PARENT): string
    {
        return (string) \admin_url('admin.php?page=' . rawurlencode($page));
    }

    public static function local(string $product): ?Recommendations
    {
        return self::$local[$product]['rec'] ?? null;
    }

    // did_action gibts im Test nicht immer
    private static function didAction(string $hook): bool
    {
        return function_exists('did_action') && \did_action($hook) > 0;
    }

    private static function hooks(string $product): void
    {
        $own = static fn (): bool => self::owner() === $product;
        \add_action('admin_menu', static function () use ($own, $product): void {
            if ($own()) {
                ToolsPages::menu($product);
            }
        }, 9);
        \add_action('admin_menu', static function () use ($product): void {
            $e = self::entries()[$product] ?? null;
            $s = is_array($e) ? $e['settings'] : null;
            if (is_array($s)) {
                \add_submenu_page(self::PARENT, $s['title'], $s['title'], $s['cap'], $s['page'], $s['render']);
            }
        }, 10);
        \add_action('admin_menu', static function () use ($own, $product): void {
            if ($own()) {
                ToolsPages::menuTail($product);
            }
        }, 11);
        \add_action('admin_menu', static function () use ($own): void {
            global $submenu;
            if ($own() && is_array($submenu[self::PARENT] ?? null)) {
                $submenu[self::PARENT] = self::sortItems($submenu[self::PARENT]);
            }
        }, 99);
        $redirect = static function () use ($own): void {
            global $pagenow;
            if (!$own()) {
                return;
            }
            $to = self::redirectTarget((string) $pagenow, $_GET, self::entries());
            if ($to !== null && \wp_safe_redirect(\admin_url($to))) {
                exit;
            }
        };
        // WP prüft die Rechte für ?page= schon in menu.php VOR admin_init > alte Adressen gaben 403 (Befund ebook/mcp)
        \add_action('admin_page_access_denied', $redirect, 1);
        \add_action('admin_init', static function () use ($own, $redirect): void {
            if ($own()) {
                ToolsPages::muteLegacy();
                $redirect();
            }
        }, 1);
        // an/aus Knopf (PluginToggle), nur der Besitzer antwortet
        \add_action('admin_post_' . PluginToggle::ACTION, static function () use ($own, $product): void {
            if ($own()) {
                PluginToggle::handle($product);
            }
        });
        \add_action('admin_notices', static function () use ($own): void {
            if ($own()) {
                ToolsPages::movedNotice();
            }
        });
    }
}

