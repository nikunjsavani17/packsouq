<?php
 
/**
 * Plugin Name: Tni Shipping
 * Plugin URI: https://code.tutsplus.com/tutorials/create-a-custom-shipping-method-for-woocommerce--cms-26098
 * Description: Custom Shipping Method for WooCommerce
 * Version: 1.0.0
 * Author: Igor Benić
 * Author URI: http://www.ibenic.com
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /lang
 * Text Domain: tutsplus
 */
 
if ( ! defined( 'WPINC' ) ) { 
    die; 
} 
/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
    function tni_shipping_method() {
        if ( ! class_exists( 'Tni_Shipping_Method' ) ) {
            class Tni_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id                 = 'tni'; 
                    $this->method_title       = __( 'Tni Shipping', 'tni' );  
                    $this->method_description = __( 'Custom Shipping Method for Tni', 'tni' ); 
 
                    $this->init();
 
                    $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Tni Shipping', 'tni' );
                }
 
                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); 
                    $this->init_settings(); 
 
                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
				function init_form_fields() 
				{ 
					$this->form_fields = array(
 
					 'enabled' => array(
						  'title' => __( 'Enable', 'tni' ),
						  'type' => 'checkbox',
						  'description' => __( 'Enable this shipping.', 'tni' ),
						  'default' => 'yes'
						  ),
			 
					 'title' => array(
						'title' => __( 'Title', 'tni' ),
						  'type' => 'text',
						  'description' => __( 'Title to be display on site', 'tni' ),
						  'default' => __( 'Tni Shipping', 'tni' )
						  ),
			 
					 );
                }
				public function calculate_shipping( $package ) 
				{
					
					//print_r($package["destination"]);exit;
					
                    $country = $package["destination"]["country"];
					$state = $package["destination"]["state"];
					$cost = 50;
					if($country == "AE" && $state == "du")
					{
						$cost = 30;
						$this->title = "Below AED 1000, Charge, Aed 30 per delivery";
					}				
					 
					$rate = array(
                        'id' => $this->id,
                        'label' => $this->title ,
                        'cost' => $cost
                    );
 
                    $this->add_rate( $rate );
					
                }
            }
        }
    } 
    add_action( 'woocommerce_shipping_init', 'tni_shipping_method' ); 
    function add_tni_shipping_method( $methods ) {
        $methods[] = 'Tni_Shipping_Method';
        return $methods;
    } 
    add_filter( 'woocommerce_shipping_methods', 'add_tni_shipping_method' );
	
	/* shipping flate rate hide  */ 
	 function tni_hide_shipping_when_free_is_available( $rates ) {
		$free = array();
		foreach ( $rates as $rate_id => $rate ) 
		{		
			if ( 'free_shipping' === $rate->method_id ) {
				$free[ $rate_id ] = $rate;
				break;
			}
		}
		return ! empty( $free ) ? $free : $rates;
	}
	add_filter( 'woocommerce_package_rates', 'tni_hide_shipping_when_free_is_available', 100,2 );  

}


