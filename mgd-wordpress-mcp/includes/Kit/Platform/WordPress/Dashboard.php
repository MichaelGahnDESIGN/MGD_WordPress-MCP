<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Catalog\Recommendations;
use Mgd\WordPressMcp\Kit\License\LicenseClient;
use Mgd\WordPressMcp\Kit\Support\Text;

// Dashboard-Rahmen: eine Admin-Seite mit Info, Updates, Weitere Plugins, Installation melden, Lizenz (nur wenn an)
// CSS lokal aus assets/kit-admin.css, keine fremden Schriften/Bilder
final class Dashboard
{
    public const CSS = __DIR__ . '/assets/kit-admin.css';
    public const NO_DATA = 'Für dieses Plugin gibt es noch keine Update-Informationen. Das ist normal, solange noch keine neue Version veröffentlicht ist.';

    /** @param \Closure():string|null $installHtml liefert das Install-Client Formular (WordPress::settingsHtml) */
    public function __construct(
        private readonly string $pluginName,
        private readonly string $version,
        private readonly string $product,
        private readonly ?Updater $updater = null,
        private readonly ?Recommendations $recommendations = null,
        private readonly ?LicenseClient $license = null,
        private readonly ?\Closure $installHtml = null,
        // in eigene Plugin-Seite eingebettet (Menü leer) > kein zweiter wrap-Rahmen + keine zweite h1 (Befund theme)
        private readonly bool $embedded = false,
    ) {
    }

    public function slug(): string
    {
        return 'mgd-kit-' . $this->product;
    }

    public function license(): ?LicenseClient
    {
        return $this->license;
    }

    /** Menüpunkt unter $parent (z. B. options-general.php oder der eigene Menü-Slug des Plugins) */
    public function register(string $parent = 'options-general.php'): void
    {
        \add_action('admin_menu', function () use ($parent): void {
            \add_submenu_page($parent, $this->pluginName, $this->pluginName, 'manage_options', $this->slug(), [$this, 'page']);
        });
    }

    // WP-Kleber: POST prüfen + Seite ausgeben, nie den Admin killen
    public function page(): void
    {
        try {
            $msg = '';
            $nonce = '';
            if ($this->license !== null && $this->license->enabled()) {
                $action = LicenseView::nonceAction($this->product);
                $post = \wp_unslash($_POST);
                $ok = \wp_verify_nonce((string) ($post[LicenseView::NONCE] ?? ''), $action) !== false;
                $msg = (string) LicenseView::handle($this->license, $this->product, is_array($post) ? $post : [], $ok);
                $nonce = (string) \wp_nonce_field($action, LicenseView::NONCE, true, false);
            }
            echo $this->html(gmdate('Y-m-d'), $nonce, $msg);
        } catch (\Throwable) {
            echo '<div class="wrap"><p>Die Seite konnte gerade nicht geladen werden.</p></div>';
        }
    }

    public function html(string $today, string $licenseNonce = '', string $message = ''): string
    {
        if (!\current_user_can('manage_options')) {
            return '';
        }
        $out = self::style() . ($this->embedded
            ? '<div class="mgd-kit-dash mgd-kit-dash--embedded"><h2>' . Text::e($this->pluginName) . '</h2>'
            : '<div class="wrap mgd-kit-dash"><h1>' . Text::e($this->pluginName) . '</h1>');
        if ($message !== '') {
            $out .= '<div class="mgd-kit-message" role="status"><p>' . Text::e($message) . '</p></div>';
        }
        $out .= self::section('info', 'Info', '<p>Plugin: ' . Text::e($this->pluginName) . '</p><p>Version: ' . Text::e($this->version) . '</p>');
        $out .= self::section('updates', 'Updates', $this->updates($today));
        $rec = $this->recommendations !== null ? RecommendationsView::render($this->recommendations, $this->product) : '';
        if ($rec !== '') {
            $out .= self::section('plugins', 'Weitere Plugins', $rec);
        }
        $install = $this->install();
        if ($install !== '') {
            $out .= self::section('install', 'Installation melden', $install);
        }
        if ($this->license !== null && $this->license->enabled()) {
            $out .= self::section('license', 'Lizenz', LicenseView::html($this->license, $this->product, $licenseNonce));
        }

        return $out . '</div>';
    }

    private function updates(string $today): string
    {
        if ($this->updater === null) {
            return '<p>Die Update-Prüfung ist für dieses Plugin nicht eingerichtet.</p>';
        }
        $u = $this->updater->info();
        if ($u === null) {
            // nie gültige Daten gehabt = Update-Datei gibts noch nicht, kein Fehler
            return $this->updater->hadData()
                ? '<p>Die Update-Prüfung hat gerade nicht geklappt. WordPress versucht es später noch einmal.</p>'
                : '<p>' . self::NO_DATA . '</p>';
        }
        $installed = $this->updater->installedVersion();
        $p = $u->isNewerThan($installed)
            ? '<p>Version ' . Text::e($u->version) . ' ist verfügbar. Du kannst sie unter <a href="plugins.php">Plugins</a> aktualisieren.</p>'
            : '<p>Du hast die aktuelle Version.</p>';
        // inline = WP schiebt den Hinweis nicht nach oben
        $notice = str_replace('class="notice ', 'class="notice inline ', NoticeView::html($u, $installed, $today, $this->pluginName));

        return $p . $notice;
    }

    private function install(): string
    {
        try {
            return $this->installHtml !== null ? (string) ($this->installHtml)() : '';
        } catch (\Throwable) {
            return '';
        }
    }

    private static function section(string $id, string $title, string $body): string
    {
        $hid = 'mgd-kit-h-' . $id;

        return '<section class="mgd-kit-section" aria-labelledby="' . $hid . '"><h2 id="' . $hid . '">' . Text::e($title) . '</h2>' . $body . '</section>';
    }

    private static function style(): string
    {
        $css = is_file(self::CSS) ? (string) file_get_contents(self::CSS) : '';

        // eigene Datei, trotzdem kein </style> durchlassen
        return $css === '' ? '' : '<style>' . str_replace('</', '', $css) . '</style>';
    }
}
