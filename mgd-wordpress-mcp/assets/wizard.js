( function () {
	'use strict';

	var root = document.querySelector( '[data-mgd-setup]' );
	var source = document.getElementById( 'mgd-wpmcp-setup-data' );
	if ( ! root || ! source ) {
		return;
	}

	var data;
	try {
		data = JSON.parse( source.textContent );
	} catch ( error ) {
		return;
	}

	var username = root.querySelector( '[data-mgd-username]' );
	var password = root.querySelector( '[data-mgd-password]' );
	var generate = root.querySelector( '[data-mgd-generate]' );
	var output = root.querySelector( '[data-mgd-output]' );
	var configOutput = root.querySelector( '[data-mgd-config-output]' );
	var copyConfig = root.querySelector( '[data-mgd-copy-config]' );
	var copyTerminal = root.querySelector( '[data-mgd-copy-terminal]' );
	var hint = root.querySelector( '[data-mgd-output-hint]' );
	var message = root.querySelector( '[data-mgd-message]' );
	var summary = root.querySelector( '[data-mgd-selection-summary]' );

	function selected( name ) {
		var field = root.querySelector( 'input[name="' + name + '"]:checked' );
		return field ? field.value : '';
	}

	function labels() {
		var client = root.querySelector( 'input[name="mgd_wpmcp_client"]:checked' );
		var system = root.querySelector( 'input[name="mgd_wpmcp_system"]:checked' );
		return {
			client: client ? client.closest( 'label' ).querySelector( 'strong' ).textContent : '',
			system: system ? system.closest( 'label' ).querySelector( 'strong' ).textContent : ''
		};
	}

	function clearSensitiveOutput() {
		configOutput.value = '';
		output.hidden = true;
		copyTerminal.hidden = true;
		hint.textContent = '';
	}

	function updateSelection() {
		var choice = labels();
		summary.textContent = choice.client + ' auf ' + choice.system + ': Die passende lokale Anleitung wird unten erzeugt.';
		clearSensitiveOutput();
	}

	function configTemplate() {
		var client = selected( 'mgd_wpmcp_client' );
		return data.configs[ client ] || data.configs.generic || '';
	}

	function buildConfig() {
		return configTemplate()
			.split( '<WORDPRESS-BENUTZERNAME>' ).join( username.value.trim() )
			.split( '<APPLICATION-PASSWORD>' ).join( password.value );
	}

	function resetSensitiveData( text ) {
		password.value = '';
		username.value = '';
		clearSensitiveOutput();
		message.textContent = text;
	}

	function copy( value, success ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( value ).then( success ).catch( function () {
				message.textContent = 'Kopieren wurde vom Browser blockiert. Bitte die Konfiguration manuell kopieren und danach das Feld leeren.';
			} );
			return;
		}

		var temporary = document.createElement( 'textarea' );
		temporary.value = value;
		temporary.setAttribute( 'aria-hidden', 'true' );
		temporary.style.position = 'fixed';
		temporary.style.opacity = '0';
		document.body.appendChild( temporary );
		temporary.focus();
		temporary.select();
		if ( document.execCommand( 'copy' ) ) {
			document.body.removeChild( temporary );
			success();
		} else {
			document.body.removeChild( temporary );
			message.textContent = 'Kopieren wurde vom Browser blockiert. Bitte die Konfiguration manuell kopieren und danach das Feld leeren.';
		}
	}

	function terminalAvailable() {
		return 'codex' === selected( 'mgd_wpmcp_client' ) && !! data.terminal[ selected( 'mgd_wpmcp_system' ) ];
	}

	generate.addEventListener( 'click', function () {
		if ( ! username.value.trim() || ! password.value ) {
			message.textContent = 'Bitte zuerst WordPress-Benutzername und Application Password lokal eintragen.';
			return;
		}

		configOutput.value = buildConfig();
		output.hidden = false;
		copyTerminal.hidden = ! terminalAvailable();
		hint.textContent = terminalAvailable()
			? 'Du kannst die Konfiguration kopieren oder den sicheren Terminal-Befehl verwenden. Der Terminal-Befehl enthält kein Kennwort.'
			: 'Kopiere die Konfiguration in die lokale MCP-Einstellung deines ausgewählten Clients und starte den Client anschließend neu.';
		message.textContent = 'Konfiguration wurde nur in diesem Browser erzeugt.';
		copyConfig.focus();
	} );

	copyConfig.addEventListener( 'click', function () {
		copy( configOutput.value, function () {
			resetSensitiveData( 'Konfiguration kopiert. Benutzername und Application Password wurden aus diesem Assistenten entfernt.' );
		} );
	} );

	copyTerminal.addEventListener( 'click', function () {
		var command = data.terminal[ selected( 'mgd_wpmcp_system' ) ];
		if ( ! command ) {
			return;
		}
		copy( command, function () {
			resetSensitiveData( 'Terminal-Befehl kopiert. Das Kennwort wird erst verdeckt in deinem Terminal abgefragt.' );
		} );
	} );

	root.querySelectorAll( 'input[name="mgd_wpmcp_client"], input[name="mgd_wpmcp_system"]' ).forEach( function ( input ) {
		input.addEventListener( 'change', updateSelection );
	} );

	updateSelection();
}() );
