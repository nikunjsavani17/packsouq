<?php
/**
 * It will delete the abandoned cart if order is placed before the cutoff time.
 * It will also, create the post meta for the abandoned cart. It will create after the cutoff time.
 * @author   Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Frontend/Place-Order
 * @since 5.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if ( !class_exists('WCAP_On_Placed_Order' ) ) {
	/**
	 * It will delete the abandoned cart if order is placed before the cutoff time.
	 * It will also, create the post meta for the abandoned cart. It will create after the cutoff time.
	 * Also, when order status is changes it will check if abandoned cart needs to delete or keep it.
	 */
  class WCAP_On_Placed_Order {
	/**
	 * It will delete the abandoned cart if order is placed before the cutoff time.
	 * It will also, create the post meta for the abandoned cart. It will create after the cutoff time.
	 * This post meta contain the abandoned cart id and if the email is sent to that cart then the email sent id.
	 * @hook woocommerce_checkout_order_processed
	 * @param int | string $order_id Order id
	 * @globals mixed $wpdb
	 * @since 5.0
	 */
    public static function wcap_order_placed( $order_id ) {

        global $wpdb;
        $email_sent_id         = wcap_get_cart_session( 'wcap_email_sent_id' );
        $abandoned_order_id    = wcap_get_cart_session( 'wcap_abandoned_id' );
        $wcap_user_id_of_guest = wcap_get_cart_session( 'wcap_user_id' );

        $abandoned_order_id_to_save = $abandoned_order_id;
        if( $email_sent_id != '' && $email_sent_id > 0 ) { // recovered cart
        
            if( $abandoned_order_id == '' || $abandoned_order_id == false ) {
            
                $get_ac_id_query    = "SELECT abandoned_order_id FROM `" . WCAP_EMAIL_SENT_HISTORY_TABLE ."` WHERE id = %d";
                $get_ac_id_results  = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query, $email_sent_id ) );
            
                $abandoned_order_id_to_save = $get_ac_id_results[0]->abandoned_order_id;
            }
            
            $wcap_account_password_check = 'no';

            // if user becomes the registered user
            if ( isset( $_POST['account_password'] ) && $_POST['account_password'] != '' ) {
                if ( $abandoned_order_id != '' && $wcap_user_id_of_guest != '' ) {
                    $abandoned_cart_id_new_user = $abandoned_order_id;

                    // delete the guest record. As it become the logged in user
                    $get_ac_id_guest_query    = "SELECT id FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE ."` WHERE user_id = %d ORDER BY id DESC";
                    $get_ac_id_guest_results  = $wpdb->get_results( $wpdb->prepare( $get_ac_id_guest_query, $wcap_user_id_of_guest ) );

                    if ( count ($get_ac_id_guest_results) >= 1 ){
                        $abandoned_order_id_of_guest = $get_ac_id_guest_results[0]->id;
                        $wpdb->delete( WCAP_ABANDONED_CART_HISTORY_TABLE , array( 'id' => $abandoned_order_id_of_guest ) );
                    }
                    // it is the new registered users cart id
                    $abandoned_order_id_to_save = $abandoned_cart_id_new_user;
            
                    $wcap_account_password_check = 'yes';
            
                }
            }
            
            $wcap_create_account = 'no';
            // if user becomes the registred user
            if ( isset( $_POST['createaccount'] ) && $_POST['createaccount'] != '' && 'no' == $wcap_account_password_check ) {
            
                if ( $abandoned_order_id != '' && $wcap_user_id_of_guest != '' ) {
                    $abandoned_cart_id_new_user = $abandoned_order_id;
            
                    // delete the guest record. As it become the logged in user
                    $get_ac_id_guest_query    = "SELECT id FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE ."` WHERE user_id = %d ORDER BY id DESC";
                    $get_ac_id_guest_results  = $wpdb->get_results( $wpdb->prepare( $get_ac_id_guest_query, $wcap_user_id_of_guest ) );
            
                    if ( count ($get_ac_id_guest_results) > 1 ){
                        $abandoned_order_id_of_guest = $get_ac_id_guest_results[0]->id;
                        $wpdb->delete( WCAP_ABANDONED_CART_HISTORY_TABLE , array( 'id' => $abandoned_order_id_of_guest ) );
                    }
            
                    // it is the new registered users cart id */
                    $abandoned_order_id_to_save = $abandoned_cart_id_new_user;
            
                    $wcap_create_account = 'yes';
                }
            }
            
            add_post_meta( $order_id , 'wcap_recover_order_placed_sent_id', $email_sent_id );
            add_post_meta( $order_id , 'wcap_recover_order_placed', $abandoned_order_id );
            
        }else if ( $abandoned_order_id != '' ){

            if ( isset( $_POST['account_password'] ) && $_POST['account_password'] != '' ) {
            
                if ( $abandoned_order_id != '' && $wcap_user_id_of_guest != '' ) {
                    $abandoned_cart_id_new_user = $abandoned_order_id;
            
                    // delete the guest record. As it become the logged in user
                    $wpdb->delete( WCAP_ABANDONED_CART_HISTORY_TABLE , array( 'user_id' => $wcap_user_id_of_guest ) );
                    $wpdb->delete( WCAP_GUEST_CART_HISTORY_TABLE ,     array( 'id' => $wcap_user_id_of_guest ) );
            
                    // it is the new registered users cart id
                    $abandoned_order_id_to_save = $abandoned_cart_id_new_user;
                }
            }elseif ( isset( $_POST['createaccount'] ) && $_POST['createaccount'] != '' ) {
                if ( $abandoned_order_id != '' && $wcap_user_id_of_guest != '' ) {
                    $abandoned_cart_id_new_user = $abandoned_order_id;
            
                    // delete the guest record. As it become the logged in user
                    $wpdb->delete( WCAP_ABANDONED_CART_HISTORY_TABLE , array( 'user_id' => $wcap_user_id_of_guest ) );
                    $wpdb->delete( WCAP_GUEST_CART_HISTORY_TABLE ,     array( 'id' => $wcap_user_id_of_guest ) );
            
                    // it is the new registered users cart id
                    $abandoned_order_id_to_save = $abandoned_cart_id_new_user;
                }
            }
        }

        add_post_meta( $order_id, 'wcap_abandoned_cart_id', $abandoned_order_id_to_save );
	}
	
	/**
	 * Deletes Abandoned Cart records once order payment is completed
	 * 
	 * @param integer $order_id - WC Order ID
	 * @param string $wc_old_status - Old WC Order Status
	 * @param string $wc_new_status - New WC Order Status
	 * 
	 * @since 7.11.0
	 * @hook woocommerce_order_status_changed
	 */
	public static function wcap_cart_details_update( $order_id, $wc_old_status, $wc_new_status ) {
	
	    if( 'pending' != $wc_new_status &&
	        'failed' != $wc_new_status &&
	        'cancelled' != $wc_new_status &&
	        'trash' != $wc_new_status ) {
	
	        global $wpdb;
	
	        if( $order_id > 0 ) {
	            $get_abandoned_id_of_order  = get_post_meta( $order_id, 'wcap_recover_order_placed', true );
	
	            if( $get_abandoned_id_of_order > 0 || wcap_get_cart_session( 'wcap_email_sent_id' ) != '' ) { // recovered order
	
	            } else {
	
	                $wcap_abandoned_id = get_post_meta( $order_id, 'wcap_abandoned_cart_id', true );

	                // check if it's a guest cart
	                $query_cart_data = "SELECT user_id, user_type FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "`
                                        WHERE id = %d";
	                $get_cart_data = $wpdb->get_results( $wpdb->prepare( $query_cart_data, $wcap_abandoned_id ) );
	
	                if( is_array( $get_cart_data ) && count( $get_cart_data ) > 0 ) {
	                    $user_type = $get_cart_data[0]->user_type;
	                    $user_id = $get_cart_data[0]->user_id;
	
	                    if( 'GUEST' == $user_type && $user_id >= 63000000 ) {
	                        $wpdb->delete( WCAP_GUEST_CART_HISTORY_TABLE ,  array( 'id' => $user_id ) );
	                    }
	                }
	                $wpdb->delete( WCAP_ABANDONED_CART_HISTORY_TABLE , array( 'id' => $wcap_abandoned_id ) );
	
	                // remove the cart ID from the list to which SMS reminders will be sent
	                Wcap_Common::wcap_delete_cart_notification( $wcap_abandoned_id );
	            }
	        }
	    }
	
	}
	/**
	 * When an order status is changed we check the order status, if the status is pending or falied then we consider that cart as an abandoned.
	 * Apart from the pending and falied we delete the abandnoned cart.
	 * @hook woocommerce_payment_complete_order_status
	 * @param string $woo_order_status New order status
	 * @param int | string $order_id Order id
	 * @return string $woo_order_status
	 * @globals mixed $wpdb
	 * @since 5.0
	 * 
	 */
    public static function wcap_order_complete_action( $woo_order_status, $order_id ) {

        $order = new WC_Order( $order_id );

        $get_abandoned_id_of_order  = get_post_meta( $order_id, 'wcap_recover_order_placed', true );
        $get_sent_email_id_of_order = get_post_meta( $order_id, 'wcap_recover_order_placed_sent_id', true );

        if( 'pending' != $woo_order_status && 
            'failed' != $woo_order_status && 
            'cancelled' != $woo_order_status && 
            'trash' != $woo_order_status ) {

          if ( isset( $get_sent_email_id_of_order ) && '' != $get_sent_email_id_of_order ) {
  
              wcap_common::wcap_updated_recovered_cart( $get_abandoned_id_of_order, $order_id, $get_sent_email_id_of_order, $order );
          }
        }

        // Order Status passed in the function is either 'processing' or 'complete' and may or may not reflect the actual order status.
        // Hence, always use the status fetched from the order object.
        
        $order_status = ( $order ) ? $order->get_status() : '';
        
        if ( 'pending' != $order_status && 'failed' != $order_status && 'cancelled' != $order_status && 'trash' != $order_status) {
            global $wpdb;
    
            if ( isset( $get_abandoned_id_of_order ) && '' != $get_abandoned_id_of_order ){
                        
              $get_abandoned_cart_user_id_query   = "SELECT user_id FROM " . WCAP_ABANDONED_CART_HISTORY_TABLE . " WHERE id = %d ";
              $get_abandoned_cart_user_id_results = $wpdb->get_results( $wpdb->prepare( $get_abandoned_cart_user_id_query, $get_abandoned_id_of_order ) );
    
              $var = $wpdb->prepare( $get_abandoned_cart_user_id_query, $get_abandoned_id_of_order );
              
              if ( count( $get_abandoned_cart_user_id_results ) > 0 ){
                $wcap_user_id = $get_abandoned_cart_user_id_results[0]->user_id;
    
                if ( $wcap_user_id >= 63000000 ){
                  $wpdb->delete( WCAP_GUEST_CART_HISTORY_TABLE,   array( 'id' => $wcap_user_id ) );
                }
    
                $wpdb->delete( WCAP_ABANDONED_CART_HISTORY_TABLE, array( 'id' => $get_abandoned_id_of_order ) );
                delete_post_meta( $order_id,  'wcap_recover_order_placed', $get_abandoned_id_of_order );
              }
            }
        }
        return $woo_order_status;
    }
    
    /**
     * Updates cart status to 'Abandoned - Order Unpaid'
     * when the order is cancelled by WooCommerce once
     * Hold Stock Limit is reached.
     *
     * @param string $created_via - From where the order has been created.
     * @param WC_order $order - Order Object
     * @return string $created_via
     * @global mixed $wpdb
     * 
     * @since 7.7
     * @hook woocommerce_cancel_unpaid_order
     */
    static function wcap_update_cart_status( $created_via, $order ) {
        global $wpdb;
    
        $order_id = ( $order ) ? $order->get_id() : 0;
    
        if( isset( $order_id ) && $order_id > 0 ) {
            $abandoned_id  = get_post_meta( $order_id, 'wcap_abandoned_cart_id', true );
    
            if( isset( $abandoned_id ) && $abandoned_id > 0 ) {
                $update_data = array( 'cart_ignored' => 2 );
    
                $wpdb->update( WCAP_ABANDONED_CART_HISTORY_TABLE, $update_data, array( 'id' => $abandoned_id ) );
            }
        }
        return $created_via;
    }
  }
}
