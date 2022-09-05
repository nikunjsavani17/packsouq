<?php
/**
 * It will add the template string to the WPML.
 * Like : Body, subject, Wc header text
 * @author   Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/I18N
 * @since 5.0
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists('Wcap_Localization' ) ) {
    /**
     * It will add the template string to the WPML.
     * Like : Body, subject, Wc header text
     *
     */
    class Wcap_Localization{
        /** 
         * This function used to register template string to WPML.
         * Like : Body, subject, Wc header text
         * @hook admin_init
         * @globals mixed $wpdb
         * @since 2.7
         */
        public static function wcap_register_template_string_for_wpml() {
            if ( function_exists('icl_register_string') ) {
                global $wpdb;
                $context = 'WCAP';
                
                $result = $wpdb->get_results("SELECT * FROM ".WCAP_EMAIL_TEMPLATE_TABLE."");
                foreach ( $result as $each_template ) {
                    $name_msg = 'wcap_template_' . $each_template->id . '_message';
                    $value_msg = $each_template->body;
                    icl_register_string($context, $name_msg, $value_msg); //for registering message

                    $name_sub = 'wcap_template_' . $each_template->id . '_subject';
                    $value_sub = $each_template->subject;
                    icl_register_string($context, $name_sub, $value_sub); //for registering subject

                    $template_name = 'wcap_template_' . $each_template->id . '_template_name';
                    $getvalue_template_name = $each_template->template_name;
                    icl_register_string($context, $template_name, $getvalue_template_name);

                    $wc_email_header = 'wcap_template_' . $each_template->id . '_wc_email_header';
                    $getvalue_wc_email_header = $each_template->wc_email_header;
                    icl_register_string($context, $wc_email_header, $getvalue_wc_email_header);
                }
            }
        }
    }
}