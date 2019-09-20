/*global wc_safe2pay_params, PagSeguroDirectPayment, wc_checkout_params */
(function ($) {
	'use strict';

	$(function () {

		var safe2pay_submit = false;

		function safe2PayGetInstallmentOption(installment) {
			return '<option value="' + installment.quantity + '" data-installment-value="' + installment.installmentAmount + '">' + safe2PayGetPriceText(installment) + '</option>';
		}

		function safe2PayGetPriceText(installment) {
			var installmentParsed = 'R$ ' + parseFloat(installment.installmentAmount, 10).toFixed(2).replace('.', ',').toString();
			var totalParsed = 'R$ ' + parseFloat(installment.totalAmount, 10).toFixed(2).replace('.', ',').toString();
			var interestFree = (true === installment.interestFree) ? ' ' + wc_safe2pay_params.interest_free : '';
			var interestText = interestFree ? interestFree : ' (' + totalParsed + ')';
			return installment.quantity + 'x ' + installmentParsed + interestText;
		}

		function InstallmentInit() {

			try {

				var instalmments = $('body #safe2pay-card-installments');

				instalmments.empty();
				instalmments.removeAttr('disabled');
				instalmments.append('<option value="0">--</option>');

			    var currency =  $('.amount')[$('.amount').length - 1].innerText;  //it works for US-style currency strings as well
				var cur_re = /\D*(\d+|\d.*?\d)(?:\D+(\d{2}))?\D*$/;
				var parts = cur_re.exec(currency);
				var total = parseFloat(parts[1].replace(/\D/, '') + '.' + (parts[2] ? parts[2] : '00'));

				for (let index = 1; index <= 12; index++) {

					var installment = {

						quantity: index,
						totalAmount: total,
						installmentAmount: total / index
					};

					instalmments.append(safe2PayGetInstallmentOption(installment));
				}
			} catch (error) {
				ShowMessageError(wc_safe2pay_params.invalid_card);
			}
		}

		function ShowMessageError(error) {
			var wrapper = $('#safe2pay-credit-card-form');

			$('.woocommerce-error', wrapper).remove();
			wrapper.prepend('<div class="woocommerce-error" style="margin-bottom: 0.5em !important;">' + error + '</div>');
		}

		function HidePaymentMethods() {
			var paymentMethods = $('#safe2pay-payment-methods');
			if (1 === $('input[type=radio]', paymentMethods).length) {
				paymentMethods.hide();
			}
		}

		function HidePaymentForm(method) {
			// window.alert( method );
			$('.safe2pay-method-form').hide();
			$('#safe2pay-payment-methods li').removeClass('active');
			$('#safe2pay-' + method + '-form').show();
			$('#safe2pay-payment-method-' + method).parent('label').parent('li').addClass('active');
		}

		function Init() {
			HidePaymentMethods();
			InstallmentInit();

			$('#safe2pay-payment-form').show();

			HidePaymentForm($('#safe2pay-payment-methods input[type=radio]:checked').val());

			var MaskBehavior = function (val) {
					return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
				},
				maskOptions = {
					onKeyPress: function (val, e, field, options) {
						field.mask(MaskBehavior.apply({}, arguments), options);
					}
				};
		}

		function safe2PayformHandler() {
			if (safe2pay_submit) {
				safe2pay_submit = false;

				return true;
			}

			if (!$('#payment_method_safe2pay').is(':checked')) {
				return true;
			}

			if ('credit-card' !== $('body li.payment_method_safe2pay input[name=safe2pay_payment_method]:checked').val()) {
				$('form.checkout, form#order_review').append($('<input name="safe2pay_sender_hash" type="hidden" />').val(PagSeguroDirectPayment.getSenderHash()));

				return true;
			}

			var form = $('form.checkout, form#order_review'),
				creditCardForm = $('#safe2pay-credit-card-form', form),
				error = false,
				errorHtml = '',
				holder = $('#safe2pay-card-holder-name').val(),
				cardNumber = $('#safe2pay-card-number', form).val().replace(/[^\d]/g, ''),
				cvv = $('#safe2pay-card-cvc', form).val(),
				expiration = $('#safe2pay-card-expiry', form).val(),
				expirationMonth = expiration.replace(/[^\d]/g, '').substr(0, 2),
				expirationYear = expiration.replace(/[^\d]/g, '').substr(2),
				installments = $('#safe2pay-card-installments', form),
				today = new Date();

			// Validate the credit card data.
			errorHtml += '<ul>';


			// Validate the expiry date.
			if (2 !== expirationMonth.length || 4 !== expirationYear.length) {
				errorHtml += '<li>' + wc_safe2pay_params.invalid_expiry + '</li>';
				error = true;
			}

			if ((2 === expirationMonth.length && 4 === expirationYear.length) && (expirationMonth > 12 || expirationYear <= (today.getFullYear() - 1) || expirationYear >= (today.getFullYear() + 20) || (expirationMonth < (today.getMonth() + 2) && expirationYear.toString() === today.getFullYear().toString()))) {
				errorHtml += '<li>' + wc_safe2pay_params.expired_date + '</li>';
				error = true;
			}

			// Installments.
			if ('0' === installments.val()) {
				errorHtml += '<li>' + wc_safe2pay_params.empty_installments + '</li>';
				error = true;
			}

			errorHtml += '</ul>';
			// Create the card token.
			if (!error) {

				$('input[name=safe2pay_credit_card_hash], input[name=safe2pay_credit_card_hash], input[name=safe2pay_installment_value]', form).remove();

				//add credit card
				//Holder
				form.append($('<input name="safe2pay-card-holder-name" type="hidden" />').val(holder));
				//Number
				form.append($('<input name="safe2pay-card-number" type="hidden" />').val(cardNumber));
				//Expiration Date
				form.append($('<input name="safe2pay-card-expiry-field" type="hidden" />').val(expiration));
				//CVC
				form.append($('<input name="safe2pay-card-cvc" type="hidden" />').val(cvv));
				//Instalmentes
				form.append($('<input name="safe2pay-card-installments" type="hidden" />').val(installments.val()));

				form.append($('<input name="safe2pay-card-installments" type="hidden" />').val(installments.val()));

				// Submit the form.
				safe2pay_submit = true;
				form.submit();
			} else {
				ShowMessageError(errorHtml);
			}

			return false;
		}

		Init();
		$('body').on('updated_checkout', function () {
			Init();
		});

		// Switch the payment method form.
		$('body').on('click', '#safe2pay-payment-methods input[type=radio]', function () {
			HidePaymentForm($(this).val());
		});


		$('body').on('updated_checkout', function () {
			var field = $('body #safe2pay-card-number');

			if (0 < field.length) {
				field.focusout();
			}
		});

		// Set the errors.
		$('body').on('focus', '#safe2pay-card-number, #safe2pay-card-expiry', function () {
			$('#safe2pay-credit-card-form .woocommerce-error').remove();
		});


		// Process the credit card data when submit the checkout form.
		$('form.checkout').on('checkout_place_order_safe2pay', function () {
			return safe2PayformHandler();
		});

		$('form#order_review').submit(function () {
			return safe2PayformHandler();
		});

	});

}(jQuery));


function MaskcpfCnpj(e) {

	v = e.value;

	//Remove tudo o que não é dígito
	v = v.replace(/\D/g, "");

	if (v.length <= 11) { //CPF

		//Coloca um ponto entre o terceiro e o quarto dígitos
		v = v.replace(/(\d{3})(\d)/, "$1.$2");

		//Coloca um ponto entre o terceiro e o quarto dígitos
		//de novo (para o segundo bloco de números)
		v = v.replace(/(\d{3})(\d)/, "$1.$2");

		//Coloca um hífen entre o terceiro e o quarto dígitos
		v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");

	} else { //CNPJ

		//Coloca ponto entre o segundo e o terceiro dígitos
		v = v.replace(/^(\d{2})(\d)/, "$1.$2");

		//Coloca ponto entre o quinto e o sexto dígitos
		v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");

		//Coloca uma barra entre o oitavo e o nono dígitos
		v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");

		//Coloca um hífen depois do bloco de quatro dígitos
		v = v.replace(/(\d{4})(\d)/, "$1-$2");

	}

	e.value = v;
};

function IsNumber(evt) {
	evt = (evt) ? evt : window.event;
	var charCode = (evt.which) ? evt.which : evt.keyCode;
	if (charCode > 31 && (charCode < 48 || charCode > 57)) {
		return false;
	}
	return true;
}

function ExpiryMask(evt, e) {

	evt = (evt) ? evt : window.event;
	var charCode = (evt.which) ? evt.which : evt.keyCode;
	if (charCode > 31 && (charCode < 48 || charCode > 57)) {
		return false;
	}

	v = e.value;

	//Remove tudo o que não é dígito
	v = v.replace(/\D/g, "");

	//Coloca um ponto entre o terceiro e o quarto dígitos
	v = v.replace(/(\d{2})(\d{0})/, "$1/$2");


	e.value = v;

	return true;

}