<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Catalog;

// Katalog-Bilder Server > Server holen + lokal ablegen (JTL + Shopware), Muster aus Platform/WordPress/CatalogMedia
// Browser vom Admin lädt nie was von extern. Nur theme.michael-gahn.de/uploads/catalog/<32 hex>.webp, echtes WebP. Wirft nie
final class MediaCache
{
    public const FILE_RE = '/^[a-f0-9]{32}\.webp$/D';
    public const MAX_BYTES = 614400;
    public const MAX_SIDE = 2000;
    public const MAX_FAILED = 2;
    // pro Seitenaufruf max so viele neue Bilder, Rest beim nächsten mal
    public const MAX_NEW = 8;
    public const PAUSE = 600;
    public const KEEP_DAYS = 14;
    public const PAUSE_FILE = 'pause';
    // öffentlicher Ordner: nur unsere Bilder ausliefern, sonst nix (Apache)
    public const HTACCESS = "<IfModule mod_authz_core.c>\n<FilesMatch \"^(?![a-f0-9]{32}\\.webp$)\">\nRequire all denied\n</FilesMatch>\n</IfModule>\nOptions -Indexes\n";

    private int $failed = 0;
    private int $new = 0;
    /** @var \Closure():int */
    private \Closure $clock;

    /**
     * @param \Closure(string,int):array{status:int,body:string} $fetch holt eine URL ohne Weiterleitung (Tests: Attrappe)
     * @param string $baseUrl öffentliche Adresse vom Ordner ('' = nicht per URL erreichbar, z. B. Shopware var/)
     */
    public function __construct(
        private readonly string $dir,
        private readonly \Closure $fetch,
        private readonly string $baseUrl = '',
        ?\Closure $clock = null,
    ) {
        $this->clock = $clock ?? static fn (): int => time();
    }

    /** Bild holen falls noch nicht da. Dateiname zurück, sonst leer (dann Farbfläche) */
    public function sync(mixed $url): string
    {
        try {
            $ok = Recommendations::mediaUrl($url);
            if ($ok === null || $this->dir === '') {
                return '';
            }
            $name = basename($ok);
            $target = $this->dir . '/' . $name;
            if (is_file($target)) {
                return $name;
            }
            if ($this->failed >= self::MAX_FAILED || $this->new >= self::MAX_NEW || $this->paused()) {
                return '';
            }
            ++$this->new;
            $r = ($this->fetch)($ok, self::MAX_BYTES + 1);
            $body = is_array($r) && (int) ($r['status'] ?? 0) === 200 && is_string($r['body'] ?? null) ? $r['body'] : '';
            if (!self::isWebp($body) || !$this->prepare()) {
                $this->fail();

                return '';
            }
            $tmp = $target . '.tmp';
            if (file_put_contents($tmp, $body, LOCK_EX) === false || !rename($tmp, $target)) {
                @unlink($tmp);
                $this->fail();

                return '';
            }
            @chmod($target, 0644);

            return $name;
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * alle Bilder einer Liste holen, Aufräumen nur bei frischer Liste
     * @param list<array<string,mixed>> $items
     */
    public function syncItems(array $items, bool $fresh): void
    {
        $keep = [];
        foreach ($items as $it) {
            foreach (['iconUrl', 'imageUrl'] as $k) {
                $n = is_array($it) ? $this->sync($it[$k] ?? null) : '';
                if ($n !== '') {
                    $keep[] = $n;
                }
            }
        }
        if ($fresh) {
            $this->prune($keep);
        }
    }

    /** Katalog-URL > Dateiname, nur wenn lokal da */
    public function local(mixed $url): string
    {
        $ok = Recommendations::mediaUrl($url);

        return $ok !== null && $this->has(basename($ok)) ? basename($ok) : '';
    }

    public function has(mixed $name): bool
    {
        return is_string($name) && preg_match(self::FILE_RE, $name) === 1 && $this->dir !== '' && is_file($this->dir . '/' . $name);
    }

    /** öffentliche Adresse, nur wenn Datei da + Ordner per URL erreichbar */
    public function url(mixed $name): string
    {
        return $this->baseUrl !== '' && $this->has($name) ? rtrim($this->baseUrl, '/') . '/' . $name : '';
    }

    /** Inhalt nochmal geprüft, sonst leer */
    public function read(mixed $name): string
    {
        try {
            if (!$this->has($name) || (int) filesize($this->dir . '/' . $name) > self::MAX_BYTES) {
                return '';
            }
            $body = (string) file_get_contents($this->dir . '/' . $name);

            return self::isWebp($body) ? $body : '';
        } catch (\Throwable) {
            return '';
        }
    }

    public function dataUri(mixed $name): string
    {
        $b = $this->read($name);

        return $b !== '' ? 'data:image/webp;base64,' . base64_encode($b) : '';
    }

    /** alte Dateien weg die nicht mehr im Katalog stehen (erst nach 14 Tagen) @param list<string> $keep */
    public function prune(array $keep): void
    {
        try {
            if ($this->dir === '' || !is_dir($this->dir)) {
                return;
            }
            $limit = ($this->clock)() - self::KEEP_DAYS * 86400;
            foreach ((array) glob($this->dir . '/*.webp') as $file) {
                if (is_string($file) && !in_array(basename($file), $keep, true) && (int) filemtime($file) < $limit) {
                    @unlink($file);
                }
            }
        } catch (\Throwable) {
        }
    }

    /** Ordner komplett weg (Deinstallieren mit Daten löschen), nur eigene Dateien, Symlinks nie folgen */
    public function wipe(): void
    {
        try {
            if ($this->dir === '' || !is_dir($this->dir) || is_link($this->dir)) {
                return;
            }
            foreach ((array) scandir($this->dir) as $f) {
                if (is_string($f) && (preg_match('/^[a-f0-9]{32}\.webp(\.tmp)?$/D', $f) === 1 || in_array($f, ['.htaccess', 'index.html', self::PAUSE_FILE], true))) {
                    @unlink($this->dir . '/' . $f);
                }
            }
            @rmdir($this->dir);
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

    /** cURL: nur https, keine Weiterleitung, Größe begrenzt @return \Closure(string,int):array{status:int,body:string} */
    public static function curl(string $userAgent): \Closure
    {
        return static function (string $url, int $max) use ($userAgent): array {
            if (!function_exists('curl_init') || Recommendations::mediaUrl($url) === null) {
                return ['status' => 0, 'body' => ''];
            }
            $body = '';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_USERAGENT => $userAgent,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_HTTPHEADER => ['Accept: image/webp'],
                CURLOPT_WRITEFUNCTION => static function ($c, string $chunk) use (&$body, $max): int {
                    $body .= $chunk;

                    // zu groß > abbrechen
                    return strlen($body) > $max ? -1 : strlen($chunk);
                },
            ]);
            $ok = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

            return ['status' => $ok === false ? 0 : $status, 'body' => $ok === false ? '' : $body];
        };
    }

    // Ordner anlegen + Schutzdateien
    private function prepare(): bool
    {
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0755, true) && !is_dir($this->dir)) {
            return false;
        }
        if (!is_file($this->dir . '/index.html')) {
            @file_put_contents($this->dir . '/index.html', '');
        }
        if ($this->baseUrl !== '' && !is_file($this->dir . '/.htaccess')) {
            @file_put_contents($this->dir . '/.htaccess', self::HTACCESS);
        }

        return is_writable($this->dir);
    }

    private function paused(): bool
    {
        $f = $this->dir . '/' . self::PAUSE_FILE;

        return is_file($f) && (int) file_get_contents($f) > ($this->clock)();
    }

    // nach Fehler 10 Min Ruhe
    private function fail(): void
    {
        ++$this->failed;
        if ($this->prepare()) {
            @file_put_contents($this->dir . '/' . self::PAUSE_FILE, (string) (($this->clock)() + self::PAUSE), LOCK_EX);
        }
    }
}
