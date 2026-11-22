<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Support\Text;

// eine Karte Weitere Plugins + Inhalt fürn Details-Dialog (template, kein Skript)
final class CatalogCard
{
    /** @param array<string,mixed> $it von RecommendationsView::prepare() */
    public static function html(array $it, string $uid, array $ctx): string
    {
        $slug = (string) $it['slug'];
        $name = (string) $it['name'];
        $did = $uid . '-d-' . $slug;
        $features = array_slice((array) $it['features'], 0, 3);
        $search = $name . ' ' . $it['description'] . ' ' . $it['category'] . ' ' . implode(' ', $features);
        $accent = is_string($it['accent']) ? ' style="--mgd-kit-cat-accent:' . Text::e($it['accent']) . '"' : '';
        $li = '';
        foreach ($features as $f) {
            $li .= '<li>' . Text::e((string) $f) . '</li>';
        }
        $bg = '';
        if ($it['image'] !== '') {
            $alt = (string) $it['imageAlt'];
            $bg = '<img class="mgd-kit-catalog__card-background" src="' . Text::e((string) $it['image']) . '" width="1080" height="360" alt="' . Text::e($alt) . '"'
                . ($alt === '' ? ' aria-hidden="true"' : '') . ' loading="lazy" decoding="async">';
        }

        return '<article class="mgd-kit-catalog__card" data-card' . $accent . ' data-category="' . Text::e((string) $it['category']) . '" data-state="' . Text::e((string) $it['state'])
            . '" data-tier="' . Text::e((string) $it['tier']) . '" data-search="' . Text::e($search) . '">'
            . '<div class="mgd-kit-catalog__card-art">' . $bg
            . '<span class="mgd-kit-catalog__category">' . Text::e((string) $it['category']) . '</span>'
            . RecommendationsView::icon($it, 'mgd-kit-catalog__card-icon', 78)
            . ($it['version'] !== '' ? '<span class="mgd-kit-catalog__version">v' . Text::e((string) $it['version']) . '</span>' : '')
            . '</div><div class="mgd-kit-catalog__card-body"><div class="mgd-kit-catalog__card-heading"><h4>' . Text::e($name) . '</h4>'
            . '<span class="mgd-kit-catalog__price">' . ($it['tier'] === 'free' ? 'Kostenlos' : 'Kostenpflichtig') . '</span></div>'
            . '<p class="mgd-kit-catalog__description">' . Text::e((string) $it['description']) . '</p>'
            . ($li !== '' ? '<ul class="mgd-kit-catalog__features">' . $li . '</ul>' : '')
            . self::status($it)
            . '<div class="mgd-kit-catalog__actions"><a class="mgd-kit-catalog__details" href="' . Text::e((string) $it['url']) . '" data-details="' . Text::e($did) . '" data-title="' . Text::e($name)
            . '" aria-label="' . Text::e('Details zu ' . $name) . '">Details <span aria-hidden="true">↗</span></a>' . self::action($it, $ctx) . '</div></div>'
            . '<template id="' . Text::e($did) . '"><p>' . Text::e((string) $it['description']) . '</p>' . ($li !== '' ? '<ul>' . $li . '</ul>' : '')
            . '<p>' . ($it['tier'] === 'free' ? 'Kostenlos erhältlich.' : 'Kostenpflichtige Erweiterung. Informationen und Konditionen findest du beim Anbieter.') . ' Installation und Aktivierung bleiben getrennte Schritte.</p>'
            . '<a class="mgd-kit-catalog__button" href="' . Text::e((string) $it['url']) . '" target="_blank" rel="noopener noreferrer">Mehr erfahren <span class="screen-reader-text">(neuer Tab)</span></a></template>'
            . '</article>';
    }

    private static function status(array $it): string
    {
        $label = $it['state'] === 'update' ? 'Update verfügbar' : ($it['active'] ? 'Aktiv' : ($it['installed'] ? 'Installiert · inaktiv' : 'Für deine Website'));

        return '<div class="mgd-kit-catalog__card-status"><span class="mgd-kit-catalog__status mgd-kit-catalog__status--' . Text::e((string) $it['state']) . '">' . $label . '</span>'
            . ($it['installedVersion'] !== '' ? '<span>v' . Text::e(ltrim((string) $it['installedVersion'], 'vV')) . ' installiert</span>' : '') . '</div>';
    }

    // Aktualisieren > Verwalten > Ansehen (bezahlt / kein Recht) > Installieren > später
    private static function action(array $it, array $ctx): string
    {
        $nonce = ($ctx['nonce'] ?? null) instanceof \Closure ? $ctx['nonce'] : null;
        $product = (string) ($ctx['product'] ?? '');
        $name = (string) $it['name'];
        if ($it['state'] === 'update' && $nonce !== null) {
            $file = (string) $it['updateFile'];
            $href = 'update.php?action=upgrade-plugin&plugin=' . rawurlencode($file) . '&_wpnonce=' . rawurlencode((string) $nonce('upgrade-plugin_' . $file));

            return '<a class="mgd-kit-catalog__button" href="' . Text::e($href) . '">Aktualisieren</a>';
        }
        if ($it['installed']) {
            return '<a class="mgd-kit-catalog__button mgd-kit-catalog__button--quiet" href="plugins.php">Verwalten</a>';
        }
        $canInstall = ($ctx['canInstall'] ?? false) === true && $product !== '' && $nonce !== null;
        if ($it['tier'] === 'free' && $it['updateProduct'] !== null && $canInstall) {
            $slug = (string) $it['slug'];

            return '<form method="post" action="admin-post.php"><input type="hidden" name="action" value="' . Text::e(CatalogInstaller::action($product)) . '">'
                . '<input type="hidden" name="slug" value="' . Text::e($slug) . '"><input type="hidden" name="_wpnonce" value="' . Text::e((string) $nonce(CatalogInstaller::nonceAction($product, $slug))) . '">'
                . '<button class="mgd-kit-catalog__button" type="submit" aria-label="' . Text::e($name . ' installieren') . '">Installieren <span aria-hidden="true">+</span></button></form>';
        }
        if ($it['tier'] === 'paid' || ($it['updateProduct'] !== null && !$canInstall)) {
            return '<a class="mgd-kit-catalog__button" href="' . Text::e((string) $it['url']) . '" target="_blank" rel="noopener noreferrer">Ansehen <span class="screen-reader-text">(neuer Tab)</span></a>';
        }

        return '<span class="mgd-kit-catalog__unavailable">Später erneut prüfen</span>';
    }
}
