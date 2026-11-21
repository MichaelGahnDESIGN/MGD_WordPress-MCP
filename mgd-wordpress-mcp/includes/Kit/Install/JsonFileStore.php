<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Install;

// Zustand als JSON-Datei im Plugin-Datenordner (atomar, nur Besitzer)
final class JsonFileStore implements Store
{
    public function __construct(private readonly string $file)
    {
    }

    public function load(): array
    {
        $raw = is_file($this->file) ? @file_get_contents($this->file) : false;
        $d = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($d) ? $d : [];
    }

    public function save(array $state): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \RuntimeException('Datenordner nicht anlegbar.');
        }
        $tmp = $this->file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($tmp, (string) json_encode($state, JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
            throw new \RuntimeException('Zustand nicht speicherbar.');
        }
        @chmod($tmp, 0600);
        if (!@rename($tmp, $this->file)) {
            @unlink($tmp);
            throw new \RuntimeException('Zustand nicht speicherbar.');
        }
    }
}
