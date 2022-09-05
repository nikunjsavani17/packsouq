<?php
/**
 * This class will add messages as needed informing users of data being tracked.
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Classes
 * @since 7.8
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if ( !class_exists('Wcap_Tracking_msg' ) ) {

    /**
     * It will add messages as needed informing users of data being tracked.
     */
    class Wcap_Tracking_msg {
        
        public function __construct() {
            // Checkout page notice for guest users
            add_filter( 'woocommerce_checkout_fields' , array( &$this, 'wcap_add_gdpr_msg' ), 10, 1 );
            // Product page notice for logged in users
            add_action( 'woocommerce_after_add_to_cart_button', array( &$this, 'wcap_add_logged_msg' ), 10 );
            // Shop Page notice
            add_action( 'woocommerce_before_shop_loop', array( &$this, 'wcap_add_logged_msg' ), 10 );
        }
        
        /**
         * Adds a message to be displayed above Billing_email
         * field on Checkout page for guest users.
         * 
         * @param array $fields - List of fields on Checkout page
         * @return array $fields - List of fields on Checkout page
         * 
         * @hook woocommerce_checkout_fields
         * @since 7.8
         */
        static function wcap_add_gdpr_msg( $fields ) {
            
            if( ! is_user_logged_in() ) {
                // check if any message is present in the settings
                $guest_msg = get_option( 'wcap_guest_cart_capture_msg' );
                
                if( isset( $guest_msg ) && '' != $guest_msg ) {
                    $existing_label = $fields[ 'billing' ][ 'billing_email' ][ 'label' ];
                    $fields[ 'billing' ][ 'billing_email' ][ 'label' ] = $existing_label . "<br><small>$guest_msg</small>";
                }
            }
            return $fields;
        }
        
        /**
         * Adds a message to be displayed for logged in users
         * Called on Shop & Product page
         * 
         * @hook woocommerce_after_add_to_cart_button
         *       woocommerce_before_shop_loop
         * @since 7.8      
         */
        static function wcap_add_logged_msg() {
            if( is_user_logged_in() ) {
                
                $registered_msg = get_option( 'wcap_logged_cart_capture_msg' );
                
                if( isset( $registered_msg ) && '' != $registered_msg ) {
                    echo "<p><small>" . __( $registered_msg, 'woocommerce-ac' ) . "</small></p>";
                }
            }
        }
        
    } // end of class
    $Wcap_Tracking_msg = new Wcap_Tracking_msg();
} // end IF
