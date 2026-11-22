<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Update;

use Mgd\WordPressMcp\Kit\Support\Text;

// Update-JSON schema 1 streng prüfen, gleiche Regeln wie bin/update-simple-file.php. Unbekannte Felder egal
// alles Kaputte = null, nie ein Fehler nach außen
final class UpdateInfo
{
    public const MAX_JSON = 65536;
    public const MAX_ZIP = 52428800; // 50 MB
    public const VERSION_RE = '/^[0-9]{1,5}\.[0-9]{1,5}\.[0-9]{1,5}$/D';

    private function __construct(
        public readonly string $product,
        public readonly string $version,
        public readonly string $filename,
        public readonly int $size,
        public readonly string $sha256,
        public readonly string $rootDir,
        public readonly string $downloadUrl,
        public readonly string $changelog,
        public readonly ?Notice $notice,
    ) {
    }

    public static function parse(mixed $json, string $product): ?self
    {
        try {
            return self::build($json, $product);
        } catch (\Throwable) {
            return null;
        }
    }

    public function isNewerThan(string $installed): bool
    {
        return preg_match(self::VERSION_RE, $installed) === 1 && version_compare($this->version, $installed, '>');
    }

    /** nur Release-Assets im eigenen GitHub-Account, kein ?, #, user@, Port oder .. */
    public static function isReleaseUrl(string $url): bool
    {
        if (strlen($url) > 500 || preg_match('/[\x00-\x20\x7f\\\\?#]/', $url) === 1) {
            return false;
        }
        $p = parse_url($url);
        if (!is_array($p) || ($p['scheme'] ?? '') !== 'https' || ($p['host'] ?? '') !== 'github.com'
            || isset($p['query']) || isset($p['user']) || isset($p['pass']) || isset($p['port']) || isset($p['fragment'])) {
            return false;
        }
        $path = (string) ($p['path'] ?? '');

        return !str_contains($path, '..')
            && preg_match('#^/MichaelGahnDESIGN/[A-Za-z0-9_.-]+/releases/download/[A-Za-z0-9_.+-]+/[A-Za-z0-9_.-]+\.zip$#D', $path) === 1;
    }

    private static function build(mixed $json, string $product): ?self
    {
        if (!is_string($json) || $json === '' || strlen($json) > self::MAX_JSON || $product === '') {
            return null;
        }
        $d = json_decode($json, true, 32);
        if (!is_array($d) || ($d['schema'] ?? null) !== 1 || ($d['product'] ?? null) !== $product) {
            return null;
        }
        $version = $d['version'] ?? null;
        $file = $d['filename'] ?? null;
        $size = $d['size'] ?? null;
        $sha = $d['sha256'] ?? null;
        $root = $d['root_dir'] ?? null;
        $url = $d['download_url'] ?? null;
        if (!is_string($version) || preg_match(self::VERSION_RE, $version) !== 1) {
            return null;
        }
        if (!is_string($file) || preg_match('/^[A-Za-z0-9._-]{1,120}\.zip$/D', $file) !== 1) {
            return null;
        }
        if (!is_int($size) || $size < 1 || $size > self::MAX_ZIP || !is_string($sha) || preg_match('/^[a-f0-9]{64}$/D', $sha) !== 1) {
            return null;
        }
        if (!is_string($root) || preg_match('/^[A-Za-z0-9_.-]{1,80}$/D', $root) !== 1 || trim($root, '.') === '') {
            return null;
        }
        if (!is_string($url) || !self::isReleaseUrl($url) || basename($url) !== $file) {
            return null;
        }
        $log = is_string($d['changelog'] ?? null) ? Text::plain($d['changelog'], 300) : '';

        // kaputter Hinweis killt nicht das Update, der fällt dann halt weg
        return new self($product, $version, $file, $size, $sha, $root, $url, $log, Notice::fromArray($d['notice'] ?? null));
    }
}
