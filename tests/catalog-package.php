<?php
/** Paketvertrag: gemeinsame Katalogdaten, neue Klasse, Versionsangaben und lokale Grafiken. */
$root = dirname(__DIR__);
$config = json_decode(file_get_contents(__DIR__ . '/catalog-config.json'), true);
$runtime = $root . '/' . $config['runtime'];
function mgd_catalog_assert(bool $ok, string $name): void {
    if (!$ok) { fwrite(STDERR, 'FAIL ' . $name . "\n"); exit(1); }
}
$main = file_get_contents($runtime . '/' . $config['main']);
mgd_catalog_assert((bool) preg_match('/Version:\s*' . preg_quote($config['version'], '/') . '\s/', $main), 'Pluginversion');
$metadata = json_decode(file_get_contents($runtime . '/includes/kit-fallback-plugins.json'), true);
mgd_catalog_assert(count($metadata) === 7, 'Sieben bekannte WordPress-Plugins');
foreach ($metadata as $repo => $item) {
    mgd_catalog_assert($item['repository'] === $repo && $item['tier'] === 'free', 'Bekannte kostenlose Einträge');
}
$bootstrap = $main;
foreach (glob($runtime . '/includes/*.php') as $file) { $bootstrap .= file_get_contents($file); }
// ab 0.6.1: alte Katalogseite ganz raus, Weitere Plugins nur noch aus dem Baukasten
mgd_catalog_assert(!is_file($runtime . '/includes/class-mgd-wordpress-plugin-catalog.php') && !is_dir($runtime . '/includes/plugin-catalog') && !is_dir($runtime . '/assets/plugin-catalog'), 'Alte Katalogseite entfernt');
mgd_catalog_assert(!str_contains($bootstrap, 'Catalog_V4') && !str_contains($bootstrap, 'mgd-wordpress-plugin-catalog'), 'Keine Reste der alten Seite');
mgd_catalog_assert(is_file($runtime . '/includes/Kit/Platform/WordPress/CatalogInstaller.php') && is_file($runtime . '/includes/Kit/Platform/WordPress/assets/kit-catalog.css'), 'Neue Ansicht im Baukasten');
mgd_catalog_assert(!str_contains($bootstrap, 'catalogActions'), 'Kein eigener catalogActions-Aufruf');
// ab 0.6.0: Baukasten ab PHP 8.1, alter Updater nur noch als Rückfall drunter
mgd_catalog_assert(is_file($runtime . '/includes/Kit/Platform/WordPress/Updater.php'), 'Baukasten vorhanden');
mgd_catalog_assert(str_contains($main, "define('MGD_WPMCP_KIT',PHP_VERSION_ID>=80100)"), 'PHP-Weiche');
mgd_catalog_assert((bool) preg_match('/if \(MGD_WPMCP_KIT\) \{\s*\/\/[^\n]*\n\s*MGD_WordPress_MCP_Kit::boot/', $main), 'Baukasten startet ab 8.1');
mgd_catalog_assert((bool) preg_match('/\} else \{[^}]*MGD_Plugin_Admin_Tools_20261023::register/s', $main), 'Alter Updater nur im else-Zweig');
mgd_catalog_assert(str_contains($bootstrap, "if(!MGD_WPMCP_KIT){MGD_WordPress_MCP_Updater::instance();}"), 'Alter GitHub-Updater ab 8.1 aus');
mgd_catalog_assert(str_contains($bootstrap, "const PRODUCT = 'mgd-mcp-wordpress';"), 'Produktname');
mgd_catalog_assert(str_contains($bootstrap, "self::\$updater, \$list, false, ''"), 'Lizenz-Baustein aus');
mgd_catalog_assert(str_contains($bootstrap, "cleanupLegacy( self::PRODUCT, MGD_WPMCP_VERSION, array( 'mgd_wpmcp_release_*' ) )"), 'Alte Caches aufräumen');
$uninstall = file_get_contents($runtime . '/uninstall.php');
mgd_catalog_assert(strpos($uninstall, "Setup::uninstall( 'mgd-mcp-wordpress', 'wordpress' )") < strpos($uninstall, "'MGD_WPMCP_PURGE_ON_UNINSTALL' ) ||"), 'Abmelden vor dem PURGE-Ausstieg');
echo "PASS Pluginversion, Ersatzliste, Baukasten und alte Katalogseite entfernt\n";
