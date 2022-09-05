<?php
/**
 * Woo Extra Product Options - Field Editor
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Options_Settings')):
class WEPOF_Product_Options_Settings extends WEPOF_Settings_Page {
	protected static $_instance = null;
	private $field_props = array();

	public function __construct() {
		parent::__construct();
		$this->page_id = 'fields';
		$this->field_props = $this->get_field_form_props();

		add_filter( 'woocommerce_attribute_label', array($this, 'woo_attribute_label'), 10, 2 );
		
		add_filter('thwepof_load_products', array($this, 'load_products'));
		add_filter('thwepof_load_products_cat', array($this, 'load_products_cat'));
	}

	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}	
	
	/*public function load_products(){
		$args = array( 'post_type' => 'product', 'order' => 'ASC', 'posts_per_page' => -1 );
		$products = get_posts( $args );
		$productsList = array();
		
		if(count($products) > 0){
			foreach($products as $product){				
				$productsList[] = array("id" => $product->ID, "title" => $product->post_title);
			}
		}		
		return $productsList;
	}*/
	public function load_products(){
		$args = array( 'post_type' => 'product', 'order' => 'ASC', 'posts_per_page' => -1, 'fields' => 'ids' );
		if(!apply_filters("thwepof_conditions_show_only_active_products", true)){
			$args['post_status'] = 'any';
		}
		$products = get_posts( $args );
		$productsList = array();
		
		if(count($products) > 0){
			foreach($products as $pid){				
				//$productsList[] = array("id" => $product->ID, "title" => $product->post_title);
				$productsList[] = array("id" => $pid, "title" => get_the_title($pid));
			}
		}		
		return $productsList;
	}
	
	public function load_products_cat(){
		$product_cat = array();
		$pcat_terms = get_terms('product_cat', 'orderby=count&hide_empty=0');
		
		foreach($pcat_terms as $pterm){
			$product_cat[] = array("id" => $pterm->slug, "title" => $pterm->name);
		}		
		return $product_cat;
	}	
	
	public function get_field_types(){
		return array(
			'inputtext' => 'Text', 'hidden' => 'Hidden', 'number' => 'Number', 'tel' => 'Telephone', 'password' => 'Password', 
			'textarea' => 'Textarea', 'select' => 'Select', 'checkbox' => 'Checkbox', 'radio' => 'Radio'
		);
	}

	public function get_available_positions(){
		return array(
			'woo_before_add_to_cart_button'	=> 'Before Add To Cart Button',
			'woo_after_add_to_cart_button' 	=> 'After Add To Cart Button',
		);
	}

	public function get_field_form_props(){
		$field_types = $this->get_field_types();
		
		$validators = array(
			'email' => 'Email',
			'number' => 'Number',
		);

		$positions = $this->get_available_positions();
		
		$title_positions = array(
			'left' => 'Left of the field',
			'above' => 'Above field',
		);
		
		return array(
			'name' 		  => array('type'=>'text', 'name'=>'name', 'label'=>'Name', 'required'=>1),
			'type' 		  => array('type'=>'select', 'name'=>'type', 'label'=>'Field Type', 'required'=>1, 'options'=>$field_types, 
								'onchange'=>'thwepoFieldTypeChangeListner(this)'),
			'value' 	  => array('type'=>'text', 'name'=>'value', 'label'=>'Default Value'),
			'placeholder' => array('type'=>'text', 'name'=>'placeholder', 'label'=>'Placeholder'),
			'validator'   => array('type'=>'multiselect', 'name'=>'validator', 'label'=>'Validations', 'placeholder'=>'Select validations', 'options'=>$validators),
			'cssclass'    => array('type'=>'text', 'name'=>'cssclass', 'label'=>'Wrapper Class', 'placeholder'=>'Seperate classes with comma'),
			'input_class'    => array('type'=>'text', 'name'=>'input_class', 'label'=>'Input Class', 'placeholder'=>'Seperate classes with comma'),
			'position' 	  => array('type'=>'select', 'name'=>'position', 'label'=>'Position', 'options'=>$positions),
			
			'minlength'   => array('type'=>'text', 'name'=>'minlength', 'label'=>'Min. Length', 'hint_text'=>'The minimum number of characters allowed'),
			'maxlength'   => array('type'=>'text', 'name'=>'maxlength', 'label'=>'Max. Length', 'hint_text'=>'The maximum number of characters allowed'),

			'cols' => array('type'=>'text', 'name'=>'cols', 'label'=>'Cols', 'hint_text'=>'The visible width of a text area'),
			'rows' => array('type'=>'text', 'name'=>'rows', 'label'=>'Rows', 'hint_text'=>'The visible height of a text area'),
			
			'checked'  => array('type'=>'checkbox', 'name'=>'checked', 'label'=>'Checked by default', 'value'=>'yes', 'checked'=>0),
			'required' => array('type'=>'checkbox', 'name'=>'required', 'label'=>'Required', 'value'=>'yes', 'checked'=>0, 'status'=>1),
			'enabled'  => array('type'=>'checkbox', 'name'=>'enabled', 'label'=>'Enabled', 'value'=>'yes', 'checked'=>1, 'status'=>1),
			
			'title'          => array('type'=>'text', 'name'=>'title', 'label'=>'Title'),
			'title_position' => array('type'=>'select', 'name'=>'title_position', 'label'=>'Title Position', 'options'=>$title_positions),
			'title_class'    => array('type'=>'text', 'name'=>'title_class', 'label'=>'Title Class', 'placeholder'=>'Seperate classes with comma'),
		);
	}

	private function sort_field_set($fieldset){
		foreach($fieldset as $hook => &$hooked_fields){
			uasort( $hooked_fields, array( $this, 'sort_fields_by_order' ) );
		}
		return $fieldset;
	}

	public function sort_fields_by_order($a, $b){
	    if($a->get_property('order') == $b->get_property('order')){
	        return 0;
	    }
	    return ($a->get_property('order') < $b->get_property('order')) ? -1 : 1;
	}
	
   /*---------------------------------------
	*----- PRODUCT FIELDS FORMS - START ----
	*---------------------------------------*/
	private function output_add_field_form_pp(){
		?>
        <div id="thwepof_new_field_form_pp" title="New Product Field" class="thwepof_popup_wrapper">
          <?php $this->output_popup_form_fields('new'); ?>
        </div>
        <?php
	}

	private function output_edit_field_form_pp(){		
		?>
        <div id="thwepof_edit_field_form_pp" title="Edit Product Field" class="thwepof_popup_wrapper">
          <?php $this->output_popup_form_fields('edit'); ?>
        </div>
        <?php
	}
   /*---------------------------------------
	*----- PRODUCT FIELDS FORMS - END ------
	*---------------------------------------*/

	public function output_page(){
		$this->output_tabs();
		$this->output_content();
	}

	private function output_fields_table_heading(){
		?>
		<th class="sort"></th>
		<th class="check-column"><input type="checkbox" style="margin:0px 4px -1px -1px;" onclick="thwepofSelectAllProductFields(this)"/></th>
		<th class="name"><?php $this->_ewcpf('Name'); ?></th>
		<th class="id"><?php $this->_ewcpf('Type'); ?></th>
		<th><?php $this->_ewcpf('Label'); ?></th>
		<th><?php $this->_ewcpf('Placeholder'); ?></th>
		<th><?php $this->_ewcpf('Validation Rules'); ?></th>
        <th class="status"><?php $this->_ewcpf('Required'); ?></th>
		<th class="status"><?php $this->_ewcpf('Enabled'); ?></th>	
        <th class="status"><?php $this->_ewcpf('Edit'); ?></th>	
        <?php
	}

	private function output_actions_row(){
		?>
        <th colspan="5">
            <button type="button" onclick="thwepofOpenNewFieldForm()" class="button button-primary"><?php $this->_ewcpf('+ Add field'); ?></button>
            <button type="button" onclick="thwepofRemoveSelectedFields()" class="button"><?php  $this->_ewcpf('Remove'); ?></button>
            <button type="button" onclick="thwepofEnableSelectedFields()" class="button"><?php  $this->_ewcpf('Enable'); ?></button>
            <button type="button" onclick="thwepofDisableSelectedFields()" class="button"><?php $this->_ewcpf('Disable'); ?></button>
        </th>
        <th colspan="5">
        	<input type="submit" name="save_fields" class="button-primary" value="<?php $this->_ewcpf('Save changes') ?>" style="float:right" />
            <input type="submit" name="reset_fields" class="button" value="<?php $this->_ewcpf('Reset to default options') ?>" style="float:right; margin-right: 5px;" 
			onclick="return confirm('Are you sure you want to reset to default fields? all your changes will be deleted.');"/>
        </th>  
    	<?php 
	}

    private function output_content(){
    	$action = isset($_POST['i_action']) ? $_POST['i_action'] : false;
    	$fields_hook_map = $this->get_product_custom_fields_hook_map();

    	if($action === 'new')
			echo $this->save_or_update_field($fields_hook_map, $action);	
			
		if($action === 'edit')
			echo $this->save_or_update_field($fields_hook_map, $action);

		if(isset($_POST['save_fields']))
			echo $this->save_options($fields_hook_map);

		if(isset($_POST['reset_fields']))
			echo $this->reset_to_default();	

		$fields_hook_map = $this->get_product_custom_fields_hook_map();
		$custom_fields = $this->get_product_custom_fields_full();
		?> 

        <div class="wrap woocommerce"><div class="icon32 icon32-attributes" id="icon-woocommerce"><br/></div>                
		    <form method="post" id="thwepof_product_fields_form" action="">
            <table id="thwepof_product_fields" class="wc_gateways widefat thpladmin_fields_table" cellspacing="0">
                <thead>
                    <tr><?php $this->output_actions_row(); ?></tr>
                    <tr><?php $this->output_fields_table_heading(); ?></tr>						
                </thead>
                <tfoot>
                    <tr><?php $this->output_fields_table_heading(); ?></tr>
                    <tr><?php $this->output_actions_row(); ?></tr>
                </tfoot>

                <tbody class="ui-sortable">
                <?php 
				//$custom_fields = $this->get_product_custom_fields_full();
				if($custom_fields && is_array($custom_fields)){
					$i=0;												
					foreach( $custom_fields as $field_name => $field ) :	
						$name = $field->get_property('name');
						$is_checked = $field->get_property('checked') ? 1 : 0; 		
						$is_required = $field->is_required() ? 1 : 0; 
						$is_enabled = $field->is_enabled() ? 1 : 0;                
					?>
						<tr class="row_<?php echo $i; echo($is_enabled == 1 ? '' : ' thwepof-disabled') ?>">
							<td width="1%" class="sort ui-sortable-handle">
								<input type="hidden" name="f_order[<?php echo $i; ?>]" class="f_order" value="<?php echo $i; ?>" />

								<input type="hidden" name="f_name[<?php echo $i; ?>]" class="f_name" value="<?php echo esc_attr($name); ?>" />                            
								<input type="hidden" name="f_type[<?php echo $i; ?>]" class="f_type" value="<?php echo $field->get_property('type'); ?>" />
                                <input type="hidden" name="f_position[<?php echo $i; ?>]" class="f_position" value="<?php echo $field->get_property('position'); ?>" />

								<input type="hidden" name="f_value[<?php echo $i; ?>]" class="f_value" value="<?php echo $field->get_property('value'); ?>" />
								<input type="hidden" name="f_placeholder[<?php echo $i; ?>]" class="f_placeholder" value="<?php echo $field->get_property('placeholder'); ?>" />
								<input type="hidden" name="f_options[<?php echo $i; ?>]" class="f_options" value="<?php echo $field->get_options_str(); ?>" />

								<input type="hidden" name="f_validator[<?php echo $i; ?>]" class="f_validator" value="<?php echo $field->get_property('validator'); ?>" /> 
								<input type="hidden" name="f_cssclass[<?php echo $i; ?>]" class="f_cssclass" value="<?php echo $field->get_property('cssclass'); ?>" /> 

								<input type="hidden" name="f_title[<?php echo $i; ?>]" class="f_title" value="<?php echo $field->get_property('title'); ?>" />
								<input type="hidden" name="f_title_class[<?php echo $i; ?>]" class="f_title_class" value="<?php echo $field->get_property('title_class'); ?>" />
                                <input type="hidden" name="f_title_position[<?php echo $i; ?>]" class="f_title_position" value="<?php echo $field->get_property('title_position'); ?>" />

                                <input type="hidden" name="f_cols[<?php echo $i; ?>]" class="f_cols" value="<?php echo $field->get_property('cols'); ?>" />
                                <input type="hidden" name="f_rows[<?php echo $i; ?>]" class="f_rows" value="<?php echo $field->get_property('rows'); ?>" />

                                <input type="hidden" name="f_checked[<?php echo $i; ?>]" class="f_checked" value="<?php echo $is_checked; ?>" />
								<input type="hidden" name="f_required[<?php echo $i; ?>]" class="f_required" value="<?php echo $is_required; ?>" />
								<input type="hidden" name="f_enabled[<?php echo $i; ?>]" class="f_enabled" value="<?php echo $is_enabled; ?>" />
								<input type="hidden" name="f_deleted[<?php echo $i; ?>]" class="f_deleted" value="0" />
                                
                                <input type="hidden" name="f_rules[<?php echo $i; ?>]" class="f_rules" value="<?php echo htmlspecialchars($field->get_conditional_rules_json()); ?>" />
							</td>
							<td class="td_select"><input type="checkbox" name="select_field"/></td>
							<td class="td_name"><?php echo esc_attr($name); ?></td>
							<td class="td_type"><?php $this->_ewcpf($field->get_property('type')); ?></td>
							<td class="td_title"><?php $this->_ewcpf($field->get_property('title')); ?></td>
							<td class="td_placeholder"><?php $this->_ewcpf($field->get_property('placeholder')); ?></td>
							<td class="td_validate"><?php echo $field->get_property('validator'); ?></td>
							<td class="td_required status">
								<?php echo($is_required == 1 ? '<span class="dashicons dashicons-yes tips" data-tip="'.$this->__wcpf('Yes').'"></span>' : '-' ) ?>
							</td>
							<td class="td_enabled status">
								<?php echo($is_enabled == 1 ? '<span class="dashicons dashicons-yes tips" data-tip="'.$this->__wcpf('Yes').'"></span>' : '-' ) ?>
							</td>
							<td class="td_edit" align="center">
								<button type="button" class="f_edit_btn" <?php echo($is_enabled == 1 ? '' : 'disabled') ?> 
								onclick="thwepofOpenEditFieldForm(this, <?php echo $i; ?>)"><?php $this->_ewcpf('Edit'); ?></button>
							</td>
						</tr>						
                <?php 
					$i++; 
					endforeach; 
				}else{
					echo '<tr><td colspan="10" align="center" class="empty-msg-row">'.__("No custom fields found. Click on <b>Add field</b> button to create new fields.", "woo-extra-product-options").'</td></tr>';
				}
				?>
                </tbody>
            </table> 
            </form>
            <?php
            $this->output_add_field_form_pp();
			$this->output_edit_field_form_pp();
			$this->output_popup_form_fragments();
			?>
    	</div>
    <?php
    }

    private function save_or_update_field($fieldset, $action) {
		try {
			$field = WEPOF_Utils_Field::prepare_field_from_posted_data($_POST, $this->field_props);
			
			if($action === 'edit'){
				$fieldset = $this->update_field($fieldset, $field);
			}else{
				$fieldset = $this->add_field($fieldset, $field);
			}
			
			$result = $this->update_fields($fieldset);

			if($result == true){
				echo '<div class="updated"><p>'. $this->__wcpf('Your changes were saved.') .'</p></div>';
			} else {
				echo '<div class="error"><p>'. $this->__wcpf('Your changes were not saved due to an error (or you made none!).') .'</p></div>';
			}
		} catch (Exception $e) {
			echo '<div class="error"><p>'. $this->__wcpf('Your changes were not saved due to an error.') .'</p></div>';
		}
	}

	public function add_field($fieldset, $field){
		$fieldset = is_array($fieldset) ? $fieldset : array();

		if(WEPOF_Utils_Field::is_valid_field($field)){
			$position = $field->get_property('position');

			if(!array_key_exists($position, $fieldset) || !is_array($fieldset[$position])){						
				$fieldset[$position] = array();
			}

			$size = sizeof($fieldset[$position]);
			$field->set_property('order', $size);

			$fieldset[$position][$field->get_property('name')]= $field;

			return $fieldset;
		}else{
			throw new Exception('Invalid Field Object.');
		}
	}

	public function update_field($fieldset, $field){
		if(WEPOF_Utils_Field::is_valid_field($field)){
			$name = $field->get_property('name');
			$position = $field->get_property('position');
			$name_old = $field->get_property('name_old');
			$position_old = $field->get_property('position_old');
			
			if(is_array($fieldset) && isset($fieldset[$position_old]) && isset($fieldset[$position_old][$name_old])){
				$o_field = $fieldset[$position_old][$name_old];
				
				unset($fieldset[$position_old][$name_old]);

				if(!array_key_exists($position, $fieldset) || !is_array($fieldset[$position])){						
					$fieldset[$position] = array();
				}

				if($position === $position_old){
					$field->set_property('order', $o_field->get_property('order'));
				}else{
					$size = sizeof($fieldset[$position]);
					$field->set_property('order', $size);
				}

				$fieldset[$position][$name]= $field;

				$fieldset = $this->sort_field_set($fieldset);
			}
			return $fieldset;
		}else{
			throw new Exception('Invalid Field Object.');
		}
	}	

	private function save_options($fieldset) {	
		$f_order = ! empty( $_POST['f_order'] ) ? $_POST['f_order'] : array();		
		if(empty($f_order)){
			echo '<div class="error"><p> '. $this->__wcpf('Your changes were not saved due to no fields found.') .'</p></div>';
			return;
		}

		$f_names = !empty( $_POST['f_name'] ) ? $_POST['f_name'] : array();
		$f_position = !empty( $_POST['f_position'] ) ? $_POST['f_position'] : array();
		$f_enabled  = !empty( $_POST['f_enabled'] ) ? $_POST['f_enabled'] : array();
		$f_deleted  = !empty( $_POST['f_deleted'] ) ? $_POST['f_deleted'] : array();

		$max = max( array_map( 'absint', array_keys( $f_names ) ) );	
		for($i = 0; $i <= $max; $i++) {
			$name = isset($f_names[$i]) ? urldecode(sanitize_title( wc_clean(stripslashes($f_names[$i])) )) : '';
			$position = isset($f_names[$i]) ? trim(stripslashes($f_position[$i])) : '';

			if(isset($f_deleted[$i]) && $f_deleted[$i] == 1){
				unset($fieldset[$position][$name]);
			}else{
				$order = isset($f_order[$i]) ? trim(stripslashes($f_order[$i])) : 0;
				$enabled = isset($f_enabled[$i]) ? $f_enabled[$i] : 1;

				$field = $fieldset[$position][$name];
				$field->set_property('order', $order);
				$field->set_property('enabled', $enabled);
				$fieldset[$position][$name] = $field;
			}
		}	

		$fieldset = $this->sort_field_set($fieldset);

		$result = $this->update_fields($fieldset);
		if($result == true){
			echo '<div class="updated"><p>'. $this->__wcpf('Your changes were saved.') .'</p></div>';
		} else {
			echo '<div class="error"><p>'. $this->__wcpf('Your changes were not saved due to an error (or you made none!).') .'</p></div>';
		}
	}

	/*private function save_options_old() {	
		$f_order = ! empty( $_POST['f_order'] ) ? $_POST['f_order'] : array();		
		if(empty($f_order)){
			echo '<div class="error"><p> '. $this->__wcpf('Your changes were not saved due to no fields found.') .'</p></div>';
			return;
		}

		$f_names = !empty( $_POST['f_name'] ) ? $_POST['f_name'] : array();
		$f_types = !empty( $_POST['f_type'] ) ? $_POST['f_type'] : array();

		$f_values       = !empty( $_POST['f_value'] ) ? $_POST['f_value'] : array();		
		$f_placeholders = !empty( $_POST['f_placeholder'] ) ? $_POST['f_placeholder'] : array();
		$f_options = !empty( $_POST['f_options'] ) ? $_POST['f_options'] : array();

		$f_validators = !empty( $_POST['f_validator'] ) ? $_POST['f_validator'] : array();
		$f_cssclasses = !empty( $_POST['f_cssclass'] ) ? $_POST['f_cssclass'] : array();

		$f_titles        = !empty( $_POST['f_title'] ) ? $_POST['f_title'] : array();		
		$f_title_classes = !empty( $_POST['f_title_class'] ) ? $_POST['f_title_class'] : array();
		$f_title_position = !empty( $_POST['f_title_position'] ) ? $_POST['f_title_position'] : array();
				
		$f_required = !empty( $_POST['f_required'] ) ? $_POST['f_required'] : array();
		$f_enabled  = !empty( $_POST['f_enabled'] ) ? $_POST['f_enabled'] : array();
		$f_deleted  = !empty( $_POST['f_deleted'] ) ? $_POST['f_deleted'] : array();

		$f_position = !empty( $_POST['f_position'] ) ? $_POST['f_position'] : array();
		$f_rules = !empty( $_POST['f_rules'] ) ? $_POST['f_rules'] : array();

		$custom_fields = array();

		$max = max( array_map( 'absint', array_keys( $f_names ) ) );	
		for($i = 0; $i <= $max; $i++) {
			if(isset($f_deleted[$i]) && $f_deleted[$i] == 1){
				continue;
			}

			$name = isset($f_names[$i]) ? urldecode(sanitize_title( wc_clean(stripslashes($f_names[$i])) )) : '';
			$type = isset($f_types[$i]) ? trim(stripslashes($f_types[$i])) : 'inputtext';

			$field = false;
			if($type === 'inputtext'){
				$field = new WEPOF_Product_Field_InputText();
				$field->set_property('placeholder', isset($f_placeholders[$i]) ? trim(stripslashes($f_placeholders[$i])) : '');
			}else if($type === 'select'){
				$field = new WEPOF_Product_Field_Select();
				$field->set_options_str(isset($f_options[$i]) ? trim(stripslashes($f_options[$i])) : '');
			}

			$field->set_property('id', $name);
			$field->set_property('name', $name);
			$field->set_property('order', isset($f_order[$i]) ? trim(stripslashes($f_order[$i])) : 0);
			$field->set_property('value', isset($f_values[$i]) ? trim(stripslashes($f_values[$i])) : '');

			$field->set_property('validator', isset($f_validators[$i]) ? trim(stripslashes($f_validators[$i])) : '');
			$field->set_property('cssclass', isset($f_cssclasses[$i]) ? trim(stripslashes($f_cssclasses[$i])) : '');

			$field->set_property('title', isset($f_titles[$i]) ? trim(stripslashes($f_titles[$i])) : '');				
			$field->set_property('title_class', isset($f_title_classes[$i]) ? trim(stripslashes($f_title_classes[$i])) : '');
			$field->set_property('title_position', isset($f_title_position[$i]) ? trim(stripslashes($f_title_position[$i])) : '');	

			$field->set_property('required', isset($f_required[$i]) ? $f_required[$i] : 0);
			$field->set_property('enabled', isset($f_enabled[$i]) ? $f_enabled[$i] : 1);

			$field->set_property('position', isset($f_position[$i]) ? $f_position[$i] : 'woo_before_add_to_cart_button');
			
			$field->set_conditional_rules_json(isset($f_rules[$i]) ? trim(stripslashes($f_rules[$i])) : '');
			$field->set_conditional_rules($this->prepare_conditional_rules($field->get_conditional_rules_json()));
			
			$field->prepare_properties();
			
			if(!array_key_exists($field->get_property('position'), $custom_fields) || !is_array($custom_fields[$field->get_property('position')])){						
				$custom_fields[$field->get_property('position')] = array();
			}			
			$custom_fields[$field->get_property('position')][$name]= $field;
		}	

		foreach($custom_fields as $hook => &$hooked_fields){
			uasort( $hooked_fields, array( $this, 'sort_fields_by_order' ) );
		}

		$result = $this->update_fields($custom_fields);
		if($result == true){
			echo '<div class="updated"><p>'. $this->__wcpf('Your changes were saved.') .'</p></div>';
		} else {
			echo '<div class="error"><p>'. $this->__wcpf('Your changes were not saved due to an error (or you made none!).') .'</p></div>';
		}
	}
	
	public function prepare_conditional_rules($conditional_rules){
		$condition_rule_sets = array();	
		if(!empty($conditional_rules)){
			$rule_sets = json_decode($conditional_rules, true);
				
			if(is_array($rule_sets)){
				foreach($rule_sets as $rule_set){
					if(is_array($rule_set)){
						$condition_rule_set_obj = new WEPOF_Condition_Rule_Set();
						$condition_rule_set_obj->set_logic('and');
												
						foreach($rule_set as $condition_sets){
							if(is_array($condition_sets)){
								$condition_rule_obj = new WEPOF_Condition_Rule();
								$condition_rule_obj->set_logic('or');
														
								foreach($condition_sets as $condition_set){
									if(is_array($condition_set)){
										$condition_set_obj = new WEPOF_Condition_Set();
										$condition_set_obj->set_logic('and');
													
										foreach($condition_set as $condition){
											if(is_array($condition)){
												$condition_obj = new WEPOF_Condition();
												$condition_obj->set_subject(isset($condition['subject']) ? $condition['subject'] : '');
												$condition_obj->set_comparison(isset($condition['comparison']) ? $condition['comparison'] : '');
												$condition_obj->set_value(isset($condition['cvalue']) ? $condition['cvalue'] : '');
												
												$condition_set_obj->add_condition($condition_obj);
											}
										}										
										$condition_rule_obj->add_condition_set($condition_set_obj);	
									}								
								}
								$condition_rule_set_obj->add_condition_rule($condition_rule_obj);
							}
						}
						$condition_rule_sets[] = $condition_rule_set_obj;
					}
				}	
			}
		}
		return $condition_rule_sets;
	}*/

	public function reset_to_default() {
		delete_option('thwepof_custom_product_fields');
		echo '<div class="updated"><p>'. $this->__wcpf('Product fields successfully reset') .'</p></div>';
	}

	public function update_fields($custom_fields){
	 	if(is_array($custom_fields)){	
			$result = update_option('thwepof_custom_product_fields', $custom_fields);
			return $result;
		}
		return false;
	}	 
	
	public function woo_attribute_label( $label, $key ) {
		if(!empty($label)){
			$options_extra = $this->get_product_custom_fields_full();
			if($options_extra){
				if(array_key_exists($label, $options_extra)) {
					$label = $options_extra[$label]->get_property('title');
				}
			}
		}
		return $label;
	}

   /*******************************************
	*-------- HTML FORM FRAGMENTS - START -----
	*******************************************/
	private function output_popup_form_fields($form_type){
		?>
		<form method="post" id="thwepof_<?php echo $form_type ?>_field_form" action="">
			<input type="hidden" name="i_action" value="<?php echo $form_type ?>" />
        	<div id="thwepof_field_editor_form_<?php echo $form_type ?>">
        		<input type="hidden" name="i_name_old" value="" />
        		<input type="hidden" name="i_position_old" value="" />
        		<input type="hidden" name="i_rules" value="" />
				<?php $this->render_field_form_fragment_general($form_type); ?>
                <table class="thwepof_field_form_placeholder" width="100%"></table>
                
                <h4 style="margin: 15px 0 0 0; color:#5c5c5c;">Field Display Rules</h4>
                <table id="thwepo-tab-rules_<?php echo $form_type ?>" width="100%" style="border-top: 1px dashed #a1a1a1; margin-top: 0px;">
                <?php $this->render_field_form_fragment_rules($form_type); ?>
                </table>
            </div>
        </form>
        <?php
	}
	
	private function output_popup_form_fragments(){
		$this->render_field_form_inputtext();
		$this->render_field_form_hidden();
		$this->render_field_form_number();
		$this->render_field_form_tel();
		$this->render_field_form_password();
		$this->render_field_form_textarea();
		$this->render_field_form_select();
		$this->render_field_form_checkbox();
		$this->render_field_form_radio();
		//$this->render_field_form_color();
		//$this->render_field_form_date();
		
		$this->render_field_form_fragment_product_list();
		$this->render_field_form_fragment_category_list();
	}		

	private function render_field_form_inputtext(){
		?>
        <table id="thwepof_field_form_id_inputtext" class="thwepof_field_form_table" width="100%" style="display:none;">
            <tr>
            	<?php
            	$this->render_field_form_fragment_title();
            	$this->render_field_form_fragment_title_class();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_value();
            	$this->render_field_form_fragment_placeholder();
				?>
            </tr>
            <tr>
            	<?php				
            	$this->render_field_form_fragment_class();
				$this->render_field_form_fragment_validate();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_position();
            	$this->render_field_form_fragment_title_position();
				?>
            </tr>
            <?php $this->render_field_form_fragment_h_spacing(); ?>
            <tr>
            	<td>&nbsp;</td>
            	<td colspan="3">
            	<?php
            	$this->render_field_form_fragment_required();
				$this->render_field_form_fragment_enabled();
				?>
                </td>
            </tr>     
        </table>
        <?php   
	}

	private function render_field_form_hidden(){
		?>
        <table id="thwepof_field_form_id_hidden" class="thwepof_field_form_table" width="100%" style="display:none;">
            <tr>
            	<?php
            	$this->render_field_form_fragment_title();
            	$this->render_field_form_fragment_value();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_class();
            	$this->render_field_form_fragment_empty_cell();
				?>
            </tr>
            <?php $this->render_field_form_fragment_h_spacing(); ?>
            <tr>
            	<td>&nbsp;</td>
            	<td colspan="3">
            	<?php
            	$this->render_field_form_fragment_required();
				$this->render_field_form_fragment_enabled();
				?>
                </td>
            </tr>     
        </table>
        <?php   
	}

	private function render_field_form_number(){
		?>
        <table id="thwepof_field_form_id_number" class="thwepof_field_form_table" width="100%" style="display:none;">
            <tr>
            	<?php
            	$this->render_field_form_fragment_title();
            	$this->render_field_form_fragment_title_class();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_value();
            	$this->render_field_form_fragment_placeholder();
				?>
            </tr>
            <tr>
            	<?php				
            	$this->render_field_form_fragment_class();
				$this->render_field_form_fragment_empty_cell();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_position();
            	$this->render_field_form_fragment_title_position();
				?>
            </tr>
            <?php $this->render_field_form_fragment_h_spacing(); ?>
            <tr>
            	<td>&nbsp;</td>
            	<td colspan="3">
            	<?php
            	$this->render_field_form_fragment_required();
				$this->render_field_form_fragment_enabled();
				?>
                </td>
            </tr>     
        </table>
        <?php   
	}

	private function render_field_form_tel(){
		?>
        <table id="thwepof_field_form_id_tel" class="thwepof_field_form_table" width="100%" style="display:none;">
            <tr>
            	<?php
            	$this->render_field_form_fragment_title();
            	$this->render_field_form_fragment_title_class();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_value();
            	$this->render_field_form_fragment_placeholder();
				?>
            </tr>
            <tr>
            	<?php				
            	$this->render_field_form_fragment_class();
				$this->render_field_form_fragment_validate();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_position();
            	$this->render_field_form_fragment_title_position();
				?>
            </tr>
            <?php $this->render_field_form_fragment_h_spacing(); ?>
            <tr>
            	<td>&nbsp;</td>
            	<td colspan="3">
            	<?php
            	$this->render_field_form_fragment_required();
				$this->render_field_form_fragment_enabled();
				?>
                </td>
            </tr>     
        </table>
        <?php   
	}

	private function render_field_form_password(){
		?>
        <table id="thwepof_field_form_id_password" class="thwepof_field_form_table" width="100%" style="display:none;">
            <tr>
            	<?php
            	$this->render_field_form_fragment_title();
            	$this->render_field_form_fragment_title_class();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_value();
            	$this->render_field_form_fragment_placeholder();
				?>
            </tr>
            <tr>
            	<?php				
            	$this->render_field_form_fragment_class();
				$this->render_field_form_fragment_validate();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_position();
            	$this->render_field_form_fragment_title_position();
				?>
            </tr>
            <?php $this->render_field_form_fragment_h_spacing(); ?>
            <tr>
            	<td>&nbsp;</td>
            	<td colspan="3">
            	<?php
            	$this->render_field_form_fragment_required();
				$this->render_field_form_fragment_enabled();
				?>
                </td>
            </tr>     
        </table>
        <?php   
	}

	private function render_field_form_textarea(){
		?>
        <table id="thwepof_field_form_id_textarea" class="thwepof_field_form_table" width="100%" style="display:none;">
            <tr>
            	<?php
            	$this->render_field_form_fragment_title();
            	$this->render_field_form_fragment_title_class();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_value();
            	$this->render_field_form_fragment_placeholder();
				?>
            </tr>
            <tr>
            	<?php				
            	$this->render_field_form_fragment_class();
				$this->render_field_form_fragment_validate();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_cols();
            	$this->render_field_form_fragment_rows();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_position();
            	$this->render_field_form_fragment_title_position();
				?>
            </tr>
            <?php $this->render_field_form_fragment_h_spacing(); ?>
            <tr>
            	<td>&nbsp;</td>
            	<td colspan="3">
            	<?php
            	$this->render_field_form_fragment_required();
				$this->render_field_form_fragment_enabled();
				?>
                </td>
            </tr>     
        </table>
        <?php   
	}

	private function render_field_form_select(){
		?>
        <table id="thwepof_field_form_id_select" class="thwepof_field_form_table" width="100%" style="display:none;">
            <tr>
            	<?php
            	$this->render_field_form_fragment_title();
            	$this->render_field_form_fragment_title_class();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_value();
            	$this->render_field_form_fragment_options();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_class();
				$this->render_field_form_fragment_empty_cell();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_position();
            	$this->render_field_form_fragment_title_position();
				?>
            </tr>
            <?php $this->render_field_form_fragment_h_spacing(); ?>
            <tr>
            	<td>&nbsp;</td>
            	<td colspan="3">
            	<?php
            	$this->render_field_form_fragment_required();
				$this->render_field_form_fragment_enabled();
				?>
                </td>
            </tr>  
        </table>
        <?php   
	}

	private function render_field_form_radio(){
		?>
        <table id="thwepof_field_form_id_radio" class="thwepof_field_form_table" width="100%" style="display:none;">
            <tr>
            	<?php
            	$this->render_field_form_fragment_title();
            	$this->render_field_form_fragment_title_class();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_value();
            	$this->render_field_form_fragment_options();
				?>
            </tr>
            <tr>
            	<?php				
            	$this->render_field_form_fragment_class();
				$this->render_field_form_fragment_empty_cell();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_position();
            	$this->render_field_form_fragment_title_position();
				?>
            </tr>
            <?php $this->render_field_form_fragment_h_spacing(); ?>
            <tr>
            	<td>&nbsp;</td>
            	<td colspan="3">
            	<?php
            	$this->render_field_form_fragment_required();
				$this->render_field_form_fragment_enabled();
				?>
                </td>
            </tr>     
        </table>
        <?php   
	}

	private function render_field_form_checkbox(){
		?>
        <table id="thwepof_field_form_id_checkbox" class="thwepof_field_form_table" width="100%" style="display:none;">
            <tr>
            	<?php
            	$this->render_field_form_fragment_title();
            	$this->render_field_form_fragment_value(true);
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_title_class();
            	$this->render_field_form_fragment_class();
				?>
            </tr>
            <tr>
            	<?php
            	$this->render_field_form_fragment_position();
            	$this->render_field_form_fragment_title_position();
				?>
            </tr>
            <?php $this->render_field_form_fragment_h_spacing(); ?>
            <tr>
            	<td>&nbsp;</td>
            	<td colspan="3">
            	<?php
            	$this->render_field_form_fragment_checked();
            	$this->render_field_form_fragment_required();
				$this->render_field_form_fragment_enabled();
				?>
                </td>
            </tr>     
        </table>
        <?php   
	}

	private function render_field_form_fragment_general($form_type){
		$field_types = $this->get_field_types();
		?>
        <table width="100%">
            <tr>                
                <td colspan="4" class="err_msgs"></td>
            </tr>            	         
            <tr>                
                <td width="15%"><?php $this->_ewcpf('Name') ?><abbr class="required" title="required">*</abbr></td>
                <td width="34%">
                	<input type="text" name="i_name" style="width:250px;"/>
                    <?php if($form_type === 'edit'){ ?>
                        <input type="hidden" name="i_rowid" value="" />
                    <?php } ?>
                </td>

                <td width="15%"><?php $this->_ewcpf('Field Type'); ?><abbr class="required" title="required">*</abbr></td>
                <td width="34%">
                    <select name="i_type" style="width:250px;" onchange="thwepofFieldTypeChangeListner(this)">
                    <?php foreach($field_types as $value=>$label){ ?>
                        <option value="<?php echo trim($value); ?>"><?php echo $label; ?></option>
                    <?php } ?>
                    </select>
                </td>
            </tr> 
        </table>  
        <?php
	}

	private function render_field_form_fragment_position(){
		$positions = $this->get_available_positions();
		?>
        <td width="15%"><?php $this->_ewcpf('Position'); ?></td>
        <td width="34%">
            <select name="i_position" style="width:250px;">
			<?php foreach($positions as $value => $label){ ?>
                <option value="<?php echo trim($value); ?>"><?php echo $label; ?></option>
            <?php } ?>
            </select>
        </td>
        <?php
	}

	private function render_field_form_fragment_title(){
		?>
        <td width="15%"><?php $this->_ewcpf('Label'); ?></td>
        <td width="34%"><input type="text" name="i_title" style="width:250px;"/></td>
        <?php
	}

	private function render_field_form_fragment_title_class(){
		?>
        <td width="15%"><?php $this->_ewcpf('Label Class'); ?></td>
        <td width="34%"><input type="text" name="i_title_class" placeholder="Seperate classes with comma" style="width:250px;"/></td>
        <?php
	}

	private function render_field_form_fragment_title_position(){
		?>
        <td width="15%"><?php $this->_ewcpf('Label Position'); ?></td>
        <td width="34%">
            <select name="i_title_position" style="width:250px;">
            	<option value="default">Default</option>
                <option value="left">Left to the field</option>                
            </select>
        </td>        
        <?php
	}

	private function render_field_form_fragment_value($is_value=false){
		$label = $is_value ? 'Value' : 'Default Value';
		?>
        <td width="15%"><?php $this->_ewcpf($label); ?></td>
        <td width="34%"><input type="text" name="i_value" style="width:250px;"/></td>
        <?php
	}

	private function render_field_form_fragment_placeholder(){
		?>
        <td width="15%"><?php $this->_ewcpf('Placeholder'); ?></td>
        <td width="34%"><input type="text" name="i_placeholder" style="width:250px;"/></td>
        <?php
	}

	private function render_field_form_fragment_options(){
		?>
        <td width="5%"><?php $this->_ewcpf('Options'); ?></td>
        <td width="34%"><input type="text" name="i_options" placeholder="Seperate options with pipe(|)" style="width:250px;"/></td>
        <?php
	}

	private function render_field_form_fragment_validate(){
		?>
        <td width="15%"><?php $this->_ewcpf('Validation'); ?></td>
        <td width="34%">
            <select multiple="multiple" name="i_validator" placeholder="Select validations" class="thwepof-enhanced-multi-select" style="width: 250px; height:30px;">
                <option value="email"><?php $this->_ewcpf('Email'); ?></option>
                <option value="number"><?php $this->_ewcpf('Number'); ?></option>
            </select>
        </td>
        <?php
	}

	private function render_field_form_fragment_class(){
		?>
        <td width="15%"><?php $this->_ewcpf('Wrapper Class'); ?></td>
        <td width="34%"><input type="text" name="i_cssclass" placeholder="Seperate classes with comma" style="width:250px;"/></td>
        <?php
	}

	private function render_field_form_fragment_cols(){
		?>
        <td width="15%"><?php $this->_ewcpf('Cols'); ?></td>
        <td width="34%"><input type="text" name="i_cols" style="width:250px;"/></td>
        <?php
	}

	private function render_field_form_fragment_rows(){
		?>
        <td width="15%"><?php $this->_ewcpf('Rows'); ?></td>
        <td width="34%"><input type="text" name="i_rows" style="width:250px;"/></td>
        <?php
	}

	private function render_field_form_fragment_checked(){
		?>
        <input type="checkbox" id="a_fchecked" name="i_checked" value="yes" />
        <label for="a_fchecked" style="margin-right: 40px;" ><?php $this->_ewcpf('Checked by default'); ?></label>
        <?php
	}

	private function render_field_form_fragment_required(){
		?>
        <input type="checkbox" id="a_frequired" name="i_required" value="yes" checked />
        <label for="a_frequired" style="margin-right: 40px;" ><?php $this->_ewcpf('Required'); ?></label>
        <?php
	}

	private function render_field_form_fragment_enabled(){
		?>                              
        <input type="checkbox" id="a_fenabled" name="i_enabled" value="yes" checked />
        <label for="a_fenabled" style="margin-right: 40px;" ><?php $this->_ewcpf('Enabled'); ?></label>
        <?php
	}

	private function render_field_form_fragment_empty_cell(){
		?>
		<td width="15%">&nbsp;</td>
        <td width="34%">&nbsp;</td>
        <?php
	}

	private function render_field_form_fragment_h_spacing($padding = 5){
		?>
        <tr><td colspan="4" style="padding-top:<?php echo $padding ?>px;"></td></tr>
        <?php
	}	
	
	private function render_field_form_fragment_rules($form_type){
		?>
        <tr>                
            <td colspan="6">
            	<table id="thwepo_conditional_rules" width="100%"><tbody>
                    <tr class="thwepo_rule_set_row">                
                        <td>
                            <table class="thwepo_rule_set" width="100%"><tbody>
                                <tr class="thwepo_rule_row">
                                    <td>
                                        <table class="thwepo_rule" width="100%" style=""><tbody>
                                            <tr class="thwepo_condition_set_row">
                                                <td>
                                                    <table class="thwepo_condition_set" width="100%" style=""><tbody>
                                                        <tr class="thwepo_condition">
                                                            <td width="25%">
                                                                <select name="i_rule_subject" style="width:200px;" onchange="thwepoRuleSubjectChangeListner(this)">
                                                                    <option value=""></option>
                                                                    <option value="product">Product</option>
                                                                    <option value="category">Category</option>
                                                                </select>
                                                            </td>
                                                            <td width="25%">
                                                                <select name="i_rule_comparison" style="width:200px;">
                                                                    <option value=""></option>
                                                                    <option value="equals">Equals to/ In</option>
                                                                    <option value="not_equals">Not Equals to/ Not in</option>
                                                                </select>
                                                            </td>
                                                            <td width="25%" class="thwepo_condition_value"><input type="text" name="i_rule_value" style="width:200px;"/></td>
                                                            <td>
                                                                <a href="javascript:void(0)" class="thwepof_logic_link" onclick="thwepoAddNewConditionRow(this, 1)" title="">AND</a>
                                                                <a href="javascript:void(0)" class="thwepof_logic_link" onclick="thwepoAddNewConditionRow(this, 2)" title="">OR</a>
                                                                <a href="javascript:void(0)" class="thwepof_delete_icon dashicons dashicons-no" onclick="thwepoRemoveRuleRow(this)" title="Remove"></a>
                                                            </td>
                                                        </tr>
                                                    </tbody></table>
                                                </td>
                                            </tr>
                                        </tbody></table>
                                    </td>
                                </tr>
                            </tbody></table>            	
                        </td>            
                    </tr> 
        		</tbody></table>
        	</td>
        </tr>
        <?php
	}
	
	private function render_field_form_fragment_product_list(){
		$products = apply_filters( "thwepof_load_products", array() );
		array_unshift( $products , array( "id" => "-1", "title" => "All Products" ));
		?>
        <div id="thwepo_product_select" style="display:none;">
        <select multiple="multiple" name="i_rule_value" class="thwepof-enhanced-multi-select" style="width:200px;" value="">
			<?php 	
                foreach($products as $product){
                    echo '<option value="'. $product["id"] .'" >'. $product["title"] .'</option>';
                }
            ?>
        </select>
        </div>
        <?php
	}
	private function render_field_form_fragment_category_list(){		
		$categories = apply_filters( "thwepof_load_products_cat", array() );
		array_unshift( $categories , array( "id" => "-1", "title" => "All Categories" ));
		?>
        <div id="thwepo_product_cat_select" style="display:none;">
        <select multiple="multiple" name="i_rule_value" class="thwepof-enhanced-multi-select" style="width:200px;" value="">
			<?php 	
                foreach($categories as $category){
                    echo '<option value="'. $category["id"] .'" >'. $category["title"] .'</option>';
                }
            ?>
        </select>
        </div>
        <?php
	}
   /*******************************************
 	*-------- HTML FORM FRAGMENTS - END -------
 	*******************************************/
}
endif;