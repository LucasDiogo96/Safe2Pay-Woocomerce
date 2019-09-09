(function ( $ ) {
	'use strict';

	$( function () {

		/**
		 * Switch transparent checkout options display basead in payment type.
		 *
		 * @param {String} method
		 */
		function safe2PaySwitchTCOptions( method ) {
			var fields  = $( '#woocommerce_safe2pay_tc_credit' ).closest( '.form-table' ),
				heading = fields.prev( 'h3' );

			if ( 'transparent' === method ) {
				fields.show();
				heading.show();
			} else {
				fields.hide();
				heading.hide();
			}
		}

		/**
		 * Switch banking ticket message display.
		 *
		 * @param {String} checked
		 */
		function safe2PaySwitchOptions( checked ) {
			var fields = $( '#woocommerce_safe2pay_tc_ticket_message' ).closest( 'tr' );

			if ( checked ) {
				fields.show();
			} else {
				fields.hide();
			}
		}

		/**
		 * Awitch user data for sandbox and production.
		 *
		 * @param {String} checked
		 */
		function safe2PaySwitchUserData( checked ) {
			var secretkey = $( '#woocommerce_safe2pay_secretkey' ).closest( 'tr' ),
				token = $( '#woocommerce_safe2pay_token' ).closest( 'tr' ),
				sandboxsecretkey = $( '#woocommerce_safe2pay_sandbox_secretkey' ).closest( 'tr' ),
				sandboxToken = $( '#woocommerce_safe2pay_sandbox_token' ).closest( 'tr' );

			if ( checked ) {
				secretkey.hide();
				token.hide();
				sandboxsecretkey.show();
				sandboxToken.show();
			} else {
				secretkey.show();
				token.show();
				sandboxsecretkey.hide();
				sandboxToken.hide();
			}
		}

		safe2PaySwitchTCOptions( $( '#woocommerce_safe2pay_method' ).val() );

		$( 'body' ).on( 'change', '#woocommerce_safe2pay_method', function () {
			safe2PaySwitchTCOptions( $( this ).val() );
		}).change();

		safe2PaySwitchOptions( $( '#woocommerce_safe2pay_tc_ticket' ).is( ':checked' ) );
		$( 'body' ).on( 'change', '#woocommerce_safe2pay_tc_ticket', function () {
			safe2PaySwitchOptions( $( this ).is( ':checked' ) );
		});

		safe2PaySwitchUserData( $( '#woocommerce_safe2pay_sandbox' ).is( ':checked' ) );
		$( 'body' ).on( 'change', '#woocommerce_safe2pay_sandbox', function () {
			safe2PaySwitchUserData( $( this ).is( ':checked' ) );
		});
	});

}( jQuery ));
