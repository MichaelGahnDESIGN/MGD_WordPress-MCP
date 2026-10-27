<?php
/** Feed-Bilder: Icon, Hintergrund, Alt-Text, Akzentfarbe, Version. Läuft ohne WordPress mit kleinen Attrappen. */
if (defined('ABSPATH')) { fwrite(STDERR, "Nur als eigenständigen Test ausführen.\n"); exit(1); }
define('ABSPATH', __DIR__ . '/catalog-fixtures/');
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);

$uploads = sys_get_temp_dir() . '/mgd-catalog-media-' . bin2hex(random_bytes(4));
mkdir($uploads, 0755, true);
$transients = [];
$requests = [];
$responses = [];
class WP_Error {}
function is_wp_error($v) { return $v instanceof WP_Error; }
function wp_upload_dir($time = null, $create = true) { global $uploads; return ['basedir' => $uploads, 'baseurl' => 'https://shop.test/wp-content/uploads', 'error' => false]; }
function wp_mkdir_p($dir) { return is_dir($dir) || mkdir($dir, 0755, true); }
function get_transient($key) { global $transients; return $transients[$key] ?? false; }
function set_transient($key, $value, $ttl) { global $transients; $transients[$key] = $value; return true; }
function sanitize_text_field($v) { return trim(strip_tags((string) $v)); }
function esc_url_raw($v) { return $v; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function wp_remote_get($url, $args = []) {
    global $requests, $responses;
    $requests[] = $url;
    $r = $responses[$url] ?? null;
    return $r === null ? new WP_Error() : ['code' => $r[0], 'body' => $r[1]];
}
function wp_remote_retrieve_response_code($r) { return $r['code']; }
function wp_remote_retrieve_body($r) { return $r['body']; }
function get_plugins() { return []; }
function get_site_transient($key) { return (object) ['response' => []]; }
function current_user_can($cap) { return true; }
function is_plugin_active($file) { return false; }
function untrailingslashit($v) { return rtrim($v, '/'); }

function check(bool $ok, string $name): void {
    if (!$ok) { fwrite(STDERR, 'FAIL ' . $name . "\n"); exit(1); }
}

$config = json_decode(file_get_contents(__DIR__ . '/catalog-config.json'), true);
require dirname(__DIR__) . '/' . $config['runtime'] . '/includes/plugin-catalog/class-provider.php';

// kleinstes gültiges WebP (1x1)
$webp = base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA');
$hex = static fn(string $c) => str_repeat($c, 32);
$url = static fn(string $c) => 'https://theme.michael-gahn.de/uploads/catalog/' . str_repeat($c, 32) . '.webp';

// 1) URL-Prüfung: nur https, eigener Host, Platform-Dateiname
check(MGD_Catalog_V4_Media::name_from_url($url('a')) === $hex('a') . '.webp', 'Gültige URL');
foreach ([
    'http://theme.michael-gahn.de/uploads/catalog/' . $hex('a') . '.webp',
    'https://evil.example/uploads/catalog/' . $hex('a') . '.webp',
    'https://theme.michael-gahn.de.evil.example/uploads/catalog/' . $hex('a') . '.webp',
    'https://theme.michael-gahn.de/uploads/catalog/' . $hex('a') . '.webp?x=1',
    'https://theme.michael-gahn.de/uploads/catalog/' . $hex('A') . '.webp',
    'https://theme.michael-gahn.de/uploads/catalog/' . $hex('a') . '.svg',
    'https://theme.michael-gahn.de/uploads/catalog/../' . $hex('a') . '.webp',
    '', null, 5, ['x'],
] as $bad) {
    check(MGD_Catalog_V4_Media::name_from_url($bad) === '', 'Ungültige URL abgelehnt');
}

// 2) Farbe
check(MGD_Catalog_V4_Media::accent('#1a2B3c') === '#1a2B3c', 'Gültige Farbe');
foreach (['red', '#12345', '#1234567', 'url(x)', '#12345g', null, ''] as $bad) {
    check(MGD_Catalog_V4_Media::accent($bad) === '', 'Ungültige Farbe abgelehnt');
}

// 3) Download, Cache, kein zweiter Abruf
$responses[$url('a')] = [200, $webp];
$failed = 0;
check(MGD_Catalog_V4_Media::sync($url('a'), $failed) === $hex('a') . '.webp', 'Download klappt');
check(is_file($uploads . '/mgd-plugin-catalog/' . $hex('a') . '.webp'), 'Datei liegt lokal');
check(is_file($uploads . '/mgd-plugin-catalog/index.php'), 'index.php Schutz');
check(MGD_Catalog_V4_Media::url($hex('a') . '.webp') === 'https://shop.test/wp-content/uploads/mgd-plugin-catalog/' . $hex('a') . '.webp', 'Lokale URL');
$before = count($requests);
check(MGD_Catalog_V4_Media::sync($url('a'), $failed) === $hex('a') . '.webp' && count($requests) === $before, 'Zweiter Aufruf ohne Netz');
check(MGD_Catalog_V4_Media::url($hex('b') . '.webp') === '' && MGD_Catalog_V4_Media::url('../x.webp') === '', 'Fehlende/ungültige Datei ohne URL');

// 4) Kaputte Antworten werden nie gespeichert
$responses[$url('b')] = [200, '<html>kein Bild</html>' . str_repeat('x', 40)];
$responses[$url('c')] = [200, substr_replace($webp, pack('V', 999), 4, 4)];
$responses[$url('d')] = [404, $webp];
$responses[$url('e')] = [200, 'RIFF' . pack('V', 614500) . 'WEBP' . str_repeat('x', 614500)];
$failed = 0;
foreach (['b', 'c'] as $c) { check(MGD_Catalog_V4_Media::sync($url($c), $failed) === '', 'Kaputte Datei abgelehnt ' . $c); }
check($failed === 2, 'Fehler gezählt');
check(!is_file($uploads . '/mgd-plugin-catalog/' . $hex('b') . '.webp') && !is_file($uploads . '/mgd-plugin-catalog/' . $hex('c') . '.webp'), 'Kaputtes nie gespeichert');

// 5) Fehlerbudget: nach 2 Fehlern kein weiterer Abruf
$before = count($requests);
check(MGD_Catalog_V4_Media::sync($url('d'), $failed) === '' && count($requests) === $before, 'Fehlerbudget stoppt Abrufe');
$failed = 0;
check(MGD_Catalog_V4_Media::sync($url('d'), $failed) === '' && $failed === 1, '404 zählt als Fehler');
check(MGD_Catalog_V4_Media::sync($url('e'), $failed) === '', 'Zu große Datei abgelehnt');

// 6) Aufräumen: nur alte, nicht mehr genutzte Dateien
$dir = $uploads . '/mgd-plugin-catalog';
file_put_contents($dir . '/' . $hex('1') . '.webp', $webp);
file_put_contents($dir . '/' . $hex('2') . '.webp', $webp);
file_put_contents($dir . '/' . $hex('3') . '.webp', $webp);
touch($dir . '/' . $hex('1') . '.webp', time() - 30 * 86400);
touch($dir . '/' . $hex('2') . '.webp', time() - 30 * 86400);
MGD_Catalog_V4_Media::prune([$hex('2') . '.webp', $hex('a') . '.webp']);
check(!is_file($dir . '/' . $hex('1') . '.webp'), 'Alte ungenutzte Datei weg');
check(is_file($dir . '/' . $hex('2') . '.webp') && is_file($dir . '/' . $hex('3') . '.webp') && is_file($dir . '/' . $hex('a') . '.webp'), 'Genutzte und frische Dateien bleiben');

// 7) Ganzer Katalog aus einem Feed
$feed = [
    'schemaVersion' => 1,
    'items' => [
        ['id' => 'a', 'name' => 'Mit Bildern', 'platform' => 'wordpress', 'status' => 'live', 'tier' => 'paid', 'version' => 'v1.2.3', 'repositoryUrl' => 'https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress', 'detailsUrl' => 'https://theme.michael-gahn.de/plugins', 'iconUrl' => $url('a'), 'imageUrl' => $url('a'), 'imageAlt' => 'Blaues Motiv', 'accent' => '#12ab34'],
        ['id' => 'b', 'name' => 'Ohne Bilder', 'platform' => 'wordpress', 'status' => 'live', 'tier' => 'paid', 'version' => '2.0.0', 'repositoryUrl' => 'https://github.com/MichaelGahnDESIGN/MGD-Share-WordPress', 'detailsUrl' => 'https://theme.michael-gahn.de/plugins', 'iconUrl' => null, 'imageUrl' => null, 'imageAlt' => null, 'accent' => null],
        ['id' => 'c', 'name' => 'Böse Felder', 'platform' => 'wordpress', 'status' => 'live', 'tier' => 'paid', 'version' => '3.0.0', 'repositoryUrl' => 'https://github.com/MichaelGahnDESIGN/MGD-Maintenance-WordPress', 'detailsUrl' => 'https://theme.michael-gahn.de/plugins', 'iconUrl' => 'http://theme.michael-gahn.de/uploads/catalog/' . $hex('a') . '.webp', 'imageUrl' => 'https://evil.example/x.webp', 'imageAlt' => 'Sollte nicht erscheinen', 'accent' => 'url(javascript:1)'],
    ],
];
$responses['https://theme.michael-gahn.de/api/catalog/plugins.json'] = [200, json_encode($feed)];
$provider = new MGD_Catalog_V4_Provider();
$catalog = $provider->catalog(true);
check($catalog['source'] === 'central' && count($catalog['items']) === 3, 'Zentralkatalog gelesen');
$by = [];
foreach ($catalog['items'] as $i) { $by[$i['repository']] = $i; }
$a = $by['MGD-Notification-WordPress'];
check($a['version'] === '1.2.3', 'Version ohne v');
check($a['icon_file'] === $hex('a') . '.webp' && $a['image_file'] === $hex('a') . '.webp', 'Bilder übernommen');
check($a['image_alt'] === 'Blaues Motiv' && $a['accent'] === '#12ab34', 'Alt-Text und Akzent übernommen');
$b = $by['MGD-Share-WordPress'];
check($b['icon_file'] === '' && $b['image_file'] === '' && $b['image_alt'] === '' && $b['accent'] === '', 'Ohne Felder: leer, kein Fehler');
$c = $by['MGD-Maintenance-WordPress'];
check($c['icon_file'] === '' && $c['image_file'] === '' && $c['image_alt'] === '' && $c['accent'] === '', 'Ungültige Felder verworfen');
check(!array_filter($requests, static fn($u) => strpos($u, 'evil.example') !== false), 'Fremder Host nie abgerufen');

$decorated = $provider->decorate($catalog['items']);
$da = array_values(array_filter($decorated, static fn($i) => $i['repository'] === 'MGD-Notification-WordPress'))[0];
check(strpos($da['icon_url'], 'https://shop.test/wp-content/uploads/mgd-plugin-catalog/') === 0 && $da['image_url'] !== '', 'Lokale Bild-URLs im Admin');
$db = array_values(array_filter($decorated, static fn($i) => $i['repository'] === 'MGD-Share-WordPress'))[0];
check($db['icon_url'] === '' && $db['image_url'] === '', 'Ohne Bild: Fallback auf mitgeliefert');
// alter Cache ohne die neuen Felder
$old = [['repository' => 'MGD-Share-WordPress', 'basenames' => [], 'url' => 'https://github.com/x']];
$dold = $provider->decorate($old)[0];
check($dold['icon_url'] === '' && $dold['accent'] === '' && $dold['image_alt'] === '', 'Alter Cache ohne neue Felder');

// aufräumen
foreach (glob($dir . '/*') as $f) { @unlink($f); }
@rmdir($dir); @rmdir($uploads);
echo "PASS Feed-Bilder: URL-Prüfung, Download, Cache, Fehlerbudget, Aufräumen, Version, Akzent und Fallback\n";
