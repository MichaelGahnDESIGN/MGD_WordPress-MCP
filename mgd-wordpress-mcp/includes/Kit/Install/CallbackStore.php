<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Install;

// Zustand über Callbacks speichern (WP-Option, Shopware SystemConfig, JTL Plugin-Einstellung ...). Wert = JSON-Text
final class CallbackStore implements Store
{
    /**
     * @param \Closure():mixed $get liefert den gespeicherten JSON-Text (oder null)
     * @param \Closure(string):void $set speichert den JSON-Text
     */
    public function __construct(private readonly \Closure $get, private readonly \Closure $set)
    {
    }

    public function load(): array
    {
        $raw = ($this->get)();
        $d = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);

        return is_array($d) ? $d : [];
    }

    public function save(array $state): void
    {
        ($this->set)((string) json_encode($state, JSON_UNESCAPED_SLASHES));
    }
}
