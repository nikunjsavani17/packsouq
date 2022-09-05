<?php
/**
* Checkout handler class.
*/

//directory access forbidden
if (!defined('ABSPATH')) {
    exit;
}
$includes_path = wc_gateway_telr()->includes_path;
require_once($includes_path. 'abstracts/abstract-wc-gateway-telr.php');

class WC_Gateway_Telr_Checkout_Handler
{
    public function __construct()
    {
        $this->telr_payment_gateway = new WC_Telr_Payment_Gateway();
        $this->payment_mode = wc_gateway_telr()->settings->__get('payment_mode');
    }
    
    /*
    * Process payment for checkout
    *
    * @param order id (int)
    * @access public
    * @return array
    */
    public function process_payment($order_id)
    {
        $order    = new WC_Order($order_id);
        $result   = $this->generate_request($order);
        
        $telr_ref = trim($result['order']['ref']);
        $telr_url = trim($result['order']['url']);


        if (empty($telr_ref) || empty($telr_url)) {
            $error_message = "Payment has been failed, Please try again.";
            if (isset($result['error']) and !empty($result['error'])) {
                $error_message = $result['error']['message'].'.';
                $error_message = str_replace('E05', 'Error', $error_message);
            }
            
            wc_add_notice($error_message, 'error');
            return array(
                'result'    => 'failure',
                'redirect'  => false,
            
            );
        }
        
        update_post_meta( $order_id, '_telr_ref', $telr_ref);
        
        if(is_ssl() && $this->payment_mode == 2) {
            if (get_post_meta($order_id, '_telr_url')) {
                delete_post_meta( $order_id, '_telr_url');
            }
            add_post_meta( $order_id, '_telr_url', $telr_url);
            $telr_url = $order->get_checkout_payment_url(true);
        }
        
        return array(
            'result'    => 'success',
            'redirect'  => $telr_url,
            
        );
    }
    
    
    /*
    * api request to telr server
    *
    * @parem request data(array)
    * @access public
    * @return array
    */
    public function api_request($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://secure.telr.com/gateway/order.json');
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        $results = curl_exec($ch);
        curl_close($ch);
        $results = json_decode($results, true);
        return $results;
    }
    
    
    /*
    * generate iframe when framed display is selected
    *
    * @parem @param order id (int)
    * @access public
    * @return array
    */
    public function receipt_page($order_id)
    {
        $payment_url = get_post_meta($order_id, '_telr_url', true);
        $style = '#telr {width: 100%; min-width: 600px; height: 600px; border: none;}';
        $style .= ".order_details {display: none;}";
        echo "<style>$style</style>";
        echo ' <iframe id= "telr" src= "'.$payment_url.'" ></iframe>';
    }
    
        
    /*
    * generate request for api request
    *
    * @parem @param order id (int)
    * @access public
    * @return array
    */
    private function generate_request($order)
    {
        global $woocommerce;

        $order_id = $order->id;

        $cart_id   = $order_id."_".uniqid();
        if (get_post_meta($order_id, '_telr_cartid')) {
            delete_post_meta($order_id, '_telr_cartid');
        }
        add_post_meta($order_id, '_telr_cartid', $cart_id);
        
        $cart_desc = trim(wc_gateway_telr()->settings->__get('cart_desc'));
        if (empty($cart_desc)) {
            $cart_desc ='Order {order_id}';
        }
        $cart_desc  = preg_replace('/{order_id}/i', $order_id, $cart_desc);

        $test_mode  = (wc_gateway_telr()->settings->__get('testmode') == 'yes') ? 1 : 0;
        if (!is_ssl() && $this->payment_mode == '2') {
            $this->payment_mode = 0;
        }
        
        $return_url = 'auto:'.add_query_arg('utm_nooverride', '1', $this->telr_payment_gateway->get_return_url($order));
        $cancel_url = 'auto:'.$order->get_cancel_order_url();

        $data = array(
            'ivp_method'      => "create",
            'ivp_source'      => 'WooCommerce '.$woocommerce->version,
            'ivp_store'       => wc_gateway_telr()->settings->__get('store_id') ,
            'ivp_authkey'     => wc_gateway_telr()->settings->__get('store_secret'),
            'ivp_cart'        => $cart_id,
            'ivp_test'        => $test_mode,
            'ivp_framed'      => "$this->payment_mode",
            'ivp_amount'      => $order->order_total,
            'ivp_lang'        => wc_gateway_telr()->settings->__get('language'),
            'ivp_currency'    => get_woocommerce_currency(),
            'ivp_desc'        => $cart_desc,
            'return_auth'     => $return_url,
            'return_can'      => $cancel_url,
            'return_decl'     => $cancel_url,
            'bill_fname'      => $order->billing_first_name,
            'bill_sname'      => $order->billing_last_name,
            'bill_addr1'      => $order->billing_address_1,
            'bill_addr2'      => $order->billing_address_2,
            'bill_city'       => $order->billing_city,
            'bill_region'     => $order->billing_state,
            'bill_zip'        => $order->billing_postcode,
            'bill_country'    => $order->billing_country,
            'bill_email'      => $order->billing_email,
            'bill_tel'        => $order->billing_phone,
            'ivp_update_url'  => get_site_url() . "/ivpcallback?cart_id=" . $cart_id,
            );

        if (is_ssl() && is_user_logged_in()) {
            $data['bill_custref'] = get_current_user_id();
        }
        
        $response = $this->api_request($data);
        return $response;
    }
}
