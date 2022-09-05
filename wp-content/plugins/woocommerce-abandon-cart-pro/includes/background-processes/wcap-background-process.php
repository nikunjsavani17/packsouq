<?php

class WCAP_Background_Process extends WP_Background_Process {

	

	/**
	 * @var string
	 */
	protected $action = 'wcap_all_process';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {

//	    wp_mail( 'pinalj1612@gmail.com',"Inside task",print_r($item,true ));
//	    Wcap_Send_Email_Using_Cron::wcap_sms_reminder( '1', '30' );
	    if( isset( $item ) ) {
	    /*    
	        $reminder_type = isset( $item[ 'type' ] ) ? $item[ 'type' ] : '';
	        $template_id = isset( $item[ 'id' ] ) ? $item[ 'id' ] : '';
	        $cart_id = isset( $item[ 'cart_id' ] ) ? $item[ 'cart_id' ] : ''; */
	//        wp_mail( 'pinalj1612@gmail.com',"Task $item ",print_r($item,true ));
	//        if( $reminder_type != '' && $template_id != '' && $cart_id != '' ) {
	            
	            switch( $item ) {
	                case 'emails':
	                    Wcap_Send_Email_Using_Cron::wcap_abandoned_cart_send_email_notification();
	                    break;
	                case 'sms':
	          //          Wcap_Send_Email_Using_Cron::wcap_sms_reminder( $template_id, $cart_id );
	                    Wcap_Send_Email_Using_Cron::wcap_send_sms_notifications();
	                    break;
	                case 'fb':
	                	WCAP_FB_Recovery::wcap_fb_cron();
	                    break;
	            }
	            
	  //      } 
	    } 
	    return false;
		
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();

		// Show notice to user or perform some other arbitrary task...
	}
	
/*	protected function handle() {
	    
	    $this->lock_process();
	    
	    do {
	        $batch = $this->data;
	  //      wp_mail( 'pinalj1612@gmail.com','Batch',print_r($batch,true ));
	        foreach( $batch as $key => $value ) {
	            
	            foreach( $value as $template_id => $cart_id ) {
	                
	                $details = array( 'type'    => $key,
	                                  'id'      => $template_id,
	                                  'cart_id' => $cart_id
	                 );
	                
	                $task  = $this->task( $details );
	                
	                if ( false !== $task ) {
	               //     $batch[ $key ] = $task;
	                } else {
	                    unset( $this->data[ $key ][ $template_id ] );
	                }
	                
	                if ( $this->time_exceeded() || $this->memory_exceeded() ) {
	                    // Batch limits reached.
	                    break;
	                }
	                 
	            }
                     
	        }
	    } while ( ! $this->time_exceeded() && ! $this->memory_exceeded() && ! $this->is_queue_empty() );
	    
	    // throttle process here with sleep to try and prevent crashing mysql
	    sleep( 5 );
	    
	    $this->unlock_process();
	    
	    // Start next batch or complete process.
	    if ( count( $this->data > 0 ) ) {
	        $this->dispatch();
	    } else {
	        $this->complete();
	    }
	     
	} */

}
