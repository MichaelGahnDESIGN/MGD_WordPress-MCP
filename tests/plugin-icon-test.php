<?php
// Netzfreier Vertrag: Das Icon verändert nur die eigene Pluginanzeige.
define('ABSPATH', __DIR__);
$hooks = array(); $styles = array();
function add_action($hook, $callback) { global $hooks; $hooks[$hook] = $callback; }
function add_filter($hook, $callback, $priority = 10, $args = 1) { global $hooks; $hooks[$hook] = $callback; }
function plugin_basename($file) { return 'example/main.php'; }
function plugin_dir_url($file) { return 'https://example.invalid/wp-content/plugins/example/'; }
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES); }
function esc_url($value) { return $value; }
function is_wp_error($value) { return false; }
function wp_register_style($handle, $source, $deps, $version) {}
function wp_enqueue_style($handle) {}
function wp_add_inline_style($handle, $css) { global $styles; $styles[] = $css; }
(require __DIR__ . '/../mgd-wordpress-mcp/includes/plugin-icon.php')(__FILE__);
$hooks['admin_enqueue_scripts']('index.php');
if ($styles) { throw new RuntimeException('Fremde Adminseite verändert'); }
$hooks['admin_enqueue_scripts']('plugins.php');
if (count($styles) !== 1 || strpos($styles[0], 'tr[data-plugin="example/main.php"]') === false || strpos($styles[0], 'icon-128.png') === false) { throw new RuntimeException('Icon fehlt oder betrifft fremde Plugins'); }
$foreign = (object) array('new_version' => '1.0.0');
$value = (object) array('response' => array('example/main.php' => (object) array('new_version'=>'2.0.0'), 'other/main.php' => $foreign));
$result = $hooks['pre_set_site_transient_update_plugins']($value);
if (count($result->response['example/main.php']->icons) !== 2 || isset($foreign->icons)) { throw new RuntimeException('Updateicon falsch zugeordnet'); }
if ($hooks['plugins_api'](false, 'plugin_information', (object) array('slug'=>'example')) !== false) { throw new RuntimeException('Fehlende Details überschrieben'); }
echo "Plugin-Icon: lokale Grafik, begrenzter Adminbereich und fremde Plugins geprüft.\n";
