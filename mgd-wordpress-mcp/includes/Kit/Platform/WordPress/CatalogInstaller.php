<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Catalog\Recommendations;
use Mgd\WordPressMcp\Kit\Update\UpdateInfo;

// "Installieren" aus Weitere Plugins: POST über admin-post.php, Recht install_plugins + Nonce
// ZIP nie aus dem Feed > Update-Datei vom Server, Hosts/Weiterleitungen/Größe/SHA wie Updater.php. Nie aktivieren
final class CatalogInstaller
{
    public const UPDATES_PREFIX = 'https://theme.michael-gahn.de/api/v1/updates/';
    public const QUERY = 'mgd_kit_cat';
    public const RESULTS = ['installed', 'failed', 'unavailable'];

    /** @var \Closure(string):bool */
    private \Closure $upgrade;
    /** @var \Closure():array<string,mixed> */
    private \Closure $plugins;
    /** @var \Closure(string,string):bool */
    private \Closure $verifyNonce;

    /**
     * Closures nur für Tests austauschbar, Standard = echtes WordPress
     * @param \Closure(string):bool|null $upgrade bekommt die geprüfte lokale ZIP
     * @param \Closure():array<string,mixed>|null $plugins wie get_plugins()
     * @param \Closure(string,string):bool|null $verifyNonce (nonce, action)
     */
    public function __construct(
        private readonly string $product,
        private readonly Recommendations $recommendations,
        private readonly string $returnUrl = '',
        ?\Closure $upgrade = null,
        ?\Closure $plugins = null,
        ?\Closure $verifyNonce = null,
    ) {
        if (preg_match(Setup::PRODUCT_RE, $product) !== 1) {
            throw new \InvalidArgumentException('Produktname ungültig.');
        }
        $this->upgrade = $upgrade ?? self::wpUpgrade(...);
        $this->plugins = $plugins ?? CatalogState::wpPlugins(...);
        $this->verifyNonce = $verifyNonce ?? static fn (string $n, string $a): bool => \wp_verify_nonce($n, $a) !== false;
    }

    /** Name für admin-post.php, pro Produkt eigener, sonst würden alle MGD-Plugins gleichzeitig loslegen */
    public static function action(string $product): string
    {
        return 'mgd_kit_cat_install_' . str_replace('-', '_', $product);
    }

    public static function nonceAction(string $product, string $slug): string
    {
        return self::action($product) . '_' . $slug;
    }

    // WP-Kleber für admin_post_<action>
    public function handle(): void
    {
        $result = 'failed';
        try {
            $post = \wp_unslash($_POST);
            $isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
            $result = $this->run(is_array($post) ? $post : [], $isPost && \current_user_can('install_plugins'));
        } catch (\Throwable) {
        }
        if ($result === 'denied') {
            \wp_die('Nicht berechtigt.', '', ['response' => 403]);
        }
        $back = (string) (\wp_get_referer() ?: ($this->returnUrl !== '' ? $this->returnUrl : \admin_url('plugins.php')));
        \wp_safe_redirect(\add_query_arg(self::QUERY, $result, \remove_query_arg(self::QUERY, $back)));
        exit;
    }

    /**
     * Ergebnis: installed | failed | unavailable | denied
     * @param array<mixed> $post
     */
    public function run(array $post, bool $allowed): string
    {
        $slug = is_string($post['slug'] ?? null) ? $post['slug'] : '';
        $nonce = is_string($post['_wpnonce'] ?? null) ? $post['_wpnonce'] : '';
        if (!$allowed || $nonce === '' || strlen($slug) > 100 || preg_match(Recommendations::SLUG_RE, $slug) !== 1) {
            return 'denied';
        }
        try {
            if (!($this->verifyNonce)($nonce, self::nonceAction($this->product, $slug))) {
                return 'denied';
            }
            $item = $this->item($slug);
            if ($item === null || $item['tier'] !== 'free' || $item['updateProduct'] === null || $this->installed($item['basenames'])) {
                return 'unavailable';
            }
            $u = $this->info($item['updateProduct'], $item['basenames']);
            $zip = $u !== null ? $this->fetchZip($u) : null;
            if ($u === null || $zip === null) {
                return 'failed';
            }
            if (!Updater::verify($zip, $u)) {
                self::delete($zip);

                return 'failed';
            }
            $ok = ($this->upgrade)($zip);
            self::delete($zip);

            return $ok === true ? 'installed' : 'failed';
        } catch (\Throwable) {
            return 'failed';
        }
    }

    // Eintrag nur aus der eigenen, bereinigten Liste, nie aus dem POST
    private function item(string $slug): ?array
    {
        foreach ($this->recommendations->load()['items'] as $it) {
            if (is_array($it) && ($it['slug'] ?? null) === $slug) {
                return $it;
            }
        }

        return null;
    }

    /** @param list<string> $basenames */
    private function installed(array $basenames): bool
    {
        $have = ($this->plugins)();
        foreach ($basenames as $b) {
            if (isset($have[$b])) {
                return true;
            }
        }

        return false;
    }

    // Update-Datei ohne Install-Header holen, root_dir muss zum Plugin-Ordner passen
    /** @param list<string> $basenames */
    private function info(string $product, array $basenames): ?UpdateInfo
    {
        $r = \wp_safe_remote_get(self::UPDATES_PREFIX . $product . '.json', [
            'timeout' => 10, 'redirection' => 0, 'limit_response_size' => UpdateInfo::MAX_JSON + 1, 'headers' => ['Accept' => 'application/json'],
        ]);
        if (\is_wp_error($r) || (int) \wp_remote_retrieve_response_code($r) !== 200) {
            return null;
        }
        $u = UpdateInfo::parse((string) \wp_remote_retrieve_body($r), $product);
        if ($u === null) {
            return null;
        }
        $dirs = array_map(static fn (string $b): string => dirname($b), $basenames);

        return $dirs === [] || in_array($u->rootDir, $dirs, true) ? $u : null;
    }

    // Weiterleitungen selbst folgen, jeder Hop muss auf der Host-Liste vom Updater stehen
    private function fetchZip(UpdateInfo $u): ?string
    {
        $tmp = \wp_tempnam($u->filename);
        if (!is_string($tmp) || $tmp === '') {
            return null;
        }
        $url = $u->downloadUrl;
        for ($hop = 0; $hop <= Updater::MAX_HOPS; ++$hop) {
            if (!is_string($url) || !Updater::allowedUrl($url)) {
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
        self::delete($tmp);

        return null;
    }

    private static function delete(string $file): void
    {
        if (is_file($file)) {
            \wp_delete_file($file);
        }
    }

    // echter Plugin_Upgrader mit der lokalen Datei, ohne Ausgabe. Aktivieren macht der Admin selbst
    private static function wpUpgrade(string $zip): bool
    {
        if (!class_exists('Plugin_Upgrader') && defined('ABSPATH')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        if (!class_exists('Plugin_Upgrader')) {
            return false;
        }
        $up = new \Plugin_Upgrader(new \Automatic_Upgrader_Skin());

        return $up->install($zip) === true;
    }
}
