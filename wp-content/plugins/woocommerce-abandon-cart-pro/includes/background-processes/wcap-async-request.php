<?php

if( !class_exists( 'WP_Async_Request' ) ) {
   include_once( WP_PLUGIN_DIR . '/woocommerce/includes/libraries/wp-async-request.php' );
   include_once( WP_PLUGIN_DIR . '/woocommerce/includes/libraries/wp-background-process.php' );
}

class WCAP_Async_Request extends WP_Async_Request {

	/**
	 * @var string
	 */
	protected $action = 'wcap_single_request';

	/**
	 * Handle
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	protected function handle() {

	    $reminder_method = $_POST[ 'method' ];
	    
	    if( isset( $reminder_method ) ) {

	        switch( $reminder_method ) {
	            case 'emails':
	                Wcap_Send_Email_Using_Cron::wcap_abandoned_cart_send_email_notification();
                    break;
	            case 'sms':
	                Wcap_Send_Email_Using_Cron::wcap_send_sms_notifications();
	                break;
                case 'fb':
                	WCAP_FB_Recovery::wcap_fb_cron();
                	break;
	        }
	    }
	}

}
