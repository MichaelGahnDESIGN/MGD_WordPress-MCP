<?php
/**
 * Regressionstest für den lokalen Verbindungsassistenten.
 *
 * Application Passwords dürfen nie als WordPress-Formularwert auftauchen.
 * Der Assistent darf sie nur clientseitig erzeugen und nach dem Kopieren leeren.
 */

declare(strict_types=1);

$root   = dirname( __DIR__ );
$wizard = (string) file_get_contents( $root . '/mgd-wordpress-mcp/includes/class-mgd-wordpress-mcp-wizard.php' );
$script = (string) file_get_contents( $root . '/mgd-wordpress-mcp/assets/wizard.js' );

if ( false === strpos( $wizard, 'data-mgd-password' ) || false === strpos( $wizard, 'data-mgd-username' ) ) {
	throw new RuntimeException( 'Der Assistent benötigt klar getrennte lokale Zugangsfelder.' );
}

if ( false !== strpos( $wizard, 'name="mgd_wpmcp_password"' ) || false !== strpos( $wizard, 'name="mgd_wpmcp_username"' ) ) {
	throw new RuntimeException( 'Lokale Zugangsfelder dürfen nicht als WordPress-Formularwerte gesendet werden.' );
}

if ( false === strpos( $script, "password.value = '';" ) || false === strpos( $script, "username.value = '';" ) ) {
	throw new RuntimeException( 'Der Assistent muss lokale Zugangsfelder nach dem Kopieren leeren.' );
}

if ( false !== strpos( $script, 'fetch(' ) || false !== strpos( $script, 'XMLHttpRequest' ) ) {
	throw new RuntimeException( 'Der lokale Konfigurationsgenerator darf keine Zugangsdaten an den Server übertragen.' );
}

if ( false === strpos( $wizard, 'mgd_wpmcp_wizard_restart' ) ) {
	throw new RuntimeException( 'Der Assistent muss manuell neu gestartet werden können.' );
}

echo "PASS: Einrichtungsassistent verarbeitet Zugangsdaten nur lokal und kann neu gestartet werden.\n";
