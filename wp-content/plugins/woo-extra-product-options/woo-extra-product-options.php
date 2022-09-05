<?php
/**
 * Plugin Name: Extra Product Options (Product Addons) for WooCommerce
 * Description: Add extra product options in product page.
 * Author:      ThemeHiGH
 * Version:     1.3.3
 * Author URI:  https://www.themehigh.com
 * Plugin URI:  https://www.themehigh.com
 * Text Domain: woo-extra-product-options
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 3.5.0
 */
 
if(!defined('ABSPATH')){ exit; }

if (!function_exists('is_woocommerce_active')){
	function is_woocommerce_active(){
	    $active_plugins = (array) get_option('active_plugins', array());
	    if(is_multisite()){
		   $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
	    }
	    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins) || class_exists('WooCommerce');
	}
}

if(is_woocommerce_active()) {
	if(!class_exists('WEPOF_Extra_Product_Options')){	
		class WEPOF_Extra_Product_Options {	
			public function __construct(){
				add_action('init', array($this, 'init'));
			}		

			public function init() {		
				$this->load_plugin_textdomain();
				
				define('TH_WEPOF_VERSION', '1.3.3');
				!defined('TH_WEPOF_BASE_NAME') && define('TH_WEPOF_BASE_NAME', plugin_basename( __FILE__ ));
				!defined('TH_WEPOF_PATH') && define('TH_WEPOF_PATH', plugin_dir_path( __FILE__ ));
				!defined('TH_WEPOF_URL') && define('TH_WEPOF_URL', plugins_url( '/', __FILE__ ));
				!defined('TH_WEPOF_ASSETS_URL') && define('TH_WEPOF_ASSETS_URL', TH_WEPOF_URL .'assets/');
				
				require_once( TH_WEPOF_PATH . 'classes/class-wepof-settings.php' );

				WEPOF_Settings::instance();					
			}

			public function load_plugin_textdomain(){							
				load_plugin_textdomain('woo-extra-product-options', FALSE, dirname(plugin_basename( __FILE__ )) . '/languages/');
			}
		}	
	}
	new WEPOF_Extra_Product_Options();
}