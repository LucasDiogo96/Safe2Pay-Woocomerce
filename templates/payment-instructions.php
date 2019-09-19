<?php

/**
 * Payment instructions.
 *
 * @author  Lucas Diogo
 * @package WooCommerce_Safe2Pay/Templates
 * @version 2.7.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>

<?php if ('1' == $type) : ?>

	<div class="woocommerce-message">
		<span>
			<a class="button" href="<?php echo esc_url($link); ?>" target="_blank">
				<?php _e('Visualizar Boleto Bancário', 'woocommerce-safe2pay'); ?>
			</a>
			<?php _e('Clique no botão para visualizar o seu boleto bancário.', 'woocommerce-safe2pay'); ?>

			<br />

			<?php _e('Você pode imprimi-lo e pagar através do seu internet banking ou qualquer casa lotérica.', 'woocommerce-safe2pay'); ?>

			<br />

			<?php _e('Após a confirmação do pagamento, Seu pedido será processado.', 'woocommerce-safe2pay'); ?>
		</span>

	</div>

<?php elseif ('2' == $type) : ?>

	<div class="woocommerce-message">
		<span>
			<?php _e('Pagamento autorizado', 'woocommerce-safe2pay'); ?></a>
			<?php _e('Tudo certo! Seu pedido será processado.', 'woocommerce-safe2pay'); ?>
	</div>

<?php elseif ('3' == $type) : ?>

	<div class="woocommerce-message">

		<span>
			<span>
				<img src="<?php echo esc_url($link); ?>" alt="QR CODE">
				</a>

				<?php _e('Após a confirmação do pagamento, Seu pedido será processado.', 'woocommerce-safe2pay'); ?>
				<br />
			
			</span>

	</div>

<?php elseif ('4' == $type) : ?>
<div class="woocommerce-message">
			<?php _e('Pagamento autorizado', 'woocommerce-safe2pay'); ?></a>
			<?php _e('Tudo certo! Seu pedido será processado.', 'woocommerce-safe2pay'); ?>
	</div>

<?php else : ?>

	<div class="woocommerce-message">
		<span><?php echo sprintf(__('Ocorreu um erro ao visualizar o recibo.', 'woocommerce-safe2pay'), '<strong>' . $installments . 'x</strong>', '<strong>' . $method . '</strong>'); ?><br /><?php _e('As soon as the credit card operator confirm the payment, your order will be processed.', 'woocommerce-safe2pay'); ?></span>
	</div>

<?php
endif;
