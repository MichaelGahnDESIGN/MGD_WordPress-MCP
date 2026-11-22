<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\Support\Text;
use Mgd\WordPressMcp\Kit\Update\UpdateInfo;

// Hinweis "Bitte aktualisieren" aus dem notice-Feld als Admin-Notice, nur für manage_options
final class NoticeView
{
    public static function register(Updater $updater, string $pluginName): void
    {
        \add_action('admin_notices', static function () use ($updater, $pluginName): void {
            try {
                echo self::output($updater, $pluginName, gmdate('Y-m-d'));
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
