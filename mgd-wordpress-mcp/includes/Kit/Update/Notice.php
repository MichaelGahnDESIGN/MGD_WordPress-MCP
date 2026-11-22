<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Update;

use Mgd\WordPressMcp\Kit\Support\Text;

// Hinweis für veraltete Versionen aus dem Feld "notice" {text,url,from,below}
// zeigen nur wenn installiert < below und heute >= from
final class Notice
{
    public const MAX_TEXT = 300;
    public const HOST = 'theme.michael-gahn.de';
    public const GITHUB_PATH = '/MichaelGahnDESIGN/';

    private function __construct(
        public readonly string $text,
        public readonly string $url,
        public readonly string $from,
        public readonly string $below,
    ) {
    }

    public static function fromArray(mixed $d): ?self
    {
        if (!is_array($d)) {
            return null;
        }
        $text = $d['text'] ?? null;
        $url = $d['url'] ?? null;
        $from = $d['from'] ?? null;
        $below = $d['below'] ?? null;
        if (!is_string($text) || !is_string($url) || !is_string($from) || !is_string($below)) {
            return null;
        }
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if ($text === '' || Text::len($text) > self::MAX_TEXT || preg_match('/[<>\x00-\x1f\x7f]/', $text) === 1) {
            return null;
        }
        if (!self::isAllowedUrl($url) || !self::isDate($from) || preg_match(UpdateInfo::VERSION_RE, $below) !== 1) {
            return null;
        }

        return new self($text, $url, $from, $below);
    }

    public function shouldShow(string $installed, string $today): bool
    {
        if (preg_match(UpdateInfo::VERSION_RE, $installed) !== 1 || !self::isDate($today)) {
            return false;
        }

        return version_compare($installed, $this->below, '<') && strcmp($today, $this->from) >= 0;
    }

    /** nur https theme.michael-gahn.de oder github.com/MichaelGahnDESIGN, wie im Generator */
    public static function isAllowedUrl(string $url): bool
    {
        if (strlen($url) > 500 || preg_match('/[\x00-\x20\x7f<>"\'\\\\]/', $url) === 1) {
            return false;
        }
        $p = parse_url($url);
        if (!is_array($p) || ($p['scheme'] ?? '') !== 'https' || isset($p['user']) || isset($p['pass']) || isset($p['port']) || isset($p['fragment'])) {
            return false;
        }
        $host = (string) ($p['host'] ?? '');
        $path = (string) ($p['path'] ?? '/');
        if (str_contains($path, '..')) {
            return false;
        }

        return $host === self::HOST || ($host === 'github.com' && str_starts_with($path, self::GITHUB_PATH));
    }

    private static function isDate(string $s): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $s);

        return $d !== false && $d->format('Y-m-d') === $s;
    }
}
