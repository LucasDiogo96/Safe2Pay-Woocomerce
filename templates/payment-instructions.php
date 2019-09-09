<?php
/**
 * Payment instructions.
 *
 * @author  Lucas Diogo
 * @package WooCommerce_Safe2Pay/Templates
 * @version 2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<?php if ('1' == $type ) : ?>

	<div class="woocommerce-message">
		<span><a class="button" href="<?php echo esc_url( $link ); ?>" target="_blank"><?php _e( 'Pay the Banking Ticket', 'woocommerce-safe2pay' ); ?></a><?php _e( 'Please click in the following button to view your Banking Ticket.', 'woocommerce-safe2pay' ); ?><br /><?php _e( 'You can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-safe2pay' ); ?><br /><?php _e( 'After we receive the ticket payment confirmation, your order will be processed.', 'woocommerce-safe2pay' ); ?></span>
	</div>

<?php elseif ('2' == $type ) : ?>

	<div class="woocommerce-message">
		<span>
		<?php _e( 'Pagamento autorizado', 'woocommerce-safe2pay' ); ?></a><?php _e( 'Please use the following button to make the payment in your bankline.', 'woocommerce-safe2pay' ); ?><br /><?php _e( 'After we receive the confirmation from the bank, your order will be processed.', 'woocommerce-safe2pay' ); ?></span>
	</div>

<?php else : ?>

	<div class="woocommerce-message">
		<span><?php echo sprintf( __( 'You just made the payment in %s using the %s.', 'woocommerce-safe2pay' ), '<strong>' . $installments . 'x</strong>', '<strong>' . $method . '</strong>' ); ?><br /><?php _e( 'As soon as the credit card operator confirm the payment, your order will be processed.', 'woocommerce-safe2pay' ); ?></span>
	</div>

<?php
endif;
