<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\License;

use Mgd\WordPressMcp\Kit\Install\{CurlTransport, Response};

// cURL POST, gleiche Grenzen wie der Install-Client: nur https theme.michael-gahn.de, keine Weiterleitung, 3s/5s, max 16 KB
// Fehlertexte ohne Header/Token
final class CurlPost implements HttpPost
{
    public const MAX_BYTES = 16384;

    public function __construct(private readonly string $userAgent)
    {
    }

    public function post(string $url, array $headers, string $body): Response
    {
        if (!CurlTransport::isAllowed($url)) {
            throw new \RuntimeException('Adresse nicht erlaubt.');
        }
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('PHP-Erweiterung curl fehlt.');
        }
        $h = [];
        foreach ($headers as $k => $v) {
            $h[] = $k . ': ' . str_replace(["\r", "\n"], '', $v);
        }
        $out = '';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $h,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => CurlTransport::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => CurlTransport::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => static function ($c, string $chunk) use (&$out): int {
                if (strlen($out) + strlen($chunk) > self::MAX_BYTES) {
                    return -1;
                }
                $out .= $chunk;

                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($ok === false) {
            throw new \RuntimeException('Verbindung fehlgeschlagen (Code ' . curl_errno($ch) . ').');
        }

        return new Response($status, [], $out);
    }
}
