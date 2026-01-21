/**
 * Oxybridge Admin JavaScript
 *
 * Handles AJAX interactions for MCP server management in the WordPress admin.
 *
 * @package Oxybridge
 * @since 1.0.0
 */

( function( $ ) {
	'use strict';

	/**
	 * Oxybridge Admin object.
	 *
	 * Manages all server management UI interactions and AJAX calls.
	 */
	var OxybridgeAdmin = {

		/**
		 * UI element selectors.
		 */
		selectors: {
			installBtn: '#oxybridge-install-btn',
			launchBtn: '#oxybridge-launch-btn',
			stopBtn: '#oxybridge-stop-btn',
			statusBtn: '#oxybridge-status-btn',
			statusIndicator: '#oxybridge-server-status',
			outputArea: '#oxybridge-output',
			serverCard: '.oxybridge-server-card'
		},

		/**
		 * Track if an operation is in progress.
		 */
		isProcessing: false,

		/**
		 * Initialize the admin module.
		 *
		 * @return {void}
		 */
		init: function() {
			this.cacheElements();
			this.bindEvents();
			this.checkStatus();
		},

		/**
		 * Cache DOM elements for better performance.
		 *
		 * @return {void}
		 */
		cacheElements: function() {
			this.$installBtn = $( this.selectors.installBtn );
			this.$launchBtn = $( this.selectors.launchBtn );
			this.$stopBtn = $( this.selectors.stopBtn );
			this.$statusBtn = $( this.selectors.statusBtn );
			this.$statusIndicator = $( this.selectors.statusIndicator );
			this.$outputArea = $( this.selectors.outputArea );
		},

		/**
		 * Bind event handlers to UI elements.
		 *
		 * @return {void}
		 */
		bindEvents: function() {
			var self = this;

			this.$installBtn.on( 'click', function( e ) {
				e.preventDefault();
				self.installDeps();
			} );

			this.$launchBtn.on( 'click', function( e ) {
				e.preventDefault();
				self.launchServer();
			} );

			this.$stopBtn.on( 'click', function( e ) {
				e.preventDefault();
				self.stopServer();
			} );

			this.$statusBtn.on( 'click', function( e ) {
				e.preventDefault();
				self.checkStatus();
			} );
		},

		/**
		 * Install npm dependencies.
		 *
		 * Makes AJAX call to install/update node_modules in the MCP server directory.
		 *
		 * @return {void}
		 */
		installDeps: function() {
			var self = this;

			if ( this.isProcessing ) {
				return;
			}

			this.setProcessing( true );
			this.setButtonLoading( this.$installBtn, true );
			this.showOutput( oxybridgeAdmin.strings.installing, 'info' );

			$.ajax( {
				url: oxybridgeAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'oxybridge_install_deps',
					nonce: oxybridgeAdmin.nonce
				},
				success: function( response ) {
					if ( response.success ) {
						self.showOutput( response.data.message, 'success' );
						if ( response.data.output ) {
							self.appendOutput( response.data.output, 'code' );
						}
					} else {
						self.showOutput( response.data.message, 'error' );
						if ( response.data.output ) {
							self.appendOutput( response.data.output, 'code' );
						}
					}
					self.checkStatus();
				},
				error: function( xhr, status, error ) {
					self.showOutput( oxybridgeAdmin.strings.ajaxError + ': ' + error, 'error' );
				},
				complete: function() {
					self.setProcessing( false );
					self.setButtonLoading( self.$installBtn, false );
				}
			} );
		},

		/**
		 * Launch the MCP server.
		 *
		 * Makes AJAX call to start the server as a background process.
		 *
		 * @return {void}
		 */
		launchServer: function() {
			var self = this;

			if ( this.isProcessing ) {
				return;
			}

			this.setProcessing( true );
			this.setButtonLoading( this.$launchBtn, true );
			this.showOutput( oxybridgeAdmin.strings.launching, 'info' );

			$.ajax( {
				url: oxybridgeAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'oxybridge_launch_server',
					nonce: oxybridgeAdmin.nonce
				},
				success: function( response ) {
					if ( response.success ) {
						self.showOutput( response.data.message, 'success' );
					} else {
						self.showOutput( response.data.message, 'error' );
					}
					self.checkStatus();
				},
				error: function( xhr, status, error ) {
					self.showOutput( oxybridgeAdmin.strings.ajaxError + ': ' + error, 'error' );
				},
				complete: function() {
					self.setProcessing( false );
					self.setButtonLoading( self.$launchBtn, false );
				}
			} );
		},

		/**
		 * Stop the MCP server.
		 *
		 * Makes AJAX call to terminate the running server process.
		 *
		 * @return {void}
		 */
		stopServer: function() {
			var self = this;

			if ( this.isProcessing ) {
				return;
			}

			this.setProcessing( true );
			this.setButtonLoading( this.$stopBtn, true );
			this.showOutput( oxybridgeAdmin.strings.stopping, 'info' );

			$.ajax( {
				url: oxybridgeAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'oxybridge_stop_server',
					nonce: oxybridgeAdmin.nonce
				},
				success: function( response ) {
					if ( response.success ) {
						self.showOutput( response.data.message, 'success' );
					} else {
						self.showOutput( response.data.message, 'error' );
					}
					self.checkStatus();
				},
				error: function( xhr, status, error ) {
					self.showOutput( oxybridgeAdmin.strings.ajaxError + ': ' + error, 'error' );
				},
				complete: function() {
					self.setProcessing( false );
					self.setButtonLoading( self.$stopBtn, false );
				}
			} );
		},

		/**
		 * Check server status.
		 *
		 * Makes AJAX call to retrieve comprehensive server status.
		 *
		 * @return {void}
		 */
		checkStatus: function() {
			var self = this;

			// Allow status check even during other operations.
			this.setStatusLoading( true );

			$.ajax( {
				url: oxybridgeAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'oxybridge_server_status',
					nonce: oxybridgeAdmin.nonce
				},
				success: function( response ) {
					if ( response.success ) {
						self.updateStatusDisplay( response.data );
						self.updateButtonStates( response.data );
						self.displayWarnings( response.data.warnings );
					} else {
						self.setStatusDisplay( 'error', oxybridgeAdmin.strings.statusError );
					}
				},
				error: function( xhr, status, error ) {
					self.setStatusDisplay( 'error', oxybridgeAdmin.strings.ajaxError );
				},
				complete: function() {
					self.setStatusLoading( false );
				}
			} );
		},

		/**
		 * Update the status indicator display.
		 *
		 * @param {Object} data Server status data from AJAX response.
		 * @return {void}
		 */
		updateStatusDisplay: function( data ) {
			var statusClass = this.getStatusClass( data.status_label );
			var statusText = data.status_message || this.getStatusText( data.status_label );

			this.setStatusDisplay( statusClass, statusText );

			// Update additional info if available.
			if ( data.node_version ) {
				this.updateVersionInfo( 'node', data.node_version );
			}
			if ( data.npm_version ) {
				this.updateVersionInfo( 'npm', data.npm_version );
			}
		},

		/**
		 * Set the status indicator content.
		 *
		 * @param {string} statusClass CSS class for the status (running, stopped, error, etc.).
		 * @param {string} text        Display text.
		 * @return {void}
		 */
		setStatusDisplay: function( statusClass, text ) {
			var iconClass = this.getStatusIcon( statusClass );

			this.$statusIndicator
				.removeClass( 'status-running status-stopped status-error status-not_installed status-not_built status-loading' )
				.addClass( 'status-' + statusClass )
				.html( '<span class="dashicons ' + iconClass + '"></span> ' + this.escapeHtml( text ) );
		},

		/**
		 * Set status indicator to loading state.
		 *
		 * @param {boolean} loading Whether to show loading state.
		 * @return {void}
		 */
		setStatusLoading: function( loading ) {
			if ( loading ) {
				this.$statusIndicator
					.addClass( 'status-loading' )
					.html( '<span class="dashicons dashicons-update spin"></span> ' + oxybridgeAdmin.strings.checking );
			}
		},

		/**
		 * Update button states based on server status.
		 *
		 * @param {Object} data Server status data.
		 * @return {void}
		 */
		updateButtonStates: function( data ) {
			// Install button - available when directory exists and npm is available.
			this.$installBtn.prop( 'disabled', ! data.can_install || this.isProcessing );

			// Launch button - available when built and not running.
			this.$launchBtn.prop( 'disabled', ! data.can_launch || this.isProcessing );

			// Stop button - only available when running.
			this.$stopBtn.prop( 'disabled', ! data.can_stop || this.isProcessing );

			// Status button - always available unless processing.
			this.$statusBtn.prop( 'disabled', this.isProcessing );
		},

		/**
		 * Display warning messages if any.
		 *
		 * @param {Array} warnings Array of warning messages.
		 * @return {void}
		 */
		displayWarnings: function( warnings ) {
			if ( ! warnings || warnings.length === 0 ) {
				return;
			}

			var warningsHtml = '<div class="oxybridge-warnings">';
			for ( var i = 0; i < warnings.length; i++ ) {
				warningsHtml += '<p class="warning-item"><span class="dashicons dashicons-warning"></span> ' + this.escapeHtml( warnings[ i ] ) + '</p>';
			}
			warningsHtml += '</div>';

			// Append warnings to output area if not already showing.
			if ( this.$outputArea.find( '.oxybridge-warnings' ).length === 0 ) {
				this.$outputArea.prepend( warningsHtml );
			}
		},

		/**
		 * Show message in the output area.
		 *
		 * @param {string} message Message to display.
		 * @param {string} type    Message type (success, error, info, code).
		 * @return {void}
		 */
		showOutput: function( message, type ) {
			var className = 'oxybridge-message oxybridge-message-' + type;
			var html = '<div class="' + className + '">' + this.escapeHtml( message ) + '</div>';

			this.$outputArea.html( html ).show();
		},

		/**
		 * Append content to the output area.
		 *
		 * @param {string} content Content to append.
		 * @param {string} type    Content type (success, error, info, code).
		 * @return {void}
		 */
		appendOutput: function( content, type ) {
			var className = 'oxybridge-message oxybridge-message-' + type;
			var html;

			if ( type === 'code' ) {
				html = '<pre class="' + className + '">' + this.escapeHtml( content ) + '</pre>';
			} else {
				html = '<div class="' + className + '">' + this.escapeHtml( content ) + '</div>';
			}

			this.$outputArea.append( html );
		},

		/**
		 * Clear the output area.
		 *
		 * @return {void}
		 */
		clearOutput: function() {
			this.$outputArea.empty().hide();
		},

		/**
		 * Set processing state.
		 *
		 * @param {boolean} processing Whether an operation is in progress.
		 * @return {void}
		 */
		setProcessing: function( processing ) {
			this.isProcessing = processing;

			// Update all button states.
			if ( processing ) {
				this.$installBtn.prop( 'disabled', true );
				this.$launchBtn.prop( 'disabled', true );
				this.$stopBtn.prop( 'disabled', true );
				this.$statusBtn.prop( 'disabled', true );
			}
		},

		/**
		 * Set button loading state.
		 *
		 * @param {jQuery}  $button  Button element.
		 * @param {boolean} loading  Whether to show loading state.
		 * @return {void}
		 */
		setButtonLoading: function( $button, loading ) {
			if ( loading ) {
				$button.addClass( 'is-loading' );
				$button.data( 'original-text', $button.text() );
				$button.html( '<span class="dashicons dashicons-update spin"></span> ' + oxybridgeAdmin.strings.processing );
			} else {
				$button.removeClass( 'is-loading' );
				$button.text( $button.data( 'original-text' ) || $button.text() );
			}
		},

		/**
		 * Update version information display.
		 *
		 * @param {string} tool    Tool name (node, npm).
		 * @param {string} version Version string.
		 * @return {void}
		 */
		updateVersionInfo: function( tool, version ) {
			var $versionSpan = $( '#oxybridge-' + tool + '-version' );
			if ( $versionSpan.length ) {
				$versionSpan.text( version );
			}
		},

		/**
		 * Get CSS class for status label.
		 *
		 * @param {string} statusLabel Status label from server.
		 * @return {string} CSS class.
		 */
		getStatusClass: function( statusLabel ) {
			var statusMap = {
				'running': 'running',
				'stopped': 'stopped',
				'error': 'error',
				'not_installed': 'not_installed',
				'not_built': 'not_built'
			};

			return statusMap[ statusLabel ] || 'stopped';
		},

		/**
		 * Get dashicon class for status.
		 *
		 * @param {string} statusClass Status class.
		 * @return {string} Dashicon class.
		 */
		getStatusIcon: function( statusClass ) {
			var iconMap = {
				'running': 'dashicons-yes-alt',
				'stopped': 'dashicons-marker',
				'error': 'dashicons-warning',
				'not_installed': 'dashicons-download',
				'not_built': 'dashicons-hammer',
				'loading': 'dashicons-update spin'
			};

			return iconMap[ statusClass ] || 'dashicons-marker';
		},

		/**
		 * Get display text for status.
		 *
		 * @param {string} statusLabel Status label.
		 * @return {string} Display text.
		 */
		getStatusText: function( statusLabel ) {
			var textMap = {
				'running': oxybridgeAdmin.strings.statusRunning,
				'stopped': oxybridgeAdmin.strings.statusStopped,
				'error': oxybridgeAdmin.strings.statusError,
				'not_installed': oxybridgeAdmin.strings.statusNotInstalled,
				'not_built': oxybridgeAdmin.strings.statusNotBuilt
			};

			return textMap[ statusLabel ] || oxybridgeAdmin.strings.statusUnknown;
		},

		/**
		 * Escape HTML entities in a string.
		 *
		 * @param {string} text Text to escape.
		 * @return {string} Escaped text.
		 */
		escapeHtml: function( text ) {
			if ( typeof text !== 'string' ) {
				return text;
			}

			var div = document.createElement( 'div' );
			div.textContent = text;
			return div.innerHTML;
		}
	};

	/**
	 * Initialize on document ready.
	 */
	$( document ).ready( function() {
		// Only initialize if we're on the Oxybridge admin page.
		if ( typeof oxybridgeAdmin !== 'undefined' && $( OxybridgeAdmin.selectors.serverCard ).length > 0 ) {
			OxybridgeAdmin.init();
		}
	} );

	// Expose for external access if needed.
	window.OxybridgeAdmin = OxybridgeAdmin;

} )( jQuery );
