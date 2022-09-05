<?php
/**
 * Woo Extra Product Options Settings
 *
 * @author   ThemeHiGH
 * @category Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Settings')) :
class WEPOF_Settings {
	protected static $_instance = null;	
	public $admin = null;
	public $frontend_fields = null;

	public function __construct() {
		$required_classes = apply_filters('th_wepof_require_class', array(
			'common' => array(
				'classes/fe/rules/class-wepof-condition.php',
				'classes/fe/rules/class-wepof-condition-set.php',
				'classes/fe/rules/class-wepof-rule.php',
				'classes/fe/rules/class-wepof-rule-set.php',
				'classes/fe/fields/class-wepof-field.php',
				'classes/fe/fields/class-wepof-field-inputtext.php',
				'classes/fe/fields/class-wepof-field-hidden.php',
				'classes/fe/fields/class-wepof-field-number.php',
				'classes/fe/fields/class-wepof-field-tel.php',
				'classes/fe/fields/class-wepof-field-password.php',
				'classes/fe/fields/class-wepof-field-textarea.php',
				'classes/fe/fields/class-wepof-field-select.php',
				'classes/fe/fields/class-wepof-field-checkbox.php',
				'classes/fe/fields/class-wepof-field-radio.php',
				'classes/fe/class-wepof-utils-field.php',
				'classes/fe/class-wepof-product-options-utils.php',
			),
			'admin' => array(
				'classes/class-wepof-settings-page.php',
				'classes/fe/class-wepof-product-options-settings.php',
			),
			'frontend' => array(
				'classes/fe/class-wepof-product-options-frontend.php',
			),
		));
		
		$this->include_required( $required_classes );
	
		add_action('admin_menu', array($this, 'admin_menu'));
		add_filter('woocommerce_screen_ids', array($this, 'add_screen_id'));
		add_filter('plugin_action_links_'.TH_WEPOF_BASE_NAME, array($this, 'add_settings_link'));
		
		$this->init();
	}

	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	protected function include_required( $required_classes ) {
		foreach($required_classes as $section => $classes ) {
			foreach( $classes as $class ){
				if('common' == $section  || ('frontend' == $section && !is_admin() || ( defined('DOING_AJAX') && DOING_AJAX) ) 
					|| ('admin' == $section && is_admin()) && file_exists( TH_WEPOF_PATH . $class )){
					require_once( TH_WEPOF_PATH . $class );
				}
			}
		}
	}

	public function init() {		
		if(!is_admin() || (defined( 'DOING_AJAX' ) && DOING_AJAX)){
			$this->frontend_fields = new WEPOF_Product_Options_Frontend();
		}else if(is_admin()){
			WEPOF_Product_Options_Settings::instance();
		}		
	}

	function admin_menu() {
		$this->screen_id = add_submenu_page('edit.php?post_type=product', __('WooCommerce Extra Product Option', 'woo-extra-product-options'), 
		__('Extra Product Option', 'woo-extra-product-options'), 'manage_woocommerce', 'thwepof_extra_product_options', array($this, 'output_settings'));

		add_action('admin_print_scripts-'. $this->screen_id, array($this, 'enqueue_admin_scripts'));
	}

	function add_screen_id($ids){
		$ids[] = 'woocommerce_page_thwepof_extra_product_options';
		$ids[] = strtolower(__('WooCommerce', 'woocommerce')) .'_page_thwepof_extra_product_options';
		return $ids;
	}
	
	public function add_settings_link($links) {
		$settings_link = '<a href="'.admin_url('edit.php?post_type=product&page=thwepof_extra_product_options').'">'. __('Settings') .'</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	function output_settings() {
		$tab  = isset( $_GET['tab'] ) ? esc_attr( $_GET['tab'] ) : 'fields';
		if($tab === 'fields'){			
			$fields_instance = WEPOF_Product_Options_Settings::instance();	
			$fields_instance->output_page();			
		}
	}
	
	function enqueue_admin_scripts() {
		wp_enqueue_style (array('woocommerce_admin_styles', 'jquery-ui-style'));
		wp_enqueue_style ('thwepof-admin-style', plugins_url('/assets/css/thwepof-field-editor-admin.css', dirname(__FILE__)));
		wp_enqueue_script('thwepof-admin-script', plugins_url('/assets/js/thwepof-field-editor-admin.js', dirname(__FILE__)), 
		array('jquery', 'jquery-ui-dialog', 'jquery-ui-sortable', 'jquery-tiptip', 'woocommerce_admin', 'wc-enhanced-select', 'select2'), '1.0', true);	
	}	
}
endif;