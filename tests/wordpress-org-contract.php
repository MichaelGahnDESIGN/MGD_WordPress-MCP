<?php
/** Einreichungsvertrag: Metadaten, Update-Kanal und Verzeichnisgrafiken. */
$root = dirname(__DIR__);
$config = json_decode(file_get_contents($root . '/wordpress-org/package.json'), true);
$stage = $root . '/build/wordpress-org/' . $config['slug'];
$failures = array();
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS)) as $file) {
    if ($file->getExtension() !== 'php') { continue; }
    $source = file_get_contents($file->getPathname());
    if (preg_match('/Update URI:\s*https?:/i', $source)) { $failures[] = 'Externe Update URI: ' . $file; }
    if (preg_match('/add_filter\s*\(\s*[\x22\x27](?:pre_set_site_transient_update_plugins|site_transient_update_plugins|update_plugins_github.com|plugins_api)[\x22\x27]/', $source)) { $failures[] = 'Überschriebener Update- oder Detailkanal: ' . $file; }
    if (strpos($source, 'api.github.com/repos/') !== false) { $failures[] = 'GitHub-Release-API im Paket: ' . $file; }
}
foreach (array('includes/plugin-catalog', 'assets/plugin-catalog', 'includes/class-mgd-wordpress-plugin-catalog.php') as $mgd_forbidden) {
    if (file_exists($stage . '/' . $mgd_forbidden)) { $failures[] = 'Externer Empfehlungskatalog im Verzeichnispaket: ' . $mgd_forbidden; }
}
$readme = file_get_contents($stage . '/readme.txt');
if (strpos($readme, 'Contributors: michaelgahndesign') === false) { $failures[] = 'Mitwirkender fehlt'; }
$mgd_header = explode('== Description ==', $readme, 2)[0];
$mgd_intro = explode("\n\n", trim($mgd_header), 2);
if (count($mgd_intro) !== 2 || strlen(trim($mgd_intro[1])) > 150) { $failures[] = 'Kurzbeschreibung muss vor dem ersten Abschnitt stehen und maximal 150 Zeichen lang sein'; }
foreach (array('icon-128x128.png' => array(128,128), 'icon-256x256.png' => array(256,256), 'banner-772x250.png' => array(772,250), 'banner-1544x500.png' => array(1544,500)) as $name => $size) {
    $image = getimagesize($root . '/wordpress-org/assets/' . $name);
    if (!$image || $image[0] !== $size[0] || $image[1] !== $size[1]) { $failures[] = 'Grafikgröße: ' . $name; }
}
$mgd_screenshots = $root . '/wordpress-org/assets/screenshot-1.png';
if (!is_file($mgd_screenshots) || !getimagesize($mgd_screenshots) || strpos($readme, '== Screenshots ==') === false) { $failures[] = 'Echter Screenshot oder Readme-Beschreibung fehlt'; }
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS)) as $mgd_file) {
    if ($mgd_file->getExtension() !== 'php') { continue; }
    $mgd_source = file_get_contents($mgd_file->getPathname());
    if (preg_match('/Plugin Name:\s*([^\r\n]+)/', $mgd_source) && preg_match('/Version:\s*([0-9.]+)/', $mgd_source, $mgd_version)) {
        if (!preg_match('/^Stable tag: ' . preg_quote($mgd_version[1], '/') . '$/m', $readme)) { $failures[] = 'Stable tag und Plugin-Version unterscheiden sich'; }
    }
    if (strpos($mgd_source, 'page=mgd-wordpress-mcp') !== false || strpos($mgd_source, 'tools_page_mgd-wordpress-mcp') !== false) { $failures[] = 'Veralteter MCP-Verwaltungslink'; }
}
if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "PASS WordPress.org-Metadaten, nativer Updatekanal und vier Grafikgrößen\n";
