( function( window, undefined ) {
	'use strict';

	jQuery( function( $ ) {

		/* ===================== *
		 * Add Calendar Meta Box *
		 * ===================== */

		var ajaxEnhance = false,
			widgets = $( '#widgets-left' );

		if ( widgets.length ) {

			// See: http://wordpress.stackexchange.com/a/37707/48502
			$( document ).ajaxComplete( function( event, XMLHttpRequest, ajaxOptions ) {

				// Determine which ajax request is this (we are after "save-widget").
				var request = {},
					pairs = ajaxOptions.data.split( '&' ),
					i,
					split,
					widget;

				for ( i in pairs ) {
					split = pairs[i].split('=');
					request[ decodeURIComponent( split[0] ) ] = decodeURIComponent( split[1] );
				}

				if ( request.action && ( request.action === 'save-widget' ) ) {

					widget = $( 'input.widget-id[value="' + request['widget-id'] + '"]' ).parents( '.widget' );

					// Trigger manual save, if this was the save request
					// and if we didn't get the form html response.
					if ( !XMLHttpRequest.responseText ) {
						wpWidgets.save( widget, 0, 1, 0 );
					} else {
						// We got a response, this could be either our request above,
						// or a correct widget-save call, so fire an event on which we can hook our code.
						$( document ).trigger( 'saved_widget', widget );
					}

				}

			} );

			// Bind our Select2 function to the saved_widget event.
			$( document ).bind( 'saved_widget', function( event, widget ) {
				ajaxEnhance = true;
				enhanceDropDown();
			} );

		}

		if ( ! ajaxEnhance ) {
			enhanceDropDown();
		}

		/**
		 * Select2.
		 */
		function enhanceDropDown() {
			$( '.simcal-field-select-enhanced' ).each( function( e, i ) {

				var field      = $( i ),
					noResults  = field.data( 'noresults' ),
					allowClear = field.data( 'allowclear' );

				field.select2({
					allowClear     : allowClear != 'undefined' ? allowClear : false,
					placeholder    : {
						id         : '',
						placeholder: ''
					},
					dir            : simcal_admin.text_dir != 'undefined' ? simcal_admin.text_dir : 'ltr',
					tokenSeparators: [','],
					width          : '100%',
					language       : {
						noResults: function() {
							return noResults != 'undefined' ? noResults : '';
						}
					}
				} );
			} );
		}

		/* ========================= *
		 * Add Calendar Media Button *
		 * ========================= */

		// Very Ugly ThickBox hack: https://core.trac.wordpress.org/ticket/17249
		$( '#simcal-insert-shortcode-button' ).on( 'click', function() {
			// ThickBox creates a div which is not immediately available.
			setTimeout( function() {
				var thickBox = document.getElementById( 'TB_window');
				if ( thickBox != 'undefined' ) {
					thickBox.classList.add( 'simcal-insert-shortcode-modal' );
				}
				var thickBoxTitle = document.getElementById( 'TB_title' );
				if ( thickBoxTitle != 'undefined' ) {
					thickBoxTitle.classList.add( 'simcal-insert-shortcode-modal-title' );
				}
			}, 120 );
		} );

		// Add shortcode in WordPress post editor.
		$( '#simcal-insert-shortcode' ).on( 'click', function( e ) {

			e.preventDefault();

			var feedId = $( '#simcal-choose-calendar' ).val();

			wp.media.editor.insert( '[calendar id="' + feedId + '"] ' );

			// Close Thickbox.
			tb_remove();
		} );

	} );

} )( this );
