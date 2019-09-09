<?php
/**
 * Plugin Name:          WooCommerce Safe2Pay
 * Plugin URI:           https://github.com/LucasDiogo96/Woocomerce-Safe2Pay
 * Description:          Includes Safe2Pay as a payment gateway to WooCommerce.
 * Author:               Lucas Diogo da Silva
 * Version:              2.13.1
 * License:              GPLv3 or later
 * Text Domain:          woocommerce-safe2pay
 * Domain Path:          /languages
 * WC requires at least: 3.0.0
 * WC tested up to:      3.5.0

 * @package WooCommerce_Safe2Pay
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'WC_SAFE2PAY_VERSION', '2.13.1' );
define( 'WC_SAFE2PAY_PLUGIN_FILE', __FILE__ );

if ( ! class_exists( 'WC_Safe2Pay' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-wc-safe2pay.php';
	add_action( 'plugins_loaded', array( 'WC_Safe2Pay', 'init' ) );
}
