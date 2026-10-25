<?php
/** Regression: Ein altes WordPress-Updateangebot darf nach der Installation nicht erneut angeboten werden. */
if (defined('ABSPATH')) { fwrite(STDERR, "Nur als eigenständigen Test ausführen.\n"); exit(1); }
define('ABSPATH', __DIR__ . '/catalog-fixtures/');
$fixture_offer = null;
$fixture_permission = true;
function get_plugins() { return ['example/example.php' => ['Version' => '1.0.6', 'UpdateURI' => 'https://github.com/MichaelGahnDESIGN/MGD-Example-WordPress']]; }
function get_site_transient($key) { global $fixture_offer; return (object) ['response' => ['example/example.php' => $fixture_offer]]; }
function current_user_can($cap) { global $fixture_permission; return $fixture_permission; }
function is_plugin_active($file) { return true; }
function untrailingslashit($value) { return rtrim($value, '/'); }
function self_admin_url($value) { return 'https://example.test/wp-admin/' . $value; }
function wp_nonce_url($value, $action) { return $value . '&_wpnonce=isolated-test'; }
$config = json_decode(file_get_contents(__DIR__ . '/catalog-config.json'), true);
require dirname(__DIR__) . '/' . $config['runtime'] . '/includes/plugin-catalog/class-provider.php';
$provider = new MGD_Catalog_V4_Provider();
$item = ['repository' => 'MGD-Example-WordPress', 'basenames' => ['example/example.php'], 'url' => 'https://github.com/MichaelGahnDESIGN/MGD-Example-WordPress'];
foreach (['1.0.6' => false, '1.0.5' => false, '1.0.7' => true, '1.1.0' => true, '' => false] as $version => $expected) {
    $fixture_offer = (object) ['new_version' => $version];
    $actual = $provider->decorate([$item])[0];
    if (($actual['update_url'] !== '') !== $expected || $actual['state'] !== ($expected ? 'update' : 'installed')) { throw new RuntimeException('Falscher Zustand für ' . $version); }
}
$fixture_offer = null;
if ($provider->decorate([$item])[0]['update_url'] !== '') { throw new RuntimeException('Fehlendes Angebot'); }
$fixture_offer = (object) ['new_version' => '2.0.0'];
$fixture_permission = false;
if ($provider->decorate([$item])[0]['update_url'] !== '') { throw new RuntimeException('Fehlende Updateberechtigung'); }
echo "PASS 7 Fälle: gleiche/ältere Version, neuere Patch-/Minorversion, leer, fehlend und fehlende Berechtigung\n";
