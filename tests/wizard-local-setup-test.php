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
$admin  = (string) file_get_contents( $root . '/mgd-wordpress-mcp/includes/class-mgd-wordpress-mcp-admin.php' );

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

for ( $step = 1; $step <= 6; $step++ ) {
	if ( false === strpos( $wizard, 'data-mgd-wizard-step="' . $step . '"' ) ) {
		throw new RuntimeException( 'Jeder geführte Assistentenschritt benötigt einen eigenen Bereich.' );
	}
}

if ( false === strpos( $script, 'setupGuidedWizard' ) || false === strpos( $script, 'showStep' ) || false === strpos( $script, 'section.hidden' ) ) {
	throw new RuntimeException( 'Der Assistent muss die Schritte einzeln und zugänglich anzeigen.' );
}

$expected_tabs = array( 'Einrichtungsassistent', 'Übersicht', 'Start Prompt', 'Sicherheit und Freigaben', 'Verbindung', 'Audit-Log', 'Über das Plugin', 'Plugin-Empfehlungen' );
$last_position = -1;
foreach ( $expected_tabs as $tab ) {
	$position = strpos( $admin, $tab );
	if ( false === $position || $position < $last_position ) {
		throw new RuntimeException( 'Die Plugin-Navigation entspricht nicht der vereinbarten Reihenfolge.' );
	}
	$last_position = $position;
}

echo "PASS: Einrichtungsassistent verarbeitet Zugangsdaten nur lokal, führt Schritt für Schritt und kann neu gestartet werden.\n";
