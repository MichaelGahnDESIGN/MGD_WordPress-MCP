<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Install\InstallClient;
use Mgd\WordPressMcp\Kit\Support\Text;
use Mgd\WordPressMcp\Kit\Update\UpdateInfo;

// WP/Woo Updater nach Muster WP-Login: Update-JSON über den Install-Client (zählt mit, fällt selbst auf /plugin-updates/ zurück)
// ZIP aus dem GitHub-Release, Größe + SHA-256 vor der Installation prüfen, kein Auto-Update ohne Admin
final class Updater
{
    public const CACHE_OK = 43200; // 12 Std, passt zum WP-Update-Cron
    public const CACHE_FAIL = 1800;  // Server weg > 30 Min still sein
    public const MAX_HOPS = 3;
    // github.com leitet auf die Asset-Hosts weiter, mehr ist nicht erlaubt
    public const HOSTS = ['github.com', 'objects.githubusercontent.com', 'release-assets.githubusercontent.com'];
    public const DETAILS_URL = 'https://theme.michael-gahn.de/plugins';
    public const ERR = 'mgd_kit_update';

    private readonly string $slug;
    private readonly string $cacheKey;
    private readonly string $okKey;

    /** @param string $basename plugin_basename(__FILE__), z. B. mgd-share/mgd-share.php */
    public function __construct(
        private readonly InstallClient $client,
        private readonly string $basename,
        private readonly string $version,
        private readonly string $name = '',
    ) {
        if (preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+\.php$#D', $basename) !== 1 || str_contains($basename, '..')
            || preg_match(UpdateInfo::VERSION_RE, $version) !== 1) {
            throw new \InvalidArgumentException('Plugin-Datei oder Version ungültig.');
        }
        $this->slug = dirname($basename);
        $this->cacheKey = 'mgd_kit_upd_' . str_replace('-', '_', $client->product());
        $this->okKey = 'mgd_kit_upd_ok_' . str_replace('-', '_', $client->product());
    }

    public function register(): void
    {
        \add_filter('pre_set_site_transient_update_plugins', [$this, 'inject']);
        \add_filter('plugins_api', [$this, 'details'], 20, 3);
        // ganz spät, damit kein anderer Filter die geprüfte Datei noch tauscht
        \add_filter('upgrader_pre_download', [$this, 'download'], PHP_INT_MAX, 4);
        \add_filter('auto_update_plugin', [$this, 'autoUpdate'], 99, 2);
    }

    public function installedVersion(): string
    {
        return $this->version;
    }

    /** Update-Daten mit Cache, null = Server weg oder Datei ungültig */
    public function info(bool $fresh = false): ?UpdateInfo
    {
        try {
            if ($fresh) {
                \delete_site_transient($this->cacheKey);
            }
            $raw = \get_site_transient($this->cacheKey);
            if (!is_string($raw)) {
                $raw = (string) ($this->client->fetchUpdates() ?? '');
                $ok = $this->parse($raw) !== null;
                \set_site_transient($this->cacheKey, $ok ? $raw : '', $ok ? self::CACHE_OK : self::CACHE_FAIL);
                // merken: gab schon mal gültige Daten (Dashboard unterscheidet "noch keine Datei" von Fehler)
                if ($ok && \get_option($this->okKey, '') !== '1') {
                    \update_option($this->okKey, '1', false);
                }
            }

            return $this->parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /** true = früher schon mal gültige Update-Daten bekommen */
    public function hadData(): bool
    {
        try {
            return \get_option($this->okKey, '') === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    /** Update-Angebot in die WP-Liste */
    public function inject(mixed $transient): mixed
    {
        if (!is_object($transient)) {
            return $transient;
        }
        try {
            $u = $this->info();
            if ($u === null) {
                return $transient;
            }
            $newer = $u->isNewerThan($this->version);
            $item = (object) [
                'id' => self::DETAILS_URL,
                'slug' => $this->slug,
                'plugin' => $this->basename,
                'new_version' => $newer ? $u->version : $this->version,
                'url' => self::DETAILS_URL,
                'package' => $newer ? $u->downloadUrl : '',
                'requires_php' => '8.1',
            ];
            $resp = is_array($transient->response ?? null) ? $transient->response : [];
            $none = is_array($transient->no_update ?? null) ? $transient->no_update : [];
            // fremde Angebote für unser Plugin (z. B. gleicher Slug auf wordpress.org) fliegen raus
            unset($resp[$this->basename], $none[$this->basename]);
            if ($newer) {
                $resp[$this->basename] = $item;
            } else {
                $none[$this->basename] = $item;
            }
            $transient->response = $resp;
            $transient->no_update = $none;
        } catch (\Throwable) {
        }

        return $transient;
    }

    /** "Details anzeigen". Ohne gültige Update-Daten bleibt $result wie er ist */
    public function details(mixed $result, mixed $action, mixed $args): mixed
    {
        if ($action !== 'plugin_information' || !is_object($args) || ($args->slug ?? '') !== $this->slug) {
            return $result;
        }
        $u = $this->info();
        if ($u === null) {
            return $result;
        }
        $info = new \stdClass();
        $info->name = $this->name !== '' ? $this->name : $this->slug;
        $info->slug = $this->slug;
        $info->version = $u->version;
        $info->homepage = self::DETAILS_URL;
        $info->download_link = $u->downloadUrl;
        $info->requires_php = '8.1';
        $info->sections = ['changelog' => '<h4>' . Text::e($u->version) . '</h4><p>' . Text::e($u->changelog !== '' ? $u->changelog : 'Keine Angaben.') . '</p>'];

        return $info;
    }

    /** ZIP selbst laden + gegen Größe/SHA-256 prüfen, sonst wird nix installiert */
    public function download(mixed $reply, mixed $package, mixed $upgrader = null, mixed $hookExtra = []): mixed
    {
        if (!$this->isOurs($package, $hookExtra) || \is_wp_error($reply)) {
            return $reply;
        }
        try {
            // beim Klick frisch holen, nicht dem Cache trauen
            $u = $this->info(true);
            if ($u === null || $package !== $u->downloadUrl) {
                return self::error('Update abgebrochen: Die Prüfdaten von theme.michael-gahn.de fehlen oder passen nicht zum Download.');
            }
            if ($reply !== false) {
                // anderes Plugin hat schon geladen > trotzdem prüfen
                return is_string($reply) && self::verify($reply, $u) ? $reply : self::error('Update abgebrochen: Die Prüfsumme des Downloads stimmt nicht. Es wurde nichts installiert.');
            }
            $tmp = $this->fetchZip($u);
            if (\is_wp_error($tmp)) {
                return $tmp;
            }
            if (!self::verify($tmp, $u)) {
                \wp_delete_file($tmp);

                return self::error('Update abgebrochen: Die Prüfsumme des Downloads stimmt nicht. Es wurde nichts installiert.');
            }

            return $tmp;
        } catch (\Throwable) {
            return self::error('Update abgebrochen: Unerwarteter Fehler. Es wurde nichts installiert.');
        }
    }

    /** Auto-Update nur, wenn der Admin es für dieses Plugin selbst eingeschaltet hat */
    public function autoUpdate(mixed $update, mixed $item): mixed
    {
        if (!is_object($item) || ($item->plugin ?? '') !== $this->basename) {
            return $update;
        }
        try {
            return in_array($this->basename, (array) \get_site_option('auto_update_plugins', []), true);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function allowedUrl(string $url): bool
    {
        $p = parse_url($url);

        return is_array($p) && ($p['scheme'] ?? '') === 'https' && in_array($p['host'] ?? '', self::HOSTS, true)
            && !isset($p['user']) && !isset($p['pass']) && ($p['port'] ?? 443) === 443;
    }

    public static function verify(string $path, UpdateInfo $u): bool
    {
        return is_file($path) && filesize($path) === $u->size && hash_equals($u->sha256, (string) hash_file('sha256', $path));
    }

    private function parse(string $raw): ?UpdateInfo
    {
        $u = UpdateInfo::parse($raw, $this->client->product());

        // root_dir muss unser Plugin-Ordner sein, sonst landet das ZIP woanders
        return $u !== null && $u->rootDir === $this->slug ? $u : null;
    }

    // nur unser Plugin. Andere MGD-Plugins haben dieselbe GitHub-Adresse, die gehen uns nix an
    private function isOurs(mixed $package, mixed $hookExtra): bool
    {
        if (is_array($hookExtra) && isset($hookExtra['plugin'])) {
            return $hookExtra['plugin'] === $this->basename;
        }
        if (!is_string($package) || $package === '') {
            return false;
        }
        $raw = \get_site_transient($this->cacheKey);
        $u = is_string($raw) ? $this->parse($raw) : null;

        return $u !== null && $package === $u->downloadUrl;
    }

    // Weiterleitungen selbst folgen, jeder Hop muss auf der Host-Liste stehen
    private function fetchZip(UpdateInfo $u): mixed
    {
        $tmp = \wp_tempnam($u->filename);
        if (!$tmp) {
            return self::error('Update abgebrochen: Keine temporäre Datei möglich.');
        }
        $url = $u->downloadUrl;
        for ($hop = 0; $hop <= self::MAX_HOPS; ++$hop) {
            if (!is_string($url) || !self::allowedUrl($url)) {
                break;
            }
            $res = \wp_safe_remote_get($url, ['timeout' => 300, 'redirection' => 0, 'stream' => true, 'filename' => $tmp, 'limit_response_size' => $u->size + 1]);
            if (\is_wp_error($res)) {
                break;
            }
            $code = (int) \wp_remote_retrieve_response_code($res);
            if ($code === 200) {
                return $tmp;
            }
            if (!in_array($code, [301, 302, 303, 307, 308], true)) {
                break;
            }
            $url = \wp_remote_retrieve_header($res, 'location');
        }
        \wp_delete_file($tmp);

        return self::error('Update abgebrochen: Der Download von GitHub hat nicht geklappt oder kam von einer nicht erlaubten Adresse.');
    }

    private static function error(string $msg): \WP_Error
    {
        return new \WP_Error(self::ERR, $msg);
    }
}
