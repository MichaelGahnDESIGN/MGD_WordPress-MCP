<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

// Alte Caches vom vorherigen Updater wegräumen (z. B. mgd_admin_release_*), 1x pro Plugin-Version
// Nur Namen mit mgd_ vorne, * am Ende = Präfix. Install-Client + Kit-Daten sind tabu
// Präfix streng [a-z0-9_], Rest dahinter darf - haben (Slugs wie mgd_admin_release_mgd-share-wordpress)
final class LegacyCleanup
{
    public const NAME_RE = '/^mgd_[a-z0-9_-]{3,150}$/D';
    public const PREFIX_RE = '/^mgd_[a-z0-9_]{3,150}$/D';
    public const REST_RE = '/^[a-z0-9_-]+$/D';
    public const PROTECTED = ['mgd_install_', 'mgd_kit_'];
    public const MAX_ROWS = 500;
    // so legt WP Transients in wp_options ab
    public const WRAP = ['', '_transient_', '_transient_timeout_', '_site_transient_', '_site_transient_timeout_'];

    /**
     * @param list<string> $names
     * @return array{exact:list<string>,prefix:list<string>} nur gültige Einträge
     */
    public static function parse(array $names): array
    {
        $out = ['exact' => [], 'prefix' => []];
        foreach ($names as $n) {
            if (!is_string($n)) {
                continue;
            }
            $isPrefix = str_ends_with($n, '*');
            $base = $isPrefix ? substr($n, 0, -1) : $n;
            if (preg_match($isPrefix ? self::PREFIX_RE : self::NAME_RE, $base) !== 1 || self::isProtected($base)) {
                continue;
            }
            $out[$isPrefix ? 'prefix' : 'exact'][] = $base;
        }

        return $out;
    }

    /** Rückgabe = gelöschte Namen (für Tests) */
    public static function run(array $names, mixed $wpdb = null): array
    {
        $p = self::parse($names);
        $done = [];
        foreach ($p['exact'] as $n) {
            \delete_transient($n);
            \delete_site_transient($n);
            \delete_option($n);
            $done[] = $n;
        }
        foreach ($p['prefix'] as $prefix) {
            foreach (self::matching($prefix, $wpdb) as $opt) {
                \delete_option($opt);
                $done[] = $opt;
            }
        }

        return $done;
    }

    // wp_options per LIKE, danach in PHP nochmal genau gegen den Präfix prüfen
    private static function matching(string $prefix, mixed $wpdb): array
    {
        if (!is_object($wpdb) || !is_string($wpdb->options ?? null) || preg_match('/^[A-Za-z0-9_]+$/D', $wpdb->options) !== 1) {
            return [];
        }
        $likes = [];
        foreach (self::WRAP as $w) {
            $likes[] = $wpdb->esc_like($w . $prefix) . '%';
        }
        $sql = 'SELECT option_name FROM ' . $wpdb->options . ' WHERE ' . implode(' OR ', array_fill(0, count($likes), 'option_name LIKE %s'))
            . ' LIMIT ' . self::MAX_ROWS;
        $rows = $wpdb->get_col($wpdb->prepare($sql, ...$likes));
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $name) {
            if (is_string($name) && self::startsWithWrapped($name, $prefix)) {
                $out[] = $name;
            }
        }

        return $out;
    }

    private static function startsWithWrapped(string $name, string $prefix): bool
    {
        foreach (self::WRAP as $w) {
            $rest = str_starts_with($name, $w . $prefix) ? substr($name, strlen($w)) : null;
            if ($rest !== null && preg_match(self::REST_RE, $rest) === 1 && !self::isProtected($rest)) {
                return true;
            }
        }

        return false;
    }

    // mgd_* oder mgd_ins* würde Install-Client Daten mitnehmen
    private static function isProtected(string $name): bool
    {
        foreach (self::PROTECTED as $p) {
            if (str_starts_with($name, $p) || str_starts_with($p, $name)) {
                return true;
            }
        }

        return false;
    }
}
