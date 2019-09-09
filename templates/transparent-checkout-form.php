<?php

/**
 * Transparent checkout form.
 *
 * @author  Lucas Diogo
 * @package WooCommerce_Safe2Pay/Templates
 * @version 2.12.5
 */

if (!defined('ABSPATH')) {
	//exit; // Exit if accessed directly.
}
?>

<fieldset id="safe2pay-payment-form">

	<ul id="safe2pay-payment-methods">
		<?php if ('yes' == $tc_credit) : ?>
			<li><label><input id="safe2pay-payment-method-credit-card" type="radio" name="safe2pay_payment_method" value="credit-card" <?php checked(true, ('yes' == $tc_credit), true); ?> /> <?php _e('Credit Card', 'woocommerce-safe2pay'); ?></label></li>
		<?php endif; ?>

		<?php if ('yes' == $tc_ticket) : ?>
			<li><label><input id="safe2pay-payment-method-banking-ticket" type="radio" name="safe2pay_payment_method" value="banking-ticket" <?php checked(true, ('no' == $tc_credit && 'no' == $tc_transfer && 'yes' == $tc_ticket), true); ?> /> <?php _e('Banking Ticket', 'woocommerce-safe2pay'); ?></label></li>
		<?php endif; ?>
	</ul>
	<div class="clear"></div>

	<?php if ('yes' == $tc_credit) : ?>
		<div id="safe2pay-credit-card-form" class="safe2pay-method-form">
			<p id="safe2pay-card-holder-name-field" class="form-row form-row-first">
				<label style=" padding-top: 20px;" for="safe2pay-card-holder-name"><?php _e('Card Holder Name', 'woocommerce-safe2pay'); ?><span class="required">*</span></label>
				<input id="safe2pay-card-holder-name" name="safe2pay_card_holder_name" class="input-text" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
			</p>
			<p id="safe2pay-card-number-field" class="form-row form-row-last">
				<label for="safe2pay-card-number"><?php _e('Card Number', 'woocommerce-safe2pay'); ?> <span class="required">*</span></label>
				<input onkeypress="return IsNumber(event)" id="safe2pay-card-number" name="input-text wc-credit-card-form-card-number" type="tel" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px; width: 100%;" />
			</p>
			<div class="clear"></div>
			<p id="safe2pay-card-expiry-field" class="form-row form-row-first">
				<label for="safe2pay-card-expiry"><?php _e('Expiry (MM/YYYY)', 'woocommerce-safe2pay'); ?> <span class="required">*</span></label>
				<input maxlength="7" onkeypress="return ExpiryMask(event,this)" id="safe2pay-card-expiry" name="input-text wc-credit-card-form-card-expiry" type="tel" autocomplete="off" placeholder="<?php _e('MM / YYYY', 'woocommerce-safe2pay'); ?>" style="font-size: 1.5em; padding: 8px; width: 100%;" />
			</p>
			<p id="safe2pay-card-cvc-field" class="form-row form-row-last">
				<label for="safe2pay-card-cvc"><?php _e('Security Code', 'woocommerce-safe2pay'); ?> <span class="required">*</span></label>
				<input maxlength="4" onkeypress="return IsNumber(event)" id="safe2pay-card-cvc" name="input-text wc-credit-card-form-card-cvc" type="tel" autocomplete="off" placeholder="<?php _e('CVC', 'woocommerce-safe2pay'); ?>" style="font-size: 1.5em; padding: 8px;width: 100%;" />
			</p>
			<div class="clear"></div>
			<p id="safe2pay-card-installments-field" class="form-row form-row-first">
				<label for="safe2pay-card-installments"><?php _e('Installments', 'woocommerce-safe2pay'); ?></label>
				<select id="safe2pay-card-installments" name="safe2pay_card_installments" style="font-size: 1.5em; padding: 8px; width: 100%; height: 60px;">
				</select>
			</p>
			<div class="clear"></div>
		</div>
	<?php endif; ?>

	<div class="clear"></div>

	<?php if ('yes' == $tc_ticket) : ?>
		<div id="safe2pay-banking-ticket-form" class="safe2pay-method-form">
			<p>
				<i id="safe2pay-icon-ticket"></i>
				<?php _e('The order will be confirmed only after the payment approval.', 'woocommerce-safe2pay'); ?>
			</p>
			<p><?php _e('* After clicking "Proceed to payment" you will have access to banking ticket which you can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-safe2pay'); ?></p>
			</p>

			<div class="clear"></div>

		</div>
	<?php endif; ?>

	<p id="safe2pay-identity-field" class="form-row form-row-first">
		<label for="safe2pay-card-cvc">Informe o seu CPF/CNPJ <span class="required">*</span></label>
		<input onkeypress="MaskcpfCnpj(this)" id="safe2pay-customer-identity_bankslip" name="billing_cpf" type="tel" autocomplete="off" maxlength="18" style="font-size: 1.5em; padding: 8px;width: 100%; heigth: 100%;" />
	</p>
	<p style="margin-bottom: 10px; margin-top: 20%;"><?php esc_html_e('This purchase is being made in Brazil', 'woocommerce-safe2pay'); ?> <img src="<?php echo esc_url($flag); ?>" alt="<?php esc_attr_e('Brazilian flag', 'woocommerce-safe2pay'); ?>" style="display: inline; float: none; vertical-align: middle; border: none;" />
	</p>

</fieldset>