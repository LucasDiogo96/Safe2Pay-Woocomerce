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

	<ul id="safe2pay-payment-methods" style="margin-bottom: 5%;">
	<?php if ('yes' == $tc_ticket) : ?>
			<li><label>
					<input id="safe2pay-payment-method-banking-ticket" type="radio" name="safe2pay_payment_method" value="banking-ticket" <?php checked(true, ('no' == $tc_cryptocurrency && 'no' == $tc_credit && 'no' == $tc_debit && 'yes' == $tc_ticket), true); ?> />
					<?php _e('Boleto', 'woocommerce-safe2pay'); ?>

				</label></li>
		<?php endif; ?>
		<?php if ('yes' == $tc_credit) : ?>
			<li><label>

					<input id="safe2pay-payment-method-credit-card" type="radio" name="safe2pay_payment_method" value="credit-card" <?php checked(true, ('yes' == $tc_credit), true); ?> />
					<?php _e('Cartão de Crédito', 'woocommerce-safe2pay'); ?>

				</label></li>
		<?php endif; ?>
	
		<?php if ('yes' == $tc_debit) : ?>
			<li><label>

					<input id="safe2pay-payment-method-banking-ticket" type="radio" name="safe2pay_payment_method" value="debit-card" <?php checked(true, ('no' == $tc_cryptocurrency && 'no' == $tc_credit && 'yes' == $tc_debit && 'no' == $tc_ticket), true); ?> />
					<?php _e('Cartão de Débito', 'woocommerce-safe2pay'); ?>

				</label></li>

		<?php endif; ?>
		<?php if ('yes' == $tc_cryptocurrency) : ?>
			<li><label>

					<input id="safe2pay-payment-method-banking-ticket" type="radio" name="safe2pay_payment_method" value="crypto-currency" <?php checked(true, ('yes' == $tc_cryptocurrency && 'no' == $tc_credit && 'no' == $tc_debit && 'no' == $tc_ticket), true); ?> />
					<?php _e('Criptomoeda', 'woocommerce-safe2pay'); ?>

				</label></li>

		<?php endif; ?>
	</ul>
	<div class="clear"></div>

	<?php if ('yes' == $tc_credit) : ?>
		<div id="safe2pay-credit-card-form" class="safe2pay-method-form">
		
			<p>
				<i id="safe2pay-icon-credit-card"></i>
				<?php _e('Realize o pagamento através do seu cartão de crédito.', 'woocommerce-safe2pay'); ?>				
			</p>
			
			<div class="clear"></div>

			<p id="safe2pay-card-holder-name-field" class="form-row form-row-first">
				<label for="safe2pay-card-holder-name"><?php _e('Nome impresso no cartão', 'woocommerce-safe2pay'); ?><span class="required">*</span></label>
				<input id="safe2pay-card-holder-name" name="safe2pay_card_holder_name" class="input-text" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
			</p>
			<p id="safe2pay-card-number-field" class="form-row form-row-last">
				<label for="safe2pay-card-number"><?php _e('Número do cartão', 'woocommerce-safe2pay'); ?> <span class="required">*</span></label>
				<input onkeypress="return IsNumber(event)" id="safe2pay-card-number" name="input-text wc-credit-card-form-card-number" type="tel" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px; width: 100%;" />
			</p>
			<div class="clear"></div>
			<p id="safe2pay-card-expiry-field" class="form-row form-row-first">
				<label for="safe2pay-card-expiry"><?php _e('Validade (MM/YYYY)', 'woocommerce-safe2pay'); ?> <span class="required">*</span></label>
				<input maxlength="7" onkeypress="return ExpiryMask(event,this)" id="safe2pay-card-expiry" name="input-text wc-credit-card-form-card-expiry" type="tel" autocomplete="off" placeholder="<?php _e('MM / YYYY', 'woocommerce-safe2pay'); ?>" style="font-size: 1.5em; padding: 8px; width: 100%;" />
			</p>
			<p id="safe2pay-card-cvc-field" class="form-row form-row-last">
				<label for="safe2pay-card-cvc"><?php _e('CVV', 'woocommerce-safe2pay'); ?> <span class="required">*</span></label>
				<input maxlength="4" onkeypress="return IsNumber(event)" id="safe2pay-card-cvc" name="input-text wc-credit-card-form-card-cvc" type="tel" autocomplete="off" placeholder="<?php _e('CVV', 'woocommerce-safe2pay'); ?>" style="font-size: 1.5em; padding: 8px;width: 100%;" />
			</p>
			<div class="clear"></div>
			<p id="safe2pay-card-installments-field" class="form-row form-row-first">
				<label for="safe2pay-card-installments"><?php _e('Parcelar em', 'woocommerce-safe2pay'); ?></label>
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
				<?php _e('Realize o pagamento através de boleto bancário.', 'woocommerce-safe2pay'); ?>
			</p>	
			<div class="clear"></div>

		</div>
	<?php endif; ?>

	<?php if ('yes' == $tc_debit) : ?>

		<div id="safe2pay-debit-card-form" class="safe2pay-method-form">

			<p>
				<i id="safe2pay-icon-debit-card"></i>
				<?php _e('Realize o pagamento através do seu cartão de débito.', 'woocommerce-safe2pay'); ?>
			</p>

			<div class="clear"></div>


			<p id="safe2pay-debit-card-holder-name-field" class="form-row form-row-first">
				<label for="safe2pay-debit-card-holder-name"><?php _e('Nome impresso no cartão', 'woocommerce-safe2pay'); ?><span class="required">*</span></label>
				<input id="safe2pay-debit-card-holder-name" name="safe2pay-debit-card-holder-name" class="input-text" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;height: 60px;" />
			</p>
			<p id="safe2pay-debit-card-number-field" class="form-row form-row-last">
				<label for="safe2pay-debit-card-number"><?php _e('Número do cartão', 'woocommerce-safe2pay'); ?> <span class="required">*</span></label>
				<input onkeypress="return IsNumber(event)" id="safe2pay-debit-card-number" name="safe2pay-debit-card-number" type="tel" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px; width: 100%;" />
			</p>
			<div class="clear"></div>
			<p id="safe2pay-debit-card-expiry-field" class="form-row form-row-first">
				<label for="safe2pay-debit-card-expiry"><?php _e('Validade (MM/YYYY)', 'woocommerce-safe2pay'); ?> <span class="required">*</span></label>
				<input maxlength="7" onkeypress="return ExpiryMask(event,this)" id="safe2pay-debit-card-expiry" name="safe2pay-debit-card-expiry" type="tel" autocomplete="off" placeholder="<?php _e('MM / YYYY', 'woocommerce-safe2pay'); ?>" style="font-size: 1.5em; padding: 8px; width: 100%;" />
			</p>
			<p id="safe2pay-debit-card-cvc-field" class="form-row form-row-last">
				<label for="safe2pay-debit-card-cvc"><?php _e('CVV', 'woocommerce-safe2pay'); ?> <span class="required">*</span></label>
				<input maxlength="4" onkeypress="return IsNumber(event)" id="safe2pay-debit-card-cvc" name="safe2pay-debit-card-cvc" type="tel" autocomplete="off" placeholder="<?php _e('CVV', 'woocommerce-safe2pay'); ?>" style="font-size: 1.5em; padding: 8px;width: 100%;" />
			</p>
			<div class="clear"></div>
		</div>
	<?php endif; ?>

	<?php if ('yes' == $tc_cryptocurrency) : ?>
		<div id="safe2pay-crypto-currency-form" class="safe2pay-method-form">
			<p>
				<i id="safe2pay-icon-cryptocurrency"></i>
				<?php _e('Realize o pagamento através de criptomoedas.', 'woocommerce-safe2pay'); ?>
			</p>

			<div class="clear"></div>

			<p id="safe2pay-currency-type-field" class="form-row form-row-first">
				<label for="safe2pay-currency-type"><?php _e('Informe o tipo de moeda.', 'woocommerce-safe2pay'); ?></label>
				<select id="safe2pay-currency-type" name="safe2pay_currency-type" style="font-size: 1.5em; padding: 8px; width: 100%; height: 60px;">
				<option value="0">--</option>
				<option value="BTC">Bitcoin</option>
				<option value="LTC">Litecoin</option>
				<option value="BCH">Bitcoin Cash</option>
				</select>
			</p>

		</div>
	<?php endif; ?>

	<div class="clear"></div>

	<p id="safe2pay-identity-field" class="form-row form-row-first">
		<label for="safe2pay-card-cvc">Confirme o seu CPF/CNPJ <span class="required">*</span></label>
		<input onkeypress="MaskcpfCnpj(this)" id="safe2pay-customer-identity_bankslip" name="billing_cpf" type="tel" autocomplete="off" maxlength="18" style="font-size: 1.5em; padding: 8px;width: 100%; heigth: 100%;" />
	</p>
</fieldset>