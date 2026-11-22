<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Catalog\Recommendations;

// Icons + Hintergründe aus dem Katalog lokal cachen > uploads/mgd-kit-catalog (Muster aus Maintenance class-media.php)
// nur Server > Server, der Browser vom Admin lädt nie was von extern (DSGVO). Wirft nie
final class CatalogMedia
{
    public const DIR = 'mgd-kit-catalog';
    public const FILE_RE = '/^[a-f0-9]{32}\.webp$/D';
    public const MAX_BYTES = 614400;
    public const MAX_SIDE = 2000;
    public const MAX_FAILED = 2;
    public const PAUSE = 600; // nach Fehler 10 Min Ruhe
    public const PAUSE_KEY = 'mgd_kit_cat_media_pause';
    public const KEEP_DAYS = 14;

    /** Dateiname aus der Katalog-URL, sonst leer */
    public static function nameFromUrl(mixed $url): string
    {
        $ok = Recommendations::mediaUrl($url);

        return $ok !== null ? basename($ok) : '';
    }

    /** Bild holen falls noch nicht da. Dateiname zurück, bei Fehler leer (dann Farbfläche) */
    public static function sync(mixed $url, int &$failed): string
    {
        try {
            $name = self::nameFromUrl($url);
            $dir = self::dirs()[0];
            if ($name === '' || $dir === '') {
                return '';
            }
            $target = $dir . '/' . $name;
            if (is_file($target)) {
                return $name;
            }
            // zu viele Fehler oder Pause > nicht weiter probieren
            if ($failed >= self::MAX_FAILED || self::paused()) {
                return '';
            }
            $body = self::download((string) $url);
            if ($body === '' || !self::mkdir($dir)) {
                self::fail($failed);

                return '';
            }
            self::guardIndex($dir);
            $tmp = $target . '.tmp';
            if (file_put_contents($tmp, $body, LOCK_EX) === false || !rename($tmp, $target)) {
                @unlink($tmp);
                self::fail($failed);

                return '';
            }

            return $name;
        } catch (\Throwable) {
            return '';
        }
    }

    /** lokale URL, nur wenn die Datei wirklich da ist */
    public static function url(mixed $name): string
    {
        try {
            if (!is_string($name) || preg_match(self::FILE_RE, $name) !== 1) {
                return '';
            }
            [$dir, $base] = self::dirs();

            return $dir !== '' && is_file($dir . '/' . $name) ? $base . '/' . $name : '';
        } catch (\Throwable) {
            return '';
        }
    }

    /** alte Dateien weg die nicht mehr im Katalog stehen (erst nach 14 Tagen) @param list<string> $keep */
    public static function prune(array $keep, ?int $now = null): void
    {
        try {
            $dir = self::dirs()[0];
            if ($dir === '' || !is_dir($dir)) {
                return;
            }
            $limit = ($now ?? time()) - self::KEEP_DAYS * 86400;
            foreach ((array) glob($dir . '/*.webp') as $file) {
                if (is_string($file) && !in_array(basename($file), $keep, true) && (int) filemtime($file) < $limit) {
                    @unlink($file);
                }
            }
        } catch (\Throwable) {
        }
    }

    /** echtes WebP? Magic Bytes, RIFF-Länge, Größe, Maße */
    public static function isWebp(mixed $body): bool
    {
        if (!is_string($body) || strlen($body) < 30 || strlen($body) > self::MAX_BYTES) {
            return false;
        }
        if (substr($body, 0, 4) !== 'RIFF' || substr($body, 8, 4) !== 'WEBP') {
            return false;
        }
        $len = unpack('V', substr($body, 4, 4));
        if (!is_array($len) || strlen($body) - 8 !== $len[1]) {
            return false;
        }
        $info = @getimagesizefromstring($body);

        return is_array($info) && ($info['mime'] ?? '') === 'image/webp' && $info[0] > 0 && $info[1] > 0
            && $info[0] <= self::MAX_SIDE && $info[1] <= self::MAX_SIDE;
    }

    // keine Weiterleitung, Größenlimit, nur 200 + webp
    private static function download(string $url): string
    {
        if (Recommendations::mediaUrl($url) === null || !function_exists('wp_safe_remote_get')) {
            return '';
        }
        $r = \wp_safe_remote_get($url, ['timeout' => 4, 'redirection' => 0, 'limit_response_size' => self::MAX_BYTES + 1, 'headers' => ['Accept' => 'image/webp']]);
        if (\is_wp_error($r) || (int) \wp_remote_retrieve_response_code($r) !== 200) {
            return '';
        }
        $body = \wp_remote_retrieve_body($r);

        return self::isWebp($body) ? (string) $body : '';
    }

    private static function paused(): bool
    {
        return function_exists('get_transient') && (int) \get_transient(self::PAUSE_KEY) > time();
    }

    private static function fail(int &$failed): void
    {
        ++$failed;
        if (function_exists('set_transient')) {
            \set_transient(self::PAUSE_KEY, time() + self::PAUSE, self::PAUSE);
        }
    }

    private static function mkdir(string $dir): bool
    {
        return is_dir($dir) || (function_exists('wp_mkdir_p') ? (bool) \wp_mkdir_p($dir) : @mkdir($dir, 0755, true));
    }

    // [Ordner, URL] im Uploads-Verzeichnis, leer wenn WP keins hat
    /** @return array{0:string,1:string} */
    private static function dirs(): array
    {
        if (!function_exists('wp_upload_dir')) {
            return ['', ''];
        }
        $u = \wp_upload_dir(null, false);
        if (!is_array($u) || !empty($u['error']) || empty($u['basedir']) || empty($u['baseurl'])) {
            return ['', ''];
        }

        return [rtrim((string) $u['basedir'], '/') . '/' . self::DIR, rtrim((string) $u['baseurl'], '/') . '/' . self::DIR];
    }

    // leere index.php gegen Verzeichnislisting
    private static function guardIndex(string $dir): void
    {
        if (!is_file($dir . '/index.php')) {
            @file_put_contents($dir . '/index.php', "<?php\n// Silence is golden.\n");
        }
    }
}
