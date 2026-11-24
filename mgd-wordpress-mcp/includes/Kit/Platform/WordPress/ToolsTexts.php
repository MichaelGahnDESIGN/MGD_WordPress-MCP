<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Platform\WordPress;

// alle Texte von MGD Tools an einem Ort (Freigabe Michael: docs/plans/MGD-TOOLS-TEXTE-FREIGABE.md)
final class ToolsTexts
{
    public const MENU = 'MGD Tools';
    public const OVERVIEW = 'Übersicht und Updates';
    public const OVERVIEW_H1 = 'Deine MGD-Plugins';
    public const LICENSE = 'Lizenz';
    public const CATALOG = 'Weitere Plugins';
    public const COLS = ['Plugin', 'Installiert', 'Neueste', 'Status', 'Update-Prüfung', 'Links'];
    public const CURRENT = 'Aktuell';
    public const NEWER = 'Update verfügbar';
    public const UNKNOWN = 'Unbekannt';
    public const CHECK_OK = 'Klappt, zuletzt am %s';
    public const CHECK_OK_NODATE = 'Klappt';
    public const CHECK_FAIL = 'Hat am %s nicht geklappt. WordPress versucht es später noch einmal.';
    public const CHECK_NONE = 'Noch keine Update-Datei veröffentlicht. Das ist normal bei neuen Plugins.';
    public const CHECK_OFF = 'Nicht eingerichtet';
    public const CHECK_NOW = 'Jetzt prüfen';
    public const CHECKED = 'Die Update-Prüfung wurde für alle MGD-Plugins neu gestartet.';
    public const SETTINGS = 'Einstellungen';
    public const DETAILS = 'Details';
    public const UPDATE_LINK = 'Zu den Plugins';
    public const INSTALL = 'Installation melden';
    public const LICENSE_INTRO = 'Trag deinen Lizenzschlüssel hier einmal ein. Er gilt für alle MGD-Plugins auf dieser Website.';
    public const LICENSE_UNLOCKS = 'Dieser Schlüssel schaltet frei:';
    public const LICENSE_OFF = 'Für deine MGD-Plugins gibt es noch keine Pro-Funktionen.';
    public const LICENSE_ELSEWHERE = 'Die Lizenz verwaltest du unter MGD Tools › Lizenz.';
    public const MOVED = 'Die MGD-Plugins findest du jetzt gesammelt im Menü „MGD Tools“.';
    public const ERROR = 'Die Seite konnte gerade nicht geladen werden.';
    public const RETRY = 'Das hat nicht geklappt. Bitte lade die Seite neu und versuche es noch einmal.';
}
