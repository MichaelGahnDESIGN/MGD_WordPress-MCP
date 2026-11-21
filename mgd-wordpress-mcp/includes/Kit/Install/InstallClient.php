<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Install;

// Free-Lizenz Anmeldung + Aktiv-Meldung (docs/plans/FREE-LIZENZEN-PLAN.md). Läuft nur im Admin, nie im Shop-Frontend.
// Fehler werden geschluckt, das Plugin funktioniert immer voll weiter
final class InstallClient
{
    public const CLIENT_VERSION = '1.0.0';
    public const BASE_URL = 'https://theme.michael-gahn.de';
    public const PLATFORMS = ['jtl5', 'shopware6', 'woocommerce', 'wordpress'];
    public const DAY = 86400;
    public const RETRY_MIN = 3600;

    /** @var \Closure():int */
    private \Closure $clock;

    public function __construct(
        private readonly Transport $http,
        private readonly Store $store,
        private readonly string $product,
        private readonly string $platform,
        private readonly string $version,
        private readonly string $domain,
        ?\Closure $clock = null,
    ) {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $product) !== 1 || !in_array($platform, self::PLATFORMS, true)) {
            throw new \InvalidArgumentException('Produkt oder Shopsystem ungültig.');
        }
        $this->clock = $clock ?? static fn (): int => time();
    }

    public function product(): string
    {
        return $this->product;
    }

    /** Schalter "Installation melden", voreingestellt an */
    public function isEnabled(): bool
    {
        return ($this->state()['report'] ?? true) !== false;
    }

    public function setEnabled(bool $on): void
    {
        $st = $this->state();
        if (!$on && isset($st['install_id'], $st['key'])) {
            // abschalten = Eintrag auf der Plattform löschen
            try {
                $this->http->send('DELETE', self::BASE_URL . '/api/v1/installs', [
                    'X-MGD-Product' => $this->product,
                    'X-MGD-Install-Id' => (string) $st['install_id'],
                    'Authorization' => 'Bearer ' . $st['key'],
                ]);
            } catch (\Throwable) {
                // egal, Löschfrist räumt sonst nach 12 Monaten auf
            }
        }
        $next = ['report' => $on] + $st;
        if (!$on) {
            unset($next['install_id'], $next['key'], $next['last_ping'], $next['fails'], $next['next_try']);
        }
        $this->save($next);
    }

    /**
     * Update-JSON holen. Mit Schalter an über die zählende Adresse, sonst/bei Ausfall die feste Datei.
     * Gibt den JSON-Text zurück oder null.
     */
    public function fetchUpdates(): ?string
    {
        if ($this->isEnabled() && !$this->inBackoff()) {
            $body = $this->report();
            if ($body !== null) {
                return $body;
            }
        }
        try {
            $r = $this->http->send('GET', self::BASE_URL . '/plugin-updates/' . $this->product . '.json', []);

            return $r->status === 200 ? $r->body : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Aktiv-Meldung, höchstens 1x am Tag. $force = beim Installieren/Aktivieren/Aktualisieren.
     * true = gemeldet
     */
    public function ping(bool $force = false): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }
        $st = $this->state();
        $now = ($this->clock)();
        if (!$force && (((int) ($st['last_ping'] ?? 0)) > $now - self::DAY || $this->inBackoff())) {
            return false;
        }

        return $this->report() !== null;
    }

    /** Statuszeile für die Update-Seite */
    public function status(): string
    {
        $st = $this->state();
        if (!$this->isEnabled()) {
            return 'Meldung ist ausgeschaltet.';
        }
        if (isset($st['last_ping'])) {
            return 'Zuletzt gemeldet: ' . gmdate('d.m.Y', (int) $st['last_ping']) . '.';
        }

        return isset($st['fails']) ? 'Plattform gerade nicht erreichbar, neuer Versuch später.' : 'Noch nicht gemeldet.';
    }

    private function report(): ?string
    {
        $st = $this->state();
        $id = is_string($st['install_id'] ?? null) && self::isUuid($st['install_id']) ? $st['install_id'] : self::uuid();
        $h = [
            'X-MGD-Install-Id' => $id,
            'X-MGD-Platform' => $this->platform,
            'X-MGD-Domain' => $this->domain,
            'X-MGD-Version' => $this->version,
        ];
        if (is_string($st['key'] ?? null) && $st['key'] !== '') {
            $h['Authorization'] = 'Bearer ' . $st['key'];
        }
        $now = ($this->clock)();
        try {
            $r = $this->http->send('GET', self::BASE_URL . '/api/v1/updates/' . $this->product . '.json', $h);
        } catch (\Throwable) {
            $r = null;
        }
        if ($r === null || $r->status === 0 || $r->status >= 500) {
            // Ausfall: 1h, 2h, 4h ... höchstens 1 Tag
            $fails = (int) ($st['fails'] ?? 0) + 1;
            $this->save(['install_id' => $id, 'fails' => $fails, 'next_try' => $now + min(self::DAY, self::RETRY_MIN * 2 ** min($fails - 1, 5))] + $st);

            return null;
        }
        if ($r->status !== 200) {
            // 404/429/3xx: nicht bei jedem Admin-Aufruf neu fragen
            $this->save(['install_id' => $id, 'next_try' => $now + self::DAY] + $st);

            return null;
        }
        $next = ['install_id' => $id, 'last_ping' => $now] + $st;
        unset($next['fails'], $next['next_try']);
        $key = $r->headers['x-mgd-install-key'] ?? '';
        if (preg_match('/^[A-Za-z0-9_-]{20,100}$/D', $key) === 1) {
            $next['key'] = $key;
        } elseif (($r->headers['x-mgd-install-key-invalid'] ?? '') === '1') {
            // Schlüssel passt nicht mehr (z. B. zwei Tabs gleichzeitig) > wegwerfen, nächste Meldung holt einen neuen
            unset($next['key'], $next['last_ping']);
        }
        $this->save($next);

        return $r->body;
    }

    private function inBackoff(): bool
    {
        return ((int) ($this->state()['next_try'] ?? 0)) > ($this->clock)();
    }

    /** @return array<string,mixed> */
    private function state(): array
    {
        try {
            return $this->store->load();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @param array<string,mixed> $st */
    private function save(array $st): void
    {
        try {
            $this->store->save($st);
        } catch (\Throwable) {
            // Speichern kaputt, nächster Admin-Aufruf versucht es wieder
        }
    }

    public static function isUuid(string $s): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $s) === 1;
    }

    private static function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
