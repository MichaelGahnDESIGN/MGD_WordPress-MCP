<?php
/**
 * Plugin Name: MGD MCP Bridge
 * Plugin URI: https://Michael-Gahn.de
 * Description: Sichere MCP- und Abilities-Bridge für WordPress. Verbindet KI-Agenten kontrolliert mit WordPress-Inhalten und Integrationen.
 * Version: 0.5.11
 * Author: Michael Gahn DESIGN
 * Author URI: https://Michael-Gahn.de
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mgd-mcp-bridge
 * Requires at least: 6.9
 * Requires PHP: 7.4
 */
if(!defined('ABSPATH'))exit;
define('MGD_WPMCP_VERSION','0.5.11');define('MGD_WPMCP_FILE',__FILE__);define('MGD_WPMCP_DIR',plugin_dir_path(__FILE__));define('MGD_WPMCP_URL',plugin_dir_url(__FILE__));define('MGD_WPMCP_BASENAME',plugin_basename(__FILE__));define('MGD_WPMCP_GITHUB_REPO','MichaelGahnDESIGN/MGD_WordPress-MCP');define('MGD_WPMCP_MCP_ENDPOINT','mgd-wordpress-mcp/v1/mcp');
require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-audit.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-environment.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-approvals.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-doctor.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-client-config.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-abilities.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-environment-ability.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-security.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-wizard.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp-admin.php';require_once MGD_WPMCP_DIR.'includes/class-mgd-wordpress-mcp.php';register_activation_hook(__FILE__,array('MGD_WordPress_MCP','activate'));register_deactivation_hook(__FILE__,array('MGD_WordPress_MCP','deactivate'));add_action('plugins_loaded',static function(){MGD_WordPress_MCP::instance();});

// Icon in der Pluginverwaltung.
(require __DIR__ . '/includes/plugin-icon.php')(__FILE__);

require_once __DIR__ . '/includes/class-mgd-plugin-admin-tools.php';
\MGD_WordPress_Org_Admin_Tools::register([
    'file' => __FILE__,
    'repo' => 'MGD_WordPress-MCP',
    'slug' => 'mgd-mcp-bridge',
    'name' => 'MGD WordPress MCP',
    'description' => 'Sichere MCP- und Abilities-Bridge für WordPress. Verbindet KI-Agenten kontrolliert mit WordPress-Inhalten und Integrationen.',
    'version' => '0.5.11',
    'settings' => 'tools.php?page=mgd-wordpress-mcp',
    'requires' => '6.9',
    'php' => '7.4',
    'caches' => ['mgd_wpmcp_release_828e8533143ca39f74744cc3a5e7b8a8'],
    'package' => '',
]);
