<?php
/**
 * Plugin Name: MGD WordPress MCP
 * Plugin URI: https://Michael-Gahn.de
 * Description: Sichere MCP- und Abilities-Bridge für WordPress. Verbindet KI-Agenten mit Inhalten, Medien, Updates, Divi 5, WPForms, SEO, UpdraftPlus und der Website-Umgebung.
 * Version: 0.2.11
 * Author: Michael Gahn DESIGN
 * Author URI: https://Michael-Gahn.de
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mgd-wordpress-mcp
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Update URI: https://github.com/MichaelGahnDESIGN/MGD_WordPress-MCP
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'MGD_WPMCP_VERSION', '0.2.11' );
define( 'MGD_WPMCP_FILE', __FILE__ );
define( 'MGD_WPMCP_DIR', plugin_dir_path( __FILE__ ) );
define( 'MGD_WPMCP_URL', plugin_dir_url( __FILE__ ) );
define( 'MGD_WPMCP_BASENAME', plugin_basename( __FILE__ ) );
define( 'MGD_WPMCP_GITHUB_REPO', 'MichaelGahnDESIGN/MGD_WordPress-MCP' );
define( 'MGD_WPMCP_MCP_ENDPOINT', 'mgd-wordpress-mcp/v1/mcp' );
require_once MGD_WPMCP_DIR . 'includes/class-mgd-wordpress-mcp-audit.php';
require_once MGD_WPMCP_DIR . 'includes/class-mgd-wordpress-mcp-environment.php';
require_once MGD_WPMCP_DIR . 'includes/class-mgd-wordpress-mcp-abilities.php';
require_once MGD_WPMCP_DIR . 'includes/class-mgd-wordpress-mcp-environment-ability.php';
require_once MGD_WPMCP_DIR . 'includes/class-mgd-wordpress-mcp-security.php';
require_once MGD_WPMCP_DIR . 'includes/class-mgd-wordpress-mcp-wizard.php';
require_once MGD_WPMCP_DIR . 'includes/class-mgd-wordpress-mcp-admin.php';
require_once MGD_WPMCP_DIR . 'includes/class-mgd-wordpress-mcp-updater.php';
require_once MGD_WPMCP_DIR . 'includes/class-mgd-wordpress-mcp.php';
register_activation_hook( __FILE__, array( 'MGD_WordPress_MCP', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MGD_WordPress_MCP', 'deactivate' ) );
add_action( 'plugins_loaded', static function () { MGD_WordPress_MCP::instance(); } );
