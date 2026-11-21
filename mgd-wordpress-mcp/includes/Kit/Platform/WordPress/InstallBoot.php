<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Install\Platform\WordPress;

// Install-Client starten, genau 1x pro Produkt. Hinweis "Installation melden" auch nach Update
// Plugins die WordPress::boot noch selbst aufrufen > doppelte Hooks fliegen bei admin_init raus
final class InstallBoot
{
    public const NOTICE_TTL = 86400;

    /** @var array<string,true> */
    private static array $booted = [];

    public static function flag(string $product): string
    {
        return 'mgd_install_notice_' . str_replace('-', '_', $product);
    }

    /** false = schon gestartet */
    public static function start(string $pluginFile, string $product, string $platform, string $version, string $settingsUrl): bool
    {
        if (isset(self::$booted[$product])) {
            return false;
        }
        self::$booted[$product] = true;
        WordPress::boot($pluginFile, $product, $platform, $version, $settingsUrl);
        $basename = \plugin_basename($pluginFile);
        // Deinstallieren: WP bindet vorher die Hauptdatei ein > dieser Hook ist dann schon da
        \add_action('uninstall_' . $basename, static function () use ($product, $platform): void {
            Setup::uninstall($product, $platform);
        });
        \register_activation_hook($pluginFile, static function () use ($pluginFile): void {
            self::registerUninstall($pluginFile);
        });
        \add_action('upgrader_process_complete', static function (mixed $upgrader = null, mixed $extra = []) use ($product, $basename): void {
            self::afterUpgrade($product, $basename, $extra);
        }, 10, 2);
        // prio 0 = vor dem admin_init vom Install-Client
        \add_action('admin_init', static function () use ($product, $version, $pluginFile): void {
            try {
                self::dedupe($product);
                // nach Update ohne neu aktivieren den Uninstall-Hook nachtragen
                if (self::versionChanged($product, $version)) {
                    self::registerUninstall($pluginFile);
                }
            } catch (\Throwable) {
            }
        }, 0);

        return true;
    }

    /** upgrader_process_complete: nur unser Plugin, nur Update */
    public static function afterUpgrade(string $product, string $basename, mixed $extra): bool
    {
        if (!is_array($extra) || ($extra['type'] ?? '') !== 'plugin' || ($extra['action'] ?? '') !== 'update') {
            return false;
        }
        $list = is_array($extra['plugins'] ?? null) ? $extra['plugins'] : [$extra['plugin'] ?? ''];
        if (!in_array($basename, $list, true)) {
            return false;
        }
        \set_transient(self::flag($product), 1, self::NOTICE_TTL);

        return true;
    }

    // Update vom alten Plugin aus: da läuft beim Update noch der alte Code ohne Hook > Version merken
    public static function versionChanged(string $product, string $version): bool
    {
        $opt = 'mgd_kit_ver_' . str_replace('-', '_', $product);
        if (\get_option($opt, '') === $version) {
            return false;
        }
        \update_option($opt, $version, false);
        \set_transient(self::flag($product), 1, self::NOTICE_TTL);

        return true;
    }

    /** WP ruft uninstall_<basename> nur auf, wenn ein Uninstall-Hook gespeichert ist. WP schreibt nur bei Änderung */
    public static function registerUninstall(string $pluginFile): void
    {
        try {
            if (function_exists('register_uninstall_hook')) {
                \register_uninstall_hook($pluginFile, [Setup::class, 'onUninstall']);
            }
        } catch (\Throwable) {
        }
    }

    /** doppelte boot-Hooks raus, der erste bleibt. Rückgabe = Anzahl entfernt */
    public static function dedupe(string $product): int
    {
        $flag = self::flag($product);
        $removed = 0;
        $rules = [
            'admin_init' => static fn (array $v): bool => ($v['product'] ?? null) === $product && array_key_exists('platform', $v) && array_key_exists('version', $v),
            'admin_notices' => static fn (array $v): bool => ($v['flag'] ?? null) === $flag && array_key_exists('settingsUrl', $v),
        ];
        foreach ($rules as $hook => $match) {
            $seen = false;
            foreach (self::callbacks($hook) as $prio => $cbs) {
                foreach ((array) $cbs as $cb) {
                    $fn = is_array($cb) ? ($cb['function'] ?? null) : null;
                    if (!self::isInstallClosure($fn, $match)) {
                        continue;
                    }
                    if ($seen) {
                        \remove_action($hook, $fn, (int) $prio);
                        ++$removed;
                    }
                    $seen = true;
                }
            }
        }

        return $removed;
    }

    // Closure aus Platform\WordPress::boot? (auch fremde Namensräume, nur Klassenname + use-Variablen)
    private static function isInstallClosure(mixed $fn, \Closure $match): bool
    {
        if (!$fn instanceof \Closure) {
            return false;
        }
        $r = new \ReflectionFunction($fn);

        return $r->getClosureScopeClass()?->getShortName() === 'WordPress' && $match($r->getStaticVariables());
    }

    /** @return array<int|string,mixed> */
    private static function callbacks(string $hook): array
    {
        $h = $GLOBALS['wp_filter'][$hook] ?? null;
        if (is_object($h)) {
            return is_array($h->callbacks ?? null) ? $h->callbacks : [];
        }

        return is_array($h) ? $h : [];
    }
}
