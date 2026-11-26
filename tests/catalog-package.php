<?php
/** Paketvertrag ab MGD Tools: Version, Baukasten, kein eigenes Menü, alter Kram raus. */
$root = dirname(__DIR__);
$config = json_decode(file_get_contents(__DIR__ . '/catalog-config.json'), true);
$runtime = $root . '/' . $config['runtime'];
function mgd_catalog_assert(bool $ok, string $name): void {
    if (!$ok) { fwrite(STDERR, 'FAIL ' . $name . "\n"); exit(1); }
}
$main = file_get_contents($runtime . '/' . $config['main']);
mgd_catalog_assert((bool) preg_match('/Version:\s*' . preg_quote($config['version'], '/') . '\s/', $main), 'Pluginversion');
mgd_catalog_assert(str_contains($main, 'Requires PHP: 8.1'), 'PHP 8.1 im Kopf');
$bootstrap = $main;
foreach (glob($runtime . '/includes/*.php') as $file) { $bootstrap .= file_get_contents($file); }
// alter Kram muss weg
foreach (['includes/class-mgd-wordpress-plugin-catalog.php', 'includes/plugin-catalog', 'assets/plugin-catalog', 'includes/class-mgd-plugin-admin-tools.php', 'includes/class-mgd-wordpress-mcp-updater.php', 'includes/class-mgd-wordpress-mcp-kit.php', 'includes/plugin-icon.php'] as $old) {
    mgd_catalog_assert(!file_exists($runtime . '/' . $old), 'Alt entfernt: ' . $old);
}
mgd_catalog_assert(!str_contains($bootstrap, 'MGD_WPMCP_KIT') && !str_contains($bootstrap, 'Catalog_V4') && !str_contains($bootstrap, 'Admin_Tools'), 'Keine Reste alter Bausteine');
mgd_catalog_assert(!str_contains($bootstrap, 'add_menu_page') && !str_contains($bootstrap, 'add_management_page') && !str_contains($bootstrap, 'add_submenu_page'), 'Kein eigenes Menü');
mgd_catalog_assert(!str_contains($bootstrap, 'array($this,\'row_meta\')') && !str_contains($bootstrap, 'array($this,\'action_links\')'), 'Keine Hooks auf gelöschte Methoden');
// Baukasten
mgd_catalog_assert(is_file($runtime . '/includes/Kit/Platform/WordPress/Tools.php'), 'Baukasten mit MGD Tools');
mgd_catalog_assert(str_contains($main, "Setup::updater(__FILE__, 'mgd-mcp-wordpress'"), 'Updater, Produktname');
mgd_catalog_assert(str_contains($main, "Setup::tools(__FILE__, 'mgd-mcp-wordpress'") && str_contains($main, "'page' => 'mgd-wordpress-mcp'"), 'MGD Tools, alte Seiten-Kennung');
mgd_catalog_assert(str_contains($main, "'license'  => false"), 'Lizenz aus');
mgd_catalog_assert(str_contains($main, "array('mgd_wpmcp_release_*')"), 'Alte Caches aufräumen');
mgd_catalog_assert(!str_contains($bootstrap, 'catalogActions'), 'Kein eigener catalogActions-Aufruf');
mgd_catalog_assert(is_file($runtime . '/assets/icon-128x128.png') && is_file($runtime . '/assets/icon-256x256.png'), 'Icons');
$uninstall = file_get_contents($runtime . '/uninstall.php');
mgd_catalog_assert(strpos($uninstall, "Setup::uninstall( 'mgd-mcp-wordpress', 'wordpress' )") < strpos($uninstall, "'MGD_WPMCP_PURGE_ON_UNINSTALL' ) ||"), 'Abmelden vor dem PURGE-Ausstieg');
echo "PASS Version, Baukasten mit MGD Tools, kein eigenes Menü, alte Bausteine entfernt\n";

// 2.1.4 Hinweis nur auf eigener Seite, kein Redirect aus MGD Tools
$mcp_src = file_get_contents(__DIR__ . '/../mgd-wordpress-mcp/includes/class-mgd-wordpress-mcp.php');
mgd_catalog_assert(false !== strpos($mcp_src, "'mgd-wordpress-mcp'!==\$page"), 'Hinweis nur auf eigener Seite');
$wiz_src = file_get_contents(__DIR__ . '/../mgd-wordpress-mcp/includes/class-mgd-wordpress-mcp-wizard.php');
mgd_catalog_assert(false !== strpos($wiz_src, "page=mgd-tools"), 'kein Redirect aus MGD Tools');
