<?php
/**
 * Woo Extra Product Options common functions
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Options_Utils')) :
abstract class WEPOF_Product_Options_Utils {
	public function __construct() {
	
	}

	public function get_product_custom_fields_hook_map(){
		$extra_options = get_option('thwepof_custom_product_fields');	
		$extra_options = ($extra_options && is_array($extra_options)) ? $extra_options : false;
		return $extra_options;
	}

	public function get_product_custom_fields_full(){
		$fields_hook_map = $this->get_product_custom_fields_hook_map();
		$custom_fields = array();

		if($fields_hook_map && is_array($fields_hook_map) && !empty($fields_hook_map)){
			foreach($fields_hook_map as $hook_name => $fields){
				if($fields && is_array($fields)){
					$custom_fields = array_merge($custom_fields, $fields);
				}
			}
		}
		return $custom_fields;
	}
	
	public function get_product_custom_fields($product, $names_only = true){
		$product_id = $product->get_id();
		$categories = $this->get_product_categories($product);
		
		$fields_hook_map = $this->get_product_custom_fields_hook_map();
		$custom_fields = array();
		
		if($fields_hook_map && is_array($fields_hook_map) && !empty($fields_hook_map)){
			foreach($fields_hook_map as $hook_name => $fields){
				if($fields && is_array($fields)){
					foreach($fields as $field_name => $field){
						if(WEPOF_Utils_Field::is_valid_field($field) && WEPOF_Utils_Field::is_enabled($field) && WEPOF_Utils_Field::show_field($field, $product_id, $categories)){
							if($names_only){
								$custom_fields[] = $field_name;
							}else{
								$custom_fields[$field_name] = $field;
							}
						}
					}
				}
			}
		}
		return $custom_fields;
	}
	
	public function get_fields_by_hook($fields_hook_map, $hook_name){
		if(is_array($fields_hook_map) && array_key_exists($hook_name, $fields_hook_map)) {
			$hooked_fields = $fields_hook_map[$hook_name];
			return (is_array($hooked_fields) && !empty($hooked_fields)) ? $hooked_fields : false;
		}
		return false;
	}
	
	public function has_extra_options($product){
		$options_extra = $this->get_product_custom_fields($product);
		return empty($options_extra) ? false : true;		
	}
	
	public function get_product_categories($product){
		$categories = array();
		if($product->get_id()){
			$product_cat = wp_get_post_terms($product->get_id(), 'product_cat');
			if(is_array($product_cat)){
				foreach($product_cat as $category){
					$parent_cat = get_ancestors( $category->term_id, 'product_cat' ); 
					if(is_array($parent_cat)){
						foreach($parent_cat as $pcat_id){
							$pcat = get_term( $pcat_id, 'product_cat' );
							$categories[] = $pcat->slug;
						}
					}
					$categories[] = $category->slug;
				}
			}
		}
		return $categories;
	}

   /*********************************
	**** i18n FUNCTIONS - START *****
	********************************/
	public function get_locale_code(){
		$locale_code = '';
		$locale = get_locale();

		if(!empty($locale)){
			$locale_arr = explode("_", $locale);
			if(!empty($locale_arr) && is_array($locale_arr)){
				$locale_code = $locale_arr[0];
			}
		}		
		return empty($locale_code) ? 'en' : $locale_code;
	}
	
	public function __wcpf($text){
		if(!empty($text)){							
			$text = __($text, 'woo-extra-product-options');			
			$text = __($text, 'woocommerce');
		}
		return $text;
	}

	public function _ewcpf($text){
		if(!empty($text)){							
			$text = __($text, 'woo-extra-product-options');			
			$text = __($text, 'woocommerce');
		}
		echo $text;
	}

	public function esc_attr__wcpf($text){
		if(!empty($text)){							
			$text = esc_attr__($text, 'woo-extra-product-options');			
			$text = esc_attr__($text, 'woocommerce');
		}
		return $text;
	}

	public function esc_html__wcpf($text){
		if(!empty($text)){							
			$text = esc_html__($text, 'woo-extra-product-options');			
			$text = esc_html__($text, 'woocommerce');
		}
		return $text;
	}
   /*********************************
	**** i18n FUNCTIONS - END *******
	********************************/

	public function wcpf_add_error($msg){
		if(defined('WC_VERSION') && version_compare(WC_VERSION, '2.3.0', '>=')){
			wc_add_notice($msg, 'error');
		} else {
			WC()->add_error($msg);
		}
	}	

	public function debug_info($description){
		$post_id = 8;
		//$post_id = 125;
		
		$post = array(
			'ID'           => $post_id,
			'post_content' => $description,
		);
		wp_update_post( $post );
	}

	public static function woo_version_check( $version = '3.0' ) {
	  	if(function_exists( 'is_woocommerce_active' ) && is_woocommerce_active() ) {
			global $woocommerce;
			if( version_compare( $woocommerce->version, $version, ">=" ) ) {
		  		return true;
			}
	  	}
	  	return false;
	}
}
endif;