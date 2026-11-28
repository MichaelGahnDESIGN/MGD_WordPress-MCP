<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Support\Text;
use Mgd\WordPressMcp\Kit\Ui\Accent;

// MGD Tools > Einstellungen: eine Primärfarbe für alle MGD-Plugins (Kit 1.17)
// gemeinsame Option, nur #rrggbb, wirkt nur als CSS-Variable im Admin, schreibt nie in Plugin-Einstellungen
final class ToolsSettings
{
    public const OPT = 'mgd_tools_accent';
    public const NONCE = '_mgd_tools_settings_nonce';
    public const ACTION = 'mgd_tools_settings';
    public const FIELD = 'mgd_tools_accent';

    /** gespeicherte Farbe oder MGD-Rot */
    public static function accent(): string
    {
        try {
            return Accent::clean(function_exists('get_option') ? \get_option(self::OPT, '') : '') ?? Accent::DEFAULT;
        } catch (\Throwable) {
            return Accent::DEFAULT;
        }
    }

    /**
     * POST auswerten. null = Formular war nicht gemeint
     * @param array<string,mixed> $post schon wp_unslash
     * @return array{0:string,1:?string} [Meldung, neue Farbe oder null = löschen]
     */
    public static function handle(array $post, bool $nonceOk): ?array
    {
        $do = $post['mgd_tools_settings_do'] ?? '';
        if (!in_array($do, ['save', 'reset'], true)) {
            return null;
        }
        if (!$nonceOk || !\current_user_can('manage_options')) {
            return [ToolsTexts::RETRY, self::accent()];
        }
        if ($do === 'reset') {
            return [ToolsTexts::ACCENT_RESET, null];
        }
        $c = Accent::clean($post[self::FIELD] ?? null);

        return $c === null ? [ToolsTexts::ACCENT_BAD, self::accent()] : [ToolsTexts::ACCENT_SAVED, $c];
    }

    /** reines HTML, Farbe vorher geprüft */
    public static function html(string $accent, string $nonceField): string
    {
        if (!\current_user_can('manage_options')) {
            return '';
        }
        $a = Accent::clean($accent) ?? Accent::DEFAULT;
        $id = 'mgd-tools-accent';
        $hint = Accent::tooLight($a) ? '<p class="mgd-tools-note" role="status">' . Text::e(ToolsTexts::ACCENT_LIGHT) . '</p>' : '';
        $on = Accent::onAccent($a);

        return '<section class="mgd-kit-section" aria-labelledby="mgd-tools-h-accent"><h2 id="mgd-tools-h-accent">' . Text::e(ToolsTexts::ACCENT) . '</h2>'
            . '<form method="post" class="mgd-tools-settings">' . $nonceField
            . '<p><label for="' . $id . '">' . Text::e(ToolsTexts::ACCENT) . '</label></p>'
            . '<p><input type="text" id="' . $id . '" name="' . self::FIELD . '" value="' . Text::e($a) . '" data-mgd-color maxlength="7" pattern="#[0-9a-fA-F]{6}" aria-describedby="' . $id . '-help"></p>'
            . '<p class="description" id="' . $id . '-help">' . Text::e(ToolsTexts::ACCENT_HELP) . '</p>' . $hint
            . '<p class="mgd-tools-preview-title">' . Text::e(ToolsTexts::PREVIEW) . '</p>'
            . '<p class="mgd-tools-preview"><span class="mgd-tools-preview__menu" style="background:' . $a . ';color:' . $on . '">' . Text::e(ToolsTexts::MENU) . '</span>'
            . ' <span class="mgd-tools-badge" style="background:' . $a . ';color:' . $on . '">' . Text::e(ToolsTexts::CURRENT) . '</span>'
            . ' <span style="color:' . Accent::textOnWhite($a) . ';text-decoration:underline">' . Text::e(ToolsTexts::DETAILS) . '</span></p>'
            . '<p class="mgd-kit-actions"><button type="submit" class="button button-primary" name="mgd_tools_settings_do" value="save">' . Text::e(ToolsTexts::SAVE) . '</button> '
            . '<button type="submit" class="button" name="mgd_tools_settings_do" value="reset">' . Text::e(ToolsTexts::ACCENT_DEFAULT) . '</button></p></form></section>';
    }

    // Seite (nur Besitzer). Speichern vor dem Ausgeben, damit die neue Farbe gleich gilt
    public static function page(): void
    {
        try {
            if (!\current_user_can('manage_options')) {
                return;
            }
            $post = \wp_unslash($_POST);
            $post = is_array($post) ? $post : [];
            $ok = \wp_verify_nonce((string) ($post[self::NONCE] ?? ''), self::ACTION) !== false;
            $r = self::handle($post, $ok);
            $msg = '';
            if ($r !== null) {
                [$msg, $new] = $r;
                if ($new === null) {
                    \delete_option(self::OPT);
                } elseif ($ok) {
                    \update_option(self::OPT, $new, true);
                }
            }
            $a = self::accent();
            // neue Farbe sofort, admin_head war schon
            echo '<style>' . Accent::css($a) . '</style>';
            echo ToolsView::wrap(ToolsTexts::SETTINGS_PAGE, $msg, self::html($a, (string) \wp_nonce_field(self::ACTION, self::NONCE, true, false)), ToolsTexts::SETTINGS_INTRO);
        } catch (\Throwable) {
            echo '<div class="wrap"><p>' . ToolsTexts::ERROR . '</p></div>';
        }
    }
}
