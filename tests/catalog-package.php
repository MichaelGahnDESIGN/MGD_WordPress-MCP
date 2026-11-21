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
$metadata = json_decode(file_get_contents($runtime . '/includes/plugin-catalog/plugins.json'), true);
mgd_catalog_assert(count($metadata) === 7, 'Sieben bekannte WordPress-Plugins');
foreach ($metadata as $repo => $item) {
    mgd_catalog_assert($item['repository'] === $repo && $item['tier'] === 'free', 'Bekannte kostenlose Einträge');
    $file = $runtime . '/assets/plugin-catalog/icons/' . $item['icon'];
    mgd_catalog_assert(is_file($file) && filesize($file) > 100, 'Lokales Icon ' . $repo);
    mgd_catalog_assert(!str_contains(file_get_contents($file), 'https://'), 'Kein externer Bildabruf im Icon');
    $picture = $runtime . '/assets/plugin-catalog/backgrounds/' . pathinfo($item['icon'], PATHINFO_FILENAME) . '.webp';
    $size = is_file($picture) ? getimagesize($picture) : false;
    mgd_catalog_assert($size && $size[0] === 1080 && $size[1] === 360 && $size['mime'] === 'image/webp', 'Lokales Kartenmotiv ' . $repo);
    mgd_catalog_assert(filesize($picture) < 80000, 'Kompaktes Kartenmotiv ' . $repo);
}
foreach (array('class-provider.php', 'view.php', 'plugins.json') as $name) {
    mgd_catalog_assert(is_file($runtime . '/includes/plugin-catalog/' . $name), 'Katalogbestandteil ' . $name);
}
$bootstrap = $main;
foreach (glob($runtime . '/includes/*.php') as $file) { $bootstrap .= file_get_contents($file); }
mgd_catalog_assert((bool) preg_match('/new\s+\\\\?MGD_WordPress_Plugin_Catalog_V4\s*\(/', $bootstrap), 'Versionsfester Einstiegspunkt');
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
echo "PASS Pluginversion, sieben Katalogeinträge, lokale Icons und vollständige Laufzeitdateien\n";
