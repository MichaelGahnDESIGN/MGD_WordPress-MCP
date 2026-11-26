<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\License\LicenseClient;
use Mgd\WordPressMcp\Kit\Support\Text;

// HTML für MGD Tools, ohne WP-Aufrufe außer current_user_can (testbar). Alles escaped
final class ToolsView
{
    /**
     * @param list<array<string,mixed>> $rows aus ToolsPages::rows
     * @param array<string,string> $installs Plugin-Name => fertiges Formular (Install-Client)
     */
    public static function overview(array $rows, string $nonceField, string $message = '', array $installs = [], string $licChecked = ''): string
    {
        if (!\current_user_can('manage_options')) {
            return '';
        }
        $h = '';
        foreach (ToolsTexts::COLS as $c) {
            $h .= '<th scope="col">' . Text::e($c) . '</th>';
        }
        $body = '';
        foreach ($rows as $r) {
            $body .= self::row($r);
        }
        // Jetzt prüfen = Hauptknopf oben rechts im Kopf
        $check = '<form method="post" class="mgd-tools-check">' . $nonceField
            . '<button type="submit" class="button button-primary" name="mgd_tools_do" value="check">' . Text::e(ToolsTexts::CHECK_NOW) . '</button></form>';
        $table = '<table class="mgd-tools-table"><thead><tr>' . $h . '</tr></thead><tbody>' . $body . '</tbody></table>'
            . ($licChecked !== '' ? '<p class="mgd-tools-lic-checked">' . Text::e(sprintf(ToolsTexts::LIC_CHECKED, $licChecked)) . '</p>' : '');
        $out = self::section('updates', ToolsTexts::OVERVIEW_H1, $table);
        if ($installs !== []) {
            $in = '';
            foreach ($installs as $name => $form) {
                // Formular kommt fertig + escaped aus dem Install-Client
                $in .= '<h3>' . Text::e((string) $name) . '</h3>' . $form;
            }
            $out .= self::section('install', ToolsTexts::INSTALL, $in);
        }

        return self::wrap(ToolsTexts::MENU, $message, $out, ToolsTexts::INTRO, $check);
    }

    /** @param array<string,mixed> $r */
    public static function row(array $r): string
    {
        $icon = (string) ($r['icon'] ?? '');
        // Icon kaputt (z. B. Dateirechte) > MGD-M dahinter bleibt sichtbar (Befund Michael: Share ohne Icon)
        $img = '<span class="mgd-tools-icon">' . self::mark(32)
            . ($icon !== '' ? '<img src="' . Text::e($icon) . '" alt="" width="32" height="32" loading="lazy" onerror="this.remove()">' : '') . '</span>';
        $latest = (string) ($r['latest'] ?? '');
        $state = (string) ($r['state'] ?? 'off');
        [$label, $tone] = match (true) {
            $state === 'fail' => [ToolsTexts::ERROR_BADGE, 'red'],
            $latest === '' => [ToolsTexts::UNKNOWN, 'grey'],
            ($r['newer'] ?? false) === true => [ToolsTexts::NEWER, 'orange'],
            default => [ToolsTexts::CURRENT, 'green'],
        };
        $badge = '<span class="mgd-tools-badge mgd-tools-badge--' . $tone . '">' . Text::e($label) . '</span>';
        if (($r['newer'] ?? false) === true) {
            $badge = '<a href="plugins.php" class="mgd-tools-badge-link">' . $badge . '</a>';
        }
        $notice = (string) ($r['notice'] ?? '');
        if ($notice !== '') {
            $badge .= '<span class="mgd-tools-note">' . Text::e($notice) . '</span>';
        }
        $links = [];
        if (($r['settings'] ?? '') !== '') {
            $links[] = '<a href="' . Text::e((string) $r['settings']) . '">' . Text::e(ToolsTexts::SETTINGS) . '</a>';
        }
        if (($r['details'] ?? '') !== '') {
            $links[] = '<a href="' . Text::e((string) $r['details']) . '" target="_blank" rel="noopener noreferrer">' . Text::e(ToolsTexts::DETAILS) . '</a>';
        }
        // data-label: auf dem Handy wird jede Zeile eine Karte mit Beschriftung
        $c = ToolsTexts::COLS;
        $td = static fn (int $i, string $html): string => '<td data-label="' . Text::e($c[$i]) . '">' . $html . '</td>';
        $lic = is_array($r['license'] ?? null) ? LicenseBadge::html($r['license']) : '–';
        // Knopf kommt fertig aus PluginToggle::form (escaped)
        $toggle = (string) ($r['toggle'] ?? '');

        return '<tr><th scope="row"><span class="mgd-tools-plugin">' . $img . '<span class="mgd-tools-name">' . Text::e((string) ($r['name'] ?? '')) . '</span></span></th>'
            . $td(1, Text::e((string) ($r['installed'] ?? '')))
            . $td(2, Text::e($latest !== '' ? $latest : '–'))
            . $td(3, $badge)
            . $td(4, $lic)
            . $td(5, '<span class="mgd-tools-check-text">' . Text::e(self::check($state, (int) ($r['ok'] ?? 0), (int) ($r['fail'] ?? 0))) . '</span>')
            . $td(6, '<span class="mgd-tools-actions">' . implode(' <span aria-hidden="true">·</span> ', $links) . $toggle . '</span>') . '</tr>';
    }

    /** MGD-M als Bild (inline SVG, kein externes Bild) */
    public static function mark(int $size): string
    {
        return '<svg class="mgd-tools-mark" width="' . $size . '" height="' . $size . '" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><rect width="32" height="32" rx="7" fill="#1e2c2c"/>'
            . '<path d="M7 23V9h3l6 7.5L22 9h3v14h-3v-9l-6 7.4L10 14v9z" fill="#d6c49a"/></svg>';
    }

    public static function check(string $state, int $ok, int $fail): string
    {
        return match ($state) {
            // alte Plugins speichern nur "1" statt Zeit > ohne Datum
            'ok' => $ok > 1000000000 ? sprintf(ToolsTexts::CHECK_OK, self::date($ok)) : ToolsTexts::CHECK_OK_NODATE,
            'fail' => sprintf(ToolsTexts::CHECK_FAIL, $fail > 0 ? self::date($fail) : '–'),
            'none' => ToolsTexts::CHECK_NONE,
            default => ToolsTexts::CHECK_OFF,
        };
    }

    /** @param array<string,string> $labels Berechtigung => Name der Pro-Funktion */
    public static function license(LicenseClient $l, string $nonceField, string $message, array $labels): string
    {
        if (!\current_user_can('manage_options')) {
            return '';
        }
        if (!$l->enabled()) {
            return self::wrap(ToolsTexts::LICENSE, $message, '<section class="mgd-kit-section"><p>' . Text::e(ToolsTexts::LICENSE_OFF) . '</p></section>', ToolsTexts::LICENSE_INTRO);
        }
        $body = '<section class="mgd-kit-section">' . LicenseView::html($l, Tools::PARENT, $nonceField);
        $ent = $l->entitlements();
        if ($ent !== []) {
            $li = '';
            foreach ($ent as $e) {
                $li .= '<li>' . Text::e($labels[$e] ?? $e) . '</li>';
            }
            $body .= '<p class="mgd-tools-unlocks-title">' . Text::e(ToolsTexts::LICENSE_UNLOCKS) . '</p><ul class="mgd-tools-unlocks">' . $li . '</ul>';
        }

        return self::wrap(ToolsTexts::LICENSE, $message, $body . '</section>', ToolsTexts::LICENSE_INTRO);
    }

    public static function wrap(string $title, string $message, string $body, string $intro = '', string $action = ''): string
    {
        // Kopf: MGD-Logo, Titel, kurzer Satz, Hauptknopf rechts
        $out = self::style() . '<div class="wrap mgd-kit-dash mgd-tools"><header class="mgd-tools-head">' . self::mark(44)
            . '<div class="mgd-tools-head__text"><h1>' . Text::e($title) . '</h1>' . ($intro !== '' ? '<p>' . Text::e($intro) . '</p>' : '') . '</div>'
            . ($action !== '' ? '<div class="mgd-tools-head__action">' . $action . '</div>' : '') . '</header>'
            // WP setzt fremde Hinweise hinter diese Linie, nicht in unseren Kopf
            . '<hr class="wp-header-end">';
        if ($message !== '') {
            $out .= '<div class="mgd-kit-message" role="status"><p>' . Text::e($message) . '</p></div>';
        }

        return $out . $body . '</div>';
    }

    private static function section(string $id, string $title, string $body): string
    {
        $hid = 'mgd-tools-h-' . $id;

        return '<section class="mgd-kit-section" aria-labelledby="' . $hid . '"><h2 id="' . $hid . '">' . Text::e($title) . '</h2>' . $body . '</section>';
    }

    private static function date(int $ts): string
    {
        return function_exists('wp_date') ? (string) \wp_date('d.m.Y H:i', $ts) : gmdate('d.m.Y H:i', $ts);
    }

    private static function style(): string
    {
        $css = is_file(Dashboard::CSS) ? (string) file_get_contents(Dashboard::CSS) : '';

        return '<style>' . str_replace('</', '', $css) . self::CSS . '</style>';
    }

    // eigenes Aussehen MGD Tools, ruhig, passt zu hellen WP-Farbschemata, Handy = Karten
    private const CSS = '.mgd-tools{max-width:1180px}'
        . '.mgd-tools-head{display:flex;align-items:center;gap:16px;margin:18px 0 20px;padding:18px 22px;background:#fff;border:1px solid #dcdcde;border-radius:12px}'
        . '.mgd-tools-head h1{margin:0;padding:0;font-size:22px;line-height:1.3;font-weight:600}'
        . '.mgd-tools-head__text{flex:1;min-width:0}.mgd-tools-head__text p{margin:4px 0 0;color:#50575e;font-size:14px}'
        . '.mgd-tools-head__action form{margin:0}.mgd-tools .mgd-kit-section{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:6px 22px 18px;margin:0 0 20px}'
        . '.mgd-tools .mgd-kit-section h2{font-size:16px;margin:16px 0 12px}'
        . '.mgd-tools-table{width:100%;border-collapse:collapse;font-size:14px}'
        . '.mgd-tools-table th,.mgd-tools-table td{padding:12px 10px;text-align:left;vertical-align:middle;border-top:1px solid #f0f0f1}'
        . '.mgd-tools-table thead th{border-top:0;color:#50575e;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap}'
        . '.mgd-tools-table td:last-child{white-space:nowrap}.mgd-tools .wp-header-end{margin:0 0 12px;border:0}'
        . '.mgd-tools-plugin{display:flex;align-items:center;gap:12px;white-space:nowrap;font-weight:600}'
        . '.mgd-tools-icon{position:relative;flex:0 0 32px;width:32px;height:32px}'
        . '.mgd-tools-icon img,.mgd-tools-icon svg{position:absolute;inset:0;width:32px;height:32px;border-radius:7px}'
        . '.mgd-tools-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;line-height:20px;white-space:nowrap}'
        . '.mgd-tools-badge--green{background:#e7f6ec;color:#0a6b2c}.mgd-tools-badge--orange{background:#fdf0e1;color:#8a4b00}'
        . '.mgd-tools-badge--grey{background:#f0f0f1;color:#50575e}.mgd-tools-badge--red{background:#fbe9ea;color:#a3151f}'
        . '.mgd-tools-badge-link{text-decoration:none}.mgd-tools-note{display:block;margin-top:4px;color:#50575e;font-size:12px}'
        . '.mgd-tools-check-text{color:#3c434a}.mgd-tools-unlocks{margin:4px 0 0 18px;list-style:disc}'
        // Lizenz + an/aus Knopf
        . '.mgd-tools-lic{display:inline-flex;flex-wrap:wrap;align-items:center;gap:6px}.mgd-tools-lic-checked{margin:10px 0 0;color:#50575e;font-size:12px}'
        . '.mgd-tools-actions{display:inline-flex;flex-wrap:wrap;align-items:center;gap:8px}.mgd-tools-toggle{display:inline;margin:0}'
        . '@media (max-width:782px){.mgd-tools-head{flex-wrap:wrap}.mgd-tools-head__action{width:100%}'
        . '.mgd-tools-table thead{display:none}.mgd-tools-table,.mgd-tools-table tbody,.mgd-tools-table tr,.mgd-tools-table th,.mgd-tools-table td{display:block;width:auto}'
        . '.mgd-tools-table tr{padding:12px 0;border-top:1px solid #dcdcde}.mgd-tools-table th,.mgd-tools-table td{border:0;padding:3px 0}'
        . '.mgd-tools-table td::before{content:attr(data-label) ": ";font-weight:600;color:#50575e}}';
}
