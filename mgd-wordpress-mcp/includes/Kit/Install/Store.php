<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Install;

// Kleiner Zustand: report, install_id, key, last_ping, fails, next_try
interface Store
{
    /** @return array<string,mixed> */
    public function load(): array;

    /** @param array<string,mixed> $state */
    public function save(array $state): void;
}
