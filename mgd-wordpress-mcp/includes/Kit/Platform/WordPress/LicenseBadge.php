<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\License\LicenseClient;
use Mgd\WordPressMcp\Kit\Support\Text;

// Lizenz je Plugin: Art (Free/Pro) + Status, aus der zentralen Lizenz. Rein, ohne WP testbar
final class LicenseBadge
{
    // klares "vorbei" vs. "stimmt nicht"
    private const EXPIRED = ['token_expired', 'token_revoked'];

    /**
     * @param list<string> $pro Berechtigungen, die das Plugin freischalten (leer = keine Pro-Version)
     * @param array{valid:bool,tier:string,reason:string,source:string,entitlements:list<string>} $state LicenseClient::state()
     * @return array{art:string,status:string,tone:string}
     */
    public static function of(array $pro, array $state, int $end = 0): array
    {
        if ($pro === []) {
            return ['art' => ToolsTexts::LIC_FREE, 'status' => ToolsTexts::LIC_NO_PRO, 'tone' => 'grey'];
        }
        $has = $state['valid'] && array_intersect($pro, $state['entitlements']) !== [];
        if ($has) {
            $status = match (true) {
                $state['source'] === 'grace' => ToolsTexts::LIC_GRACE,
                $end > 0 => sprintf(ToolsTexts::LIC_VALID_UNTIL, self::date($end)),
                default => ToolsTexts::LIC_VALID,
            };

            return ['art' => ToolsTexts::LIC_PRO, 'status' => $status, 'tone' => $state['source'] === 'grace' ? 'orange' : 'green'];
        }
        [$status, $tone] = match (true) {
            $state['valid'] => [ToolsTexts::LIC_NOT_INCLUDED, 'grey'],
            in_array($state['reason'], ['no_token', 'disabled'], true) => [ToolsTexts::LIC_NONE, 'grey'],
            in_array($state['reason'], self::EXPIRED, true) => [ToolsTexts::LIC_EXPIRED, 'red'],
            // Server weg ohne Gnadenfrist: nix behaupten
            $state['reason'] === 'unreachable' => [ToolsTexts::UNKNOWN, 'grey'],
            default => [ToolsTexts::LIC_INVALID, 'red'],
        };

        return ['art' => ToolsTexts::LIC_FREE, 'status' => $status, 'tone' => $tone];
    }

    /** @param list<string> $pro */
    public static function fromClient(array $pro, LicenseClient $l): array
    {
        return self::of($pro, $l->state(), $l->periodEnd());
    }

    /** @param array{art:string,status:string,tone:string} $b */
    public static function html(array $b): string
    {
        return '<span class="mgd-tools-lic"><strong>' . Text::e($b['art']) . '</strong> <span class="mgd-tools-badge mgd-tools-badge--' . Text::e($b['tone']) . '">'
            . Text::e($b['status']) . '</span></span>';
    }

    private static function date(int $ts): string
    {
        return function_exists('wp_date') ? (string) \wp_date('d.m.Y', $ts) : gmdate('d.m.Y', $ts);
    }
}
