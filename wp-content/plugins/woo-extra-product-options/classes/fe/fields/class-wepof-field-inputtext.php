<?php
/**
 * Product Field - Input Text
 *
 * @author    ThemeHiGH
 * @category  Admin
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPOF_Product_Field_InputText')):
class WEPOF_Product_Field_InputText extends WEPOF_Product_Field{
	/*public $order = '';
	public $type = '';
	public $id   = '';
	public $name = '';	

	public $value = '';
	public $placeholder = '';
	public $validator = '';
	public $cssclass = '';
	public $cssclass_str = '';

	public $title = '';
	public $title_class = '';
	public $title_class_str = '';
	public $title_position = 'default';
	
	public $required = false;
	public $enabled  = true;

	public $position = 'woo_before_add_to_cart_button';
	
	public $conditional_rules_json = '';
	public $conditional_rules = array();*/

	public function __construct() {
		$this->type = 'inputtext';
	}	
	
	/*public function prepare_properties(){
		$this->set_property('cssclass_str', WEPOF_Utils_Field::convert_cssclass_string($this->cssclass));
		$this->set_property('title_class_str', WEPOF_Utils_Field::convert_cssclass_string($this->title_class));
		
		if(empty($this->title_position)){
			$this->title_position = 'default';
		}
		
		if(empty($this->position)){
			$this->position = 'woo_before_add_to_cart_button';
		}
	}*/

   /***********************************
	**** Setters & Getters - START ****
	***********************************/
	/*public function set_property($name, $value){
		$this->$name = $value;
	}
	
	public function get_property($name){
		if(property_exists($this, $name)){
			return $this->$name;
		}else{
			return '';
		}
	}
		
	public function set_conditional_rules_json($conditional_rules_json){
		$conditional_rules_json = str_replace("'", '"', $conditional_rules_json);
		$this->conditional_rules_json = $conditional_rules_json;
	}
	public function set_conditional_rules($conditional_rules){
		$this->conditional_rules = $conditional_rules;
	}*/
				
	/*** Getters ***/	
	/*public function is_required(){
		return $this->required;
	}	

	public function is_enabled(){
		return $this->enabled;
	}
	
	public function get_conditional_rules_json(){
		return $this->conditional_rules_json;
	}
	public function get_conditional_rules(){
		return $this->conditional_rules;
	}*/

	/*** DUMMY SETTERS & GETTERS ***/
	/*public function set_options_str($options_str){}
	public function set_options($options){}

	public function get_options(){ return false; }
	public function get_options_str(){ return false; }*/

   /***********************************
	**** Setters & Getters - END ******
	***********************************/
}
endif;