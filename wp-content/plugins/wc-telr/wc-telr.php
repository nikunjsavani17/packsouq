<?php
/*
 * Plugin Name: Telr Secure Payments for woocommerce
 * Plugin URI: https://www.telr.com/
 * Description: Telr Hosted Payment Pages for WooCommerce
 * Version: 1.0
 * Author: Telr
 * Author URI: https://www.telr.com/
 * License: GPL2
 * WC requires at least: 3.0.0
 * WC tested up to: 3.2.0
*/

//directory access forbidden
if (!defined('ABSPATH')) {
    exit;
}

function wc_gateway_telr()
{
    static $plugin;

    if (!isset($plugin)) {
        require_once('includes/class-wc-gateway-telr-plugin.php');

        $plugin = new WC_Gateway_Telr_Plugin(__FILE__);
    }

    return $plugin;
}

wc_gateway_telr()->maybe_run();
