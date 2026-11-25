<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Ui;

// MGD Farbwähler, Teil für alle Plattformen: Prüfung der Farblisten + Dateien zum Einbetten
final class Colors
{
    public const ASSETS = __DIR__ . '/assets/';
    public const MAX_MINE = 24;
    public const MAX_RECENT = 8;

    /** nur #RRGGBB, groß, ohne Doppelte, begrenzt @return list<string> */
    public static function clean(mixed $list, int $max): array
    {
        $out = [];
        foreach (is_array($list) ? $list : [] as $c) {
            $c = is_string($c) ? strtoupper(trim($c)) : '';
            if (preg_match('/^#[0-9A-F]{6}$/D', $c) === 1 && !in_array($c, $out, true)) {
                $out[] = $c;
            }
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }

    public static function js(): string
    {
        return (string) @file_get_contents(self::ASSETS . 'mgd-color.js');
    }

    public static function css(): string
    {
        return (string) @file_get_contents(self::ASSETS . 'mgd-color.css');
    }

    // inline <style>/<script> ohne </ durchzulassen
    public static function inline(string $adapterJs): string
    {
        $css = self::css();
        $js = self::js();
        if ($js === '') {
            return '';
        }

        return ($css !== '' ? '<style>' . str_replace('</', '', $css) . '</style>' : '')
            . '<script>' . str_replace('</', '<\/', $js . "\n" . $adapterJs) . '</script>';
    }
}
