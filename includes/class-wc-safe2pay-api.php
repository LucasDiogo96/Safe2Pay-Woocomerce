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
		return $protocol . '://' . $hostName . $pathInfo['dirname'] . "/" . 'wp-json/safe2pay/v2/callback/' . $orderId;
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
		//Get Version
		$woo = new WooCommerce();
		//PaymentMethod Code
		$paymentMethod = 0;
		//PaymentMethod Object
		$PaymentObject = null;

		//Produto do payload
		$Products = array(
			(object) array(
				'Code' => 1,
				'Description' => "Ordem #" . $order->get_id(),
				'Quantity' => 1,
				'UnitPrice' => $order->get_total()
			)
		);

		switch (strtoupper($method)) {
			case 'BOLETO':

				$paymentMethod  = "1";
				$PaymentObject = $this->gateway->GetBankSlipConfig();

				break;
			case 'CREDITCARD':
				$paymentMethod  = "2";

				$PaymentObject = array(
					'Holder' => sanitize_text_field($posted['safe2pay-card-holder-name']),
					'CardNumber' => sanitize_text_field($posted['safe2pay-card-number']),
					'ExpirationDate' => sanitize_text_field($posted['safe2pay-card-expiry-field']),
					'SecurityCode' => sanitize_text_field($posted['safe2pay-card-cvc']),
					'InstallmentQuantity' => sanitize_text_field($posted['safe2pay-card-installments'])
				);

				break;
			case 'CRYPTOCURRENCY':
				$paymentMethod  = "3";

				$PaymentObject = array(
					'Symbol' => sanitize_text_field($posted['safe2pay_currency-type']),
				);

				break;
			case 'DEBITCARD':
				$paymentMethod  = "4";

				$PaymentObject = array(
					'Holder' => sanitize_text_field($posted['safe2pay-debit-card-holder-name']),
					'CardNumber' => sanitize_text_field($posted['safe2pay-debit-card-number']),
					'ExpirationDate' => sanitize_text_field($posted['safe2pay-debit-card-expiry']),
					'SecurityCode' => sanitize_text_field($posted['safe2pay-debit-card-cvc'])
				);

				break;
			default:
				return array(
					'url'   => '',
					'data'  => '',
					'error' => 'Método de pagamento não selecionado',
				);
		};

		//Monta payload Safe2Pay
		$payload = array(
			'IsSandbox' => $IsSandbox,
			'Application' => 'Woocomerce ' . $woo->version,
			'PaymentMethod' => $paymentMethod,
			'PaymentObject' => $PaymentObject,
			'Reference' => $order->get_id(),
			'Products' => $Products,
			'Customer' => array(
				"Name" => sanitize_text_field($posted['billing_first_name'] . ' ' . $posted['billing_last_name']),
				"Identity" => sanitize_text_field(preg_replace("/[^0-9]/", "",  $posted['billing_cpf'])),
				"Phone" => sanitize_text_field($posted['billing_phone']),
				"Email" => sanitize_text_field($posted['billing_email']),
				"Address" => array(
					"Street" => sanitize_text_field($posted['billing_address_1']),
					"Number" =>   sanitize_text_field(isset($posted['billing_number']) ? $posted['billing_number'] : 'S/N'),
					"District" =>  sanitize_text_field(isset($posted['billing_neighborhood']) ? $posted['billing_neighborhood'] : 'Não informado'),
					"ZipCode" => sanitize_text_field($posted['billing_postcode']),
					"CityName" =>  sanitize_text_field($posted['billing_city']),
					"StateInitials" => sanitize_text_field($posted['billing_state']),
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
					'error' => array(__('Too bad! The email or token from the Safe2Pay are invalids my little friend!', 'woo-safe2pay')),
				);
			}
		} catch (Exception $e) {

			// Return error message.
			return array(
				'url'   => '',
				'token' => '',
				'error' => array('<strong>' . __('Safe2Pay', 'woo-safe2pay') . '</strong>: ' . __('An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woo-safe2pay')),
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
				'error' => array('<strong>' . __('Safe2Pay', 'woo-safe2pay') . '</strong>: ' .  __('Please, select a payment method.', 'woo-safe2pay')),
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
				$this->gateway->log->add($this->gateway->id, 'WP_Error in requesting the direct payment:');
			}
		} else if (401 === $response['response']['code']) {
			if ('yes' == $this->gateway->debug) {
				$this->gateway->log->add($this->gateway->id, 'The user does not have permissions to use the Safe2Pay Transparent Checkout!');
			}

			return array(
				'url'   => '',
				'data'  => '',
				'error' => array(__('You are not allowed to use the Safe2Pay Transparent Checkout. Looks like you neglected to installation guide of this plugin. This is not pretty, do you know?', 'woo-safe2pay')),
			);
		} else {
			try {

				$response = json_decode($response["body"]);


				if ($response->HasError == false) {
					if ($response->ResponseDetail->Status == 8) {
						return array(
							'url'   => '',
							'data'  => '',
							'error' => array('<strong>' . __('Safe2Pay', 'woo-safe2pay') . '</strong>: ' .  __($response->ResponseDetail->Message, 'woo-safe2pay')),
						);
					}


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
						'error' => array('<strong>' . __('Safe2Pay', 'woo-safe2pay') . '</strong>: ' .  __($response->Error, 'woo-safe2pay')),
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
