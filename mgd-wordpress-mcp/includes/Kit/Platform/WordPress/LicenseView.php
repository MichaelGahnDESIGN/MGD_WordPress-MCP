<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

use Mgd\WordPressMcp\Kit\License\LicenseClient;
use Mgd\WordPressMcp\Kit\Support\Text;

// Lizenz-Karte: Feld, Speichern, Entfernen, Statuszeile. Nur manage_options, Schlüssel wird nie zurückgegeben (nur …abcd)
final class LicenseView
{
    public const NONCE = '_mgd_kit_lic_nonce';

    public static function nonceAction(string $product): string
    {
        return 'mgd_kit_lic_' . $product;
    }

    public static function field(string $product): string
    {
        return 'mgd_kit_lic_' . str_replace('-', '_', $product);
    }

    /** @param string $nonceField fertiges Hidden-Feld (wp_nonce_field) */
    public static function html(LicenseClient $l, string $product, string $nonceField): string
    {
        if (!$l->enabled() || !\current_user_can('manage_options')) {
            return '';
        }
        $f = self::field($product);
        $id = Text::e($f . '_token');
        $out = '<form method="post" class="mgd-kit-license" autocomplete="off">' . $nonceField
            . '<p><label for="' . $id . '">Lizenzschlüssel</label></p>'
            . '<p><input type="password" id="' . $id . '" name="' . Text::e($f . '_token') . '" value="" autocomplete="off" spellcheck="false" class="regular-text"></p>';
        if ($l->hasToken()) {
            $out .= '<p class="description">Eingetragen ist ein Schlüssel, der auf ' . Text::e($l->tokenHint()) . ' endet.</p>';
        }
        $out .= '<p class="mgd-kit-status" role="status">' . Text::e($l->status()) . '</p>'
            . '<p class="mgd-kit-actions"><button type="submit" class="button button-primary" name="' . Text::e($f . '_do') . '" value="save">Speichern</button>';
        if ($l->hasToken()) {
            $out .= ' <button type="submit" class="button" name="' . Text::e($f . '_do') . '" value="remove">Entfernen</button>';
        }

        return $out . '</p><p class="description">Zur Prüfung gehen nur der Lizenzschlüssel und die Domain deiner Seite an theme.michael-gahn.de.</p></form>';
    }

    /**
     * POST auswerten. null = Formular war nicht gemeint
     * @param array<string,mixed> $post schon wp_unslash
     */
    public static function handle(LicenseClient $l, string $product, array $post, bool $nonceOk): ?string
    {
        $f = self::field($product);
        $do = $post[$f . '_do'] ?? '';
        if (!$l->enabled() || !in_array($do, ['save', 'remove'], true)) {
            return null;
        }
        if (!$nonceOk || !\current_user_can('manage_options')) {
            return 'Das hat nicht geklappt. Bitte lade die Seite neu und versuche es noch einmal.';
        }
        if ($do === 'remove') {
            $l->forget();

            return 'Der Lizenzschlüssel ist entfernt.';
        }

        return $l->setToken(is_string($post[$f . '_token'] ?? null) ? $post[$f . '_token'] : '')
            ? 'Der Lizenzschlüssel ist gespeichert.'
            : 'Der Lizenzschlüssel sieht nicht richtig aus. Bitte kopiere ihn vollständig aus deinem Kundenkonto.';
    }
}
