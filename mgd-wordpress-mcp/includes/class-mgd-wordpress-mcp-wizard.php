<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MGD_WordPress_MCP_Wizard {
	const OPTION_WIZARD = 'mgd_wordpress_mcp_wizard';
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', array( $this, 'redirect_after_activation' ) );
		add_action( 'admin_post_mgd_wpmcp_wizard_save', array( $this, 'save' ) );
		add_action( 'admin_post_mgd_wpmcp_wizard_profile', array( $this, 'save_profile' ) );
		add_action( 'admin_post_mgd_wpmcp_wizard_finish', array( $this, 'finish' ) );
		add_action( 'admin_post_mgd_wpmcp_wizard_restart', array( $this, 'restart' ) );
	}

	public static function activate() {
		if ( false === get_option( self::OPTION_WIZARD, false ) ) {
			add_option( self::OPTION_WIZARD, self::default_data(), '', false );
		}
		set_transient( 'mgd_wpmcp_wizard_redirect', 1, 60 );
	}

	private static function default_data() {
		return array( 'completed' => false, 'builder' => '', 'other_builder' => '', 'divi_skill' => 'ask' );
	}

	public function redirect_after_activation() {
		if ( ! get_transient( 'mgd_wpmcp_wizard_redirect' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		delete_transient( 'mgd_wpmcp_wizard_redirect' );
		if ( wp_doing_ajax() || isset( $_GET['activate-multi'] ) ) {
			return;
		}
		wp_safe_redirect( admin_url( 'tools.php?page=mgd-wordpress-mcp&tab=wizard' ) );
		exit;
	}

	public static function data() {
		$data = get_option( self::OPTION_WIZARD, array() );
		return wp_parse_args( is_array( $data ) ? $data : array(), self::default_data() );
	}

	public function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'mgd-wordpress-mcp' ) );
		}
		check_admin_referer( 'mgd_wpmcp_wizard_save' );
		$allowed = array( 'divi', 'elementor', 'gutenberg', 'bricks', 'beaver-builder', 'other' );
		$builder = isset( $_POST['builder'] ) ? sanitize_key( wp_unslash( $_POST['builder'] ) ) : '';
		if ( ! in_array( $builder, $allowed, true ) ) {
			$builder = '';
		}
		$data                  = self::data();
		$data['completed']     = false;
		$data['builder']       = $builder;
		$data['other_builder'] = isset( $_POST['other_builder'] ) ? sanitize_text_field( wp_unslash( $_POST['other_builder'] ) ) : '';
		$data['divi_skill']    = ( 'divi' === $builder && ! empty( $_POST['divi_skill'] ) ) ? 'recommended' : 'not-selected';
		update_option( self::OPTION_WIZARD, $data, false );
		wp_safe_redirect( admin_url( 'tools.php?page=mgd-wordpress-mcp&tab=wizard&saved=1' ) );
		exit;
	}

	public function save_profile() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'mgd-wordpress-mcp' ) );
		}
		check_admin_referer( 'mgd_wpmcp_wizard_profile' );
		$profile = sanitize_key( wp_unslash( $_POST['profile'] ?? '' ) );
		if ( ! in_array( $profile, array( 'read_only', 'content_editor', 'admin', 'full_access' ), true ) ) {
			$profile = 'read_only';
		}
		$settings                         = MGD_WordPress_MCP::get_settings();
		$settings['permission_profile']   = $profile;
		$settings['writes_enabled']       = in_array( $profile, array( 'content_editor', 'admin', 'full_access' ), true );
		$settings['divi_writes_enabled']  = in_array( $profile, array( 'content_editor', 'admin', 'full_access' ), true );
		$settings['media_imports_enabled'] = in_array( $profile, array( 'content_editor', 'admin', 'full_access' ), true );
		$settings['maintenance_enabled']  = in_array( $profile, array( 'admin', 'full_access' ), true );
		update_option( MGD_WordPress_MCP::OPTION_SETTINGS, $settings, false );
		wp_safe_redirect( admin_url( 'tools.php?page=mgd-wordpress-mcp&tab=wizard&profile_saved=1#mgd-security-profiles' ) );
		exit;
	}

	public function finish() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'mgd-wordpress-mcp' ) );
		}
		check_admin_referer( 'mgd_wpmcp_wizard_finish' );
		$data              = self::data();
		$data['completed'] = true;
		update_option( self::OPTION_WIZARD, $data, false );
		wp_safe_redirect( admin_url( 'tools.php?page=mgd-wordpress-mcp&tab=status' ) );
		exit;
	}

	public function restart() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'mgd-wordpress-mcp' ) );
		}
		check_admin_referer( 'mgd_wpmcp_wizard_restart' );
		$data              = self::data();
		$data['completed'] = false;
		update_option( self::OPTION_WIZARD, $data, false );
		wp_safe_redirect( admin_url( 'tools.php?page=mgd-wordpress-mcp&tab=wizard&restarted=1' ) );
		exit;
	}

	private static function status_item( $ok, $label, $detail = '' ) {
		echo '<div class="mgd-wpmcp-ready-item ' . ( $ok ? 'is-ok' : 'is-warn' ) . '"><span class="mgd-wpmcp-ready-dot" aria-hidden="true">' . ( $ok ? '✓' : '!' ) . '</span><span><strong>' . esc_html( $label ) . '</strong>';
		if ( $detail ) {
			echo '<small>' . esc_html( $detail ) . '</small>';
		}
		echo '</span></div>';
	}

	private static function profile_label( $profile ) {
		$labels = array( 'read_only' => 'Nur lesen', 'content_editor' => 'Inhalte bearbeiten', 'admin' => 'Administration', 'full_access' => 'Vollzugriff' );
		return isset( $labels[ $profile ] ) ? $labels[ $profile ] : 'Nur lesen';
	}

	private static function setup_payload() {
		return array(
			'configs'  => MGD_WordPress_MCP_Client_Config::configs(),
			'endpoint' => MGD_WordPress_MCP::adapter_endpoint_url(),
			'terminal' => array( 'macos' => self::codex_terminal_command(), 'linux' => self::codex_terminal_command() ),
		);
	}

	private static function codex_terminal_command() {
		$endpoint = MGD_WordPress_MCP::adapter_endpoint_url();
		return 'mkdir -p "$HOME/.codex"' . "\n"
			. 'CONFIG="$HOME/.codex/config.toml"' . "\n"
			. 'if grep -q "^\\[mcp_servers\\.mgd_wordpress\\]$" "$CONFIG" 2>/dev/null; then' . "\n"
			. '  echo "MGD WordPress MCP ist bereits eingetragen. Bitte den vorhandenen Eintrag prüfen."' . "\n"
			. '  exit 1' . "\n"
			. 'fi' . "\n"
			. 'printf "WordPress-Benutzername: "' . "\n"
			. 'IFS= read -r MGD_USERNAME' . "\n"
			. 'printf "Application Password: "' . "\n"
			. 'stty -echo' . "\n"
			. 'IFS= read -r MGD_PASSWORD' . "\n"
			. 'stty echo' . "\n"
			. 'printf "\\n"' . "\n"
			. 'cat >> "$CONFIG" <<EOF' . "\n\n"
			. '[mcp_servers.mgd_wordpress]' . "\n"
			. 'command = "npx"' . "\n"
			. 'args = ["-y", "@automattic/mcp-wordpress-remote@0.5.4"]' . "\n\n"
			. '[mcp_servers.mgd_wordpress.env]' . "\n"
			. 'WP_API_URL = "' . $endpoint . '"' . "\n"
			. 'WP_API_USERNAME = "$MGD_USERNAME"' . "\n"
			. 'WP_API_PASSWORD = "$MGD_PASSWORD"' . "\n"
			. 'OAUTH_ENABLED = "false"' . "\n"
			. 'EOF' . "\n"
			. 'unset MGD_USERNAME MGD_PASSWORD' . "\n"
			. 'echo "Fertig. Codex neu starten und danach: codex mcp list"';
	}

	private static function restart_button() {
		?>
		<form class="mgd-wpmcp-wizard-restart" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mgd_wpmcp_wizard_restart">
			<?php wp_nonce_field( 'mgd_wpmcp_wizard_restart' ); ?>
			<button class="button button-secondary" type="submit">Assistent neu starten</button>
		</form>
		<?php
	}

	public static function render() {
		$data     = self::data();
		$detected = MGD_WordPress_MCP_Environment::builder();
		$locks    = MGD_WordPress_MCP_Environment::frontend_locks();
		$doctor   = MGD_WordPress_MCP_Doctor::summary();
		$profile  = MGD_WordPress_MCP_Security::profile();
		$adapter  = class_exists( '\\WP\\MCP\\Core\\McpAdapter' );
		$payload  = wp_json_encode( self::setup_payload(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		$clients  = array(
			'codex'       => array( 'Codex', 'Codex Desktop oder Codex CLI · TOML' ),
			'claude-code' => array( 'Claude Code', 'Claude Code · JSON' ),
			'cursor'      => array( 'Cursor / VS Code', 'Cursor oder VS Code · JSON' ),
			'generic'     => array( 'Anderer MCP-Client', 'Client mit lokalem Stdio-MCP-Server' ),
		);
		$systems = array( 'macos' => array( 'macOS', 'Terminal' ), 'windows' => array( 'Windows', 'Windows Terminal / PowerShell' ), 'linux' => array( 'Linux', 'Terminal' ) );
		?>
		<div class="mgd-wpmcp-wizard-shell" data-mgd-setup>
			<script type="application/json" id="mgd-wpmcp-setup-data"><?php echo $payload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
			<section class="mgd-wpmcp-card mgd-wpmcp-wizard-intro"><span class="mgd-wpmcp-step-kicker">SCHRITT FÜR SCHRITT</span><h2>WordPress sicher mit deinem KI-Agenten verbinden</h2><p>Wähle Agent und Betriebssystem. Der Assistent zeigt nur passende Schritte. Dein Application Password bleibt in deinem Browser und wird weder an WordPress gesendet noch im Plugin gespeichert.</p><?php if ( ! empty( $data['completed'] ) ) : ?><div class="mgd-wpmcp-wizard-intro-actions"><?php self::restart_button(); ?></div><?php endif; ?></section>

			<section class="mgd-wpmcp-card"><div class="mgd-wpmcp-section-head"><span class="mgd-wpmcp-step" aria-hidden="true">1</span><div><h2>Ist deine Website bereit?</h2><p>Die wichtigsten Voraussetzungen werden automatisch geprüft.</p></div></div><div class="mgd-wpmcp-ready-grid"><?php self::status_item( version_compare( get_bloginfo( 'version' ), '6.9', '>=' ), 'WordPress ' . get_bloginfo( 'version' ), 'Abilities API verfügbar' ); self::status_item( is_ssl(), 'HTTPS', is_ssl() ? 'Sichere Verbindung aktiv' : 'Für Remote-MCP empfohlen' ); self::status_item( $adapter, 'MCP Adapter', $adapter ? 'Offizieller Adapter erkannt' : 'Noch nicht erkannt' ); self::status_item( true, $detected['label'], 'Builder erkannt' ); self::status_item( ! $locks['potentially_locked'], 'Frontend-Zugriff', $locks['potentially_locked'] ? 'Schutz erkannt, nur bei visueller Prüfung relevant' : 'Kein Schutz erkannt' ); ?></div><div class="mgd-wpmcp-doctor-summary"><strong>Connection Doctor:</strong> <?php echo esc_html( $doctor['passed'] . ' von ' . $doctor['total'] . ' Prüfungen erfolgreich' ); ?> <a href="<?php echo esc_url( admin_url( 'tools.php?page=mgd-wordpress-mcp&tab=help' ) ); ?>">Details prüfen →</a></div></section>

			<section class="mgd-wpmcp-card"><div class="mgd-wpmcp-section-head"><span class="mgd-wpmcp-step" aria-hidden="true">2</span><div><h2>Womit arbeitest du?</h2><p>Diese Auswahl verändert nur die lokale Anleitung. Sie wird nicht gespeichert.</p></div></div><fieldset class="mgd-wpmcp-agent-choice"><legend>KI-Agent auswählen</legend><div class="mgd-wpmcp-choice-grid"><?php foreach ( $clients as $key => $client ) : ?><label class="mgd-wpmcp-choice"><input type="radio" name="mgd_wpmcp_client" value="<?php echo esc_attr( $key ); ?>" <?php checked( 'codex', $key ); ?>><span><strong><?php echo esc_html( $client[0] ); ?></strong><small><?php echo esc_html( $client[1] ); ?></small></span></label><?php endforeach; ?></div></fieldset><fieldset class="mgd-wpmcp-agent-choice"><legend>Dein Betriebssystem</legend><div class="mgd-wpmcp-choice-grid mgd-wpmcp-os-grid"><?php foreach ( $systems as $key => $system ) : ?><label class="mgd-wpmcp-choice"><input type="radio" name="mgd_wpmcp_system" value="<?php echo esc_attr( $key ); ?>" <?php checked( 'macos', $key ); ?>><span><strong><?php echo esc_html( $system[0] ); ?></strong><small><?php echo esc_html( $system[1] ); ?></small></span></label><?php endforeach; ?></div></fieldset><p class="mgd-wpmcp-selection-summary" data-mgd-selection-summary aria-live="polite"></p></section>

			<section class="mgd-wpmcp-card"><div class="mgd-wpmcp-section-head"><span class="mgd-wpmcp-step" aria-hidden="true">3</span><div><h2>Eigenen Zugang erstellen</h2><p>Für einen sicheren Rückruf verwende am besten ein separates WordPress-Konto mit nur den nötigen Rechten.</p></div></div><?php if ( $adapter ) : ?><div class="mgd-wpmcp-success-box"><strong>✓ MCP Adapter ist bereits installiert und aktiv.</strong></div><?php else : ?><div class="mgd-wpmcp-warning-box"><strong>MCP Adapter fehlt noch.</strong><a class="button" target="_blank" rel="noopener" href="https://github.com/WordPress/mcp-adapter/releases/latest">Adapter herunterladen</a></div><?php endif; ?><div class="mgd-wpmcp-numbered"><div><b>1</b><span><strong>Application Password erstellen</strong><small>Dein normales WordPress-Passwort wird dafür nie verwendet.</small></span><a class="button button-primary" href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>">Zugang erstellen</a></div><div><b>2</b><span><strong>Deinen Endpunkt merken</strong><small><?php echo esc_html( MGD_WordPress_MCP::adapter_endpoint_url() ); ?></small></span></div></div></section>

			<section class="mgd-wpmcp-card"><div class="mgd-wpmcp-section-head"><span class="mgd-wpmcp-step" aria-hidden="true">4</span><div><h2>Lokale Verbindung erzeugen</h2><p>Diese zwei Angaben werden nur lokal für die gerade erzeugte Konfiguration verwendet. Sie werden nicht gespeichert oder per Formular übertragen.</p></div></div><div class="mgd-wpmcp-local-form"><label>WordPress-Benutzername<input type="text" autocomplete="username" data-mgd-username placeholder="z. B. micha"></label><label>Application Password<input type="password" autocomplete="new-password" data-mgd-password placeholder="Nur hier lokal einfügen"></label></div><p class="description">Das Feld wird beim Kopieren sofort geleert. Der Terminal-Befehl enthält das Kennwort absichtlich nie im Klartext, sondern fragt es erst im eigenen Terminal verdeckt ab.</p><p><button type="button" class="button button-primary" data-mgd-generate>Konfiguration lokal erzeugen</button></p><div class="mgd-wpmcp-local-output" data-mgd-output hidden><label for="mgd-wpmcp-generated-config"><strong>Deine lokale Konfiguration</strong></label><textarea id="mgd-wpmcp-generated-config" class="large-text code" rows="13" readonly data-mgd-config-output></textarea><p><button type="button" class="button button-primary" data-mgd-copy-config>Konfiguration kopieren und Zugang entfernen</button> <button type="button" class="button" data-mgd-copy-terminal hidden>Terminal-Befehl kopieren</button></p><p class="description" data-mgd-output-hint></p></div><p class="mgd-wpmcp-client-message" data-mgd-message role="status" aria-live="polite"></p></section>

			<section class="mgd-wpmcp-card"><div class="mgd-wpmcp-section-head"><span class="mgd-wpmcp-step" aria-hidden="true">5</span><div><h2>Website-Kontext und Freigaben</h2><p>Diese WordPress-Einstellungen sind unabhängig von deinem lokalen Kennwort.</p></div></div><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="mgd_wpmcp_wizard_save"><?php wp_nonce_field( 'mgd_wpmcp_wizard_save' ); $choices = array( 'divi' => 'Divi 5 / Divi Builder', 'elementor' => 'Elementor', 'gutenberg' => 'WordPress Block Editor / Gutenberg', 'bricks' => 'Bricks', 'beaver-builder' => 'Beaver Builder', 'other' => 'Anderes System' ); $selected = $data['builder'] ? $data['builder'] : $detected['type']; ?><div class="mgd-wpmcp-choice-grid"><?php foreach ( $choices as $value => $label ) : ?><label class="mgd-wpmcp-choice"><input type="radio" name="builder" value="<?php echo esc_attr( $value ); ?>" <?php checked( $selected, $value ); ?>><span><strong><?php echo esc_html( $label ); ?></strong><?php if ( $value === $detected['type'] ) : ?><small>Automatisch erkannt</small><?php endif; ?></span></label><?php endforeach; ?></div><p><label>Anderes System<br><input class="regular-text" type="text" name="other_builder" value="<?php echo esc_attr( $data['other_builder'] ); ?>"></label></p><?php if ( 'divi' === $selected || 'divi' === $detected['type'] ) : ?><div class="mgd-wpmcp-recommend"><strong>Divi 5 erkannt</strong><p>Für Claude Code und Codex empfehlen wir zusätzlich den kostenlosen MGD Divi 5 Dev Skill. Er läuft beim Agenten und benötigt keine WordPress-Zugangsdaten.</p><label><input type="checkbox" name="divi_skill" value="1" <?php checked( 'recommended', $data['divi_skill'] ); ?>> Divi 5 Dev Skill verwenden</label> <a target="_blank" rel="noopener" href="https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL">Skill ansehen →</a></div><?php endif; ?><?php submit_button( 'Website-Kontext speichern', 'secondary' ); ?></form><div id="mgd-security-profiles" class="mgd-wpmcp-profile-grid"><?php $profiles = array( 'read_only' => array( 'Nur lesen', 'Website analysieren und Inhalte lesen, aber nichts verändern.' ), 'content_editor' => array( 'Inhalte bearbeiten', 'Beiträge, Seiten, Medien, SEO und freigegebene Builder-Funktionen bearbeiten.' ), 'admin' => array( 'Administration', 'Zusätzlich Wartung, Backups und Updates. Kritische Aktionen benötigen eine weitere Bestätigung.' ), 'full_access' => array( 'Vollzugriff', 'Alle vom Plugin angebotenen Funktionen ohne zusätzliche Einzelbestätigung. WordPress-Benutzerrechte bleiben die Obergrenze.' ) ); foreach ( $profiles as $key => $item ) : ?><form class="mgd-wpmcp-profile <?php echo $profile === $key ? 'is-active' : ''; ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="mgd_wpmcp_wizard_profile"><input type="hidden" name="profile" value="<?php echo esc_attr( $key ); ?>"><?php wp_nonce_field( 'mgd_wpmcp_wizard_profile' ); ?><span class="mgd-wpmcp-profile-check" aria-hidden="true"><?php echo $profile === $key ? '✓' : '○'; ?></span><strong><?php echo esc_html( $item[0] ); ?></strong><small><?php echo esc_html( $item[1] ); ?></small><?php if ( 'full_access' === $key ) : ?><em>Nur bewusst und beaufsichtigt verwenden</em><?php endif; ?><button type="submit" class="button <?php echo $profile === $key ? '' : 'button-secondary'; ?>" <?php disabled( $profile, $key ); ?>><?php echo $profile === $key ? 'Aktiv' : 'Auswählen'; ?></button></form><?php endforeach; ?></div><p class="description">Aktuell: <strong><?php echo esc_html( self::profile_label( $profile ) ); ?></strong>. Audit-Log, Rate Limit, Secret Redaction und WordPress-Benutzerrechte bleiben auch bei Vollzugriff aktiv.</p></section>

			<section class="mgd-wpmcp-card mgd-wpmcp-finish"><div><h2>Jetzt sicher testen</h2><p>Starte deinen Agenten nach dem Einrichten neu. Prüfe zuerst die Serverliste und stelle eine reine Leseanfrage. Erst danach solltest du absichtlich Schreibrechte nutzen.</p></div><div><a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'tools.php?page=mgd-wordpress-mcp&tab=help' ) ); ?>">Website bereit prüfen</a><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="mgd_wpmcp_wizard_finish"><?php wp_nonce_field( 'mgd_wpmcp_wizard_finish' ); ?><button class="button" type="submit">Assistent abschließen</button></form></div></section>
		</div>
		<?php
	}
}
