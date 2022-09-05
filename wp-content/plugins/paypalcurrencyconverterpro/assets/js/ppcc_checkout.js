/*
Place this, or a subset of it, inside PayPal Payment description:

Cart Total: <span class="ppcc_cart_total" /><br>
Shipping Total: <span class="ppcc_shipping_total" /><br>
Handling fee <span class="ppcc_handling_percentage" />% plus <span class="ppcc_handling_amount" /> fixed.<br>
Order Total Tax: <span class="ppcc_tax_total" /><br>
Order Total inclusive Tax: <span class="ppcc_total_order_inc_tax" /><br>
Conversion Rate: <span class="ppcc_cr" />

*/


jQuery(document).ready(function(){
	jQuery(document.body).on('change', 'input[name="payment_method"]', function() {
	   jQuery('body').trigger('update_checkout');
	});
});



jQuery( document ).ajaxComplete(function(){  


	// This Values come initially by the session
	jQuery('.ppcc_cart_total').html(ppcc_data.cart_total);
	jQuery('.ppcc_tax_total').html(ppcc_data.tax_total);
	jQuery('.ppcc_shipping_total').html(ppcc_data.shipping_total);
	jQuery('.ppcc_total_order_inc_tax').html(ppcc_data.total_order_inc_tax);
	jQuery('.ppcc_cr').html(ppcc_data.cr);
	jQuery('.ppcc_handling_percentage').html(ppcc_data.handling_percentage);
	jQuery('.ppcc_handling_amount').html(ppcc_data.handling_amount);

	// The following code retrieves and recalculates the changes made in the checkout preview:

	if(jQuery("tr.shipping td span").length){
		var ppcc_shipping_total = PPCCtoNumber(jQuery("tr.shipping td span").contents()[1].data) * ppcc_data.crval;
		jQuery('.ppcc_shipping_total').html(ppcc_shipping_total.toFixed(2) + ' ' + ppcc_data.target_currency);
	}
	if(jQuery("tr.tax-rate td span").length){
		var tax_rate = PPCCtoNumber(jQuery("tr.tax-rate td span").contents()[1].data) * ppcc_data.crval;
		jQuery('.ppcc_tax_total').html(tax_rate.toFixed(2) + ' ' + ppcc_data.target_currency);
	}
	if(jQuery("tr.order-total td span").length){
		var order_total = PPCCtoNumber(jQuery("tr.order-total td span").contents()[1].data) * ppcc_data.crval;
		jQuery('.ppcc_total_order_inc_tax').html(order_total.toFixed(2) + ' ' + ppcc_data.target_currency);
	}

	// Unfortunately WooCommerc does not provide a filter to retrieve the preview values only(does it?). It uses its own wc-ajax mode to fetch the complete html code for the checkout preview.

});


function PPCCtoNumber(str){

	// wc_get_price_thousand_separator	
	// wc_get_price_decimal_separator
	// wc_get_price_decimals

	if(ppcc_data.wc_get_price_decimals > 0){
		var decimals = str.substring(str.length-ppcc_data.wc_get_price_decimals)
		var amount = str.substring(0, str.length-ppcc_data.wc_get_price_decimals)
		return Number(amount.replace(/[^0-9]+/g,"") + '.' + decimals);
	}
	else
	{
		return Number(amount.replace(/[^0-9]+/g,""));	
	}	
}


