<?php
/**
 * This class is responsible for sending the abandoned cart reminder emails to the customers.
 * Also it is generating the coupon code if needed in the template.
 * This if condition is used to identify that the wcap_send_mail.php file has been called directly from the cron job.
 * So it will prevent the below code when this file is called from the required_once.
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Cron
 * @since    5.0
 */

/**
 * It will work when admin has added the cron job from the cPanel and disabled the auto cron setting in our plugin.
 * It will load the wp-load.php file. It will allow to access all WordPress functions.
 */
if( basename(__FILE__) == basename( $_SERVER[ "SCRIPT_FILENAME" ] ) ) {
    static $wp_load; // Since this will be called twice, hold onto it.
    if( ! file_exists( $wp_load = ABSPATH . "/wp-load.php" ) ) {
        $wp_load    = false;
        $dir        = __FILE__;
        while( '/' != ( $dir = dirname( $dir ) ) ) {
            if( file_exists( $wp_load = "{$dir}/wp-load.php" ) ) {
                break;
            }
        }
    }
    $wcap_root      = dirname( dirname(__FILE__) ); // go two level up for directory from this file.
    require_once $wp_load;
    $wcap_auto_cron = get_option ( 'wcap_use_auto_cron ');
    if( isset( $wcap_auto_cron ) && ( $wcap_auto_cron == false || '' == $wcap_auto_cron ) ) {
        require_once( $wcap_root. "/includes/classes/class_wcap_aes.php" );
        require_once( $wcap_root. "/includes/classes/class_wcap_aes_ctr.php" );
        Wcap_Send_Email_Using_Cron::wcap_abandoned_cart_send_email_notification();
        Wcap_Send_Email_Using_Cron::wcap_send_sms_notifications();
        include_once( WP_PLUGIN_DIR . '/woocommerce-abandon-cart-pro/includes/fb-recovery/fb-recovery.php' );
        WCAP_FB_Recovery::wcap_fb_cron();
    }
}

/** Files for Twilio SMS **/
require_once( WP_PLUGIN_DIR . '/woocommerce-abandon-cart-pro/includes/libraries/twilio-php/Twilio/autoload.php' ); // Loads the library
use Twilio\Rest\Client;

/**
 * It will send the abandoed cart reminder email the customers.
 * It will also update the abandoned cart status if the customer had placed the order before any reminder email is sent.
 * 
 */
class Wcap_Send_Email_Using_Cron {
    /**
     * It will send the abandoed cart reminder email the customers.
     * It will also update the abandoned cart status if the customer had placed the order before any reminder email is sent.
     * @hook woocommerce_ac_send_email_action
     * @globals mixed $wpdb
     * @globals mixed $woocommerce
     * @since 5.0
     */
    public static function wcap_abandoned_cart_send_email_notification() {
        global $wpdb, $woocommerce;

        $enable_email = get_option( 'ac_enable_cart_emails' );

        if( 'on' == $enable_email ) {

            //Grab the cart abandoned cut-off time from database.
            $cut_off_time              = get_option( 'ac_cart_abandoned_time' ) ;
            $cart_abandon_cut_off_time = $cut_off_time * 60;
            $ac_cutoff_time_guest      = get_option( 'ac_cart_abandoned_time_guest' );
            $cut_off_time_guest        = $ac_cutoff_time_guest * 60;

            //Fetch all active templates present in the system
            $query                     = "SELECT wpet . * FROM `" . WCAP_EMAIL_TEMPLATE_TABLE . "` AS wpet WHERE wpet.is_active = '1' ORDER BY `day_or_hour` DESC, `frequency` ASC ";
            $results_template          = $wpdb->get_results ( $query );
            $minute_seconds            = 60;
            $hour_seconds              = 3600; // 60 * 60
            $day_seconds               = 86400; // 24 * 60 * 60
            $admin_abandoned_email     = '';
            $wcap_from_name            = get_option ( 'wcap_from_name' );
            $wcap_from_email           = get_option ( 'wcap_from_email' );
            $wcap_reply_email          = get_option ( 'wcap_reply_email' );

            $headers                   = "From: " . $wcap_from_name . " <" . $wcap_from_email . ">" . "\r\n";
            $headers                  .= "Content-Type: text/html"."\r\n";
            $headers                  .= "Reply-To:  " . $wcap_reply_email . " " . "\r\n";
            
            $go_date_format            = get_option( 'date_format' );
            $go_time_format            = get_option( 'time_format' );
            $go_blogname               = get_option( 'blogname' );
            $go_siteurl                = get_option( 'siteurl' );

            $go_product_image_height   = get_option( 'wcap_product_image_height' );
            $go_product_image_width    = get_option( 'wcap_product_image_width' );

            $wcap_admin_email          = get_option( 'admin_email' );

            // check if WPML is active
            $icl_register_function_exists = false;
            if( function_exists( 'icl_register_string' ) ) {
                $icl_register_function_exists = true;
            }

            // fetch checkout page settings & create link
            if( version_compare( WOOCOMMERCE_VERSION, '2.3' ) < 0 ) {
                $checkout_page_link = $woocommerce->cart->get_checkout_url();
            } else {
                $checkout_page_id   = wc_get_page_id( 'checkout' );
                $checkout_page_link = $checkout_page_id ? get_permalink( $checkout_page_id ) : '';
            }
            // Force SSL if needed
            $ssl_is_used = false;
            if ( is_ssl() ) {
                $ssl_is_used = true;
            }
            if( true === $ssl_is_used || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
                $checkout_page_https = true;
                $checkout_page_link  = str_replace( 'http:', 'https:', $checkout_page_link );
            }

            // fetch cart page settings & create link
            if( version_compare( WOOCOMMERCE_VERSION, '2.3' ) < 0 ) {
                $cart_page_link = $woocommerce->cart->get_cart_url();
            } else {
                $cart_page_id   = wc_get_page_id( 'cart' );
                $cart_page_link = $cart_page_id ? get_permalink( $cart_page_id ) : '';
            }
            if ( true === $ssl_is_used ) {
                $cart_page_link = str_replace( 'http:', 'https:', $cart_page_link );
            }

            // fetch woocommerce template header & footer
            ob_start();
            wc_get_template( 'emails/email-header.php', array( 'email_heading' => '{{wc_template_header}}' ) );
            $email_body_template_header = ob_get_clean();

            ob_start();
            wc_get_template( 'emails/email-footer.php' );
            $email_body_template_footer = ob_get_clean();

            // check if it's a multisite
            if( is_multisite() ) {
                $main_prefix = $wpdb->get_blog_prefix(1);
            } else {
                $main_prefix = $wpdb->prefix;
            }

            $wcap_current_time = current_time( 'timestamp' );

            foreach( $results_template as $results_template_key => $results_template_value ) {
                if( 'Minutes' == $results_template_value->day_or_hour ) {
                    $time_to_send_template_after = $results_template_value->frequency * $minute_seconds;
                } else if( 'Days' == $results_template_value->day_or_hour ) {
                    $time_to_send_template_after = $results_template_value->frequency * $day_seconds;
                } else if( 'Hours' == $results_template_value->day_or_hour ) {
                    $time_to_send_template_after = $results_template_value->frequency * $hour_seconds;
                }
                $template_id            = $results_template_value->id;

                $cart_time              = $wcap_current_time - $time_to_send_template_after - $cart_abandon_cut_off_time;
                $cart_time_guest        = $wcap_current_time - $time_to_send_template_after - $cut_off_time_guest;

                $carts                  = Wcap_Send_Email_Using_Cron::wcap_get_carts( $cart_time, $cart_time_guest, $template_id, $main_prefix );

                $email_frequency        = $results_template_value->frequency;
                $email_body_template    = convert_smilies( $results_template_value->body );
                $template_email_subject = convert_smilies( $results_template_value->subject );

                $wcap_template_filter   = $results_template_value->wc_template_filter;

                $template_name          = $results_template_value->template_name;
                $coupon_id              = $results_template_value->coupon_code;
                $coupon_to_apply        = get_post( $coupon_id, ARRAY_A );
                $coupon_code            = $coupon_to_apply[ 'post_title' ];
                $default_template       = $results_template_value->default_template;
                $discount_amount        = $results_template_value->discount;
                $generate_unique_code   = $results_template_value->generate_unique_coupon_code;
                $is_wc_template         = $results_template_value->is_wc_template;
                $wc_template_header_t   = $results_template_value->wc_email_header != '' ? $results_template_value->wc_email_header : __( 'Abandoned cart reminder', 'woocommerce-ac');
                $coupon_code_to_apply   = '';
                $email_subject          = '';

                foreach( $carts as $key => $value ) {

                    $cart_id = $value->id;
                    $abandoned_user_id = $value->user_id;
                    $abandoned_user_type = $value->user_type;
                    
                    /**
                     * This function will check the user id for the guest user is correct or not.
                     * If the guest user id is wrong then it will not send the reminder emails.
                     * @since: 7.0
                     */
                    $wcap_is_guest_id_valid = Wcap_Send_Email_Using_Cron::wcap_get_is_guest_valid ( $abandoned_user_id, $abandoned_user_type ) ;
                    if ( true === $wcap_is_guest_id_valid ) {
                       
                        $selected_lanaguage = '';
                        if( "GUEST" == $abandoned_user_type && '0' != $abandoned_user_id ) {
                            $query_guest        = "SELECT billing_first_name, billing_last_name, email_id FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "` WHERE id = %d";
                            $results_guest      = $wpdb->get_results( $wpdb->prepare( $query_guest, $abandoned_user_id ) );
                        }

                        /**
                         * Retrive the email address needed for the template
                         */
                        $value->user_email = Wcap_Send_Email_Using_Cron::wcap_get_email_for_template( $template_id, $abandoned_user_type, $abandoned_user_id, $wcap_admin_email );
                        $abandoned_user_email = $value->user_email;
                        
                        $cart = new stdClass();
                        $cart_info_db_field = json_decode( stripslashes( $value->abandoned_cart_info ) );
                        if( !empty( $cart_info_db_field->cart ) ) {
                            $cart           = $cart_info_db_field->cart;
                        }

                        // Currency selected
                        $currency = isset( $cart_info_db_field->currency ) ? $cart_info_db_field->currency : '';

                        if( isset( $cart_id ) && isset( $value->user_email ) && '' != $value->user_email && '0' != $abandoned_user_id ) {
                            $cart_update_time   = $value->abandoned_cart_time;
                            $new_user           = Wcap_Send_Email_Using_Cron::wcap_check_sent_history( $abandoned_user_id, $cart_update_time, $template_id, $cart_id );
                            $selected_lanaguage = $value->language;

                            /**
                             * When we click on the place order button, we check if the order is placed after the cut off time.
                             * And if yes then if the status of the order is pending or falied then we keep it as the
                             * abandoned and we need to send reminder emails. So in below function we first check if any order
                             * is placed with today's date then we do not send the reminder email. But if the placed order
                             * status is pending or falied, the reminder email will be sent.
                             */
                            $wcap_check_cart_staus_need_to_update = self::wcap_get_cart_status( $time_to_send_template_after, $cart_update_time, $abandoned_user_id, $abandoned_user_type, $cart_id, $abandoned_user_email );
                            
                            if( true == $new_user && count( get_object_vars( $cart ) ) > 0 ) {

                                // The unnecessary cart of the template filter will be removed from the below function
                                // Return true if cart need to skip & for false we need to proceed
                                $explode_selected_filter_of_template = explode( "," , $wcap_template_filter );

                                $wcap_check_cart_needed_for_template = false; // it's for All filter
                                if ( '' != $wcap_template_filter && !in_array('All', $explode_selected_filter_of_template ) ){
                                    $wcap_check_cart_needed_for_template = Wcap_Send_Email_Using_Cron::wcap_remove_cart_for_template_filter ( $explode_selected_filter_of_template, $value->abandoned_cart_info, $abandoned_user_type  );
                                }

                                /**
                                 * When there are 3 templates and for cart id 1 all template time has been reached. 
                                 * But all templates are deactivated.
                                 * If we activate all 3 template then at a 1 time all 3 email templates send to the users.
                                 * So below function check that after first email is sent time and then from that time it will 
                                 * send the 2nd template time.  ( It will not consider the cart abandoned time in this case. )
                                 */
                                $wcap_check_cart_needed_for_multiple_template = Wcap_Send_Email_Using_Cron::wcap_remove_cart_for_mutiple_templates ( $cart_id, $time_to_send_template_after, $template_id );
                                if ( false == $wcap_check_cart_needed_for_template &&
                                     false == $wcap_check_cart_needed_for_multiple_template &&
                                     false == $wcap_check_cart_staus_need_to_update ){

                                    $wcap_used_coupon      = '' ;
                                    $wcap_check_cart_total = Wcap_Send_Email_Using_Cron::wcap_check_cart_total( $cart );

                                    if( true == $wcap_check_cart_total ) {

                                        $wcap_explode_emails              = explode(',', $value->user_email );
                                        $expiry_date_extend               = date( "Y-m-d", strtotime( date( 'Y-m-d' ) . " +7 days" ) );
                                        if( preg_match( "{{coupon.code}}", $email_body_template, $matched ) ) {
                                            $coupon_post_meta = '';
                                            if( '' == $coupon_code  && '1' == $default_template ) {
                                                if( '5' == $discount_amount ) {
                                                    $amount               = $discount_amount; 
                                                    $discount_type        = 'percent';
                                                    $expiry_date          = apply_filters( 'wcap_coupon_expiry_date', $expiry_date_extend );
                                                    $coupon_code_to_apply = Wcap_Send_Email_Using_Cron::wp_coupon_code ( $amount, $discount_type, $expiry_date, $coupon_post_meta );
                                                } elseif ( '10' == $discount_amount ) {
                                                    $amount               = $discount_amount; 
                                                    $discount_type        = 'percent';
                                                    $expiry_date          = apply_filters( 'wcap_coupon_expiry_date', $expiry_date_extend );
                                                    $coupon_code_to_apply = Wcap_Send_Email_Using_Cron::wp_coupon_code ( $amount, $discount_type, $expiry_date, $coupon_post_meta );
                                                }
                                            } elseif( '' != $coupon_code  && '1' == $generate_unique_code ) {
                                                $coupon_post_meta         = get_post_meta( $coupon_id );
                                                
                                                $coupon_expiry_timestamp  = strtotime( $coupon_post_meta['expiry_date'][0] );                       
                                                
                                                $discount_type            = $coupon_post_meta['discount_type'][0];
                                                $amount                   = $coupon_post_meta['coupon_amount'][0];
                                                if( isset( $coupon_post_meta['expiry_date'][0] ) && $coupon_expiry_timestamp >= $wcap_current_time && $coupon_post_meta['expiry_date'][0] != '' ) {
                                                    $expiry_date = $coupon_post_meta['expiry_date'][0];
                                                } else{
                                                    $expiry_date = apply_filters( 'wcap_coupon_expiry_date', $expiry_date_extend );
                                                }
                                                $coupon_code_to_apply = Wcap_Send_Email_Using_Cron::wp_coupon_code( $amount, $discount_type, $expiry_date, $coupon_post_meta );
                                            } else {
                                                $coupon_code_to_apply = $coupon_code;
                                            }
                                            
                                        }

                                        $selected_lanaguage      = $value->language;
                                        $name_msg                = 'wcap_template_' . $template_id . '_message';
                                        $email_body_template     = Wcap_Send_Email_Using_Cron::wcap_get_translated_texts( $name_msg, $results_template_value->body, $selected_lanaguage );
                                        $name_sub                = 'wcap_template_' . $template_id . '_subject';
                                        $template_email_subject  = Wcap_Send_Email_Using_Cron::wcap_get_translated_texts ( $name_sub, $results_template_value->subject, $selected_lanaguage );
                                        $wc_template_header_text = 'wcap_template_' . $template_id . '_wc_email_header';
                                        $wc_template_header      = Wcap_Send_Email_Using_Cron::wcap_get_translated_texts ( $wc_template_header_text, $wc_template_header_t, $selected_lanaguage );
                                        $cart_info_db            = $value->abandoned_cart_info;
                                        $email_body              = convert_smilies( $email_body_template );
                                        $email_body              .= '{{email_open_tracker}}';
                                        if( "GUEST" == $abandoned_user_type ) {
                                            // default
                                            $customer_first_name = '';
                                            $customer_last_name = '';
                                            $customer_full_name = '';
                                            
                                            if( isset( $results_guest[0]->billing_first_name ) ) {
                                                $customer_first_name = $results_guest[0]->billing_first_name;
                                                $customer_full_name = $customer_first_name;
                                            }
                                            if( isset( $results_guest[0]->billing_last_name ) ) {
                                                $customer_last_name = $results_guest[0]->billing_last_name;
                                                $customer_full_name .= " $customer_last_name";
                                            }
                                            
                                            $wcap_guest_session_id      = isset( $value->session_id ) ? $value->session_id : 0;
                                            $query_guest_session   = "SELECT session_value FROM `" . $wpdb->prefix . "woocommerce_sessions`
                                                                        WHERE session_key = %s";
                                            $results_guest_session = $wpdb->get_results( $wpdb->prepare( $query_guest_session, $wcap_guest_session_id  ) );

                                            if ( count( $results_guest_session ) > 0 ) {
                                                $wcap_result_session    = unserialize ($results_guest_session[0]->session_value);
                                                $wcap_coupon_sesson     = unserialize ( $wcap_result_session['applied_coupons'] );
                                                if ( count ( $wcap_coupon_sesson ) > 0 ) {
                                                    $wcap_used_coupon       = $wcap_coupon_sesson[0];
                                                    //$coupon_code_to_apply   = $wcap_used_coupon;
                                                }
                                            }
                                        } else {
                                            $logged_in_user_key = get_post_meta ( $cart_id , '_woocommerce_ac_coupon');
                                           if ( count( $logged_in_user_key ) > 0 ) {
                                                $wcap_used_coupon     =  $logged_in_user_key[0]['coupon_code'];
                                                //$coupon_code_to_apply = $wcap_used_coupon;
                                            }

                                            $coupon_detail_post_meta = get_post_meta( $cart_id, '_woocommerce_ac_coupon');

                                            if( '' != $coupon_detail_post_meta ) {
                                                $coupon_code_used = '';
                                                foreach( $coupon_detail_post_meta as $coupon_detail_post_meta_key => $coupon_detail_post_meta_value ) {
                                                    if( isset($coupon_detail_post_meta[$coupon_detail_post_meta_key]['coupon_code'] ) && $coupon_detail_post_meta[$coupon_detail_post_meta_key]['coupon_code'] != '' ) {
                                                    $coupon_code_used .= $coupon_detail_post_meta[$coupon_detail_post_meta_key]['coupon_code'] . "</br>";
                                                    }
                                                }
                                            }
                                            
                                            $customer_first_name = '';
                                            $customer_last_name = '';
                                            
                                            $user_first_name_temp = get_user_meta( $abandoned_user_id , 'billing_first_name', true );
                                            if( "" == $user_first_name_temp && isset( $user_first_name_temp ) ) {
                                                $user_data  = get_userdata( $abandoned_user_id  );
                                                $customer_first_name = $user_data->first_name;
                                            } else {
                                                $customer_first_name = $user_first_name_temp;
                                            }
                                            $customer_full_name = $customer_first_name;
                                            
                                            $user_last_name_temp = get_user_meta( $abandoned_user_id , 'billing_last_name', true );
                                            if( "" == $user_last_name_temp && isset( $user_last_name_temp ) ) {
                                                $user_data  = get_userdata( $abandoned_user_id  );
                                                $customer_last_name = $user_data->last_name;
                                            } else {
                                                $customer_last_name = $user_last_name_temp;
                                            }
                                        
                                            $customer_full_name .= " $customer_last_name";
                                        }

                                        $email_subject = str_replace( "{{customer.firstname}}", $customer_first_name, $template_email_subject );

                                        $email_body = str_replace( "{{customer.firstname}}", $customer_first_name, $email_body );
                                        $email_body = str_replace( "{{customer.lastname}}", $customer_last_name, $email_body );
                                        $email_body = str_replace( "{{customer.fullname}}", $customer_full_name, $email_body );
                                        
                                        $wcap_get_customers_email = Wcap_Send_Email_Using_Cron::wcap_get_customers_email( $abandoned_user_id, $abandoned_user_type );
                                        $email_body = str_replace( "{{customer.email}}", $wcap_get_customers_email, $email_body );

                                        $wcap_get_customers_phone = Wcap_Send_Email_Using_Cron::wcap_get_customers_phone( $abandoned_user_id, $abandoned_user_type );
                                        $email_body = str_replace( "{{customer.phone}}", $wcap_get_customers_phone, $email_body );

                                        $order_date = "";
                                        if( "" != $cart_update_time &&  0 != $cart_update_time ) {
                                            $date_format = date_i18n( $go_date_format, $cart_update_time );
                                            $time_format = date_i18n( $go_time_format, $cart_update_time );
                                            $order_date  = $date_format . ' ' . $time_format;
                                        }
                                        
                                        $email_body = str_replace( "{{coupon.code}}", $coupon_code_to_apply, $email_body );
                                        $email_body  = str_replace( "{{cart.abandoned_date}}", $order_date, $email_body );
                                        $email_body  = str_replace( "{{shop.name}}", $go_blogname, $email_body );
                                        $email_body  = str_replace( "{{shop.url}}", $go_siteurl, $email_body );
                                        if( version_compare( WOOCOMMERCE_VERSION, '3.2.0', ">=" ) ) {
                                            $store_address = Wcap_Common::wcap_get_wc_address();
                                            $email_body  = str_replace( "{{store.address}}", $store_address, $email_body );
                                        }

                                        $admin_args = array( 'role' => 'administrator',
                                                             'fields' => array( 'id' )
                                                      );

                                        $admin_usr  = get_users( $admin_args );
                                        $uid        = $admin_usr[0]->id;
                                        $admin_phone = get_user_meta( $uid, 'billing_phone', true );
                                        $email_body  = str_replace( '{{admin.phone}}', $admin_phone, $email_body );


                                        if( $checkout_page_id ) {
                                            if( true === $icl_register_function_exists ) {
                                                if( 'en' == $selected_lanaguage  ) {
                                                    // do nothing
                                                } else {
                                                    $checkout_page_link = apply_filters( 'wpml_permalink', $checkout_page_link, $selected_lanaguage );
                                                    // if ssl is enabled
                                                    if( isset( $checkout_page_https ) && true === $checkout_page_https ) {
                                                        $checkout_page_link = str_replace( 'http:', 'https:', $checkout_page_link );
                                                    }
                                                }
                                            }
                                        }
                                        
                                        foreach( $wcap_explode_emails as $emails ) {
                                            $user_email = $emails;
                                        
                                            $query_sent       = "INSERT INTO `" . WCAP_EMAIL_SENT_HISTORY_TABLE . "` ( template_id, abandoned_order_id, sent_time, sent_email_id )
                                                                  VALUES ( '" . $template_id . "', '" . $cart_id . "', '" . current_time('mysql') . "', '" . $user_email . "' )";
                                            $wpdb->query( $query_sent );
                                            $email_sent_id     = $wpdb->insert_id;

                                            $encoding_checkout = $email_sent_id . '&url=' . $checkout_page_link;
                                            $validate_checkout = Wcap_Common::encrypt_validate( $encoding_checkout );

                                            // Populate the product name if its present in the email subject line
                                            $sub_line_prod_name = '';
                                            $cart_details       = $cart_info_db_field->cart;
                                            foreach( $cart_details as $k => $v ) {
                                                $sub_line_prod_name = get_the_title( $v->product_id );
                                                break;
                                            }
                                            $email_subject = str_replace( "{{product.name}}", $sub_line_prod_name, $email_subject );

                                            $encoding_cart = $email_sent_id . '&url=' . $cart_page_link;
                                            $validate_cart = Wcap_Common::encrypt_validate( $encoding_cart );
                                            
                                            if( isset( $coupon_code_to_apply ) && $coupon_code_to_apply != '' ) {
                                                $encypted_coupon_code = Wcap_Common::encrypt_validate( $coupon_code_to_apply );
                                            
                                                // Cart Link
                                                $cart_link_track      = $go_siteurl . '/?wacp_action=track_links&validate=' . $validate_cart . '&c=' . $encypted_coupon_code;
                                                // Checkout Link
                                                $checkout_link_track  = $go_siteurl . '/?wacp_action=track_links&validate=' . $validate_checkout . '&c='.$encypted_coupon_code;
                                            } else {
                                                // Cart Link
                                                $cart_link_track      = $go_siteurl . '/?wacp_action=track_links&validate=' . $validate_cart;
                                                // Checkout Link
                                                $checkout_link_track = $go_siteurl . '/?wacp_action=track_links&validate=' . $validate_checkout;
                                            }
                                            
                                            // Populate the products.cart shortcode if it exists
                                            if( preg_match( "{{item.image}}", $email_body, $matched ) || preg_match( "{{item.name}}", $email_body, $matched ) || preg_match( "{{item.price}}", $email_body, $matched ) || preg_match( "{{item.quantity}}", $email_body, $matched ) || preg_match( "{{item.subtotal}}", $email_body, $matched ) || preg_match( "{{cart.total}}", $email_body, $matched ) ) {

                                                $cart_details      = $cart_info_db_field->cart;
                                                
                                                $wcap_product_image_height = $go_product_image_height;
                                                $wcap_product_image_width  = $go_product_image_width;
                                                
                                                $email_settings[ 'image_height' ] = $wcap_product_image_height;
                                                $email_settings[ 'image_width' ] = $wcap_product_image_width;
                                                $email_settings[ 'checkout_link' ] = $checkout_link_track;
                                                $email_settings[ 'coupon_used' ] = $wcap_used_coupon;
                                                $email_settings[ 'currency' ] = $currency;
                                                $email_settings[ 'abandoned_id' ] = $cart_id;
                                                
                                                $email_body = self::replace_product_cart( $email_body, $cart_details, $email_settings );
                                            }

                                            if( true === $icl_register_function_exists ) {
                                                if( 'en' == $selected_lanaguage ) {
                                                    // do nothing
                                                } else {
                                                    $cart_page_link = apply_filters( 'wpml_permalink', $cart_page_link, $selected_lanaguage );

                                                    // if ssl is enabled
                                                    if( true === $ssl_is_used ) {
                                                        $cart_page_link = str_replace( 'http:', 'https:', $cart_page_link );
                                                    }
                                                }
                                            }
                                            
                                            $email_body    = str_replace( "{{checkout.link}}", $checkout_link_track, $email_body );
                                            $email_body                    = str_replace( "{{cart.link}}", $cart_link_track, $email_body );
                                            $validate_unsubscribe          = Wcap_Common::encrypt_validate( $email_sent_id );
                                            $email_sent_id_address         = $user_email;
                                            $encrypt_email_sent_id_address = hash( 'sha256', $email_sent_id_address );
                                            $plugins_url                   = $go_siteurl . "/?wcap_track_unsubscribe=wcap_unsubscribe&validate=" . $validate_unsubscribe . "&track_email_id=" . $encrypt_email_sent_id_address;
                                            $unsubscribe_link_track        = $plugins_url;
                                            $email_body                    = str_replace( "{{cart.unsubscribe}}" , $unsubscribe_link_track , $email_body );
                                            $plugins_url_track_image       = $go_siteurl . '/?wcap_track_email_opens=wcap_email_open&email_id=';
                                            $hidden_image                  = '<img style="border:0px; height: 1px; width:1px;" alt="" src="' . $plugins_url_track_image . $email_sent_id . '" >';
                                            $email_body                    = str_replace( "{{email_open_tracker}}" , $hidden_image , $email_body );

                                            if( isset( $is_wc_template ) && "1" == $is_wc_template ) {

                                                $email_body_template_header = str_replace( '{{wc_template_header}}', $wc_template_header, $email_body_template_header );
                                                $final_email_body           =  $email_body_template_header . $email_body . $email_body_template_footer;
                                                Wcap_Common::wcap_add_wc_mail_header();
                                                wc_mail( $user_email, stripslashes( $email_subject ), stripslashes( $final_email_body ) , $headers );
                                                Wcap_Common::wcap_remove_wc_mail_header();
                                            } else {
                                                Wcap_Common::wcap_add_wp_mail_header();
                                                wp_mail( $user_email, stripslashes( $email_subject ), stripslashes( $email_body ), $headers );
                                                Wcap_Common::wcap_remove_wc_mail_header();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }    
                }
            }   
        }
    }

    /**
     * Replaces {{product.cart}} tag in the email body
     *
     * @param unknown $email_body
     * @param unknown $cart_details
     * @param unknown $wcap_product_image_height
     * @param unknown $wcap_product_image_width
     * @return mixed
     *
     * @since 7.10.0
     */
    static function replace_product_cart( $email_body, $cart_details, $email_settings ) {
    
        $replace_html      = '';
    
        $cart_total        = $item_subtotal = $item_total = $line_subtotal_tax_display =  $after_item_subtotal = $after_item_subtotal_display = 0;
    
        $wcap_product_image_height = $email_settings[ 'image_height' ];
        $wcap_product_image_width = $email_settings[ 'image_width' ];
        $checkout_link_track = $email_settings[ 'checkout_link' ];
        $wcap_used_coupon = $email_settings[ 'coupon_used' ];
        $currency = $email_settings[ 'currency' ];
        $abandoned_id = $email_settings[ 'abandoned_id' ];
    
        $go_prices_include_tax     = get_option( 'woocommerce_prices_include_tax' );
        $go_calc_taxes             = get_option( 'woocommerce_calc_taxes' );
    
        $go_blogname               = get_option( 'blogname' );
        $go_siteurl                = get_option( 'siteurl' );
    
        $line_subtotal_tax = 0;
        $wcap_include_tax  = $go_prices_include_tax;
        $wcap_include_tax_setting = $go_calc_taxes;
        // This array will be used to house the columns in the hierarchy they appear
        $position_array = array();
        $start_position = $end_position = $image_start_position = $name_start_position = 0;
        //check which columns are present
        if( preg_match( "{{item.image}}", $email_body, $matched ) ) {
            $image_start_position = strpos( $email_body, '{{item.image}}' );
            $position_array[ $image_start_position ] = 'image';
        }
        if( preg_match( "{{item.name}}", $email_body, $matched ) ) {
            $name_start_position = strpos( $email_body,'{{item.name}}' );
            $position_array[ $name_start_position ] = 'name';
        }
        if( preg_match( "{{item.price}}", $email_body, $matched ) ) {
            $price_start_position = strpos( $email_body, '{{item.price}}' );
            $position_array[ $price_start_position ] = 'price';
        }
        if( preg_match( "{{item.quantity}}", $email_body, $matched ) ) {
            $quantity_start_position = strpos( $email_body, '{{item.quantity}}' );
            $position_array[ $quantity_start_position ] = 'quantity';
        }
        if( preg_match( "{{item.subtotal}}", $email_body, $matched ) ) {
            $subtotal_start_position = strpos( $email_body,'{{item.subtotal}}' );
            $position_array[ $subtotal_start_position ] = 'subtotal';
        }
        // Complete populating the array
        ksort( $position_array );
        $tr_array   = explode( "<tr", $email_body );
        $check_html = $style = '';
        foreach( $tr_array as $tr_key => $tr_value ) {
            if( ( preg_match( "{{item.image}}", $tr_value, $matched ) || preg_match( "{{item.name}}", $tr_value, $matched) || preg_match( "{{item.price}}", $tr_value, $matched ) || preg_match( "{{item.quantity}}", $tr_value, $matched) || preg_match( "{{item.subtotal}}", $tr_value, $matched)) && ! preg_match( "{{cart.total}}", $tr_value, $matched ) && count( get_object_vars( $cart_details ) ) > 0 ) {
    
                $style_start  = strpos( $tr_value, 'style' );
                $style_end    = strpos( $tr_value, '>', $style_start );
                $style_end    = $style_end - $style_start;
                $style        = substr( $tr_value, $style_start, $style_end );
                $tr_value     = "<tr" . $tr_value;
                $end_position = strpos( $tr_value, '</tr>' );
                $end_position = $end_position + 5;
                $check_html   = substr( $tr_value, 0, $end_position );
            }
        }
        $i            = 1;
        $bundle_child = array();
        foreach( $cart_details as $k => $v ) {
            $product      = wc_get_product( $v->product_id );
    
            $prod_name = '';
            $image_url = '';
            $item_price = '';
            $quantity = '';
            $item_subtotal_display = '';
            if ( false !== $product ) {
                $image_size   = array( $wcap_product_image_width, $wcap_product_image_height, '1' );

                $image_id = isset( $v->variation_id ) && $v->variation_id != '' && $v->variation_id > 0 ? $v->variation_id : $v->product_id;
                $image_url    = Wcap_Common::wcap_get_product_image( $image_id, $image_size );
    
                $quantity     = $v->quantity;
    
                if( version_compare( WOOCOMMERCE_VERSION, '3.0.0', ">=" ) ) {
                    $wcap_product_type = $product->get_type();
                    $item_name = $product->get_title();
                }else {
                    $wcap_product_type = $product->product_type;
                    $item_name    = $product->post_title;
                }
                
                $prod_name    = apply_filters( 'wcap_product_name', $item_name );
                
                $wcap_product_sku = apply_filters( 'wcap_product_sku', $product->get_sku() );
                if( false != $wcap_product_sku && '' != $wcap_product_sku ) {
                    if( $wcap_product_type == 'simple' && '' != $product->get_sku() ){
                        $wcap_sku = '<br> SKU: ' . $product->get_sku();
                    } else {
                        $wcap_sku = '';
                    }
                    $prod_name    = $prod_name . $wcap_sku;
                }
    
                // Show variation
                if( isset( $v->variation_id ) && '' != $v->variation_id ){
                    $variation_id = $v->variation_id;
                    $variation    = wc_get_product( $variation_id );
                    if ( false !== $variation ) {
                        $name         = $variation->get_formatted_name() ;
                        $explode_all  = explode( "&ndash;", $name );

                        if( version_compare( WOOCOMMERCE_VERSION, '3.0.0', ">=" ) ) {
                            if( false != $wcap_product_sku && '' != $wcap_product_sku ) {
                                $wcap_sku  = '';
                                if ( $variation->get_sku() ) {
                                    $wcap_sku = "SKU: " . $variation->get_sku() . "<br>";
                                }
                                $wcap_get_formatted_variation = wc_get_formatted_variation( $variation, true );
    
                                $add_product_name = $prod_name . ' - ' . $wcap_sku . ' ' .$wcap_get_formatted_variation;
                            } else {
    
                                $wcap_get_formatted_variation = wc_get_formatted_variation( $variation, true );
    
                                $add_product_name = $prod_name . '<br>' .$wcap_get_formatted_variation;
                            }
    
                            $pro_name_variation = (array) $add_product_name;
                        }else{
                            $pro_name_variation = array_slice( $explode_all, 1, -1 );
                        }
                        $product_name_with_variable = '';
                        $explode_many_varaition     = array();
                        foreach( $pro_name_variation as $pro_name_variation_key => $pro_name_variation_value ) {
                            $explode_many_varaition = explode ( ",", $pro_name_variation_value );
                            if( !empty( $explode_many_varaition ) ) {
                                foreach( $explode_many_varaition as $explode_many_varaition_key => $explode_many_varaition_value ) {
                                    $product_name_with_variable = $product_name_with_variable .  html_entity_decode ( $explode_many_varaition_value ) . "<br>";
                                }
                            } else {
                                $product_name_with_variable = $product_name_with_variable .  html_entity_decode ( $explode_many_varaition_value ) . "<br>";
                            }
                        }
                        $prod_name = $product_name_with_variable;
                    }
                }
                // Price and Item Subtotal
                // Item subtotal is calculated as product total including taxes
                if( isset( $wcap_include_tax ) && 'no' == $wcap_include_tax &&
                    isset( $wcap_include_tax_setting ) && 'yes' == $wcap_include_tax_setting ) {
    
                        $item_subtotal       = $item_subtotal + $v->line_total;
                        $line_subtotal_tax  += $v->line_tax;
                        $after_item_subtotal =  $v->line_total ;
    
                    } elseif ( isset( $wcap_include_tax ) && $wcap_include_tax == 'yes' &&
                        isset( $wcap_include_tax_setting ) && $wcap_include_tax_setting == 'yes' ) {
                        // Item subtotal is calculated as product total including taxes
                        if( 0 != $v->line_tax && $v->line_tax > 0 ) {
    
                            $line_subtotal_tax_display += $v->line_tax;
    
                            // After copon code price
                            $after_item_subtotal = $item_subtotal + $v->line_total + $v->line_tax;
    
                            // Calculate the product price
                            $item_subtotal = $item_subtotal + $v->line_subtotal + $v->line_subtotal_tax;
                        } else {
                            $item_subtotal = $item_subtotal + $v->line_total;
                            $line_subtotal_tax_display += $v->line_tax;
                            $after_item_subtotal = $item_subtotal + $v->line_tax;
                        }
                    } else {
                        if( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
                            $after_item_subtotal = $v->line_total + $v->line_subtotal_tax;
                            $item_subtotal       = $item_subtotal + $v->line_subtotal + $v->line_subtotal_tax;
                        } else {
                            $after_item_subtotal = $v->line_total;
                            $item_subtotal = $item_subtotal + $v->line_total;
                        }
                    }
                    //  Line total
                    $item_total            = $item_subtotal;
                    $item_price            = $item_subtotal / $quantity;
                    $after_item_subtotal_display = ( $item_subtotal - $after_item_subtotal ) +  $after_item_subtotal_display ;
    
                    $item_subtotal_display = apply_filters ( 'acfac_change_currency', Wcap_Common::wcap_get_price( $item_total, $currency ), $abandoned_id, $item_total, 'wcap_cron' );
    
                    $item_price            = apply_filters ( 'acfac_change_currency', Wcap_Common::wcap_get_price( $item_price, $currency ), $abandoned_id, $item_price, 'wcap_cron' );
                    $cart_total            += $after_item_subtotal;
    
                    $item_subtotal         = $item_total = 0;
                    /*if( $i % 2 == 0 ) {
                     $replace_html .= '<tr>';
                     } else {*/
                    $replace_html .= '<tr ' . $style . '>';
                    //}
                    // If bundled product, get the list of sub products
                    if( isset( $v->product_type ) && 'bundle' == $v->product_type && isset( $product->bundle_data ) && is_array( $product->bundle_data ) && count( $product->bundle_data ) > 0 ) {
                        foreach( $product->bundle_data as $b_key => $b_value ) {
                            $bundle_child[] = $b_key;
                        }
                    }
                    /**
                     * Check if the product is a part of the bundles product, if yes, set qty
                     * and totals to blanks
                     */
                    if( isset( $bundle_child ) && count( $bundle_child ) > 0 ) {
                        if ( in_array( $v->product_id, $bundle_child ) ) {
                            $item_subtotal_display = $item_price = $quantity = '';
                        }
                    }
                    foreach( $position_array as $k => $v ) {
                        switch( $v ) {
                            case 'image':
                                $replace_html .= '<td style="text-align:center;"> <a href="' . $checkout_link_track . '">' . $image_url . '</a> </td>';
                                break;
                            case 'name':
                                $replace_html .= '<td style="text-align:center;"> <a href="' . $checkout_link_track . '">' . $prod_name . '</a> </td>';
                                break;
                            case 'price':
                                if ( '' == $item_price ) {
                                    $replace_html .= '<td></td>';
                                } else {
                                    $replace_html .= '<td style="text-align:center;">' . $item_price . '</td>';
                                }
                                break;
                            case 'quantity':
                                $replace_html .= '<td style="text-align:center;">' . $quantity . '</td>';
                                break;
                            case 'subtotal':
                                if ( '' == $item_subtotal_display ) {
                                    $replace_html .= '<td></td>';
                                } else {
                                    $replace_html .= '<td style="text-align:center;">' . $item_subtotal_display . '</td>';
                                }
                                break;
                            default:
                                $replace_html .= '<td></td>';
                        }
                    }
                    $replace_html .= '</tr>';
    
            } else {
                $replace_html .= '<tr> <td colspan="5"> Product you had added to cart is currently unavailable. Please choose another product from <a href="'.$go_siteurl.'">'.$go_blogname.'</a> </td> </tr>';
                $after_item_subtotal_display = $wcap_line_subtotal_tax = '';
            }
    
    
            $i++;
        }
        if( '' != $wcap_used_coupon && isset($after_item_subtotal_display) && $after_item_subtotal_display > 0 ) {
            $after_item_subtotal_display = apply_filters ( 'acfac_change_currency', Wcap_Common::wcap_get_price( $after_item_subtotal_display, $currency ), $abandoned_id, $after_item_subtotal_display, 'wcap_cron' );
            $replace_html .= '<tr>
                                                                        <td> </td>
                                                                        <td> </td>
                                                                        <td> </td>
                                                                        <td>'.__( "<strong>Coupon: $wcap_used_coupon</strong>", "woocommerce-ac" ).'</td>
                                                                        <td> -'. $after_item_subtotal_display .'</td>
                                                                    </tr>';
        }
         $show_taxes = apply_filters('wcap_show_taxes', true);
    
        if( $show_taxes && isset( $wcap_include_tax ) && 'no' == $wcap_include_tax &&
            isset( $wcap_include_tax_setting ) && 'yes' == $wcap_include_tax_setting ) {

            $count_columns = count( $position_array ) - 2;

            $wcap_line_subtotal_tax = apply_filters ( 'acfac_change_currency', Wcap_Common::wcap_get_price( $line_subtotal_tax, $currency ), $abandoned_id, $line_subtotal_tax, 'wcap_cron' );

            $replace_html .= '<tr>';
            if ( count( $position_array ) > 2 ) {
                for( $count_c = 1; $count_c <= $count_columns; $count_c++ ){
                    $replace_html .= '<td></td>';
                }

            }
            $replace_html .= '<td>'.__( "<strong>Tax:</strong>", "woocommerce-ac" ).'</td>
                            <td> '. $wcap_line_subtotal_tax .'</td>
                        </tr>';

            /*$replace_html .= '<tr>
                                                                    <td> </td>
                                                                    <td> </td>
                                                                    <td> </td>
                                                                    <td>'.__( "<strong>Tax:</strong>", "woocommerce-ac" ).'</td>
                                                                    <td> '. $wcap_line_subtotal_tax .'</td>
                                                                </tr>';*/
        }
        // Calculate the cart total
        if( isset( $wcap_include_tax ) && 'yes' == $wcap_include_tax &&
            isset( $wcap_include_tax_setting ) && 'yes' == $wcap_include_tax_setting ) {

            $cart_total                = apply_filters ( 'acfac_change_currency', Wcap_Common::wcap_get_price( $cart_total, $currency ), $abandoned_id, $cart_total, 'wcap_cron' ); //wc_price( $cart_total );
            $line_subtotal_tax_display = apply_filters ( 'acfac_change_currency', Wcap_Common::wcap_get_price( $line_subtotal_tax_display, $currency ), $abandoned_id, $line_subtotal_tax_display, 'wcap_cron' );
            if ( $show_taxes ) {
            
                $cart_total                = $cart_total . ' (' . __( "includes Tax: " , "woocommerce-ac" ) . $line_subtotal_tax_display . ')';
            } else {
                $cart_total                = $cart_total; 
                }
        }elseif( isset( $wcap_include_tax ) && $wcap_include_tax == 'no' &&
            isset( $wcap_include_tax_setting ) && 'yes' == $wcap_include_tax_setting ) {
            $cart_total = $cart_total + $line_subtotal_tax ;
            $cart_total = apply_filters ( 'acfac_change_currency', Wcap_Common::wcap_get_price( $cart_total, $currency ), $abandoned_id, $cart_total, 'wcap_cron' );
        } else {

            $cart_total = apply_filters ( 'acfac_change_currency', Wcap_Common::wcap_get_price( $cart_total, $currency ), $abandoned_id, $cart_total, 'wcap_cron' );
        }
        // Populate/Add the product rows
        $email_body    = str_replace( $check_html, $replace_html, $email_body );
        // Populate the cart total
        $email_body    = str_replace( "{{cart.total}}", $cart_total, $email_body );
        $email_body    = str_replace( "{{item.name}}", $prod_name, $email_body );
        $replace_image = $image_url;
        $email_body    = str_replace( "{{item.image}}", $replace_image, $email_body );
        $email_body    = str_replace( "{{item.price}}", $item_price, $email_body );
        $email_body    = str_replace( "{{item.quantity}}", $quantity, $email_body );
        $email_body    = str_replace( "{{item.subtotal}}", $item_subtotal_display, $email_body );

        return $email_body;
    }

    /**
     * Checks if the order was recovered but not updated in the 
     * cart history table
     * 
     * @param string $time_to_send_template_after - Frequency at which the reminder email should be sent.
     * @param string $cart_abandoned_timee - Time at which the cart was abandoned
     * @param integer $abandoned_user_id - User ID by which the cart was abandoned
     * @param string $abandoned_user_type - User Type (Guest, Registered)
     * @param integer $abandoned_id - Abndoned Cart ID
     * @param string $abandoned_user_email - User email for which the cart was abandoned
     * @return boolean $wcap_check_cart_status_need_to_update - False (reminder should be sent)
     * 
     * @since 7.10.0
     * 
     */
    static function wcap_get_cart_status( $time_to_send_template_after, $cart_abandoned_time, $abandoned_user_id, $abandoned_user_type, $abandoned_id, $abandoned_user_email ) {
    
        global $wpdb;
    
        $wcap_check_if_cart_is_present_in_post_meta   = "SELECT wpm.post_id, wpost.post_date, wpost.post_status  FROM `" . $wpdb->prefix . "postmeta` AS wpm
                                    LEFT JOIN `" . $wpdb->prefix . "posts` AS wpost
                                    ON wpm.post_id = wpost.ID
                                    WHERE wpm.meta_key = 'wcap_abandoned_cart_id' AND
                                    wpm.meta_value = %s AND wpm.post_id = wpost.ID AND
                                    wpost.post_type = 'shop_order'
                                    ORDER BY wpm.post_id   DESC LIMIT 1";
    
        $results_wcap_check_if_cart_is_present_in_post_meta = $wpdb->get_results( $wpdb->prepare( $wcap_check_if_cart_is_present_in_post_meta, $abandoned_id  ) );
    
        $order_status = isset( $results_wcap_check_if_cart_is_present_in_post_meta[0]->post_status ) ? $results_wcap_check_if_cart_is_present_in_post_meta[0]->post_status : '';
    
        $wcap_check_cart_status_need_to_update = false;
        if ( /* empty ($results_wcap_check_if_cart_is_present_in_post_meta) || */
            ( isset( $results_wcap_check_if_cart_is_present_in_post_meta[0] ) &&
                $order_status != "wc-failed" && $order_status != "wc-pending" ) ){
    
    
            $order_data = array();
             
            $wcap_check_cart_status_need_to_update = self::wcap_update_abandoned_cart_status_for_placed_orders ( $time_to_send_template_after, $cart_abandoned_time, $abandoned_user_id, $abandoned_user_type, $abandoned_id, $abandoned_user_email );
        }
    
        return $wcap_check_cart_status_need_to_update;
    }
    
    /**
     * This function will check if the user type is Guest and the id is greater than 63000000.
     * Then conider that as a correct guest user, if is not then do not send the emails.
     * @param int | string $wcap_user_id
     * @param string $wcap_user_type
     * @return true | false
     * @since 7.1
     */
    public static function wcap_get_is_guest_valid ( $wcap_user_id, $wcap_user_type ) {

        if ( 'REGISTERED' == $wcap_user_type ){
            return true;
        }

        if ( 'GUEST' == $wcap_user_type && $wcap_user_id >= '63000000' ) {
            return true;
        }

        /**
         * It indicates that the user type is guest but the id for them is wrong.
         */
        return false;
    }

    /**
     * It will check the cart total. If the cart total is 0 then email will not be sent.
     * @param array $cart Cart detail
     * @return true | false
     * @since 4.7
     */
    public static function wcap_check_cart_total ( $cart ){

        foreach( $cart as $k => $v ) {
            if( $v->line_total != 0 && $v->line_total > 0 ) {
               return true;
            }
        }
        return apply_filters( 'wcap_cart_total', false );

   }
    /**
     * Get all carts which have the creation time earlier than the one that is passed.
     * @param timestamp $cart_time Cutoff time for loggedin user
     * @param timestamo $cart_time_guest Cutoff time for Guest user
     * @param int | string $template_id Template id
     * @param string $main_prefix Multisite main site prefix
     * @globals mixed $wpdb
     * @return array | object $results All carts
     */
    public static function wcap_get_carts( $cart_time, $cart_time_guest, $template_id, $main_prefix ) {
        global $wpdb;

        $wcap_add_template_condition = '';
        $wcap_template_time = get_post_meta( $template_id, 'wcap_template_time' );
        if ( count ( $wcap_template_time ) > 0 ) {
            $wcap_add_template_condition = ' AND abandoned_cart_time > ' . $wcap_template_time[0];
        }
        // return carts with statuses 'Abandoned - cart_ignored = 0' and 'Abandoned - Order Unpaid - cart_ignored = 2'
        $query = "SELECT DISTINCT wpac.id, wpac.user_id, wpac.abandoned_cart_info, wpac.abandoned_cart_time, wpac.user_type, wpac.language, wpac.session_id FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` AS wpac 
                LEFT JOIN " . WCAP_EMAIL_SENT_HISTORY_TABLE . " AS wesht ON wpac.id = wesht.abandoned_order_id 
                WHERE (
                        ( user_type = 'REGISTERED' AND cart_ignored IN ('0','2') AND unsubscribe_link = '0' AND abandoned_cart_time < '$cart_time' AND manual_email = '' AND wcap_trash = '' )
                        OR 
                        ( user_type = 'GUEST' AND cart_ignored IN ('0','2') AND unsubscribe_link = '0' AND abandoned_cart_time < '$cart_time_guest' AND manual_email = '' AND wcap_trash = '' )
                      )
                    AND wpac.user_id != 0
                    AND wpac.recovered_cart = '0'
                    AND wpac.id NOT IN ( SELECT abandoned_order_id FROM " . WCAP_EMAIL_SENT_HISTORY_TABLE . " WHERE template_id = $template_id )
                    $wcap_add_template_condition ";

        $results   = $wpdb->get_results ( $query );

        return $results;
    }

    /**
     * Filter for removing the carts for sending reminder emails.
     * @param string $explode_selected_filter_of_template Selected segment values
     * @param json_encode $wcap_carts_abandoned_info Cart data
     * @param string $wcap_user_type User type
     * @return true Send email
     * @return false Do not send email
     * @since 4.1
     * @todo Optimize the function to remove unwanted foreach conditions.
     */
    public static function wcap_remove_cart_for_template_filter( $explode_selected_filter_of_template, $wcap_carts_abandoned_info, $wcap_user_type ) {
        /**
         * If we have multiple filter and an one of the filter does not meet the criteria in the cart, then we are not sending 
         * the abandoned cart reminder emails to that cart.
         * So, here for each check the same, if any of the one selected filter of the template does not meet the cart criteria
         * then that cart will not be sent to the user.
         */
        $abandoned_cart_info = json_decode( $wcap_carts_abandoned_info );
        foreach( $explode_selected_filter_of_template as $explode_selected_filter_of_template_key => $explode_selected_filter_of_template_value ) {
            switch( $explode_selected_filter_of_template_value ) {
                case "Carts abandoned with one product" :
                    $cart_data = array();
                    if( !empty( $abandoned_cart_info ) ) {
                        $cart_data = $abandoned_cart_info->cart;
                    }
                    if( !empty( $cart_data ) ) {
                        $total_products_in_cart = 0 ;
                        foreach( $cart_data as $cart_key => $cart_value ) {
                            if( array_key_exists ( 'product_id', $cart_value )){
                                $total_products_in_cart = $total_products_in_cart + 1;
                            }
                        }

                        if( $total_products_in_cart > 1 ){
                            return true;
                        }
                    }
                break;
                case "Carts abandoned with more than one product":
                    $cart_data           = array();
                    if( !empty( $abandoned_cart_info ) ) {
                        $cart_data = $abandoned_cart_info->cart;
                    }
                    if( !empty( $cart_data ) ) {
                        $total_products_in_cart = 0 ;
                        foreach( $cart_data as $cart_key => $cart_value ) {
                            if( array_key_exists ( 'product_id', $cart_value ) ) {
                                $total_products_in_cart = $total_products_in_cart + 1;
                            }
                        }
                        if( $total_products_in_cart <= 1 ) {
                            return true;
                        }
                    }
                break;
                case "Registered Users" :

                    if( 'GUEST' == $wcap_user_type && !in_array( 'Guest Users', $explode_selected_filter_of_template ) ) {
                        return true;
                    }
                break;
                case "Guest Users" :
                    if( 'REGISTERED' == $wcap_user_type && !in_array( 'Registered Users', $explode_selected_filter_of_template ) ) {
                        return true;
                    }
                break;
            }
        }

        return false;
    }

    /**
     * When all email templates are activated after a period - let's say 1 month, then the emails shouldn't be sent together for all templates
     * Instead, the emails should be sent with earliest template first & then subsequently after the appropriate interval for 2nd template
     * & so on.
     * @param int | string $wcap_cart_id Abandoned cart id
     * @param timestamp $time_to_send_template_after Email template time
     * @param int $template_id Template id
     * @return true Send email
     * @return false Dont send email
     * @globals mixed $wpdb
     * @since 3.7
     */
    public static function wcap_remove_cart_for_mutiple_templates( $wcap_cart_id, $time_to_send_template_after, $template_id ) {
        global $wpdb;

        $wcap_get_last_email_sent_time              = "SELECT `sent_time` FROM `" . WCAP_EMAIL_SENT_HISTORY_TABLE . "` WHERE abandoned_order_id = $wcap_cart_id ORDER BY `sent_time` DESC LIMIT 1";
        $wcap_get_last_email_sent_time_results_list = $wpdb->get_results( $wcap_get_last_email_sent_time );

        if( count( $wcap_get_last_email_sent_time_results_list ) > 0 ) {
            $last_template_send_time   = strtotime( $wcap_get_last_email_sent_time_results_list[0]->sent_time );
            $second_template_send_time = $last_template_send_time + $time_to_send_template_after ;
            $current_time_test         = current_time( 'timestamp' );
            if ( $second_template_send_time > $current_time_test ) {
                return true;
            }
        }
        return false;
    }
    /**
     * It will update the abandoned cart status if the customer has placed the order before the reminder email is sent.
     * @param timestamp $time_to_send_template_after Template time
     * @param timestamp $wcap_cart_time Abadoned cart time
     * @param int | string $wcap_user_id User id
     * @param string $wcap_user_type User type
     * @param int | string $wcap_cart_id Abandoned cart id
     * @param string $wcap_user_email Email address of user
     * @global mixed $wpdb
     * @return true Cart updated
     * @return false Cart not updated
     * @since 5.0
     */
    public static function wcap_update_abandoned_cart_status_for_placed_orders( $time_to_send_template_after, $wcap_cart_time, $wcap_user_id, $wcap_user_type, $wcap_cart_id, $wcap_user_email ) {
        global $wpdb;

        if( $wcap_user_id >= '63000000' &&  'GUEST' ==  $wcap_user_type ) {
            $updated_value = Wcap_Send_Email_Using_Cron::wcap_update_status_of_guest( $wcap_cart_id, $wcap_cart_time , $time_to_send_template_after, $wcap_user_email );
            if( 1 == $updated_value ) {
                return true;
            }
        } elseif ( $wcap_user_id < '63000000' && 'REGISTERED' == $wcap_user_type ) {
            $updated_value = Wcap_Send_Email_Using_Cron::wcap_update_status_of_loggedin ( $wcap_cart_id, $wcap_cart_time , $time_to_send_template_after, $wcap_user_email );
            if( 1 == $updated_value ) {
                return true;
            }
        }
        return false;
    }

    /**
     * It will update the abandoned cart status of the loggedin customers.
     * @param timestamp $time_to_send_template_after Template time
     * @param timestamp $abandoned_cart_time Abadoned cart time
     * @param int | string $wcap_user_id User id
     * @param int | string $cart_id Abandoned cart id
     * @param string $user_billing_email Email address of user
     * @global mixed $wpdb
     * @return true Cart updated
     * @return false Cart not updated
     * @since 5.0
     * @todo Optimze the code or make a comman function for updating the cart status.
     */
    public static function wcap_update_status_of_loggedin( $cart_id, $abandoned_cart_time , $time_to_send_template_after, $user_billing_email ) {
        global $wpdb;

        $query_email_id   = "SELECT wpm.post_id, wpost.post_date, wpost.post_status  FROM `" . $wpdb->prefix . "postmeta` AS wpm 
                            LEFT JOIN `" . $wpdb->prefix . "posts` AS wpost 
                            ON wpm.post_id =  wpost.ID WHERE wpm.meta_key = '_billing_email' 
                            AND wpm.meta_value = %s 
                            AND wpm.post_id = wpost.ID 
                            Order BY wpm.post_id DESC LIMIT 1";

        $results_query_email = $wpdb->get_results( $wpdb->prepare( $query_email_id, $user_billing_email  ) );

        if ( count ( $results_query_email ) > 0 ){
            $current_time     = current_time( 'timestamp' );
            $todays_date      = date( 'Y-m-d', $current_time );
            $order_date_time = $results_query_email[0]->post_date;
            $order_date      = substr( $order_date_time, 0, 10 );

            if ( $order_date == $todays_date ){

                $wcap_check_email_sent_to_cart = Wcap_Send_Email_Using_Cron::wcap_get_cart_sent_data ( $cart_id );

                if ( 0 !=  $wcap_check_email_sent_to_cart ){

                    $wcap_get_order_id = $wcap_query   = "SELECT `post_id` FROM `" . $wpdb->prefix . "postmeta` 
                                                            WHERE  meta_value = %s
                                                            AND meta_key = %s ";
                    $wcap_results = $wpdb->get_results ( $wpdb->prepare( $wcap_query, $cart_id, 'wcap_recover_order_placed' ) );

                    if ( count( $wcap_results ) > 0 ){

                        $order_id = $wcap_results[0]->post_id;

                        try {
                            $order    = new WC_Order( $order_id );

                            $query_order = "UPDATE `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` SET 
                                            recovered_cart= '" . $order_id . "', 
                                            cart_ignored = '1',
                                            language = '' 
                                            WHERE id = '".$cart_id."' ";
                            $wpdb->query( $query_order );

                            $recover_order = "UPDATE `" . WCAP_EMAIL_SENT_HISTORY_TABLE . "` SET recovered_order = '1'
                                              WHERE id ='" . $wcap_check_email_sent_to_cart . "' ";
                            $wpdb->query( $recover_order );

                            $order->add_order_note( __( 'This order was abandoned & subsequently recovered.', 'woocommerce-ac' ) );

                            //delete_post_meta( $order_id,  'wcap_recover_order_placed',         $cart_id );
                            //delete_post_meta( $order_id , 'wcap_recover_order_placed_sent_id', $wcap_check_email_sent_to_cart );
                        } catch ( Exception $e ) {
                            
                        }

                    }

                }else{

                    $query_ignored = "UPDATE `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` SET cart_ignored = '1' WHERE id ='" . $cart_id . "'";
                    $wpdb->query( $query_ignored );
                }
                return 1;
            }else if ( strtotime( $order_date_time ) >=  $abandoned_cart_time ) {
                $query_ignored = "UPDATE `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` SET cart_ignored = '1' WHERE id ='" . $cart_id . "'";
                $wpdb->query( $query_ignored );
                return 1; // We return here 1 so it indicate that the cart has been modifed so do not sent email and delete from the array.
            }else if( $results_query_email[0]->post_status == "wc-pending" || $results_query_email[0]->post_status == "wc-failed" ) {
                return 0; // If status of the order is pending or falied then return 0 so it will not delete that cart and send reminder email
            }
        }
        return 0; // It means there are no record found to be update it.
    }

    /**
     * It will update the abandoned cart status of the guest customers.
     * @param timestamp $time_to_send_template_after Template time
     * @param timestamp $abandoned_cart_time Abadoned cart time
     * @param int | string $wcap_user_id User id
     * @param int | string $cart_id Abandoned cart id
     * @param string $wcap_user_email_address Email address of user
     * @global mixed $wpdb
     * @return true Cart updated
     * @return false Cart not updated
     * @since 5.0
     * @todo Optimze the code or make a comman function for updating the cart status.
     */
    public static function wcap_update_status_of_guest ( $cart_id, $abandoned_cart_time , $time_to_send_template_after, $wcap_user_email_address ) {
        global $wpdb;

        $query_email_id   = "SELECT wpm.post_id, wpost.post_date, wpost.post_status  FROM `" . $wpdb->prefix . "postmeta` AS wpm
                LEFT JOIN `" . $wpdb->prefix . "posts` AS wpost ON wpm.post_id = wpost.ID
                WHERE wpm.meta_key = '_billing_email' AND wpm.meta_value = %s AND wpm.post_id = wpost.ID AND wpost.post_type = 'shop_order' Order BY wpm.post_id   DESC LIMIT 1";

        $results_query_email = $wpdb->get_results( $wpdb->prepare( $query_email_id, $wcap_user_email_address  ) );

        // This will check that For abc@abc.com email address we have order for todays date in WC post table.
        if ( count( $results_query_email ) > 0 ) {

            $current_time     = current_time( 'timestamp' );
            $todays_date      = date( 'Y-m-d', $current_time );

            $order_date_with_time = $results_query_email[0]->post_date;
            $order_date           = substr( $order_date_with_time, 0, 10 );

            if ( $order_date == $todays_date ) {

                /**
                 *  In some case the cart is recovered but it is not marked as the recovred. So here we check if any record is 
                 * found for that cart id if yes then update the record respectively.
                 */
                $wcap_check_email_sent_to_cart = Wcap_Send_Email_Using_Cron::wcap_get_cart_sent_data ( $cart_id );

                if ( 0 !=  $wcap_check_email_sent_to_cart ) {

                    $wcap_query   = "SELECT `post_id` FROM `" . $wpdb->prefix . "postmeta` WHERE meta_value = %s AND meta_key = %s ";
                    $wcap_results = $wpdb->get_results ( $wpdb->prepare( $wcap_query, $cart_id, 'wcap_recover_order_placed' ) );

                    if ( count( $wcap_results ) > 0 ) {

                        $order_id = $wcap_results[0]->post_id;
                        try {
                            $order    = new WC_Order( $order_id );

                            $order_status = $order->get_status();
                            
                            if( $order_status != 'cancelled' && $order_status != 'refunded' && $order_status != 'trash' ) {
                                wcap_common::wcap_updated_recovered_cart( $cart_id, $order_id, $wcap_check_email_sent_to_cart, $order );
                            
                            }
                            
                        } catch ( Exception $e ) {

                        }
                    }
                }else{

                    $query_ignored = "UPDATE `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` SET cart_ignored = '1' WHERE id ='" . $cart_id . "'";
                    $wpdb->query( $query_ignored );
                }
                return 1;
            }else if ( strtotime( $order_date_with_time ) > $abandoned_cart_time ) {
                $query_ignored = "UPDATE `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` SET cart_ignored = '1' WHERE id ='" . $cart_id . "'";
                $wpdb->query( $query_ignored );
                return 1;
            } else if( $results_query_email[0]->post_status == "wc-pending" || $results_query_email[0]->post_status == "wc-failed" ) {

                /**
                 * If the post status are pending or failed  the send them for abandoned cart reminder emails 
                 */
                return 0;
            }
        }
        return 0;
    }

    /**
     * It will give the email sent id of the cart id.
     * @param int | string $wcap_cart_id Abadoned cart id
     * @globals mixed $wpdb
     * @return int $wcap_sent_id Email sent id
     * @return int 0 Email not sent
     * @since 5.0
     */
    public static function wcap_get_cart_sent_data ( $wcap_cart_id ) {
        global $wpdb;

        $wcap_query   = "SELECT id FROM `" . WCAP_EMAIL_SENT_HISTORY_TABLE . "` WHERE abandoned_order_id = %d  AND recovered_order = '0' ORDER BY 'id' DESC LIMIT 1 ";
        $wcap_results = $wpdb->get_results ( $wpdb->prepare( $wcap_query , $wcap_cart_id ) );

        if ( count( $wcap_results ) > 0 ) {
            $wcap_sent_id = $wcap_results[0]->id;
            return $wcap_sent_id;
        }
        return 0;
    }

    /**
     * This function will return the email address needed for the selected template. As we have given the choice to the admin that 
     * he can choose who will recive the template.
     * @param int | string $wcap_template_id Template Id
     * @param string $wcap_user_type User type
     * @param int | string $wcap_user_id User id
     * @param string $wcap_admin_email Admin Email address
     * @globals mixed $wpdb
     * @return string $wcap_email_address Email ids on reminder need to send
     * @since: 7.0
     */
    public static function wcap_get_email_for_template( $wcap_template_id, $wcap_user_type, $wcap_user_id, $wcap_admin_email ) {

        global $wpdb;
        $wcap_email_address = '';

        $wcap_email_action = get_post_meta ( $wcap_template_id , 'wcap_email_action' );
        if ( isset( $wcap_email_action [0] ) && '' != $wcap_email_action [0] ) {
            $wcap_email_action = $wcap_email_action[0];
        }

        if ( 'wcap_email_customer' === $wcap_email_action || 'wcap_email_customer_admin' === $wcap_email_action ) {

            if( "GUEST" === $wcap_user_type && '0' != $wcap_user_id ) {
                
                $query_guest        = "SELECT billing_first_name, billing_last_name, email_id FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "` WHERE id = %d";
                $results_guest      = $wpdb->get_results( $wpdb->prepare( $query_guest, $wcap_user_id ) );
                if( count( $results_guest ) > 0 ) {
                    $wcap_email_address = $results_guest[0]->email_id;
                }
            } else if( "GUEST" != $wcap_user_type && '0' != $wcap_user_id ) { 
                $key                = 'billing_email';
                $single             = true;
                $user_billing_email = get_user_meta( $wcap_user_id, $key, $single );
                if( isset( $user_billing_email ) && $user_billing_email != '' ) {
                    $user_email = $user_billing_email;
               } else {
                    $user_data          = get_userdata( $wcap_user_id );
                    if ( isset( $user_data->user_email ) && '' != $user_data->user_email ) {
                        $user_email = $user_data->user_email;
                    }
                }
                $wcap_email_address = sanitize_email( $user_email );
            }
        } else if ( 'wcap_email_admin' === $wcap_email_action ) {
            $wcap_email_address = sanitize_email( $wcap_admin_email );
        } else if ( 'wcap_email_others' === $wcap_email_action ) {

            $wcap_email_other_action = get_post_meta( $wcap_template_id, 'wcap_other_emails' );
            
            if ( count ( $wcap_email_other_action ) > 0 && '' != $wcap_email_other_action[0] ) {
                $wcap_explode_emails = explode( "\n", $wcap_email_other_action[0] );
                $wcap_other_emails_list = '';
                foreach( $wcap_explode_emails as $emails ) {
                    if( trim( $emails ) != '' ) {
                        if( $wcap_other_emails_list == '' ) {
                            $wcap_other_emails_list = sanitize_email( $emails );
                        } else {
                            $wcap_other_emails_list .= "," . sanitize_email( $emails );
                        }
                    }
                }
            }
            $wcap_email_address = $wcap_other_emails_list;
        }

        if ( 'wcap_email_customer_admin' == $wcap_email_action ) {
            $wcap_email_address = $wcap_email_address . ' , ' . $wcap_admin_email;
        }
      
        $validate = self::wcap_validate_email_format( $wcap_email_address );
        
        if( $validate === 1 ) {
            return sanitize_text_field( $wcap_email_address );
        } else {
            return '';
        }
      
    }

    /**
     * This function will return the email address of the customer for the cart. As we have given the choice to the admin that 
     * he can choose who will recive the template.
     * @param string $wcap_user_type User type
     * @param int | string $wcap_user_id User id
     * @globals mixed $wpdb
     * @return string $wcap_email_address Email ids of customer
     * @since: 7.0
     */
    public static function wcap_get_customers_email ( $wcap_user_id, $wcap_user_type ) {
        global $wpdb;

        $wcap_email_address = '';
        if( "GUEST" == $wcap_user_type && '0' != $wcap_user_id ) {
                
            $query_guest        = "SELECT billing_first_name, billing_last_name, email_id FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "` WHERE id = %d";
            $results_guest      = $wpdb->get_results( $wpdb->prepare( $query_guest, $wcap_user_id ) );
            if( count( $results_guest ) > 0 ) {
                $wcap_email_address = $results_guest[0]->email_id;
            }
        } else if( "GUEST" != $wcap_user_type && '0' != $wcap_user_id ) { 
            $key                = 'billing_email';
            $single             = true;
            $user_billing_email = get_user_meta( $wcap_user_id, $key, $single );
            if( isset( $user_billing_email ) && $user_billing_email != '' ) {
               $wcap_email_address = $user_billing_email;
           }else{
                $user_data          = get_userdata( $wcap_user_id );
                if ( isset( $user_data->user_email ) && '' != $user_data->user_email ) {
                    $wcap_email_address = $user_data->user_email;
                }
            }
        }
        return $wcap_email_address;
    }

    /**
     * This function will return the phone number of the customer for the cart.
     * @param string $wcap_user_type User type
     * @param int | string $wcap_user_id User id
     * @globals mixed $wpdb
     * @return string $wcap_customer_phone Phone number of customer
     * @since: 7.0
     */
    public static function wcap_get_customers_phone ( $wcap_user_id, $wcap_user_type ) {
        global $wpdb;

        $wcap_customer_phone = '';
        if( "GUEST" == $wcap_user_type && '0' != $wcap_user_id ) {
                
            $get_phone_query_guest = "SELECT phone FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "` WHERE id = %d";
            $results_guest         = $wpdb->get_results( $wpdb->prepare( $get_phone_query_guest, $wcap_user_id ) );
            if( count( $results_guest ) > 0 ) {
                $wcap_customer_phone = $results_guest[0]->phone;
            }
        } else if( "GUEST" != $wcap_user_type && '0' != $wcap_user_id ) { 
            $user_phone_number = get_user_meta( $wcap_user_id, 'billing_phone' );
            if( isset( $user_phone_number[0] ) ) {
                $wcap_customer_phone = $user_phone_number[0];
            }
        }
        return $wcap_customer_phone;
    }

    /**
     * It will check that email template is sent for the abandoned cart.
     * @param int | string $user_id User id
     * @param timestamp $cart_update_time Abandoned cart time
     * @param int | string $template_id Template id
     * @param int | string $id Abandoned cart id
     * @globals mixed $wpdb
     * @return true Send email
     * @return false Dont send email
     * @since 5.0
     */
    public static function wcap_check_sent_history( $user_id, $cart_update_time, $template_id, $id ) {
        global $wpdb;
        $query   = "SELECT wpcs . * , wpac . abandoned_cart_time , wpac . user_id FROM `" . WCAP_EMAIL_SENT_HISTORY_TABLE . "` AS wpcs LEFT JOIN " . WCAP_ABANDONED_CART_HISTORY_TABLE . " AS wpac ON wpcs.abandoned_order_id =  wpac.id WHERE template_id= %d AND wpcs.abandoned_order_id = %d ORDER BY 'id' DESC LIMIT 1 ";
        $results = $wpdb->get_results ( $wpdb->prepare( $query , $template_id , $id ) );
        if ( count( $results ) == 0 ) {
            return true;
        } elseif ( $results[0]->abandoned_cart_time < $cart_update_time ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * It will create the unique coupon code.
     * @param int $discount_amt Discount amount
     * @param string $get_discount_type Discount type
     * @param date $get_expiry_date Expiry date
     * @param array | object $coupon_post_meta Data of Parent coupon
     * @return string $final_string 12 Digit unique coupon code name
     * @since 2.3.6
     */
    public static function wp_coupon_code( $discount_amt, $get_discount_type, $get_expiry_date, $coupon_post_meta ) {
        $ten_random_string = Wcap_Send_Email_Using_Cron::wp_random_string();
        $first_two_digit   = rand( 0, 99 );
        $final_string      = $first_two_digit.$ten_random_string;
        $datetime          = $get_expiry_date ; 
        $coupon_code       = $final_string;
        $coupon_product_categories         = isset( $coupon_post_meta['product_categories'] [ 0 ] ) && $coupon_post_meta['product_categories'] [ 0 ] != '' ? unserialize( $coupon_post_meta['product_categories'] [ 0 ] )  : array();

        $coupon_exculde_product_categories = isset( $coupon_post_meta['exclude_product_categories'] [ 0 ] ) && $coupon_post_meta['exclude_product_categories'] [ 0 ] != '' ? unserialize ( $coupon_post_meta['exclude_product_categories'] [ 0 ] ) : array();

        $coupon_product_ids                = isset( $coupon_post_meta['product_ids'] [ 0 ] ) && $coupon_post_meta['product_ids'] [ 0 ] != '' ? $coupon_post_meta['product_ids'] [ 0 ] : '';

        $coupon_exclude_product_ids        = isset( $coupon_post_meta['exclude_product_ids'] [ 0 ] ) && $coupon_post_meta['exclude_product_ids'] [ 0 ] != '' ? $coupon_post_meta['exclude_product_ids'] [ 0 ] : '';

        $individual_use                    = 'yes'; // defaulted to yes, as auto created coupons always need to be for a one-time use.

        $coupon_free_shipping              = isset( $coupon_post_meta['free_shipping'] [ 0 ] ) && $coupon_post_meta['free_shipping'] [ 0 ] != '' ? $coupon_post_meta['free_shipping'] [ 0 ] : 'no';

        $coupon_minimum_amount             = isset( $coupon_post_meta['minimum_amount'] [ 0 ] ) && $coupon_post_meta['minimum_amount'] [ 0 ] != '' ? $coupon_post_meta['minimum_amount'] [ 0 ] : '';

        $coupon_maximum_amount             = isset( $coupon_post_meta['maximum_amount'] [ 0 ] ) && $coupon_post_meta['maximum_amount'] [ 0 ] != '' ? $coupon_post_meta['maximum_amount'] [ 0 ] : '';

        $coupon_exclude_sale_items         = isset( $coupon_post_meta['exclude_sale_items'] [ 0 ] ) && $coupon_post_meta['exclude_sale_items'] [ 0 ] != '' ? $coupon_post_meta['exclude_sale_items'] [ 0 ] : 'no';

        $use_limit                         = isset( $coupon_post_meta['usage_limit'] [ 0 ] ) && $coupon_post_meta['usage_limit'] [ 0 ] != '' ? $coupon_post_meta['usage_limit'] [ 0 ] : '';

        $use_limit_user                    = isset( $coupon_post_meta['usage_limit_per_user'] [ 0 ] ) && $coupon_post_meta['usage_limit_per_user'] [ 0 ] != '' ? $coupon_post_meta['usage_limit_per_user'] [ 0 ] : '';

        if( class_exists( 'WC_Free_Gift_Coupons' ) ) {
            $free_gift_coupon              = isset( $coupon_post_meta['gift_ids'] [ 0 ] ) && $coupon_post_meta['gift_ids'] [ 0 ] != '' ? $coupon_post_meta['gift_ids'] [ 0 ] : '';
            $free_gift_shipping            = isset( $coupon_post_meta['free_gift_shipping'] [ 0 ] ) && $coupon_post_meta['free_gift_shipping'] [ 0 ] != '' ? $coupon_post_meta['free_gift_shipping'] [ 0 ] : 'no';
        }
        if( is_plugin_active( 'yith-woocommerce-brands-add-on/init.php' ) ) {
            $coupon_brand = isset( $coupon_post_meta['brand'] [ 0 ] ) && $coupon_post_meta['brand'] [ 0 ] != '' ? unserialize ( $coupon_post_meta['brand'] [ 0 ] ) : array();
        }
        $amount        = $discount_amt;
        $discount_type = $get_discount_type;
        $coupon        = array(
                    'post_title'   => $coupon_code,
                    'post_content' => 'This coupon provides 5% discount on cart price.',
                    'post_status'  => 'publish',
                    'post_author'  => 1,
                    'post_type'    => 'shop_coupon',
                    'post_expiry_date' => $datetime,
                );
        $new_coupon_id = wp_insert_post( $coupon );
        // Add meta
        update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
        update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
        update_post_meta( $new_coupon_id, 'minimum_amount', $coupon_minimum_amount );
        update_post_meta( $new_coupon_id, 'maximum_amount', $coupon_maximum_amount );
        update_post_meta( $new_coupon_id, 'individual_use', $individual_use );
        update_post_meta( $new_coupon_id, 'free_shipping', $coupon_free_shipping );
        update_post_meta( $new_coupon_id, 'product_ids', '' );
        update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
        update_post_meta( $new_coupon_id, 'usage_limit', $use_limit );
        update_post_meta( $new_coupon_id, 'usage_limit_per_user', $use_limit_user );
        update_post_meta( $new_coupon_id, 'expiry_date', $datetime );
        update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
        update_post_meta( $new_coupon_id, 'product_ids', $coupon_product_ids );
        update_post_meta( $new_coupon_id, 'exclude_sale_items', $coupon_exclude_sale_items );
        update_post_meta( $new_coupon_id, 'exclude_product_ids', $coupon_exclude_product_ids );
        update_post_meta( $new_coupon_id, 'product_categories', $coupon_product_categories );
        update_post_meta( $new_coupon_id, 'exclude_product_categories', $coupon_exculde_product_categories );
        if( class_exists( 'WC_Free_Gift_Coupons' ) ) {
            update_post_meta( $new_coupon_id, 'gift_ids', $free_gift_coupon );
            update_post_meta( $new_coupon_id, 'free_gift_shipping', $free_gift_shipping );
        }
        if( is_plugin_active( 'yith-woocommerce-brands-add-on/init.php' ) ) {
            update_post_meta( $new_coupon_id, 'brand', $coupon_brand );
        }
        return $final_string;
    }
    /**
     * It will generate 12 digit unique string for coupon code.
     * @return string $temp_array 12 digit unique string
     * @since 2.3.6
     */
    public static function wp_random_string() {
        $character_set_array   = array();
        $character_set_array[] = array( 'count' => 5, 'characters' => 'abcdefghijklmnopqrstuvwxyz' );
        $character_set_array[] = array( 'count' => 5, 'characters' => '0123456789' );
        $temp_array            = array();
        foreach ( $character_set_array as $character_set ) {
            for ( $i = 0; $i < $character_set['count']; $i++ ) {
                    $temp_array[] = $character_set['characters'][ rand( 0, strlen( $character_set['characters'] ) - 1 ) ];
                }
            }
        shuffle( $temp_array );
        return implode( '', $temp_array );
    }
    /**
     * It will give the translated text from the WPML.
     * @param string $get_translated_text Id of the message
     * @param string $message Message
     * @param string $language Selected language
     * @global mixed $wpdb
     * @return $message Message
     * @since 2.6
     */
    public static function wcap_get_translated_texts( $get_translated_text, $message, $language ) {
        if( function_exists( 'icl_register_string' ) ) {
            if( $language == 'en' ) {
                return $message;
            } else {
                global $wpdb;
                $context    = 'WCAP';
                $translated = '';
                $results    = $wpdb->get_results( $wpdb->prepare("
                    SELECT s.name, s.value, t.value AS translation_value, t.status
                    FROM  {$wpdb->prefix}icl_strings s
                    LEFT JOIN {$wpdb->prefix}icl_string_translations t ON s.id = t.string_id
                    WHERE s.context = %s
                    AND (t.language = %s OR t.language IS NULL)
                    ", $context, $language ), ARRAY_A);
                foreach( $results as $each_entry ) {
                    if( $each_entry['name'] == $get_translated_text ) {
                        if( $each_entry['translation_value'] ) {
                            $translated = $each_entry['translation_value'];
                        } else {
                            $translated = $each_entry['value'];
                        }
                    }
                }
                if ( $translated != '' ) {
                    return $translated;
                } else {
                    return $message;
                }
            }
        } else {
            return $message;
       }
    }
    /**
     * It will validate the email format.
     * @param string $wcap_email_address Email address
     * @return int 1 Correct format of email
     * @since 3.7
     */
    public static function wcap_validate_email_format( $wcap_email_address ) {

        /**
         * As we have given the choice to admin, that he can send email template to any one.
         * So we need to check each email address is in correct format in or not.
         * If any one of the email address is correct then send the email.
         * We are having the multiple email address in the comma seprated so we need to check each email format.
         */
        $wcap_explode_emails = explode(',', $wcap_email_address );
        $validated_value_array = array();
        
        if( version_compare( phpversion(), '5.2.0', '>=' ) ) {
            foreach ($wcap_explode_emails as $wcap_explode_emails_key => $wcap_explode_emails_value ) {

                $wcap_sanitize_email_add = sanitize_text_field ( $wcap_explode_emails_value);
                $validated_value = filter_var( sanitize_text_field ( $wcap_explode_emails_value) , FILTER_VALIDATE_EMAIL );
                if( $validated_value == $wcap_sanitize_email_add ) {
                    $validated_value_array [] = 1;
                } else {
                    $validated_value_array [] = 0;
                }
            }
        } else {
            $pattern         = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';

            foreach ( $wcap_explode_emails as $wcap_explode_emails_key => $wcap_explode_emails_value ) {

                $validated_value = preg_match( $pattern, $wcap_explode_emails_value ) ;

                $validated_value_array [] = $validated_value;
            }
            
        }
        if ( in_array( 1 , $validated_value_array) ){
            $validated_value = 1;   
        }

        return $validated_value;
    }
    
    /**
     * Send SMS Reminders if enabled.
     *
     * @hook woocommerce_ac_send_email_action
     * @since 7.9
     */
    public static function wcap_send_sms_notifications() {
        $enable_sms = get_option( 'wcap_enable_sms_reminders' );
    
        if( isset( $enable_sms ) && 'on' == $enable_sms ) {
            self::wcap_send_sms_reminders();
    
        }
    }
    
    /**
     * Sends the reminder emails for all SMS templates
     * and abandoned carts.
     *
     * @since 7.9
     */
    static function wcap_send_sms_reminders() {
    
        // check if all the details are correctly filled
        $sid = get_option( 'wcap_sms_account_sid' );
        $token = get_option( 'wcap_sms_auth_token' );
        $from_phone = get_option( 'wcap_sms_from_phone' );
    
        if( $sid == '' || $token == '' || $from_phone == '' ) {
            return;
        } else {
            $twilio_details = array( 'sid' => $sid,
                'token' => $token,
                'from_phone' => $from_phone
            );
        }
    
        // require the common functions file
        require_once( WP_PLUGIN_DIR . '/woocommerce-abandon-cart-pro/includes/wcap_functions.php' );
        require_once( WP_PLUGIN_DIR . '/woocommerce-abandon-cart-pro/includes/wcap_tiny_url.php' );
    
        // get the SMS Templates
        $sms_templates = wcap_get_notification_templates( 'sms' );
    
        if( is_array( $sms_templates ) && count( $sms_templates ) > 0 ) {
    
            $current_time = current_time( 'timestamp' );
            $registered_cut_off = get_option( 'ac_cart_abandoned_time' ) * 60;
            $guest_cut_off = get_option( 'ac_cart_abandoned_time_guest' ) * 60;
    
            foreach( $sms_templates as $frequency => $template_data ) {
    
                // template ID
                $template_id = $template_data[ 'id' ];
    
                $time_check_registered = $current_time - $frequency - $registered_cut_off;
                $time_check_guest = $current_time - $frequency - $guest_cut_off;
                // get abandoned carts
                $carts = wcap_get_notification_carts( $time_check_registered, $time_check_guest, $template_id );
                //     wp_mail( 'pinalj1612@gmail.com',"carts $template_id",print_r($carts,true ));
                if( is_array( $carts ) && count( $carts ) > 0 ) {
                    foreach( $carts as $cart_data ) {
                        // SMS Reminders
                        self::wcap_send_sms( $cart_data, $template_data, $twilio_details );
    
                        // Delete the cart ID from notifications meta
                        $cart_id = $cart_data->id;
                        wcap_update_meta( $template_id, $cart_id );
    
                    }
                }
            }
        }
    }
    
    /**
     * Sends the SMS Reminder for the abandoned cart
     *
     * @param object $cart_data - Data from the Cart History Table
     * @param object $template_data - SMS Template Data
     * @param array $twilio_details - Twilio Connection Details
     * @since 7.9
     */
    static function wcap_send_sms( $cart_data, $template_data, $twilio_details ) {
    
        // cart Data - cart ID, cart info, user ID, cart abandoned time
        // template data - body, coupon code
        $message_temp = $template_data[ 'body' ];
    
        $coupon_code = $template_data[ 'coupon_code' ];
        $msg_body = self::wcap_replace_sms_tags( $message_temp, $cart_data, $template_data );

        // get the phone number to send the SMS to
        $to_phone = self::get_phone( $cart_data->user_id );

        $from_phone = $twilio_details[ 'from_phone' ];
        $sid = $twilio_details[ 'sid' ];
        $token = $twilio_details[ 'token' ];

        // send the message
        if( $to_phone ) {
            
            try {
                $client = new Client($sid, $token);
        
                $message = $client->messages->create(
                                $to_phone,
                                array(
                                    'from' => $from_phone,
                                    'body' => $msg_body,
                            )
                );
                
                $template_id = $template_data[ 'id' ];
                $cart_id = $cart_data->id;
                
                if( $message->sid ) {
                    $message_sid = $message->sid;
                
                    $message_details = $client->messages( $message_sid )->fetch();
                
                    $status = $message_details->status;
                
                    // update the details in the tiny urls
                    self::wcap_update_sms_details( $template_id, $cart_id, $to_phone, $status, $message_sid );
                
                    // update the count
                    self::wcap_update_sms_count( $template_id, 1 );
                }
            } catch( Exception $e ) {
                $msg = $e->getMessage();
            }
        }
    
    }
    
    /**
     * Returns the Phone number of the user
     *
     * @param integer $user_id - User ID
     * @return string`|boolean - Phone Number
     *
     * @since 7.9
     */
    static function get_phone( $user_id ) {
    
        global $wpdb;

        $country_map = Wcap_Common::wcap_country_code_map();

        $to_phone = '';
        // User Name
        if( $user_id >= 63000000 ) {
            $phone_query = "SELECT phone, billing_country FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "`
                                WHERE id = %d";
            $phone = $wpdb->get_results( $wpdb->prepare( $phone_query, $user_id ) );
            
            if( is_array( $phone ) && count( $phone ) > 0 ) {
                $billing_country = $phone[0]->billing_country;
    
                $dial_code = isset( $country_map[$billing_country] ) ? $country_map[$billing_country]['dial_code'] : '';
                $to_phone = $phone[0]->phone;
            }
        } else {
            $user = get_user_by( 'id', $user_id );

            $billing_country = $user->billing_country;
            $dial_code = isset( $country_map[$billing_country] ) ? $country_map[$billing_country]['dial_code'] : '';

            $to_phone = $user->billing_phone;
        }
        
        // Verify the Phone number
        if( is_numeric( $to_phone ) ) {
            // if first character is not a +, add it
            if( substr( $to_phone, 0, 1 ) != '+' ) {
                if ( $dial_code !== '' ) {
                    $to_phone = $dial_code . $to_phone;
                }else {
                    $to_phone = '+' . $to_phone;
                }
            }
            return $to_phone;
        } else {
            return false;
        }
    
    }
    
    /**
    * Replace the merge tags with cart data
    *
    * @param string $body - SMS text
    * @param object $cart_data - Cart Data
    * @param integer $coupon_code - Coupon code
    * @return string $msg - SMS text
    *
    * @since 7.9
    */
    static function wcap_replace_sms_tags( $body, $cart_data, $template_data ) {
    
        global $wpdb;
        
        $user_id = $cart_data->user_id;
        // User Name
        if( $user_id >= 63000000 ) {
            $name_query = "SELECT billing_first_name FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "`
                            WHERE id = %d";
            $name = $wpdb->get_col( $wpdb->prepare( $name_query, $user_id ) );
            
            if( is_array( $name ) && count( $name ) > 0 ) {
                $replace_tags[ '{{user.name}}' ] = $name[0];
            }
        } else {
        
            $user = get_user_by( 'id', $user_id );
            $replace_tags[ '{{user.name}}' ] = $user->first_name;
        }

        $abandoned_id = $cart_data->id;
        $template_id = $template_data[ 'id' ];
        
        // Date Abandoned
        $replace_tags[ '{{date.abandoned}}' ] = date( 'Y-m-d', $cart_data->abandoned_cart_time );
         // Shop Name
        $replace_tags[ '{{shop.name}}' ] = get_option( 'blogname' );
        // Shop Link
        if( strpos( $body, '{{shop.link}}' ) !== false ) {
        
            $shop_link = wc_get_page_permalink( 'shop' );
        
            // shorten it
            $shortened_shop_link = WCAP_Tiny_Url::get_short_url( $shop_link );
        
            $wpdb->insert( WCAP_TINY_URLS,
                array( 'cart_id'        => $abandoned_id,
                    'template_id'    => $template_id,
                    'long_url'       => $shop_link,
                    'short_code'     => $shortened_shop_link,
                    'date_created'   => current_time( 'timestamp' ),
                    'counter'        => 0,
                    'notification_data' => json_encode( array( 'link_clicked' => 'Shop Page'  ) ),
                )
            );
            $insert_id = $wpdb->insert_id;
        
            // add the website url to the short url
            $shop_link = get_option( 'siteurl' ) . "/$shortened_shop_link";
        
            $replace_tags[ '{{shop.link}}' ] = $shop_link;
        
        } else {
            $replace_tags[ '{{shop.link}}' ] = '';
        }
        
        if( strpos( $body, '{{checkout.link}}' ) !== false ) {
            
            /** Checkout Link **/
            
            // generate the long url
            $db_id = generate_checkout_url( $cart_data, $template_data, 'sms_link' );
        
            // get the long url
            $long_url = WCAP_Tiny_Url::get_long_url_from_id( $db_id );
            // shorten it
            $short_url = WCAP_Tiny_Url::get_short_url( $long_url );
    
            // update the DB
            WCAP_Tiny_Url::update_short_url( $db_id, $short_url );
    
            // add the website url to the short url
            $short_url = get_option( 'siteurl' ) . "/$short_url";
    
            $replace_tags[ '{{checkout.link}}' ] = $short_url;

        } else {
            $replace_tags[ '{{checkout.link}}' ] = '';
        }
        
        // Admin Phone Number
        $user_admin = get_user_by( 'email', get_option( 'admin_email' ) );
        $admin_id = $user_admin->ID;
        $replace_tags[ '{{phone.number}}' ] = get_user_meta( $admin_id, 'billing_phone', true );

        // coupon code
        $coupon_id = $template_data[ 'coupon_code' ];
        $coupon_to_apply        = get_post( $coupon_id, ARRAY_A );
        $coupon_code            = $coupon_to_apply[ 'post_title' ];
        $replace_tags[ '{{coupon.code}}' ] = $coupon_code;
        
        // replace
        $msg = $body;
        foreach( $replace_tags as $key => $value ) {
            $msg = str_replace( $key, $value, $msg );
        }
    
        return $msg;
    }
    
    /**
    * Update the SMS Sent count
    *
    * @param integer $template_id - SMS Template ID
    * @param integer $update_by - Number by which to update the count
    *
    * @since 7.9
    */
     static function wcap_update_sms_count( $template_id, $update_by = 1 ) {
    
        // get the existing count
        $count = wcap_get_notification_meta( $template_id, 'sent_count' );
    
        if( ! $count ) {
            $count = 0;
        }
        // update it
        $count += $update_by;
        // update in DB
        wcap_update_notification_meta( $template_id, 'sent_count', $count );
    }
    
    /**
     * Update SMS details in the Tiny URLs table.
     * 
     * @param integer $template_id - SMS Template ID
     * @param integer $cart_id - Abandoned Cart ID
     * @param string $to_phone - Phone Number to which SMS has been sent E.164 format
     * @param string $sms_status - SMS Status
     * @param string $message_sid - Message ID (received from Twilio)
     * @since 7.10.0
     */
    static function wcap_update_sms_details( $template_id, $cart_id, $to_phone, $sms_status, $message_sid ) {
    
        global $wpdb;
    
        // get the record from tiny urls table
        $get_id = "SELECT id, notification_data FROM " . WCAP_TINY_URLS . "
                        WHERE cart_id = %d
                        AND template_id = %d";
    
        $record_id = $wpdb->get_results( $wpdb->prepare( $get_id, $cart_id, $template_id ) );
    
        if( is_array( $record_id ) && count( $record_id ) > 0 ) {
            foreach( $record_id as $r_id ) {
                $notification_data = json_decode( $r_id->notification_data );
    
                // prepare the data to be inserted
                $notification_data->phone_number = $to_phone;
                $notification_data->sent_time = current_time( 'timestamp' );
                $notification_data->sms_status = $sms_status;
                $notification_data->msg_id = $message_sid;
                $id = $r_id->id;
     
                // update the record
                $wpdb->update( WCAP_TINY_URLS, array( 'notification_data' => json_encode( $notification_data ) ), array( 'id' => $r_id->id ) );
    
            }
        }
    
    }
}
?>
