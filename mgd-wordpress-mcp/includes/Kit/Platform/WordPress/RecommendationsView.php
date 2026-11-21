<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Catalog\Recommendations;
use Mgd\WordPressMcp\Kit\Support\Text;

// Karten "Weitere Plugins", nur Admin, keine fremden Bilder, nix wird installiert
final class RecommendationsView
{
    public static function render(?Recommendations $r): string
    {
        try {
            if ($r === null || !\current_user_can('manage_options')) {
                return '';
            }

            return self::html($r->load());
        } catch (\Throwable) {
            return '';
        }
    }

    /** @param array{items?:mixed,source?:mixed} $result von Recommendations::load() */
    public static function html(array $result): string
    {
        $items = is_array($result['items'] ?? null) ? $result['items'] : [];
        $source = $result['source'] ?? '';
        $out = '<div class="mgd-kit-recommendations">';
        if ($source === 'stale' || $source === 'fallback') {
            $out .= '<p class="description">Die Liste konnte gerade nicht aktualisiert werden. Du siehst den letzten bekannten Stand.</p>';
        }
        if ($items === []) {
            return $out . '<p>Gerade gibt es keine weiteren Plugins.</p></div>';
        }
        $out .= '<div class="mgd-kit-cards">';
        foreach ($items as $it) {
            $out .= is_array($it) ? self::card($it) : '';
        }

        return $out . '</div></div>';
    }

    private static function card(array $it): string
    {
        $slug = is_string($it['slug'] ?? null) ? $it['slug'] : '';
        if (preg_match(Recommendations::SLUG_RE, $slug) !== 1) {
            return '';
        }
        // Link immer selbst bauen, nie aus den Daten übernehmen
        $url = Recommendations::DETAILS_PREFIX . $slug;
        $version = is_string($it['version'] ?? null) ? ltrim($it['version'], 'vV') : '';

        return '<div class="mgd-kit-card card"><h3>' . Text::e((string) ($it['name'] ?? '')) . '</h3>'
            . '<p>' . Text::e((string) ($it['description'] ?? '')) . '</p>'
            . ($version !== '' ? '<p class="mgd-kit-version">Version ' . Text::e($version) . '</p>' : '')
            . '<p><a class="button" href="' . Text::e($url) . '" target="_blank" rel="noopener noreferrer">Mehr erfahren</a></p></div>';
    }
}
