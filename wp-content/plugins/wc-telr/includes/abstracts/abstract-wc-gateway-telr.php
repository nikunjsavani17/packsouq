<?php
/**
 * Telr Payment gateway .
 */

//directory access forbidden
if (!defined('ABSPATH')) {
    exit;
}

class WC_Telr_Payment_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->has_fields             = false;  // No additional fields in checkout page
        $this->method_title           = __('Telr', 'wctelr');
        $this->method_description     = __('Telr Checkout', 'wctelr');
        $this->order_button_text      = __('Proceed to Telr', 'wctelr');
    
        // Load the settings.
        $this->init_form_fields();
        
        // Configure page fields
        $this->init_settings();

        $preload = '<iframe style="width:1px;height:1px;visibility:hidden;display:none;" src="https://secure.telrcdn.com/preload.html"></iframe>';
        $this->enabled              = wc_gateway_telr()->settings->__get('enabled');
        $this->title                = wc_gateway_telr()->settings->__get('title');
        $this->description          = wc_gateway_telr()->settings->__get('description').$preload;
        $this->store_id             = wc_gateway_telr()->settings->__get('store_id');
        $this->store_secret         = wc_gateway_telr()->settings->__get('store_secret');
        $this->testmode             = wc_gateway_telr()->settings->__get('testmode');
        $this->debug                = wc_gateway_telr()->settings->__get('debug');
        $this->order_status         = wc_gateway_telr()->settings->__get('order_status');
        $this->cart_desc            = wc_gateway_telr()->settings->__get('cart_desc');
        $this->payment_mode         = wc_gateway_telr()->settings->__get('payment_mode');
        $this->language             = wc_gateway_telr()->settings->__get('language');
        $this->default_order_status = wc_gateway_telr()->settings->__get('default_order_status');
        
        //actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array( $this, 'receipt_page'));
        add_action('woocommerce_thankyou', array($this, 'update_order_status'));
    }
    
    
    /*
    * show telr settings in woocommerce checkout settings
    */
    public function admin_options()
    {
        if (wc_gateway_telr()->admin->is_valid_for_use()) {
            $this->show_admin_options();
            return true;
        }
        
        wc_gateway_telr()->settings->__set('enabled', 'no');
        wc_gateway_telr()->settings->save();
        ?>
        <div class="inline error"><p><strong><?php _e('Gateway disabled', 'wctelr'); ?></strong>: <?php _e('Telr Payments does not support your store currency.', 'wctelr'); ?></p></div>
        <?php
    }
    
    public function show_admin_options()
    {
    ?>
        <h3><?php _e('Telr', 'wctelr'); ?></h3>
        <div id="wc_get_started">
            <span class="main"><?php _e('Telr Hosted Payment Page', 'wctelr'); ?></span>
            <span><a href="https://www.telr.com/" target="_blank">Telr</a> <?php _e('are a PCI DSS Level 1 certified payment gateway. We guarantee that we will handle the storage, processing and transmission of your customer\'s cardholder data in a manner which meets or exceeds the highest standards in the industry.', 'wctelr'); ?></span>
            <span><br><b>NOTE: </b> You must enter your store ID and authentication key</span>
        </div>

        <table class="form-table">
        <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }
    
    
    /*
    *Update order status on return
    *
    * @access public
    * @param (int)order id
    * @return void
    */
    public function update_order_status($order_id)
    {
        $order        = new WC_Order($order_id);
        $order_status = $this->default_order_status;
        
        //checking for default order status. If set, apply the default
        if ($this->default_order_status == 'none') {
            $order_status = $this->check_order($order_id);
        }
        
        if ($order_status == 'processing') {
            $order->payment_complete();
        } else {
            $order->update_status($order_status);
        }
    }


    /**
    * Process the payment and return the result.
    *
    * @access public
    * @param (int)order id
    * @return array
    */
    public function process_payment($order_id)
    {
        return wc_gateway_telr()->checkout->process_payment($order_id);
    }

    
    /**
    * check order status.
    *
    * @access public
    * @param (int)order id
    * @return bool
    */
    public function check_order($order_id)
    {
        $order_ref = get_post_meta($order_id, '_telr_ref', true);

        $data = array(
            'ivp_method'  => "check",
            'ivp_store'   => $this->store_id ,
            'order_ref'   => $order_ref,
            'ivp_authkey' => $this->store_secret,
            );

        $response = wc_gateway_telr()->checkout->api_request($data);
        
        if (array_key_exists("order", $response)) {
            $order_status       = $response['order']['status']['code'];
            $transaction_status = $response['order']['transaction']['status'];
            if ($transaction_status == 'A') {
                switch ($order_status) {
                    case '3':
                        return 'processing';
                        break;

                    case '-1':
                        return 'failed';
                        break;

                    case '-2':
                        return 'cancelled';
                        break;
                        
                    case '-3':
                        return 'cancelled';
                        break;

                    default:
                        // No action defined
                        break;
                }
            }
            if ($transaction_status == 'H') {
                switch ($order_status) {
                    case '2':
                        return 'on-hold';
                        break;

                    default:
                        // No action defined
                        break;
                }
            }
        }
        return 'pending';
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
        wc_gateway_telr()->checkout->receipt_page($order_id);
    }
    
    
    /**
     * initialize Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = wc_gateway_telr()->admin->init_form_fields();
    }
}