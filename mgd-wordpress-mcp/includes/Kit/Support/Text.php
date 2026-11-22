<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Support;

// Text-Helfer: Klartext kürzen + HTML escapen, geht auch ohne mbstring
final class Text
{
    public static function plain(string $s, int $max): string
    {
        $s = (string) preg_replace('/[\x00-\x1f\x7f]/', ' ', strip_tags($s));

        return trim(function_exists('mb_substr') ? mb_substr($s, 0, $max, 'UTF-8') : substr($s, 0, $max));
    }

    public static function len(string $s): int
    {
        return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
    }

    public static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
