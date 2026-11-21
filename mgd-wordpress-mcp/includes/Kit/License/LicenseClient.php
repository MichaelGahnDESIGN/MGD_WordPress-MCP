<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\License;

use Mgd\WordPressMcp\Kit\Install\{Response, Store};

// Lizenz-Baustein (docs/PLUGIN-BAUKASTEN.md). Standard AUS: ohne $enabled kein Netz, kein UI, isPro() = false
// POST /api/license/verify, Token nur im Bearer-Header, Body nur die Domain. Nur 200 + valid = Pro
// Cache max 24 Std, Server weg > 48 Std Gnadenfrist, klares Nein (401/403/valid false) > sofort Free
final class LicenseClient
{
    public const URL = 'https://theme.michael-gahn.de/api/license/verify';
    public const DAY = 86400;
    public const GRACE = 172800;
    public const NEGATIVE_TTL = 300;
    public const RETRY = 120;
    public const TOKEN_RE = '/^[A-Za-z0-9_.-]{20,5000}$/D';
    public const DOMAIN_RE = '/^[a-z0-9](?:[a-z0-9.-]{0,251}[a-z0-9])?$/D';
    public const TIERS = ['free', 'plus', 'pro', 'agency'];

    private readonly bool $enabled;
    private readonly \Closure $now;
    /** @var array{valid:bool,tier:string,reason:string,source:string}|null */
    private ?array $memo = null;

    public function __construct(
        private readonly ?HttpPost $http,
        private readonly Store $store,
        private readonly string $domain,
        bool $enabled = false,
        ?\Closure $now = null,
    ) {
        $this->enabled = $enabled && $http !== null && preg_match(self::DOMAIN_RE, $domain) === 1;
        $this->now = $now ?? static fn (): int => time();
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function isPro(): bool
    {
        $s = $this->state();

        return $s['valid'] && $s['tier'] !== 'free';
    }

    public function status(): string
    {
        return LicenseTexts::status($this->state());
    }

    public function hasToken(): bool
    {
        return $this->enabled && $this->token() !== '';
    }

    /** nur die letzten 4 Zeichen, für die Anzeige */
    public function tokenHint(): string
    {
        $t = $this->enabled ? $this->token() : '';

        return $t === '' ? '' : '…' . substr($t, -4);
    }

    /** false = aus oder Schlüssel sieht falsch aus, dann wird nix gespeichert */
    public function setToken(string $token): bool
    {
        $token = trim($token);
        if (!$this->enabled || preg_match(self::TOKEN_RE, $token) !== 1) {
            return false;
        }
        $this->store->save(['token' => $token]);
        $this->memo = null;

        return true;
    }

    public function forget(): void
    {
        if ($this->enabled) {
            $this->store->save([]);
        }
        $this->memo = null;
    }

    /** @return array{valid:bool,tier:string,reason:string,source:string} */
    public function state(): array
    {
        try {
            return $this->memo ??= $this->resolve();
        } catch (\Throwable) {
            return self::free('unreachable');
        }
    }

    private function resolve(): array
    {
        if (!$this->enabled) {
            return self::free('disabled');
        }
        $st = $this->store->load();
        $token = $this->token($st);
        if ($token === '') {
            return self::free('no_token');
        }
        $now = ($this->now)();
        if ($now < (int) ($st['until'] ?? 0)) {
            $valid = ($st['valid'] ?? false) === true;

            return ['valid' => $valid, 'tier' => $valid ? (string) $st['tier'] : 'free', 'reason' => (string) ($st['reason'] ?? 'ok'), 'source' => 'cache'];
        }
        // Server grad weg > nicht bei jedem Seitenaufruf bis zum Timeout warten
        if ($now < (int) ($st['retry'] ?? 0)) {
            return self::fallback($st, $now);
        }
        $res = $this->ask($token);
        $json = $res === null ? null : json_decode($res->body, true, 8);
        $json = is_array($json) ? $json : [];
        if ($res !== null && $res->status === 200 && ($json['valid'] ?? null) === true) {
            return $this->positive($st, $json, $now);
        }
        if ($res !== null && (in_array($res->status, [401, 403], true) || (($json['valid'] ?? null) === false && $res->status === 200))) {
            return $this->negative($st, $json, $res->status, $now);
        }
        $st['retry'] = $now + self::RETRY;
        $this->store->save($st);

        return self::fallback($st, $now);
    }

    private function ask(string $token): ?Response
    {
        try {
            return $this->http?->post(self::URL, [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ], (string) json_encode(['domain' => $this->domain]));
        } catch (\Throwable) {
            // Fehlertext nie weitergeben, könnte Header enthalten
            return null;
        }
    }

    private function positive(array $st, array $json, int $now): array
    {
        $lic = is_array($json['license'] ?? null) ? $json['license'] : [];
        $tier = is_string($lic['tier'] ?? null) && in_array($lic['tier'], self::TIERS, true) ? $lic['tier'] : '';
        if ($tier === '') {
            return $this->negative($st, ['reason' => 'unknown_tier'], 200, $now);
        }
        $until = strtotime((string) ($json['cache_valid_until'] ?? '')) ?: $now + 3600;
        $until = max($now + 60, min($until, $now + self::DAY));
        // Abo verlängert > neuer Schlüssel vom Server
        $renewed = $json['renewed_token'] ?? null;
        $token = is_string($renewed) && preg_match(self::TOKEN_RE, $renewed) === 1 ? $renewed : $this->token($st);
        $this->store->save(['token' => $token, 'valid' => true, 'tier' => $tier, 'reason' => 'ok', 'until' => $until, 'ok_until' => $until, 'ok_tier' => $tier]);

        return ['valid' => true, 'tier' => $tier, 'reason' => 'ok', 'source' => 'live'];
    }

    // klares Nein: Gnadenfrist weg, sofort Free
    private function negative(array $st, array $json, int $status, int $now): array
    {
        $reason = is_string($json['reason'] ?? null) && preg_match('/^[a-z_]{1,40}$/D', $json['reason']) === 1
            ? $json['reason'] : ($status === 401 ? 'token_malformed' : 'invalid');
        $this->store->save(['token' => $this->token($st), 'valid' => false, 'tier' => 'free', 'reason' => $reason, 'until' => $now + self::NEGATIVE_TTL]);

        return ['valid' => false, 'tier' => 'free', 'reason' => $reason, 'source' => 'live'];
    }

    private static function fallback(array $st, int $now): array
    {
        $okUntil = (int) ($st['ok_until'] ?? 0);
        $tier = (string) ($st['ok_tier'] ?? '');
        if ($okUntil > 0 && $now < $okUntil + self::GRACE && in_array($tier, self::TIERS, true)) {
            return ['valid' => true, 'tier' => $tier, 'reason' => 'ok', 'source' => 'grace'];
        }

        return self::free('unreachable');
    }

    private function token(?array $st = null): string
    {
        $t = ($st ?? $this->store->load())['token'] ?? '';

        return is_string($t) && preg_match(self::TOKEN_RE, $t) === 1 ? $t : '';
    }

    /** @return array{valid:bool,tier:string,reason:string,source:string} */
    private static function free(string $reason): array
    {
        return ['valid' => false, 'tier' => 'free', 'reason' => $reason, 'source' => 'none'];
    }
}
