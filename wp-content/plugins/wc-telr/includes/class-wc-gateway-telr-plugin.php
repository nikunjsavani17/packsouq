<?php
/*
 * Telr plugin for woocommercee
*/

//directory access forbidden
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Telr_Plugin
{
    const DEPENDENCIES_UNSATISFIED  = 1;

    public function __construct($file)
    {
        $this->file = $file;

    // Path.
        $this->plugin_path   = trailingslashit(plugin_dir_path($this->file));
        $this->plugin_url    = trailingslashit(plugin_dir_url($this->file));
        $this->plugin_url    = trailingslashit(plugin_dir_url($this->file));
        $this->includes_path = $this->plugin_path . trailingslashit('includes');
    }

    /**
     * Maybe run the plugin.
    */
    public function maybe_run()
    {
        register_activation_hook($this->file, array($this, 'activate'));
        add_action('plugins_loaded', array($this, 'bootstrap'));
        add_filter('plugin_action_links_' . plugin_basename($this->file), array($this, 'plugin_action_links'));
        add_action('pre_get_posts', array($this, 'ivp_check_function'));

        add_action('init', array($this, 'init_ssl_check'));

    }

    public function bootstrap()
    {
        try {
            $this->_check_dependencies();
            $this->_run();
            delete_option('wc_gateway_telr_bootstrap_warning_message');
        } catch (Exception $e) {
            if (in_array($e->getCode(), array(self::DEPENDENCIES_UNSATISFIED))) {

                update_option('wc_gateway_telr_bootstrap_warning_message', $e->getMessage());
            }

            add_action('admin_notices', array($this, 'show_bootstrap_warning'));
        }
    }

    protected function _check_dependencies()
    {
        if (!function_exists('WC')) {
            throw new Exception(__('Telr Secure payments for WooCommerce requires WooCommerce to be activated', 'telr-for-woocommerce'), self::DEPENDENCIES_UNSATISFIED);
        }

        if (version_compare(WC()->version, '3.0', '<')) {
            throw new Exception(__('Telr Secure payments for WooCommerce requires WooCommerce version 3.0 or greater', 'telr-for-woocommerce'), self::DEPENDENCIES_UNSATISFIED);
        }

        if (!function_exists('curl_init')) {
            throw new Exception(__('Telr Secure payments for WooCommerce requires cURL to be installed on your server', 'telr-for-woocommerce'), self::DEPENDENCIES_UNSATISFIED);
        }
    }

    public function show_bootstrap_warning()
    {
        $dependencies_message = get_option('wc_gateway_telr_bootstrap_warning_message', '');
        if (!empty($dependencies_message)) {
            ?>
            <div class="error fade">
                <p>
                    <strong><?php echo esc_html($dependencies_message); ?></strong>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Run the plugin.
     */
    protected function _run()
    {
        $this->_load_handlers();
    }

    protected function _load_handlers()
    {

        // Load handlers.
        require_once($this->includes_path . 'class-wc-gateway-telr-settings.php');
        require_once($this->includes_path . 'class-wc-gateway-telr-gateway-loader.php');
        require_once($this->includes_path . 'class-wc-gateway-telr-admin-handler.php');
        require_once($this->includes_path . 'class-wc-gateway-telr-checkout-handler.php');

        $this->settings       = new WC_Gateway_Telr_Settings();
        $this->gateway_loader = new WC_Gateway_telr_Gateway_Loader();
        $this->admin          = new WC_Gateway_Telr_Admin_Handler();
        $this->checkout       = new WC_Gateway_Telr_Checkout_Handler();
    }

    /**
     * Callback for activation hook.
     */
    public function activate()
    {
        if (!isset($this->setings)) {
            require_once($this->includes_path . 'class-wc-gateway-telr-settings.php');
            $this->settings = new WC_Gateway_Telr_Settings();
        }
        return true;
    }

    public function plugin_action_links($links)
    {
        $plugin_links = array();

        $setting_url = $this->get_admin_setting_link();
        $plugin_links[] = '<a href="' . esc_url($setting_url) . '">' . esc_html__('Settings', 'wctelr') . '</a>';


        return array_merge($plugin_links, $links);
    }

    /**
     * Link to settings screen.
     */
    public function get_admin_setting_link()
    {
        return admin_url('admin.php?page=wc-settings&tab=checkout&section=wctelr');
    }


    /**
     *  Endpoint to handle Transaction Advice Service Requests.
     */
    public function ivp_check_function($query)
    {
        if ($query->is_main_query()) {
            if (isset($query->query['pagename'])) {
                $pagename = $query->query['pagename'];
                if ($pagename == 'ivpcallback' && isset($_GET['cart_id']) && !empty($_GET['cart_id'])) {
                    // proceed to update order payment details:
                    $cartIdExtract = explode("_", $_POST['tran_cartid']);
                    $order_id = $cartIdExtract[0];
                    
                    $cart_id = get_post_meta($order_id, '_telr_cartid', true);
                    if ($cart_id == $_GET['cart_id'] and $cart_id = $_POST['tran_cartid']) {
                        try {
                            $order = new WC_Order($order_id);
                            //checking for default order status. If set, apply the default
                            $default_order_status = wc_gateway_telr()->settings->__get('default_order_status');
                            if ($default_order_status !== 'none') {
                                $order->update_status($default_order_status);
                                return;
                            }
                            
                            $tranType = $_POST['tran_type'];
                            $tranStatus = $_POST['tran_authstatus'];

                            if ($tranStatus == 'A') {
                                switch ($tranType) {
                                    case '1':
                                    case '4':
                                    case '7':
                                        $order->payment_complete();
                                        break;

                                    case '2':
                                    case '6':
                                    case '8':
                                        $newOrderStatus = 'cancelled';
                                        $order->update_status($newOrderStatus);
                                        break;

                                    case '3':
                                        $newOrderStatus = 'refunded';
                                        $order->update_status($newOrderStatus);
                                        break;

                                    default:
                                        // No action defined
                                        break;
                                }
                            }
                        } catch (Exception $e) {
                            // Error Occurred While processing request.
                             die('Error Occurred While processing request');
                        }
                    } else {
                         die('Cart id mismatch');
                    }
                    
                    exit;
                }
            }
        }
    }

    public function init_ssl_check()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO']) {
            $_SERVER['HTTPS'] = 'on';
        }
    }
}
