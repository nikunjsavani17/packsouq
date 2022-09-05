<?php
/**
 * Abandoned Cart Pro for WooCommerce Uninstall
 *
 * Uninstalling Abandoned Cart Pro for WooCommerce deletes tables, and options.
 *
 * @author      Tyche Softwares
 * @package     Abandoned-Cart-Pro-for-WooCommerce/Uninstaller
 * @version     5.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

wp_clear_scheduled_hook( 'woocommerce_ac_send_email_action' );

/**
 * Delete the data for the WordPress Multisite.
 */
if ( is_multisite() ) {
        
        $blog_list = wp_get_sites(  );
        foreach( $blog_list as $blog_list_key => $blog_list_value ) {
             if( $blog_list_value['blog_id'] > 1 ){
                $sub_site_prefix = $wpdb->prefix.$blog_list_value['blog_id']."_";
                $table_name_ac_abandoned_cart_history = $sub_site_prefix . "ac_abandoned_cart_history";
                $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_abandoned_cart_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_abandoned_cart_history );

                $table_name_ac_email_templates = $sub_site_prefix . "ac_email_templates";
                $sql_ac_email_templates = "DROP TABLE " . $table_name_ac_email_templates ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_email_templates );

                $table_name_ac_sent_history = $sub_site_prefix . "ac_sent_history";
                $sql_ac_sent_history = "DROP TABLE " . $table_name_ac_sent_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_sent_history );

                $table_name_ac_opened_emails = $sub_site_prefix . "ac_opened_emails";
                $sql_ac_opened_emails = "DROP TABLE " . $table_name_ac_opened_emails ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_opened_emails );

                $table_name_ac_link_clicked_email = $sub_site_prefix . "ac_link_clicked_email";
                $sql_ac_link_clicked_email = "DROP TABLE " . $table_name_ac_link_clicked_email ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_link_clicked_email );

                $table_name_ac_guest_abandoned_cart_history = $sub_site_prefix . "ac_guest_abandoned_cart_history";
                $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_guest_abandoned_cart_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_abandoned_cart_history );

                $table_name_notifications = $sub_site_prefix . "ac_notifications";
                $sql_notifications = "DROP TABLE " . $table_name_notifications ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_notifications );
                
                $table_name_notifications_meta = $sub_site_prefix . "ac_notifications_meta";
                $sql_notifications_meta = "DROP TABLE " . $table_name_notifications_meta ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_notifications_meta );
                
                $table_name_tiny_urls = $sub_site_prefix . "ac_tiny_urls";
                $sql_ac_tiny_urls = "DROP TABLE " . $table_name_tiny_urls ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_tiny_urls );
                
                $sql_table_user_meta = "DELETE FROM `" . $sub_site_prefix . "usermeta` WHERE meta_key = '_woocommerce_ac_coupon'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_user_meta );

                $sql_table_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = '_woocommerce_ac_coupon'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_post_meta );

                $sql_table_user_meta_cart = "DELETE FROM `" . $sub_site_prefix . "usermeta` WHERE meta_key = '_woocommerce_persistent_cart'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_user_meta_cart );

                $option_name = $wpdb->prefix.$blog_list_value['blog_id']."_wcap_ac_default_templates_installed";

                $sql_table_option_data = "DELETE FROM `" . $sub_site_prefix . "options` WHERE option_name = '".$option_name."' ";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_option_data );

                 /**
                 * ATC meta key for the reports
                 */
                $wcap_atc_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = 'wcap_atc_report'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_atc_post_meta );

                /**
                 * Post meta for recovred email sent
                 */
                $wcap_recovered_email_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = 'wcap_recovered_email_sent'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_recovered_email_post_meta );

                /**
                 * Post meta for the email templates action
                 */
                $wcap_email_templates_action_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = 'wcap_email_action'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_email_templates_action_post_meta );
                
                $wcap_email_templates_action_other_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = 'wcap_other_emails'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_email_templates_action_other_post_meta );

                /**
                 * New created email template time
                 */
                $wcap_new_email_templates_time_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = 'wcap_template_time'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_new_email_templates_time_post_meta );

             } else { 
                $option_name = $wpdb->prefix . "wcap_ac_default_templates_installed";
                delete_option( $option_name );

                $table_name_ac_abandoned_cart_history = $wpdb->prefix . "ac_abandoned_cart_history";
                $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_abandoned_cart_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_abandoned_cart_history );

                $table_name_ac_email_templates = $wpdb->prefix . "ac_email_templates";
                $sql_ac_email_templates = "DROP TABLE " . $table_name_ac_email_templates ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_email_templates );

                $table_name_ac_sent_history = $wpdb->prefix . "ac_sent_history";
                $sql_ac_sent_history = "DROP TABLE " . $table_name_ac_sent_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_sent_history );

                $table_name_ac_opened_emails = $wpdb->prefix . "ac_opened_emails";
                $sql_ac_opened_emails = "DROP TABLE " . $table_name_ac_opened_emails ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_opened_emails );

                $table_name_ac_link_clicked_email = $wpdb->prefix . "ac_link_clicked_email";
                $sql_ac_link_clicked_email = "DROP TABLE " . $table_name_ac_link_clicked_email ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_link_clicked_email );

                $table_name_ac_guest_abandoned_cart_history = $wpdb->prefix . "ac_guest_abandoned_cart_history";
                $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_guest_abandoned_cart_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_abandoned_cart_history );

                $sql_table_user_meta = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_ac_coupon'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_user_meta );

                $sql_table_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = '_woocommerce_ac_coupon'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_post_meta );

                $sql_table_user_meta_cart = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_persistent_cart'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_user_meta_cart );
                
                $table_name_ac_notifications = $wpdb->prefix . "ac_notifications";
                $sql_ac_notifications = "DROP TABLE " . $table_name_ac_notifications ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_notifications );
                
                $table_name_ac_notifications_meta = $wpdb->prefix . "ac_notifications_meta";
                $sql_ac_notifications_meta = "DROP TABLE " . $table_name_ac_notifications_meta ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_notifications_meta );
                
                $table_name_ac_tiny_urls = $wpdb->prefix . "ac_tiny_urls";
                $sql_ac_tiny_urls = "DROP TABLE " . $table_name_ac_tiny_urls ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_tiny_urls );
                
                 /**
                 * ATC meta key for the reports
                 */
                $wcap_atc_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_atc_report'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_atc_post_meta );

                /**
                 * Post meta for recovred email sent
                 */
                $wcap_recovered_email_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_recovered_email_sent'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_recovered_email_post_meta );

                /**
                 * Post meta for the email templates action
                 */
                $wcap_email_templates_action_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_email_action'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_email_templates_action_post_meta );
                
                $wcap_email_templates_action_other_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_other_emails'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_email_templates_action_other_post_meta );

                /**
                 * New created email template time
                 */
                $wcap_new_email_templates_time_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_template_time'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_new_email_templates_time_post_meta );
            }
        }
    } else {
        /**
         * Delete the data for the single website ( Non-Multisite )
         */
        delete_option( 'wcap_ac_default_templates_installed' );

        $table_name_ac_abandoned_cart_history = $wpdb->prefix . "ac_abandoned_cart_history";
        $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_abandoned_cart_history ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_abandoned_cart_history );

        $table_name_ac_email_templates = $wpdb->prefix . "ac_email_templates";
        $sql_ac_email_templates = "DROP TABLE " . $table_name_ac_email_templates ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_email_templates );

        $table_name_ac_sent_history = $wpdb->prefix . "ac_sent_history";
        $sql_ac_sent_history = "DROP TABLE " . $table_name_ac_sent_history ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_sent_history );

        $table_name_ac_opened_emails = $wpdb->prefix . "ac_opened_emails";
        $sql_ac_opened_emails = "DROP TABLE " . $table_name_ac_opened_emails ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_opened_emails );

        $table_name_ac_link_clicked_email = $wpdb->prefix . "ac_link_clicked_email";
        $sql_ac_link_clicked_email = "DROP TABLE " . $table_name_ac_link_clicked_email ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_link_clicked_email );

        $table_name_ac_guest_abandoned_cart_history = $wpdb->prefix . "ac_guest_abandoned_cart_history";
        $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_guest_abandoned_cart_history ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_abandoned_cart_history );

        $table_name_ac_notifications = $wpdb->prefix . "ac_notifications";
        $sql_ac_notifications = "DROP TABLE " . $table_name_ac_notifications ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_notifications );
        
        $table_name_ac_notifications_meta = $wpdb->prefix . "ac_notifications_meta";
        $sql_ac_notifications_meta = "DROP TABLE " . $table_name_ac_notifications_meta ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_notifications_meta );
        
        $table_name_ac_tiny_urls = $wpdb->prefix . "ac_tiny_urls";
        $sql_ac_tiny_urls = "DROP TABLE " . $table_name_ac_tiny_urls ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_tiny_urls );
        
        $sql_table_user_meta = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_ac_coupon'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_table_user_meta );

        $sql_table_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = '_woocommerce_ac_coupon'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_table_post_meta );

        $sql_table_user_meta_cart = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_persistent_cart'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_table_user_meta_cart );
        /**
         * ATC meta key for the reports
         */
        $wcap_atc_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_atc_report'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $wcap_atc_post_meta );

        /**
         * Post meta for recovred email sent
         */
        $wcap_recovered_email_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_recovered_email_sent'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $wcap_recovered_email_post_meta );
        
        /**
         * Post meta for the email templates action
         */
        $wcap_email_templates_action_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_email_action'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $wcap_email_templates_action_post_meta );
        
        $wcap_email_templates_action_other_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_other_emails'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $wcap_email_templates_action_other_post_meta );

        /**
         * New created email template time
         */
        $wcap_new_email_templates_time_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_template_time'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $wcap_new_email_templates_time_post_meta );
    }

    /**
     * Delete the settings option records.
     */
    delete_option( 'ac_enable_cart_emails' );
    delete_option( 'ac_cart_abandoned_time' );
    delete_option( 'ac_cart_abandoned_time_guest' );
    delete_option( 'ac_delete_abandoned_order_days' );
    delete_option( 'ac_email_admin_on_recovery' );
    delete_option( 'ac_track_coupons' );
    delete_option( 'ac_disable_guest_cart_email' );
    delete_option( 'ac_disable_logged_in_cart_email' );
    delete_option( 'ac_track_guest_cart_from_cart_page' );
    delete_option( 'ac_settings_status' );
    delete_option( 'woocommerce_ac_db_version' );
    delete_option( 'wcap_cron_time_duration' );
    delete_option( 'ac_email_admin_on_abandoned' );
    delete_option( 'wcap_restrict_ip_address' );
    delete_option( 'wcap_restrict_email_address' );
    delete_option( 'wcap_restrict_domain_address' );
    delete_option( 'wcap_use_auto_cron' );
    delete_option( 'wcap_from_name' );
    delete_option( 'wcap_from_email' );
    delete_option( 'wcap_reply_email' );
    delete_option( 'wcap_product_image_height' );
    delete_option( 'wcap_product_image_width' );
    delete_option( 'wcap_guest_user_id_altered' );
    delete_option( 'wcap_guest_last_id_checked' );
    delete_option( 'wcap_guest_cart_capture_msg' );
    delete_option( 'wcap_guest_cart_capture_msg' );
    
    /**
     * ATC DATA 
     */
    delete_option( 'wcap_heading_section_text_email' );
    delete_option( 'wcap_popup_heading_color_picker' );
    delete_option( 'wcap_text_section_text' );
    delete_option( 'wcap_popup_text_color_picker' );
    delete_option( 'wcap_email_placeholder_section_input_text' );
    delete_option( 'wcap_button_section_input_text' );
    delete_option( 'wcap_button_color_picker' );
    delete_option( 'wcap_button_text_color_picker' );
    delete_option( 'wcap_non_mandatory_text' );
    delete_option( 'wcap_atc_enable_modal' );
    delete_option( 'wcap_atc_mandatory_email' );
    delete_option( 'wcap_custom_pages_list' );
    
    delete_option( 'wcap_import_page_displayed' );
    delete_option( 'wcap_lite_data_imported' );
    delete_option( 'wcap_alter_tables_ran' );
    delete_option( 'wcap_alter_guest_columns' );
    delete_option( 'wcap_update_options_ran' );
    delete_option( 'wcap_template_customer_added' );

    delete_option( 'ac_capture_email_address_from_url' );

    delete_option( 'edd_sample_license_key_ac_woo' );
    delete_option( 'edd_sample_license_status_ac_woo' );
    
    /**
     * SMS Settings Data
     * @since 7.9
     */
    delete_option( 'wcap_enable_sms_reminders' );
    delete_option( 'wcap_sms_from_phone' );
    delete_option( 'wcap_sms_account_sid' );
    delete_option( 'wcap_sms_auth_token' );

    /**
     * Notice option
     * @since 7.10.0
     */
    delete_option( 'wcap_notice_dissmissed' );
    delete_option( 'wcap_pro_welcome_page_shown' );
    delete_option( 'wcap_pro_welcome_page_shown_time' );
    delete_option( 'wcap_installation_wizard_license_key' );
    delete_option( 'wcap_allow_tracking' );
    
    /**
     * Facebook Settings
     */
    delete_option( 'wcap_enable_fb_reminders' );
    delete_option( 'wcap_enable_fb_reminders_popup' );
    delete_option( 'wcap_fb_consent_text' );
    delete_option( 'wcap_fb_page_id' );
    delete_option( 'wcap_fb_user_icon' );
    delete_option( 'wcap_fb_app_id' );
    delete_option( 'wcap_fb_page_token' );
    delete_option( 'wcap_fb_verify_token' );

    /**
     * For new email templates
     */
    delete_option( 'wcap_new_default_templates' );

    wp_cache_flush();