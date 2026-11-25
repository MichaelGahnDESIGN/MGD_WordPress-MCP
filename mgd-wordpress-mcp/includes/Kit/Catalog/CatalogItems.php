<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Catalog;

use Mgd\WordPressMcp\Kit\Support\Text;

// Karten-Daten für JTL + Shopware (wie WordPress RecommendationsView::prepare, nur ohne Installieren)
// installiert/aktiv gibts erst wenn der Feed eine sichere Kennung liefert, bis dahin nie raten
final class CatalogItems
{
    /**
     * @param mixed $items von Recommendations::load()
     * @param \Closure(?string):string|null $media Katalog-URL > lokale Adresse/Name ('' = nicht da > Farbfläche)
     * @return list<array<string,mixed>>
     */
    public static function prepare(mixed $items, ?\Closure $media = null): array
    {
        $out = [];
        foreach (is_array($items) ? array_slice(array_values($items), 0, Recommendations::MAX_ITEMS) : [] as $it) {
            $card = is_array($it) ? self::card($it, $media) : null;
            if ($card !== null && !isset($out[$card['slug']])) {
                $out[$card['slug']] = $card;
            }
        }

        return array_values($out);
    }

    private static function card(array $it, ?\Closure $media): ?array
    {
        $slug = is_string($it['slug'] ?? null) ? $it['slug'] : '';
        $name = is_string($it['name'] ?? null) ? Text::plain($it['name'], 120) : '';
        if (strlen($slug) > 100 || preg_match(Recommendations::SLUG_RE, $slug) !== 1 || $name === '') {
            return null;
        }
        $f = Recommendations::catalogFields($it);
        $local = static fn (?string $url): string => $url !== null && $media !== null ? (string) $media($url) : '';
        $image = $local($f['imageUrl']);

        return [
            'slug' => $slug,
            'name' => $name,
            'description' => is_string($it['description'] ?? null) ? Text::plain($it['description'], 500) : '',
            'version' => is_string($it['version'] ?? null) ? ltrim(Text::plain($it['version'], 30), 'vV') : '',
            // Link immer selbst bauen, nie aus den Daten
            'url' => Recommendations::DETAILS_PREFIX . $slug,
            'category' => $f['category'],
            'features' => $f['features'],
            'tags' => $f['tags'],
            'tier' => $f['tier'],
            'accent' => $f['accent'],
            'icon' => $local($f['iconUrl']),
            'image' => $image,
            'imageAlt' => $image !== '' ? $f['imageAlt'] : '',
            'state' => 'available',
            'installed' => false,
            'active' => false,
        ];
    }
}
