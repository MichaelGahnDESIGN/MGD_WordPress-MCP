<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Install;

// cURL: nur https auf theme.michael-gahn.de, keine Weiterleitungen, 3s Verbindung / 5s gesamt, max 64 KB
final class CurlTransport implements Transport
{
    public const HOST = 'theme.michael-gahn.de';
    public const CONNECT_TIMEOUT = 3;
    public const TIMEOUT = 5;
    public const MAX_BYTES = 65536;

    public function __construct(private readonly string $userAgent)
    {
    }

    public static function isAllowed(string $url): bool
    {
        $p = parse_url($url);

        return is_array($p) && ($p['scheme'] ?? '') === 'https' && strtolower((string) ($p['host'] ?? '')) === self::HOST
            && !isset($p['user']) && !isset($p['pass']) && (!isset($p['port']) || $p['port'] === 443);
    }

    public function send(string $method, string $url, array $headers): Response
    {
        if (!self::isAllowed($url)) {
            throw new \RuntimeException('Adresse nicht erlaubt.');
        }
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('PHP-Erweiterung curl fehlt.');
        }
        $h = [];
        foreach ($headers as $k => $v) {
            // keine Zeilenumbrüche in Kopfzeilen
            $h[] = $k . ': ' . str_replace(["\r", "\n"], '', $v);
        }
        $resHeaders = [];
        $body = '';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $h,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_NOBODY => $method === 'HEAD',
            CURLOPT_HEADERFUNCTION => static function ($c, string $line) use (&$resHeaders): int {
                $pos = strpos($line, ':');
                if ($pos !== false) {
                    $resHeaders[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos + 1));
                }

                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => static function ($c, string $chunk) use (&$body): int {
                if (strlen($body) + strlen($chunk) > self::MAX_BYTES) {
                    return -1;
                }
                $body .= $chunk;

                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($ok === false) {
            throw new \RuntimeException('Verbindung fehlgeschlagen (Code ' . curl_errno($ch) . ').');
        }

        return new Response($status, $resHeaders, $body);
    }
}
