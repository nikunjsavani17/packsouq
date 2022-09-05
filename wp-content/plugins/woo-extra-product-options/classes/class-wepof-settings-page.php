<?php
/**
 * Woo Extra Product Options Setting Page
 *
 * @author   ThemeHiGH
 * @category Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Settings_Page')) :
abstract class WEPOF_Settings_Page extends WEPOF_Product_Options_Utils{
	protected $page_id = '';		
	protected $tabs = '';

	public function __construct() {
		$this->tabs = array( 'fields' => 'Manage Product Options');
	}

	public function get_tabs(){
		return $this->tabs;
	}
	
	public function get_current_tab(){
		return $this->page_id;
	}
		
	public function output_tabs(){
		$current_tab = $this->get_current_tab();
		$tabs = $this->get_tabs();

		if(empty($tabs)){
			return;
		}
		
		echo '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';
		foreach( $tabs as $id => $label ){
			$active = ($current_tab == $id) ? 'nav-tab-active' : '';
			$label = $this->__wcpf($label);
			echo '<a class="nav-tab '.$active.'" href="'.admin_url('edit.php?post_type=product&page=thwepof_extra_product_options&tab='.$id).'">'.$label.'</a>';
		}
		echo '</h2>';
		
		//$this->output_premium_version_notice();	
	}	
	
	public function output_premium_version_notice(){
		?>		<?php 
        /* <div id="message" class="wc-connect updated thwcfd-notice">
            <div class="squeezer">
            	<table>
                	<tr>
                    	<td width="70%">
                        	<p><strong><i>WooCommerce Extra Product Options Pro</i></strong> premium version provides more features to customise product page.</p>
                            <ul>
                            	<li>14 field types available,<br/>(<i>Text, Hidden, Password, Textarea, Radio, Checkbox, Checkbox Group, Select, Multi-select, Date Picker, Time Picker, File Upload Heading, Label</i>).</li>
                                <li>Option to add price fields.</li>
                                <li>Option to display fields based on different rules.</li>
                                <li>Option to set RegEx based custom validations.</li>
                                <li>Option to add custom fields in different sections and positions.</li>
                            </ul>
                        </td>
                        <td>
                        	<a target="_blank" href="https://www.themehigh.com/product/woocommerce-extra-product-options/" class="">
                            	<img src="<?php echo plugins_url( '../assets/css/upgrade-btn.png', __FILE__ ); ?>" />
                            </a>
                        </td>
                    </tr>
                </table>
            </div>
        </div> */  ?>
        <?php
	}
}
endif;