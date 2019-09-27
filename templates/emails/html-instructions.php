<?php

/**
 * HTML email instructions.
 *
 * @author  Lucas Diogo
 * @package WooCommerce_Safe2Pay/Templates
 * @version 2.7.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>

<h2><?php _e('Payment', 'woo-safe2pay'); ?></h2>

<?php if ('1' == $type) : ?>
	<span>
		<a class="button" href="<?php echo esc_url($link); ?>" target="_blank">
			<?php _e('Visualizar Boleto Bancário', 'woo-safe2pay'); ?>
		</a>
		<?php _e('Clique no botão para visualizar o seu boleto bancário.', 'woo-safe2pay'); ?>

		<br />

		<?php _e('Você pode imprimi-lo e pagar através do seu internet banking ou qualquer casa lotérica.', 'woo-safe2pay'); ?>

		<br />

		<?php _e('Após a confirmação do pagamento, Seu pedido será processado.', 'woo-safe2pay'); ?>
	</span>
<?php elseif ('2' == $type) : ?>
	<div class="order_details">
		<span>
			<?php _e('Pagamento autorizado', 'woo-safe2pay'); ?></a>
			<?php _e('Tudo certo! Seu pedido será processado.', 'woo-safe2pay'); ?>
	</div>

<?php elseif ('3' == $type) : ?>
	<div class="order_details">
		<div style="margin-left: auto;margin-right: auto;width: 10em;">
			<img src="<?php echo esc_url($link); ?>" alt="QR CODE">
			<br />
		</div>

		<div style="text-align: center; font-size: 12px;">
			<span style="">Wallet Address: <?php echo ($walletaddress); ?></span>
			<br>
		</div>
		
		<br />

		<div style="text-align: center;">
			<?php _e('Após a confirmação do pagamento, Seu pedido será processado.', 'woo-safe2pay'); ?>
		</div>

		<br />

	</div>
<?php elseif ('4' == $type) : ?>
	<div class="order_details">

		<?php _e('Pagamento autorizado', 'woo-safe2pay'); ?></a>
		<?php _e('Tudo certo! Seu pedido será processado.', 'woo-safe2pay'); ?>

	</div>

<?php else : ?>

	<p class="order_details"><?php echo sprintf(__('You just made the payment in %s using the %s.', 'woo-safe2pay'), '<strong>' . $installments . 'x</strong>', '<strong>' . $method . '</strong>'); ?><br /><?php _e('As soon as the credit card operator confirm the payment, your order will be processed.', 'woo-safe2pay'); ?></p>

<?php
endif;
