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
    public static function overview(array $rows, string $nonceField, string $message = '', array $installs = []): string
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
        $table = '<table class="widefat striped mgd-tools-table"><thead><tr>' . $h . '</tr></thead><tbody>' . $body . '</tbody></table>'
            . '<form method="post" class="mgd-tools-check">' . $nonceField
            . '<p><button type="submit" class="button" name="mgd_tools_do" value="check">' . Text::e(ToolsTexts::CHECK_NOW) . '</button></p></form>';
        $out = self::section('updates', ToolsTexts::OVERVIEW_H1, $table);
        if ($installs !== []) {
            $in = '';
            foreach ($installs as $name => $form) {
                // Formular kommt fertig + escaped aus dem Install-Client
                $in .= '<h3>' . Text::e((string) $name) . '</h3>' . $form;
            }
            $out .= self::section('install', ToolsTexts::INSTALL, $in);
        }

        return self::wrap(ToolsTexts::MENU, $message, $out);
    }

    /** @param array<string,mixed> $r */
    public static function row(array $r): string
    {
        $icon = (string) ($r['icon'] ?? '');
        $img = $icon !== '' ? '<img src="' . Text::e($icon) . '" alt="" width="32" height="32" class="mgd-tools-icon"> ' : '';
        $latest = (string) ($r['latest'] ?? '');
        $status = match (true) {
            $latest === '' => ToolsTexts::UNKNOWN,
            ($r['newer'] ?? false) === true => ToolsTexts::NEWER,
            default => ToolsTexts::CURRENT,
        };
        if (($r['newer'] ?? false) === true) {
            $status = '<a href="plugins.php">' . Text::e($status) . '</a>';
        } else {
            $status = Text::e($status);
        }
        $notice = (string) ($r['notice'] ?? '');
        if ($notice !== '') {
            $status .= '<br><span class="description">' . Text::e($notice) . '</span>';
        }
        $links = [];
        if (($r['settings'] ?? '') !== '') {
            $links[] = '<a href="' . Text::e((string) $r['settings']) . '">' . Text::e(ToolsTexts::SETTINGS) . '</a>';
        }
        if (($r['details'] ?? '') !== '') {
            $links[] = '<a href="' . Text::e((string) $r['details']) . '" target="_blank" rel="noopener noreferrer">' . Text::e(ToolsTexts::DETAILS) . '</a>';
        }

        return '<tr><th scope="row">' . $img . Text::e((string) ($r['name'] ?? '')) . '</th>'
            . '<td>' . Text::e((string) ($r['installed'] ?? '')) . '</td>'
            . '<td>' . Text::e($latest !== '' ? $latest : '–') . '</td>'
            . '<td>' . $status . '</td>'
            . '<td>' . Text::e(self::check((string) ($r['state'] ?? 'off'), (int) ($r['ok'] ?? 0), (int) ($r['fail'] ?? 0))) . '</td>'
            . '<td>' . implode(' · ', $links) . '</td></tr>';
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
            return self::wrap(ToolsTexts::LICENSE, $message, '<p>' . Text::e(ToolsTexts::LICENSE_OFF) . '</p>');
        }
        $body = '<p>' . Text::e(ToolsTexts::LICENSE_INTRO) . '</p>' . LicenseView::html($l, Tools::PARENT, $nonceField);
        $ent = $l->entitlements();
        if ($ent !== []) {
            $li = '';
            foreach ($ent as $e) {
                $li .= '<li>' . Text::e($labels[$e] ?? $e) . '</li>';
            }
            $body .= '<p>' . Text::e(ToolsTexts::LICENSE_UNLOCKS) . '</p><ul class="mgd-tools-unlocks">' . $li . '</ul>';
        }

        return self::wrap(ToolsTexts::LICENSE, $message, $body);
    }

    public static function wrap(string $title, string $message, string $body): string
    {
        $out = self::style() . '<div class="wrap mgd-kit-dash mgd-tools"><h1>' . Text::e($title) . '</h1>';
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

        return $css === '' ? '' : '<style>' . str_replace('</', '', $css) . '.mgd-tools-icon{vertical-align:middle;border-radius:6px}.mgd-tools-table{max-width:1100px}</style>';
    }
}
