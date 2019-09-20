<?php

/**
 * WooCommerce Safe2Pay API class
 *
 * @package WooCommerce_Safe2Pay/Classes/API
 * @version 2.12.0
 */

if (!defined('ABSPATH')) {
	exit;
}

class WC_Safe2Pay_API
{

	protected $gateway;


	public function __construct($gateway = null)
	{
		$this->gateway = $gateway;
	}


	protected function HttpClient($url, $method = 'POST', $data = array(), $headers = array())
	{
		$params = array(
			'method'  => $method,
			'timeout' => 60,
		);

		if ('POST' == $method && !empty($data)) {
			$params['body'] = $data;
		}

		if (!empty($headers)) {
			$params['headers'] = $headers;
		}

		return wp_safe_remote_post($url, $params);
	}

	protected function GetPaymentURI()
	{
		return 'https://payment.safe2pay.com.br/v2/Payment';
	}

	public function GetCallbackURI($orderId)
	{

		// output: /myproject/index.php
		$currentPath = $_SERVER['PHP_SELF'];

		// output: Array ( [dirname] => /myproject [basename] => index.php [extension] => php [filename] => index ) 
		$pathInfo = pathinfo($currentPath);

		// output: localhost
		$hostName = $_SERVER['HTTP_HOST'];

		// output: http://
		$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https' ? 'https' : 'http';

		// return: http://localhost/myproject/
		return $protocol . '://' . $hostName . $pathInfo['dirname'] . "/" . 'wp-json/safe2pay/v2/callback/'.$orderId;
	}

	public function GetPaymentMethod($method)
	{
		$methods = array(
			'credit-card'    => 'creditCard',
			'banking-ticket' => 'boleto',
			'debit-card' => 'debitCard',
			'crypto-currency' => 'cryptocurrency',
		);

		return isset($methods[$method]) ? $methods[$method] : '';
	}

	public function GetErrorMessage($code)
	{
		$code = (string) $code;

		$messages = array(
			'11013' => __('Please enter with a valid phone number with DDD. Example: (11) 5555-5555.', 'woocommerce-safe2pay'),
			'11014' => __('Please enter with a valid phone number with DDD. Example: (11) 5555-5555.', 'woocommerce-safe2pay'),
			'53018' => __('Please enter with a valid phone number with DDD. Example: (11) 5555-5555.', 'woocommerce-safe2pay'),
			'53019' => __('Please enter with a valid phone number with DDD. Example: (11) 5555-5555.', 'woocommerce-safe2pay'),
			'53020' => __('Please enter with a valid phone number with DDD. Example: (11) 5555-5555.', 'woocommerce-safe2pay'),
			'53021' => __('Please enter with a valid phone number with DDD. Example: (11) 5555-5555.', 'woocommerce-safe2pay'),
			'11017' => __('Please enter with a valid zip code number.', 'woocommerce-safe2pay'),
			'53022' => __('Please enter with a valid zip code number.', 'woocommerce-safe2pay'),
			'53023' => __('Please enter with a valid zip code number.', 'woocommerce-safe2pay'),
			'53053' => __('Please enter with a valid zip code number.', 'woocommerce-safe2pay'),
			'53054' => __('Please enter with a valid zip code number.', 'woocommerce-safe2pay'),
			'11164' => __('Please enter with a valid CPF number.', 'woocommerce-safe2pay'),
			'53110' => '',
			'53111' => __('Please select a bank to make payment by bank transfer.', 'woocommerce-safe2pay'),
			'53045' => __('Credit card holder CPF is required.', 'woocommerce-safe2pay'),
			'53047' => __('Credit card holder birthdate is required.', 'woocommerce-safe2pay'),
			'53042' => __('Credit card holder name is required.', 'woocommerce-safe2pay'),
			'53049' => __('Credit card holder phone is required.', 'woocommerce-safe2pay'),
			'53051' => __('Credit card holder phone is required.', 'woocommerce-safe2pay'),
			'11020' => __('The address complement is too long, it cannot be more than 40 characters.', 'woocommerce-safe2pay'),
			'53028' => __('The address complement is too long, it cannot be more than 40 characters.', 'woocommerce-safe2pay'),
			'53029' => __('<strong>Neighborhood</strong> is a required field.', 'woocommerce-safe2pay'),
			'53046' => __('Credit card holder CPF invalid.', 'woocommerce-safe2pay'),
			'53122' => __('Invalid email domain. You must use an email @sandbox.safe2pay.com.br while you are using the Safe2Pay Sandbox.', 'woocommerce-safe2pay'),
			'53081' => __('The customer email can not be the same as the Safe2Pay account owner.', 'woocommerce-safe2pay'),
		);

		if (isset($messages[$code])) {
			return $messages[$code];
		}

		return __('An error has occurred while processing your payment, please review your data and try again. Or contact us for assistance.', 'woocommerce-safe2pay');
	}

	protected function GetAvailablePaymentMethods()
	{
		$methods = array();

		$token = strtoupper($this->gateway->settings['sandbox']) !== "NO" ? $this->gateway->sandbox_token : $this->gateway->token;
		$response = $this->HttpClient('https://api.safe2pay.com.br/v2/MerchantPaymentMethod/List', 'GET', null, array('Content-Type' => 'application/json', 'X-API-KEY' => $token));

		$response = json_decode($response['body']);

		if ($response->HasError == false) {


			foreach ($response->ResponseDetail as $key => $value) {

				if ($value->PaymentMethod->Code === '1') {
					$methods[] = 'banking-ticket';
				}

				if ($value->PaymentMethod->Code === '2') {
					$methods[] = 'credit-card';
				}

				if ($value->PaymentMethod->Code === '3') {
					$methods[] = 'crypto-currency';
				}

				if ($value->PaymentMethod->Code === '4') {
					$methods[] = 'debit-card';
				}
			}
		}


		return $methods;
	}

	protected function GetPayload($order, $posted, $IsSandbox)
	{

		//Metodo de pagamento
		$method  = isset($posted['safe2pay_payment_method']) ? $this->GetPaymentMethod($posted['safe2pay_payment_method']) : '';
		$paymentMethod = 0;
		$PaymentObject = null;

		$payloadProduct = new stdClass();
		$payloadProduct->Code = 1; // PHP creates  a Warning here
		$payloadProduct->Description = "Ordem #". $order->get_id();
		$payloadProduct->Quantity = 1;
		$payloadProduct->UnitPrice = $order->get_total();

		$Products = array($payloadProduct);

		if ('BOLETO' === strtoupper($method)) {

			$dueDate = new DateTime();
			$dueDate->add(new DateInterval('P3D'));

			$paymentMethod  = "1";
			$PaymentObject = array(
				"DueDate"   => $dueDate->format('Y-m-d')
			);
		} else if ('CREDITCARD' === strtoupper($method)) {
			$paymentMethod  = "2";
			$PaymentObject = array(
				'Holder' => $posted['safe2pay-card-holder-name'],
				'CardNumber' => $posted['safe2pay-card-number'],
				'ExpirationDate' => $posted['safe2pay-card-expiry-field'],
				'SecurityCode' => $posted['safe2pay-card-cvc'],
				'InstallmentQuantity' => $posted['safe2pay-card-installments']
			);
		} else if ('CRYPTOCURRENCY' === strtoupper($method)) {
			$paymentMethod  = "3";
			$PaymentObject = array(
				'Symbol' => $posted['safe2pay_currency-type'],
			);
		} else if ('DEBITCARD' === strtoupper($method)) {
			$paymentMethod  = "4";
			$PaymentObject = array(
				'Holder' => $posted['safe2pay-debit-card-holder-name'],
				'CardNumber' => $posted['safe2pay-debit-card-number'],
				'ExpirationDate' => $posted['safe2pay-debit-card-expiry'],
				'SecurityCode' => $posted['safe2pay-debit-card-cvc']
			);
		}


		//Monta payload Safe2Pay
		$payload = array(
			'IsSandbox' => $IsSandbox,
			'Application' => 'Safe2Pay',
			'PaymentMethod' => $paymentMethod,
			'PaymentObject' => $PaymentObject,
			'Reference' => $order->get_id(),
			'Products' => $Products,
			'Customer' => array(
				"Name" => $posted['billing_first_name'] . ' ' . $posted['billing_last_name'],
				"Identity" => $posted['billing_cpf'],
				"Phone" => $posted['billing_phone'],
				"Email" => $posted['billing_email'],
				"Address" => array(
					"Street" => $posted['billing_address_1'],
					"Number" =>   isset($posted['shipping_number']) ? $posted['shipping_number'] : 'S/N',
					"District" =>  isset($posted['shipping_neighborhood']) ? $posted['shipping_neighborhood'] : 'Não informado',
					"ZipCode" => $posted['billing_postcode'],
					"CityName" =>  $posted['billing_city'],
					"StateInitials" => $posted['shipping_state'],
					"CountryName" =>  'BRASIL'
				)
			),
			'CallbackUrl' => $this->GetCallbackURI($order->Id)
		);

		return json_encode($payload);
	}

	public function CheckoutController($order, $posted)
	{

		try {

			$IsSandbox = (strtoupper($this->gateway->settings['sandbox']) !== "NO" ? true : false);

			$payload = $this->GetPayload($order, $posted, $IsSandbox);

			if ('yes' == $this->gateway->debug) {
				$this->gateway->log->add($this->gateway->id, 'Requesting token for order ' . $order->get_order_number() . ' with the following data: ' . $payload);
			}

			$token = strtoupper($this->gateway->settings['sandbox']) !== "NO" ? $this->gateway->sandbox_token : $this->gateway->token;

			$response = $this->HttpClient($this->GetPaymentURI(), 'POST', $payload, array('Content-Type' => 'application/json', 'X-API-KEY' => $token));

			if ($response['response']['code'] === 200) {


				$response = json_decode($response["body"]);


				if ($response->HasError == false) {

					return array(
						'url'   => $this->gateway->GetPaymentURI(),
						'data'  => $response,
						'error' => '',
					);


					if ('yes' == $this->gateway->debug) {
						$this->gateway->log->add($this->gateway->id, 'Transação efetuada com sucesso!');
					}


					return array(
						'url'   => $this->GetPaymentURI(),
						'token' => $response->TransactionId,
						'error' => '',
					);
				}
			} else if ($response['response']['code'] === 401) {

				if ('yes' == $this->gateway->debug) {
					$this->gateway->log->add($this->gateway->id, 'Invalid token and/or email settings!');
				}

				return array(
					'url'   => '',
					'data'  => '',
					'error' => array(__('Too bad! The email or token from the Safe2Pay are invalids my little friend!', 'woocommerce-safe2pay')),
				);
			}
		} catch (Exception $e) {

			// Return error message.
			return array(
				'url'   => '',
				'token' => '',
				'error' => array('<strong>' . __('Safe2Pay', 'woocommerce-safe2pay') . '</strong>: ' . __('An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woocommerce-safe2pay')),
			);
		}
	}

	public function PaymentController($order, $posted)
	{
		$payment_method = isset($posted['safe2pay_payment_method']) ? $posted['safe2pay_payment_method'] : '';

		/**
		 * Validate if has selected a payment method.
		 */
		if (!in_array($payment_method, $this->GetAvailablePaymentMethods())) {
			return array(
				'url'   => '',
				'data'  => '',
				'error' => array('<strong>' . __('Safe2Pay', 'woocommerce-safe2pay') . '</strong>: ' .  __('Please, select a payment method.', 'woocommerce-safe2pay')),
			);
		}

		$IsSandbox = (strtoupper($this->gateway->settings['sandbox']) !== "NO" ? true : false);

		$payload = $this->GetPayload($order, $posted, $IsSandbox);


		if ('yes' == $this->gateway->debug) {
			$this->gateway->log->add($this->gateway->id, 'Requesting direct payment for order ' . $order->get_order_number() . ' with the following data: ' . $payload);
		}

		$token = strtoupper($this->gateway->settings['sandbox']) !== "NO" ? $this->gateway->sandbox_token : $this->gateway->token;

		$response = $this->HttpClient($this->GetPaymentURI(), 'POST', $payload, array('Content-Type' => 'application/json', 'X-API-KEY' => $token));

		if (is_wp_error($response)) {
			if ('yes' == $this->gateway->debug) {
				$this->gateway->log->add($this->gateway->id, 'WP_Error in requesting the direct payment: ' . $response->GetErrorMessage());
			}
		} else if (401 === $response['response']['code']) {
			if ('yes' == $this->gateway->debug) {
				$this->gateway->log->add($this->gateway->id, 'The user does not have permissions to use the Safe2Pay Transparent Checkout!');
			}

			return array(
				'url'   => '',
				'data'  => '',
				'error' => array(__('You are not allowed to use the Safe2Pay Transparent Checkout. Looks like you neglected to installation guide of this plugin. This is not pretty, do you know?', 'woocommerce-safe2pay')),
			);
		} else {
			try {

				$response = json_decode($response["body"]);


				if ($response->HasError == false) {


					if ('yes' == $this->gateway->debug) {
						$this->gateway->log->add($this->gateway->id, 'Pagamento gerado com sucesso!');
					}

					return array(
						'url'   => $this->gateway->get_return_url($order),
						'data'  => $response->ResponseDetail,
						'error' => '',
					);
				} else {

					// $this->gateway->log->add($this->gateway->id, '!');

					return array(
						'url'   => '',
						'data'  => '',
						'error' => array('<strong>' . __('Safe2Pay', 'woocommerce-safe2pay') . '</strong>: ' .  __($response->Error, 'woocommerce-safe2pay')),
					);
				}
			} catch (Exception $e) {
				$data = '';

				if ('yes' == $this->gateway->debug) {
					$this->gateway->log->add($this->gateway->id, 'Error while parsing the Safe2Pay response: ' . print_r($e->getMessage(), true));
				}
			}
		}
	}



	public function get_direct_payment_url()
	{
		return 'https://stc.' . '' . 'pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js';
	}
}
