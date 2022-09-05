<?php
/**
 * Woo Extra Product Options common functions
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Utils_Field')) :
class WEPOF_Utils_Field {
	public static function is_valid_field($field){
		if(isset($field) && $field instanceof WEPOF_Product_Field && self::is_valid($field)){
			return true;
		} 
		return false;
	}
	
	public static function is_enabled($field){
		if(self::is_valid_field($field) && $field->get_property('enabled')){
			return true;
		}
		return false;
	}
	
	public static function is_valid($field){
		if(empty($field->name) || empty($field->type)){
			return false;
		}
		return true;
	}

	public static function prepare_properties($field){
		$field->set_property('id', $field->get_property('name'));
		$field->set_property('cssclass_str', self::convert_cssclass_string($field->get_property('cssclass')));
		$field->set_property('title_class_str', self::convert_cssclass_string($field->get_property('title_class')));

		$position = $field->get_property('position');
		$title_position = $field->get_property('title_position');
		
		if(empty($position)){
			$field->set_property('position', 'woo_before_add_to_cart_button');
		}

		if(empty($title_position)){
			$field->set_property('title_position', 'default');
		}
	}

	public static function prepare_field_from_posted_data($posted, $props){
		$type = isset($posted['i_type']) ? trim(stripslashes($posted['i_type'])) : '';
		//$type = empty($type) ? trim(stripslashes($posted['i_original_type'])) : $type;
			
		$field = self::create_field($type); 
		
		foreach( $props as $pname => $property ){
			$iname  = 'i_'.$pname;
			
			$pvalue = '';
			if($property['type'] === 'checkbox'){
				$pvalue = isset($posted[$iname]) ? $posted[$iname] : 0;
			}else if(isset($posted[$iname])){
				$pvalue = is_array($posted[$iname]) ? implode(',', $posted[$iname]) : trim(stripslashes($posted[$iname]));
			}
			
			$field->set_property($pname, $pvalue);
		}
		
		if($type === 'select' || $type === 'radio'){
			/*$options_json = isset($posted['i_options']) ? trim(stripslashes($posted['i_options'])) : '';
			$options_arr = self::prepare_options_array($options_json);
			
			$options_extra = apply_filters('thwepo_field_options', array(), $field->get_property('name'));
			if(is_array($options_extra) && !empty($options_extra)){
				$options_arr = array_merge($options_arr, $options_extra);
				$options_json = self::prepare_options_json($options_arr);
			}

			$field->set_property('options_json', $options_json);
			$field->set_property('options', $options_arr);*/
			$field->set_options_str(isset($posted['i_options']) ? trim(stripslashes($posted['i_options'])) : '');
		}
		
		$field->set_property('name_old', isset($posted['i_name_old']) ? trim(stripslashes($posted['i_name_old'])) : '');
		$field->set_property('position_old', isset($posted['i_position_old']) ? trim(stripslashes($posted['i_position_old'])) : '');

		$field->set_conditional_rules_json(isset($posted['i_rules']) ? trim(stripslashes($posted['i_rules'])) : '');
		$field->set_conditional_rules(self::prepare_conditional_rules($field->get_conditional_rules_json()));
		
		self::prepare_properties($field);
		return $field;
	}

	public static function prepare_conditional_rules($conditional_rules){
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
	}
	
	public static function show_field($field, $product, $categories){
		$show = true;
		$conditional_rules = $field->get_property('conditional_rules');
		if(!empty($conditional_rules)){
			foreach($conditional_rules as $conditional_rule){				
				if(!$conditional_rule->is_satisfied($product, $categories)){
					$show = false;
				}
			}
		}
		return $show;
	}
	
	public static function convert_cssclass_string($cssclass){
		if(!is_array($cssclass)){
			$cssclass = array_map('trim', explode(',', $cssclass));
		}
		
		if(is_array($cssclass)){
			$cssclass = implode(" ",$cssclass);
		}
		return $cssclass;
	}
	
	public static function render_field($field){
		echo self::get_html($field);
	}

	public static function get_html($field){
		$html = '';
		if(self::is_valid_field($field)){
			$name  = $field->get_property('name');
			$type  = $field->get_property('type');
			$value = isset($_POST[$name]) ? $_POST[$name] : $field->get_property('value');
			$value = $value ? trim(stripslashes($value)) : $value;
			
			$input_html = false;
			if($type === 'inputtext'){
				$input_html = self::get_html_inputtext($field, $name, $value);
			}else if($type === 'hidden'){
				$input_html = self::get_html_hidden($field, $name, $value);
			}else if($type === 'number'){
				$input_html = self::get_html_number($field, $name, $value);
			}else if($type === 'tel'){
				$input_html = self::get_html_tel($field, $name, $value);
			}else if($type === 'password'){
				$input_html = self::get_html_password($field, $name, $value);
			}else if($type === 'textarea'){
				$input_html = self::get_html_textarea($field, $name, $value);
			}else if($type === 'select'){
				$input_html = self::get_html_select($field, $name, $value);
			}else if($type === 'checkbox'){
				$input_html = self::get_html_checkbox($field, $name, $value);
			}else if($type === 'radio'){
				$input_html = self::get_html_radio($field, $name, $value);
			}
			
			if($input_html){
				if($type === 'hidden'){
					$html .= $input_html;
				}else if($type === 'checkbox'){
					$title_html  = $field->get_property('title');
					$title_html .= self::get_required_html($field);

					$html .= '<p class="thwepo-extra-options left form-row form-row-wide '. $field->get_property('cssclass_str') .'">';
					$html .= $input_html;
					$html .= '<label for="'. $name .'" class="'. $field->get_property('title_class_str') .'">'.$title_html.'</label>';
					$html .= '</p><style>.single_variation_wrap {padding-top: 40px;position: relative;}</style>';
				}else{
					$title_html  = $field->get_property('title');
					$title_html .= self::get_required_html($field);

					$title_position = $field->get_property('title_position');

					if($title_position === 'left'){
						$html .= '<p class="thwepo-extra-options left form-row form-row-wide '. $field->get_property('cssclass_str') .'">';
						$html .= '<label for="'. $name .'" class="'. $field->get_property('title_class_str') .'">'.$title_html.'</label>';
						$html .= $input_html;
						$html .= '</p><style>.single_variation_wrap {padding-top: 40px;position: relative;}</style>';
					}else{
						$html .= '<p class="thwepo-extra-options form-row form-row-wide '. $field->get_property('cssclass_str') .'">';
						$html .= '<label for="'. $name .'" class="'. $field->get_property('title_class_str') .'">'.$title_html.'</label>';
						$html .= $input_html;
						$html .= '</p><style>.single_variation_wrap {padding-top: 40px;position: relative;}</style>';
					}
				}
			}
		}	
		return $html;
	}
	
	private static function get_required_html($field){
		$required = $field->get_property('required');
		$html = '';
		
		if($required){
			$html = apply_filters( 'thwepof_required_html', ' <abbr class="required" title="required">*</abbr>', $field->get_property('name') );
		}
		return $html;
	}
	
	private static function get_html_inputtext($field, $name, $value){
		$html = '<input type="text" id="'.$name.'" name="'.$name.'" placeholder="'.$field->get_property('placeholder').'" value="'.$value.'" >';
		return $html;
	}

	private static function get_html_hidden($field, $name, $value){
		$html = '<input type="hidden" id="'.$name.'" name="'.$name.'" value="'.$value.'" >';
		return $html;
	}

	private static function get_html_number($field, $name, $value){
		$html = '<input type="number" id="'.$name.'" name="'.$name.'" placeholder="'.$field->get_property('placeholder').'" value="'.$value.'" >';
		return $html;
	}

	private static function get_html_tel($field, $name, $value){
		$html = '<input type="tel" id="'.$name.'" name="'.$name.'" placeholder="'.$field->get_property('placeholder').'" value="'.$value.'" >';
		return $html;
	}

	private static function get_html_password($field, $name, $value){
		$html = '<input type="password" id="'.$name.'" name="'.$name.'" placeholder="'.$field->get_property('placeholder').'" value="'.$value.'" >';
		return $html;
	}

	private static function get_html_textarea($field, $name, $value){
		$ph = $field->get_property('placeholder');
		$cols = is_numeric($field->get_property('cols')) ? 'cols="'.$field->get_property('cols').'"' : '';
		$rows = is_numeric($field->get_property('rows')) ? 'rows="'.$field->get_property('rows').'"' : '';
		$html = '<textarea id="'.$name.'" name="'.$name.'" placeholder="'.$ph.'" '.$cols.' '.$rows.'>'.$value.'</textarea>';
		return $html;
	}
	
	private static function get_html_select($field, $name, $value){
		$html = '<select id="'.$name.'" name="'.$name.'" placeholder="'.$field->get_property('placeholder').'" value="'.$value.'" >';
		foreach($field->get_property('options') as $option_key => $option_text){
			$selected = ($option_text === $value) ? 'selected' : '';
			$html .= '<option value="'.$option_text.'" '.$selected.'>'.$option_text.'</option>';
		}
		$html .= '</select>';
		return $html;
	}

	private static function get_html_checkbox($field, $name, $value){
		$checked = $field->get_property('checked') ? 'checked' : '';
		$value = empty($value) ? '1' : $value;
		$html = '<input type="checkbox" id="'.$name.'" name="'.$name.'" value="'.$value.'" '.$checked.'>';
		return $html;
	}

	private static function get_html_radio($field, $name, $value){
		$html = '';
		$i=0;
		foreach($field->get_property('options') as $option_key => $option_text){
			$id = $name.'_'.$option_key;
			$checked = ($option_text === $value) ? 'checked' : '';
			$style = $i > 0 ? 'margin-left:10px;' : '';
			$html .= '<input type="radio" id="'.$id.'" name="'.$name.'" value="'.$option_text.'" '.$checked.' style="'.$style.'"> '.$option_text;
			$i++;
		}
		return $html;
	}

	public static function create_field($type, $name = false, $field_args = false){
		$field = false;
		
		if(isset($type)){
			if($type === 'inputtext'){
				return new WEPOF_Product_Field_InputText();
			}else if($type === 'hidden'){
				return new WEPOF_Product_Field_Hidden();
			}else if($type === 'number'){
				return new WEPOF_Product_Field_Number();
			}else if($type === 'tel'){
				return new WEPOF_Product_Field_Tel();
			}else if($type === 'password'){
				return new WEPOF_Product_Field_Password();
			}else if($type === 'textarea'){
				return new WEPOF_Product_Field_Textarea();
			}else if($type === 'select'){
				return new WEPOF_Product_Field_Select();
			}else if($type === 'radio'){
				return new WEPOF_Product_Field_Radio();
			}else if($type === 'checkbox'){
				return new WEPOF_Product_Field_Checkbox();
			}else if($type === 'datepicker'){
				return new WEPOF_Product_Field_DatePicker();
			}else if($type === 'label'){
				return new WEPOF_Product_Field_html();
			}
		}else{
			$field = new WEPOF_Product_Field_InputText();
		}
		return $field;
	}
}
endif;