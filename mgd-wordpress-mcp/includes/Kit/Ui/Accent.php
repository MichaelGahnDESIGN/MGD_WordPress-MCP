<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Ui;

// Primärfarbe für alle MGD-Plugins (Kit 1.17), rein + testbar. Standard MGD-Rot aus design/tokens.json
// Kontrast nach WCAG: Schrift auf der Farbe hell/dunkel automatisch, Links auf Weiß notfalls abgedunkelt
final class Accent
{
    public const DEFAULT = '#ce1517';
    public const DARK_TEXT = '#1d2327';
    public const HEX_RE = '/^#[0-9a-f]{6}$/D';
    public const MIN_TEXT = 4.5;
    // WP-Admin-Hintergrund, Links stehen meist darauf
    public const ADMIN_BG = '#f0f0f1';

    /** nur #rrggbb (klein), sonst null */
    public static function clean(mixed $v): ?string
    {
        if (!is_string($v)) {
            return null;
        }
        $v = strtolower(trim($v));

        return preg_match(self::HEX_RE, $v) === 1 ? $v : null;
    }

    public static function luminance(string $hex): float
    {
        $c = array_map(static fn (string $p): float => hexdec($p) / 255, str_split(substr($hex, 1), 2));
        $c = array_map(static fn (float $x): float => $x <= 0.04045 ? $x / 12.92 : (($x + 0.055) / 1.055) ** 2.4, $c);

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    // Schrift auf der Farbe: was besser lesbar ist
    public static function onAccent(string $hex): string
    {
        $w = self::contrast($hex, '#ffffff');
        $d = self::contrast($hex, self::DARK_TEXT);
        if ($w >= self::MIN_TEXT || $d >= self::MIN_TEXT) {
            return $w >= $d ? '#ffffff' : self::DARK_TEXT;
        }

        // Mittelgrau: weder Weiß noch #1d2327 reicht, dann das bessere von Weiß und Schwarz (Befund Prüfung)
        return $w >= self::contrast($hex, '#000000') ? '#ffffff' : '#000000';
    }

    // Link auf weißem Grund: so lange dunkler, bis 4.5:1 erreicht ist
    public static function textOnWhite(string $hex): string
    {
        $c = $hex;
        for ($i = 0; $i < 20 && self::contrast($c, self::ADMIN_BG) < self::MIN_TEXT; ++$i) {
            $c = self::shade($c, 0.85);
        }

        return $c;
    }

    public static function tooLight(string $hex): bool
    {
        return self::contrast($hex, self::ADMIN_BG) < self::MIN_TEXT;
    }

    /** $f < 1 dunkler, > 1 heller (Richtung Weiß) */
    public static function shade(string $hex, float $f): string
    {
        $out = '#';
        foreach (str_split(substr($hex, 1), 2) as $p) {
            $v = hexdec($p);
            $v = $f < 1 ? $v * $f : $v + (255 - $v) * ($f - 1);
            $out .= sprintf('%02x', max(0, min(255, (int) round($v))));
        }

        return $out;
    }

    // Hover: weiße Schrift > dunkler (Kontrast steigt), dunkle Schrift > heller
    public static function hover(string $hex): string
    {
        return self::onAccent($hex) === '#ffffff' ? self::shade($hex, 0.85) : self::shade($hex, 1.15);
    }

    /** CSS-Variablen + Menüblock MGD Tools, alle Werte vorher geprüft */
    public static function css(?string $hex): string
    {
        $a = self::clean($hex) ?? self::DEFAULT;
        $on = self::onAccent($a);
        $h = self::hover($a);
        $t = self::textOnWhite($a);
        $m = '#adminmenu li#toplevel_page_mgd-tools';

        $onH = self::onAccent($h);

        return ':root{--mgd-accent:' . $a . ';--mgd-accent-hover:' . $h . ';--mgd-on-accent:' . $on . ';--mgd-on-accent-hover:' . $onH . ';--mgd-accent-text:' . $t . '}'
            // Menüpunkt immer als Block in der Primärfarbe (Wunsch Michael, wie "Multichannel")
            . $m . '>a.menu-top,' . $m . '>a.menu-top:focus{background:var(--mgd-accent);color:var(--mgd-on-accent);font-weight:600}'
            . $m . '>a.menu-top:hover{background:var(--mgd-accent-hover);color:var(--mgd-on-accent-hover)}'
            . $m . '>a.menu-top:hover .wp-menu-name,' . $m . '>a.menu-top:hover .wp-menu-image:before{color:var(--mgd-on-accent-hover)!important}'
            . $m . '>a.menu-top .wp-menu-name{color:var(--mgd-on-accent)}'
            . $m . ' .wp-menu-image img{opacity:1}' . $m . ' .wp-menu-image:before{color:var(--mgd-on-accent)!important}'
            . $m . '>a.menu-top:focus-visible{outline:2px solid var(--mgd-on-accent);outline-offset:-4px}'
            // Untermenü dunkel, aktiver Punkt mit Akzentbalken
            . $m . ' .wp-submenu{background:#1d2327}'
            . $m . ' .wp-submenu li.current a,' . $m . ' .wp-submenu li a:focus{color:#fff;font-weight:600;box-shadow:inset 4px 0 0 var(--mgd-accent)}'
            . $m . ' .wp-submenu a:hover{color:#fff}'
            . $m . '.wp-has-current-submenu .wp-menu-arrow div,' . $m . '.wp-has-current-submenu .wp-menu-arrow{background:var(--mgd-accent)}'
            // MGD-Seiten: Hauptknopf + Links in der Primärfarbe
            . '.mgd-tools .button-primary,.mgd-tools .button-primary:focus{background:var(--mgd-accent);border-color:var(--mgd-accent);color:var(--mgd-on-accent)}'
            . '.mgd-tools .button-primary:hover{background:var(--mgd-accent-hover);border-color:var(--mgd-accent-hover);color:var(--mgd-on-accent-hover)}'
            . '.mgd-tools a:not(.button):not(.mgd-kit-catalog__button){color:var(--mgd-accent-text)}';
    }
}
