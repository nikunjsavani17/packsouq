<?php
/**
 * Plugin Name: PayPal Currency Converter PRO for WooCommerce
 * Plugin URI: https://codecanyon.net/item/paypal-currency-converter-pro-for-woocommerce/6343249
 * Description: Convert any currency to allowed PayPal currencies for PayPal's Payment Gateway within WooCommerce
 * Version: 3.1.0
 * Author: Intelligent-IT
 * Author URI: https://codecanyon.net/user/intelligent-it
 * @author Henry Krupp <henry.krupp@gmail.com> 
 * @copyright 2018 Intelligent IT 
 * @license http://codecanyon.net/licenses/regular Codecanyon Regular
 * @version  3.1.0 Multisite
 */
 

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

//Includes
include_once('functions.php');

// Check if WooCommerce is active and bail if it's not
if ( ! is_woocommerce_active() )
	return;

//Instantiate PPCC
$GLOBALS['ppcc'] = new ppcc();

// localization
load_plugin_textdomain( 'PPCC-PRO', false, plugin_basename( dirname(__FILE__) ) . '/languages' );  

//PPCC Class
class ppcc {


	//define valid PayPal Currencies
	public $pp_currencies=array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'CNY' );//RMB?

    protected $option_name = 'ppcc-options';
	
	//default settings
    protected $data = array(
		'ppcc_use_custom_currency' => 'off',
		'ppcc_custom_currency_code' => '',
		'ppcc_custom_currency' => '',
		'ppcc_custom_currency_symbol' => '',
		'ppcc_custom_currency_name' => '',
        'target_currency' => 'USD',
        'conversion_rate' => '1.0',
		'auto_update' => 'off',
		'time_stamp' => 0,
		'exrlog' => 'on',
		'oer_api_id' => '', //https://openexchangerates.org/
		'xignite_api_id' => '', //https://www.xignite.com/
		'apilayer_api_id' => '', //http://www.apilayer.net/
		'fixer_io_api_id' => '', //https://fixer.io/
		'api_selection' => 'currencyconverterapi', //was 'custom'
		'retrieval_count' => 0,
		'autocomplete' => 'on', //PayPal auto completion for virtual products  //was 'off'
		'autoprocessing' => 'on', //PayPal auto processing for standard products //was 'off'
		'email_order_completed_note'=>'off', // enable Email Order Complete Note
		'order_email_note' => 'This order is payed with PayPal, converted with the currency exchange rate <em>%s%s/%s</em>.<br>Billed Total: <strong>%s</strong><br>Including handling fee percentage <strong>%s</strong> plus <strong>%s</strong> fix.',
		'precision' => '5', //digits to round to
		'handling_percentage' => '0.0', //handling fee percentage
		'handling_amount' => '0.00', //handling amount
		'handling_taxable' => 'on', //handling fee taxable?
		'shipping_handling_fee' => 'on', //handling fee also added to shipping cost
		'handling_title' => 'Handling fee', //handling fee custom title?
    );

    public function __construct() {

        add_action('init', array($this, 'init'));

        // Admin sub-menu
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_page'));

	
        // Listen for the activate event
        register_activation_hook(__FILE__, array($this, 'activate'));

        // Deactivation plugin
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));


	}

    public function init() {

		$options = get_ppcc_option('ppcc-options');

		//custom currency if checked
		
		if ("on"==$options['ppcc_use_custom_currency']){//there is  a custom currency
			add_filter('woocommerce_currencies', array($this,'add_ppcc_currency' ));
			add_filter('woocommerce_currency_symbol', array($this, 'add_ppcc_currency_symbol'), 10, 2); 
		}

		// add target Currency to Paypal and convert
		add_filter( 'woocommerce_paypal_supported_currencies', array($this, 'add_new_paypal_valid_currency' ));
		
		// convert currency for PayPal Request
		add_filter('woocommerce_paypal_args', array($this, 'convert_currency')); //Standard & Advanced PayPal gateway   
		add_filter('woocommerce_paypal_advanced_args', array($this, 'convert_currency')); //Advanced PayPal gateway by browsepress@codecanyon   
		add_filter('woocommerce_paypal_digital_goods_currency', array($this, 'get_target_currency'));  //PPPDG gateway
		add_filter('woocommerce_paypal_digital_goods_nvp_args', array($this, 'convert_currency_ppdg')); //PPPDG gateway

		add_filter('wc_gateway_paypal_express_request_params', array($this, 'convert_currency_ppexp'), 10, 2 ); //PPEXP gateway

		//Scheduler
		//add_action('ppcc_cexr_update',  array($this, 'currency_exchange_rate_update'));
		//add_action('ppcc_cexr_update',  'currency_exchange_rate_update');

		/*handle order status*/		
		add_action( 'woocommerce_order_status_on-hold', array($this,'ppcc_order_payment_handle_order_status'), 1, 2 );

		// Add Email Instructions
		add_action( 'woocommerce_email_order_meta', array($this,'ppcc_add_order_notes_to_email' ), 999, 2);

		// add_filter( 'woocommerce_payment_complete_order_status', array($this, 'ppcc_order_payment_complete_order_status'), 1, 2 );

		/*Prepare converted totals for checkout page*/
		add_action('woocommerce_review_order_before_payment', array($this, 'ppcc_converted_totals'));

		// handling fee
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'ppcc_calculate_totals' ), 10, 1 );
	
	}

	public function ppcc_add_order_notes_to_email($order,$is_admin) {
		
	    if ( 'paypal' == $order->payment_method ) {
			echo '<h2>' . __( 'PayPal currency conversion Notes', 'PPCC-PRO' ) . '</h2>';
			echo '<ul class="order_notes">';

			$options = get_ppcc_option('ppcc-options');

			$handling_amount_converted = number_format( $options['handling_amount'] * $options['conversion_rate'], 2, '.', '' )." ".$options['target_currency'];

		    $converted_total = $order->get_total() * $options['conversion_rate'];

		    global $woocommerce;
		    echo sprintf('<li>'.$options['order_email_note'].'</li>',$options['conversion_rate'], $order->get_order_currency(),$options['target_currency'], wc_price($converted_total,array('currency'=> $options['target_currency'])),$options['handling_percentage'].'%',$handling_amount_converted);
			echo '</ul>';


		}
	}


    public function activate() {

    	//Initialize only if options don't already exist

    	if(!get_ppcc_option('ppcc-options')){
	        update_ppcc_option($this->option_name, $this->data);
			global $woocommerce;
			$options = get_ppcc_option('ppcc-options');
			//$exrdata = get_exchangerate(get_woocommerce_currency(),$options['target_currency']);
			$options['conversion_rate'] = '1';
			$options['time_stamp']= current_time( 'timestamp' );
			$options['retrieval_count'] = $options['retrieval_count'] + 1;
			update_ppcc_option( 'ppcc-options', $options );
		}

		register_uninstall_hook( __FILE__, 'ppcc_uninstall' );
    }

    public function deactivate() {
    	//do nothing on deactivation
    }

    public function ppcc_uninstall() {
        delete_ppcc_option($this->option_name);
    }


	
	/*
	* Custom currency
	*/
	public function add_ppcc_currency( $currencies ) {
		$options = get_ppcc_option('ppcc-options');
		$currencies[$options['ppcc_custom_currency_code']] = __( $options['ppcc_custom_currency_name'], 'woocommerce' );
		return $currencies;
	}
	public function add_ppcc_currency_symbol( $currency_symbol, $currency ) {
		$options = get_ppcc_option('ppcc-options');
		switch( $currency ) {
			case $options['ppcc_custom_currency_code']: $currency_symbol = $options['ppcc_custom_currency_symbol']; break;
		}
		return $currency_symbol;
	}

	// add target Currency to Paypal and convert
	public function add_new_paypal_valid_currency( $currencies ) { 
			//$options = get_ppcc_option('ppcc-options');
			array_push ( $currencies , get_woocommerce_currency() );  
			return $currencies;    
		}  

	// convert currency for PayPal Standard & Advanced gateway
	public function convert_currency($paypal_args){ 
		
		global $woocommerce;
		$options = get_ppcc_option('ppcc-options');
		$convert_rate = $options['conversion_rate']; //set the converting rate  
		
		if($paypal_args['currency_code'] == $options['target_currency']){//don't convert
			return $paypal_args; 	
		}

		$nondecimalcurrencies=array('HUF','JPY','TWD');
		$decimals= (in_array($options['target_currency'], $nondecimalcurrencies))?0:2; //non decimal currencies
		if (isset($paypal_args['currency_code'])) $paypal_args['currency_code'] = $options['target_currency']; 
		$i = 1;  
		while (isset($paypal_args['amount_' . $i])) {  
			$paypal_args['amount_' . $i] = round( $paypal_args['amount_' . $i] * $convert_rate, $decimals);  
			++$i;  
		}
	
		// Discount
		if (isset($paypal_args['discount_amount_cart'])) $paypal_args['discount_amount_cart'] = round(@$paypal_args['discount_amount_cart'] * $convert_rate, $decimals);
		// Tax
		if (isset($paypal_args['tax_cart'])) $paypal_args['tax_cart'] = round( $paypal_args['tax_cart'] * $convert_rate, $decimals);
		// Shipping
		if (isset($paypal_args['shipping_1'])) $paypal_args['shipping_1'] = round( $paypal_args['shipping_1'] * $convert_rate, $decimals);
		
		
		//WooCommerce PayPal advanced
		if (isset($paypal_args['CURRENCY'])) $paypal_args['CURRENCY'] = $options['target_currency']; 
		if (isset($paypal_args['FREIGHTAMT'])) $paypal_args['FREIGHTAMT'] = round( $paypal_args['FREIGHTAMT'] * $convert_rate, $decimals);  
		//if (isset($paypal_args['L_COST0'])) $paypal_args['L_COST0'] = round( $paypal_args['L_COST0'] * $convert_rate, $decimals);  
		if (isset($paypal_args)) $paypal_args['ITEMAMT'] = round( $paypal_args['ITEMAMT'] * $convert_rate, $decimals);  
		if (isset($paypal_args['TAXAMT'])) $paypal_args['TAXAMT']  = round( $paypal_args['TAXAMT'] * $convert_rate, $decimals);  
		if (isset($paypal_args)) $paypal_args['AMT']  = round( $paypal_args['AMT'] * $convert_rate, $decimals);  
		if (isset($paypal_args['DISCOUNT'])) $paypal_args['DISCOUNT']= round( $paypal_args['DISCOUNT'] * $convert_rate, $decimals); 

		$i = 0;  
		while (isset($paypal_args['L_COST' . $i])) {  
			$paypal_args['L_COST'.$i] = round( $paypal_args['L_COST' . $i] * $convert_rate, $decimals);  
			$paypal_args['L_TAXAMT'.$i] = round( $paypal_args['L_TAXAMT' . $i] * $convert_rate, $decimals);  

			++$i;  
		}
		//handling fee
		if (isset($paypal_args['handling_fee'])){
			$paypal_args['handling_fee'] += $options['handling_amount'] + round( $options['handling_amount']/100 * $paypal_args['AMT'], $decimals);
		}
		else{
			$paypal_args['handling_fee'] = $options['handling_amount'] + round( $options['handling_amount']/100 * $paypal_args['AMT'], $decimals);
		}

		return $paypal_args;  
	}  

	// convert currency for PayPal Advanced gateway from browsepress@codecanyon
	public function convert_currency_ppadvanced_browsepress($paypal_args){ 
		
		global $woocommerce;
		$options = get_ppcc_option('ppcc-options');
		
		if($paypal_args['currency_code'] == $options['target_currency']){//don't convert
			return $paypal_args; 	
		}

		$convert_rate = $options['conversion_rate']; //set the converting rate  
		
		$nondecimalcurrencies=array('HUF','JPY','TWD');
		$decimals= (in_array($options['target_currency'], $nondecimalcurrencies))?0:2; //non decimal currencies

		$paypal_args['CURRENCY'] = $options['target_currency']; 

		// Total
		$paypal_args['AMT'] = round( $paypal_args['AMT'] * $convert_rate, $decimals);

		if (isset($paypal_args['L_COST1'])) $paypal_args['L_COST1']= round( $paypal_args['L_COST1'] * $convert_rate, $decimals); 
		if (isset($paypal_args['L_COST2'])) $paypal_args['L_COST2']= round( $paypal_args['L_COST2'] * $convert_rate, $decimals); 
		if (isset($paypal_args['DISCOUNT'])) $paypal_args['DISCOUNT']= round( $paypal_args['DISCOUNT'] * $convert_rate, $decimals); 
		// Tax
		if (isset($paypal_args['TAXAMT'])) $paypal_args['TAXAMT'] = round( $paypal_args['TAXAMT'] * $convert_rate, $decimals);
		
		if (isset($paypal_args['L_COST0'])){
			$i = 0;  
			while (isset($paypal_args['L_COST' . $i])) {//check for items existence

				$paypal_args['L_COST' . $i] = round( $paypal_args['L_COST' . $i] * $convert_rate, $decimals);  
				++$i;  
			}
		}
		return $paypal_args;  
	}  

	
	
	public function convert_currency_ppdg($paypal_args){ //conversion for PayPal Digital Goods

		global $woocommerce;
		$options = get_ppcc_option('ppcc-options');

		if($paypal_args['currency_code'] == $options['target_currency']){//don't convert
			return $paypal_args; 	
		}

		$convert_rate = $options['conversion_rate']; //set the converting rate  
		$nondecimalcurrencies=array('HUF','JPY','TWD');
		$decimals= (in_array($options['target_currency'], $nondecimalcurrencies))?0:2; //non decimal currencies
		
		
		if (isset($paypal_args['initial_amount'])){//recurring payment
			$paypal_args['initial_amount'] = number_format(round( $paypal_args['initial_amount'] * $convert_rate, $decimals), 2, '.', '' ); 
			$paypal_args['amount'] = number_format(round( $paypal_args['amount'] * $convert_rate, $decimals), 2, '.', '' ); 

			$paypal_args['average_amount'] = number_format(round( $paypal_args['average_amount'] * $convert_rate, $decimals), 2, '.', '' ); 
			
			list($product_title, $subscription_plan) = preg_split('/ - /', $paypal_args['description']);
			list($subscription_plan_part, $frequency) = preg_split('/ \/ /', $subscription_plan);

			$paypal_args['description'] = $product_title.' - '.$paypal_args['initial_amount'] .$options['target_currency']." up front then ". $paypal_args['average_amount'].$options['target_currency'].' / '.$frequency ;
			
		}
		else{//express checkout 
			$amount=0;
			$tax_amount=0;
			$i = 0;  
			while (isset($paypal_args['items'][$i]['item_amount'])) {  
				$paypal_args['items'][$i]['item_amount']=number_format(round( $paypal_args['items'][$i]['item_amount'] * $convert_rate, $decimals), 2, '.', '' ); 
				$amount+=$paypal_args['items'][$i]['item_amount'] * $paypal_args['items'][$i]['item_quantity'];
				$paypal_args['items'][$i]['item_tax']=number_format(round( $paypal_args['items'][$i]['item_tax'] * $convert_rate, $decimals), 2, '.', '' );
				$tax_amount+=$paypal_args['items'][$i]['item_tax'] * $paypal_args['items'][$i]['item_quantity']; 
				++$i;  
			}
		
			// Tax
			//$paypal_args['tax_amount']=number_format(round( $paypal_args['tax_amount'] * $convert_rate, $decimals), 2, '.', '' ); 
			$paypal_args['tax_amount']=number_format($tax_amount, 2, '.', '' ); 
			//$paypal_args['amount']=number_format(round( $paypal_args['amount'] * $convert_rate, $decimals), 2, '.', '' ); 
			$paypal_args['amount']=number_format($amount+$tax_amount, 2, '.', '' ); 
		}
		return $paypal_args;  
	}



	public function convert_currency_ppexp($paypal_args){ //conversion for PayPal Express

		global $woocommerce;
		$options = get_ppcc_option('ppcc-options');

		if($paypal_args['PAYMENTREQUEST_0_CURRENCYCODE'] == $options['target_currency']){//don't convert
			return $paypal_args; 	
		}

		$convert_rate = $options['conversion_rate']; //set the converting rate  
		
		$nondecimalcurrencies=array('HUF','JPY','TWD');
		$decimals= (in_array($options['target_currency'], $nondecimalcurrencies))?0:2; //non decimal currencies

		$paypal_args[ 'PAYMENTREQUEST_0_CURRENCYCODE' ] = $options['target_currency'];

		if(!isset($paypal_args['converted_parameters']))
		{
			$paypal_args['converted_parameters'] = array();
		}

		foreach ($paypal_args as $key => $value) {
			if (strpos($key,'AMT') !== false and in_array($key, $paypal_args['converted_parameters'])==false) { //
			   	$paypal_args[ $key ] = round( $paypal_args[ $key ] * $convert_rate, $decimals);//number_format( $value, 2, '.', '' );
				array_push($paypal_args['converted_parameters'], $key);
			}
		}


/*echo '<pre>';
print_r($woocommerce->cart);
print_r($paypal_args);
echo '<pre>';
die;*/
		return $paypal_args;  
	}  


	public function ppcc_order_payment_handle_order_status( $order){
		$order = new WC_Order( $order);
		
	    if ( 'paypal' == $order->payment_method){
		    // PayPal payment method with conversion
			$options = get_ppcc_option('ppcc-options');	
		    
			$order->add_order_note('PPCC - Cart total paid with '.$order->payment_method_title.' in converted currency: '.$order->order_total.get_woocommerce_currency().'*'.$options['conversion_rate'].$options['target_currency'].'/'.get_woocommerce_currency().'='.round($order->order_total * $options['conversion_rate'],2).$options['target_currency']);	
			$order->add_order_note('Transaction ID: '. get_post_meta($order->id, ‘_transaction_id’, true));
			$virtual_order = null;
 
			if ( count( $order->get_items() ) > 0 ) {
			  foreach( $order->get_items() as $item ) {
				if ( 'line_item' == $item['type'] ) {
				  $_product = $order->get_product_from_item( $item );
				  if ( ! $_product->is_virtual() ) {
					// once we've found one non-virtual product we know we're done, break out of the loop
					$virtual_order = false;
					break;
				  } else {
					$virtual_order = true;
				  }
				}
			  }
			}
			// virtual order, mark as completed
			if ( $virtual_order and $options['autocomplete'] == 'on') {
				$order->update_status('completed', __( 'Due to PPCC settings for virtual Orders: ', 'PPCC-PRO' ));
				// Reduce stock levels
				$order->reduce_order_stock();
			}

			// non virtual order, mark as processing and reduce stock
			if ( !$virtual_order and $options['autoprocessing'] == 'on'){ 
				$order->update_status('processing', __( 'Due to PPCC settings: ', 'PPCC-PRO' ));
				// Reduce stock levels
				$order->reduce_order_stock();
			}
		}
	}



	public function get_target_currency() {
		$options = get_ppcc_option('ppcc-options');
		return $options['target_currency'];
	}

	
	//calculate the converted total and tax amount for the payment-gateway description
	public function ppcc_converted_totals() {
		global $woocommerce;

		$options = get_ppcc_option('ppcc-options');
		$cart_contents_total = number_format( $woocommerce->cart->cart_contents_total * $options['conversion_rate'], 2, '.', '' )." ".$options['target_currency'];
		$shipping_total = number_format( ($woocommerce->cart->shipping_total) * $options['conversion_rate'], 2, '.', '' )." ".$options['target_currency'];
		//$tax = number_format(array_sum($woocommerce->cart->taxes)*$options['conversion_rate'], 2, '.', '' )." ".$options['target_currency'];
		$cart_tax_total = number_format( ($woocommerce->cart->shipping_tax_total + array_sum($woocommerce->cart->taxes)) * $options['conversion_rate'], 2, '.', '' )." ".$options['target_currency'];
		$shipping_tax_total = number_format(array_sum($woocommerce->cart->shipping_taxes) * $options['conversion_rate'], 2, '.', '' )." ".$options['target_currency'];

		$order_total_exc_tax = number_format( ($woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total) * $options['conversion_rate'], 2, '.', '' )." ".$options['target_currency'];
		$order_total_inc_tax = number_format( ($woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total + array_sum($woocommerce->cart->taxes) + $woocommerce->cart->shipping_tax_total) * $options['conversion_rate'], 2, '.', '' )." ".$options['target_currency'];
		$tax_total = number_format( ( array_sum($woocommerce->cart->taxes) + $woocommerce->cart->shipping_tax_total) * $options['conversion_rate'], 2, '.', '' )." ".$options['target_currency'];

		$cr = $options['conversion_rate']." ".$options['target_currency']."/".get_woocommerce_currency();
		$crval = $options['conversion_rate'];

		$handling_amount_converted = number_format( $options['handling_amount'] * $options['conversion_rate'], 2, '.', '' )." ".$options['target_currency'];
			
		wp_register_script( 'ppcc_checkout', plugins_url( '/assets/js/ppcc_checkout.js', __FILE__ ),'woocommerce.min.js', '1.0', true);//pass variables to javascript

		//echo $total.$tax.$cr."....".$_POST['payment_method'];

		$data = array(	
						'cart_total' => $cart_contents_total,
						'cart_tax' => $cart_tax_total,
						'shipping_total' => $shipping_total,
						'shipping_tax_total' => $shipping_tax_total,
						'total_order_exc_tax' => $order_total_exc_tax,
						'tax_total' => $tax_total,
						'total_order_inc_tax' => $order_total_inc_tax,
						'cr'=>$cr,
						'crval'=>$crval,
						'handling_percentage'=>$options['handling_percentage'],
						'handling_amount'=>$handling_amount_converted,	
						'wc_get_price_thousand_separator'=>wc_get_price_thousand_separator(),
						'wc_get_price_decimal_separator'=>wc_get_price_decimal_separator(),
						'wc_get_price_decimals'=>wc_get_price_decimals(),
						'target_currency'=>$options['target_currency'],
						);
						
		wp_localize_script('ppcc_checkout', 'ppcc_data', $data);
		wp_enqueue_script('ppcc_checkout');
			
	}

	
    // White list our options using the Settings API
    public function admin_init() {
        register_setting('ppcc_options', $this->option_name, array($this, 'validate'));
    }

    // Add entry in the WooCommerce settings menu
    public function add_page() {
		add_submenu_page( 'woocommerce', 'Exchange Rate',  'Exchange Rate' , 'manage_options', 'ppcc_options', array($this, 'options_do_page') );
    }

    // Print the menu page itself
    public function options_do_page() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		global $woocommerce;

        $options = get_ppcc_option($this->option_name);


	    // Warning for known colliding Plugins
		// include_once(ABSPATH.'wp-admin/includes/plugin.php');
		if (is_plugin_active('woocommerce-all-currencies/woocommerce-all-currencies.php') ) {
			echo '<div class="error plugin-collisison"><p>'.__('Deactivate "WooCommerce All Currencies" for "PayPal Currency Converter PRO for WooCommerce" to work properly!').'</p></div>';
		}
		if (is_plugin_active('woocommerce-custom-currencies/woocommerce-custom-currencies.php')) {
			echo '<div class="error plugin-collision"><p>'.__('Deactivate "WooCommerce Custom Currencies" for "PayPal Currency Converter PRO for WooCommerce" to work properly!').'</p></div>';
		}

		//Warning if CURL is missing
		if( !ppcc_is_curl_installed() and $options['api_selection'] != "custom") {
			echo '<div class="error settings-error"><p>'.__('Your server does not support CURL, but it is needed for "PayPal Currency Converter PRO for WooCommerce" to work properly!').'</p></div>';
		} 
		
		//warning for inproper setting
		if(($options['auto_update']=='on') and ($options['api_selection']=='custom')) {
			echo '<div class="error"><p>'.__(' While using a custom exchange rate there is not need to have auto update checked.').'</p></div>';
		} 

		//visibility will be handled by javascript
			echo '<div class="error settings-error" visibility="hidden"><p>Please check your current Currency Exchange Rate setting! Maybe the actual rate has changed.</p></div>';

		$fromto = get_woocommerce_currency().$options['target_currency'];
		if ($_GET['page']=='ppcc_options'){
			$exrdata = get_exchangerate(get_woocommerce_currency(),$options['target_currency']);

			wp_register_script( 'ppcc_script', plugins_url( '/assets/js/ppcc_script.js', __FILE__ ),'woocommerce.min.js', '1.0', true);//pass variables to javascript
			wp_register_script( 'woocommerce_admin', $woocommerce->plugin_url() . '/assets/js/admin/woocommerce_admin.min.js', array( 'jquery', 'jquery-tiptip'), $woocommerce->version );
			wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );

			$data = array(	
							'source_currency' => get_woocommerce_currency(),
							'target_currency' => $options['target_currency'],
							'amount'=>$exrdata,
							);
							
			wp_localize_script('ppcc_script', 'ppcc_data', $data);
			wp_enqueue_script('ppcc_script');
			wp_enqueue_script( 'woocommerce_admin' );

			update_paypal_description();

		}
		
		//determine current system
		$woocommerce_plugin_file =  trailingslashit( WP_PLUGIN_DIR ) . "woocommerce/woocommerce.php";
		$wc_data=get_plugin_data( $woocommerce_plugin_file);
		$ppcc_plugin_file =  __FILE__ ;
		$ppcc_data=get_plugin_data( $ppcc_plugin_file);
		$networkactiv = is_plugin_active_for_network(plugin_basename(__FILE__))?"true":"false";
		$currentsystem = "Wordpress ".get_bloginfo('version')." | ".$wc_data['Title' ]." ".$wc_data['Version' ]." | ".$ppcc_data['Title' ]." ".$ppcc_data['Version' ];

		if(function_exists('get_blog_details')){
			$bloginfo = get_blog_details(get_current_blog_id())->blogname;		
		}
		else{
			$bloginfo = get_bloginfo( 'name' );
		}
		
		
		$multisitesetup = " Network active:".$networkactiv.", current Blog name: ".$bloginfo;

		$currency_selector='<select id="target_cur" name="'.$this->option_name.'[target_currency]">';
		
		foreach($this->pp_currencies as $key => $value)
				{
					if ($options['target_currency']==$value){
						$currency_selector.= '<option value="'.$value.'" selected="selected">'.$value.'</option>';
						}else{
						$currency_selector.= '<option value="'.$value.'">'.$value.'</option>';
						}
				};
		$currency_selector.='</select>
		<label for="ppcc_target_cur"> (convert to currency)</label>';

        
		if($options['ppcc_use_custom_currency']=="on"){
			$custom_currency_checkbox_status='checked="checked"';
			$required='required="required"';
			$hidden='';
		}else{
			$custom_currency_checkbox_status='';
			$required='';
			$hidden='hidden';
			}
		($options['auto_update']=="on"?$auto_update_checkbox_status='checked="checked"': $auto_update_checkbox_status='');
		($options['exrlog']=="on"?$exrlog_checkbox_status='checked="checked"': $exrlog_checkbox_status='');
		($options['api_selection']=="oer_api_id"?$oer_api_checked='checked="checked"': $oer_api_checked='');
		($options['api_selection']=="ecb"?$ecb_checked='checked="checked"': $ecb_checked='');
		($options['api_selection']=="bnro"?$bnro_checked='checked="checked"': $bnro_checked='');
		($options['api_selection']=="currencyconverterapi"?$currencyconverterapi_checked='checked="checked"': $currencyconverterapi_checked='');
		($options['api_selection']=="custom"?$custom_checked='checked="checked"': $custom_checked='');
		($options['api_selection']=="xignite"?$xignite_checked='checked="checked"': $xignite_checked='');
		($options['api_selection']=="fixer_io_api_id"?$api_fixer_io_checked='checked="checked"': $api_fixer_io_checked='');
		($options['api_selection']=="apilayer"?$apilayer_checked='checked="checked"': $apilayer_checked='');
		($options['autocomplete']=="on"?$autocomplete_checkbox_status='checked="checked"': $autocomplete_checkbox_status='');
		($options['autoprocessing']=="on"?$autoprocessing_checkbox_status='checked="checked"': $autoprocessing_checkbox_status='');
		($options['email_order_completed_note']=="on"?$email_order_completed_note_checkbox_status='checked="checked"': $email_order_completed_note_checkbox_status='');
		($options['handling_taxable']=="on"?$handling_taxable_checkbox_status='checked="checked"': $handling_taxable_checkbox_status='');
		($options['shipping_handling_fee']=="on"?$shipping_handling_fee_checkbox_status='checked="checked"': $shipping_handling_fee_checkbox_status='');

		//Currency precision defines input field
		switch ($options['precision']) {
		    case 3:
		        $pval="0.001";
		        break;
		    case 4:
		        $pval="0.0001";
		        break;
		    case 5:
		        $pval="0.00001";
		        break;
		    case 6:
		        $pval="0.000001";
		        break;
		    case 7:
		        $pval="0.0000001";
		        break;
		    case 8:
		        $pval="0.00000001";
		        break;		        
		    }

		$handling_amount_converted = wc_price(number_format( $options['handling_amount'] * $options['conversion_rate'], 2, '.', '' ),array('currency'=> $options['target_currency']));

		$hide_bnro='hidden';
		if(get_woocommerce_currency()=='RON' or get_woocommerce_currency()=='ron'){
			$hide_bnro='';
		}

		$GatewayDescription= 'Cart Total: <span class="ppcc_cart_total" /><br>Shipping Total: <span class="ppcc_shipping_total" /><br>Handling fee <span class="ppcc_handling_percentage" />% plus <span class="ppcc_handling_amount" /> fixed.<br>Order Total Tax: <span class="ppcc_tax_total" /><br>Order Total inclusive Tax: <span class="ppcc_total_order_inc_tax" /><br>Conversion Rate: <span class="ppcc_cr" />';


        echo '<div class="wrap woocommerce">
            <h2>PayPal Currency Converter PRO</h2>
            <form method="post" action="options.php">';
        settings_fields('ppcc_options');
?>
			<h2 class="nav-tab-wrapper">
			    <a href="#?section=BaseSettings" class="nav-tab nav-tab-active" onclick="
				    jQuery('.ppcc_tab').hide();
				    jQuery('#BaseSettings').show();
				    jQuery('.nav-tab').removeClass('nav-tab-active');
				    jQuery(this).addClass('nav-tab-active');
			    "><?php echo __('Base settings','PPCC-PRO')?></a>
			    <a href="#?section=ExhangeRateProvider" class="nav-tab" onclick="
			    	jQuery('.ppcc_tab').hide();
			    	jQuery('#ExhangeRateProvider').show();
			    	jQuery('.nav-tab').removeClass('nav-tab-active');
				    jQuery(this).addClass('nav-tab-active');
			    	"><?php echo __('Exchange rate provider','PPCC-PRO')?></a>
			    <a href="#?section=HandlingFee" class="nav-tab" onclick="
			    	jQuery('.ppcc_tab').hide();
			    	jQuery('#HandlingFee').show();
			    	jQuery('.nav-tab').removeClass('nav-tab-active');
				    jQuery(this).addClass('nav-tab-active');
				    "><?php echo __('Handling fees','PPCC-PRO')?></a>
			    <a href="#?section=OrderStatus" class="nav-tab" onclick="
			    	jQuery('.ppcc_tab').hide();
			    	jQuery('#OrderStatus').show();
			    	jQuery('.nav-tab').removeClass('nav-tab-active');
				    jQuery(this).addClass('nav-tab-active');
				    "><?php echo __('Order Status Handling','PPCC-PRO')?></a>
			    <a href="#?section=OrderEmail" class="nav-tab" onclick="
			    	jQuery('.ppcc_tab').hide();
			    	jQuery('#OrderEmail').show();
			    	jQuery('.nav-tab').removeClass('nav-tab-active');
				    jQuery(this).addClass('nav-tab-active');
			    	"><?php echo __('Email Order Complete Note','PPCC-PRO')?></a>
			    <a href="#?section=Scheduler" class="nav-tab" onclick="
			    	jQuery('.ppcc_tab').hide();
			    	jQuery('#scheduler').show();
			    	jQuery('.nav-tab').removeClass('nav-tab-active');
				    jQuery(this).addClass('nav-tab-active');			    	
			    	"><?php echo __('Scheduled update','PPCC-PRO')?></a>
			    <a href="#?section=Info" class="nav-tab" onclick="
			    	jQuery('.ppcc_tab,.submit').hide();
			    	jQuery('#info').show();
			    	jQuery('.nav-tab').removeClass('nav-tab-active');
				    jQuery(this).addClass('nav-tab-active');
			    	"><?php echo __('Info','PPCC-PRO')?></a>
			</h2>

<?php
		echo '   
			<div class="ppcc_tab" id ="BaseSettings" style="display: show;">	
				<h2>Make your choice.</h2>
				<table class="form-table">
	 				<tbody>
						<tr valign="top">
							<th class="titledesc" scope="row" style="width: 250px;">
								<label >'. __('Need a custom currency','PPCC-PRO').'? </label>
								<span class="woocommerce-help-tip" data-tip="'. __('Check this to enable your custom currency. Once checked and all custom currency settings are done, save the settings...go to WooCommerce settings and select your custom currency, save it and come back here. ','PPCC-PRO').'"></span>
							</th>
							<td class="forminp"><input type="checkbox" id="ucc" value="1" name="'. $this->option_name.'[ppcc_use_custom_currency]" '.$custom_currency_checkbox_status.' />
								<span id="customcurrency" '.$hidden.'>
									Code:<span class="woocommerce-help-tip" data-tip="'. __('Your custom currency ISO-4217-Code. If the conversion fails...make sure you are using the correct code','PPCC-PRO').'"></span> <input type="text" size="3" id="ccc" name="'. $this->option_name.'[ppcc_custom_currency_code]" value="'.$options['ppcc_custom_currency_code'].'" '.$required.'/>
									Symbol:<span class="woocommerce-help-tip" data-tip="'. __('Your custom currency symbol.','PPCC-PRO').'"></span> <input type="text" size="3" id="ccs" name="'. $this->option_name.'[ppcc_custom_currency_symbol]" value="'.$options['ppcc_custom_currency_symbol'].'" '.$required.'/>
									Name:<span class="woocommerce-help-tip" data-tip="'. __('Your custom currency name.','PPCC-PRO').'"></span> <input type="text" size="8" id="ccn" name="'. $this->option_name.'[ppcc_custom_currency_name]" value="'.$options['ppcc_custom_currency_name'].'" '.$required.'/>
								</span>	
							</td>
	                    </tr>
	                   <tr valign="top">
							<th class="titledesc" scope="row">
								<label >'.__('Source Currency','PPCC-PRO').'</label>
								<span class="woocommerce-help-tip" data-tip="'.__('Source Currency as settled in general settings.','PPCC-PRO').'"></span>
							</th>
	                        <td class="forminp"><input type="text" size="3" value="'. get_woocommerce_currency().'"  disabled/><label for="ppcc_source_cur"> (convert from currency, this is your WooCommerce Shop Currency)</label></td>
	                    </tr>
	                    <tr valign="top">
						<th class="titledesc" scope="row">
								<label >'.__('Target Currency','PPCC-PRO').'</label>
								<span class="woocommerce-help-tip" data-tip="'.__('Desired target currency, what you expect to be billed in PayPal.','PPCC-PRO').'"></span>
							</th>
	                        <td class="forminp">'. $currency_selector .'</td>
	                    </tr>
	                    <tr valign="top">
							<th class="titledesc" scope="row"><label >'. __('Google\'s conversion history chart','PPCC-PRO').':</label >
							
	                        <td class="forminp">
								<img src="https://www.google.com/finance/chart?q=CURRENCY:'. $fromto .'&tkr=1&p=5Y&chst=vkc&chs=400x140"></img><br/>
							</td>
	                    </tr>
		                <tr valign="top">
							<th class="titledesc" scope="row">
								<label >'. __('Precision','PPCC-PRO').'</label>
								<span class="woocommerce-help-tip" data-tip="'. __('Defines the number of digits to round to.','PPCC-PRO').'"></span>
							</th>
							<td class="forminp">
								<input style ="width: 3em;" type="number" placeholder="1" step="1" min="3" max="8" id="precision_value" name="'. $this->option_name.'[precision]" value="'.$options['precision'].'"/>
								<span class="woocommerce-help-tip" data-tip="'. __('Usually exchange rates are given with 4 digits precision, but depending on your currency pair this can be inaccurate so it can be tweaked to more digits. Min value = 3, max value = 8 digits.','PPCC-PRO').'"></span>
							</td>	
	                    </tr>	                    
	                    <tr valign="top">
							<th class="titledesc" scope="row">
								<label >'. __('Shop Conversion Rate','PPCC-PRO').'</label>
								<span class="woocommerce-help-tip" data-tip="'. __('Accept suggested rate or set your own conversion rate. (Will be overwritten if scheduled update is active.)','PPCC-PRO').'"></span>
							</th>
							<td class="forminp" >
								<input style ="width: 9em;" type="number" placeholder="1" step="'.$pval.'" min="'.$pval.'" max="10000" id="cr" size="'.$options['precision'].'" name="'. $this->option_name.'[conversion_rate]" value="'.$options['conversion_rate'].'" /><span class="woocommerce-help-tip" data-tip="'. __('Input will be red when custom currency is not equal suggested currency. Press the button with the actual rate to accept the current suggested rate.','PPCC-PRO').'"></span>
								'. __('press this button','PPCC-PRO').'&#9658;<input type="button" class="button-primary" id="selected_currency" value="'.$exrdata.'"/> '. __('to accept the current exchange rate.','PPCC-PRO').'<span class="woocommerce-help-tip" data-tip="Currently selected provider\'s internal Id = '.$options['api_selection'].'"></span>
							</td>
	                    </tr>	                    
					</tbody>
				</table>
				<p class="submit">
					<button name="save" class="button-primary woocommerce-save-button" type="submit" value="Save changes">Save changes</button>
				</p>
			</div>


			<div class="ppcc_tab" id ="ExhangeRateProvider" style="display: none;">
				<h2>Select your desired exchange rate provider.</h2>
				<table class="form-table">
				<tbody>
					<tr valign="top">
						<th class="titledesc" scope="row" style="width:250px;">
							<label >'. __('Custom Exchange Rate','PPCC-PRO').' </label>
							<span class="woocommerce-help-tip" data-tip="'. __('Please click this radio button to set your own custom exchange rate.','PPCC-PRO').' Internal Id = custom"></span>
						</th>
						<td class="forminp">
							<input type="radio" id="custom" name="'. $this->option_name.'[api_selection]" value="custom" '.$custom_checked.'/>
						</td>
					</tr>

					<tr valign="top">
						<th class="titledesc" scope="row">
							<label >CURRENCYCONVERTERAPI</label>
							<span class="woocommerce-help-tip" data-tip="'. __('Please click this radio button to choose free.currencyconverterapi.com as your exchange rate provider. It is free but max precission is 6 digits.','PPCC-PRO').' Internal Id = currencyconverterapi"></span>
						</th>
						</th>
						<td class="forminp">
							<input type="radio" id="currencyconverterapi" name="'. $this->option_name.'[api_selection]" value="currencyconverterapi" '.$currencyconverterapi_checked.'/>Source <a href="https://free.currencyconverterapi.com/">free.currencyconverterapi.com</a>					
						</td>
					</tr>

					<tr valign="top" '.$hide_bnro.'>
						<th class="titledesc" scope="row">
							<label >'. __('Bank of Romania','PPCC-PRO').' </label>
							<span class="woocommerce-help-tip" data-tip="'. __('Please click this radio button to choose BNRO ( Banca Naţională a României) as your exchange rate provider.','PPCC-PRO').' Internal Id = bnro"></span>
						</th>
						<td class="forminp">
							<input type="radio" id="bnro" name="'. $this->option_name.'[api_selection]" value="bnro" '.$bnro_checked.'/><span class="woocommerce-help-tip" data-tip="'. __('BANK of Romania<br>(Base = RON)','PPCC-PRO').'"></span> Source <a href="http://www.bnro.ro/nbrfxrates.xml">BNRO</a>						
						</td>
                    </tr>

					<tr valign="top">
						<th class="titledesc" scope="row">
							<label >European Central Bank</label>
							<span class="woocommerce-help-tip" data-tip="'. __('Please click this radio button to choose api.fixer.io as your exchange rate provider.','PPCC-PRO').' Internal Id = fixer_io_api_id"></span>
						</th>
						<td class="forminp">
							<input type="radio" id="fixer_io_api_id" name="'. $this->option_name.'[api_selection]" value="fixer_io_api_id" '.$api_fixer_io_checked.'/>
							<input type="text" size="35" id="fixer_io_api_id" name="'. $this->option_name.'[fixer_io_api_id]" value="'.$options['fixer_io_api_id'].'"/>
							<span class="woocommerce-help-tip" data-tip="'. __('Register your fixer.io API ID in this text field and save.<br/>(Base = EUR)','PPCC-PRO').'"></span> Get the API ID here: <a href="https://fixer.io">fixer.io</a>					
						</td>
					</tr>


					<tr valign="top">
						<th class="titledesc" scope="row">
							<label >'. __('OPENEXCHANGERATES API ID','PPCC-PRO').' </label>
							<span class="woocommerce-help-tip" data-tip="'. __('Please click this radio button to choose OPENEXCHANGERATES as your exchange rate provider.','PPCC-PRO').' Internal Id = oer_api_id"></span>
						</th>
						<td class="forminp">
							<input type="radio" id="oer_api_id" name="'. $this->option_name.'[api_selection]" value="oer_api_id" '.$oer_api_checked.'/> 
							<input type="text" size="35" id="oer_api_id" name="'. $this->option_name.'[oer_api_id]" value="'.$options['oer_api_id'].'"/>
							<span class="woocommerce-help-tip" data-tip="'. __('Register your Open Exchange Rate API ID in this text field and save.<br/>(Base = USD)','PPCC-PRO').'"></span> Get the API ID here: <a href="https://openexchangerates.org">openexchangerates.org</a>
						</td>
                    </tr>


					<tr valign="top">
						<th class="titledesc" scope="row" >
							<label >XIGNITE API ID</label>
							<span class="woocommerce-help-tip" data-tip="'. __('Please click this radio button to choose XIGNITE as your exchange rate provider.','PPCC-PRO').' Internal Id = xignite"></span>
						</th>
						<td class="forminp">
							<input type="radio" id="xignite_api" name="'. $this->option_name.'[api_selection]" value="xignite" '.$xignite_checked.'/> 
							<input type="text" size="35" id="oer_api_id" name="'. $this->option_name.'[xignite_api_id]" value="'.$options['xignite_api_id'].'"/>
							<span class="woocommerce-help-tip" data-tip="'. __('Register your XIGNITE API ID in this text field and save.<br/>(Base = USD)','PPCC-PRO').'"></span> Get the API ID here: <a href="https://www.xignite.com">xignite.com</a>
						</td>
                    </tr>

					<tr valign="top">
						<th class="titledesc" scope="row" >
							<label >APILAYER API ID</label>
							<span class="woocommerce-help-tip" data-tip="'. __('Please click this radio button to choose APILAYER as your exchange rate provider.','PPCC-PRO').' Internal Id = apilayer"></span>
						</th>
						</th>
						<td class="forminp">
							<input type="radio" id="apilayer_api" name="'. $this->option_name.'[api_selection]" value="apilayer" '.$apilayer_checked.'/> 
							<input type="text" size="35" id="oer_api_id" name="'. $this->option_name.'[apilayer_api_id]" value="'.$options['apilayer_api_id'].'"/>
							<span class="woocommerce-help-tip" data-tip="'. __('Register your APILAYER API ID in this text field and save.<br/>(Base = USD)','PPCC-PRO').'"></span> Get the API ID here: <a href="https://currencylayer.com/product">currencylayer.comt</a>
						</td>
                    </tr>

				</tbody>
				</table>
				<br><strong>NO guarantees</strong> are given whatsoever of accuracy, validity, availability, or fitness for any purpose - please use at your own risk.
				<p class="submit">
					<button name="save" class="button-primary woocommerce-save-button" type="submit" value="Save changes">Save changes</button>
				</p>
			</div>


			<div class="ppcc_tab" id ="HandlingFee" style="display: none;">
				<h2>'. __('Add (optional) handling fees to the transaction.','PPCC-PRO').'
				</h2>
				<table class="form-table">
				<tbody>
					<tr valign="top">
						<th class="titledesc" scope="row" style="width: 250px;">
							<label >'. __('Percentage','PPCC-PRO').' </label>
							<span class="woocommerce-help-tip" data-tip="'. __('Enter a percentage value other then 0 to add a percentage on the total order amount(befor fixed amount is added).','PPCC-PRO').'"></span>
						</th>
						<td class="forminp">
							<input style ="width: 5em;" type="number" placeholder="1.0" step="0.01" min="0" max="10" id="handling_percentage"  name="'. $this->option_name.'[handling_percentage]" value="'.$options['handling_percentage'].'" /> in %
							<span class="woocommerce-help-tip" data-tip="'. __('A handling fee percentage should be in a reasonable range, certainly and most probably lower then 5%. Min value = 0.00%(equals no percentual handling fee), max value = 10.00%.'		,'PPCC-PRO').'"></span> 
						</td>
                    </tr>
                    <tr valign="top">
						<th class="titledesc" scope="row" style="width: 250px;">
							<label >'. __('Fixed amount','PPCC-PRO').' </label>
							<span class="woocommerce-help-tip" data-tip="'. __('Enter a handling fee amount value other then 0 to add a fixed amount to the order total as handling fee.','PPCC-PRO').'"></span>
						</th>
						<td class="forminp">
							<input style ="width: 5em;" type="number" placeholder="1.0" step="0.01" min="0" max="10000" id="handling_amount"  name="'. $this->option_name.'[handling_amount]" value="'.$options['handling_amount'].'" /> in '.get_woocommerce_currency().'
							<span class="woocommerce-help-tip" data-tip="'. __('A handling fee amount should be in a reasonable range given in the shop currency. Min value = 0.00(equals no fixed handling fee amount), max value = 10\'000.00 units in shop currency.','PPCC-PRO').'"></span>
						</td>
                    </tr>
                    <tr valign="top">
						<th class="titledesc" scope="row" style="width: 250px;">
							<label >'. __('Taxable','PPCC-PRO').' </label>
							<span class="woocommerce-help-tip" data-tip="'. __('Check this to enable standard tax calculation on handling fee. Please consider not to tax customers abroad.','PPCC-PRO').'"></span>
						</th>
						<td class="forminp"><input type="checkbox" id="htax" value="1" name="'. $this->option_name.'[handling_taxable]" '.$handling_taxable_checkbox_status.' /> Handling fee will be taxed when checked.
						</td>
                    </tr> 
                    <tr valign="top">
						<th class="titledesc" scope="row" style="width: 250px;">
							<label >'. __('Shipping included','PPCC-PRO').' </label>
							<span class="woocommerce-help-tip" data-tip="'. __('Check this to also calculate the percentage handling fee including shipping cost.','PPCC-PRO').'"></span>
						</th>
						<td class="forminp"><input type="checkbox" id="htax" value="1" name="'. $this->option_name.'[shipping_handling_fee]" '.$shipping_handling_fee_checkbox_status.' /> Handling fee percentage will also take shipping cost into account.
						</td>
                    </tr>                  
                    <tr valign="top">
						<th class="titledesc" scope="row" style="width: 250px;">
							<label >'. __('Custom title','PPCC-PRO').' </label>
							<span class="woocommerce-help-tip" data-tip="'. __("Name your handling fee. 'Handling fee' recommended.",'PPCC-PRO').'"></span>
						</th>
						<td class="forminp"><input type="text" id="htitle" value="'. $options['handling_title'].'" name="'. $this->option_name.'[handling_title]" /> Custom handling fee title.
						</td>
                    </tr>  
   				</tbody>
				</table>
				<p class="submit">
					<button name="save" class="button-primary woocommerce-save-button" type="submit" value="Save changes">Save changes</button>
				</p>
			</div>


			<div class="ppcc_tab" id ="OrderStatus" style="display: none;">
				<h2>'. __('Override the normal behavior of PayPal which sets Orders on_hold due to the difference in amount and currency.','PPCC-PRO').'
				</h2>
				<table class="form-table">
				<tbody>
					<tr valign="top">
						<th class="titledesc" scope="row" style="width: 250px;">
							<label >'. __('Auto-complete','PPCC-PRO').' </label>
							<span class="woocommerce-help-tip" data-tip="'. __('Check this to override PayPal\'s amount and currency comparison-check on your own risk.','PPCC-PRO').'"></span>
						</th>
						<td class="forminp"><input type="checkbox" id="ac" value="1" name="'. $this->option_name.'[autocomplete]" '.$autocomplete_checkbox_status.' /> Orders for virtual (downloadable) products will be completed when checked and stock will be reduced.
						</td>
                    </tr>
                    <tr valign="top">
						<th class="titledesc" scope="row" style="width: 250px;">
							<label >'. __('Auto-processing','PPCC-PRO').' </label>
							<span class="woocommerce-help-tip" data-tip="'. __('Check this to override PayPal\'s amount and currency comparison-check on your own risk.','PPCC-PRO').'"></span>
						</th>
						<td class="forminp"><input type="checkbox" id="ap" value="1" name="'. $this->option_name.'[autoprocessing]" '.$autoprocessing_checkbox_status.' /> Orders for standard products will be set to processing and stock will be reduced when checked.
						</td>
                    </tr>
				</tbody>
				</table>
				<p class="submit">
					<button name="save" class="button-primary woocommerce-save-button" type="submit" value="Save changes">Save changes</button>
				</p>
			</div>


			<div class="ppcc_tab" id ="OrderEmail" style="display: none;">
				<h2>'. __('Add Information to the Order Complete Email.','PPCC-PRO').'</h2>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th class="titledesc" scope="row" style="width: 250px;">
								<label >'. __('Add Note to Order Receipt Email ','PPCC-PRO').' </label>
								<span class="woocommerce-help-tip" data-tip="'. __('checked = add the note / unchecked = no action','PPCC-PRO').'"></span>
							</th>
							<td class="forminp"><input type="checkbox" id="ac" value="1" name="'. $this->option_name.'[email_order_completed_note]" '.$email_order_completed_note_checkbox_status.' /> Adds a note to the order receipt email about the conversion and converted total amount paid via PayPal.
							</td>
	                    </tr>
	                    <tr>
	                    <th>
	                    	Email order note html
							<span class="woocommerce-help-tip" data-tip="'. __('Here you may edit the text that will be displayed in the Email order notification.<br>The sequence for placeholder %s and their content is fixed. <ol><li>%s = exchangerate</li><li>%s = shop currency</li><li>%s = target currency</li><li>%s = converted order total</li><li>%s = handling percentage</li><li>%s = handling amount in '.$options['target_currency'].'</li></ol>','PPCC-PRO').'"></span>
	                    </th>
	                    <td>
	                    	<textarea cols="80" rows="3" id="order_email_note" name="'. $this->option_name.'[order_email_note]" />'.$options['order_email_note'].'</textarea>
	                    </td>
	                    </tr>
						<tr valign="top">
	                    <th>
	                    	Email order note rendered
							<span class="woocommerce-help-tip" data-tip="'. __('This is what will be displayed in the Email order notification, except the Order Total will be calculated accordingly.<br> Save your settings to see the current result.','PPCC-PRO').'"></span>
	                    </th>
	                    <td><em>'.
	                    	sprintf($options['order_email_note'],$options['conversion_rate'], get_woocommerce_currency(),$options['target_currency'], wc_price('100',array('currency'=> $options['target_currency'])),$options['handling_percentage'].'%',$handling_amount_converted)
	                    .'</em></td>
	                    </tr>
					</tbody>
				</table>
				<p class="submit">
					<button name="save" class="button-primary woocommerce-save-button" type="submit" value="Save changes">Save changes</button>
				</p>
			</div>


			<div class="ppcc_tab" id ="scheduler" style="display: none;">
				<h2>'. __('Enable scheduled currency exchange rate updates.','PPCC-PRO').'</h2>
				<table class="form-table">
					<tbody>
	                    <tr valign="top">
							<th class="titledesc" scope="row" style="width: 250px;">
								<label >'. __('Activate Scheduled Update','PPCC-PRO').' </label>
								<span class="woocommerce-help-tip" data-tip="'. __('Check this to have the exchange rate automatically updated dependant on your settings in your hosting server\'s Cron Job or in your Wordpress\'s Cron Job plugin.','PPCC-PRO').'"></span>
							</th>
							<td class="forminp"><input type="checkbox" id="ud" value="1" name="'. $this->option_name.'[auto_update]" '.$auto_update_checkbox_status.' /> '. __('Make sure to have a valid API ID registered above when using Open Exchange Rates API!','PPCC-PRO').'
							</td>
	                    </tr>
		               <tr valign="top">
							<th class="titledesc" scope="row">
								<label >'. __('Last Update','PPCC-PRO').' </label>
								<span class="woocommerce-help-tip" data-tip="'. __('Indicates the last time the Currency Exchange Rate was updated manually or automatic.','PPCC-PRO').'"></span>
							</th>
							<td class="forminp">
								<input type="text" id="ludisabled" value="'.date( 'Y-m-d H:i:s',(int)$options['time_stamp']).'" disabled />
								<input type="text" id="lu" name="'. $this->option_name.'[time_stamp]" value="'.(int)$options['time_stamp'].'" hidden />
							</td>
	                    </tr>
						<tr valign="top">
							<th class="titledesc" scope="row">
								<label >'. __('Log','PPCC-PRO').' </label>
								<span class="woocommerce-help-tip" data-tip="'. __('Check this to write actions into log file.','PPCC-PRO').'"></span>
							</th>
							<td class="forminp"><input type="checkbox" id="ud" value="1" name="'. $this->option_name.'[exrlog]" '.$exrlog_checkbox_status.' /> '.__('Notification email for each Exchange Rate Update will be sent to ','PPCC-PRO').get_ppcc_option('admin_email').'
							</td>
	                    </tr>					
						<tr valign="top">
							<th scope="row" class="titledesc"><label >'.__('Scheduler log','PPCC-PRO').' </label><span class="woocommerce-help-tip" data-tip="'.__('Log location of Currerncy Exchange Rate updates.','PPCC-PRO').'" /></th>
							<td class="forminp">	
								'. sprintf( __( '<code>../wp-content/uploads/wc-logs/PPCC-%s.txt</a></code><br/>', 'PPCC-PRO' ), sanitize_file_name( wp_hash( 'ppcc' ) ) ) . '
							</td>
						</tr>			
						<tr valign="top">
							<th scope="row" class="titledesc"><label >'.__('Ajax Call URL for Cron Job','PPCC-PRO').' </label><span class="woocommerce-help-tip" data-tip="'.__('Call this URL from your Hosting Servers Cron Job for exactly timed currency updates.','PPCC-PRO').'" /></th>
							<td class="forminp">	
								<code>' . site_url() .'/wp-admin/admin-ajax.php?action=ppcc&ppcc_function=cexr_update</code><br/><a href="https://www.easycron.com?ref=14909" title="EAYSYCRON.COM"><img src="'.plugins_url( '/assets/images/easycron-logo.png',__file__).'" alt"easycron.com"></a>...provides a hassle free solution !
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label >'.__('Hook to call for "WP Cron" Job','PPCC-PRO').' </label>
									<span class="woocommerce-help-tip" data-tip="'.__('Call the Hook \'ppcc_cexr_update\' from your Cron Job Plugin. But consider that Wordpress\'s Cron System is dependant on traffic and not necessarily timely exact.','PPCC-PRO').'" />
							</th>
							<td class="forminp">
								<code>ppcc_cexr_update</code>
							</td>
						</tr>
	                </tbody>
           		</table>
				<p class="submit">
					<button name="save" class="button-primary woocommerce-save-button" type="submit" value="Save changes">Save changes</button>
				</p>
       		</div>
	    </form>	

			<div class="ppcc_tab" id="info" style="display: none;">
				<table class="form-table">
				<tbody>
					<tr valign="middle">
						<th scope="row" class="titledesc" style="width: 250px;">
							Disclaimer
						</th>
						<td class="forminp">
						'.__('Use this plugin at your own risk, <strong>test in advance in "PayPal sandbox" mode</strong>. Intelligent-IT shall not be liable for any loss or injury caused in whole, or in part, by its actions, omissions, or contingencies beyond its control, including in procuring, compiling, or delivering the information, or arising out of any errors, omissions, or inaccuracies in the information regardless of how caused, or arising out of any user\'s decision, or action taken or not taken in reliance upon information furnished.','PPCC-PRO').'
						</td>
						<td class="forminp">
						</td>

						</tr>
					<tr valign="middle">
						<th scope="row" class="titledesc" style="width: 250px;">
							Linked Sites
						</th>
						<td class="forminp">
						'.__('Intelligent-IT does not assume any responsibility or liability for any information, communications or materials available at such linked sites, or at any link contained in a linked site. Intelligent-IT does not intend these third party links to be referrals or endorsements of the linked entities, and are provided for convenience only. Each individual site has its own set of policies about what information is appropriate for public access. User assumes sole responsibility for use of third party links and pointers.','PPCC-PRO').'
						</td>
						<td class="forminp">
						</td>
					</tr>
					<tr valign="middle">
						<th scope="row" class="titledesc" style="width: 250px;">
							Compatibility
						</th>
						<td class="forminp">
						'.__('Paypal Currency Converter Pro is also capable of working with <a href="http://www.woothemes.com/products/paypal-digital-goods-gateway/" title="PayPal Digital Goods">PayPal Digital Goods gateway</a> and <a href="http://docs.woothemes.com/document/paypal-payments-advanced/" titel="PayPal Advanced">PayPal Advanced</a> besides PayPal Standard . However this needs some tiny customization in the plugins. Mainly it is the shop currency to be set in the allowed currencies.','PPCC-PRO').'
						</td>
					</tr>
				<tr valign="top">
					<th scope="row" class="titledesc"><label >'.__('System versions','ppcc').' </label><span class="woocommerce-help-tip" data-tip="'.__('information about your systems versions','ppcc').'" /></th>
					<td class="forminp">'
					. $currentsystem.
					'</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc"><label >'.__('Multisite setup','ppcc').' </label><span class="woocommerce-help-tip" data-tip="'.__('information about your setup','ppcc').'" />
					</th>
					<td class="forminp">'
					. $multisitesetup.
					'</td>
				</tr>
                <tr valign="top">
					<th class="titledesc" scope="row">
						<label >'. __('Retrieval Counter','PPCC-PRO').'</label>
						<span class="woocommerce-help-tip" data-tip="'. __('Indicates the number of Currency Exchange Rate retrievals, since plugin activation.','PPCC-PRO').'"></span>
					</th>
					<td class="forminp">
						<input type="text" size="5" id="rcdisabled" value="'.$options['retrieval_count'].'"  disabled/>
						<input type="text" size="5" id="rc" name="'. $this->option_name.'[retrieval_count]" value="'.$options['retrieval_count'].'" hidden/>
					</td>	
                </tr>
				<tr valign="top">
					<th scope="row" class="titledesc"><label >'.__('Payment gateway description','ppcc').' </label><span class="woocommerce-help-tip" data-tip="'.__('Place this, or a subset of it, inside PayPal Payment description','ppcc').'" />
					</th>
					<td class="forminp">
						<code>
						'.htmlentities($GatewayDescription).'
						</code><br/>
						'.__('Copy the code above, then go to your <a href="' . site_url() .'/wp-admin/admin.php?page=wc-settings&tab=checkout&section=paypal">WooCommerce/Checkout/PayPal</a>, and paste it into the description. Feel free to change the text, the order of rows, or to omit rows. But make sure the span tags stay valid, because that\'s what will be replaced with values on the checkout page.','PPCC-PRO').'
					</td>
				</tr>                

				<tr valign="middle">
					<th >
					<label >'.__('Help & Support','ppcc').' </label>
						<a href="https://codecanyon.net/user/intelligent-it">
							<span class="woocommerce-help-tip" data-tip="'. __('Hover the icon on the right.','PPCC-PRO').'" />
						</a> 
					</th>
					<td >
						<a href="http://codecanyon.net/item/paypal-currency-converter-pro-for-woocommerce/6343249"  title="'. __('PayPal Currency Converter PRO plugin support is an option you have to opt for at Code Canyon. Follow the link of the icon.','PPCC-PRO').'">
							<img  src="'.plugins_url( '/assets/images/PPCC-PRO-icon-80x80.png',__file__).'" />
						</a>
					</td>
				</tr>
					<tr valign="middle">
						<th scope="row" class="titledesc" style="width: 250px;">
							Plugin in action
							<span class="woocommerce-help-tip" data-tip="'. __('Watch how to set up the plugin.','PPCC-PRO').'" />
						</th>
						<td class="forminp">
							 <iframe width="420" height="315" src="https://www.youtube.com/embed/B-rlzUBJ8B8?"></iframe> 	
							 <br/>watch <a href="https://www.youtube.com/watch?v=B-rlzUBJ8B8">PayPal Currency Converter Pro for WooCommerce Demo</a> on YouTube.
						</td>
					</tr>				
				</tbody>
				</table>
			</div>

	</div>
	';

    }







	public function validate($input) {

		$valid = array();
		($input['ppcc_use_custom_currency']=="1"||$input['ppcc_use_custom_currency']=="on")? $valid['ppcc_use_custom_currency'] = "on":$valid['ppcc_use_custom_currency'] = "off";
		$valid['ppcc_custom_currency_code'] = sanitize_text_field($input['ppcc_custom_currency_code']);
		$valid['ppcc_custom_currency_symbol'] = sanitize_text_field($input['ppcc_custom_currency_symbol']);
		$valid['ppcc_custom_currency_name'] = sanitize_text_field($input['ppcc_custom_currency_name']);
		$valid['target_currency'] = $input['target_currency'];
		$valid['conversion_rate'] = sanitize_text_field($input['conversion_rate']);
		($input['auto_update']=="1"||$input['auto_update']=="on")? $valid['auto_update'] = "on":$valid['auto_update'] = "off";
		(@$_GET['settings-updated']=='true' || @$_GET['ppcc_function'] == 'cexr_update')? $valid['time_stamp'] = current_time( 'timestamp' ):$valid['time_stamp'] = $input['time_stamp'];
		($input['exrlog']=="1"||$input['exrlog']=="on")? $valid['exrlog'] = "on":$valid['exrlog'] = "off";
		$valid['fixer_io_api_id'] = $input['fixer_io_api_id'];
		$valid['oer_api_id'] = $input['oer_api_id'];
		$valid['xignite_api_id'] = $input['xignite_api_id'];
		$valid['apilayer_api_id'] = $input['apilayer_api_id'];
		$valid['api_selection'] = $input['api_selection'];
		$valid['retrieval_count'] = intval($input['retrieval_count']);
		($input['autocomplete']=="1"||$input['autocomplete']=="on")? $valid['autocomplete'] = "on":$valid['autocomplete'] = "off";
		($input['autoprocessing']=="1"||$input['autoprocessing']=="on")? $valid['autoprocessing'] = "on":$valid['autoprocessing'] = "off";
		($input['email_order_completed_note']=="1"||$input['email_order_completed_note']=="on")? $valid['email_order_completed_note'] = "on":$valid['email_order_completed_note'] = "off";
		$valid['order_email_note'] = $input['order_email_note'];
		$valid['precision'] = $input['precision'];
		$valid['handling_percentage'] = $input['handling_percentage'];
		$valid['handling_amount'] = $input['handling_amount'];
		($input['handling_taxable']=="1"||$input['handling_taxable']=="on")? $valid['handling_taxable'] = "on":$valid['handling_taxable'] = "off";
		($input['shipping_handling_fee']=="1"||$input['shipping_handling_fee']=="on")? $valid['shipping_handling_fee'] = "on":$valid['shipping_handling_fee'] = "off";
		$valid['handling_title'] = $input['handling_title'];

		// Logs
		if ( 'on' == $valid['exrlog'] ){
			$this->logging($valid['target_currency'].$valid['conversion_rate']);
		}

		return $valid;

	}


	public function logging($msg) {
		global $woocommerce;
		$this->log =  new WC_Logger();
		$this->log->add( 'ppcc', $msg);
	}


	public function ppcc_calculate_totals($totals){
		global $woocommerce;


		$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();


		$current_gateway = '';
		if (!empty($available_gateways) && !is_cart())
			{

			// Check pament gateway

			if (isset($woocommerce->session->chosen_payment_method) && isset($available_gateways[$woocommerce->session->chosen_payment_method]))
				{
				$current_gateway = $available_gateways[$woocommerce->session->chosen_payment_method];
				}
			elseif (isset($available_gateways[get_option('woocommerce_default_gateway') ]))
				{
				$current_gateway = $available_gateways[get_option('woocommerce_default_gateway') ];
				}
			  else
				{
				$current_gateway = current($available_gateways);
				}
			}

		if (strpos(strtolower($current_gateway->title), 'paypal')!== false)
			{
					
				$options = get_ppcc_option('ppcc-options');



			if ($options['handling_percentage']> 0 || $options['handling_amount']> 0)
				{
					if($options['shipping_handling_fee'] == 'on')//handling fee calculation including shipping
					{
						$percentage_fee = ($totals->cart_contents_total + $totals->shipping_total) * $options['handling_percentage']/ 100;
						$handling_fee = ($totals->cart_contents_total + $totals->shipping_total)  * $options['handling_percentage']/ 100 + $options['handling_amount'];
					}
					else //handling fee calculation without shipping
					{
						$percentage_fee = $totals->cart_contents_total * $options['handling_percentage']/ 100;
						$handling_fee = $totals->cart_contents_total * $options['handling_percentage']/ 100 + $options['handling_amount'];
					}
				
					$totals->cart_contents_total += $handling_fee;

					if ($options['handling_percentage']> 0 && $options['handling_amount']> 0)
					{
						$handling_fee_title =  $options['handling_title'].' ('.$options['handling_percentage'].'% + '.get_woocommerce_currency_symbol(get_option('woocommerce_currency')).$options['handling_amount'].__(' fixed', 'ppcc'). ')';
					}
					elseif ($options['handling_percentage']> 0) {
						$handling_fee_title = $options['handling_percentage'].'% '. $options['handling_title'];
					}
					else
					{
						$handling_fee_title =  $options['handling_title'];
					}
					
					// $options[handling_taxable] == 'on'?true:false;
					$woocommerce->cart->add_fee( $handling_fee_title , $handling_fee , ($options['handling_taxable'] == 'on'?true:false) , 'standard');

				}

		}

		return $totals;
	}


//class
}
?>