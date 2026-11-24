<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Support\Text;
use Mgd\WordPressMcp\Kit\Update\UpdateInfo;

// Hinweis "Bitte aktualisieren" aus dem notice-Feld: in der Plugin-Liste + Dashboard, nur für manage_options
final class NoticeView
{
    public static function register(Updater $updater, string $pluginName): void
    {
        // kein admin_notices mehr (Regel: keine Dauer-Hinweise), nur in der Plugin-Liste unter dem Update + in MGD Tools
        \add_action('in_plugin_update_message-' . $updater->basename(), static function () use ($updater): void {
            try {
                $n = $updater->info()?->notice;
                if ($n !== null && \current_user_can('manage_options') && $n->shouldShow($updater->installedVersion(), gmdate('Y-m-d'))) {
                    echo ' ' . Text::e($n->text);
                }
            } catch (\Throwable) {
                // nie den WP-Admin killen
            }
        });
    }

    public static function output(Updater $updater, string $pluginName, string $today): string
    {
        try {
            if (!\current_user_can('manage_options')) {
                return '';
            }

            return self::html($updater->info(), $updater->installedVersion(), $today, $pluginName);
        } catch (\Throwable) {
            return '';
        }
    }

    public static function html(?UpdateInfo $u, string $installed, string $today, string $pluginName): string
    {
        $n = $u?->notice;
        if ($n === null || !$n->shouldShow($installed, $today)) {
            return '';
        }

        return '<div class="notice notice-warning mgd-kit-notice"><p><strong>' . Text::e($pluginName) . ':</strong> '
            . Text::e($n->text) . ' <a href="' . Text::e($n->url) . '" target="_blank" rel="noopener noreferrer">Zur aktuellen Version</a></p></div>';
    }
}
