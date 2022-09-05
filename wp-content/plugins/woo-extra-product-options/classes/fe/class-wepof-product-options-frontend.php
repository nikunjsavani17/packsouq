<?php

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Options_Frontend')):
class WEPOF_Product_Options_Frontend extends WEPOF_Product_Options_Utils {
	public $options_extra = array();
	
	public function __construct(){
		//if( !session_id()) session_start();
		
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_filter('woocommerce_loop_add_to_cart_link', array($this, 'woo_loop_add_to_cart_link'), 10, 3);
		add_action('woocommerce_before_single_product', array($this, 'woo_before_single_product') );	
			
		add_action('woocommerce_before_add_to_cart_button', array($this, 'woo_before_add_to_cart_button'), 10);
		add_action('woocommerce_after_add_to_cart_button', array($this, 'woo_after_add_to_cart_button'), 10);	
			
		add_filter('woocommerce_add_to_cart_validation', array($this, 'woo_add_to_cart_validation'), 99, 6 );
		add_filter('woocommerce_add_cart_item_data', array($this, 'woo_add_cart_item_data'), 10, 3 );
		add_filter('woocommerce_get_item_data', array($this, 'woo_get_item_data'), 10, 2 );

		if(self::woo_version_check()){
			add_action('woocommerce_new_order_item', array($this, 'woo_new_order_item'), 10, 3);
		}else{
			add_action('woocommerce_add_order_item_meta', array($this, 'woo_add_order_item_meta'), 1, 3);
		}
		
		add_filter('woocommerce_order_item_get_formatted_meta_data', array($this, 'woo_order_item_get_formatted_meta_data'), 10, 2);						/* add_action( 'woocommerce_before_calculate_totals', array( $this, 'wk_update_price' ) );  */		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'df_add_ticket_surcharge' ), 10, 2 );  
	}

	public function enqueue_scripts(){			
		global $wp_scripts;
		if(is_product()){
			wp_enqueue_style('thwepof-frontend-style', TH_WEPOF_ASSETS_URL.'css/thwepof-field-editor-frontend.css', TH_WEPOF_VERSION);
		}
	}
   
   /***************************************************
	**** PREPARE CUSTOM SECTIONS & OPTIONS - START ****
	***************************************************/
	public function woo_loop_add_to_cart_link($link, $product, $args = array()){
		$modify = apply_filters('thwepof_modify_loop_add_to_cart_link', true);

		$product_type = false;
		if(self::woo_version_check()){
			$product_type = $product->get_type();
		}else{
			$product_type = $product->product_type;
		}

		if($modify && $this->has_extra_options($product) && $product->is_in_stock() && ($product_type === 'simple' || $product_type === 'bundle')){
			$link_text = apply_filters('thwepof_loop_add_to_cart_link_text', 'Select options');

			$product_id = false;
			$product_sku = false;
    		if(self::woo_version_check()){
    			$product_id = $product->get_id();
    			$product_sku = $product->get_sku();
    		}else{
    			$product_id = $product->id;
    			$product_sku = $product->sku;
    		}
			
			if(self::woo_version_check('3.3')){
				if(isset($args['class'])){
					$args['class'] = str_replace("ajax_add_to_cart", "", $args['class']);
				}

				$link = sprintf( '<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
					esc_url( $product->get_permalink() ),
					esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
					esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
					isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
					esc_html( $this->__wcpf($link_text) )
				);
			}else{
				$link = sprintf( '<a rel="nofollow" href="%s" data-quantity="%s" data-product_id="%s" data-product_sku="%s" class="%s">%s</a>',
					esc_url( $product->get_permalink() ),
					esc_attr( isset( $quantity ) ? $quantity : 1 ),
					esc_attr( $product_id ),
					esc_attr( $product_sku ),
					esc_attr( isset( $class ) ? $class : 'button' ),
					esc_html( $this->__wcpf($link_text) )
				);
			}
		}
		return $link;
	}
	
	public function woo_before_single_product(){
		global $product;
		$product_id = $product->get_id();
		$categories = $this->get_product_categories($product);
					
		$fields_hook_map = $this->get_product_custom_fields_hook_map();
		
		if($fields_hook_map && is_array($fields_hook_map) && !empty($fields_hook_map)){
			foreach($fields_hook_map as $hook_name => $fields){
				$custom_fields = array();
				if($fields && is_array($fields)){
					foreach($fields as $field_name => $field){
						if(WEPOF_Utils_Field::is_valid_field($field) && WEPOF_Utils_Field::is_enabled($field) && WEPOF_Utils_Field::show_field($field, $product_id, $categories)){
							$custom_fields[$field_name] = $field;
						}
					}
				}
				
				if(!empty($custom_fields)){
					$fields_hook_map[$hook_name] = $custom_fields;
				}else{
					unset($fields_hook_map[$hook_name]);
				}
			}
		}
		$this->options_extra = $fields_hook_map;
	}
	
	private function prepare_product_options($names_only = true){
		$final_fields = array();
		$product_fields  = isset( $_POST['thwepof_product_fields'] ) ? wc_clean( $_POST['thwepof_product_fields'] ) : '';
		$prod_fields = $product_fields ? explode(",", $product_fields) : array();
		
		if($names_only){
			$final_fields = $prod_fields;
		}else{
			$extra_options = $this->get_product_custom_fields_full();
			foreach($prod_fields as $name) {
				if(isset($extra_options[$name])){
					$final_fields[$name] = $extra_options[$name];
				}
			}
		}
		return $final_fields;
	}
   /***************************************************
	**** PREPARE CUSTOM SECTIONS & OPTIONS - END ******
	***************************************************/
	
   /***********************************************
	**** DISPLAY CUSTOM PRODUCT FIELDS - START ****
	***********************************************/	
	private function render_fields($hook_name){	
		$fields = $this->get_fields_by_hook($this->options_extra, $hook_name);
		if($fields){						
			foreach($fields as $name => $field){
				WEPOF_Utils_Field::render_field($field);
			}
		}
	}
	
	private function render_product_field_names_hidden_field(){
		global $product;
		$prod_field_names = $this->get_product_custom_fields($product, true);
		$prod_field_names = is_array($prod_field_names) ? implode(",", $prod_field_names) : '';
		
		echo '<input type="hidden" id="thwepof_product_fields" name="thwepof_product_fields" value="'.$prod_field_names.'"/>';
	}

	public function woo_before_add_to_cart_button(){
		$this->render_product_field_names_hidden_field();
		$this->render_fields('woo_before_add_to_cart_button');
	}

	public function woo_after_add_to_cart_button(){
		$this->render_fields('woo_after_add_to_cart_button');
	}
   /***********************************************
	**** DISPLAY CUSTOM PRODUCT FIELDS - END ******
	***********************************************/
	

   /***************************************************
	**** CUSTOM PRODUCT OPTIONS VALIDATION - START ****
	***************************************************/
	public function woo_add_to_cart_validation($passed, $product_id, $quantity, $variation_id=false, $variations=false, $cart_item_data=false) { 
		$extra_options = $this->prepare_product_options(false);
		if($extra_options){
			foreach($extra_options as $field_name => $field){
				$value  = $this->get_posted_value($field_name);
				$passed = $this->validate_field($passed, $field, $value);
			}
		}
		return $passed;
	}
	
	private function get_posted_value($name){
		$is_posted = isset($_POST[$name]) || isset($_REQUEST[$name]) ? true : false;
		$value = false;
		
		if($is_posted){
			$value = isset($_POST[$name]) && $_POST[$name] ? $_POST[$name] : false;
			$value = empty($value) && isset($_REQUEST[$name]) ? $_REQUEST[$name] : $value;
		}
		return $value;
	}

	private function validate_field($valid, $field, $value){		
		if($field->is_required() && empty($value)) {
			$this->wcpf_add_error($this->__wcpf( 'Please enter a value for '.$field->get_property('title') ));
			$valid = false;
		}else{
			$validators = $field->get_property('validator');
			$validators = !empty($validators) ? explode("|", $validators) : false;

			if($validators && !empty($value)){
				foreach($validators as $validator){
					switch($validator) {
						case 'number' :
							if(!is_numeric($value)){
								$this->wcpf_add_error('<strong>'.$field->get_property('title').'</strong> '. sprintf($this->__wcpf('(%s) is not a valid number.'), $value));
								$valid = false;
							}
							break;

						case 'email' :
							if(!is_email($value)){
								$this->wcpf_add_error('<strong>'.$field->get_property('title').'</strong> '. sprintf($this->__wcpf('(%s) is not a valid email address.'), $value));
								$valid = false;
							}
							break;
					}
				}
			}
		}
		return $valid;
	}
	/************************************************
	**** CUSTOM PRODUCT OPTIONS VALIDATION - END ****
	*************************************************/
		

   /*********************************************************
	**** ADD CUSTOM OPTIONS & PRICE to CART ITEM - START ****
	*********************************************************/
	private function prepare_extra_cart_item_data(){
		$extra_data = array();
		$extra_options = $this->prepare_product_options(false);
		
		if($extra_options && is_array($extra_options)){
			foreach($extra_options as $name => $field){
				$posted_value = $this->get_posted_value($name);
				if($posted_value) {
					$data_arr = array();
					$data_arr['name']  			= $name;
					$data_arr['value'] 		 	= $posted_value;
					$data_arr['label'] 		 	= $field->get_property('title');
					$data_arr['options']        = $field->get_property('options');
					
					$extra_data[$name] = $data_arr;
				}
			}
		}
		return $extra_data;
	}			
	function df_add_ticket_surcharge( $cart_object )	
	{		
		global $woocommerce;
		$spfee = 0;
 		foreach ( $cart_object->cart_contents as $key => $value ) 
		{
			$proid = $value['product_id'];
 			$quantiy = $value['quantity']; 
			$itmprice = $value['data']->price; 		
			if( isset( $value["thwepof_options"] ) ) 
			{
				foreach ( $value['thwepof_options'] as $svalue ) 
				{
					$additionalPrice = $svalue['value'];
					$spfee = $spfee + $additionalPrice;
				}			  
			}		
		}	
		if($spfee > 0 ) 
		{
			$woocommerce->cart->add_fee( 'Civil Defense Supply certificate required?', $spfee, true);		
		}
	}
	
	
	
	
	public function wk_update_price( $cart_object ) {      $cart_items = $cart_object->cart_contents;	  if ( ! empty( $cart_items ) ) {        $additionalPrice = 100;         /* foreach ( $cart_items as $key => $value ) {			foreach ( $value['thwepof_options'] as $svalue ) {				$value['data']->set_price(  $value['price'] + $svalue['value'] );				}	        } */				foreach ( WC()->cart->get_cart() as $key => $value ) {            if( isset( $value["thwepof_options"] ) ) {          				foreach ( $value['thwepof_options'] as $svalue ) {								if( method_exists( $value['data'], "set_price" ) ) {											/* Woocommerce 3.0 + */						$orgPrice = floatval( $value['data']->get_price() );						$additionalPrice = $svalue['value'];						$value['data']->set_price( $orgPrice + $additionalPrice );					} else {							/* Version before 3.0 */						$orgPrice = floatval( $value['data']->price );						$additionalPrice = $svalue['value'];						$value['data']->price = ( $orgPrice + $additionalPrice );                    					}   				}			            }        }					/* 	global $woocommerce;		$woocommerce->cart->add_fee( 'Additional Charge', $additionalPrice, true, '' );		 */						      }	}
	public function woo_add_cart_item_data($cart_item_data, $product_id = 0, $variation_id = 0) {
		$skip_bundled_items = (isset($cart_item_data['bundled_by']) && apply_filters('thwepof_skip_extra_options_for_bundled_items', true)) ? true : false;
				
		if(!$skip_bundled_items){
			$extra_cart_item_data = $this->prepare_extra_cart_item_data();
			
			if($extra_cart_item_data){
				if(apply_filters('thwepof_set_unique_key_for_cart_item', false, $cart_item_data, $product_id, $variation_id)){
					$cart_item_data['unique_key'] = md5( microtime().rand() );
				}
				//$cart_item_data['unique_key'] = md5( microtime().rand() );
				$cart_item_data['thwepof_options'] = $extra_cart_item_data;
			}
		}
		return $cart_item_data;
	}
	
	public function woo_get_item_data($item_data, $cart_item = null) {
		$item_data = is_array($item_data) ? $item_data : array();		
		$extra_options = $cart_item && isset($cart_item['thwepof_options']) ? $cart_item['thwepof_options'] : false;
		
		if($extra_options){
			foreach($extra_options as $name => $data){
				if(isset($data['value']) && isset($data['label'])) {
					$item_data[] = array("name" => $data['label'], "value" => trim(stripslashes($data['value'])));
				}
			}
		}
		return $item_data;
	}
	
	public function woo_new_order_item($item_id, $item, $order_id){
		$legacy_values = is_object($item) && isset($item->legacy_values) ? $item->legacy_values : false;
		if($legacy_values){
			$extra_options = isset($legacy_values['thwepof_options']) ? $legacy_values['thwepof_options'] : false;
			if($extra_options){
				foreach($extra_options as $name => $data){
					wc_add_order_item_meta( $item_id, $name, trim(stripslashes($data['value'])) );
				}
			}
		}
	}

	public function woo_add_order_item_meta($item_id, $values, $cart_item_key) {
		if(is_array($values)){
			$extra_options = isset($values['thwepof_options']) ? $values['thwepof_options'] : false;
			if($extra_options){
				foreach($extra_options as $name => $data){
					wc_add_order_item_meta( $item_id, $name, trim(stripslashes($data['value'])) );
				}
			}
		}
	}
	
	/*public function woo_add_order_item_meta( $item_id, $values, $cart_item_key ) {
		$options_extra = $this->get_extra_options_from_woo_session_by_cart_item_key($cart_item_key);
		if($options_extra){			
			foreach($options_extra as $name => $data){
				wc_add_order_item_meta( $item_id, $data->get_name(), $values[$name] );
			}
		}	
	}*/

	public function woo_order_items_meta_get_formatted( $formatted_meta, $item_meta ) {
		if(!empty($formatted_meta)){
			$options_extra = $this->get_product_custom_fields_full();
			if($options_extra){
				foreach($formatted_meta as &$meta){
					if(array_key_exists($meta['key'], $options_extra)) {
						$meta['label'] = $options_extra[$meta['key']]->get_property('title');
					}
				}
			}
		}
		return $formatted_meta;
	}
	
	public function woo_order_item_get_formatted_meta_data( $formatted_meta, $order_item){
		if(!empty($formatted_meta)){
			$options_extra = $this->get_product_custom_fields_full();
			if($options_extra){
				foreach($formatted_meta as $key => $meta){
					if(array_key_exists($meta->key, $options_extra)) {
						$formatted_meta[$key] = (object) array(
							'key'           => $meta->key,
							'value'         => $meta->value,
							'display_key'   => apply_filters( 'woocommerce_order_item_display_meta_key', $options_extra[$meta->key]->get_property('title'), $meta, $order_item ),
							'display_value' => wpautop( make_clickable( apply_filters( 'woocommerce_order_item_display_meta_value', $meta->value, $meta, $order_item ) ) ),
						);
					}
				}
			}
		}
		return $formatted_meta;
	}

   /*********************************************************
	**** ADD CUSTOM OPTIONS & PRICE to CART ITEM - END ******
	*********************************************************/
}
endif;