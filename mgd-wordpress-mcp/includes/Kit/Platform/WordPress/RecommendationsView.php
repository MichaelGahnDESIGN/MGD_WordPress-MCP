<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Catalog\Recommendations;
use Mgd\WordPressMcp\Kit\Support\Text;

// Weitere Plugins im Design der alten MGD Plugin-Kollektion (Maintenance > assets/plugin-catalog)
// nur Admin, Bilder nur lokal gecacht, Daten fürs JS nur über data-Attribute
final class RecommendationsView
{
    public const CSS = __DIR__ . '/assets/kit-catalog.css';
    public const JS = __DIR__ . '/assets/kit-catalog.js';
    public const NOTICES = [
        'installed' => 'Plugin installiert. Du kannst es unter „Plugins“ aktivieren.',
        'failed' => 'Die Installation ist fehlgeschlagen. Prüfe Dateirechte und Verbindung unter „Plugins“.',
        'unavailable' => 'Für dieses Plugin ist gerade keine Installation möglich. Bitte versuche es später noch einmal.',
    ];

    /** $product = Produktname vom Plugin, nur damit gibts Installieren-Knöpfe (Setup::catalogActions muss laufen) */
    public static function render(?Recommendations $r, string $product = ''): string
    {
        try {
            if ($r === null || !\current_user_can('manage_options')) {
                return '';
            }
            $res = $r->load();
            $items = CatalogState::fromWp(self::syncMedia($res['items'], $res['source'] === 'fresh'));
            self::enqueue();
            $get = $_GET[CatalogInstaller::QUERY] ?? '';
            $checked = (int) ($res['checkedAt'] ?? 0);

            return self::html(['items' => $items, 'source' => $res['source']], [
                'product' => preg_match(Setup::PRODUCT_RE, $product) === 1 ? $product : '',
                'canInstall' => \current_user_can('install_plugins') && function_exists('wp_create_nonce'),
                'nonce' => function_exists('wp_create_nonce') ? static fn (string $a): string => (string) \wp_create_nonce($a) : null,
                'notice' => is_string($get) ? $get : '',
                'checked' => $checked > 0 ? (function_exists('wp_date') ? (string) \wp_date('d.m.Y · H:i', $checked) : gmdate('d.m.Y · H:i', $checked)) : '',
                'media' => static fn (string $url): string => CatalogMedia::url(CatalogMedia::nameFromUrl($url)),
            ]);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * reines HTML, testbar ohne WP
     * @param array{items?:mixed,source?:mixed,checkedAt?:mixed} $result von Recommendations::load() (+ CatalogState)
     * @param array{product?:string,canInstall?:bool,nonce?:?\Closure,notice?:string,checked?:string,media?:?\Closure} $ctx
     *   media: Katalog-URL > lokale URL (ohne = gar keine Bilder, nie fremde Adressen)
     */
    public static function html(array $result, array $ctx = []): string
    {
        $items = [];
        foreach (is_array($result['items'] ?? null) ? $result['items'] : [] as $it) {
            $card = is_array($it) ? self::prepare($it, $ctx) : null;
            if ($card !== null) {
                $items[] = $card;
            }
        }
        $source = is_string($result['source'] ?? null) ? $result['source'] : '';
        $uid = 'mgd-kit-cat-' . (($ctx['product'] ?? '') !== '' ? str_replace('-', '_', (string) $ctx['product']) : 'x');
        $checked = (string) ($ctx['checked'] ?? '');
        if ($checked === '' && is_int($result['checkedAt'] ?? null) && $result['checkedAt'] > 0) {
            $checked = gmdate('d.m.Y · H:i', $result['checkedAt']);
        }
        $notice = (string) ($ctx['notice'] ?? '');

        return self::style() . '<div class="mgd-kit-catalog" data-mgd-kit-catalog>'
            . self::hero($items)
            . self::bar($items)
            . (isset(self::NOTICES[$notice]) ? '<p class="mgd-kit-catalog__notice" role="status">' . Text::e(self::NOTICES[$notice]) . '</p>' : '')
            . ($source === 'stale' ? '<p class="description">Die Liste konnte gerade nicht aktualisiert werden. Du siehst den letzten bekannten Stand.</p>' : '')
            . self::browser($items, $uid, $ctx)
            . self::footer($source, $checked)
            . '<dialog class="mgd-kit-catalog__dialog" aria-labelledby="' . $uid . '-dialog-title"><div class="mgd-kit-catalog__dialog-header"><p>MGD PLUGIN-KOLLEKTION</p><button type="button" data-close aria-label="Details schließen">×</button></div>'
            . '<h2 id="' . $uid . '-dialog-title">Plugin-Details</h2><div data-dialog-content></div></dialog></div>';
    }

    // Felder nochmal sicher machen, egal woher die Daten kommen + lokale Bild-URLs
    private static function prepare(array $it, array $ctx): ?array
    {
        $slug = is_string($it['slug'] ?? null) ? $it['slug'] : '';
        $name = is_string($it['name'] ?? null) ? Text::plain($it['name'], 120) : '';
        if (strlen($slug) > 100 || preg_match(Recommendations::SLUG_RE, $slug) !== 1 || $name === '') {
            return null;
        }
        $media = ($ctx['media'] ?? null) instanceof \Closure ? $ctx['media'] : null;
        $f = Recommendations::catalogFields($it);
        $local = static function (?string $url) use ($media): string {
            $l = $url !== null && $media !== null ? (string) $media($url) : '';

            // nur eigene Uploads-Adresse, nie theme.michael-gahn.de direkt
            return $l !== '' && preg_match('~^(https?://[^/?#]+)?/[^?#"\'<>\s]*/' . CatalogMedia::DIR . '/[a-f0-9]{32}\.webp$~D', $l) === 1 ? $l : '';
        };
        $image = $local($f['imageUrl']);
        $state = in_array($it['state'] ?? '', ['update', 'installed'], true) ? (string) $it['state'] : 'available';
        $file = is_string($it['updateFile'] ?? null) && preg_match(Recommendations::BASENAME_RE, $it['updateFile']) === 1 ? $it['updateFile'] : '';
        $installedVersion = is_string($it['installedVersion'] ?? null) ? Text::plain($it['installedVersion'], 30) : '';

        return [
            'slug' => $slug,
            'name' => $name,
            'description' => is_string($it['description'] ?? null) ? Text::plain($it['description'], 500) : '',
            'version' => is_string($it['version'] ?? null) ? ltrim(Text::plain($it['version'], 30), 'vV') : '',
            // Link immer selbst bauen, nie aus den Daten übernehmen
            'url' => Recommendations::DETAILS_PREFIX . $slug,
            'icon' => $local($f['iconUrl']),
            'image' => $image,
            'imageAlt' => $image !== '' ? $f['imageAlt'] : '',
            'installed' => $state !== 'available' || ($it['installed'] ?? false) === true,
            'active' => ($it['active'] ?? false) === true,
            'installedVersion' => $installedVersion,
            'updateFile' => $state === 'update' ? $file : '',
            'state' => $state === 'update' && $file === '' ? 'installed' : $state,
        ] + $f;
    }

    private static function hero(array $items): string
    {
        $art = '';
        foreach (array_slice($items, 0, 3) as $i => $it) {
            $art .= self::icon($it, 'mgd-kit-catalog__hero-icon mgd-kit-catalog__hero-icon--' . $i, 94);
        }

        return '<header class="mgd-kit-catalog__hero"><div class="mgd-kit-catalog__hero-copy"><p class="mgd-kit-catalog__eyebrow"><span class="mgd-kit-catalog__brand">MGD</span> WORDPRESS · PLUGIN-KOLLEKTION</p>'
            . '<h3>Mehr aus deiner<br><em>Website machen.</em></h3><p>Durchdachte Erweiterungen von Michael Gahn DESIGN.<br>Entdecke die passenden Werkzeuge für dein WordPress.</p></div>'
            . '<div class="mgd-kit-catalog__hero-art" aria-hidden="true"><span class="mgd-kit-catalog__orbit"></span>' . $art . '<span class="mgd-kit-catalog__art-caption">GEMACHT FÜR DEIN WORDPRESS</span></div></header>';
    }

    private static function bar(array $items): string
    {
        $installed = count(array_filter($items, static fn (array $it): bool => $it['installed']));

        return '<div class="mgd-kit-catalog__collection-bar"><p><strong>' . count($items) . ' Erweiterungen</strong><span>' . $installed . ' installiert</span><span>Ohne automatische Aktivierung</span></p></div>';
    }

    private static function browser(array $items, string $uid, array $ctx): string
    {
        $cats = '';
        foreach (array_values(array_unique(array_column($items, 'category'))) as $c) {
            $cats .= '<button type="button" data-category="' . Text::e($c) . '" aria-pressed="false">' . Text::e($c) . '</button>';
        }
        // Tag-Chips (mehrere wählbar), nur wenn der Feed Tags liefert
        $all = [];
        foreach ($items as $it) {
            foreach ((array) ($it['tags'] ?? []) as $t) {
                $all[mb_strtolower((string) $t)] = (string) $t;
            }
        }
        $chips = '';
        foreach ($all as $key => $label) {
            $chips .= '<button type="button" data-tag="' . Text::e($key) . '" aria-pressed="false">' . Text::e($label) . '</button>';
        }
        $tagBar = $chips === '' ? '' : '<div class="mgd-kit-catalog__tags" role="group" aria-label="Nach Tag filtern" data-filters hidden><span class="mgd-kit-catalog__tags-title">Tags</span>' . $chips
            . '<button type="button" class="mgd-kit-catalog__tags-reset" data-tag-reset hidden>Alle Tags zurücksetzen</button></div>';
        $cards = '';
        foreach ($items as $it) {
            $cards .= CatalogCard::html($it, $uid, $ctx);
        }
        $n = count($items);

        return '<section class="mgd-kit-catalog__browser" aria-label="Plugins entdecken">'
            . '<div class="mgd-kit-catalog__toolbar" data-filters hidden>'
            . '<div class="mgd-kit-catalog__search"><label for="' . $uid . '-search"><span aria-hidden="true">⌕</span><span class="screen-reader-text">Plugins durchsuchen</span></label><input id="' . $uid . '-search" type="search" placeholder="Was möchtest du verbessern?" autocomplete="off" data-search-input></div>'
            . '<label class="mgd-kit-catalog__select">Status<select data-status><option value="all">Alle Plugins</option><option value="installed">Installiert</option><option value="available">Nicht installiert</option><option value="update">Updates verfügbar</option></select></label>'
            . '<label class="mgd-kit-catalog__select">Preis<select data-price><option value="all">Alle Preise</option><option value="free">Kostenlos</option><option value="paid">Kostenpflichtig</option></select></label></div>'
            . '<div class="mgd-kit-catalog__browse-heading"><div class="mgd-kit-catalog__categories" role="group" aria-label="Kategorie filtern" data-filters hidden><button type="button" data-category="all" aria-pressed="true">Alle entdecken</button>' . $cats . '</div>'
            . '<p class="mgd-kit-catalog__count" aria-live="polite" aria-atomic="true" data-count>' . $n . ' ' . ($n === 1 ? 'Plugin' : 'Plugins') . '</p></div>'
            . $tagBar
            . '<div class="mgd-kit-catalog__grid">' . $cards . '</div>'
            . '<div class="mgd-kit-catalog__empty" data-empty' . ($n > 0 ? ' hidden' : '') . '><span aria-hidden="true">⌕</span>'
            . '<h4>' . ($n > 0 ? 'Noch nicht das Richtige dabei?' : 'Hier ist gerade nichts gelistet.') . '</h4>'
            . '<p>' . ($n > 0 ? 'Versuche einen anderen Suchbegriff oder setze die Filter zurück.' : 'Bitte schau später noch einmal vorbei.') . '</p>'
            . '<button class="mgd-kit-catalog__button" type="button" data-reset hidden>Filter zurücksetzen</button></div></section>';
    }

    private static function footer(string $source, string $checked): string
    {
        $central = $source === 'fresh' || $source === 'cache' || $source === 'stale';

        return '<footer class="mgd-kit-catalog__footer"><p><strong>Michael Gahn DESIGN</strong><span>Werkzeuge mit einem klaren Zweck.</span></p><p>'
            . ($central ? 'Katalog von theme.michael-gahn.de' : 'Ersatzkatalog aktiv · Zentraler Feed derzeit nicht erreichbar')
            . ($checked !== '' ? '<br><span>Geprüft: ' . Text::e($checked) . ' · Zwischenspeicherung bis zu 15 Minuten</span>' : '') . '</p></footer>';
    }

    /** Icon lokal oder mitgeliefertes Motiv als SVG im HTML (keine Datei, keine Adresse) */
    public static function icon(array $it, string $class, int $size): string
    {
        if (($it['icon'] ?? '') !== '') {
            return '<img class="' . Text::e($class) . '" src="' . Text::e((string) $it['icon']) . '" width="' . $size . '" height="' . $size . '" alt="" loading="lazy" decoding="async">';
        }

        return '<svg class="' . Text::e($class) . '" viewBox="0 0 128 128" width="' . $size . '" height="' . $size . '" aria-hidden="true" focusable="false"><rect width="128" height="128" rx="27" fill="#263b37"/>'
            . '<g fill="none" stroke="#e5c58e" stroke-width="5"><rect x="28" y="28" width="27" height="27" rx="5"/><rect x="28" y="73" width="27" height="27" rx="5"/><rect x="73" y="73" width="27" height="27" rx="5"/></g>'
            . '<path d="M86 27v28M72 41h28" stroke="#e6eee7" stroke-width="5" stroke-linecap="round"/></svg>';
    }

    // Bilder Server > Server holen, nur fehlende. Aufräumen nur bei frischer Liste
    /** @param list<array<string,mixed>> $items */
    private static function syncMedia(array $items, bool $fresh): array
    {
        $failed = 0;
        $keep = [];
        foreach ($items as $it) {
            foreach (['iconUrl', 'imageUrl'] as $k) {
                $n = CatalogMedia::sync($it[$k] ?? null, $failed);
                if ($n !== '') {
                    $keep[] = $n;
                }
            }
        }
        if ($fresh) {
            CatalogMedia::prune($keep);
        }

        return $items;
    }

    // JS als Datei im Footer, geht auch noch während die Seite schon ausgegeben wird
    private static function enqueue(): void
    {
        if (!function_exists('wp_enqueue_script') || !function_exists('plugins_url') || !is_file(self::JS)) {
            return;
        }
        \wp_enqueue_script('mgd-kit-catalog-' . substr(md5(__DIR__), 0, 8), \plugins_url('assets/kit-catalog.js', __FILE__), [], (string) filemtime(self::JS), true);
    }

    private static function style(): string
    {
        $css = is_file(self::CSS) ? (string) file_get_contents(self::CSS) : '';

        // eigene Datei, trotzdem kein </style> durchlassen
        return $css === '' ? '' : '<style>' . str_replace('</', '', $css) . '</style>';
    }
}
