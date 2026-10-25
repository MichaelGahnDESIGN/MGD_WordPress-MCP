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
	var steps = Array.prototype.slice.call( root.querySelectorAll( '[data-mgd-wizard-step]' ) );
	var progress;
	var activeStep = 1;

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

	function stepFromHash() {
		var match = window.location.hash.match( /^#mgd-wizard-step-([1-6])$/ );
		return match ? Number( match[1] ) : 1;
	}

	function nextLabel( step ) {
		var labels = {
			1: 'Weiter: Agent und Betriebssystem',
			2: 'Weiter: Zugang erstellen',
			3: 'Weiter: Verbindung einrichten',
			4: 'Weiter: Freigaben festlegen',
			5: 'Weiter: Abschlusstest'
		};
		return labels[ step ] || 'Weiter';
	}

	function renderProgress() {
		if ( ! progress ) {
			return;
		}
		Array.prototype.forEach.call( progress.querySelectorAll( 'li' ), function ( item ) {
			var step = Number( item.getAttribute( 'data-step' ) );
			item.classList.toggle( 'is-active', step === activeStep );
			item.classList.toggle( 'is-done', step < activeStep );
			item.setAttribute( 'aria-current', step === activeStep ? 'step' : 'false' );
		} );
	}

	function showStep( step, focusHeading ) {
		activeStep = Math.max( 1, Math.min( steps.length, step ) );
		Array.prototype.forEach.call( steps, function ( section ) {
			section.hidden = Number( section.getAttribute( 'data-mgd-wizard-step' ) ) !== activeStep;
		} );
		window.history.replaceState( null, '', window.location.pathname + window.location.search + '#mgd-wizard-step-' + activeStep );
		renderProgress();

		if ( focusHeading ) {
			var heading = root.querySelector( '#mgd-wizard-step-' + activeStep + ' h2' );
			if ( heading ) {
				heading.setAttribute( 'tabindex', '-1' );
				heading.focus();
			}
		}
	}

	function validStep( step ) {
		if ( 4 !== step ) {
			return true;
		}
		if ( username.value && ! password.value ) {
			message.textContent = 'Bitte noch das Application Password ergänzen oder den Benutzernamen wieder leeren.';
			password.focus();
			return false;
		}
		if ( password.value && ! username.value.trim() ) {
			message.textContent = 'Bitte noch den WordPress-Benutzernamen ergänzen oder das Application Password wieder leeren.';
			username.focus();
			return false;
		}
		return true;
	}

	function setupGuidedWizard() {
		if ( ! steps.length ) {
			return;
		}

		root.classList.add( 'is-guided' );
		progress = document.createElement( 'ol' );
		progress.className = 'mgd-wpmcp-wizard-progress';
		progress.setAttribute( 'aria-label', 'Fortschritt im Einrichtungsassistenten' );
		Array.prototype.forEach.call( steps, function ( section, index ) {
			var item = document.createElement( 'li' );
			item.setAttribute( 'data-step', String( index + 1 ) );
			item.textContent = 'Schritt ' + String( index + 1 );
			progress.appendChild( item );

			var controls = document.createElement( 'div' );
			controls.className = 'mgd-wpmcp-wizard-controls';
			if ( index > 0 ) {
				var back = document.createElement( 'button' );
				back.type = 'button';
				back.className = 'button button-secondary';
				back.textContent = 'Zurück';
				back.addEventListener( 'click', function () {
					showStep( index, true );
				} );
				controls.appendChild( back );
			}
			if ( index < steps.length - 1 ) {
				var next = document.createElement( 'button' );
				next.type = 'button';
				next.className = 'button button-primary';
				next.textContent = nextLabel( index + 1 );
				next.addEventListener( 'click', function () {
					if ( validStep( index + 1 ) ) {
						showStep( index + 2, true );
					}
				} );
				controls.appendChild( next );
			}
			section.appendChild( controls );
		} );

		root.querySelector( '.mgd-wpmcp-wizard-intro' ).insertAdjacentElement( 'afterend', progress );
		showStep( stepFromHash(), false );
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
	setupGuidedWizard();
}() );
