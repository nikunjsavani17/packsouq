jQuery(document).ready(function() {
	jQuery( ".settings-error" ).hide();

	jQuery('#selected_currency').val(currency() + ' ' + ppcc_data.target_currency + '/' + ppcc_data.source_currency);
	if (jQuery('#cr').val()==currency()){
		jQuery('#cr').css('color', 'green');
	}else{
		jQuery('#cr').css('color', 'red');
		jQuery( ".settings-error" ).show();
	}
});
jQuery(function(){
	// Tooltips
	jQuery(".tips, .help_tip, .woocommerce-help-tip").tipTip({
    	'attribute' : 'data-tip',
    	'fadeIn' : 50,
    	'fadeOut' : 50,
    	'delay' : 200
    });
});

//Change target Currency
	jQuery('#target_cur').change(function() {
		jQuery(this).parents('form').submit();
	});
//Accept Currency
	jQuery('#selected_currency').click(function() {
	jQuery('#cr').val(currency());
	jQuery('#cr').css('color', 'green');
	});		

function currency(){
	//var currate=jQuery('#currency_converter_result span.bld').text().replace(/[^\d\.]/g, '');
	var currate=ppcc_data.amount;
	//currate = Math.round(currate * 1000) / 1000000;
	//jQuery('#cr').val(currate);
	return currate;
}

function oc_currency(){
	//var currate=jQuery('#currency_converter_result span.bld').text().replace(/[^\d\.]/g, '');
	var currate=ppcc_data.amount;
	//currate = Math.round(currate * 1000) / 1000000;
	//jQuery('#cr').val(crate);
	return currate;
}

// make custom currency fields mandatory if used
jQuery('#ucc').click(function(){
    if (this.checked) {
		jQuery('#customcurrency').toggle();
		jQuery('#ccc').attr("required",true).attr("hidden",false);
		jQuery('#ccs').attr("required",true).attr("hidden",false);
		jQuery('#ccn').attr("required",true).attr("hidden",false);
    }else{
		jQuery('#customcurrency').hide();
		jQuery('#ccc').attr("required",false).attr("hidden",true);
		jQuery('#ccs').attr("required",false).attr("hidden",true);
		jQuery('#ccn').attr("required",false).attr("hidden",true);
	}
}) 