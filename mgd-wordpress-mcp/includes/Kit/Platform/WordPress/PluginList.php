<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

// WordPress > Plugins einheitlich: "Einstellungen" vor "Deaktivieren", keine doppelten Details/Autor-Links
final class PluginList
{
    public static function register(string $basename, string $settingsUrl, string $icon = ''): void
    {
        // Icon vor dem Namen in der Plugin-Liste (WP zeigt dort selbst keins, Befund share)
        $css = self::iconCss($basename, $icon);
        if ($css !== '') {
            \add_action('admin_head-plugins.php', static function () use ($css): void {
                echo '<style>' . $css . '</style>';
            });
        }
        \add_filter('plugin_action_links_' . $basename, static fn (mixed $links): mixed => is_array($links) ? self::actions($links, $settingsUrl) : $links, 99);
        \add_filter('plugin_row_meta', static fn (mixed $meta, mixed $file = ''): mixed => $file === $basename && is_array($meta) ? self::meta($meta) : $meta, 99, 2);
    }

    /**
     * eigene Einstellungen-Links raus, einer vorne rein
     * @param array<int|string,mixed> $links
     * @return array<int|string,mixed>
     */
    public static function actions(array $links, string $settingsUrl): array
    {
        $out = [];
        foreach ($links as $k => $l) {
            $t = self::text($l);
            if (in_array($t, ['einstellungen', 'settings', 'lizenz', 'license'], true)) {
                continue;
            }
            $out[$k] = $l;
        }
        if ($settingsUrl === '') {
            return $out;
        }

        return ['mgd_settings' => '<a href="' . \esc_url($settingsUrl) . '">' . ToolsTexts::SETTINGS . '</a>'] + $out;
    }

    /**
     * doppelte Einträge (gleicher Text) raus, Autor nur 1x, Details nur 1x
     * @param array<int|string,mixed> $meta
     * @return array<int|string,mixed>
     */
    public static function meta(array $meta): array
    {
        $seen = [];
        $author = false;
        $details = false;
        $out = [];
        foreach ($meta as $k => $m) {
            $t = self::text($m);
            $isAuthor = str_contains($t, 'michael gahn');
            $isDetails = in_array($t, ['details anzeigen', 'view details', 'plugin-website besuchen', 'visit plugin site'], true);
            if ($t === '' || isset($seen[$t]) || ($isAuthor && $author) || ($isDetails && $details)) {
                continue;
            }
            $seen[$t] = true;
            $author = $author || $isAuthor;
            $details = $details || $isDetails;
            $out[$k] = $m;
        }

        return $out;
    }

    /** CSS nur mit sauberem Basename + Adresse, sonst nix */
    public static function iconCss(string $basename, string $icon): string
    {
        if (preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+\.php$#D', $basename) !== 1 || str_contains($basename, '..') || preg_match('#^https?://[A-Za-z0-9._~:/%-]+$#D', $icon) !== 1) {
            return '';
        }

        return 'tr[data-plugin="' . $basename . '"] .plugin-title strong::before{content:"";display:inline-block;width:20px;height:20px;margin-right:6px;'
            . 'vertical-align:-4px;border-radius:5px;background:url("' . $icon . '") center/cover no-repeat}';
    }

    private static function text(mixed $html): string
    {
        $t = is_string($html) ? html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';

        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $t)));
    }
}
