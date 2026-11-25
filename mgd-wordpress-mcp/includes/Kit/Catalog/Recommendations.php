<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Catalog;

use Mgd\WordPressMcp\Kit\Install\{Store, Transport};
use Mgd\WordPressMcp\Kit\Support\Text;

// "Weitere Plugins" aus /api/v1/recommendations/<plattform>.json (Regeln docs/KATALOG.md)
// 15 Min Cache, 5 Min Pause nach Fehler, letzte gute Liste 7 Tage, sonst die mitgelieferte Liste. Wirft nie
final class Recommendations
{
    // Repos mit mehreren Plugins (JTL-Sammelrepo) > Repo sagt nix übers eigene Plugin
    public const SHARED_REPOS = ['michathemev5_jtl-shop-5'];
    public const URL_PREFIX = 'https://theme.michael-gahn.de/api/v1/recommendations/';
    public const DETAILS_PREFIX = 'https://theme.michael-gahn.de/plugins/';
    public const CATALOG_PREFIX = 'https://theme.michael-gahn.de/downloads?plattform=';
    public const TTL = 900;
    // Speicher-Format: alter Speicher (ohne Bilder/Kategorien) gilt nach Update nicht mehr als frisch > sofort neu holen
    public const FORMAT = 2;
    public const PAUSE = 300;
    public const STALE_MAX = 604800;
    public const MAX_ITEMS = 80;
    public const MAX_BYTES = 524288;
    public const SLUG_RE = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';
    public const REPO_RE = '/^[A-Za-z0-9_.-]{1,100}$/D';
    public const REPO_FIELDS = ['repo', 'repository', 'github'];
    // Katalog-Felder für die Karten (Kategorie, Häkchen, Erkennung installiert, Update-Datei, Bilder)
    public const CATEGORIES = ['Marketing', 'Medien', 'Administration', 'Kommunikation', 'Gestaltung', 'Barrierefreiheit', 'Weitere'];
    public const CATEGORY_DEFAULT = 'Weitere';
    public const MAX_FEATURES = 5;
    public const FEATURE_LEN = 80;
    public const MAX_TAGS = 12;
    public const TAG_LEN = 40;
    public const MAX_BASENAMES = 5;
    // kein Punkt vorne bei Ordner oder Datei (../x.php, .versteckt/x.php fliegen raus)
    public const BASENAME_RE = '/^[a-z0-9][a-z0-9._-]*\/[a-z0-9][a-z0-9._-]*\.php$/D';
    public const MEDIA_PREFIX = 'https://theme.michael-gahn.de/uploads/catalog/';
    public const MEDIA_FILE_RE = '/^[a-f0-9]{32}\.webp$/D';
    public const ACCENT_RE = '/^#[0-9a-fA-F]{6}$/D';

    // Kit-Shopsystem > Feed-Name + erlaubte platform-Werte im alten Feed
    public const PLATFORMS = [
        'wordpress' => ['wordpress', ['wordpress', 'woocommerce']],
        'woocommerce' => ['woocommerce', ['wordpress', 'woocommerce']],
        'jtl5' => ['jtl', ['jtl', 'jtl5']],
        'shopware6' => ['shopware', ['shopware', 'shopware6']],
    ];

    /** @var \Closure():int */
    private \Closure $clock;
    private readonly string $feed;
    /** @var list<string> */
    private readonly array $allowed;
    private readonly string $selfRepo;

    /**
     * @param list<array<string,mixed>> $fallback mitgelieferte Liste (slug, name, description, version)
     * @param string $selfRepo eigenes GitHub-Repo, z. B. MGD-Share-WordPress
     */
    public function __construct(
        private readonly Transport $http,
        private readonly Store $store,
        string $platform,
        private readonly string $selfSlug,
        private readonly array $fallback = [],
        ?\Closure $clock = null,
        string $selfRepo = '',
    ) {
        if (!isset(self::PLATFORMS[$platform])) {
            throw new \InvalidArgumentException('Shopsystem ungültig.');
        }
        [$this->feed, $this->allowed] = self::PLATFORMS[$platform];
        $this->clock = $clock ?? static fn (): int => time();
        $this->selfRepo = preg_match(self::REPO_RE, $selfRepo) === 1 ? strtolower($selfRepo) : '';
    }

    /**
     * @return array{items:list<array<string,mixed>>,source:string,checkedAt:int}
     *   source: fresh | cache | stale | fallback, checkedAt = Zeit vom letzten Abruf (Unix)
     *   Felder pro Eintrag siehe item()
     */
    public function load(): array
    {
        try {
            return $this->resolve();
        } catch (\Throwable) {
            return ['items' => $this->fallbackItems(), 'source' => 'fallback', 'checkedAt' => $this->now()];
        }
    }

    /**
     * Feed auf die schmale Anzeigeliste bringen. null = Feed unbrauchbar
     * @param array<mixed> $feed
     */
    public function normalize(array $feed): ?array
    {
        if (!is_array($feed['items'] ?? null)) {
            return null;
        }
        $items = array_slice(array_values($feed['items']), 0, self::MAX_ITEMS);
        $rows = [];
        if (($feed['schema'] ?? null) === 1) {
            // Vertrag v1: Feed muss genau zu unserem System gehören
            if (($feed['platform'] ?? null) !== $this->feed || ($feed['catalog_url'] ?? null) !== self::CATALOG_PREFIX . $this->feed) {
                return null;
            }
            foreach ($items as $row) {
                if (is_array($row) && ($row['platform'] ?? $this->feed) === $this->feed) {
                    $rows[] = $row;
                }
            }
        } elseif (($feed['schemaVersion'] ?? null) === 1) {
            // alter Feed, den liefert die Adresse heute noch > id = slug
            $ok = [...$this->allowed, 'other', 'all'];
            foreach ($items as $row) {
                if (is_array($row) && in_array($row['platform'] ?? '', $ok, true) && in_array($row['status'] ?? '', ['live', 'announced'], true)) {
                    $rows[] = ['slug' => $row['id'] ?? null] + $row;
                }
            }
        } else {
            return null;
        }

        return $this->clean(self::order($rows, $this->relatedOf($items)));
    }

    // KATALOG.md: empfohlene (related vom eigenen Eintrag) zuerst, dann live vor announced, sonst Feed-Reihenfolge
    /** @param list<array<mixed>> $rows @param list<string> $related @return list<array<mixed>> */
    private static function order(array $rows, array $related): array
    {
        $rank = static fn (array $r): int => (in_array($r['slug'] ?? null, $related, true) ? 0 : 2) + (($r['status'] ?? 'live') === 'announced' ? 1 : 0);
        $keyed = [];
        foreach (array_values($rows) as $i => $r) {
            $keyed[] = [$rank($r), $i, $r];
        }
        usort($keyed, static fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return array_column($keyed, 2);
    }

    /** @param list<mixed> $items @return list<string> */
    private function relatedOf(array $items): array
    {
        foreach ($items as $row) {
            $id = is_array($row) ? ($row['slug'] ?? $row['id'] ?? null) : null;
            if ($id !== null && $id === $this->selfSlug && is_array($row['related'] ?? null)) {
                return array_values(array_filter($row['related'], 'is_string'));
            }
        }

        return [];
    }

    private function resolve(): array
    {
        $now = ($this->clock)();
        $st = $this->state();
        $at = (int) ($st['rec_at'] ?? 0);
        $has = is_array($st['rec_items'] ?? null);
        $fresh = (int) ($st['rec_fmt'] ?? 0) === self::FORMAT;
        if ($has && $fresh && $at <= $now && $now - $at < self::TTL) {
            return ['items' => $this->clean($st['rec_items']), 'source' => 'cache', 'checkedAt' => $at];
        }
        if ((int) ($st['rec_pause'] ?? 0) <= $now) {
            $items = $this->fetch();
            if ($items !== null) {
                $this->save(['rec_items' => $items, 'rec_at' => $now, 'rec_fmt' => self::FORMAT]);

                return ['items' => $items, 'source' => 'fresh', 'checkedAt' => $now];
            }
            $this->save(['rec_pause' => $now + self::PAUSE] + $st);
        }
        if ($has && $at <= $now && $now - $at <= self::STALE_MAX) {
            return ['items' => $this->clean($st['rec_items']), 'source' => 'stale', 'checkedAt' => $at];
        }

        return ['items' => $this->fallbackItems(), 'source' => 'fallback', 'checkedAt' => $now];
    }

    private function fetch(): ?array
    {
        try {
            $r = $this->http->send('GET', self::URL_PREFIX . $this->feed . '.json', ['Accept' => 'application/json']);
        } catch (\Throwable) {
            return null;
        }
        if ($r->status !== 200 || $r->body === '' || strlen($r->body) > self::MAX_BYTES) {
            return null;
        }
        $feed = json_decode($r->body, true, 16);

        return is_array($feed) ? $this->normalize($feed) : null;
    }

    /** @param array<mixed> $rows */
    private function clean(array $rows): array
    {
        $out = [];
        foreach (array_slice(array_values($rows), 0, self::MAX_ITEMS) as $row) {
            $it = is_array($row) ? $this->item($row) : null;
            if ($it !== null && !isset($out[$it['slug']])) {
                $out[$it['slug']] = $it;
            }
        }

        return array_values($out);
    }

    private function item(array $row): ?array
    {
        $slug = $row['slug'] ?? null;
        $name = is_string($row['name'] ?? null) ? Text::plain($row['name'], 120) : '';
        // eigenes Plugin raus
        if (!is_string($slug) || strlen($slug) > 100 || preg_match(self::SLUG_RE, $slug) !== 1 || $slug === $this->selfSlug || $name === '' || $this->isSelfRepo($row)) {
            return null;
        }

        return [
            'slug' => $slug,
            'name' => $name,
            'description' => is_string($row['description'] ?? null) ? Text::plain($row['description'], 500) : '',
            'version' => is_string($row['version'] ?? null) ? ltrim(Text::plain($row['version'], 30), 'vV') : '',
            'url' => self::DETAILS_PREFIX . $slug,
        ] + self::catalogFields($row);
    }

    /**
     * neue Katalog-Felder, alles optional. Alte Speicherstände ohne die Felder kriegen die Standards
     * @return array{category:string,features:list<string>,tags:list<string>,basenames:list<string>,updateProduct:?string,tier:string,iconUrl:?string,imageUrl:?string,imageAlt:string,accent:?string}
     */
    public static function catalogFields(array $row): array
    {
        $cat = $row['category'] ?? null;
        $image = self::mediaUrl($row['imageUrl'] ?? null);
        $upd = $row['updateProduct'] ?? null;
        $tier = $row['tier'] ?? 'free';

        return [
            'category' => is_string($cat) && in_array($cat, self::CATEGORIES, true) ? $cat : self::CATEGORY_DEFAULT,
            'features' => self::features($row['features'] ?? null),
            // Tags (Feed ab Plattform 1.14.1), fehlt = leer
            'tags' => self::tags($row['tags'] ?? null),
            'basenames' => self::basenames($row['basenames'] ?? null),
            'updateProduct' => is_string($upd) && strlen($upd) <= 100 && preg_match(self::SLUG_RE, $upd) === 1 ? $upd : null,
            // fehlt = kostenlos, kaputter Wert = kostenpflichtig (dann gibts keinen Installieren-Knopf)
            'tier' => $tier === 'free' || $tier === 'paid' ? $tier : 'paid',
            'iconUrl' => self::mediaUrl($row['iconUrl'] ?? null),
            'imageUrl' => $image,
            'imageAlt' => $image !== null && is_string($row['imageAlt'] ?? null) ? Text::plain($row['imageAlt'], 160) : '',
            'accent' => is_string($row['accent'] ?? null) && preg_match(self::ACCENT_RE, $row['accent']) === 1 ? strtolower($row['accent']) : null,
        ];
    }

    public static function mediaUrl(mixed $url): ?string
    {
        // Präfix fest + nur 32 hex .webp dahinter, kein ?, # oder Pfad
        return is_string($url) && str_starts_with($url, self::MEDIA_PREFIX)
            && preg_match(self::MEDIA_FILE_RE, substr($url, strlen(self::MEDIA_PREFIX))) === 1 ? $url : null;
    }

    /** @return list<string> */
    private static function tags(mixed $list): array
    {
        $out = [];
        foreach (is_array($list) ? array_slice(array_values($list), 0, 30) : [] as $t) {
            $t = is_string($t) ? Text::plain($t, self::TAG_LEN) : '';
            if ($t !== '' && !in_array($t, $out, true)) {
                $out[] = $t;
            }
        }

        return array_slice($out, 0, self::MAX_TAGS);
    }

    /** @return list<string> */
    private static function features(mixed $list): array
    {
        $out = [];
        foreach (is_array($list) ? array_slice(array_values($list), 0, 10) : [] as $f) {
            $t = is_string($f) ? Text::plain($f, self::FEATURE_LEN) : '';
            if ($t !== '' && !in_array($t, $out, true)) {
                $out[] = $t;
            }
        }

        return array_slice($out, 0, self::MAX_FEATURES);
    }

    /** @return list<string> */
    private static function basenames(mixed $list): array
    {
        $out = [];
        foreach (is_array($list) ? array_slice(array_values($list), 0, 20) : [] as $b) {
            if (is_string($b) && strlen($b) <= 200 && preg_match(self::BASENAME_RE, $b) === 1 && !in_array($b, $out, true)) {
                $out[] = $b;
            }
        }

        return array_slice($out, 0, self::MAX_BASENAMES);
    }

    // alte Listen ohne Katalog-Kennung > eigenes Plugin übers Repo erkennen. Sammelrepos nie (sonst fliegen Suche + Notification mit raus, Befund theme)
    private function isSelfRepo(array $row): bool
    {
        if ($this->selfRepo === '' || in_array($this->selfRepo, self::SHARED_REPOS, true)) {
            return false;
        }
        foreach (self::REPO_FIELDS as $f) {
            $v = is_string($row[$f] ?? null) ? strtolower(rtrim(trim($row[$f]), '/')) : '';
            if ($v !== '' && ($v === $this->selfRepo || str_ends_with($v, '/' . $this->selfRepo))) {
                return true;
            }
        }

        return false;
    }

    private function now(): int
    {
        try {
            return (int) ($this->clock)();
        } catch (\Throwable) {
            return time();
        }
    }

    private function fallbackItems(): array
    {
        try {
            return $this->clean($this->fallback);
        } catch (\Throwable) {
            return [];
        }
    }

    private function state(): array
    {
        try {
            return $this->store->load();
        } catch (\Throwable) {
            return [];
        }
    }

    private function save(array $st): void
    {
        try {
            $this->store->save($st);
        } catch (\Throwable) {
            // Speichern kaputt, egal - nächster Aufruf fragt halt neu
        }
    }
}
