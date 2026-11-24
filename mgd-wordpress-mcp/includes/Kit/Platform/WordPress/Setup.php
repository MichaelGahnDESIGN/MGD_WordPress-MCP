<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Install\{CallbackStore, CurlTransport, InstallClient, Transport};
use Mgd\WordPressMcp\Kit\Install\Platform\WordPress;
use Mgd\WordPressMcp\Kit\Catalog\Recommendations;
use Mgd\WordPressMcp\Kit\License\{CurlPost, LicenseClient};

// Einstieg fürs Hauptplugin (docs/PLUGIN-BAUKASTEN.md). Fehler > null, der WP-Admin läuft immer weiter
final class Setup
{
    public const PRODUCT_RE = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';
    // gemeinsamer Lizenz-Speicher aller MGD-Plugins
    public const LICENSE_OPT = 'mgd_tools_license';
    // Installieren-Knopf je Produkt nur 1x anmelden
    /** @var array<string,true> */
    private static array $catalogRegistered = [];

    /**
     * Updater + Hinweis anmelden, startet auch den Install-Client (WordPress::boot) mit. $platform = wordpress oder woocommerce
     * $settingsUrl leer = Kit-Dashboard unter Einstellungen, sonst eigene Seite (z. B. Setup::dashboardUrl($product, 'mgd-share'))
     */
    public static function updater(string $pluginFile, string $product, string $platform, string $version, string $pluginName, string $settingsUrl = ''): ?Updater
    {
        try {
            $c = WordPress::client($product, $platform, $version);
            if ($c === null) {
                return null;
            }
            self::boot($pluginFile, $product, $platform, $version, $settingsUrl);
            $u = new Updater($c, \plugin_basename($pluginFile), $version, $pluginName);
            $u->register();
            NoticeView::register($u, $pluginName);

            return $u;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Install-Client starten, nur 1x pro Produkt (updater() macht das schon) */
    public static function boot(string $pluginFile, string $product, string $platform, string $version, string $settingsUrl = ''): bool
    {
        try {
            if (preg_match(self::PRODUCT_RE, $product) !== 1) {
                return false;
            }

            $url = $settingsUrl !== '' ? $settingsUrl : self::dashboardUrl($product);
            // Installieren bei Weitere Plugins schon beim Laden anmelden, admin-post.php baut keine Admin-Seite auf
            self::catalogAuto($product, $platform, $url);

            return InstallBoot::start($pluginFile, $product, $platform, $version, $url);
        } catch (\Throwable) {
            return false;
        }
    }

    /** Adresse der Kit-Seite. $parent wie bei dashboard() */
    public static function dashboardUrl(string $product, string $parent = 'options-general.php'): string
    {
        $page = 'mgd-kit-' . $product;
        $path = str_ends_with($parent, '.php') && preg_match('/^[a-z-]+\.php$/D', $parent) === 1 ? $parent : 'admin.php';

        return (string) \admin_url($path . '?page=' . rawurlencode($page));
    }

    /**
     * Alte Caches/Optionen vom vorherigen Updater löschen, 1x pro Version bei admin_init
     * Namen mit mgd_ vorne, * am Ende = alles mit diesem Anfang (z. B. mgd_admin_release_*)
     * @param list<string> $names
     */
    public static function cleanupLegacy(string $product, string $version, array $names): void
    {
        if (preg_match(self::PRODUCT_RE, $product) !== 1 || $names === []) {
            return;
        }
        $opt = 'mgd_kit_cleanup_' . str_replace('-', '_', $product);
        \add_action('admin_init', static function () use ($opt, $version, $names): void {
            try {
                if (\get_option($opt, '') === $version) {
                    return;
                }
                LegacyCleanup::run($names, $GLOBALS['wpdb'] ?? null);
                \update_option($opt, $version, false);
            } catch (\Throwable) {
            }
        });
    }

    /**
     * Deinstallieren: Eintrag auf der Plattform löschen (gespeicherter Schlüssel), danach Kit-Optionen von dem Produkt weg
     * Läuft von selbst über uninstall_<basename>. Plugins mit eigener uninstall.php rufen das dort auf. Wirft nie
     */
    public static function uninstall(string $product, string $platform, ?Transport $http = null): bool
    {
        if (preg_match(self::PRODUCT_RE, $product) !== 1) {
            return false;
        }
        $done = false;
        try {
            $c = self::installClient($product, $platform, $http);
            if ($c !== null) {
                $c->setEnabled(false);
                $done = true;
            }
        } catch (\Throwable) {
        }
        try {
            self::forget($product);
        } catch (\Throwable) {
        }

        return $done;
    }

    /** Fallback für register_uninstall_hook (nur statisch erlaubt) - die echte Arbeit macht der uninstall_ Hook aus boot() */
    public static function onUninstall(): void
    {
    }

    /**
     * Liste "Weitere Plugins". $selfSlug = Katalog-Kennung des eigenen Plugins (wird ausgeblendet)
     * $selfRepo = eigenes GitHub-Repo (z. B. MGD-Share-WordPress), blendet sich auch in alten Listen ohne Kennung aus
     * @param list<array<string,mixed>> $fallback mitgelieferte Liste
     */
    public static function recommendations(string $product, string $platform, string $selfSlug, array $fallback = [], string $selfRepo = ''): ?Recommendations
    {
        try {
            if (preg_match(self::PRODUCT_RE, $product) !== 1) {
                return null;
            }
            // eigene Option ohne Autoload
            $opt = 'mgd_kit_rec_' . str_replace('-', '_', $product);
            $store = new CallbackStore(
                static fn (): mixed => \get_option($opt, ''),
                static function (string $v) use ($opt): void {
                    \update_option($opt, $v, false);
                },
            );

            return new Recommendations(new CurlTransport('MGDKit/1.0 (' . $platform . ')'), $store, $platform, $selfSlug, $fallback, null, $selfRepo);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Lizenz-Baustein. Standard aus: dann kein Netz, kein UI, isPro() = false
     * Plugin-Lizenzen für wordpress sind auf dem Server noch nicht verkäuflich > erst mit Schritt 7 einschalten
     */
    public static function license(string $product, string $platform, bool $licenseEnabled = false): LicenseClient
    {
        try {
            if (!$licenseEnabled || preg_match(self::PRODUCT_RE, $product) !== 1) {
                return self::licenseOff();
            }
            $store = self::licenseStore($product, true);
            $domain = strtolower((string) \wp_parse_url(\home_url(), PHP_URL_HOST));

            return new LicenseClient(new CurlPost('MGDKit/1.0 (' . $platform . ')'), $store, $domain, true);
        } catch (\Throwable) {
            return self::licenseOff();
        }
    }

    // Pro-Pakete vom Server (ProContentClient), nutzt den Schlüssel vom Lizenz-Baustein, Standard AUS
    // $keys = fest eingebaute öffentliche Schlüssel kid => base64 (docs/PRO-CONTENT-API.md)
    /** @param array<string,string> $keys */
    public static function proContent(string $product, string $platform, array $keys, bool $enabled = false): \Mgd\WordPressMcp\Kit\License\ProContentClient
    {
        $off = static fn (): \Mgd\WordPressMcp\Kit\License\ProContentClient => new \Mgd\WordPressMcp\Kit\License\ProContentClient(null, new CallbackStore(static fn (): mixed => '', static function (string $v): void {
        }), '', [], false);
        try {
            if (!$enabled || preg_match(self::PRODUCT_RE, $product) !== 1) {
                return $off();
            }
            // nur lesen, schreiben macht der LicenseClient
            $store = self::licenseStore($product, false);
            $domain = strtolower((string) \wp_parse_url(\home_url(), PHP_URL_HOST));

            return new \Mgd\WordPressMcp\Kit\License\ProContentClient(new CurlPost('MGDKit/1.0 (' . $platform . ')', \Mgd\WordPressMcp\Kit\License\ProContentClient::MAX_BYTES, \Mgd\WordPressMcp\Kit\License\ProContentClient::TIMEOUT), $store, $domain, $keys, true);
        } catch (\Throwable) {
            return $off();
        }
    }

    /**
     * Ein Schlüssel für alle MGD-Plugins (MGD Tools > Lizenz). Alter Einzel-Schlüssel mgd_kit_lic_<produkt> wird 1x übernommen
     */
    public static function licenseStore(string $product, bool $writable): CallbackStore
    {
        $old = 'mgd_kit_lic_' . str_replace('-', '_', $product);
        $read = static function () use ($old, $writable): mixed {
            $v = \get_option(self::LICENSE_OPT, '');
            if (is_string($v) && $v !== '') {
                return $v;
            }
            $legacy = \get_option($old, '');
            if ($writable && is_string($legacy) && $legacy !== '') {
                \update_option(self::LICENSE_OPT, $legacy, false);
            }

            return $legacy;
        };
        $write = $writable ? static function (string $v): void {
            \update_option(self::LICENSE_OPT, $v, false);
        } : static function (string $v): void {
        };

        return new CallbackStore($read, $write);
    }

    /**
     * MGD Tools (ab Plattform 1.11): Plugin ins gemeinsame Menü, Übersicht/Updates, zentrale Lizenz, Weitere Plugins,
     * Icons + einheitliche Plugin-Liste. Ersetzt Setup::dashboard() und das eigene Hauptmenü
     * $o: settings => [page, title, cap, render], slug => Katalog-Kennung, repo => GitHub-Repo, license => bool, pro => [Berechtigung => Name]
     * @param array<string,mixed> $o
     */
    public static function tools(string $pluginFile, string $product, string $platform, string $version, string $pluginName, ?Updater $updater = null, array $o = []): bool
    {
        try {
            if (preg_match(self::PRODUCT_RE, $product) !== 1) {
                return false;
            }
            $slug = is_string($o['slug'] ?? null) ? $o['slug'] : '';
            $rec = self::recommendations($product, $platform, $slug, [], is_string($o['repo'] ?? null) ? $o['repo'] : '');
            $icons = self::icons($pluginFile);
            $updater?->setIcons($icons);
            $ok = Tools::add([
                'product' => $product,
                'name' => $pluginName,
                'version' => $version,
                'basename' => \plugin_basename($pluginFile),
                'icon' => $icons['1x'] ?? '',
                'slug' => $slug,
                'settings' => is_array($o['settings'] ?? null) ? $o['settings'] : null,
                'status' => $updater !== null ? static fn (bool $fresh = false): array => $updater->status($fresh) : null,
                'install' => static fn (): string => WordPress::settingsHtml($product, $platform, $version),
                'license' => ($o['license'] ?? false) === true,
                'pro' => is_array($o['pro'] ?? null) ? $o['pro'] : [],
            ], $rec, $platform);
            if (!$ok) {
                return false;
            }
            self::catalogActions($product, $rec, Tools::url(Tools::PAGE_CATALOG));
            // Farbwähler auf allen MGD-Seiten (ersetzt input type=color / wp-color-picker)
            ColorPicker::register();
            $page = is_array($o['settings'] ?? null) && is_string($o['settings']['page'] ?? null) ? $o['settings']['page'] : '';
            PluginList::register(\plugin_basename($pluginFile), Tools::url($page !== '' && preg_match(Tools::PAGE_RE, $page) === 1 ? $page : Tools::PARENT), $icons['1x'] ?? '');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * assets/icon-128x128.png + icon-256x256.png im Plugin (aus dem Katalog), nur wenn die Datei da ist
     * @return array<string,string>
     */
    public static function icons(string $pluginFile): array
    {
        $out = [];
        foreach (['1x' => 'icon-128x128.png', '2x' => 'icon-256x256.png', 'svg' => 'icon.svg'] as $k => $f) {
            if (is_file(dirname($pluginFile) . '/assets/' . $f) && function_exists('plugins_url')) {
                $url = (string) \plugins_url('assets/' . $f, $pluginFile);
                // hinter Proxy meint WP manchmal http, die Seite läuft aber mit https (Befund share)
                if (str_starts_with((string) \home_url(), 'https://')) {
                    $url = (string) preg_replace('#^http://#', 'https://', $url);
                }
                $out[$k] = $url;
            }
        }

        return $out;
    }

    /**
     * Admin-Seite mit allen Bereichen. $parent = options-general.php (Einstellungen) oder eigener Menü-Slug, '' = kein Menüpunkt
     * Speichern von "Installation melden" läuft über WordPress::boot(), das startet updater() bzw. boot() schon
     */
    public static function dashboard(
        string $product,
        string $platform,
        string $version,
        string $pluginName,
        ?Updater $updater = null,
        ?Recommendations $recommendations = null,
        bool $licenseEnabled = false,
        string $parent = 'options-general.php',
    ): ?Dashboard {
        try {
            $d = new Dashboard(
                $pluginName,
                $version,
                $product,
                $updater,
                $recommendations,
                self::license($product, $platform, $licenseEnabled),
                static fn (): string => WordPress::settingsHtml($product, $platform, $version),
                $parent === '',
            );
            if ($parent !== '') {
                $d->register($parent);
            }
            self::catalogActions($product, $recommendations, $parent !== '' ? self::dashboardUrl($product, $parent) : '');

            return $d;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Knopf "Installieren" bei Weitere Plugins anmelden (admin-post.php). Setup::updater()/boot() machen das schon beim Laden,
     * Setup::dashboard auch. Von Hand nur, wenn dein Plugin weder updater() noch boot() aufruft
     */
    public static function catalogActions(string $product, ?Recommendations $recommendations, string $returnUrl = ''): void
    {
        try {
            if ($recommendations === null || preg_match(self::PRODUCT_RE, $product) !== 1) {
                return;
            }
            if (isset(self::$catalogRegistered[$product])) {
                return;
            }
            self::$catalogRegistered[$product] = true;
            $i = new CatalogInstaller($product, $recommendations, $returnUrl);
            \add_action('admin_post_' . CatalogInstaller::action($product), [$i, 'handle']);
        } catch (\Throwable) {
        }
    }

    // aus boot()/updater(): Liste erst beim Klick laden (gleicher Speicher wie die Ansicht), eigenes Plugin ist eh installiert
    private static function catalogAuto(string $product, string $platform, string $returnUrl): void
    {
        if (isset(self::$catalogRegistered[$product]) || !function_exists('add_action')) {
            return;
        }
        self::$catalogRegistered[$product] = true;
        \add_action('admin_post_' . CatalogInstaller::action($product), static function () use ($product, $platform, $returnUrl): void {
            $r = self::recommendations($product, $platform, '');
            if ($r !== null) {
                (new CatalogInstaller($product, $r, $returnUrl))->handle();
            }
        });
    }

    /** nur Tests: Merker zurücksetzen */
    public static function resetCatalogActions(): void
    {
        self::$catalogRegistered = [];
    }

    // gleiche Option wie WordPress::client, Transport nur für Tests austauschbar. Version egal fürs Löschen
    private static function installClient(string $product, string $platform, ?Transport $http): ?InstallClient
    {
        if ($http === null) {
            return WordPress::client($product, $platform, '0.0.0');
        }
        $opt = 'mgd_install_' . str_replace('-', '_', $product);
        $store = new CallbackStore(static fn (): mixed => \get_option($opt, ''), static function (string $v) use ($opt): void {
            \update_option($opt, $v, false);
        });

        return new InstallClient($http, $store, $product, $platform, '0.0.0', '');
    }

    // nur die eigenen Namen vom Produkt, nix mit LIKE
    private static function forget(string $product): void
    {
        $id = str_replace('-', '_', $product);
        foreach (['mgd_install_', 'mgd_kit_cleanup_', 'mgd_kit_rec_', 'mgd_kit_lic_', 'mgd_kit_upd_ok_', 'mgd_kit_upd_fail_', 'mgd_kit_ver_'] as $p) {
            \delete_option($p . $id);
        }
        \delete_site_transient('mgd_kit_upd_' . $id);
        \delete_transient(InstallBoot::flag($product));
    }

    // aus = Attrappe ohne Netz
    private static function licenseOff(): LicenseClient
    {
        return new LicenseClient(null, new CallbackStore(static fn (): mixed => '', static function (string $v): void {
        }), '', false);
    }
}
