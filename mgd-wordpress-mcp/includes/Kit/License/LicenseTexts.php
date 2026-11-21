<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\License;

// Sätze zum Lizenzstatus, nie mit Schlüssel drin
final class LicenseTexts
{
    public const FREE_TAIL = ' Das Plugin läuft mit den Free-Funktionen.';

    private const REASONS = [
        'disabled' => 'Für dieses Plugin gibt es noch keine Pro-Lizenz.',
        'no_token' => 'Kein Lizenzschlüssel eingetragen.' . self::FREE_TAIL,
        'unreachable' => 'Der Lizenzserver ist gerade nicht erreichbar. Das Plugin läuft mit den Free-Funktionen weiter.',
        'token_expired' => 'Dein Lizenzschlüssel ist abgelaufen.' . self::FREE_TAIL,
        'token_revoked' => 'Dein Lizenzschlüssel wurde widerrufen oder gekündigt.' . self::FREE_TAIL,
        'domain_mismatch' => 'Dieser Lizenzschlüssel gilt nicht für diese Domain.' . self::FREE_TAIL,
        'signature_invalid' => 'Der Lizenzschlüssel ist nicht gültig. Bitte kopiere ihn vollständig aus deinem Kundenkonto.',
        'token_malformed' => 'Der Lizenzschlüssel ist nicht gültig. Bitte kopiere ihn vollständig aus deinem Kundenkonto.',
        'unknown_tier' => 'Die Lizenz gehört zu keiner bekannten Stufe.' . self::FREE_TAIL,
    ];

    /** @param array{valid:bool,tier:string,reason:string,source:string} $s */
    public static function status(array $s): string
    {
        if ($s['valid'] && $s['tier'] !== 'free') {
            return $s['source'] === 'grace'
                ? 'Pro ist aktiv. Der Lizenzserver ist gerade nicht erreichbar, es gilt der zuletzt geprüfte Stand.'
                : 'Pro ist aktiv. Dein Lizenzschlüssel ist gültig.';
        }
        if ($s['valid']) {
            return 'Dein Lizenzschlüssel ist gültig, gehört aber zur Free-Stufe.' . self::FREE_TAIL;
        }

        return self::REASONS[$s['reason']] ?? 'Der Lizenzschlüssel ist nicht gültig.' . self::FREE_TAIL;
    }
}
