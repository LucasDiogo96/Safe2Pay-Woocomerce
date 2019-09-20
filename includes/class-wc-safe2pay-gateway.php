<?php
/**
 * WooCommerce Safe2Pay Gateway class
 *
 * @package WooCommerce_Safe2Pay/Classes/Gateway
 * @version 2.13.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Safe2Pay gateway.
 */
class WC_Safe2Pay_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'safe2pay';
		$this->icon               = apply_filters( 'woocommerce_safe2pay_icon', plugins_url( 'assets/images/safe2pay.png', plugin_dir_path( __FILE__ ) ) );
		$this->method_title       = __( 'Safe2Pay', 'woocommerce-safe2pay' );
		$this->method_description = __( 'Accept payments by credit card, bank debit or banking ticket using the Safe2Pay.', 'woocommerce-safe2pay' );
		$this->order_button_text  = __( 'Finalizar', 'woocommerce-safe2pay' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title             = $this->get_option( 'title' );
		$this->description       = $this->get_option( 'description' );
		$this->secretkey             = $this->get_option( 'secretkey' );
		$this->token             = $this->get_option( 'token' );
		$this->sandbox_secretkey     = $this->get_option( 'sandbox_secretkey' );
		$this->sandbox_token     = $this->get_option( 'sandbox_token' );
		$this->method            = $this->get_option( 'method', 'direct' );
		$this->tc_credit         = $this->get_option( 'tc_credit', 'yes' );
		$this->tc_debit          = $this->get_option( 'tc_debit', 'yes' );
		$this->tc_ticket         = $this->get_option( 'tc_ticket', 'yes' );
		$this->tc_cryptocurrency = $this->get_option( 'tc_cryptocurrency', 'yes' );
		$this->invoice_prefix    = $this->get_option( 'invoice_prefix', 'WC-' );
		$this->sandbox           = $this->get_option( 'sandbox', 'no' );
		$this->debug             = $this->get_option( 'debug' );
		$this->duedate  =  $this->get_option( 'duedate' );

		// Active logs.
		if ( 'yes' === $this->debug ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				$this->log = wc_get_logger();
			} else {
				$this->log = new WC_Logger();
			}
		}

		// Set the API.
		$this->api = new WC_Safe2Pay_API( $this );
		
		add_action( 'valid_safe2pay_ipn_request', array( $this, 'update_order_status' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );

		// Transparent checkout actions.
		if ( 'transparent' === $this->method ) {
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
			add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
			add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );
		}
	}



	

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return 'BRL' === get_woocommerce_currency();
	}

	/**
	 * Get token.
	 *
	 * @return string
	 */
	public function GetAPIKEY() {
		return 'yes' === $this->sandbox ? $this->sandbox_token : $this->token;
	}

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @return bool
	 */
	public function IsAvailable() {
		// Test if is valid for use.
		$available = 'yes' === $this->get_option( 'enabled' ) && $this->using_supported_currency();
		return $available;
	}

	/**
	 * Checkout scripts.
	 */
	public function checkout_scripts() {
		if ($this->IsAvailable() ) {
			if ( ! get_query_var( 'order-received' ) ) {
;
					wp_enqueue_style( 'safe2pay-checkout', plugins_url( 'assets/css/frontend/transparent-checkout.css', plugin_dir_path( __FILE__ ) ), array(), WC_SAFE2PAY_VERSION );
					wp_enqueue_script( 'safe2pay-checkout', plugins_url( 'assets/js/frontend/transparent-checkout.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', 'safe2pay-library' ), WC_SAFE2PAY_VERSION, true );
					
					wp_enqueue_script( 'safe2pay-library', $this->api->get_direct_payment_url(), array(), WC_SAFE2PAY_VERSION, true );
					
				wp_localize_script(
					'safe2pay-checkout',
					'wc_safe2pay_params',
					array(
						'interest_free'      => __( 'interest free', 'woocommerce-safe2pay' ),
						'invalid_card'       => __( 'Invalid credit card number.', 'woocommerce-safe2pay' ),
						'invalid_expiry'     => __( 'Invalid expiry date, please use the MM / YYYY date format.', 'woocommerce-safe2pay' ),
						'expired_date'       => __( 'Please check the expiry date and use a valid format as MM / YYYY.', 'woocommerce-safe2pay' ),
						'general_error'      => __( 'Unable to process the data from your credit card on the Safe2Pay, please try again or contact us for assistance.', 'woocommerce-safe2pay' ),
						'empty_installments' => __( 'Select a number of installments.', 'woocommerce-safe2pay' ),
					)
				);
			}
		}
	}

	protected function get_log_view() {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '>=' ) ) {
			return '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', 'woocommerce-safe2pay' ) . '</a>';
		}

		return '<code>woocommerce/logs/' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.txt</code>';
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'              => array(
				'title'   => __( 'Ativar/Desativar', 'woocommerce-safe2pay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Ativar Safe2Pay', 'woocommerce-safe2pay' ),
				'default' => 'yes',
			),
			'title'                => array(
				'title'       => __( 'Título', 'woocommerce-safe2pay' ),
				'type'        => 'text',
				'description' => __( 'Título do método de pagamento', 'woocommerce-safe2pay' ),
				'desc_tip'    => true,
				'default'     => __( 'Safe2Pay', 'woocommerce-safe2pay' ),
			),
			'description'          => array(
				'title'       => __( 'Descrição do método de pagamento', 'woocommerce-safe2pay' ),
				'type'        => 'textarea',
				'description' => __( 'Descrição do método de pagamento durante o checkout.', 'woocommerce-safe2pay' ),
				'default'     => __( 'Pagar via Safe2Pay', 'woocommerce-safe2pay' ),
			),
			'integration'          => array(
				'title'       => __( 'Integração', 'woocommerce-safe2pay' ),
				'type'        => 'title',
				'description' => '',
			),
			'method'               => array(
				'title'       => __( 'Método de integração', 'woocommerce-safe2pay' ),
				'type'        => 'select',
				'description' => __( 'Choose how the customer will interact with the Safe2Pay. Redirect (Client goes to Safe2Pay page) or Lightbox (Inside your store)', 'woocommerce-safe2pay' ),
				'desc_tip'    => true,
				'default'     => 'direct',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'transparent' => __( 'Transparent Checkout', 'woocommerce-safe2pay' ),
				),
			),
			'sandbox'              => array(
				'title'       => __( 'Safe2Pay Sandbox', 'woocommerce-safe2pay' ),
				'type'        => 'checkbox',
				'label'       => __( 'Ativar/Desativar Safe2Pay Sandbox', 'woocommerce-safe2pay' ),
				'desc_tip'    => true,
				'default'     => 'no',
				'description' => __( 'Safe2Pay Sandbox pode ser utilizado para testes de pagamento.', 'woocommerce-safe2pay' ),
			),
			'token'                => array(
				'title'       => __( 'Safe2Pay Token', 'woocommerce-safe2pay' ),
				'type'        => 'text',
				/* translators: %s: link to Safe2Pay settings */
				'description' => sprintf( __( 'Insira seu Token aqui. Isso é necessário para processar os pagamentos.', 'woocommerce-safe2pay' ), '<a href="https://admin.safe2pay.com.br/integracao">' . __( 'here', 'woocommerce-safe2pay' ) . '</a>' ),
				'default'     => '',
			),
			'secretkey'                => array(
				'title'       => __( 'Safe2Pay SecretKey', 'woocommerce-safe2pay' ),
				'type'        => 'text',
				/* translators: %s: link to Safe2Pay settings */
				'description' => sprintf( __( 'Insira sua Secret Key aqui. Isso é necessário para receber notificações de mudanças de status do pagamento.', 'woocommerce-safe2pay' ), '<a href="https://admin.safe2pay.com.br/integracao">' . __( 'here', 'woocommerce-safe2pay' ) . '</a>' ),
				'default'     => '',
			),
			'sandbox_token'        => array(
				'title'       => __( 'Safe2Pay Sandbox Token', 'woocommerce-safe2pay' ),
				'type'        => 'text',
				/* translators: %s: link to Safe2Pay settings */
				'description' => sprintf( __( 'Insira seu Token de Sandbox aqui. Isso é necessário para processar os pagamentos em ambiente de teste.', 'woocommerce-safe2pay' ), '<a href=https://admin.safe2pay.com.br/integracao">' . __( 'here', 'woocommerce-safe2pay' ) . '</a>' ),
				'default'     => '',
			),
			'sandbox_secretkey'                => array(
				'title'       => __( 'Safe2Pay Sandbox SecretKey', 'woocommerce-safe2pay' ),
				'type'        => 'text',
				/* translators: %s: link to Safe2Pay settings */
				'description' => sprintf( __( 'Insira sua Secret Key de Sandbox aqui. Isso é necessário para receber notificações de mudança de status do pagamento em ambiente de teste.', 'woocommerce-safe2pay' ), '<a href="https://admin.safe2pay.com.br/integracao">' . __( 'here', 'woocommerce-safe2pay' ) . '</a>' ),
				'default'     => '',
			),
			'transparent_checkout' => array(
				'title'       => __( 'Opções de pagamento', 'woocommerce-safe2pay' ),
				'type'        => 'title',
				'description' => '',
			),
			'tc_ticket'            => array(
				'title'   => __( 'Boleto Bancário', 'woocommerce-safe2pay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Boleto Bancário', 'woocommerce-safe2pay' ),
				'default' => 'yes',
			),
			'tc_credit'            => array(
				'title'   => __( 'Cartão de Crédito', 'woocommerce-safe2pay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Cartão de crédito', 'woocommerce-safe2pay' ),
				'default' => 'yes',
			),	
			'tc_debit'            => array(
				'title'   => __( 'Cartão de Débito', 'woocommerce-safe2pay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Cartão de débito', 'woocommerce-safe2pay' ),
				'default' => 'yes',
			),
			'tc_cryptocurrency'            => array(
				'title'   => __( 'Criptomoedas', 'woocommerce-safe2pay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Criptomoeda', 'woocommerce-safe2pay' ),
				'default' => 'yes',
			),
			'behavior'             => array(
				'title'       => __( 'Integration Behavior', 'woocommerce-safe2pay' ),
				'type'        => 'title',
				'description' => '',
			),
			'invoice_prefix'       => array(
				'title'       => __( 'Invoice Prefix', 'woocommerce-safe2pay' ),
				'type'        => 'text',
				'description' => __( 'Please enter a prefix for your invoice numbers. If you use your Safe2Pay account for multiple stores ensure this prefix is unqiue as Safe2Pay will not allow orders with the same invoice number.', 'woocommerce-safe2pay' ),
				'desc_tip'    => true,
				'default'     => 'WC-',
			),
			'testing'              => array(
				'title'       => __( 'Gateway Testing', 'woocommerce-safe2pay' ),
				'type'        => 'title',
				'description' => '',
			),
			'debug'                => array(
				'title'       => __( 'Debug Log', 'woocommerce-safe2pay' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce-safe2pay' ),
				'default'     => 'no',
				/* translators: %s: log page link */
				'description' => sprintf( __( 'Log Safe2Pay events, such as API requests, inside %s', 'woocommerce-safe2pay' ), $this->get_log_view() ),
			),
		);
	}

	public function admin_options() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'safe2pay-admin', plugins_url( 'assets/js/admin/admin' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), WC_SAFE2PAY_VERSION, true );

		include dirname( __FILE__ ) . '/admin/views/html-admin-page.php';
	}

	protected function send_email( $subject, $title, $message ) {
		$mailer = WC()->mailer();
		$mailer->send( get_option( 'admin_email' ), $subject, $mailer->wrap_message( $title, $message ) );
	}

	public function payment_fields() {
		wp_enqueue_script( 'wc-credit-card-form' );
		wp_enqueue_script( 'wc-debit-card-form' );

		$description = $this->get_description();
		if ( $description ) {
			echo wpautop( wptexturize( $description ) ); // WPCS: XSS ok.
		}

		$cart_total = $this->get_order_total();

			wc_get_template(
				'transparent-checkout-form.php', array(
					'cart_total'        => $cart_total,
					'tc_credit'         => $this->tc_credit,
					'tc_ticket'         => $this->tc_ticket,
					'tc_debit'          => $this->tc_debit,
					'tc_cryptocurrency' => $this->tc_cryptocurrency,
					'flag'              => plugins_url( 'assets/images/brazilian-flag.png', plugin_dir_path( __FILE__ ) ),
				), 'woocommerce/safe2pay/', WC_Safe2Pay::get_templates_path()
			);
		
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( 'transparent' === $this->method ) {

			$response = $this->api->PaymentController( $order, $_POST ); // WPCS: input var ok, CSRF ok.

			if ( $response['data'] ) {
				$this->update_order_status($response['data'], $order_id );
			}

			if ( $response['url'] ) {
				// Remove cart.
				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $response['url'],
				);
			} else {
				foreach ( $response['error'] as $error ) {
					wc_add_notice( $error, 'error' );
				}

				return array(
					'result'   => 'fail',
					'redirect' => '',
				);
			}
		} else {
			$use_shipping = isset( $_POST['ship_to_different_address'] ) ? true : false; // WPCS: input var ok, CSRF ok.

			return array(
				'result'   => 'success',
				'redirect' => add_query_arg( array( 'use_shipping' => $use_shipping ), $order->get_checkout_payment_url( true ) ),
			);
		}
	}

	public function receipt_page( $order_id ) {
		$order        = wc_get_order( $order_id );
		$request_data = $_POST;  // WPCS: input var ok, CSRF ok.
		if ( isset( $_GET['use_shipping'] ) && true === (bool) $_GET['use_shipping'] ) {  // WPCS: input var ok, CSRF ok.
			$request_data['ship_to_different_address'] = true;
		}

		$response = $this->api->CheckoutController( $order, $request_data );

		if ( $response['url'] ) {
			// Lightbox script.
			wc_enqueue_js(
				'
				$( "#browser-has-javascript" ).show();
				$( "#browser-no-has-javascript, #cancel-payment, #submit-payment" ).hide();
				var isOpenLightbox = Safe2PayLightbox({
						code: "' . esc_js( $response['token'] ) . '"
					}, {
						success: function ( transactionCode ) {
							window.location.href = "' . str_replace( '&amp;', '&', esc_js( $this->get_return_url( $order ) ) ) . '";
						},
						abort: function () {
							window.location.href = "' . str_replace( '&amp;', '&', esc_js( $order->get_cancel_order_url() ) ) . '";
						}
				});
				if ( ! isOpenLightbox ) {
					window.location.href = "' . esc_js( $response['url'] ) . '";
				}
			'
			);

			wc_get_template(
				'lightbox-checkout.php', array(
					'cancel_order_url'    => $order->get_cancel_order_url(),
					'payment_url'         => $response['url'],
					'lightbox_script_url' =>'',
				), 'woocommerce/safe2pay/', WC_Safe2Pay::get_templates_path()
			);
		} else {
			include dirname( __FILE__ ) . '/views/html-receipt-page-error.php';
		}
	}

	protected function SavePaymentData( $order, $posted) {
		$meta_data    = array();
		$payment_data = array(
			'type'         => '',
			'method'       => '',
			'installments' => '',
			'link'         => '',
			'WalletAddress'=> '',
		);

		if ($order->Data['billing']['email'] != null) {
			$meta_data[ __( 'Payer email', 'woocommerce-safe2pay' ) ] = sanitize_text_field($order->Data['billing']['email']);
		}
		if ($order->Data['billing']['first_name'] != null) {
			$meta_data[ __( 'Payer name', 'woocommerce-safe2pay' ) ] = sanitize_text_field( $order->Data['billing']['first_name'].' '.$order->Data['billing']['last_name'] );
		}

		$method = strtoupper($_POST['safe2pay_payment_method']);
		
		if (isset($method)) { 
			
			if(strtoupper($_POST['safe2pay_payment_method']) === "BANKING-TICKET"){
				
				if ($posted->BankSlipUrl != null) { 
					$payment_data['type'] = '1';
					$payment_data['link'] = sanitize_text_field( (string) $posted->BankSlipUrl );
					$meta_data[ __( 'Payment URL', 'woocommerce-safe2pay' ) ] = $payment_data['link'];
				}
			}else if(strtoupper($_POST['safe2pay_payment_method']) === "CREDIT-CARD"){

				$payment_data['type'] = '2';
			
				$payment_data['installments'] = $_POST['safe2pay_card_installments'];
			}
			else if(strtoupper($_POST['safe2pay_payment_method']) === "CRYPTO-CURRENCY"){

				$payment_data['type'] = '3';
			
				$payment_data['link'] = sanitize_text_field( (string)  $posted->QrCode );
				$meta_data[ __( 'Payment URL', 'woocommerce-safe2pay' ) ] = $payment_data['link'];

				$payment_data['walletaddress'] = sanitize_text_field( (string)  $posted->WalletAddress );
				$meta_data[ __( 'WalletAddress', 'woocommerce-safe2pay' ) ] = $payment_data['walletaddress'];
			}
			if(strtoupper($_POST['safe2pay_payment_method']) === "DEBIT-CARD"){

				$payment_data['type'] = '4';
			
				// $payment_data['link'] = sanitize_text_field( (string)  $posted->QrCode );
				// $meta_data[ __( 'Payment URL', 'woocommerce-safe2pay' ) ] = $payment_data['link'];
			}
		}


		$meta_data['_wc_safe2pay_payment_data'] = $payment_data;

		// WooCommerce 3.0 or later.
		if ( method_exists( $order, 'update_meta_data' ) ) {
			foreach ( $meta_data as $key => $value ) {
				$order->update_meta_data( $key, $value );
			}
			$order->save();
		} else {
			foreach ( $meta_data as $key => $value ) {
				update_post_meta( $order->id, $key, $value );
			}
		}
	}

	public function update_order_status( $posted, $order_id  ) {
		if ( isset($posted->IdTransaction) ) {
			$id    = (int) str_replace( $this->invoice_prefix, '', $posted->IdTransaction);
			$order = wc_get_order( $order_id );

			// Check if order exists.
			if ( ! $order ) {
				return;
			}

			$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

			// Checks whether the invoice number matches the order.
			// If true processes the payment.
			if ($id  > 0) {
				if ( 'yes' === $this->debug ) {
					$this->log->add( $this->id, 'Safe2Pay payment status for order ' . $order->get_order_number() . ' is: ' . intval( $posted->Status ) );
				}

				// Save meta data.
				$this->SavePaymentData( $order, $posted );

				switch ($posted->Status) {
					case "1":
						$order->update_status( 'pending', __( 'Safe2Pay: Pendente.', 'woocommerce-safe2pay' ) );

						break;
					case "2":
						$order->update_status( 'processing', __( 'Safe2Pay: Processamento.', 'woocommerce-safe2pay' ) );

						// Reduce stock for billets.
						if ( function_exists( 'wc_reduce_stock_levels' ) ) {
							wc_reduce_stock_levels( $order_id );
						}

						break;
					case "3":
						// Sometimes Safe2Pay should change an order from cancelled to paid, so we need to handle it.
						if ( method_exists( $order, 'get_status' ) && 'cancelled' === $order->get_status() ) {
							$order->update_status( 'on-hold', __( 'Safe2Pay: Payment approved.', 'woocommerce-safe2pay' ) );
							wc_reduce_stock_levels( $order_id );
						} else {
							$order->add_order_note( __( 'Safe2Pay: Autorizado.', 'woocommerce-safe2pay' ) );

							// Changing the order for processing and reduces the stock.
							$order->payment_complete( sanitize_text_field( (string) $posted->IdTransaction ) );
						}

						break;
					case "5":
						$order->update_status( 'processing', __( 'Safe2Pay: Em disputa.', 'woocommerce-safe2pay' ) );
						$this->send_email(
							/* translators: %s: order number */
							sprintf( __( 'Payment for order %s came into dispute', 'woocommerce-safe2pay' ), $order->get_order_number() ),
							__( 'Payment in dispute', 'woocommerce-safe2pay' ),
							/* translators: %s: order number */
							sprintf( __( 'Order %s has been marked as on-hold, because the payment came into dispute in Safe2Pay.', 'woocommerce-safe2pay' ), $order->get_order_number() )
						);

						break;
					case "6":
						$order->update_status( 'refunded', __( 'Safe2Pay: Devolvido.', 'woocommerce-safe2pay' ) );
						$this->send_email(
							/* translators: %s: order number */
							sprintf( __( 'Payment for order %s refunded', 'woocommerce-safe2pay' ), $order->get_order_number() ),
							__( 'Payment refunded', 'woocommerce-safe2pay' ),
							/* translators: %s: order number */
							sprintf( __( 'Order %s has been marked as refunded by Safe2Pay.', 'woocommerce-safe2pay' ), $order->get_order_number() )
						);

						if ( function_exists( 'wc_increase_stock_levels' ) ) {
							wc_increase_stock_levels( $order_id );
						}

						break;
					case "12":
						$order->update_status( 'cancelled', __( 'Safe2Pay: Em cancelamento.', 'woocommerce-safe2pay' ) );

						if ( function_exists( 'wc_increase_stock_levels' ) ) {
							wc_increase_stock_levels( $order_id );
						}

						break;

					default:
						break;
				}
			} else {
				if ( 'yes' === $this->debug ) {
					$this->log->add( $this->id, 'Error: Order Key does not match with Safe2Pay reference.' );
				}
			}
		}
	}

	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		// WooCommerce 3.0 or later.
		if ( method_exists( $order, 'get_meta' ) ) {
			$data = $order->get_meta( '_wc_safe2pay_payment_data' );
		} else {
			$data = get_post_meta( $order->id, '_wc_safe2pay_payment_data', true );
		}

		if ( isset( $data['type'] ) ) {
			wc_get_template(
				'payment-instructions.php', array(
					'type'         => $data['type'],
					'link'         => $data['link'],
					'method'       => $data['method'],
					'walletaddress'         => isset($data['walletaddress']) ? $data['walletaddress'] : "",
					'installments' => $data['installments'],
				), 'woocommerce/safe2pay/', WC_Safe2Pay::get_templates_path()
			);
		}
	}

	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		// WooCommerce 3.0 or later.
		if ( method_exists( $order, 'get_meta' ) ) {
			if ( $sent_to_admin || 'on-hold' !== $order->get_status() || $this->id !== $order->get_payment_method() ) {
				return;
			}

			$data = $order->get_meta( '_wc_safe2pay_payment_data' );
		} else {
			if ( $sent_to_admin || 'on-hold' !== $order->status || $this->id !== $order->payment_method ) {
				return;
			}

			$data = get_post_meta( $order->id, '_wc_safe2pay_payment_data', true );
		}

		if ( isset( $data['type'] ) ) {
			if ( $plain_text ) {
				wc_get_template(
					'emails/plain-instructions.php', array(
						'type'         => $data['type'],
						'link'         => $data['link'],
						'walletaddress'         => isset($data['walletaddress']) ? $data['walletaddress'] : "",
						'method'       => $data['method'],
						'installments' => $data['installments'],
					), 'woocommerce/safe2pay/', WC_Safe2Pay::get_templates_path()
				);
			} else {
				wc_get_template(
					'emails/html-instructions.php', array(
						'type'         => $data['type'],
						'link'         => $data['link'],
						'walletaddress'=> isset($data['walletaddress']) ? $data['walletaddress'] : "",
						'method'       => $data['method'],
						'installments' => $data['installments'],
					), 'woocommerce/safe2pay/', WC_Safe2Pay::get_templates_path()
				);
			}
		}
	}
}
