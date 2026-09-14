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
        add_action( 'admin_post_mgd_wpmcp_wizard_finish', array( $this, 'finish' ) );
    }

    public static function activate() {
        if ( false === get_option( self::OPTION_WIZARD, false ) ) {
            add_option(
                self::OPTION_WIZARD,
                array(
                    'completed'    => false,
                    'builder'      => '',
                    'other_builder'=> '',
                    'divi_skill'   => 'ask',
                ),
                '',
                false
            );
        }
        set_transient( 'mgd_wpmcp_wizard_redirect', 1, 60 );
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
        return wp_parse_args(
            is_array( $data ) ? $data : array(),
            array( 'completed' => false, 'builder' => '', 'other_builder' => '', 'divi_skill' => 'ask' )
        );
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

        update_option(
            self::OPTION_WIZARD,
            array(
                'completed'     => false,
                'builder'       => $builder,
                'other_builder' => isset( $_POST['other_builder'] ) ? sanitize_text_field( wp_unslash( $_POST['other_builder'] ) ) : '',
                'divi_skill'    => ( 'divi' === $builder && ! empty( $_POST['divi_skill'] ) ) ? 'recommended' : 'not-selected',
            ),
            false
        );

        wp_safe_redirect( admin_url( 'tools.php?page=mgd-wordpress-mcp&tab=wizard&saved=1' ) );
        exit;
    }

    public function finish() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Keine Berechtigung.', 'mgd-wordpress-mcp' ) );
        }
        check_admin_referer( 'mgd_wpmcp_wizard_finish' );
        $data = self::data();
        $data['completed'] = true;
        update_option( self::OPTION_WIZARD, $data, false );
        wp_safe_redirect( admin_url( 'tools.php?page=mgd-wordpress-mcp&tab=status' ) );
        exit;
    }

    public static function render() {
        $data = self::data();
        $detected = MGD_WordPress_MCP_Environment::builder();
        $locks = MGD_WordPress_MCP_Environment::frontend_locks();
        ?>
        <div class="mgd-wpmcp-card mgd-wpmcp-wizard">
            <h2><?php esc_html_e( 'Einrichtungs-Assistent', 'mgd-wordpress-mcp' ); ?></h2>
            <p><?php esc_html_e( 'Der Assistent erkennt die Umgebung und merkt sich, mit welchem Builder Agenten auf dieser Website arbeiten sollen. Zugangsdaten werden dabei nicht gespeichert.', 'mgd-wordpress-mcp' ); ?></p>

            <h3><?php esc_html_e( '1. Erkannte Umgebung', 'mgd-wordpress-mcp' ); ?></h3>
            <p><strong><?php esc_html_e( 'Builder:', 'mgd-wordpress-mcp' ); ?></strong> <?php echo esc_html( $detected['label'] ); ?></p>
            <?php if ( $locks['potentially_locked'] ) : ?>
                <div class="notice notice-warning inline"><p><?php echo esc_html( $locks['agent_instruction'] ); ?></p></div>
            <?php else : ?>
                <p><?php echo esc_html( $locks['agent_instruction'] ); ?></p>
            <?php endif; ?>

            <h3><?php esc_html_e( '2. Welcher Builder soll bevorzugt werden?', 'mgd-wordpress-mcp' ); ?></h3>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="mgd_wpmcp_wizard_save">
                <?php wp_nonce_field( 'mgd_wpmcp_wizard_save' ); ?>
                <?php
                $choices = array(
                    'divi'           => 'Divi 5 / Divi Builder',
                    'elementor'      => 'Elementor',
                    'gutenberg'      => 'WordPress Block Editor / Gutenberg',
                    'bricks'         => 'Bricks',
                    'beaver-builder' => 'Beaver Builder',
                    'other'          => __( 'Anderes System', 'mgd-wordpress-mcp' ),
                );
                $selected = $data['builder'] ? $data['builder'] : $detected['type'];
                foreach ( $choices as $value => $label ) : ?>
                    <label class="mgd-wpmcp-radio"><input type="radio" name="builder" value="<?php echo esc_attr( $value ); ?>" <?php checked( $selected, $value ); ?>> <?php echo esc_html( $label ); ?></label>
                <?php endforeach; ?>
                <p><label><?php esc_html_e( 'Anderes System:', 'mgd-wordpress-mcp' ); ?><br><input class="regular-text" type="text" name="other_builder" value="<?php echo esc_attr( $data['other_builder'] ); ?>"></label></p>

                <div class="mgd-wpmcp-divi-skill">
                    <h3><?php esc_html_e( '3. Divi 5 Skill', 'mgd-wordpress-mcp' ); ?></h3>
                    <p><?php esc_html_e( 'Wenn Divi 5 verwendet wird, empfiehlt MGD WordPress MCP den kostenlosen MGD Divi 5 Dev Skill für Claude Code und Codex. Der Skill wird nicht ungefragt serverseitig installiert und benötigt keine WordPress-Zugangsdaten.', 'mgd-wordpress-mcp' ); ?></p>
                    <label><input type="checkbox" name="divi_skill" value="1" <?php checked( 'recommended', $data['divi_skill'] ); ?>> <?php esc_html_e( 'MGD Divi 5 Dev Skill für meinen Agenten verwenden', 'mgd-wordpress-mcp' ); ?></label>
                    <p><a class="button" target="_blank" rel="noopener" href="https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL"><?php esc_html_e( 'Skill auf GitHub öffnen', 'mgd-wordpress-mcp' ); ?></a></p>
                </div>

                <?php submit_button( __( 'Auswahl speichern', 'mgd-wordpress-mcp' ) ); ?>
            </form>

            <h3><?php esc_html_e( '4. MCP-Verbindung', 'mgd-wordpress-mcp' ); ?></h3>
            <p><?php esc_html_e( 'Installiere den offiziellen WordPress MCP Adapter, lege ein separates Application Password an und verbinde anschließend Claude Code, Codex oder einen anderen kompatiblen MCP-Client. Schreibrechte bleiben standardmäßig deaktiviert.', 'mgd-wordpress-mcp' ); ?></p>
            <p><a class="button" target="_blank" rel="noopener" href="https://github.com/WordPress/mcp-adapter/releases/latest"><?php esc_html_e( 'WordPress MCP Adapter', 'mgd-wordpress-mcp' ); ?></a> <a class="button" href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>"><?php esc_html_e( 'Application Passwords', 'mgd-wordpress-mcp' ); ?></a></p>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="mgd_wpmcp_wizard_finish">
                <?php wp_nonce_field( 'mgd_wpmcp_wizard_finish' ); ?>
                <?php submit_button( __( 'Assistent abschließen', 'mgd-wordpress-mcp' ), 'secondary' ); ?>
            </form>
        </div>
        <?php
    }
}
