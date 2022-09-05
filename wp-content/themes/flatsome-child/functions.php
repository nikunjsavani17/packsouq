<?php
// Add custom Theme Functions here


/* add_action('wp_ajax_nopriv_tni_custom_fromdatasave', 'tni_custom_fromdatasave');
add_action('wp_ajax_tni_custom_fromdatasave', 'tni_custom_fromdatasave');  
 */
 
 
 
  add_filter( 'woocommerce_currency_symbol', 'wc_change_uae_currency_symbol', 10, 2 );        function wc_change_uae_currency_symbol( $currency_symbol, $currency ) {        switch ( $currency ) {            case 'AED':                $currency_symbol = 'AED';                break;        }                return $currency_symbol;    }			add_action( 'add_meta_boxes', 'create_custom_meta_box' );if ( ! function_exists( 'create_custom_meta_box' ) ){    function create_custom_meta_box()    {        add_meta_box(            'custom_product_meta_box',            __( 'Product Features & Specification', 'cmb' ),            'add_custom_content_meta_box',            'product',            'normal',            'default'        );    }}if ( ! function_exists( 'add_custom_content_meta_box' ) ){    function add_custom_content_meta_box( $post ){        $prefix = '_bhww_';         $ingredients = get_post_meta($post->ID, $prefix.'ingredients_wysiwyg', true) ? get_post_meta($post->ID, $prefix.'ingredients_wysiwyg', true) : '';        $benefits = get_post_meta($post->ID, $prefix.'benefits_wysiwyg', true) ? get_post_meta($post->ID, $prefix.'benefits_wysiwyg', true) : '';        $args['textarea_rows'] = 6;		echo '<div class = "tnimetabox">';				echo '<p style = "font-size: 15px;">'.__( 'Product Video', 'cmb' ).'</p>';       		$field = array(			'id' => 'prod_video',			'label' => __( 'Youtube Video URL', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'prod_video', true ),					);		woocommerce_wp_text_input( $field );						echo '<p style = "font-size: 15px;"> '.__( 'Product Features', 'cmb' ).'</p>';       		$field = array(			'id' => 'features_1',			'label' => __( 'Features 1', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'features_1', true ),					);		woocommerce_wp_text_input( $field );				$field = array(			'id' => 'features_2',			'label' => __( 'Features 2', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'features_2', true ),					);		woocommerce_wp_text_input( $field );				$field = array(			'id' => 'features_3',			'label' => __( 'Features 3', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'features_3', true ),					);		woocommerce_wp_text_input( $field );				$field = array(			'id' => 'features_4',			'label' => __( 'Features 4', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'features_4', true ),					);		woocommerce_wp_text_input( $field );						$field = array(			'id' => 'features_5',			'label' => __( 'Features 5', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'features_5', true ),					);		woocommerce_wp_text_input( $field );				$field = array(			'id' => 'features_6',			'label' => __( 'Features 6', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'features_6', true ),					);		woocommerce_wp_text_input( $field );				$field = array(			'id' => 'features_7',			'label' => __( 'Features 7', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'features_7', true ),					);		woocommerce_wp_text_input( $field );				$field = array(			'id' => 'features_8',			'label' => __( 'Features 8', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'features_8', true ),					);		woocommerce_wp_text_input( $field );						$field = array(			'id' => 'features_9',			'label' => __( 'Features 9', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'features_9', true ),					);		woocommerce_wp_text_input( $field );								$field = array(			'id' => 'features_10',			'label' => __( 'Features 10', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'features_10', true ),					);		woocommerce_wp_text_input( $field );										echo '<p style = "font-size: 15px;">'.__( 'Product Specification', 'cmb' ).'</p>';       		$field = array(			'id' => 'specification_1',			'label' => __( 'Specification 1', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'specification_1', true ),					);		woocommerce_wp_text_input( $field );				$field = array(			'id' => 'specification_2',			'label' => __( 'Specification 2', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'specification_2', true ),					);		woocommerce_wp_text_input( $field );				$field = array(			'id' => 'specification_3',			'label' => __( 'Specification 3', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'specification_3', true ),					);		woocommerce_wp_text_input( $field );				$field = array(			'id' => 'specification_4',			'label' => __( 'Specification 4', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'specification_4', true ),					);		woocommerce_wp_text_input( $field );						$field = array(			'id' => 'specification_5',			'label' => __( 'Specification 5', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'specification_5', true ),					);		woocommerce_wp_text_input( $field );				$field = array(			'id' => 'specification_6',			'label' => __( 'Specification 6', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'specification_6', true ),					);		woocommerce_wp_text_input( $field );				$field = array(			'id' => 'specification_7',			'label' => __( 'Specification 7', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'specification_7', true ),					);		woocommerce_wp_text_input( $field );				$field = array(			'id' => 'specification_8',			'label' => __( 'Specification 8', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'specification_8', true ),					);		woocommerce_wp_text_input( $field );						$field = array(			'id' => 'specification_9',			'label' => __( 'Specification 9', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'specification_9', true ),					);		woocommerce_wp_text_input( $field );						$field = array(			'id' => 'specification_10',			'label' => __( 'Specification 10', 'textdomain' ),			'value'   => get_post_meta( get_the_ID(), 'specification_10', true ),					);		woocommerce_wp_text_input( $field );						echo '</div><style>.tnimetabox label {  float: left;   width: 100%; font-size: 15px;  margin-bottom: 5px;}.tnimetabox input { height: 33px;}</style>';						    }}add_action( 'save_post', 'save_custom_content_meta_box', 10, 1 );if ( ! function_exists( 'save_custom_content_meta_box' ) ){    function save_custom_content_meta_box( $post_id )	{        				if (isset($_POST[ 'prod_video'])) {            update_post_meta( $post_id, 'prod_video', $_POST[ 'prod_video' ]);        }				if (isset($_POST[ 'features_1'])) {            update_post_meta( $post_id, 'features_1', $_POST[ 'features_1' ]);        }		if (isset($_POST[ 'features_2' ])) {            update_post_meta( $post_id, 'features_2', $_POST[ 'features_2' ]);        }		if (isset($_POST[ 'features_3' ])) {            update_post_meta( $post_id, 'features_3', $_POST[ 'features_3' ]);        }		if (isset($_POST[ 'features_4' ])) {            update_post_meta( $post_id, 'features_4', $_POST[ 'features_4' ]);        }		if (isset($_POST[ 'features_5' ])) {            update_post_meta( $post_id, 'features_5', $_POST[ 'features_5' ]);        }		if (isset($_POST[ 'features_6' ])) {            update_post_meta( $post_id, 'features_6', $_POST[ 'features_6' ]);        }		if (isset($_POST[ 'features_7' ])) {            update_post_meta( $post_id, 'features_7', $_POST[ 'features_7' ]);        }		if (isset($_POST[ 'features_8' ])) {            update_post_meta( $post_id, 'features_8', $_POST[ 'features_8' ]);        }		if (isset($_POST[ 'features_9' ])) {            update_post_meta( $post_id, 'features_9', $_POST[ 'features_9' ]);        }		if (isset($_POST[ 'features_10' ])) {            update_post_meta( $post_id, 'features_10', $_POST[ 'features_10' ]);        }								if (isset($_POST[ 'specification_1' ])) {            update_post_meta( $post_id, 'specification_1', $_POST[ 'specification_1' ]);        }		if (isset($_POST[ 'specification_2' ])) {            update_post_meta( $post_id, 'specification_2', $_POST[ 'specification_2' ]);        }		if (isset($_POST[ 'specification_3' ])) {            update_post_meta( $post_id, 'specification_3', $_POST[ 'specification_3' ]);        }		if (isset($_POST[ 'specification_4' ])) {            update_post_meta( $post_id, 'specification_4', $_POST[ 'specification_4' ]);        }		if (isset($_POST[ 'specification_5' ])) {            update_post_meta( $post_id, 'specification_5', $_POST[ 'specification_5' ]);        }		if (isset($_POST[ 'specification_6' ])) {            update_post_meta( $post_id, 'specification_6', $_POST[ 'specification_6' ]);        }		if (isset($_POST[ 'specification_7' ])) {            update_post_meta( $post_id, 'specification_7', $_POST[ 'specification_7' ]);        }		if (isset($_POST[ 'specification_8' ])) {            update_post_meta( $post_id, 'specification_8', $_POST[ 'specification_8' ]);        }		if (isset($_POST[ 'specification_9' ])) {            update_post_meta( $post_id, 'specification_9', $_POST[ 'specification_9' ]);        }		if (isset($_POST[ 'specification_10' ])) {            update_post_meta( $post_id, 'specification_10', $_POST[ 'specification_10' ]);        }		    }}add_action('wp_ajax_save_download_file_info', 'save_download_file_info', 0);add_action('wp_ajax_nopriv_save_download_file_info', 'save_download_file_info');function save_download_file_info() {	session_start();	global $wpdb;	$new_user_name = stripcslashes($_POST['new_user_name']);	$new_user_email = stripcslashes($_POST['new_user_email']);	$product_name = stripcslashes($_POST['product_name']);	$download_file = stripcslashes($_POST['download_file']);		if(isset($_POST['tnidowndigit']) || $_POST['tnidowndigit'] != '' || isset($_SESSION['tnidowndigit']) || $_SESSION['tnidowndigit']  != '') 	{		if($_POST['tnidowndigit'] != $_SESSION['tnidowndigit'])		{			echo json_encode(array('status'=>21,'tnidowndigit'=>$_POST['tnidowndigit'],'tnidowndigit'=>$_SESSION['tnidowndigit']));			exit;		}		}	else	{		echo json_encode(array('status'=>21,'tnidowndigit'=>$_POST['tnidowndigit'],'tnidowndigit'=>$_SESSION['tnidowndigit']));		exit;	} 		$table_name = $wpdb->prefix . "donwload_attement_by";	$wpdb->insert($table_name, array(		'name' => $new_user_name,		'email' => $new_user_email,		'product_name' => $product_name,		'download_file' => $download_file	));	echo json_encode(array('status'=>1));	die;}

  
  /*bhumi*/


 /* Remove product data tabs */
 /*
add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );

function woo_remove_product_tabs( $tabs ) {
    unset( $tabs['additional_information'] );  	// Remove the additional information tab

    return $tabs;
}

/**
 * Reorder product data tabs
 *//*
add_filter( 'woocommerce_product_tabs', 'woo_reorder_tabs', 98 );
function woo_reorder_tabs( $tabs ) {
	$tabs['reviews']['priority'] = 25;			
	$tabs['description']['priority'] = 10;			
	$tabs['questions']['priority'] = 15;
	return $tabs;
}
/*----------*/
/* display feature in description tab */



add_action('woocommerce_product_options_general_product_data', 'woocommerce_product_inquiry_custom_fields');
add_action('woocommerce_process_product_meta', 'woocommerce_product_inquiry_custom_fields_save');
function woocommerce_product_inquiry_custom_fields()
{   
	global $woocommerce, $post;    
	echo '<div class="woocommerce_options_panel options_group">';  	
	$field = array(
		'id' => '_tni_model',
		'label' => __( 'Model', 'textdomain' ),
		'value'   => get_post_meta( get_the_ID(), '_tni_model', true ),			
	);
	woocommerce_wp_text_input( $field );
	
	$field = array(
		'id' => '_supplier_name',
		'label' => __( 'Supplier Name', 'textdomain' ),
		'value'   => get_post_meta( get_the_ID(), '_supplier_name', true ),			
	);
	woocommerce_wp_text_input( $field );
	
	$field = array(
		'id' => '_supplier_price',
		'label' => __( 'Supplier Price', 'textdomain' ),
		'value'   => get_post_meta( get_the_ID(), '_supplier_price', true ),			
	);
	woocommerce_wp_text_input( $field );
	
	
	
			
	echo '</div>';
	echo '<div style = "clear:both"></div>'; 	
}	
function woocommerce_product_inquiry_custom_fields_save($post_id)
{   
	$woocommerce_custom_procut_tni_model = $_POST['_tni_model'];  
	update_post_meta($post_id, '_tni_model', esc_html($woocommerce_custom_procut_tni_model));
	
	$woocommerce_custom_procut_supplier_name = $_POST['_supplier_name'];  
	update_post_meta($post_id, '_supplier_name', esc_html($woocommerce_custom_procut_supplier_name));	
	$woocommerce_custom_procut_supplier_price = $_POST['_supplier_price'];  
	update_post_meta($post_id, '_supplier_price', esc_html($woocommerce_custom_procut_supplier_price));
	
}








add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );
function woo_remove_product_tabs( $tabs ) {
    unset( $tabs['description'] );    
    return $tabs;
}


remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
add_action( 'woocommerce_after_single_product_summary','ni_single_product_descrition_features',7);
if ( ! function_exists( 'ni_single_product_descrition_features' ) ) {
	function ni_single_product_descrition_features() {
	?>		
		<div class = "tniproduct_des_fea">
			<div class = "tniproductcont">
				<div class = "tniproductdes tniproductfeasection">
					<h5 class="desc-ttl"> Description </h5>
					<div class = "tniproductdescontent">
						<?php the_content(); ?>
						<?php
						if(get_post_meta( get_the_ID(),  'prod_video', true )) : ?>
						<div class = "tniproductvideo">
							<div class = "tnivideoshocase">
								<iframe width="560" height="315" src="https://www.youtube.com/embed/<?php echo get_post_meta( get_the_ID(),'prod_video', true ); ?>" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
							</div>
						</div>	
						<?php endif; ?>
					</div>	
				</div>
				<div class = "tniproductfea tniproductfeasection">
					<h5 class="feature-ttl"> Feature </h5>
					<div class="additional-attributes-wrapper table-wrapper features">
						<ul>
							<?php 
							for($i=1;$i<=15;$i++)
							{
								$f = get_post_meta( get_the_ID(),  'features_'.$i, true );
								if($f !=''): ?>
								<li> <?php echo $f; ?> </li>
							<?php endif; } ?>
						</ul>
					</div>
					<h5 class="speci-ttl"> Specification </h5>
					<div class="additional-attributes-wrapper table-wrapper features specification">
						<ul>
							<?php 
							for($i=1;$i<=20;$i++)
							{
								$f = get_post_meta( get_the_ID(),  'specification_'.$i, true );
								if($f !=''): ?>
								<li> <?php echo $f; ?> </li>
							<?php endif; } ?>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<div style = "clear:both; margin-bottom:0"></div>
		<?php
	}
} 

/* add term in product summary */

add_action( 'woocommerce_single_product_summary', 'my_term_offer', 20 );
function my_term_offer(){
    global $product;

    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 40 );
    add_action( 'woocommerce_single_product_summary', 'custom_offer', 50 );
}

function custom_offer(){
    global $post, $product;
	
    ?>
    <div class="offersandpaymenttersimage" style="float: left;margin-top:0px">
		<ul class="tnishippingtermandoffersul">
			<li class="tnitermborder-bottom tnitermborder-right">
				<div class="tnistermimage">
					<img src="https://www.packsouq.com/wp-content/uploads/2022/01/Get-online-quote.png" alt="" style="max-width: 35px;"/>
				</div>
				<div class="tnistermcontent">
					<h4>Get Online Quote</h4>
				</div>
			</li>
			<li class="tnitermborder-bottom">
				<div class="tnistermimage">
					<img src="https://www.packsouq.com/wp-content/uploads/2022/01/Free-design-support.png" alt="" style="max-width: 35px;" />
				</div>
				<div class="tnistermcontent">
					<h4>Free Design Support</h4>
				</div>
			</li>
			<li class="tnitermborder-right">
				<div class="tnistermimage">
					<img src="https://www.packsouq.com/wp-content/uploads/2022/01/Request-now.png" alt="" style="max-width: 35px;" />
				</div>
				<div class="tnistermcontent">
					<h4>Request Now</h4>
				</div>
			</li>
			<li>
				<div class="tnistermimage">
					<img src="https://www.packsouq.com/wp-content/uploads/2022/01/Custom-size.png" alt="" style="max-width:35px;" />
				</div>
				<div class="tnistermcontent">
					<h4>Custom Size</h4>
				</div>
			</li>
			</ul>
</div>

    <?php
}


add_action( 'woocommerce_check_cart_items', 'required_min_cart_subtotal_amount',15 );
function required_min_cart_subtotal_amount() {

    $minimum_amount = 100;
    $cart_subtotal = WC()->cart->subtotal;

     if( $cart_subtotal < $minimum_amount  ) {
        
		// Display an error message

        wc_add_notice( '<strong>' . sprintf( __("A minimum total purchase amount of %s is required to checkout."), wc_price($minimum_amount) ) . '<strong>', 'error' );
    } 
}



/* add term in product summary */



//remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );

function woocommerce_add_custom_text_after_product_title()
{
   global $post, $product;	
    ?>
    <div class="tni_custom_attr_filed tni_custom_attr_filed">
		<ul class="tni_custom_attr_ul">
			<?php if(get_post_meta( $product->get_id(), '_tni_model', true )) : ?>				
			<li>
				<span class="tni_custom_att_label">
					Model:
				</span>
				<span class="tni_custom_att_value sku">
					<?php echo get_post_meta( $product->get_id(), '_tni_model', true ); ?>
				</span>
			</li>
			<?php endif; ?>
			<li>
				<span class="tni_custom_att_label ">
					SKU: 
				</span>
				<span class="tni_custom_att_value sku">
					<?php echo $product->get_sku(); ?>
				</span>
			</li>
		</ul>
	</div>
	<style>
	.product_meta .sku_wrapper {
		display: none !important;
	}
	.tni_custom_attr_filed ul {
		margin-bottom: 0;
	}
	.tni_custom_attr_ul {
		display: flex;
		list-style: none;
	}
	.tni_custom_attr_ul li {
		margin-left:0 !important;
		margin-right:15px;
		
	}
	.tni_custom_att_label {
		font-weight:bold;
		margin-left:0 !important;
		font-size:.8em;
	}
	.tni_custom_att_value {
		font-size:.8em;
	}
	.product-title.product_title.entry-title {
		margin-bottom:0px;
	}
	</style>
    <?php
}
add_action( 'woocommerce_single_product_summary', 'woocommerce_add_custom_text_after_product_title', 5);


add_filter( 'woocommerce_states', 'custom_woocommerce_states' );
function custom_woocommerce_states( $states ) {
  $states['AE'] = array(
    'du' => 'Dubai',
    'ad' => 'Abu Dhabi',
    'al' => 'Alain',
	'fu' => 'Fujairah',
    'rk' => 'Ras Al Khaimah',
	'sj' => 'Sharjah'
  ); 
  return $states;
}
