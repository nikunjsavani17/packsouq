<?php 

class Wcap_Process_Base {
    
    /**
     * @var WCAP_Background_Process
     */
    protected $process;
    
    /**
     * @var WCAP_Async_Request
     */
    protected $request;
    
    
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        
        $wcap_auto_cron = get_option ( 'wcap_use_auto_cron' );
        if ( isset( $wcap_auto_cron ) && $wcap_auto_cron != false && '' != $wcap_auto_cron ) {
            // Hook into that action that'll fire every 5 minutes
            //    add_action( 'woocommerce_ac_send_email_action',        array( 'Wcap_Send_Email_Using_Cron', 'wcap_abandoned_cart_send_email_notification' ) );
            //    add_action( 'woocommerce_ac_send_email_action',        array( 'Wcap_Send_Email_Using_Cron', 'wcap_send_sms_notifications' ), 11 );
            add_action( 'woocommerce_ac_send_email_action',        array( &$this, 'wcap_process_handler' ), 11 );
        }
    }
    
    public function init() {
        
        require_once plugin_dir_path( __FILE__ ) . 'wcap-async-request.php';
        require_once plugin_dir_path( __FILE__ ) . 'wcap-background-process.php';
        
        $this->request    = new WCAP_Async_Request();
        $this->process    = new WCAP_Background_Process();
        
    } 
    
    public function wcap_process_handler() {

    /*    if ( ! isset( $_GET['process'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }
        
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'process') ) {
            return;
        }
        */
        
     /*   if ( 'single' === $_GET['process'] ) {
            $this->handle_single();
        } 
        
        if ( 'all' === $_GET['process'] ) {
            $this->handle_all();
        } */
        // check if reminders are enabled
        $reminders_list = wcap_get_enabled_reminders();
//        wp_mail( 'pinalj1612@gmail.com','reminders list action',print_r($reminders_list,true ));
        if( is_array( $reminders_list ) && count( $reminders_list ) > 0 ) {
            $this->start( $reminders_list );
        }
        
    }
    
    public function start( $reminders_list ) {
        
 /*       global $wpdb;
        
        $list_reminders = array();
        // get the carts for SMS & FB
        $list_ids = wcap_get_notification_meta_by_key( 'to_be_sent_cart_ids' );
        
        foreach( $list_ids as $lists ) {
            
            $template_id = $lists->template_id;
            $meta_value = $lists->meta_value; 
            
            // check if the template is active
            $template_details = $wpdb->get_results( $wpdb->prepare( "SELECT is_active, type FROM `" . WCAP_NOTIFICATIONS . "` WHERE id = %d", $template_id ) );
            
            $active = $template_details[0]->is_active;
            $template_type = $template_details[0]->type;
            
            // if active, gather the list in an array
            if( '1' == $active && in_array( $template_type, $reminders_list ) ) {
                $explode_carts = explode( ',', $meta_value );
                
                $list_reminders[ $template_type ][ $template_id ] = $explode_carts;
            }
        } 
        wp_mail( 'pinalj1612@gmail.com','List of reminders',print_r($list_reminders,true )); */
        $this->handle_all( $reminders_list ); 
        
    }
    public function handle_single() {
        
    }
    
    public function handle_all( $list_reminders ) {
//     wp_mail( 'pinalj1612@gmail.com','HANDLE',print_r($list_reminders,true ));
/*        $this->process->push_to_queue( $list_reminders );
        $this->process->save();
        wp_mail( 'pinalj1612@gmail.com',"Pushed",print_r( $this->process->data, true ));
        $this->process->dispatch();
  */

   /*     foreach( $list_reminders as $key => $value ) {
             
            foreach( $value as $template_id => $cart_id ) {
                 
                $details = array( 'type'    => $key,
                    'id'      => $template_id,
                    'cart_id' => $cart_id
                );
                wp_mail( 'pinalj1612@gmail.com',"Foreach $key", print_r($details,true ));
                $this->process->push_to_queue( $details );
            }
        }
     */

     foreach( $list_reminders as $reminders ) {
         $this->process->push_to_queue( $reminders );
     }
        $this->process->save()->dispatch();
    }
    
}
new Wcap_Process_Base();
?>