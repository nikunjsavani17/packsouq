<?php
/**
 * 
 * It contain all the functions for ajax call.
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Ajax-Functions
 * @since   5.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if ( !class_exists('Wcap_Ajax' ) ) {

    /**
     * This class contain all the ajax function used in the plugin.
     */
    class Wcap_Ajax{

        /**
         * It will add the option to the database when admin don't wish to import the lite plugin data to pro version.
         * It will also deactivate the lite plugin data.  
         * @hook wp_ajax_wcap_do_not_import_lite_data 
         * @since: 8.0
         */
        public static function wcap_do_not_import_lite_data () {

            $wcap_lite_plugin_path =   ( dirname( dirname ( WCAP_PLUGIN_FILE ) ) ) . '/woocommerce-abandoned-cart/woocommerce-ac.php';
            deactivate_plugins( $wcap_lite_plugin_path );

            /**
             * Add  option which button is clicked for the record.
             */
            add_option ( 'wcap_lite_data_imported', 'no' ); 
            wp_die();
        }

        /**
         * This function will import the data of the lite version. Like abandoned carts, email templates, settings, sent history.
         * @hook wp_ajax_wcap_import_lite_datas
         * @globals mixed $wpdb
         * @since: 8.0
         */

        public static function wcap_import_lite_data () {

            global $wpdb;
            if ( 'true' === $_POST['wcap_import_ac_cart'] ) {
                $wcap_abandoned_cart_history_data_query = "INSERT INTO `". WCAP_ABANDONED_CART_HISTORY_TABLE ."` ( `id`,`user_id`, `abandoned_cart_info`, `abandoned_cart_time`, `cart_ignored`, `recovered_cart`, `user_type`, `unsubscribe_link`, `session_id` ) SELECT `id`,`user_id`, `abandoned_cart_info`, `abandoned_cart_time`, `cart_ignored`, `recovered_cart`, `user_type`, `unsubscribe_link`, `session_id` FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` ON DUPLICATE KEY UPDATE
                    `id` = VALUES (`id`),  
                    `user_id` = VALUES (`user_id`),
                    `abandoned_cart_info` = VALUES (`abandoned_cart_info`),
                    `abandoned_cart_time` = VALUES (`abandoned_cart_time`),
                    `cart_ignored` = VALUES (`cart_ignored`),
                    `recovered_cart` = VALUES (`recovered_cart`),
                    `user_type` = VALUES (`user_type`),
                    `unsubscribe_link` = VALUES (`unsubscribe_link`),
                    `session_id` = VALUES (`session_id`)
                      "; 
                $wpdb->query ($wcap_abandoned_cart_history_data_query);
                
                $wcap_abandoned_cart_guest_history_data_query = "INSERT INTO `". WCAP_GUEST_CART_HISTORY_TABLE ."` ( `id`,`billing_first_name`, `billing_last_name`, `billing_company_name`, `billing_address_1`, `billing_address_2`, `billing_city`, `billing_county`, `billing_zipcode`, `email_id`, `phone`, `ship_to_billing`, `order_notes`, `shipping_first_name`, `shipping_last_name`, `shipping_company_name`, `shipping_address_1`, `shipping_address_2`, `shipping_city`, `shipping_county`, `shipping_zipcode`, `shipping_charges` ) 
                    SELECT `id`, `billing_first_name`, `billing_last_name`, `billing_company_name`, `billing_address_1`, `billing_address_2`, `billing_city`, `billing_county`, `billing_zipcode`, `email_id`, `phone`, `ship_to_billing`, `order_notes`, `shipping_first_name`, `shipping_last_name`, `shipping_company_name`, `shipping_address_1`, `shipping_address_2`, `shipping_city`, `shipping_county`, `shipping_zipcode`, `shipping_charges` FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` ON DUPLICATE KEY UPDATE
                        `id` = VALUES (`id`),
                        `billing_first_name` = VALUES (`billing_first_name`), 
                        `billing_last_name` = VALUES (`billing_last_name`), 
                        `phone` = VALUES (`phone`), 
                        `email_id` = VALUES (`email_id`)
                    ";
                $wpdb->query ($wcap_abandoned_cart_guest_history_data_query );
            }

            $wcap_is_settings_checked = 1;
            if ( 'true' === $_POST['wcap_import_settings'] ) {
                
                $wcap_get_lite_cut_off_time   = get_option ( 'ac_lite_cart_abandoned_time' );
                if ( isset( $wcap_get_lite_cut_off_time ) && '' != $wcap_get_lite_cut_off_time ) {
                    update_option ( 'ac_cart_abandoned_time',       $wcap_get_lite_cut_off_time );
                    update_option ( 'ac_cart_abandoned_time_guest', $wcap_get_lite_cut_off_time );
                }
                
                $wcap_get_lite_admin_recovery = get_option ( 'ac_lite_email_admin_on_recovery' );
                if ( ( $wcap_get_lite_admin_recovery == 'on' || '' == $wcap_get_lite_admin_recovery ) && false !== $wcap_get_lite_admin_recovery ) {

                    update_option( 'ac_email_admin_on_recovery', $wcap_get_lite_admin_recovery );
                }

                $wcap_get_lite_visitors       = get_option ( 'ac_lite_track_guest_cart_from_cart_page' );  
                if ( isset( $wcap_get_lite_visitors ) && ( $wcap_get_lite_visitors == 'on' || '' == $wcap_get_lite_visitors ) 
                     && false !== $wcap_get_lite_admin_recovery ) {
                    update_option ( 'ac_track_guest_cart_from_cart_page', $wcap_get_lite_visitors );
                }

                $wcal_from_name      = get_option ( 'wcal_from_name' );
                if ( isset( $wcal_from_name ) && '' != $wcal_from_name ) {
                    update_option ( 'wcap_from_name', $wcal_from_name );
                }
                
                $wcal_from_email     = get_option ( 'wcal_from_email' );
                if ( isset( $wcal_from_email ) && '' != $wcal_from_email ) {
                    update_option ( 'wcap_from_email', $wcal_from_email );
                }
            
                $wcal_reply_email    = get_option ( 'wcal_reply_email' );
                if ( isset( $wcal_reply_email ) && '' != $wcal_reply_email ) {
                    update_option ( 'wcap_reply_email', $wcal_reply_email );
                }

            }

            $wcap_get_all_templates = "SELECT id from `".$wpdb->prefix."ac_email_templates` WHERE `default_template` = '1' ";
            if ( 'true' === $_POST['wcap_import_template'] ) {

                $wcap_replace_with_merge_code = addslashes ( '<table border="0" cellspacing="5" align="center"><caption><b>Cart Details</b>
                    </caption>
                    <tbody>
                    <tr>
                    <th></th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    </tr>
                    <tr style="background-color:#f4f5f4;"><td>{{item.image}}</td><td>{{item.name}}</td><td>{{item.price}}</td><td>{{item.quantity}}</td><td>{{item.subtotal}}</td></tr>
                    <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <th>Cart Total:</th>
                    <td>{{cart.total}}</td>
                    </tr></tbody></table>
                    <br> <br>') ;

                $wcap_update_templates_mergecode = 'UPDATE `'.$wpdb->prefix.'ac_email_templates_lite`
                    SET `body` = replace( `body`, "{{products.cart}}", "'.$wcap_replace_with_merge_code.'" ) ';
                $wpdb->query ( $wcap_update_templates_mergecode );

                $wcap_templates_data_query = "INSERT INTO `". WCAP_EMAIL_TEMPLATE_TABLE ."` ( `subject`, `body`, `is_active`, `frequency`, `day_or_hour`, `template_name`, `is_wc_template`, `default_template`, `wc_email_header` ) 
                    SELECT `subject`, `body`, '0', `frequency`, `day_or_hour`, `template_name`, `is_wc_template`, `default_template`, `wc_email_header` FROM `".$wpdb->prefix."ac_email_templates_lite` "; 
                $wpdb->query ($wcap_templates_data_query); 

                $wcap_get_all_templates = "SELECT id from `".$wpdb->prefix."ac_email_templates`";
            }

            $wcap_get_result_of_templates =  $results = $wpdb->get_results( $wcap_get_all_templates );

            $wcap_current_time = current_time( 'timestamp' );
            if ( count( $wcap_get_result_of_templates ) > 0 ) {

                foreach ( $wcap_get_result_of_templates as $wcap_get_result_of_templates_key => $wcap_get_result_of_templates_value) {

                    if ( isset( $wcap_get_result_of_templates_value->id ) ) {
                        $wcap_template_id = $wcap_get_result_of_templates_value->id;
                        add_post_meta ( $wcap_template_id , 'wcap_template_time', $wcap_current_time );
                        
                        $wcap_get_email_action = get_post_meta ( $wcap_template_id , 'wcap_email_action');

                        $wcap_admin_setting = get_option ('ac_email_admin_on_abandoned');

                        if ( isset( $wcap_admin_setting ) && 'on' == $wcap_admin_setting ) {
                            $wcap_customers_key = 'wcap_email_customer_admin';
                        }else{
                            $wcap_customers_key = 'wcap_email_customer';
                        }
                            
                        add_post_meta ( $wcap_template_id , 'wcap_email_action', $wcap_customers_key );
                        
                    }
                }
            } 
            
            $wcap_lite_plugin_path =   ( dirname( dirname ( WCAP_PLUGIN_FILE ) ) ) . '/woocommerce-abandoned-cart/woocommerce-ac.php';
            deactivate_plugins( $wcap_lite_plugin_path );
            /**
             * Add  option which button is clicked for the record.
             */
            add_option ( 'wcap_lite_data_imported', 'yes' );

            echo "Setting imorted";
            
            wp_die();
        }

        /**
         * This ajax create the preview email content for the without WooCommerce setting.
         * @hook wp_ajax_wcap_preview_email
         * @since: 7.0
         */
        public static function wcap_preview_email () {
            
            $wcap_email                    = convert_smilies ( $_POST [ 'body_email_preview' ]  ) ;
            $wcap_email_body_strip_slashes = stripslashes( $wcap_email );
            
            $body_email_preview = Wcap_Common::wcap_replace_email_body_merge_code ( $wcap_email_body_strip_slashes );
            
            $wcap_include_tax_setting = get_option( 'woocommerce_calc_taxes' );
            $wcap_add_tax_note        = '';
            if ( isset( $wcap_include_tax_setting ) && 'yes' == $wcap_include_tax_setting ) {
                $wcap_add_tax_note = '<strong>Note</strong>: Tax amount is hardcoded in the preview. It will be replaced with real tax amount when reminder email will be sent to customers.';
            }
            $wcap_footer_fields = ' <div id = "wcap_tax_note_preview" class = "wcap_tax_note_preview"> '.$wcap_add_tax_note.' </div>
                                    <tr>
                                        <th>
                                            <label for="woocommerce_ac_email_preview">
                                                <b>Send a test Email to:</b>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="text" id="send_test_email_preview" name="send_test_email_preview" class="regular-text send_test_email_preview">
                                            <input type="button" value="Send a test Email" id="preview_test_email"  class= "preview_test_email button-primary" onclick="javascript:void(0);" data-wcap-email-type="normal_preview">
                                            <span id="preview_test_email_sent_msg" style="display:none;"></span>
                                        </td>
                                    </tr>';
            $wcap_email_body = '<div class="wcap-modal__header">
                                    <h1>Email preview</h1>
                                </div>
                                <div class="wcap-modal__body">
                                    <div class="wcap-modal__body-inner">'.$body_email_preview.' </div>
                                </div>
                                <div class="wcap-modal__footer">'.$wcap_footer_fields.' </div>' ;
            echo $wcap_email_body;
            wp_die();

        }

        /**
         * This ajax create the preview email content for the WooCommerce setting.
         * @hook wp_ajax_wcap_preview_wc_email
         * @globals mixed $woocommerce
         * @since: 7.0
         */
        public static function wcap_preview_wc_email () {
            global $woocommerce;

            $wcap_email                    = convert_smilies ( $_POST [ 'body_email_preview' ]  ) ;
            $wcap_email_body_strip_slashes = stripslashes( $wcap_email );
            
            $body_email_preview = Wcap_Common::wcap_replace_email_body_merge_code ( $wcap_email_body_strip_slashes );

            $wcap_message = '';
            if ( $woocommerce->version < '2.3' ) {
                global $email_heading;
                $wcap_mailer        = WC()->mailer();
                $wcap_email_heading = stripslashes( $_POST [ 'wc_template_header' ] );
                $wcap_message       =  $wcap_mailer->wrap_message( $wcap_email_heading, $body_email_preview );
            } else {

                $wcap_mailer        = WC()->mailer();
                $wcap_email_heading = stripslashes( $_POST [ 'wc_template_header' ] );
                $wcap_email         = new WC_Email();
                $wcap_message       = $wcap_email->style_inline( $wcap_mailer->wrap_message( $wcap_email_heading, $body_email_preview ) );
            }
            $wcap_include_tax_setting = get_option( 'woocommerce_calc_taxes' );
            $wcap_add_tax_note        = '';
            if ( isset( $wcap_include_tax_setting ) && 'yes' == $wcap_include_tax_setting ) {
                $wcap_add_tax_note = '<strong>Note</strong>: Tax amount is hardcoded in the preview. It will be replaced with real tax amount when reminder email will be sent to customers.';
            }
            $wcap_footer_fields = ' <div id = "wcap_tax_note_preview" class = "wcap_tax_note_preview"> '.$wcap_add_tax_note.' </div>
                                    <tr>
                                        <th>
                                            <label for="woocommerce_ac_email_preview">
                                                <b>Send a test Email to:</b>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="text" id="send_test_email_preview" name="send_test_email_preview" class="regular-text send_test_email_preview">
                                            <input type="button" value="Send a test Email" id="preview_test_email"  class= "preview_test_email button-primary" onclick="javascript:void(0);" data-wcap-email-type="wc_preview">
                                            <span id="preview_test_email_sent_msg" style="display:none;"></span>
                                        </td>
                                    </tr>';
            $wcap_email_body = '<div class="wcap-modal__header">
                                    <h1>Email preview </h1>
                                </div>
                                <div class="wcap-modal__body">
                                    <div class="wcap-modal__body-inner">'.$wcap_message.' </div>
                                </div>
                                <div class="wcap-modal__footer">'.$wcap_footer_fields.' </div>' ;
            echo $wcap_email_body;
            wp_die();
        }

        /**
         * This function check if the Add to Cart is enabled or not when we disabled the guest cart capturing.
         * @hook wp_ajax_wcap_is_atc_enable
         * @since: 7.0
         */
        public static function wcap_is_atc_enable () {

            $wcap_get_atc_enabled = get_option ( 'wcap_atc_enable_modal' );

            if ( 'off' == $wcap_get_atc_enabled ){
                echo $wcap_get_atc_enabled;
                wp_die();
            }

            if ( 'on' == $wcap_get_atc_enabled ) {
                $wcap_atc_enable = 'off';
                update_option( 'wcap_atc_enable_modal', $wcap_atc_enable );

                echo $wcap_get_atc_enabled;
                wp_die();
            }
        }

        /**
         * It will activate and deactivate the template from the template page.
         * @hook wp_ajax_wcap_toggle_template_status
         * @globals mixed $wpdb
         * @since 4.8
         */
        public static function wcap_toggle_template_status(){
            global $wpdb;
            $template_id             = $_POST['wcap_template_id'];
            $current_template_status = $_POST['current_state'];

            $template_type = isset( $_POST[ 'template_type' ] ) ? $_POST[ 'template_type' ] : 'emailtemplates';
            
            if( 'emailtemplates' == $template_type ) {
            
                $active = ( "on" == $current_template_status ) ? '1' : '0';
                $query_update = "UPDATE `" . WCAP_EMAIL_TEMPLATE_TABLE . "`
                        SET
                        is_active = '" . $active . "'
                        WHERE id  = '" . $template_id . "' ";
                $wpdb->query( $query_update );
            } else {
            
                if( 'on' == $current_template_status ) {
                    $active = '1';
            
                    // get the template_frequency
                    $get_freq = "SELECT frequency FROM `" . WCAP_NOTIFICATIONS . "`
                                WHERE id = %d
                                AND type = %s";
            
                    $res_frequency = $wpdb->get_results( 
                        $wpdb->prepare( 
                            $get_freq, 
                            $template_id, 
                            $template_type ) );
            
                    $frequency = $res_frequency[0]->frequency;
            
                    // check if there are any templates active for the same frequency.
                    $get_active = "SELECT ID FROM `" . WCAP_NOTIFICATIONS . "`
                                WHERE type= %s
                                AND frequency = %s
                                AND is_active = '1'";
                    $results_active = $wpdb->get_results( 
                        $wpdb->prepare( 
                            $get_active, 
                            $template_type,
                            $frequency ) );
            
                    if( is_array( $results_active ) && count( $results_active ) > 0 ) {
                        // if yes, deactivate those
            
                        $wcap_all_ids = '';
                        foreach( $results_active as $active_temp ) {
                            $wcap_all_ids = ( $wcap_all_ids == '' ) ? $active_temp->ID : "$wcap_all_ids," . $active_temp->ID;
                        }
                        $wpdb->update( WCAP_NOTIFICATIONS, array( 'is_active' => 0 ), array( 'frequency' => $frequency ) );
                        echo 'wcap-template-updated:'. $wcap_all_ids ;
                    }
                } else {
                    $active = '0';
                }
            
                // update the status for the designated template
                $wpdb->update( WCAP_NOTIFICATIONS, array( 'is_active' => $active ), array( 'id' => $template_id ) );
            }
                
            wp_die();
        }

        /**
         * It will reset all the default configuration of the Add To Cart modal.
         * @hook wp_ajax_wcap_atc_reset_setting
         * @since: 6.0
         */
        public static function wcap_atc_reset_setting(){
            $wcap_atc_heading = 'Please enter your email';
            update_option( 'wcap_heading_section_text_email', $wcap_atc_heading );

            $wcap_atc_heading_color = '#737f97';
            update_option( 'wcap_popup_heading_color_picker', $wcap_atc_heading_color );

            $wcap_atc_sub_text = 'To add this item to your cart, please enter your email address.';
            update_option( 'wcap_text_section_text', $wcap_atc_sub_text );

            $wcap_atc_sub_text = 'To add this item to your cart, please enter your email address.';
            update_option( 'wcap_text_section_text', $wcap_atc_sub_text );

            $wcap_atc_sub_text_color = '#bbc9d2';
            update_option( 'wcap_popup_text_color_picker', $wcap_atc_sub_text_color );

            $wcap_atc_email_field_placeholder = 'Email address';
            update_option( 'wcap_email_placeholder_section_input_text', $wcap_atc_email_field_placeholder );

            $wcap_atc_button = 'Add to Cart';
            update_option( 'wcap_button_section_input_text', $wcap_atc_button );

            $wcap_atc_button_color = '#0085ba';
            update_option( 'wcap_button_color_picker', $wcap_atc_button_color );

            $wcap_atc_button_text_color = '#ffffff';
            update_option( 'wcap_button_text_color_picker', $wcap_atc_button_text_color );

            $wcap_atc_non_mandatory_text = 'No thanks';
            update_option( 'wcap_non_mandatory_text', $wcap_atc_non_mandatory_text );

            $wcap_atc_mandatory = 'off';
            update_option( 'wcap_atc_enable_modal', $wcap_atc_mandatory );

            $wcap_atc_email_mandatory = 'on';
            update_option( 'wcap_atc_mandatory_email', $wcap_atc_email_mandatory );
        }

        /**
         * We need to define the do_action for running the ajax on the shop page.
         * Because the wp-ajax was not runing on the shop page.
         * We have used the WC ajax so it can run on shop page.
         * @hook init
         * @since: 6.0
         */
        public static function wcap_add_ajax_for_atc() {
            if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'wcap_atc_store_guest_email' ):
              do_action( 'wp_ajax_nopriv_' . $_REQUEST['action'] );
            endif;
        }

        /**
         * We have used WC ajax because the wp-ajax was not runing on the shop page.
         * When we run the wp-admin ajax, it was giving the 302 status of the ajax call.
         * @hook wp_ajax_nopriv_wcap_atc_store_guest_email
         * @globals mixed $wpdb 
         * @globals mixed $woocommerce
         * @since: 6.0
         */
        public static function wcap_atc_store_guest_email() {
            
            global $wpdb, $woocommerce;
            $wcap_guest_email        = $_POST['wcap_atc_email'];
            $current_user_ip_address =  Wcap_Common::wcap_get_client_ip();

            $wcap_is_ip_restricted            = Wcap_Common::wcap_is_ip_restricted            ( $current_user_ip_address );
            $wcap_is_email_address_restricted = Wcap_Common::wcap_is_email_address_restricted ( $wcap_guest_email );
            $wcap_is_domain_restricted        = Wcap_Common::wcap_is_domain_restricted        ( $wcap_guest_email );
            if ( false == $wcap_is_ip_restricted && false == $wcap_is_email_address_restricted && false == $wcap_is_domain_restricted ) {
                $wcap_session_cookie = Wcap_Common::wcap_get_guest_session_key();
                $wc_shipping_charges = "";
                if ( function_exists('WC') ) {
                    $cart['cart'] = WC()->session->cart;
                    if ( "disabled" != get_option ( "woocommerce_ship_to_countries" ) ) {
                        $wc_shipping_charges = WC()->cart->get_cart_shipping_total();
                        // Extract the shipping amount
                        $wc_shipping_charges = strip_tags( html_entity_decode( $wc_shipping_charges ) );
                        $wc_shipping_charges = (float) filter_var( $wc_shipping_charges, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
                    }
                } else {
                    $cart['cart'] = $woocommerce->session->cart;
                    if ( "disabled" != get_option ( "woocommerce_ship_to_countries" ) ) {
                        $wc_shipping_charges = $woocommerce->cart->get_cart_shipping_total();
                    }
                }

                $cart_info = json_encode( $cart );

                $current_time             = current_time( 'timestamp' );

                if ( function_exists( 'icl_register_string' ) ) {
                    $current_user_lang = isset( $_SESSION['wpml_globalcart_language'] ) ? $_SESSION['wpml_globalcart_language'] : ICL_LANGUAGE_CODE;
                } else {
                    $current_user_lang = 'en';
                }

                if ( isset( $_POST['wcap_atc_user_action'] ) && 'yes' == $_POST['wcap_atc_user_action'] ) {
                    $wcap_user_email_from_popup = $_POST['wcap_atc_email'];
                    wcap_set_cart_session( 'wcap_guest_email', $wcap_user_email_from_popup );

                    $wcap_insert_guest_email = array( 'email_id' => $wcap_user_email_from_popup,
                                                      'billing_first_name' => '',
                                                      'billing_last_name'  => '',
                                                      'phone'              => '',
                                                      'shipping_charges'   =>  $wc_shipping_charges );
                    $wpdb->insert( WCAP_GUEST_CART_HISTORY_TABLE, $wcap_insert_guest_email );
                    $wcap_guest_user_id = $wpdb->insert_id;

                    Wcap_Ajax::wcap_add_guest_record_for_atc ( $wcap_guest_user_id, $cart_info, $current_time, $current_user_lang, $current_user_ip_address );

                    wcap_set_cart_session( 'wcap_user_id', $wcap_guest_user_id );

                    // Fetch now so it's available
                    $wcap_abandoned_cart_id = wcap_get_cart_session( 'wcap_abandoned_id' );
                    
                    $wcap_popup_modal_report = array( "wcap_atc_open" => "yes", "wcap_atc_action" => "yes" );

                    add_post_meta( $wcap_abandoned_cart_id, "wcap_atc_report", $wcap_popup_modal_report );

                    do_action ('acfac_add_data', $wcap_abandoned_cart_id );
                    echo $wcap_abandoned_cart_id;
                }else if ( isset( $_POST['wcap_atc_user_action'] ) && 'no' == $_POST['wcap_atc_user_action'] ) {

                    $wcap_guest_user_id = "0";
                    
                    // Fetch now so it might be available
                    $wcap_abandoned_cart_id = wcap_get_cart_session( 'wcap_abandoned_id' );
                    
                    // if session not set insert record and set session
                    if ( $wcap_abandoned_cart_id == '' ) {
                        Wcap_Ajax::wcap_add_visitor_record_for_new_session ( $wcap_guest_user_id, $cart_info, $current_time, $current_user_lang, $current_user_ip_address );
                    }

                    $wcap_popup_modal_report = array( "wcap_atc_open" => "yes", "wcap_atc_action" => "no" );

                    add_post_meta( $wcap_abandoned_cart_id, "wcap_atc_report", $wcap_popup_modal_report );

                    do_action ('acfac_add_data', $wcap_abandoned_cart_id );
                    echo $wcap_abandoned_cart_id;
                }
            }
            wp_die();
        }

        /**
         * 
         * This function will add the Guest user cart information in abandoned cart history table.
         * It will check if any session reocrd is present and it is not updated cart then update / insert the record
         * with GUEST Id.
         * @globals mixed $wpdb 
         * @globals mixed $woocommerce
         * @param int | string $user_id User id of the abandoned cart
         * @param json_encode $cart_info Cart information
         * @param timestamp $current_time Current Time
         * @param string $current_user_lang User selected language while abandoing the cart
         * @param string $current_user_ip_address Ip address of the user.
         * @since: 6.0
         */
        public static function wcap_add_guest_record_for_atc( $user_id, $cart_info, $current_time, $current_user_lang, $current_user_ip_address ){

            global $wpdb, $woocommerce;

            $abandoned_cart_id = 0;
            $wcap_wc_session_key = Wcap_Common::wcap_get_guest_session_key();

            $wcap_check_session_key_data = "SELECT id FROM `" .  WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = '0' AND session_id = %s AND cart_ignored = '0' ";
            $wcap_check_session_key_data_results     = $wpdb->get_results( $wpdb->prepare( $wcap_check_session_key_data, $wcap_wc_session_key ) );

            if ( count( $wcap_check_session_key_data_results ) > 0 ){

                $wcap_id = $wcap_check_session_key_data_results[0]->id;
                $wcap_update_guest_data = array( 'user_id'             => $user_id,
                                                 'abandoned_cart_time' => $current_time );
                $wpdb->update( WCAP_ABANDONED_CART_HISTORY_TABLE ,
                               $wcap_update_guest_data,
                               array('id'=> $wcap_id)
                           );
                $abandoned_cart_id   = $wcap_id;
            }else{
                $abandoned_cart_id = WCAP_DB_Layer::insert_cart_history( 
                    $user_id, 
                    $cart_info, 
                    $current_time, 
                    '0', 
                    '0', 
                    '', 
                    'GUEST', 
                    $current_user_lang, 
                    $wcap_wc_session_key, 
                    $current_user_ip_address, 
                    '', 
                    '' );
            }
            wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );

            $insert_persistent_cart = "INSERT INTO `" . $wpdb->prefix . "usermeta`( user_id, meta_key, meta_value )
                                      VALUES ( %d , '_woocommerce_persistent_cart', %s  )";
            $wpdb->query( $wpdb->prepare( $insert_persistent_cart, $user_id, $cart_info ) );
        }

        /**
         * 
         * It will add the visitors cart when customer do not provide the email address in Add To Cart modal.
         * To be deprecated by v8.0
         * 
         * @globals mixed $wpdb 
         * @globals mixed $woocommerce
         * @param int | string $wcap_guest_user_id User id of the abandoned cart
         * @param json_encode $wcap_cart_info Cart information
         * @param timestamp $current_time Current Time
         * @param string $current_user_lang User selected language while abandoing the cart
         * @param string $current_user_ip_address Ip address of the user.
         * @since: 6.0
         */
        public static function wcap_add_visitor_record_for_new_session( $wcap_guest_user_id, $wcap_cart_info, $current_time, $current_user_lang, $current_user_ip_address ) {

            $wcap_atc_email_mandatory = get_option( 'wcap_atc_mandatory_email' );
            $wcap_atc_email_mandatory = get_option( 'wcap_atc_enable_modal' );
            if ( ( "off" == $wcap_atc_email_mandatory && "on" == $wcap_atc_email_mandatory ) || ( "on" == $wcap_atc_email_mandatory && "on" == $wcap_atc_email_mandatory ) ) {
                global $wpdb, $woocommerce;
                $wcap_wc_session_key      = Wcap_Common::wcap_get_guest_session_key();
                $wcap_insert_visitor_cart = array(
                                                "user_id"             => $wcap_guest_user_id,
                                                "abandoned_cart_info" => $wcap_cart_info,
                                                "abandoned_cart_time" => $current_time,
                                                "cart_ignored"        => "0",
                                                "recovered_cart"      => "0",
                                                "user_type"           => "GUEST",
                                                "language"            => $current_user_lang,
                                                "session_id"          => $wcap_wc_session_key,
                                                "ip_address"          => $current_user_ip_address
                                            );
                $wpdb->insert( WCAP_ABANDONED_CART_HISTORY_TABLE, $wcap_insert_visitor_cart );
                $abandoned_cart_id = $wpdb->insert_id;
                wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );

                $insert_persistent_cart = "INSERT INTO `" . $wpdb->prefix . "usermeta`( user_id, meta_key, meta_value )
                                          VALUES ( %d , '_woocommerce_persistent_cart', %s  )";
                $wpdb->query( $wpdb->prepare( $insert_persistent_cart, $wcap_guest_user_id, $wcap_cart_info ) );
            }    
        }

        /**
         * It will change the status of the email field is mandatory or not.
         * @hook wp_ajax_wcap_toggle_atc_mandatory_status
         * @globals mixed $wpdb 
         * @since: 6.0
         */
        public static function wcap_toggle_atc_mandatory_status(){
            global $wpdb;
            $current_atc_modal_status = $_POST['new_state'];

            if( "off" == $current_atc_modal_status ) {
                update_option ('wcap_atc_mandatory_email' , 'off');
                $active = "0";
            } else if ( "on" == $current_atc_modal_status ) {
                $active = "1";
                update_option ('wcap_atc_mandatory_email' , 'on');
            }
            wp_die();
        }

        /**
         * It will change the status popup modal visibility on the front end.
         * @hook wp_ajax_wcap_toggle_atc_enable_status
         * @globals mixed $wpdb
         * @since: 6.0
         */
        public static function wcap_toggle_atc_enable_status(){
            global $wpdb;
            $current_atc_modal_status = $_POST['new_state'];

            if( "off" == $current_atc_modal_status ) {
                update_option ('wcap_atc_enable_modal' , 'off');
                $active = "0";
            } else if ( "on" == $current_atc_modal_status ) {
                $active = "1";
                update_option ('wcap_atc_enable_modal' , 'on');

                /**
                 * If we enable the ATC & the guest cart capture is enabaled then we will disabled it.
                 * @since: 7.0
                 */
                $wcap_get_guest_capture_cart = get_option( 'ac_disable_guest_cart_email' );

                if ( 'on' == $wcap_get_guest_capture_cart ) {
                    update_option ( 'ac_disable_guest_cart_email' , 'off' );
                }
            }
            wp_die();
        }

        /**
         * It will populate the modal detail for the abandoned cart.
         * @hook wp_ajax_wcap_abandoned_cart_info
         * @since 4.8
         */
        public static function wcap_abandoned_cart_info (){

            $wcap_cart_id          = isset( $_POST ['wcap_cart_id'] )            ? $_POST ['wcap_cart_id'] : '';
            $wcap_email_address    = isset( $_POST [ 'wcap_email_address'] )     ? $_POST [ 'wcap_email_address'] : '';
            $wcap_customer_details = isset( $_POST [ 'wcap_customer_details' ] ) ? $_POST [ 'wcap_customer_details' ] : '';
            $wcap_cart_total       = isset( $_POST [ 'wcap_cart_total' ] )       ? $_POST [ 'wcap_cart_total' ] : '';
            $wcap_abandoned_date   = isset( $_POST [ 'wcap_abandoned_date' ] )   ? $_POST [ 'wcap_abandoned_date' ] : '';
            $wcap_abandoned_status = isset( $_POST [ 'wcap_abandoned_status' ] ) ? $_POST [ 'wcap_abandoned_status' ] : '';
            $wcap_current_page     = isset( $_POST [ 'wcap_current_page' ] )     ? $_POST [ 'wcap_current_page' ] : '';
            Wcap_Abandoned_Cart_Details::wcap_get_cart_detail_view ( $wcap_cart_id, $wcap_email_address, $wcap_customer_details, $wcap_cart_total, $wcap_abandoned_date, $wcap_abandoned_status, $wcap_current_page );
            wp_die();
        }

        /**
         * It will send the test email from the template add / edit page.
         * @hook wp_ajax_wcap_preview_email_sent
         * @since 1.0
         */
        public static function wcap_preview_email_sent() {
            $from_email_name           = get_option ( 'wcap_from_name' );
            $from_email_preview        = get_option ( 'wcap_from_email' );
            $reply_name_preview        = get_option ( 'wcap_reply_email' );
            $subject_email_preview     = convert_smilies( $_POST['subject_email_preview'] );
            $body_email_preview        = convert_smilies( $_POST['body_email_preview'] );
            $to_email_preview          = "";
            if ( isset( $_POST[ 'send_email_id' ] ) ) {
                $to_email_preview      = $_POST[ 'send_email_id' ];
            }

            $is_wc_template            = $_POST['is_wc_template'];
            $wc_template_header        = $_POST[ 'wc_template_header' ];
            $headers                   = "From: " . $from_email_name . " <" . $from_email_preview . ">" . "\r\n";
            $headers                  .= "Content-Type: text/html" . "\r\n";
            $headers                  .= "Reply-To:  " . $reply_name_preview . " " . "\r\n";

            $subject_email_preview     = str_replace( '{{customer.firstname}}', 'John', $subject_email_preview );
            $subject_email_preview     = str_replace( '{{product.name}}', 'Spectre', $subject_email_preview );
            $body_email_preview        = Wcap_Common::wcap_replace_email_body_merge_code ( $body_email_preview );

            if ( isset( $is_wc_template ) && "true" == $is_wc_template ) {
                ob_start();
                wc_get_template( 'emails/email-header.php', array( 'email_heading' => $wc_template_header ) );
                $email_body_template_header = ob_get_clean();

                ob_start();
                wc_get_template( 'emails/email-footer.php' );
                $email_body_template_footer = ob_get_clean();

                $final_email_body =  $email_body_template_header . $body_email_preview . $email_body_template_footer;

                Wcap_Common::wcap_add_wc_mail_header();
                wc_mail( $to_email_preview, stripslashes( $subject_email_preview ), stripslashes( $final_email_body ) , $headers );
                Wcap_Common::wcap_remove_wc_mail_header();
            } else {
                Wcap_Common::wcap_add_wp_mail_header();
                wp_mail( $to_email_preview, stripslashes( $subject_email_preview ), stripslashes( $body_email_preview ), $headers );
                Wcap_Common::wcap_remove_wc_mail_header();
            }
            echo "email sent";
            die();
        }

        /**
         * It will search for the coupon code. It is called on the add / edit template page.
         * @hook wp_ajax_wcap_json_find_coupons
         * @param string $x 
         * @param array $post_types Post type which we want to search
         * @since 1.0
         */
        public static function wcap_json_find_coupons( $x = '', $post_types = array( 'shop_coupon' ) ) {
            check_ajax_referer( 'search-products', 'security' );
            $term = (string) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
            if ( empty( $term ) ) {
                die();
            }
            if ( is_numeric( $term ) ) {
                $args = array(
                        'post_type'         => $post_types,
                        'post_status'       => 'publish',
                        'posts_per_page'    => -1,
                        'post__in'          => array(0, $term),
                        'fields'            => 'ids'
                );
                $args2 = array(
                        'post_type'         => $post_types,
                        'post_status'       => 'publish',
                        'posts_per_page'    => -1,
                        'post_parent'       => $term,
                        'fields'            => 'ids'
                );
                $args3 = array(
                        'post_type'         => $post_types,
                        'post_status'       => 'publish',
                        'posts_per_page'    => -1,
                        'meta_query'        => array(
                                array(
                                        'key'       => '_sku',
                                        'value'     => $term,
                                        'compare'   => 'LIKE'
                                )
                        ),
                        'fields' => 'ids'
                );
                $posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ) ) );
            } else {
                $args = array(
                        'post_type'         => $post_types,
                        'post_status'       => 'publish',
                        'posts_per_page'    => -1,
                        's'                 => $term,
                        'fields'            => 'ids'
                );
                $args2 = array(
                        'post_type'         => $post_types,
                        'post_status'       => 'publish',
                        'posts_per_page'    => -1,
                        'meta_query'        => array(
                                array(
                                        'key'       => '_sku',
                                        'value'     => $term,
                                        'compare'   => 'LIKE'
                                )
                        ),
                        'fields' => 'ids'
                );
                $posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ) ) );
            }
            $found_products = array();
            if ( $posts ) foreach ( $posts as $post ) {
                $SKU              = get_post_meta( $post, '_sku', true );
                $wcap_product_sku = apply_filters( 'wcap_product_sku', $SKU );
                if( false != $wcap_product_sku && '' != $wcap_product_sku ) {                    
                    if ( isset( $SKU ) && $SKU ) {
                        $SKU = ' ( SKU: ' . $SKU . ' )';
                    }    
                    $found_products[ $post ] = get_the_title( $post ) . ' &ndash; #' . $post . $SKU;
                } else { 
                $found_products[ $post ] = get_the_title( $post ) . ' &ndash; #' . $post;
                }     
            }
            echo json_encode( $found_products );
            die();
        }

        /**
         * Searches for pages matching the term sent. 
         * Used to allow for Add to Cart Pop-up to be displayed 
         * on Custom pages
         * 
         * @param string $x 
         * @param array $post_types Post type which we want to search - Pages
         * @return Matched pages
         * @since 7.10.0
         */
        public static function wcap_json_find_pages( $x = '', $post_types = array( 'page' ) ) {
            check_ajax_referer( 'search-products', 'security' );
            $term = (string) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
            if ( empty( $term ) ) {
                die();
            }
            $args = array( 'post_type'      => $post_types,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                's'              => $term,
                'fields'         => 'ids'
            );
            $page_list = get_posts( $args );
            $found_pages = array();
            if ( $page_list ) {
                foreach ( $page_list as $page ) {
                    $found_pages[ $page ] = get_the_title( $page );
                }
            }
            echo json_encode( $found_pages );
            die();
        }
        
        /**
         * It will generate the selected template data and return to the ajax.
         * @hook wp_ajax_wcap_change_manual_email_data
         * @since: 4.2
         */
        public static function wcap_change_manual_email_data () {
            $return_selected_template_data = array();
            if ( isset( $_POST['wcap_template_id'] ) ) {
                global $wpdb;
                global $woocommerce;
                $template_id = $_POST['wcap_template_id'];
                $query       = "SELECT wpet . *  FROM `" .  WCAP_EMAIL_TEMPLATE_TABLE . "` AS wpet WHERE id= %d";
                $results     = $wpdb->get_results( $wpdb->prepare( $query,  $template_id ) );

                $return_selected_template_data ['from_name']       = get_option ( 'wcap_from_name' );
                $return_selected_template_data ['from_email']      = get_option ( 'wcap_from_email' );
                $return_selected_template_data ['reply_email']     = get_option ( 'wcap_reply_email' );
                $return_selected_template_data ['subject']         = $results[0]->subject;
                $return_selected_template_data ['body']            = $results[0]->body;
                $return_selected_template_data ['is_wc_template']  = $results[0]->is_wc_template;
                $return_selected_template_data ['wc_email_header'] = $results[0]->wc_email_header;
                $return_selected_template_data ['coupon_code']     = $results[0]->coupon_code;
                if ( $results[0]->coupon_code > 0 ) {
                    $coupon_to_apply   = get_post( $results[0]->coupon_code, ARRAY_A );
                    $coupon_code_name  = $coupon_to_apply['post_title'];
                    $return_selected_template_data ['coupon_code_name'] = $coupon_code_name;
                } else {
                    $return_selected_template_data ['coupon_code_name'] = '';
                }
                $return_selected_template_data ['generate_unique_coupon_code'] = $results[0]->generate_unique_coupon_code;

                 if ( function_exists('WC') ) {
                    $return_selected_template_data ['wc_version'] = WC()->version;
                } else {
                    $return_selected_template_data ['wc_version'] = $woocommerce->version;
                }
            }
            echo json_encode( $return_selected_template_data );
            die();
        }

        /**
         * It will store the guest users data in the ac_guest_abandoned_cart_history & ac_abandoned_cart_history table.
         * It is called on the checkout page on email field.
         * @hook wp_ajax_nopriv_wcap_save_guest_data
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @since 1.0
         * 
         */
        public static function wcap_save_guest_data() {
            $current_user_ip_address =  Wcap_Common::wcap_get_client_ip();
            $current_user_email = '';
            if ( isset( $_POST['billing_email'] ) && '' != $_POST['billing_email'] ) {
               $current_user_email = $_POST['billing_email'];
            }

            $get_restricted_ip_address     = Wcap_Common::wcap_is_ip_restricted( $current_user_ip_address );
            $get_restricted_email_address  = Wcap_Common::wcap_is_email_address_restricted( $current_user_email );
            $get_restricted_domain_address = Wcap_Common::wcap_is_domain_restricted( $current_user_email );

            if ( ! is_user_logged_in() && ( false == $get_restricted_ip_address && false == $get_restricted_email_address && false == $get_restricted_domain_address ) ) {

                global $wpdb, $woocommerce;

                $wcap_wc_session_key = Wcap_Common::wcap_get_guest_session_key();
                $wc_shipping_charges = "";
                if ( "disabled" != get_option ( "woocommerce_ship_to_countries" ) ) {

                    if ( function_exists('WC') ) {
                        $wc_shipping_charges = WC()->cart->get_cart_shipping_total(); //returns the formatted shipping total in a <span> tag
                        // Extract the shipping amount 
                        $wc_shipping_charges = strip_tags( html_entity_decode( $wc_shipping_charges ) );
                        $wc_shipping_charges = (float) filter_var( $wc_shipping_charges, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
                        
                    } else {
                        $wc_shipping_charges = $woocommerce->cart->get_shipping_total();
                    }
                }

                $abandoned_order_id = wcap_get_cart_session( 'wcap_abandoned_id' );
                $wcap_user_id = wcap_get_cart_session( 'wcap_user_id' );

                if ( $wcap_user_id !='' && 
                     ( wcap_get_cart_session( 'billing_email' ) != '' || wcap_get_cart_session( 'billing_phone' ) != '' || wcap_get_cart_session( 'wcap_guest_email' ) != '' ) ) {

                    /**
                     * If a record is present in the guest cart history table for the same email id and same session id.
                     */
                    /*$query_existing_guest   = "SELECT  wpag.*, wpah.session_id FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "` As wpag LEFT JOIN `".WCAP_ABANDONED_CART_HISTORY_TABLE."`   AS wpah ON wpag.id = wpah.user_id WHERE wpag.email_id = %s AND wpah.id = %s";
                    $results_existing_guest = $wpdb->get_results( $wpdb->prepare( $query_existing_guest, wcap_get_cart_session( 'billing_email' ), $abandoned_order_id ) );*/

                    $billing_email = '';
                    if ( wcap_get_cart_session( 'wcap_guest_email' ) != '' ) {
                        $billing_email = wcap_get_cart_session( 'wcap_guest_email' );
                    }elseif ( wcap_get_cart_session( 'billing_email' ) != '' ) {
                        $billing_email = wcap_get_cart_session( 'billing_email' );
                    }

                    if ( $billing_email != '' && 
                         isset( $_POST['billing_email'] ) && 
                         $_POST['billing_email'] != '') {

                        Wcap_Ajax::wcap_update_guest_record_on_same_email( $wcap_wc_session_key, $wc_shipping_charges );
                        wp_die();
                    } else {
                        /**
                         * If a record is present in the guest cart history table for the same phone number and same session id.
                         */
                        $query_existing_guest_phone   = "SELECT  wpag.*, wpah.session_id FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "` As wpag LEFT JOIN `". WCAP_ABANDONED_CART_HISTORY_TABLE."` AS wpah ON wpag.id = wpah.user_id WHERE wpag.phone = %s AND wpah.id = %s";
                        $results_existing_guest_phone = $wpdb->get_results( $wpdb->prepare( $query_existing_guest_phone, $_POST['billing_phone'], $abandoned_order_id ) );

                        if ( isset( $results_existing_guest_phone ) && 
                             count ( $results_existing_guest_phone ) > 0 && 
                             isset( $_POST['billing_phone'] ) && 
                             $_POST['billing_phone'] != '') {

                            Wcap_Ajax::wcap_update_guest_record_on_same_phone( $wcap_wc_session_key, $wc_shipping_charges );
                            wp_die();
                        }
                    }
                }else {

                    $billing_email_session = wcap_get_cart_session( 'billing_email' );

                    if( isset( $_POST['billing_email'] ) && 
                        $_POST['billing_email'] != '' &&
                        isset( $_POST['wcap_record_added'] ) &&
                        $_POST['wcap_record_added'] !== 'false' &&
                        ( ( $billing_email_session != '' ) ||
                          ( isset( $_POST['wcap_abandoned_id'] ) &&
                            $_POST['wcap_abandoned_id'] !== '' ) ) ) {

                        /**
                         * If a record is present in the guest cart history table for the same email id and same session id.
                         */
                        // first thing to confirm whether the Email address in Session and POST matches. If not, the let's update the session details.
                        $email_check = $_POST[ 'billing_email' ];

                        $session_email = wcap_get_cart_session( 'wcap_guest_email' );
                        
                        if( $session_email != $_POST['billing_email'] ) {
                            $email_check = $session_email;
                            wcap_set_cart_session( 'wcap_guest_email', $_POST[ 'billing_email' ] );
                        }
                        
                        $query_existing_guest = 
                            "SELECT wpag.email_id, wpah.session_id 
                            FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "` As wpag 
                            LEFT JOIN `".WCAP_ABANDONED_CART_HISTORY_TABLE."`   AS wpah 
                            ON wpag.id = wpah.user_id 
                            WHERE wpag.email_id = %s 
                            AND wpah.session_id = %s";
                        $results_existing_guest = $wpdb->get_results( 
                            $wpdb->prepare( 
                                $query_existing_guest, 
                                $email_check, 
                                $wcap_wc_session_key ) );

                        if ( isset( $results_existing_guest ) && 
                             count ( $results_existing_guest ) > 0 && 
                             isset( $_POST['billing_email'] ) && 
                             $_POST['billing_email'] != '') {

                            Wcap_Ajax::wcap_update_guest_record_on_same_email( $wcap_wc_session_key, $wc_shipping_charges );
                            wp_die();
                        }
                    }elseif ( isset( $_POST['billing_phone'] ) && 
                        $_POST['billing_phone'] != '' &&
                        isset( $_POST['wcap_record_added'] ) &&
                        $_POST['wcap_record_added'] !== 'false' &&
                        wcap_get_cart_session( 'billing_phone' ) != '' ) {

                        /**
                         * If a record is present in the guest cart history table for the same phone number and same
                         * session id.
                         */
                        $query_existing_guest_phone = 
                            "SELECT wpag.phone, wpah.session_id 
                            FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "` As wpag 
                            LEFT JOIN `". WCAP_ABANDONED_CART_HISTORY_TABLE."` AS wpah 
                            ON wpag.id = wpah.user_id 
                            WHERE wpag.phone = %s 
                            AND wpah.session_id = %s";
                        $results_existing_guest_phone = $wpdb->get_results( 
                            $wpdb->prepare( 
                                $query_existing_guest_phone, 
                                $_POST['billing_phone'], 
                                $wcap_wc_session_key ) );

                        if ( isset( $results_existing_guest_phone ) && 
                             count ( $results_existing_guest_phone ) > 0 && 
                             isset( $_POST['billing_phone'] ) && 
                             $_POST['billing_phone'] != '') {

                            Wcap_Ajax::wcap_update_guest_record_on_same_phone( $wcap_wc_session_key, $wc_shipping_charges );
                            wp_die();
                        }
                    }elseif ( isset( $_POST['billing_first_name'] ) && 
                        $_POST['billing_first_name'] != '' &&
                        isset( $_POST['wcap_record_added'] ) &&
                        $_POST['wcap_record_added'] !== 'false' &&
                        wcap_get_cart_session( 'billing_first_name' ) != '' ) {

                        /**
                         * If a record is present in the guest cart history table for the same First name and same
                         * session id.
                         */
                        $query_existing_guest_first_name = 
                            "SELECT  wpag.billing_first_name, wpah.session_id 
                            FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "` As wpag 
                            LEFT JOIN `". WCAP_ABANDONED_CART_HISTORY_TABLE."` AS wpah 
                            ON wpag.id = wpah.user_id 
                            WHERE wpag.billing_first_name = %s 
                            AND wpah.session_id = %s";
                        $results_existing_guest_first_name = $wpdb->get_results( 
                            $wpdb->prepare( 
                                $query_existing_guest_first_name, 
                                $_POST['billing_first_name'], 
                                $wcap_wc_session_key ) );

                        if( isset( $results_existing_guest_first_name ) && 
                            count ( $results_existing_guest_first_name ) > 0 && 
                            isset( $_POST['billing_first_name'] ) && 
                            $_POST['billing_first_name'] != '' ) {

                            Wcap_Ajax::wcap_update_guest_record_on_same_first_name( $wcap_wc_session_key, $wc_shipping_charges );
                            wp_die();
                        }
                    }elseif ( isset( $_POST['billing_last_name'] ) && 
                        $_POST['billing_last_name'] != '' &&
                        isset( $_POST['wcap_record_added'] ) &&
                        $_POST['wcap_record_added'] !== 'false' &&
                        wcap_get_cart_session( 'billing_last_name' ) != '' ) {

                        /**
                         * If a record is present in the guest cart history table for the same
                         * Last name and same session id.
                         */
                        $query_existing_guest_last_name = 
                            "SELECT  wpag.billing_last_name, wpah.session_id 
                            FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "` As wpag 
                            LEFT JOIN `". WCAP_ABANDONED_CART_HISTORY_TABLE."` AS wpah 
                            ON wpag.id = wpah.user_id 
                            WHERE wpag.billing_last_name = %s 
                            AND wpah.session_id = %s";
                        $results_existing_guest_last_name = $wpdb->get_results( 
                            $wpdb->prepare( 
                                $query_existing_guest_last_name, 
                                $_POST['billing_last_name'], 
                                $wcap_wc_session_key ) );

                        if( isset( $results_existing_guest_last_name ) && 
                            count ( $results_existing_guest_last_name ) > 0 && 
                            isset( $_POST['billing_last_name'] ) && 
                            $_POST['billing_last_name'] != '' ) {

                            Wcap_Ajax::wcap_update_guest_record_on_same_last_name( $wcap_wc_session_key, $wc_shipping_charges );
                            wp_die();
                        }
                    }elseif ( isset( $_POST['billing_email'] ) && $_POST['billing_email'] != '' || 
                         isset( $_POST['billing_phone'] ) && $_POST['billing_phone'] != '' || 
                         isset( $_POST['billing_first_name'] ) && $_POST['billing_first_name'] != '' ||
                         isset( $_POST['billing_last_name'] ) && $_POST['billing_last_name'] != '' ) {

                        if ( isset( $_POST['billing_first_name'] ) && $_POST['billing_first_name'] != '' ) {
                            wcap_set_cart_session( 'billing_first_name', $_POST['billing_first_name'] );
                        }
                        if ( isset( $_POST['billing_last_name'] ) && $_POST['billing_last_name'] != '' ) {
                            wcap_set_cart_session( 'billing_last_name', $_POST['billing_last_name'] );
                        }
                        if ( isset( $_POST['billing_postcode'] ) && $_POST['billing_postcode'] != '' ) {
                            wcap_set_cart_session( 'billing_postcode', $_POST['billing_postcode'] );
                        }
                        if ( isset( $_POST['billing_email'] ) && $_POST['billing_email'] != '' ) {
                            wcap_set_cart_session( 'billing_email', $_POST['billing_email'] );
                        }else{
                            wcap_set_cart_session( 'billing_email', '' );
                        }
                        if ( isset( $_POST['billing_phone'] ) && $_POST['billing_phone'] != '' ) {
                            wcap_set_cart_session( 'billing_phone', $_POST['billing_phone'] );
                        }
                        if ( isset( $_POST['shipping_postcode'] ) && $_POST['shipping_postcode'] != '' ) {
                            wcap_set_cart_session( 'shipping_postcode', $_POST['shipping_postcode'] );
                        }

                        $session_billing_email = wcap_get_cart_session( 'billing_email' );

                        /**
                         * If a record is present in the guest cart history table for the same email id, then update
                         * the previous records of the user
                         */
                        $query_guest   = "SELECT id FROM `" . WCAP_GUEST_CART_HISTORY_TABLE . "`  WHERE email_id = %s";
                        $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $session_billing_email ) );

                        if ( $results_guest ) {
                            foreach( $results_guest as $key => $value ) {
                                $query  = "SELECT id FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d AND recovered_cart = '0'";
                                $result = $wpdb->get_results( $wpdb->prepare( $query, $value->id ) );

                                if ( $result ) {
                                    $query_update_same_record = "UPDATE `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` SET cart_ignored = '1' WHERE user_id = '".$value->id."' ";
                                    $wpdb->query( $query_update_same_record );
                                }
                            }
                        }

                        $billing_first_name = wcap_get_cart_session( 'billing_first_name' );
                        $billing_last_name  = wcap_get_cart_session( 'billing_last_name' );
                        $billing_phone      = wcap_get_cart_session( 'billing_phone' );

                        $billing_email      = $session_billing_email;

                        $shipping_zipcode = $billing_zipcode = '';
                        if ( wcap_get_cart_session( 'shipping_postcode' ) != '' ) {
                            $shipping_zipcode = wcap_get_cart_session( 'shipping_postcode' );
                        } else if ( wcap_get_cart_session( 'billing_postcode' ) != "" ) {
                            $shipping_zipcode = $billing_zipcode = wcap_get_cart_session( 'billing_postcode' );
                        }

                        /**
                         * Insert the guest record.
                         */
                        $insert_guest     = "INSERT INTO `" . WCAP_GUEST_CART_HISTORY_TABLE . "`( billing_first_name, billing_last_name, email_id, phone, billing_zipcode, shipping_zipcode, shipping_charges, billing_country ) VALUES ( %s , %s , %s , %s , %s , %s, %s, %s )";
                        $wpdb->query( $wpdb->prepare( $insert_guest, $billing_first_name, $billing_last_name, $billing_email, $billing_phone, $billing_zipcode, $shipping_zipcode, $wc_shipping_charges, $_POST['billing_country'] ) );

                        /**
                         * Insert record in abandoned cart table for the guest user.
                         */
                        $user_id                  = $wpdb->insert_id;
                        wcap_set_cart_session( 'wcap_user_id', $user_id );
                        $current_time             = current_time( 'timestamp' );
                        $cut_off_time             = get_option( 'ac_cart_abandoned_time_guest' );
                        $cart_cut_off_time        = $cut_off_time * 60;
                        $compare_time             = $current_time - $cart_cut_off_time;

                        /**
                         * Check if the generated user id is present in the abandoned cart history table.
                         * If yes then we will update that abandoned cart history row.
                         * If not then create the new record in the abandoned cart history table.
                         */
                        $query               = "SELECT * FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0' AND user_type = 'GUEST'";
                        $results             = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
                        $cart                = array();

                        if ( function_exists('WC') ) {
                            $cart['cart'] = WC()->session->cart;
                        } else {
                            $cart['cart'] = $woocommerce->session->cart;
                        }

                        if ( wcap_get_cart_session( 'wcap_user_ref' ) != '' ) {
                            $cart['wcap_user_ref'] = wcap_get_cart_session( 'wcap_user_ref' );
                        }

                        /**
                         * Count 0 indicate that the guest user id is not exists in the abandoned history table.
                         */
                        if ( count( $results ) == 0 ) {

                            if ( function_exists( 'icl_register_string' ) ) {
                                $current_user_lang = isset( $_SESSION['wpml_globalcart_language'] ) ? $_SESSION['wpml_globalcart_language'] : ICL_LANGUAGE_CODE;
                            } else {
                                $current_user_lang = 'en';
                            }
                            $cart_info = json_encode( $cart );
                            $query     = "SELECT COUNT(`id`) FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` 
                                            WHERE 
                                            session_id LIKE '$wcap_wc_session_key' 
                                            AND 
                                            cart_ignored = '0' 
                                            AND 
                                            recovered_cart = '0' ";
                            $results_count = $wpdb->get_var( $query );

                            if ( $results_count == 0 ) {

                                Wcap_Ajax::wcap_add_guest_record_for_new_session ( $user_id, $cart_info, $current_time, $current_user_lang, $current_user_ip_address );
                            } else {

                                Wcap_Ajax::wcap_add_guest_record_for_same_session ( $cart_info, $user_id, $current_time, $current_user_lang, $current_user_ip_address, $current_user_ip_address );
                            }
                        }
                        wp_die();
                    }
                }
            }else if ( ! is_user_logged_in() &&
                     ( true == $get_restricted_ip_address ||
                       true == $get_restricted_email_address ||
                       true == $get_restricted_domain_address ) ) {
                global $wpdb, $woocommerce;

                $wcap_wc_session_key = Wcap_Common::wcap_get_guest_session_key();
                $delete_guest = "DELETE FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE session_id = '" . $wcap_wc_session_key . "'";
                $wpdb->query( $delete_guest );
            }
        }

        /**
         * Update the Guest user record if we found the same last name for the same session.
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @param string $wcap_wc_session_key The session key of the guest user
         * @param int | string $wc_shipping_charges The charges of the shipping
         * @since: 7.6
         */
        public static function wcap_update_guest_record_on_same_last_name ( $wcap_wc_session_key, $wc_shipping_charges ) {

            global $wpdb, $woocommerce;

            $update_on_last_name_info = 
                "UPDATE `" . WCAP_GUEST_CART_HISTORY_TABLE . "` AS wpag 
                LEFT JOIN `".WCAP_ABANDONED_CART_HISTORY_TABLE."` AS wpah 
                ON wpag.id = wpah.user_id 
                SET wpag.phone = %s , 
                    wpag.email_id = %s, 
                    wpag.billing_first_name = %s ,
                    wpag.billing_country = %s,
                    wpag.shipping_charges = %s 
                WHERE wpag.billing_first_name = %s 
                AND wpah.session_id = %s";

            $wpdb->query( 
                $wpdb->prepare( 
                    $update_on_last_name_info, 
                    $_POST['billing_phone'], 
                    $_POST['billing_email'], 
                    $_POST['billing_first_name'] ,
                    $_POST['billing_country'],
                    $wc_shipping_charges,
                    $_POST['billing_last_name'], 
                    $wcap_wc_session_key ) );

            wcap_set_cart_session( 'billing_last_name', $_POST['billing_last_name'] );
            $guest_id = wcap_get_cart_session( 'wcap_user_id' );

            $query_update_get     = "SELECT * FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d";
            $get_abandoned_record = $wpdb->get_results( $wpdb->prepare( $query_update_get, $guest_id ) );

            $abandoned_cart_id             = $get_abandoned_record[0]->id;
            wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );

            do_action ('acfac_add_data', $abandoned_cart_id );
        }

        /**
         * Update the Guest user record if we found the same first name for the same session.
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @param string $wcap_wc_session_key The session key of the guest user
         * @param int | string $wc_shipping_charges The charges of the shipping
         * @since: 7.6
         */
        public static function wcap_update_guest_record_on_same_first_name ( $wcap_wc_session_key, $wc_shipping_charges ) {

            global $wpdb, $woocommerce;

            $update_on_first_name_info = 
                "UPDATE `" . WCAP_GUEST_CART_HISTORY_TABLE . "` AS wpag 
                LEFT JOIN `".WCAP_ABANDONED_CART_HISTORY_TABLE."` AS wpah 
                ON wpag.id = wpah.user_id 
                SET wpag.phone = %s , 
                    wpag.email_id = %s, 
                    wpag.billing_last_name = %s , 
                    wpag.billing_country = %s,
                    wpag.shipping_charges = %s
                WHERE wpag.billing_first_name = %s 
                AND wpah.session_id = %s";

            $wpdb->query( 
                $wpdb->prepare( 
                    $update_on_first_name_info, 
                    $_POST['billing_phone'], 
                    $_POST['billing_email'], 
                    $_POST['billing_last_name'] , 
                    $_POST['billing_country'],
                    $wc_shipping_charges,
                    $_POST['billing_first_name'], 
                    $wcap_wc_session_key ) );

            wcap_set_cart_session( 'billing_first_name', $_POST['billing_first_name'] );
            $guest_id = wcap_get_cart_session( 'wcap_user_id' );

            $query_update_get     = "SELECT * FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d";
            $get_abandoned_record = $wpdb->get_results( $wpdb->prepare( $query_update_get, $guest_id ) );

            $abandoned_cart_id             = $get_abandoned_record[0]->id;
            wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );

            do_action ('acfac_add_data', $abandoned_cart_id );
        }

        /**
         * Update the Guest user reocrd if we found the same email address for the same session.
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @param string $wcap_wc_session_key The session key of the guest user
         * @param int | string $wc_shipping_charges The charges of the shipping
         * @since: 7.6
         */
        public static function wcap_update_guest_record_on_same_email ( $wcap_wc_session_key, $wc_shipping_charges ) {

            global $wpdb, $woocommerce;
            // default the variable
            $abandoned_cart_id = 0;
            
            $update_mobile_info = 
                "UPDATE `" . WCAP_GUEST_CART_HISTORY_TABLE . "` AS wpag 
                LEFT JOIN `".WCAP_ABANDONED_CART_HISTORY_TABLE."` AS wpah 
                ON wpag.id = wpah.user_id 
                SET wpag.phone = %s , 
                    wpag.billing_first_name = %s,  
                    wpag.billing_last_name = %s, 
                    wpag.billing_country = %s,
                    wpag.shipping_charges = %s, 
                wpag.email_id = %s 
                WHERE wpah.session_id = %s";
            $wpdb->query( 
                $wpdb->prepare( 
                    $update_mobile_info, 
                    $_POST['billing_phone'], 
                    $_POST['billing_first_name'], 
                    $_POST['billing_last_name'] , 
                    $_POST['billing_country'],
                    $wc_shipping_charges, 
                    $_POST['billing_email'], 
                    $wcap_wc_session_key ) );

            wcap_set_cart_session( 'billing_phone', $_POST['billing_phone'] );
            wcap_set_cart_session( 'billing_email', $_POST['billing_email'] );

            if ( isset( $_POST['wcap_abandoned_id'] ) && $_POST['wcap_abandoned_id'] !== '' ) {
                $abandoned_cart_id = $_POST['wcap_abandoned_id'];
                wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );
            }else {
                $guest_id = wcap_get_cart_session( 'wcap_user_id' );

                $query_update_get     = "SELECT * FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d";
                
                $get_abandoned_record = $wpdb->get_results( $wpdb->prepare( $query_update_get, $guest_id ) );

                if( is_array( $get_abandoned_record ) && count( $get_abandoned_record ) > 0 ) { 
                    $abandoned_cart_id             = $get_abandoned_record[0]->id;
                    wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );
                }
            }
            do_action ('acfac_add_data', $abandoned_cart_id );
        }

        /**
         * Update the Guest user reocrd if we found the same Phone number for the same session.
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @param string $wcap_wc_session_key The session key of the guest user
         * @param int | string $wc_shipping_charges The charges of the shipping
         * @since: 7.6
         */
        public static function wcap_update_guest_record_on_same_phone ( $wcap_wc_session_key, $wc_shipping_charges ) {

            global $wpdb, $woocommerce;

            $update_mobile_info = 
                "UPDATE `" . WCAP_GUEST_CART_HISTORY_TABLE . "` AS wpag 
                LEFT JOIN `".WCAP_ABANDONED_CART_HISTORY_TABLE."` AS wpah 
                ON wpag.id = wpah.user_id 
                SET wpag.phone = %s , 
                    wpag.billing_first_name = %s,  
                    wpag.billing_last_name = %s, 
                    wpag.billing_country = %s,
                    wpag.email_id = %s, 
                    wpag.shipping_charges = %s 
                WHERE wpag.phone = %s 
                AND wpah.session_id = %s";
            $wpdb->query( 
                $wpdb->prepare( 
                    $update_mobile_info, 
                    $_POST['billing_phone'], 
                    $_POST['billing_first_name'], 
                    $_POST['billing_last_name'] , 
                    $_POST['billing_country'],
                    $_POST['billing_email'], 
                    $wc_shipping_charges,
                    $_POST['billing_phone'], 
                    $wcap_wc_session_key ) );

            wcap_set_cart_session( 'billing_phone', $_POST['billing_phone'] );

            if ( isset( $_POST['wcap_abandoned_id'] ) && $_POST['wcap_abandoned_id'] !== '' ) {
                wcap_set_cart_session( 'wcap_abandoned_id', $_POST['wcap_abandoned_id'] );
            }else {
                $guest_id = wcap_get_cart_session( 'wcap_user_id' );

                $query_update_get     = "SELECT * FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d ";
                $get_abandoned_record = $wpdb->get_results( $wpdb->prepare( $query_update_get, $guest_id ) );

                $abandoned_cart_id             = $get_abandoned_record[0]->id;
                wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );
            }

            do_action ('acfac_add_data', $abandoned_cart_id );
        }

        /**
         * Insert the new record for the guest user if we do not have any relevant record for the user.
         * @globals mixed $wpdb 
         * @globals mixed $woocommerce
         * @param int | string $user_id User id of the abandoned cart
         * @param json_encode $cart_info Cart information
         * @param timestamp $current_time Current Time
         * @param string $current_user_lang User selected language while abandoing the cart
         * @param string $current_user_ip_address Ip address of the user.
         * @since 6.0
         */
        public static function wcap_add_guest_record_for_new_session( $user_id, $cart_info, $current_time, $current_user_lang, $current_user_ip_address ) {

            global $wpdb, $woocommerce;

            $wcap_wc_session_key = Wcap_Common::wcap_get_guest_session_key();

            $abandoned_cart_id = WCAP_DB_Layer::insert_cart_history( 
                $user_id, 
                $cart_info, 
                $current_time, 
                '0', 
                '0', 
                '', 
                'GUEST', 
                $current_user_lang, 
                $wcap_wc_session_key, 
                $current_user_ip_address, 
                '', 
                '' );

            wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );

            $insert_persistent_cart = "INSERT INTO `" . $wpdb->prefix . "usermeta`( user_id, meta_key, meta_value )
                                      VALUES ( %d , '_woocommerce_persistent_cart', %s  )";
            $wpdb->query( $wpdb->prepare( $insert_persistent_cart, $user_id, $cart_info ) );

            do_action ('acfac_add_data', $abandoned_cart_id );
        }

        /**
         * It will update the reocrd of the same user when we found the user data in the database.
         * @globals mixed $wpdb 
         * @globals mixed $woocommerce
         * @param int | string $user_id User id of the abandoned cart
         * @param json_encode $cart_info Cart information
         * @param timestamp $current_time Current Time
         * @param string $current_user_lang User selected language while abandoing the cart
         * @param string $current_user_ip_address Ip address of the user.
         * @since 6.0
         */
        public static function wcap_add_guest_record_for_same_session( $cart_info, $user_id, $current_time, $current_user_lang, $current_user_ip_address ){

            global $wpdb, $woocommerce;

            $wcap_wc_session_key = Wcap_Common::wcap_get_guest_session_key();

            if ( function_exists( 'icl_object_id' ) ) {
                $cart_info = WCAP_DB_Layer::add_wcml_currency( $cart_info );
            }

            $query_update = 
                "UPDATE `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` 
                SET abandoned_cart_info = %s , 
                    user_id = %d , 
                    abandoned_cart_time = %d, 
                    language = %s, 
                    ip_address = %s 
                WHERE session_id = %s 
                AND cart_ignored = '0'";

            $wpdb->query( 
                $wpdb->prepare( 
                    $query_update, 
                    $cart_info, 
                    $user_id, 
                    $current_time, 
                    $current_user_lang, 
                    $current_user_ip_address, 
                    $wcap_wc_session_key ) );

            if ( isset( $_POST['wcap_abandoned_id'] ) && $_POST['wcap_abandoned_id'] !== '' ) {
                $abandoned_cart_id = $_POST['wcap_abandoned_id'];
                wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );
            }else {
                $query_update_get = "SELECT id FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d AND cart_ignored='0' AND session_id = %s ";
                $get_abandoned_record = $wpdb->get_results( $wpdb->prepare( $query_update_get, $user_id, $wcap_wc_session_key ) );

                $abandoned_cart_id = $get_abandoned_record[0]->id;
                wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );
            }

            $insert_persistent_cart = 
                "INSERT INTO `" . $wpdb->prefix . "usermeta` 
                    ( user_id, meta_key, meta_value )
                    VALUES ( %d , '_woocommerce_persistent_cart', %s )";
            $wpdb->query( $wpdb->prepare( $insert_persistent_cart, $user_id, $cart_info ) );

            do_action ('acfac_add_data', $abandoned_cart_id );
        }
                
    }
}
?>
