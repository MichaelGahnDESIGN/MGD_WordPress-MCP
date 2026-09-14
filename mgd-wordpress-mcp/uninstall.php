<?php
/**
 * Uninstall handler.
 *
 * MGD WordPress MCP intentionally preserves settings and audit logs on uninstall.
 * This avoids accidental loss of security/audit information. Users can remove
 * the option and database table manually if a complete purge is required.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}
