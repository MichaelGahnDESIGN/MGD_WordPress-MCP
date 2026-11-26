<?php
/**
 * Plugin Name: MGD WordPress MCP
 * Plugin URI: https://theme.michael-gahn.de/plugins/plugin-wordpress-mcp
 * Description: Sichere MCP-Verbindung für WordPress, mit der KI-Agenten kontrolliert auf Inhalte und Integrationen zugreifen.
 * Version: 2.1.3
 * Requires at least: 6.9
 * Requires PHP: 8.1
 * Author: Michael Gahn DESIGN
 * Author URI: https://michael-gahn.de
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://github.com/MichaelGahnDESIGN/MGD_WordPress-MCP
 * Text Domain: mgd-wordpress-mcp
 */
if(!defined('ABSPATH'))exit;
define('MGD_WPMCP_VERSION','2.1.3');define('MGD_WPMCP_FILE',__FILE__);define('MGD_WPMCP_DIR',plugin_dir_path(__FILE__));define('MGD_WPMCP_URL',plugin_dir_url(__FILE__));define('MGD_WPMCP_BASENAME',plugin_basename(__FILE__));define('MGD_WPMCP_GITHUB_REPO','MichaelGahnDESIGN/MGD_WordPress-MCP');define('MGD_WPMCP_MCP_ENDPOINT','mgd-wordpress-mcp/v1/mcp');
require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-audit.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-environment.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-approvals.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-doctor.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-client-config.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-abilities.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-environment-ability.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-security.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-wizard.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-admin.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp.php';register_activation_hook(__FILE__,array('MGD_WordPress_MCP','activate'));register_deactivation_hook(__FILE__,array('MGD_WordPress_MCP','deactivate'));add_action('plugins_loaded',static function(){MGD_WordPress_MCP::instance();});

// Baukasten aus MichaThemeV5-Pro > includes/Kit (bin/build-plugin-kit.php)
spl_autoload_register(static function (string $class): void {
    $prefix = 'Mgd\\WordPressMcp\\Kit\\';
    if (str_starts_with($class, $prefix)) {
        $file = MGD_WPMCP_DIR . 'includes/Kit/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

// Updater + MGD Tools Menü vom Baukasten
use Mgd\WordPressMcp\Kit\Platform\WordPress\Setup;
use Mgd\WordPressMcp\Kit\Platform\WordPress\Tools;

$mgd_wpmcp_updater = Setup::updater(__FILE__, 'mgd-mcp-wordpress', 'wordpress', MGD_WPMCP_VERSION, 'MGD WordPress MCP', Tools::url());
// Reste vom alten GitHub-Updater wegräumen, 1x pro Version
Setup::cleanupLegacy('mgd-mcp-wordpress', MGD_WPMCP_VERSION, array('mgd_wpmcp_release_*'));

// Einstellungsseite unter MGD Tools - Seiten-Kennung bleibt mgd-wordpress-mcp
Setup::tools(__FILE__, 'mgd-mcp-wordpress', 'wordpress', MGD_WPMCP_VERSION, 'MGD WordPress MCP', $mgd_wpmcp_updater, array(
    'slug'     => 'plugin-wordpress-mcp',
    'repo'     => 'MGD_WordPress-MCP',
    'settings' => array('page' => 'mgd-wordpress-mcp', 'title' => 'WordPress MCP', 'cap' => 'manage_options', 'render' => static function (): void { MGD_WordPress_MCP_Admin::instance()->render(); }),
    'license'  => false,
));
