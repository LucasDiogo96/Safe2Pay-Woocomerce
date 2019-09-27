<?php
/**
 * Plain email instructions.
 *
 * @author  Lucas Diogo
 * @package WooCommerce_Safe2Pay/Templates
 * @version 2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

_e( 'Payment', 'woo-safe2pay' );

echo "\n\n";

if ( '1' == $type ) {

	_e( 'Please use the link below to view your Banking Ticket, you can print and pay in your internet banking or in a lottery retailer:', 'woo-safe2pay' );

	echo "\n";

	echo esc_url( $link );

	echo "\n";

	_e( 'After we receive the ticket payment confirmation, your order will be processed.', 'woo-safe2pay' );

} elseif ( '2' == $type ) {

	_e( 'Please use the link below to make the payment in your bankline:', 'woo-safe2pay' );

	echo "\n";

	echo esc_url( $link );

	echo "\n";

	_e( 'After we receive the confirmation from the bank, your order will be processed.', 'woo-safe2pay' );

} else {

	echo sprintf( __( 'You just made the payment in %s using the %s.', 'woo-safe2pay' ), $installments . 'x', $method );

	echo "\n";

	_e( 'As soon as the credit card operator confirm the payment, your order will be processed.', 'woo-safe2pay' );

}

echo "\n\n****************************************************\n\n";
