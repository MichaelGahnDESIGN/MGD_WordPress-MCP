<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\License;

use Mgd\WordPressMcp\Kit\Install\{Response, Store};

// Pro-Pakete holen + prüfen > docs/PRO-CONTENT-API.md Abschnitt 1. Standard AUS
// Token aus dem Store vom LicenseClient, öffentliche Schlüssel FEST eingebaut (kid => base64), nie vom Server übernehmen
// Speichern + Rückfall auf Free macht das Plugin, hier nur holen, prüfen, klarer Status
final class ProContentClient
{
    public const URL_PREFIX = 'https://theme.michael-gahn.de/api/pro-content/';
    public const MAX_BYTES = 4194304;
    public const TIMEOUT = 15;
    public const MAX_TTL = 604800;
    public const SLUG_RE = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';
    public const FILE_RE = '/^[a-z0-9][a-z0-9_-]{0,80}\.(css|json|webp|png|jpg|woff2)$/D';
    public const TYPES = ['css' => 'text/css', 'json' => 'application/json', 'webp' => 'image/webp', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'woff2' => 'font/woff2'];

    // Status für das Plugin
    public const OK = 'ok';             // Dateien geprüft, speichern bis exp
    public const DELETE = 'delete';     // klares Nein (403): Pro-Dateien löschen, Rückfall auf Free
    public const KEEP = 'keep';         // Server weg/429/503/500/kaputt: vorhandenes Paket bis exp behalten
    public const TOO_OLD = 'too_old';   // 409: erst Plugin aktualisieren
    public const NOT_FOUND = 'not_found';
    public const OFF = 'off';           // aus oder kein Schlüssel

    private readonly bool $enabled;
    private readonly \Closure $now;
    /** @var array<string,string> kid => Rohschlüssel 32 Byte */
    private readonly array $keys;

    /** @param array<string,string> $keys kid => öffentlicher Schlüssel base64 */
    public function __construct(
        private readonly ?HttpPost $http,
        private readonly Store $licenseStore,
        private readonly string $domain,
        array $keys,
        bool $enabled = false,
        ?\Closure $now = null,
    ) {
        $clean = [];
        foreach ($keys as $kid => $b64) {
            $raw = is_string($b64) ? base64_decode($b64, true) : false;
            if (is_string($kid) && preg_match('/^[a-f0-9]{16}$/D', $kid) === 1 && is_string($raw) && strlen($raw) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
                $clean[$kid] = $raw;
            }
        }
        $this->keys = $clean;
        $this->enabled = $enabled && $http !== null && $clean !== [] && function_exists('sodium_crypto_sign_verify_detached')
            && preg_match(LicenseClient::DOMAIN_RE, $domain) === 1;
        $this->now = $now ?? static fn (): int => time();
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array{status:string,reason?:string,min_plugin_version?:string,version?:string,iat?:int,exp?:int,watermark?:string,files?:list<array{name:string,type:string,bytes:string}>,contents?:array<string,string>}
     */
    public function fetch(string $product, string $package, string $pluginVersion): array
    {
        try {
            return $this->run($product, $package, $pluginVersion);
        } catch (\Throwable) {
            return ['status' => self::KEEP, 'reason' => 'error'];
        }
    }

    private function run(string $product, string $package, string $pluginVersion): array
    {
        if (!$this->enabled) {
            return ['status' => self::OFF, 'reason' => 'disabled'];
        }
        if (strlen($product) > 64 || strlen($package) > 64 || preg_match(self::SLUG_RE, $product) !== 1 || preg_match(self::SLUG_RE, $package) !== 1
            || preg_match('/^\d{1,4}\.\d{1,4}\.\d{1,4}$/D', $pluginVersion) !== 1) {
            return ['status' => self::KEEP, 'reason' => 'bad_call'];
        }
        $token = $this->licenseStore->load()['token'] ?? '';
        if (!is_string($token) || preg_match(LicenseClient::TOKEN_RE, $token) !== 1) {
            return ['status' => self::OFF, 'reason' => 'no_token'];
        }
        $res = $this->ask($product, $package, $token, $pluginVersion);
        if ($res === null) {
            return ['status' => self::KEEP, 'reason' => 'unreachable'];
        }
        $json = json_decode($res->body, true, 8);
        $json = is_array($json) ? $json : [];

        return match (true) {
            $res->status === 200 => $this->verify($json, $product, $package),
            $res->status === 403 => ['status' => self::DELETE, 'reason' => self::word($json['reason'] ?? $json['error'] ?? 'invalid')],
            $res->status === 409 => ['status' => self::TOO_OLD, 'min_plugin_version' => is_string($json['min_plugin_version'] ?? null) && preg_match('/^\d{1,4}\.\d{1,4}\.\d{1,4}$/D', $json['min_plugin_version']) === 1 ? $json['min_plugin_version'] : ''],
            $res->status === 404 => ['status' => self::NOT_FOUND, 'reason' => 'package_not_found'],
            // 401 heißt hier: Schlüssel fehlt/kaputt > wie klares Nein
            $res->status === 401 => ['status' => self::DELETE, 'reason' => 'token_malformed'],
            default => ['status' => self::KEEP, 'reason' => 'http_' . $res->status],
        };
    }

    private function ask(string $product, string $package, string $token, string $version): ?Response
    {
        try {
            return $this->http?->post(self::URL_PREFIX . $product . '/' . $package, [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ], (string) json_encode(['domain' => $this->domain, 'version' => $version]));
        } catch (\Throwable) {
            return null; // Fehlertext nie weitergeben
        }
    }

    // alles nach Vertrag prüfen, sonst KEEP (nix speichern, altes Paket bleibt bis exp)
    private function verify(array $r, string $product, string $package): array
    {
        $bad = static fn (string $why): array => ['status' => self::KEEP, 'reason' => $why];
        if (($r['alg'] ?? null) !== 'Ed25519' || !is_string($r['kid'] ?? null) || !isset($this->keys[$r['kid']])) {
            return $bad('unknown_key');
        }
        $payload = self::b64url($r['payload'] ?? null);
        $sig = self::b64url($r['sig'] ?? null);
        if ($payload === null || $sig === null || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES
            || !sodium_crypto_sign_verify_detached($sig, $payload, $this->keys[$r['kid']])) {
            return $bad('signature_invalid');
        }
        $p = json_decode($payload, true, 8);
        if (!is_array($p) || ($p['schema'] ?? null) !== 1 || ($p['product'] ?? null) !== $product || ($p['package'] ?? null) !== $package
            || ($p['domain'] ?? null) !== $this->domain || !is_int($p['iat'] ?? null) || !is_int($p['exp'] ?? null)
            || !is_string($p['version'] ?? null) || preg_match('/^\d{1,4}\.\d{1,4}\.\d{1,4}$/D', $p['version']) !== 1
            || !is_array($p['files'] ?? null) || $p['files'] === []) {
            return $bad('payload_invalid');
        }
        $now = ($this->now)();
        if ($p['exp'] <= $now || $p['exp'] - $p['iat'] > self::MAX_TTL || $p['iat'] > $now + 300) {
            return $bad('expired');
        }
        $files = [];
        foreach ($p['files'] as $f) {
            if (!is_array($f) || !is_string($f['name'] ?? null) || preg_match(self::FILE_RE, $f['name'], $m) !== 1
                || ($f['type'] ?? null) !== self::TYPES[$m[1]] || !is_string($f['sha256'] ?? null) || !is_string($f['data'] ?? null)) {
                return $bad('file_invalid');
            }
            $bytes = base64_decode($f['data'], true);
            if ($bytes === false || !hash_equals($f['sha256'], hash('sha256', $bytes))) {
                return $bad('checksum_invalid');
            }
            $files[] = ['name' => $f['name'], 'type' => $f['type'], 'bytes' => $bytes];
        }
        $wm = is_string($p['watermark'] ?? null) && preg_match('/^[a-f0-9]{12}$/D', $p['watermark']) === 1 ? $p['watermark'] : '';

        // contents = [name => inhalt] (Wunsch maintenance), files mit Typ bleibt
        return ['status' => self::OK, 'version' => $p['version'], 'iat' => $p['iat'], 'exp' => $p['exp'], 'watermark' => $wm, 'files' => $files,
            'contents' => array_column($files, 'bytes', 'name')];
    }

    private static function b64url(mixed $s): ?string
    {
        if (!is_string($s) || $s === '' || preg_match('/^[A-Za-z0-9_-]+$/D', $s) !== 1) {
            return null;
        }
        $raw = base64_decode(strtr($s, '-_', '+/'), true);

        return $raw === false ? null : $raw;
    }

    private static function word(mixed $v): string
    {
        return is_string($v) && preg_match('/^[a-z_]{1,40}$/D', $v) === 1 ? $v : 'invalid';
    }
}
