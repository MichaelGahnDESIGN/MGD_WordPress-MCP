<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Install;

// Schalter + Hinweis für die Update-/Einstellungsseite des Plugins (alle Systeme gleich)
final class Settings
{
    public const FIELD = 'mgd_install_report';
    public const ACTION = 'mgd_install_save';

    // Hinweistext, von Michael freigegeben am 18.11.2026 (docs/plans/FREE-LIZENZEN-HINWEISTEXTE.md)
    public const HINT = 'Dieses Plugin meldet sich einmal bei theme.michael-gahn.de an und danach höchstens einmal am Tag. '
        . 'Gesendet werden nur: Plugin-Name, Version, Shopsystem, die Domain deines Shops und eine zufällige Installations-ID. '
        . 'So wissen wir, wie viele Shops das Plugin nutzen, und können dir Updates bereitstellen. '
        . 'Du kannst das hier jederzeit abschalten. Das Plugin funktioniert dann genauso weiter.';

    public const LABEL = 'Installation bei Michael Gahn DESIGN melden';

    // je Produkt eigene Feldnamen, sonst stören sich zwei MGD-Plugins im selben Admin
    public static function actionName(InstallClient $c): string
    {
        return self::ACTION . '_' . str_replace('-', '_', $c->product());
    }

    public static function fieldName(InstallClient $c): string
    {
        return self::FIELD . '_' . str_replace('-', '_', $c->product());
    }

    /** @param string $csrfField fertiges Hidden-Feld mit dem CSRF-Token des Systems */
    public static function render(InstallClient $c, string $csrfField): string
    {
        $on = $c->isEnabled();

        return '<form method="post" class="mgd-install">' . $csrfField
            . '<input type="hidden" name="' . self::actionName($c) . '" value="1">'
            . '<label><input type="checkbox" name="' . self::fieldName($c) . '" value="1"' . ($on ? ' checked' : '') . '> ' . self::e(self::LABEL) . '</label>'
            . '<p class="small text-muted description">' . self::e(self::HINT) . '</p>'
            . '<p class="small">' . self::e($c->status()) . '</p>'
            . '<button type="submit" class="btn btn-default button">Speichern</button></form>';
    }

    /**
     * POST auswerten. CSRF + Rechte prüft der Aufrufer VORHER.
     * @param array<string,mixed> $post
     * @return bool true = Formular war gemeint und gespeichert
     */
    public static function handle(InstallClient $c, array $post): bool
    {
        if (($post[self::actionName($c)] ?? '') !== '1') {
            return false;
        }
        $c->setEnabled(($post[self::fieldName($c)] ?? '') === '1');

        return true;
    }

    private static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
