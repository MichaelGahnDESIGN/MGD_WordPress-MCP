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
    // Berechtigung = Produkt-SKU oder Theme-Modul, z.B. mgd-maintenance-pro
    public const ENTITLEMENT_RE = '/^[a-z0-9]+(?:[-:][a-z0-9]+)*$/D';
    public const MAX_ENTITLEMENTS = 200;

    private readonly bool $enabled;
    private readonly \Closure $now;
    /** @var array{valid:bool,tier:string,reason:string,source:string,entitlements:list<string>}|null */
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

    // Pro-Funktion X freigeschaltet? Nur was der Server in entitlements schickt, sonst nein
    public function has(string $entitlement): bool
    {
        $s = $this->state();

        return $s['valid'] && in_array($entitlement, $s['entitlements'], true);
    }

    /** @return list<string> */
    public function entitlements(): array
    {
        $s = $this->state();

        return $s['valid'] ? $s['entitlements'] : [];
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

    /** Ablauf der Lizenz (Unix-Zeit), 0 = unbekannt oder keine gültige Lizenz */
    public function periodEnd(): int
    {
        if (!$this->enabled || !$this->state()['valid']) {
            return 0;
        }
        $end = $this->store->load()['end'] ?? 0;

        return is_int($end) && $end > 0 ? $end : 0;
    }

    /** wann zuletzt beim Server gefragt, 0 = noch nie */
    public function checkedAt(): int
    {
        $c = $this->enabled ? ($this->store->load()['checked'] ?? 0) : 0;

        return is_int($c) && $c > 0 ? $c : 0;
    }

    // Jetzt prüfen: Cache + Wartezeit weg, Schlüssel und Gnadenfrist bleiben
    public function refresh(): void
    {
        if (!$this->enabled) {
            return;
        }
        $st = $this->store->load();
        if (is_array($st) && $st !== []) {
            unset($st['until'], $st['retry']);
            $this->store->save($st);
        }
        $this->memo = null;
    }

    /** @return array{valid:bool,tier:string,reason:string,source:string,entitlements:list<string>} */
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

            return ['valid' => $valid, 'tier' => $valid ? (string) $st['tier'] : 'free', 'reason' => (string) ($st['reason'] ?? 'ok'), 'source' => 'cache', 'entitlements' => $valid ? self::cleanList($st['ent'] ?? []) : []];
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
        $ent = self::cleanList($lic['entitlements'] ?? []);
        // Ablaufdatum fürs Anzeigen (MGD Tools), kaputt/fehlt = 0
        $end = is_string($lic['period_end'] ?? null) ? (int) strtotime($lic['period_end']) : 0;
        $this->store->save(['token' => $token, 'valid' => true, 'tier' => $tier, 'reason' => 'ok', 'until' => $until, 'ok_until' => $until, 'ok_tier' => $tier, 'ent' => $ent, 'ok_ent' => $ent, 'end' => max(0, $end), 'checked' => $now]);

        return ['valid' => true, 'tier' => $tier, 'reason' => 'ok', 'source' => 'live', 'entitlements' => $ent];
    }

    // klares Nein: Gnadenfrist weg, sofort Free
    private function negative(array $st, array $json, int $status, int $now): array
    {
        $reason = is_string($json['reason'] ?? null) && preg_match('/^[a-z_]{1,40}$/D', $json['reason']) === 1
            ? $json['reason'] : ($status === 401 ? 'token_malformed' : 'invalid');
        $this->store->save(['token' => $this->token($st), 'valid' => false, 'tier' => 'free', 'reason' => $reason, 'until' => $now + self::NEGATIVE_TTL, 'checked' => $now]);

        return ['valid' => false, 'tier' => 'free', 'reason' => $reason, 'source' => 'live', 'entitlements' => []];
    }

    private static function fallback(array $st, int $now): array
    {
        $okUntil = (int) ($st['ok_until'] ?? 0);
        $tier = (string) ($st['ok_tier'] ?? '');
        if ($okUntil > 0 && $now < $okUntil + self::GRACE && in_array($tier, self::TIERS, true)) {
            return ['valid' => true, 'tier' => $tier, 'reason' => 'ok', 'source' => 'grace', 'entitlements' => self::cleanList($st['ok_ent'] ?? [])];
        }

        return self::free('unreachable');
    }

    private function token(?array $st = null): string
    {
        $t = ($st ?? $this->store->load())['token'] ?? '';

        return is_string($t) && preg_match(self::TOKEN_RE, $t) === 1 ? $t : '';
    }

    /** @return array{valid:bool,tier:string,reason:string,source:string,entitlements:list<string>} */
    private static function free(string $reason): array
    {
        return ['valid' => false, 'tier' => 'free', 'reason' => $reason, 'source' => 'none', 'entitlements' => []];
    }

    // nur saubere Namen, max 64 Zeichen, max 200 Stück, doppelte raus
    /** @return list<string> */
    private static function cleanList(mixed $list): array
    {
        if (!is_array($list)) {
            return [];
        }
        $out = [];
        foreach ($list as $e) {
            if (is_string($e) && strlen($e) <= 64 && preg_match(self::ENTITLEMENT_RE, $e) === 1) {
                $out[$e] = $e;
            }
            if (count($out) >= self::MAX_ENTITLEMENTS) {
                break;
            }
        }

        return array_values($out);
    }
}
