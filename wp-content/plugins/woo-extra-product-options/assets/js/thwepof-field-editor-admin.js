var thwepof_settings = (function($, window, document) {
	var MSG_INVALID_NAME = 'NAME/ID must begin with a lowercase letter ([a-z]) and may be followed by any number of lowercase letters, digits ([0-9]) and underscores ("_")';

	var OP_AND_HTML  = '<label class="thwepof_logic_label">AND</label>';
		OP_AND_HTML += '<a href="javascript:void(0)" onclick="thwepoRemoveRuleRow(this)" class="thwepof_delete_icon dashicons dashicons-no" title="Remove"></a>';
	var OP_OR_HTML   = '<tr class="thwepo_rule_or"><td colspan="4" align="center">OR</td></tr>';
	
	var OP_HTML  = '<a href="javascript:void(0)" class="thwepof_logic_link" onclick="thwepoAddNewConditionRow(this, 1)" title="">AND</a>';
		OP_HTML += '<a href="javascript:void(0)" class="thwepof_logic_link" onclick="thwepoAddNewConditionRow(this, 2)" title="">OR</a>';
		OP_HTML += '<a href="javascript:void(0)" onclick="thwepoRemoveRuleRow(this)" class="thwepof_delete_icon dashicons dashicons-no" title="Remove"></a>';
				
	var CONDITION_HTML  = '<tr class="thwepo_condition">';
		CONDITION_HTML += '<td width="25%"><select name="i_rule_subject" style="width:200px;" onchange="thwepoRuleSubjectChangeListner(this)">';
		CONDITION_HTML += '<option value=""></option><option value="product">Product</option><option value="category">Category</option>';
		CONDITION_HTML += '</select></td>';		
		CONDITION_HTML += '<td width="25%"><select name="i_rule_comparison" style="width:200px;">';
		CONDITION_HTML += '<option value=""></option> <option value="equals">Equals to/ In</option><option value="not_equals">Not Equals to/ Not in</option>';
		CONDITION_HTML += '</select></td>';
		CONDITION_HTML += '<td width="25%" class="thwepo_condition_value"><input type="text" name="i_rule_value" style="width:200px;"/></td>';
		CONDITION_HTML += '<td>'+ OP_HTML +'</td></tr>';
		
	var CONDITION_SET_HTML  = '<tr class="thwepo_condition_set_row"><td>';
		CONDITION_SET_HTML += '<table class="thwepo_condition_set" width="100%" style=""><tbody>'+CONDITION_HTML+'</tbody></table>';
		CONDITION_SET_HTML += '</td></tr>';
		
	var CONDITION_SET_HTML_WITH_OR = '<tr class="thwepo_condition_set_row"><td>';
		CONDITION_SET_HTML_WITH_OR += '<table class="thwepo_condition_set" width="100%" style=""><thead>'+OP_OR_HTML+'</thead><tbody>'+CONDITION_HTML+'</tbody></table>';
		CONDITION_SET_HTML_WITH_OR += '</td></tr>';
	
	var RULE_HTML  = '<tr class="thwepo_rule_row"><td>';
		RULE_HTML += '<table class="thwepo_rule" width="100%" style=""><tbody>'+CONDITION_SET_HTML+'</tbody></table>';
		RULE_HTML += '</td></tr>';	
		
	var RULE_SET_HTML  = '<tr class="thwepo_rule_set_row"><td>';
		RULE_SET_HTML += '<table class="thwepo_rule_set" width="100%"><tbody>'+RULE_HTML+'</tbody></table>';
		RULE_SET_HTML += '</td></tr>';		

   /*------------------------------------
	*---- ON-LOAD FUNCTIONS - SATRT -----
	*------------------------------------*/	 
	$( "#thwepof_new_field_form_pp" ).dialog({
	  	modal: true,
		width: 900,
		resizable: false,
		autoOpen: false,
		buttons: [
			{
				text: "Cancel",
				click: function() { $( this ).dialog( "close" ); }	
			},
			{
				text: "Add New Field",
				click: function() {
					var form = $("#thwepof_new_field_form");
					var result = validate_field_form( form );
					if(result){ 
						prepare_field_form(form);
						form.submit(); 
					}
					/*var result = wcpf_add_new_row( this );
					if(result){
						$( this ).dialog( "close" );
					}*/
				}
			}
		]
	});	

	$( "#thwepof_edit_field_form_pp" ).dialog({
	  	modal: true,
		width: 900,
		resizable: false,
		autoOpen: false,
		buttons: [
			{
				text: "Cancel",
				click: function() { $( this ).dialog( "close" ); }	
			},
			{
				text: "Update Field",
				click: function() {
					var form = $("#thwepof_edit_field_form");
					var result = validate_field_form( form );
					if(result){ 
						prepare_field_form(form);
						form.submit(); 
					}
					/*var result = wcpf_update_row( this );
					if(result){
						$( this ).dialog( "close" );
					}*/
				}
			}
		]
	});

	$('#thwepof_product_fields tbody').sortable({
		items:'tr',
		cursor:'move',
		axis:'y',
		handle: 'td.sort',
		scrollSensitivity:40,
		helper:function(e,ui){
			ui.children().each(function(){
				$(this).width($(this).width());
			});
			ui.css('left', '0');
			return ui;
		}		
	});	

	$("#thwepof_product_fields tbody").on("sortstart", function( event, ui ){
		ui.item.css('background-color','#f6f6f6');										
	});

	$("#thwepof_product_fields tbody").on("sortstop", function( event, ui ){
		ui.item.removeAttr('style');
		thwepof_prepare_field_order_indexes();
	});	
   /*------------------------------------
	*---- ON-LOAD FUNCTIONS - END -------
	*------------------------------------*/

   /*------------------------------------
	*---- COMMON FUNCTIONS - START ------
	*------------------------------------*/
	function setup_enhanced_multi_select(form){
		form.find('select.thwepof-enhanced-multi-select').each(function(){
			if(!$(this).hasClass('enhanced')){
				$(this).select2({
					minimumResultsForSearch: 10,
					allowClear : true,
					placeholder: $(this).data('placeholder')
				}).addClass('enhanced');
			}
		});
	}

	function thwepof_prepare_field_order_indexes() {
		$('#thwepof_product_fields tbody tr').each(function(index, el){
			$('input.f_order', el).val( parseInt( $(el).index('#thwepof_product_fields tbody tr') ) );
		});
	};

	function isHtmlIdValid(id) {
		/*var re = /^[A-Za-z]+[\w\-\:\.]*$/;
		return re.test(id)*/
		var re = /^[a-z\_]+[a-z0-9\_]*$/;
		return re.test(id.trim());
	}

	_selectAllFields = function selectAllFields(elm){
		var checkAll = $(elm).prop('checked');
		$('#thwepof_product_fields tbody input:checkbox[name=select_field]').prop('checked', checkAll);
	}

	function get_property_field_value(form, type, name){
		var value = '';
		
		switch(type) {
			case 'select':
				value = form.find("select[name=i_"+name+"]").val();
				value = value == null ? '' : value;
				break;
				
			case 'checkbox':
				value = form.find("input[name=i_"+name+"]").prop('checked');
				value = value ? 1 : 0;
				break;
				
			default:
				value = form.find("input[name=i_"+name+"]").val();
				value = value == null ? '' : value;
		}	
		
		return value;
	}
	
	function set_property_field_value(form, type, name, value, multiple){
		switch(type) {
			case 'select':
				if(multiple == 1 && typeof(value) === 'string'){
					value = value.split(",");
					name = name+"[]";
				}
				form.find('select[name="i_'+name+'"]').val(value);
				break;
				
			case 'checkbox':
				value = value == 1 ? true : false;
				form.find("input[name=i_"+name+"]").prop('checked', value);
				break;
				
			default:
				form.find("input[name=i_"+name+"]").val(value);
		}	
	}
   /*------------------------------------
	*---- COMMON FUNCTIONS - END --------
	*------------------------------------*/

   /*------------------------------------
	*---- PRODUCT FIELDS - SATRT --------
	*------------------------------------*/
	_openNewFieldForm = function openNewFieldForm(){
		var form = $("#thwepof_field_editor_form_new");
		
		clear_field_form_general(form);
		form.find("select[name=i_type]").change();	
		clear_field_form(form);
		
	  	$("#thwepof_new_field_form_pp").dialog("open");
	}
	
	_openEditFieldForm = function openEditFieldForm(elm, rowId){
		var row = $(elm).closest('tr');
		var form = $("#thwepof_field_editor_form_edit");

		populate_field_form_general(row, form, rowId);					
		form.find("select[name=i_type]").change();		
		populate_field_form(row, form, rowId);	
		
		$("#thwepof_edit_field_form_pp").dialog("open");
	}

	_fieldTypeChangeListner = function fieldTypeChangeListner(elm){
		var type = $(elm).val();
		var form = $(elm).closest('form');

		type = type == null ? 'default' : type;
		form.find('.thwepof_field_form_placeholder').html($('#thwepof_field_form_id_'+type).html());
		setup_enhanced_multi_select(form);				
	}

	function clear_field_form_general( form ){
		form.find('.err_msgs').html('');
		form.find("input[name=i_name]").val('');
		form.find("select[name=i_type]").prop('selectedIndex',0);
	}

	function clear_field_form(form){
		form.find("select[name=i_position]").prop('selectedIndex',0);
		form.find("input[name=i_value]").val('');
		form.find("input[name=i_placeholder]").val('');
		form.find("input[name=i_options]").val('');

		form.find("select[name=i_validator] option:selected").removeProp('selected');
		form.find("input[name=i_cssclass]").val('');

		form.find("input[name=i_title]").val('');
		form.find("input[name=i_title_class]").val('');
		form.find("select[name=i_title_position]").prop('selectedIndex',0);

		form.find("input[name=i_cols]").val('');
		form.find("input[name=i_rows]").val('');

		form.find("input[name=i_checked]").prop('checked', false);
		form.find("input[name=i_required]").prop('checked', true);
		form.find("input[name=i_enabled]").prop('checked', true);	
		
		var conditionalRulesTable = form.find("#thwepo_conditional_rules tbody");
		conditionalRulesTable.html(RULE_SET_HTML);
		setup_enhanced_multi_select(conditionalRulesTable);
	}

	function populate_field_form_general(row, form, rowId){
		var name = row.find(".f_name").val();
		var type = row.find(".f_type").val();

		form.find("input[name=i_rowid]").val(rowId);
		form.find("input[name=i_name]").val(name);
		form.find("input[name=i_name_old]").val(name);
		form.find("select[name=i_type]").val(type);		
	}

	function populate_field_form(row, form, rowId){	
		var position = row.find(".f_position").val();
		var value = row.find(".f_value").val();
		var placeholder = row.find(".f_placeholder").val();
		var selectOptions = row.find(".f_options").val();

		var validator = row.find(".f_validator").val();
		var cssclass = row.find(".f_cssclass").val();

		var title = row.find(".f_title").val();
		var title_class = row.find(".f_title_class").val();
		var title_position = row.find(".f_title_position").val();

		var cols = row.find(".f_cols").val();
		var rows = row.find(".f_rows").val();

		var checked = row.find(".f_checked").val();
		var required = row.find(".f_required").val();		
		var enabled = row.find(".f_enabled").val();
		
		var conditionalRules = row.find(".f_rules").val();
													
		required = required == 1 ? true : false;
		enabled  = enabled == 1 ? true : false;
		validator = validator.split(",");

		form.find("select[name=i_position]").val(position);
		form.find("input[name=i_position_old]").val(position);
		form.find("input[name=i_value]").val(value);
		form.find("input[name=i_placeholder]").val(placeholder);
		form.find("input[name=i_options]").val(selectOptions);

		form.find("select[name=i_validator]").val(validator).trigger("change");
		form.find("input[name=i_cssclass]").val(cssclass);			

		form.find("input[name=i_title]").val(title);
		form.find("input[name=i_title_class]").val(title_class);
		form.find("select[name=i_title_position]").val(title_position);

		set_property_field_value(form, 'text', 'cols', cols);
		set_property_field_value(form, 'text', 'rows', rows);
		set_property_field_value(form, 'checkbox', 'checked', checked);

		form.find("input[name=i_required]").prop('checked', required);
		form.find("input[name=i_enabled]").prop('checked', enabled);
		
		populate_conditional_rules(form, conditionalRules);	
	}

	function validate_field_form(form){
		var err_msgs = '';
		
		var fname  = get_property_field_value(form, 'text', 'name');
		var ftype  = get_property_field_value(form, 'select', 'type');
		var ftitle = get_property_field_value(form, 'text', 'title');
		//var foriginalType  = thwepo_base.get_property_field_value(form, 'hidden', 'original_type');
		
		if(ftype == 'html'){
			if(fname == ''){
				err_msgs = 'Name is required';
			}else if(!isHtmlIdValid(fname)){
				err_msgs = MSG_INVALID_NAME;
			}else if(ftitle == ''){
				err_msgs = 'Title is required';
			}		
		}else{
			if(ftype == '' ){
				err_msgs = 'Type is required';
			}else if(fname == ''){
				err_msgs = 'Name is required';
			}else if(!isHtmlIdValid(fname)){
				err_msgs = MSG_INVALID_NAME;
			}
		}	
		
		if(err_msgs != ''){
			form.find('.err_msgs').html(err_msgs);
			return false;
		}
		return true;
	}

	function prepare_field_form(form){
		var rules_json = get_conditional_rules(form);	
		rules_json = rules_json.replace(/"/g, "'");

		set_property_field_value(form, 'text', 'rules', rules_json);
	}

	function wcpf_add_new_row(form){		
		var type = $(form).find("select[name=i_type]").val();
		var name = $(form).find("input[name=i_name]").val();
		var position = $(form).find("select[name=i_position]").val();

		var value = $(form).find("input[name=i_value]").val();
		var placeholder = $(form).find("input[name=i_placeholder]").val();
		var selectOptions = $(form).find("input[name=i_options]").val();
		
		var validator = $(form).find("select[name=i_validator]").val();
		var cssclass = $(form).find("input[name=i_cssclass]").val();

		var title = $(form).find("input[name=i_title]").val();
		var title_class = $(form).find("input[name=i_title_class]").val();
		var title_position = $(form).find("select[name=i_title_position]").val();
				
		var required = $(form).find("input[name=i_required]").prop('checked');
		var enabled  = $(form).find("input[name=i_enabled]").prop('checked');
		
		var rules_json = get_conditional_rules(form);	
		rules_json = rules_json.replace(/"/g, "'");
	
		var err_msgs = '';
		if(type == ''){
			err_msgs = 'Type is required';
		}else if(name == ''){
			err_msgs = 'Name is required';
		}else if(!isHtmlIdValid(name)){
			err_msgs = MSG_INVALID_NAME;
		}

		if(err_msgs != ''){
			$(form).find('.err_msgs').html(err_msgs);
			return false;
		}

		required = required ? 1 : 0;
		enabled  = enabled ? 1 : 0;		
		validator = validator == null ? '' : validator;

		var index = $('#thwepof_product_fields tbody tr').size();

		var newRow = '<tr class="row_'+index+'">';
		newRow += '<td width="1%" class="sort ui-sortable-handle">';		
		newRow += '<input type="hidden" name="f_order['+index+']" class="f_order" value="'+index+'" />';
		newRow += '<input type="hidden" name="f_name['+index+']" class="f_name" value="'+name+'" />';
		newRow += '<input type="hidden" name="f_type['+index+']" class="f_type" value="'+type+'" />';
		newRow += '<input type="hidden" name="f_position['+index+']" class="f_position" value="'+position+'" />';

		newRow += '<input type="hidden" name="f_value['+index+']" class="f_value" value="'+value+'" />';
		newRow += '<input type="hidden" name="f_placeholder['+index+']" class="f_placeholder" value="'+placeholder+'" />';	
		newRow += '<input type="hidden" name="f_options['+index+']" class="f_options" value="'+selectOptions+'" />';

		newRow += '<input type="hidden" name="f_validator['+index+']" class="f_validator" value="'+validator+'" />';	
		newRow += '<input type="hidden" name="f_cssclass['+index+']" class="f_cssclass" value="'+cssclass+'" />';
				
		newRow += '<input type="hidden" name="f_title['+index+']" class="f_title" value="'+title+'" />';
		newRow += '<input type="hidden" name="f_title_class['+index+']" class="f_title_class" value="'+title_class+'" />';
		newRow += '<input type="hidden" name="f_title_position['+index+']" class="f_title_position" value="'+title_position+'" />';

		newRow += '<input type="hidden" name="f_required['+index+']" class="f_required" value="'+required+'" />';
		newRow += '<input type="hidden" name="f_enabled['+index+']" class="f_enabled" value="'+enabled+'" />';
		newRow += '<input type="hidden" name="f_deleted['+index+']" class="f_deleted" value="0" />';
		newRow += '<input type="hidden" name="f_rules['+index+']" class="f_rules" value="'+rules_json+'" />';
		newRow += '</td>';		
		newRow += '<td class="td_select"><input type="checkbox" /></td>';		
		newRow += '<td class="td_name">'+name+'</td>';
		newRow += '<td class="td_type">'+type+'</td>';
		newRow += '<td class="td_title">'+title+'</td>';
		newRow += '<td class="td_placeholder">'+placeholder+'</td>';
		newRow += '<td class="td_validate">'+validator+'</td>';

		if(required == true){
			newRow += '<td class="td_required status"><span class="status-enabled tips">Yes</span></td>';
		}else{
			newRow += '<td class="td_required status">-</td>';
		}

		if(enabled == true){
			newRow += '<td class="td_enabled status"><span class="status-enabled tips">Yes</span></td>';
		}else{
			newRow += '<td class="td_enabled status">-</td>';
		}

		newRow += '<td class="td_edit" align="center"><button type="button" onclick="thwepofOpenEditFieldForm(this, '+index+')">Edit</button></td>';
		newRow += '</tr>';

		if(index > 0){
			$('#thwepof_product_fields tbody tr:last').after(newRow);
		}else{
			$('#thwepof_product_fields tbody').append(newRow);
		}

		return true;
	}

	function wcpf_update_row(form){
		var rowId = $(form).find("input[name=i_rowid]").val();

		var type = $(form).find("select[name=i_type]").val();
		var name = $(form).find("input[name=i_name]").val();
		var position = $(form).find("select[name=i_position]").val();

		var value = $(form).find("input[name=i_value]").val();
		var placeholder = $(form).find("input[name=i_placeholder]").val();
		var selectOptions = $(form).find("input[name=i_options]").val();

		var validator = $(form).find("select[name=i_validator]").val();
		var cssclass = $(form).find("input[name=i_cssclass]").val();

		var title = $(form).find("input[name=i_title]").val();
		var title_class = $(form).find("input[name=i_title_class]").val();
		var title_position = $(form).find("select[name=i_title_position]").val();

		var required = $(form).find("input[name=i_required]").prop('checked');
		var enabled  = $(form).find("input[name=i_enabled]").prop('checked');
		
		var rules_json = get_conditional_rules(form);

		required = required ? 1 : 0;
		enabled  = enabled ? 1 : 0;
		validator = validator == null ? '' : validator;

		var err_msgs = '';
		if(type == ''){
			err_msgs = 'Type is required';
		}else if(name == ''){
			err_msgs = 'Name is required';
		}else if(!isHtmlIdValid(name)){
			err_msgs = MSG_INVALID_NAME;
		}

		if(err_msgs != ''){
			$(form).find('.err_msgs').html(err_msgs);
			return false;
		}

		var row = $('#thwepof_product_fields tbody').find('.row_'+rowId);
		
		row.find(".f_type").val(type);
		row.find(".f_name").val(name);
		row.find(".f_position").val(position);

		row.find(".f_value").val(value);
		row.find(".f_placeholder").val(placeholder);
		row.find(".f_options").val(selectOptions);

		row.find(".f_validator").val(validator);
		row.find(".f_cssclass").val(cssclass);
		
		row.find(".f_title").val(title);
		row.find(".f_title_class").val(title_class);
		row.find(".f_title_position").val(title_position);

		row.find(".f_required").val(required);
		row.find(".f_enabled").val(enabled);
		
		row.find(".f_rules").val(rules_json);

		row.find(".td_name").html(name);
		row.find(".td_type").html(type);
		row.find(".td_title").html(title);
		row.find(".td_placeholder").html(placeholder);
		row.find(".td_validate").html(""+validator+"");
		row.find(".td_required").html(required == 1 ? '<span class="status-enabled tips">Yes</span>' : '-');
		row.find(".td_enabled").html(enabled == 1 ? '<span class="status-enabled tips">Yes</span>' : '-');

		return true;
	}		
	
	/* Conditional rules */
	
	this.ruleSubjectChangeListner = function(elm){
		$(elm).closest("tr.thwepo_condition").find("td.thwepo_condition_value").html();
		
		var subject = $(elm).val();
		var condition_row = $(elm).closest("tr.thwepo_condition");
		var target  = condition_row.find("td.thwepo_condition_value");
		
		if(subject === 'category'){
			target.html( $("#thwepo_product_cat_select").html() );
		}else{
			target.html( $("#thwepo_product_select").html() );
		}	
		setup_enhanced_multi_select(condition_row);		
	}
	
	_add_new_rule_row = function add_new_rule_row(elm, op){
		var condition_row = $(elm).closest('tr');
		condition = {};
		condition["subject"] = condition_row.find("select[name=i_rule_subject]").val();
		condition["comparison"] = condition_row.find("select[name=i_rule_comparison]").val();
		condition["cvalue"] = condition_row.find("select[name=i_rule_value]").val();
		if(!is_valid_condition(condition)){
			alert('Please provide a valid condition.');
			return;
		}
		
		if(op == 1){
			var conditionSetTable = $(elm).closest('.thwepo_condition_set');
			var conditionSetSize  = conditionSetTable.find('tbody tr.thwepo_condition').size();
			
			if(conditionSetSize > 0){
				$(elm).closest('td').html(OP_AND_HTML);
				conditionSetTable.find('tbody tr.thwepo_condition:last').after(CONDITION_HTML);
			}else{
				conditionSetTable.find('tbody').append(CONDITION_HTML);
			}
		}else if(op == 2){
			var ruleTable = $(elm).closest('.thwepo_rule');
			var ruleSize  = ruleTable.find('tbody tr.thwepo_condition_set_row').size();
			
			if(ruleSize > 0){
				ruleTable.find('tbody tr.thwepo_condition_set_row:last').after(CONDITION_SET_HTML_WITH_OR);
			}else{
				ruleTable.find('tbody').append(CONDITION_SET_HTML);
			}
		}	
	}
	
	_remove_rule_row = function remove_rule_row(elm){
		var ctable = $(elm).closest('table.thwepo_condition_set');
		var rtable = $(elm).closest('table.thwepo_rule');
		
		$(elm).closest('tr.thwepo_condition').remove();
		
		var cSize = ctable.find('tbody tr.thwepo_condition').size();
		if(cSize == 0){
			ctable.closest('tr.thwepo_condition_set_row').remove();
		}
		
		rSize = rtable.find('tbody tr.thwepo_condition_set_row').size();
		if(cSize == 0 && rSize == 0){
			rtable.find('tbody').append(CONDITION_SET_HTML);
		}
	}
		
	function is_valid_condition(condition){
		if(condition["subject"] && condition["comparison"]){
			return true;
		}
		return false;
	}
	
	function get_conditional_rules(elm){
		var conditionalRules = [];
		$(elm).find("#thwepo_conditional_rules tbody tr.thwepo_rule_set_row").each(function() {
			var ruleSet = [];
			$(this).find("table.thwepo_rule_set tbody tr.thwepo_rule_row").each(function() {
				var rule = [];															 
				$(this).find("table.thwepo_rule tbody tr.thwepo_condition_set_row").each(function() {
					var conditions = [];
					$(this).find("table.thwepo_condition_set tbody tr.thwepo_condition").each(function() {
						condition = {};
						condition["subject"] = $(this).find("select[name=i_rule_subject]").val();
						condition["comparison"] = $(this).find("select[name=i_rule_comparison]").val();
						condition["cvalue"] = $(this).find("select[name=i_rule_value]").val();
						//rule["op"] = $(this).find("input[name=i_rule_op]").val();
						if(is_valid_condition(condition)){
							conditions.push(condition);
						}
					});
					if(conditions.length > 0){
						rule.push(conditions);
					}
				});
				if(rule.length > 0){
					ruleSet.push(rule);
				}
			});
			if(ruleSet.length > 0){
				conditionalRules.push(ruleSet);
			}
		});
		
		var conditionalRulesJson = conditionalRules.length > 0 ? JSON.stringify(conditionalRules) : '';
		return conditionalRulesJson;
	}
		
	function populate_conditional_rules(form, conditionalRulesJson){
		var conditionalRulesHtml = "";
		if(conditionalRulesJson){
			try{
				var conditionalRules = $.parseJSON(conditionalRulesJson);
				if(conditionalRules){
					jQuery.each(conditionalRules, function() {
						var ruleSet = this;	
						var rulesHtml = '';
						
						jQuery.each(ruleSet, function() {
							var rule = this;
							var conditionSetsHtml = '';
							
							var y=0;
							var ruleSize = rule.length;
							jQuery.each(rule, function() {
								var conditions = this;								   	
								var conditionsHtml = '';
								
								var x=1;
								var size = conditions.length;
								jQuery.each(conditions, function() {
									var lastRow = (x==size) ? true : false;
									var conditionHtml = populate_condition_html(this, lastRow);
									if(conditionHtml){
										conditionsHtml += conditionHtml;
									}
									x++;
								});
								
								var firstRule = (y==0) ? true : false;
								var conditionSetHtml = populate_condition_set_html(conditionsHtml, firstRule);
								if(conditionSetHtml){
									conditionSetsHtml += conditionSetHtml;
								}
								y++;
							});
							
							var ruleHtml = populate_rule_html(conditionSetsHtml);
							if(ruleHtml){
								rulesHtml += ruleHtml;
							}
						});
						
						var ruleSetHtml = populate_rule_set_html(rulesHtml);
						if(ruleSetHtml){
							conditionalRulesHtml += ruleSetHtml;
						}
					});
				}
			}catch(err) {
				alert(err);
			}
		}
		
		if(conditionalRulesHtml){
			var conditionalRulesTable = form.find("#thwepo_conditional_rules tbody");
			conditionalRulesTable.html(conditionalRulesHtml);
			setup_enhanced_multi_select(conditionalRulesTable);
			
			conditionalRulesTable.find('tr.thwepo_condition').each(function(){
				$ruleVal = $(this).find("input[name=i_rule_value_hidden]").val();	
				$ruleVal = $ruleVal.split(",");													
				$(this).find("select[name=i_rule_value]").val($ruleVal).trigger("change");
			});
		}else{
			var conditionalRulesTable = form.find("#thwepo_conditional_rules tbody");
			conditionalRulesTable.html(RULE_SET_HTML);
			setup_enhanced_multi_select(conditionalRulesTable);
		}
	}
	
	function populate_rule_set_html(ruleHtml){
		var html = '';
		if(ruleHtml){
			html += '<tr class="thwepo_rule_set_row"><td><table class="thwepo_rule_set" width="100%"><tbody>';
			html += ruleHtml;
			html += '</tbody></table></td></tr>';
		}
		return html;
	}
	
	function populate_rule_html(conditionSetHtml){
		var html = '';
		if(conditionSetHtml){
			html += '<tr class="thwepo_rule_row"><td><table class="thwepo_rule" width="100%" style=""><tbody>';
			html += conditionSetHtml;
			html += '</tbody></table></td></tr>';
		}
		return html;
	}
	
	function populate_condition_set_html(conditionsHtml, firstRule){
		var html = '';
		if(conditionsHtml){
			if(firstRule){
				html += '<tr class="thwepo_condition_set_row"><td><table class="thwepo_condition_set" width="100%" style=""><tbody>';
				html += conditionsHtml;
				html += '</tbody></table></td></tr>';
			}else{
				html += '<tr class="thwepo_condition_set_row"><td><table class="thwepo_condition_set" width="100%" style=""><thead>'+OP_OR_HTML+'</thead><tbody>';
				html += conditionsHtml;
				html += '</tbody></table></td></tr>';
			}
		}
		return html;
	}
	
	function populate_condition_html(condition, lastRow){
		var html = '';
		if(condition){
			var selectedSubjProd = condition.subject === "product" ? "selected" : "";
			var selectedSubjCat = condition.subject === "category" ? "selected" : "";
			
			var selectedCompjE = condition.comparison === "equals" ? "selected" : "";
			var selectedCompjNE = condition.comparison === "not_equals" ? "selected" : "";
			
			var valueHtml = '<input type="hidden" name="i_rule_value_hidden" value="'+condition.cvalue+'"/>';
			if(condition.subject === "product"){
				valueHtml += $("#thwepo_product_select").html();
			}else if(condition.subject === "category"){
				valueHtml += $("#thwepo_product_cat_select").html();
			}else{
				valueHtml += '<input type="text" name="i_rule_value" style="width:200px;" value="'+condition.cvalue+'"/>';
			}
			
			var actionsHtml = lastRow ? OP_HTML : OP_AND_HTML;
			
			html += '<tr class="thwepo_condition">';
			html += '<td width="25%"><select name="i_rule_subject" style="width:200px;" onchange="thwepoRuleSubjectChangeListner(this)" value="'+condition.subject+'">';
			html += '<option value=""></option><option value="product" '+selectedSubjProd+'>Product</option><option value="category" '+selectedSubjCat+'>Category</option>';
			html += '</select></td>';		
			html += '<td width="25%"><select name="i_rule_comparison" style="width:200px;" value="'+condition.comparison+'">';
			html += '<option value=""></option><option value="equals" '+selectedCompjE+'>Equals to/ In</option>';
			html += '<option value="not_equals" '+selectedCompjNE+'>Not Equals to/ Not in</option>';
			html += '</select></td>';
			html += '<td width="25%" class="thwepo_condition_value">'+ valueHtml +'</td>';
			html += '<td>'+ actionsHtml+'</td></tr>';							
		}
		return html;
	}
   /*------------------------------------
	*---- PRODUCT FIELDS - END -----------
	*------------------------------------*/

   /*---------------------------------------
	* Remove fields functions - START
	*----------------------------------------*/
	_removeSelectedFields = function removeSelectedFields(){
		$('#thwepof_product_fields tbody tr').removeClass('strikeout');
		$('#thwepof_product_fields tbody input:checkbox[name=select_field]:checked').each(function () {
			var row = $(this).closest('tr');
			if(!row.hasClass("strikeout")){
				row.addClass("strikeout");
			}
			row.find(".f_deleted").val(1);
			row.find(".f_edit_btn").prop('disabled', true);
	  	});	
	}
   /*---------------------------------------
	* Remove fields functions - END
	*----------------------------------------*/

   /*---------------------------------------
	* Enable or Disable fields functions - START
	*----------------------------------------*/
	_enableDisableSelectedFields = function enableDisableSelectedFields(enabled){
		$('#thwepof_product_fields tbody input:checkbox[name=select_field]:checked').each(function(){
			var row = $(this).closest('tr');

			if(enabled == 0){
				if(!row.hasClass("thwepof-disabled")){
					row.addClass("thwepof-disabled");
				}
			}else{
				row.removeClass("thwepof-disabled");				
			}

			row.find(".f_edit_btn").prop('disabled', enabled == 1 ? false : true);
			row.find(".td_enabled").html(enabled == 1 ? '<span class="status-enabled tips">Yes</span>' : '-');
			row.find(".f_enabled").val(enabled);
	  	});	
	}
   /*---------------------------------------
	* Enable or Disable fields functions - END
	*----------------------------------------*/

	return {
		openNewFieldForm : _openNewFieldForm,
		openEditFieldForm : _openEditFieldForm,
		removeSelectedFields : _removeSelectedFields,
		enableDisableSelectedFields : _enableDisableSelectedFields,
		fieldTypeChangeListner : _fieldTypeChangeListner,
		selectAllFields : _selectAllFields,
		ruleSubjectChangeListner : ruleSubjectChangeListner,
		add_new_rule_row : _add_new_rule_row,
		remove_rule_row : _remove_rule_row,
   	};
}(window.jQuery, window, document));	

function thwepofOpenNewFieldForm(){
	thwepof_settings.openNewFieldForm();		
}

function thwepofOpenEditFieldForm(elm, rowId){
	thwepof_settings.openEditFieldForm(elm, rowId);		
}
	
function thwepofRemoveSelectedFields(){
	thwepof_settings.removeSelectedFields();
}

function thwepofEnableSelectedFields(){
	thwepof_settings.enableDisableSelectedFields(1);
}

function thwepofDisableSelectedFields(){
	thwepof_settings.enableDisableSelectedFields(0);
}

function thwepofFieldTypeChangeListner(elm){	
	thwepof_settings.fieldTypeChangeListner(elm);
}

function thwepofSelectAllProductFields(elm){
	thwepof_settings.selectAllFields(elm);
}

function thwepoRuleSubjectChangeListner(elm){
	thwepof_settings.ruleSubjectChangeListner(elm);
}

function thwepoAddNewConditionRow(elm, op){
	thwepof_settings.add_new_rule_row(elm, op);
}

function thwepoRemoveRuleRow(elm){
	thwepof_settings.remove_rule_row(elm);
}
