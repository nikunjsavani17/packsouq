<?php
/**
 * It will update the tables, options and any other changes when we update the plugin.
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Update
 * @since 5.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if ( !class_exists('Wcap_Update_Check' ) ) {
    /**
     * It will update the tables, options and any other changes when we update the plugin.
     */
    class Wcap_Update_Check {

        /**
         * It will upadate the tables and other options for the plugin, it will be called when we upadate the plugin.
         * @globals mixed $wpdb
         * @globals int | string $woocommerce_ac_plugin_version Old version of plugin
         * @globals 
         * @since 5.0
         */
        public static function wcap_update_db_check() {

            global $wpdb, $woocommerce_ac_plugin_version;
            $woocommerce_ac_plugin_version = get_option( 'woocommerce_ac_db_version' );

            if ( $woocommerce_ac_plugin_version != Wcap_Common::wcap_get_version() ) {

                //get the option, if it is not set to individual then convert to individual records and delete the base record
                $ac_settings = get_option( 'ac_settings_status' );
                if ( $ac_settings != 'INDIVIDUAL' ) {

                    //fetch the existing settings and save them as inidividual to be used for the settings API
                    $woocommerce_ac_settings = json_decode( get_option( 'woocommerce_ac_settings' ) );
                    add_option( 'ac_enable_cart_emails',              $woocommerce_ac_settings[0]->enable_cart_notification );
                    add_option( 'ac_cart_abandoned_time',             $woocommerce_ac_settings[0]->cart_time );
                    add_option( 'ac_delete_abandoned_order_days',     $woocommerce_ac_settings[0]->delete_order_days );
                    add_option( 'ac_email_admin_on_recovery',         $woocommerce_ac_settings[0]->email_admin );
                    add_option( 'ac_track_coupons',                   $woocommerce_ac_settings[0]->track_coupons );
                    add_option( 'ac_disable_guest_cart_email',        $woocommerce_ac_settings[0]->disable_guest_cart );
                    add_option( 'ac_disable_logged_in_cart_email',    $woocommerce_ac_settings[0]->disable_logged_in_cart );
                    add_option( 'ac_track_guest_cart_from_cart_page', $woocommerce_ac_settings[0]->disable_guest_cart_from_cart_page );
                    update_option( 'ac_settings_status', 'INDIVIDUAL' );
                    //Delete the main settings record
                    delete_option( 'woocommerce_ac_settings' );
                }
                update_option( 'woocommerce_ac_db_version', '7.11.1' );

                $check_table_query = "SHOW COLUMNS FROM ".WCAP_EMAIL_CLICKED_TABLE." LIKE 'link_clicked'";
                $results = $wpdb->get_results( $check_table_query );
                if ( $results[0]-> Type == 'varchar(60)' ) {
                    $alter_table_query = "ALTER TABLE ".WCAP_EMAIL_CLICKED_TABLE." MODIFY COLUMN link_clicked varchar (500)";
                    $wpdb->get_results( $alter_table_query );
                }

                if ( !get_option( 'ac_cart_abandoned_time_guest' ) ) {
                    $cart_abandoned_time = get_option( 'ac_cart_abandoned_time' );
                    update_option( 'ac_cart_abandoned_time_guest', $cart_abandoned_time );
                }

                $wcap_check_option_available        = "SELECT `option_name` FROM {$wpdb->prefix}options WHERE `option_name` LIKE 'wcap_use_auto_cron'";
                $result_wcap_check_option_available = $wpdb->get_results ($wcap_check_option_available);

                if ( count( $result_wcap_check_option_available ) == 0  ) {
                    $wcap_auto_cron = 'on';
                    update_option( 'wcap_use_auto_cron', $wcap_auto_cron );
                }

                if ( !get_option( 'wcap_cron_time_duration' ) ) {
                    $wcap_cron_duration_time_minutes = 15;
                    update_option( 'wcap_cron_time_duration', $wcap_cron_duration_time_minutes );
                }

                /**
                 * As we do not use the trash feature in sent emails tab we dont need the trash coulmn in the database.
                 * @since: 7.6
                 */
                if ( $wpdb->get_var( "SHOW COLUMNS FROM `".WCAP_EMAIL_SENT_HISTORY_TABLE."` LIKE 'wcap_sent_trash';" ) ) {
                    $wpdb->query( "ALTER TABLE ".WCAP_EMAIL_SENT_HISTORY_TABLE." DROP COLUMN `wcap_sent_trash`;" );
                }
                
                // @since 7.7 - Add new cart status for cart_ignored column in cart history table.
                $check_table_query = "SHOW COLUMNS FROM " . WCAP_ABANDONED_CART_HISTORY_TABLE . " LIKE 'cart_ignored'";
                $results = $wpdb->get_results( $check_table_query );
                if ( $results[0]->Type == "enum('0','1')" ) {
                    $alter_table_query = "ALTER TABLE " . WCAP_ABANDONED_CART_HISTORY_TABLE . " MODIFY COLUMN cart_ignored enum('0','1','2')";
                    $wpdb->query( $alter_table_query );
                }
                
                /**
                 * Create 3 new tables
                 * @since 7.9
                 */
                $wcap_collate = '';
                if ( $wpdb->has_cap( 'collation' ) ) {
                    $wcap_collate = $wpdb->get_charset_collate();
                }
                
                $sql_parent = "CREATE TABLE IF NOT EXISTS " . WCAP_NOTIFICATIONS . " (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `subject` text COLLATE utf8mb4_unicode_ci,
                                `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
                                `type` text COLLATE utf8mb4_unicode_ci NOT NULL,
                                `is_active` enum('0','1') COLLATE utf8mb4_unicode_ci NOT NULL,
                                `frequency` text NOT NULL,
                                `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
                                `default_template` enum('0','1') COLLATE utf8mb4_unicode_ci NOT NULL,
                                PRIMARY KEY (`id`)
                                ) $wcap_collate AUTO_INCREMENT=1 ";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
                $wpdb->query( $sql_parent );
                 
                $sql_meta = "CREATE TABLE IF NOT EXISTS " . WCAP_NOTIFICATIONS_META . " (
                                `meta_id` int(11) NOT NULL AUTO_INCREMENT,
                                `template_id` int(11) NOT NULL,
                                `meta_key` text COLLATE utf8mb4_unicode_ci NOT NULL,
                                `meta_value` text COLLATE utf8mb4_unicode_ci,
                                PRIMARY KEY(`meta_id`)
                                ) $wcap_collate AUTO_INCREMENT=1";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
                $wpdb->query( $sql_meta );

                $sql_tinyurls = "CREATE TABLE IF NOT EXISTS " . WCAP_TINY_URLS . " (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `cart_id` int(11) NOT NULL,
                                    `template_id` int(11) NOT NULL,
                                    `long_url` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                                    `short_code` VARCHAR(10) COLLATE utf8mb4_unicode_ci NOT NULL,
                                    `date_created` int(11) NOT NULL,
                                    `counter` int(11) NOT NULL DEFAULT '0',
                                    `notification_data` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                                    PRIMARY KEY (`id`),
                                    KEY short_code (`short_code`)
                                    ) $wcap_collate AUTO_INCREMENT=52";
                                     
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
                $wpdb->query( $sql_tinyurls );

                /**
                 * @since 7.11.0
                 * Integration with Aelia Currency Switcher
                 */
                $aelia_table = $wpdb->prefix . "abandoned_cart_aelia_currency";

                $aelia_sql = "CREATE TABLE IF NOT EXISTS $aelia_table (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `abandoned_cart_id` int(11) COLLATE utf8_unicode_ci NOT NULL,
                        `acfac_currency` text COLLATE utf8_unicode_ci NOT NULL,
                        `date_time` TIMESTAMP on update CURRENT_TIMESTAMP COLLATE utf8_unicode_ci NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`)
                        ) $wcap_collate AUTO_INCREMENT=1 ";           
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->query( $aelia_sql );
                
                $check_query = "SELECT COUNT(id) FROM " . WCAP_NOTIFICATIONS . " WHERE `default_template` = '1'";
                 
                $get_count = $wpdb->get_var( $check_query );

                $default_fb_body = array( 
                    '{"header":"We saved your cart","subheader":"Purchase now before they are out of stock","header_image":"' . WCAP_PLUGIN_URL . '/includes/fb-recovery/assets/css/images/carts_div.png","checkout_text":"Checkout Now!","unsubscribe_text":"Unsubscribe"}',
                    '{"header":"You left some items in your cart","subheader":"We have saved some items in your cart","header_image":"' . WCAP_PLUGIN_URL . '/includes/fb-recovery/assets/css/images/carts_div.png","checkout_text":"Checkout","unsubscribe_text":"Unsubscribe"}'
                );

                if( isset( $get_count ) && $get_count == 0 ) {

                    // add 2 default sms templates and 2 FB templates
                    $insert_templates = "INSERT INTO " . WCAP_NOTIFICATIONS . " 
                                (`subject`, `body`, `type`, `is_active`, `frequency`, `coupon_code`, `default_template`) 
                                VALUES
                                ( 
                                    NULL,
                                    'Hey {{user.name}}, I noticed you left some products in your cart at {{shop.link}}. If you have any queries, please get in touch with me on {{phone.number}}. - {{shop.name}}', 
                                    'sms', 
                                    '0', 
                                    '30 minutes',
                                    '', 
                                    '1' 
                                ),
                                ( 
                                    NULL,
                                    'Hey {{user.name}}, we have saved your cart at {{shop.name}}. Complete your purchase using {{checkout.link}} now!', 
                                    'sms', 
                                    '0', 
                                    '1 days', 
                                    '',
                                    '1' 
                                ),
                                ( 
                                    'Hey there, We noticed that you left some great products in your cart at " . get_bloginfo( 'name' ) . ". Do not worry we saved them for you:',
                                    '" . $default_fb_body[0] . "', 
                                    'fb', 
                                    '0', 
                                    '30 minutes', 
                                    '',
                                    '1' 
                                ),
                                ( 
                                    'Hey there, There are some great products in your cart you left behind at " . get_bloginfo( 'name' ) . ". Here is a list of items you left behind:',
                                    '" . $default_fb_body[1] . "', 
                                    'fb', 
                                    '0', 
                                    '6 hours', 
                                    '',
                                    '1' 
                                )";
                    $wpdb->query( $insert_templates );
                } else if( isset( $get_count ) && $get_count == 2 ) {

                    /** @since 7.10.0 - Addded default FB templates **/
                    // add 2 default FB templates
                    $insert_fb = "INSERT INTO " . WCAP_NOTIFICATIONS . " 
                                (`subject`, `body`, `type`, `is_active`, `frequency`, `coupon_code`, `default_template`) 
                                VALUES
                                ( 
                                    'Hey there, We noticed that you left some great products in your cart at " . get_bloginfo( 'name' ) . ". Do not worry we saved them for you:',
                                    '" . $default_fb_body[0] . "', 
                                    'fb', 
                                    '0', 
                                    '30 minutes', 
                                    '',
                                    '1' 
                                ),
                                ( 
                                    'Hey there, There are some great products in your cart you left behind at " . get_bloginfo( 'name' ) . ". Here is a list of items you left behind:',
                                    '" . $default_fb_body[1] . "', 
                                    'fb', 
                                    '0', 
                                    '6 hours', 
                                    '',
                                    '1' 
                                )";
                    $wpdb->query( $insert_fb );
                }
                
                /** @since 7.10.0 - Added a new colum in Tiny URls **/
                if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".WCAP_TINY_URLS."` LIKE 'notification_data';" ) ) {
                    $wpdb->query( "ALTER TABLE ".WCAP_TINY_URLS." ADD `notification_data` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL;" );
                }

                if ( !get_option( 'wcap_new_default_templates' ) ) {
                    $default_template = new Wcap_Default_Settings();
                    $default_template->wcap_create_default_templates();

                    add_option( 'wcap_new_default_templates', 1 );
                }
            }
            
            /**
             * Please change in this function if it requires any updation in tables or in options
             */
            Wcap_Update_Check::wcap_alter_tables_if_required();
            Wcap_Update_Check::wcap_update_options_if_required();

            $wcap_get_admin_option = get_option ( 'ac_email_admin_on_abandoned' );
            
            if ( ( $wcap_get_admin_option == 'on' || '' == $wcap_get_admin_option ) && false !== $wcap_get_admin_option ) {
                Wcap_Update_Check::wcap_add_customer_for_template();
            }
        }

        /**
         * It will alter the tables if required.
         * @globals mixed $wpdb
         * @since 5.0
         */
        public static function wcap_alter_tables_if_required() {
            global $wpdb;
            if ( $wpdb->get_var( "SHOW TABLES LIKE '".WCAP_GUEST_CART_HISTORY_TABLE."' " )  && 'yes' != get_option ('wcap_guest_user_id_altered') ) {
                $last_id = $wpdb->get_var( "SELECT max(id) FROM `".WCAP_GUEST_CART_HISTORY_TABLE."`;" );
                if ( NULL != $last_id && $last_id <= 63000000 ) {
                    $wpdb->query( "ALTER TABLE ".WCAP_GUEST_CART_HISTORY_TABLE." AUTO_INCREMENT = 63000000;" );
                    update_option ( 'wcap_guest_user_id_altered' , 'yes' );
                }
            }
            if ( '1' != get_option( 'wcap_alter_tables_ran' ) ) {

                if ( $wpdb->get_var( "SHOW TABLES LIKE '".WCAP_EMAIL_TEMPLATE_TABLE."' " ) ) {
                    
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".WCAP_EMAIL_TEMPLATE_TABLE."` LIKE 'wc_template_filter';" ) ) {
                        $wpdb->query( "ALTER TABLE ".WCAP_EMAIL_TEMPLATE_TABLE." ADD `wc_template_filter` varchar(500) COLLATE utf8_unicode_ci NOT NULL AFTER `wc_email_header`;" );
                    }
                }
                
                if ( $wpdb->get_var( "SHOW TABLES LIKE '".WCAP_ABANDONED_CART_HISTORY_TABLE."' " ) ) {
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".WCAP_ABANDONED_CART_HISTORY_TABLE."` LIKE 'language';" ) ) {
                        $wpdb->query( "ALTER TABLE ".WCAP_ABANDONED_CART_HISTORY_TABLE." ADD `language` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `user_type`;" );
                    }
                    
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".WCAP_ABANDONED_CART_HISTORY_TABLE."` LIKE 'session_id';" ) ) {
                        $wpdb->query( "ALTER TABLE ".WCAP_ABANDONED_CART_HISTORY_TABLE." ADD `session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `language`;" );
                    }
                    
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".WCAP_ABANDONED_CART_HISTORY_TABLE."` LIKE 'ip_address';" ) ) {
                        $wpdb->query( "ALTER TABLE ".WCAP_ABANDONED_CART_HISTORY_TABLE." ADD `ip_address` longtext COLLATE utf8_unicode_ci NOT NULL AFTER  `session_id`;" );
                    }
                    
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".WCAP_ABANDONED_CART_HISTORY_TABLE."` LIKE 'manual_email';" ) ) {
                        $wpdb->query( "ALTER TABLE ".WCAP_ABANDONED_CART_HISTORY_TABLE." ADD `manual_email` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `ip_address`;" );
                    }
                    
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".WCAP_ABANDONED_CART_HISTORY_TABLE."` LIKE 'wcap_trash';" ) ) {
                        $wpdb->query( "ALTER TABLE ".WCAP_ABANDONED_CART_HISTORY_TABLE." ADD `wcap_trash` varchar(1) COLLATE utf8_unicode_ci NOT NULL AFTER `manual_email`;" );
                    }
                }

                /**
                 * Since 4.3
                 * We have added Trash feature in the sent emails tab. It will add new coulmn in the sent history table
                 */
                if ( $wpdb->get_var( "SHOW TABLES LIKE '".WCAP_EMAIL_SENT_HISTORY_TABLE."'" ) ) {
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".WCAP_EMAIL_SENT_HISTORY_TABLE."` LIKE 'wcap_sent_trash';" ) ) {
                        $wpdb->query( "ALTER TABLE ".WCAP_EMAIL_SENT_HISTORY_TABLE." ADD `wcap_sent_trash` varchar(1) COLLATE utf8_unicode_ci NOT NULL AFTER `recovered_order`;" );
                    }
                }
                /**
                 * This is used to prevent guest users wrong Id. If guest users id is less then 63000000 then this code will ensure that we will change the id of guest tables so it wont affect on the next guest users.
                 */
                if ( $wpdb->get_var( "SHOW TABLES LIKE '".WCAP_GUEST_CART_HISTORY_TABLE."' " )  && 'yes' != get_option ('wcap_guest_user_id_altered') ) {
                    $last_id = $wpdb->get_var( "SELECT max(id) FROM `".WCAP_GUEST_CART_HISTORY_TABLE."`;" );
                    if ( NULL != $last_id && $last_id <= 63000000 ) {
                        $wpdb->query( "ALTER TABLE ".WCAP_GUEST_CART_HISTORY_TABLE." AUTO_INCREMENT = 63000000;" );
                        update_option ( 'wcap_guest_user_id_altered' , 'yes' );
                    }
                }

                /*
                 * Since 4.7
                 * We have moved email templates fields in the setings section. SO to remove that fields column fro the db we need it.
                 * For existing user we need to fill this setting with the first template.
                 */
                if ( $wpdb->get_var( "SHOW TABLES LIKE '".WCAP_EMAIL_TEMPLATE_TABLE."' " ) ) {

                    if ( $wpdb->get_var( "SHOW COLUMNS FROM `".WCAP_EMAIL_TEMPLATE_TABLE."` LIKE 'from_email';" ) ) {
                        $get_email_template_query  = "SELECT `from_email` FROM ".WCAP_EMAIL_TEMPLATE_TABLE." WHERE `is_active` = '1' ORDER BY `id` ASC LIMIT 1";
                        $get_email_template_result = $wpdb->get_results ($get_email_template_query);

                        $wcap_from_email = '';
                        if ( isset( $get_email_template_result ) && count ( $get_email_template_result ) > 0 ) {
                            $wcap_from_email =  $get_email_template_result[0]->from_email;

                            /* Store data in setings api*/
                            update_option ( 'wcap_from_email', $wcap_from_email );

                            /* Delete table from the Db*/
                            $wpdb->query( "ALTER TABLE ".WCAP_EMAIL_TEMPLATE_TABLE." DROP COLUMN `from_email`;" );
                        }
                    }

                    if ( $wpdb->get_var( "SHOW COLUMNS FROM `".WCAP_EMAIL_TEMPLATE_TABLE."` LIKE 'from_name';" ) ) {
                        $get_email_template_from_name_query  = "SELECT `from_name` FROM ".WCAP_EMAIL_TEMPLATE_TABLE." WHERE `is_active` = '1' ORDER BY `id` ASC LIMIT 1";
                        $get_email_template_from_name_result = $wpdb->get_results ($get_email_template_from_name_query);

                        $wcap_from_name = '';
                        if ( isset( $get_email_template_from_name_result ) && count ( $get_email_template_from_name_result ) > 0 ){
                            $wcap_from_name =  $get_email_template_from_name_result[0]->from_name;

                            /* Store data in setings api*/
                            update_option ( 'wcap_from_name', $wcap_from_name );

                            /* Delete table from the Db*/
                            $wpdb->query( "ALTER TABLE ".WCAP_EMAIL_TEMPLATE_TABLE." DROP COLUMN `from_name`;" );
                        }
                    }

                    if ( $wpdb->get_var( "SHOW COLUMNS FROM `".WCAP_EMAIL_TEMPLATE_TABLE."` LIKE 'reply_email';" ) ) {
                        $get_email_template_reply_email_query  = "SELECT `reply_email` FROM ".WCAP_EMAIL_TEMPLATE_TABLE." WHERE `is_active` = '1' ORDER BY `id` ASC LIMIT 1";
                        $get_email_template_reply_email_result = $wpdb->get_results ($get_email_template_reply_email_query);

                        $wcap_reply_email = '';
                        if ( isset( $get_email_template_reply_email_result ) && count ( $get_email_template_reply_email_result ) > 0 ){
                            $wcap_reply_email =  $get_email_template_reply_email_result[0]->reply_email;

                            /* Store data in setings api*/
                            update_option ( 'wcap_reply_email', $wcap_reply_email );

                            /* Delete table from the Db*/
                            $wpdb->query( "ALTER TABLE ".WCAP_EMAIL_TEMPLATE_TABLE." DROP COLUMN `reply_email`;" );
                        }
                    }
                }
                update_option( 'wcap_alter_tables_ran', '1', 'no' );
            }

            if ( '1' != get_option( 'wcap_alter_guest_columns' ) ) {
                $wpdb->query( "
                    ALTER TABLE " . WCAP_GUEST_CART_HISTORY_TABLE . " 
                    ADD billing_country TEXT AFTER billing_last_name" );
                update_option( 'wcap_alter_guest_columns', '1', 'no' );
            }
        }

        /**
         * It will alter the options if required.
         * @globals mixed $wpdb
         * @since 5.0
         */
        public static function wcap_update_options_if_required() {

            if ( '1' != get_option( 'wcap_update_options_ran' ) ) {

                global $wpdb;

                if ( !get_option( 'ac_security_key' ) ){
                    update_option( 'ac_security_key', "qJB0rGtIn5UB1xG03efyCp" );
                }
                if ( !get_option( 'wcap_product_image_height' ) ) {
                    $wcap_product_image_height_px = 125;
                    update_option( 'wcap_product_image_height', $wcap_product_image_height_px);
                }
                if ( !get_option( 'wcap_product_image_width' ) ) {
                    $wcap_product_image_width_px = 125;
                    update_option( 'wcap_product_image_width', $wcap_product_image_width_px );
                }

                if ( !get_option( 'wcap_heading_section_text_email' ) ) {
                    $wcap_atc_heading = 'Please enter your email';
                    add_option( 'wcap_heading_section_text_email', $wcap_atc_heading );
                }
                
                if ( !get_option( 'wcap_popup_heading_color_picker' ) ) {
                    $wcap_atc_heading_color = '#737f97';
                    add_option( 'wcap_popup_heading_color_picker', $wcap_atc_heading_color );
                }

                if ( !get_option( 'wcap_text_section_text' ) ) {
                    $wcap_atc_sub_text = 'To add this item to your cart, please enter your email address.';
                    add_option( 'wcap_text_section_text', $wcap_atc_sub_text );
                }

                if ( !get_option( 'wcap_text_section_text' ) ) {
                    $wcap_atc_sub_text = 'To add this item to your cart, please enter your email address.';
                    add_option( 'wcap_text_section_text', $wcap_atc_sub_text );
                }

                if ( !get_option( 'wcap_popup_text_color_picker' ) ) {
                    $wcap_atc_sub_text_color = '#bbc9d2';
                    add_option( 'wcap_popup_text_color_picker', $wcap_atc_sub_text_color );
                }

                if ( !get_option( 'wcap_email_placeholder_section_input_text' ) ) {
                    $wcap_atc_email_field_placeholder = 'Email address';
                    add_option( 'wcap_email_placeholder_section_input_text', $wcap_atc_email_field_placeholder );
                }

                if ( !get_option( 'wcap_button_section_input_text' ) ) {
                    $wcap_atc_button = 'Add to Cart';
                    add_option( 'wcap_button_section_input_text', $wcap_atc_button );
                }

                if ( !get_option( 'wcap_button_color_picker' ) ) {
                    $wcap_atc_button_color = '#0085ba';
                    add_option( 'wcap_button_color_picker', $wcap_atc_button_color );
                }

                if ( !get_option( 'wcap_button_text_color_picker' ) ) {
                    $wcap_atc_button_text_color = '#ffffff';
                    add_option( 'wcap_button_text_color_picker', $wcap_atc_button_text_color );
                }
                
                if ( !get_option( 'wcap_non_mandatory_text' ) ) {
                    $wcap_atc_non_mandatory_text = 'No thanks';
                    add_option( 'wcap_non_mandatory_text', $wcap_atc_non_mandatory_text );
                }
                
                if ( !get_option( 'wcap_atc_enable_modal' ) ) {
                    $wcap_atc_mandatory = 'off';
                    add_option( 'wcap_atc_enable_modal', $wcap_atc_mandatory );
                }
                
                if ( !get_option( 'wcap_atc_mandatory_email' ) ) {
                    $wcap_atc_email_mandatory = 'on';
                    add_option( 'wcap_atc_mandatory_email', $wcap_atc_email_mandatory );
                }

                update_option( 'wcap_update_options_ran', '1', 'no' );
            }

            if ( !get_option( 'wcap_fb_consent_text' ) ) {
                add_option( 'wcap_fb_consent_text', 'Allow order status to be sent to Facebook Messenger' );
            }
            
        }
        /**
         * When we update the plugin we need to add who will receive the email template.
         * @globals mixed $wpdb
         * @since 7.1
         */
        public static function wcap_add_customer_for_template () {
            global $wpdb;

            $wcap_get_all_template   = "SELECT id FROM ". WCAP_EMAIL_TEMPLATE_TABLE ;
            $wcap_result_of_template = $wpdb->get_results ( $wcap_get_all_template );

            $wcap_admin_setting = get_option ('ac_email_admin_on_abandoned');

            if ( isset( $wcap_admin_setting ) && 'on' == $wcap_admin_setting ) {
                $wcap_customers_key = 'wcap_email_customer_admin';
            }else{
                $wcap_customers_key = 'wcap_email_customer';
            }
            if ( count( $wcap_result_of_template ) > 0 ) {

                foreach ($wcap_result_of_template as $wcap_result_of_template_key => $wcap_result_of_template_value ) {
                    add_post_meta ( $wcap_result_of_template_value->id , 'wcap_email_action', $wcap_customers_key );
                }

                delete_option ( 'ac_email_admin_on_abandoned' );
            }
        }
    }
}
