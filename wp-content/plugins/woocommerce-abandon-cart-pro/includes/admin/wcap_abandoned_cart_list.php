<?php
/**
 * Display the Abandoned carts list.
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Tab
 * @since 5.0
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists('Wcap_Abandoned_Cart_List' ) ) {
    /**
     * Display the Abandoned carts list.
     * @since 5.0
     */
    class Wcap_Abandoned_Cart_List {

        /**
         * This function will show the abandoned cart list. 
         * It will show the all views of the abandoned cart list tab.
         * It will also add the Print & CSV buttons.
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @since 5.0
         */
        public static function wcap_display_abandoned_cart_list( ){
            global $woocommerce, $wpdb;
            $duration_range = "";
            if ( isset( $_POST['duration_select'] ) ) {
                $duration_range = $_POST['duration_select'];
                $_SESSION['duration']   = $duration_range;
            }
            if ( '' == $duration_range && isset( $_GET['duration_select'] ) ) {
                $duration_range = $_GET['duration_select'];
                $_SESSION['duration']   = $duration_range;
            }
            if ( isset($_SESSION ['duration'] ) && '' != $_SESSION ['duration'] ){
                $duration_range = $_SESSION ['duration'];
            }
            if ( '' == $duration_range ) {
                $duration_range = "last_seven";
                $_SESSION['duration']   = $duration_range;
            }
            $wcap_ac_class  = new Woocommerce_Abandon_Cart();
            ?>
            <p>
                <?php _e( 'The list below shows all Abandoned Carts which have remained in cart for a time higher than the "Cart abandoned cut-off time" setting.', 'woocommerce-ac' ); ?>
            </p>
            <div id="abandoned_stats_date" class="postbox" style="display:block">
                <div class="inside">
                    <form method="post" action="admin.php?page=woocommerce_ac_page&action=listcart" id="ac_stats">
                        <select id="duration_select" name="duration_select" >
                            <?php
                            foreach ( $wcap_ac_class->duration_range_select as $key => $value ) {
                                $sel = "";
                                if ( $key == $duration_range ) {
                                    $sel = __( " selected ", "woocommerce-ac" );
                                }
                                echo"<option value='" . $key . "' $sel> " . __( $value,'woocommerce-ac' ) . " </option>";
                            }
                            $date_sett = $wcap_ac_class->start_end_dates[ $duration_range ];
                            ?>
                        </select>

                        <?php
                        $start_date_range = '';
                        if ( isset( $_POST['start_date'] ) ){
                            $start_date_range = $_POST['start_date'];
                            $_SESSION ['start_date'] = $start_date_range;
                        }

                        if ( isset( $_SESSION ['start_date'] ) &&  '' != $_SESSION ['start_date'] ) {
                            $start_date_range = $_SESSION ['start_date'];
                        }
                        if ( '' == $start_date_range ) {
                            $start_date_range = $date_sett['start_date'];
                            $_SESSION ['start_date'] = $start_date_range;
                        }
                        $end_date_range = '';
                        if ( isset( $_POST['end_date'] ) ) {
                            $end_date_range = $_POST['end_date'];
                            $_SESSION ['end_date'] = $end_date_range;
                        }
                        if ( isset($_SESSION ['end_date'] ) && '' != $_SESSION ['end_date'] ){
                            $end_date_range = $_SESSION ['end_date'];
                        }
                        if ( '' == $end_date_range ) {
                            $end_date_range = $date_sett['end_date'];
                            $_SESSION ['end_date'] = $end_date_range;
                        }
                        ?>
                        <label class="start_label" for="start_day"> <?php _e( 'Start Date:', 'woocommerce-ac' ); ?> </label>
                        <input type="text" id="start_date" name="start_date" readonly="readonly" value="<?php echo $start_date_range; ?>"/>

                        <label class="end_label" for="end_day"> <?php _e( 'End Date:', 'woocommerce-ac' ); ?> </label>
                        <input type="text" id="end_date" name="end_date" readonly="readonly" value="<?php echo $end_date_range; ?>"/>

                        <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Go', 'woocommerce-ac' ); ?>"  />
                    </form>
                </div>
            </div>
           <?php
            $get_all_abandoned_count      = Wcap_Common::wcap_get_abandoned_order_count( 'wcap_all_abandoned' );
            $get_trash_abandoned_count    = Wcap_Common::wcap_get_abandoned_order_count( 'wcap_trash_abandoned' );
            $get_registered_user_ac_count = Wcap_Common::wcap_get_abandoned_order_count( 'wcap_all_registered' );
            $get_guest_user_ac_count      = Wcap_Common::wcap_get_abandoned_order_count( 'wcap_all_guest' );
            $get_visitor_user_ac_count    = Wcap_Common::wcap_get_abandoned_order_count( 'wcap_all_visitor' );

            $wcap_user_reg_text = 'User';
            if ( $get_registered_user_ac_count > 1){
                $wcap_user_reg_text = 'Users';
            }
            $wcap_user_gus_text = 'User';
            if ( $get_guest_user_ac_count > 1){
                $wcap_user_gus_text = 'Users';
            }
            $wcap_user_vis_text = 'User';
            if ( $get_visitor_user_ac_count > 1){
                $wcap_user_vis_text = 'Users';
            }

            $wcap_all_abandoned_carts  = $wcap_trash_abandoned = $section = $wcap_all_registered = $wcap_all_guest = $wcap_all_visitor = "" ;
            if ( isset( $_GET[ 'wcap_section' ] ) ) {
                $section = $_GET[ 'wcap_section' ];
            } else {
                $section = '';
            }
            if ( $section == 'wcap_all_abandoned' || $section == '' ||  $get_trash_abandoned_count == 0 ) {
                $wcap_all_abandoned_carts = "current";
            }
            if( $section == 'wcap_trash_abandoned' ) {
                $wcap_trash_abandoned = "current";
                $wcap_all_abandoned_carts = "";
            }
            if( $section == 'wcap_all_registered' ) {
                $wcap_all_registered = "current";
                $wcap_all_abandoned_carts = "";
            }
            if( $section == 'wcap_all_guest' ) {
                $wcap_all_guest = "current";
                $wcap_all_abandoned_carts = "";
            }

            if( $section == 'wcap_all_visitor' ) {
                $wcap_all_visitor = "current";
                $wcap_all_abandoned_carts = "";
            }
            ?>
            <ul class="subsubsub" id="wcap_recovered_orders_list">
                <li>
                    <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcap_section=wcap_all_abandoned" class="<?php echo $wcap_all_abandoned_carts; ?>"><?php _e( "All ", 'woocommerce-ac' ) ;?> <span class = "count" > <?php echo "( $get_all_abandoned_count )" ?> </span></a>
                </li>

                <?php if ($get_trash_abandoned_count > 0 ) { ?>
                   <li>
                   | <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcap_section=wcap_trash_abandoned" class="<?php echo $wcap_trash_abandoned; ?>"><?php _e( "Trash ", 'woocommerce-ac' );?> <span class = "count" > <?php echo "( $get_trash_abandoned_count )" ?> </a>
                </li>
                <?php } ?>

                <?php if ($get_registered_user_ac_count > 0 ) { ?>
                <li>
                    | <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcap_section=wcap_all_registered" class="<?php echo $wcap_all_registered; ?>"><?php _e( " Registered $wcap_user_reg_text ", 'woocommerce-ac' ) ;?> <span class = "count" > <?php echo "( $get_registered_user_ac_count )" ?> </span></a>
                </li>
                <?php } ?>

                <?php if ($get_guest_user_ac_count > 0 ) { ?>
                <li>
                    | <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcap_section=wcap_all_guest" class="<?php echo $wcap_all_guest; ?>"><?php _e( " Guest $wcap_user_gus_text ", 'woocommerce-ac' ) ;?> <span class = "count" > <?php echo "( $get_guest_user_ac_count )" ?> </span></a>
                </li>
                <?php } ?>

                <?php if ($get_visitor_user_ac_count > 0 ) { ?>
                <li>
                    | <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcap_section=wcap_all_visitor" class="<?php echo $wcap_all_visitor; ?>"><?php _e( " Carts without Customer Details ", 'woocommerce-ac' ) ;?> <span class = "count" > <?php echo "( $get_visitor_user_ac_count )" ?> </span></a>
                </li>
                <?php } ?>


            </ul>
            <br class="clear">
            <div id="wcap_ac_bulk_message" class="error">
                <p class="wcap_ac_bulk_message_p">
                    <strong>
                        <?php _e( "" ); ?>
                    </strong>
                </p>
            </div>

            <?php
            global $wpdb;
            if ( $section == 'wcap_all_abandoned' || $section == ''
                || $section == 'wcap_all_registered' || $section == 'wcap_all_guest'
                || $section == 'wcap_all_visitor' ) {

                $wcap_abandoned_order_list = new Wcap_Abandoned_Orders_Table();
            }else if ( $section == 'wcap_trash_abandoned' ) {

                $wcap_abandoned_order_list = new Wcap_Abandoned_Trash_Orders_Table();
            }
                $wcap_abandoned_order_list->wcap_abandoned_order_prepare_items();
            ?>
            <div class="wrap">
                <form id="wcap-abandoned-orders" method="get" >
                    <input type="hidden" name="page" value="woocommerce_ac_page" />
                    <input type="hidden" name="action" value="listcart" />
                    <input type="hidden" name="wcap_action" value="listcart" />
                    <div class= "wcap_download" >
                        <a href="<?php echo esc_url( add_query_arg( 'wcap_download', 'wcap.print' ) ); ?>" target="_blank" class="button-secondary"><?php _e( 'Print', 'woocommerce-ac' ); ?></a>
                        <a href="<?php echo esc_url( add_query_arg( 'wcap_download', 'wcap.csv' ) ); ?>"  class="button-secondary"><?php _e( 'CSV', 'woocommerce-ac' ); ?></a>
                        <?php do_action ( 'wcap_add_buttons_on_abandoned_orders' )?>
                    </div>
                    <?php $wcap_abandoned_order_list->display(); ?>
                </form>
            </div>
        <?php

        }
    }
}
