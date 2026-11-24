<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

// MGD Farbwähler für WordPress: lädt Ui/assets/mgd-color.* auf allen MGD-Tools-Seiten
// Meine Farben = ganze Website (Option), Zuletzt benutzt = je Benutzer (User-Meta). Nur Hex, nur mit Nonce
final class ColorPicker
{
    public const HANDLE = 'mgd-color';
    public const AJAX = 'mgd_tools_colors';
    public const OPT = 'mgd_tools_colors';
    public const META = 'mgd_tools_colors_recent';
    public const MAX_MINE = 24;
    public const MAX_RECENT = 8;
    public const CAP = 'edit_posts';
    public const ASSETS = __DIR__ . '/../../Ui/assets/';

    /** 1x pro Seite, egal wie viele MGD-Plugins es mitbringen */
    public static function register(): void
    {
        if (!empty($GLOBALS[Tools::GLOBAL_KEY]['color']) || !function_exists('add_action')) {
            return;
        }
        $GLOBALS[Tools::GLOBAL_KEY]['color'] = true;
        \add_action('admin_enqueue_scripts', static function (): void {
            if (self::isMgdPage(is_string($_GET['page'] ?? null) ? $_GET['page'] : '')) {
                self::enqueue();
            }
        });
        \add_action('wp_ajax_' . self::AJAX, [self::class, 'ajax']);
    }

    // MGD Tools + Einstellungsseiten der MGD-Plugins
    public static function isMgdPage(string $page): bool
    {
        if (str_starts_with($page, Tools::PARENT)) {
            return true;
        }
        foreach (Tools::entries() as $e) {
            if (is_array($e['settings']) && $e['settings']['page'] === $page) {
                return true;
            }
        }

        return false;
    }

    /** auch von Hand nutzbar (z. B. eigene Seite außerhalb von MGD Tools) */
    public static function enqueue(): void
    {
        try {
            $base = dirname(__DIR__, 2) . '/KitInfo.php';
            $v = \Mgd\WordPressMcp\Kit\KitInfo::VERSION;
            \wp_enqueue_style(self::HANDLE, \plugins_url('Ui/assets/mgd-color.css', $base), [], $v);
            \wp_enqueue_script(self::HANDLE, \plugins_url('Ui/assets/mgd-color.js', $base), [], $v, true);
            \wp_add_inline_script(self::HANDLE, self::adapter((string) \admin_url('admin-ajax.php'), (string) \wp_create_nonce(self::AJAX)), 'after');
        } catch (\Throwable) {
        }
    }

    // Speicher-Adapter: lädt/speichert über admin-ajax, Fehler > Picker geht trotzdem (nur ohne Speichern)
    public static function adapter(string $ajaxUrl, string $nonce): string
    {
        $cfg = (string) json_encode(['url' => $ajaxUrl, 'action' => self::AJAX, 'nonce' => $nonce], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

        return '(function(c){if(!window.MGDColor)return;var q=function(m,b){var f=new FormData();f.append("action",c.action);f.append("_ajax_nonce",c.nonce);'
            . 'if(b)f.append("colors",JSON.stringify(b));return fetch(c.url,{method:"POST",credentials:"same-origin",body:f}).then(function(r){return r.json()})'
            . '.then(function(j){return j&&j.success?j.data:{}})};MGDColor.setStore({load:function(){return q()},save:function(d){return q("save",d)}});})(' . $cfg . ');';
    }

    public static function ajax(): void
    {
        if (\check_ajax_referer(self::AJAX, false, false) === false || !\current_user_can(self::CAP)) {
            \wp_send_json_error(null, 403);
        }
        $raw = isset($_POST['colors']) ? json_decode((string) \wp_unslash($_POST['colors']), true, 4) : null;
        if (is_array($raw)) {
            // Meine Farben gelten für alle, also nur wer Einstellungen darf
            if (\current_user_can('manage_options') && isset($raw['mine'])) {
                \update_option(self::OPT, self::clean($raw['mine'], self::MAX_MINE), false);
            }
            \update_user_meta(\get_current_user_id(), self::META, self::clean($raw['recent'] ?? [], self::MAX_RECENT));
        }
        \wp_send_json_success(self::data());
    }

    /** @return array{mine:list<string>,recent:list<string>} */
    public static function data(): array
    {
        return [
            'mine' => self::clean(\get_option(self::OPT, []), self::MAX_MINE),
            'recent' => self::clean(\get_user_meta(\get_current_user_id(), self::META, true), self::MAX_RECENT),
        ];
    }

    /** nur #RRGGBB, groß, ohne Doppelte, begrenzt @return list<string> */
    public static function clean(mixed $list, int $max): array
    {
        $out = [];
        foreach (is_array($list) ? $list : [] as $c) {
            $c = is_string($c) ? strtoupper(trim($c)) : '';
            if (preg_match('/^#[0-9A-F]{6}$/D', $c) === 1 && !in_array($c, $out, true)) {
                $out[] = $c;
            }
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }
}
