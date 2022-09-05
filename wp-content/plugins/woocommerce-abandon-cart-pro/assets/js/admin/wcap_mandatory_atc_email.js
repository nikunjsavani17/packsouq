jQuery(function( $ ) {

	$('.wcap-switch-atc-modal-mandatory.wcap-toggle-atc-modal-mandatory').click(function(){

		var $switch_mandatory, state_mandatory, new_state_mandatory;

		$switch_mandatory = $(this);

		if ( $switch_mandatory.is('.wcap-loading') ){
			return;
		}

		state_mandatory = $switch_mandatory.attr( 'wcap-atc-switch-modal-mandatory' );

		new_state_mandatory = state_mandatory === 'on' ? 'off' : 'on';
		$switch_mandatory.addClass('wcap-loading');
		$switch_mandatory.attr( 'wcap-atc-switch-modal-mandatory', new_state_mandatory );

		if ( 'off' == new_state_mandatory ){
			$(".wcap_non_mandatory_modal_section_fields_div :input").attr("disabled", false);
		}else if ( 'on' == new_state_mandatory ){
			$(".wcap_non_mandatory_modal_section_fields_div :input").attr("disabled", true);
		}

		$.post( ajaxurl, {
			action    : 'wcap_toggle_atc_mandatory_status',
			new_state : new_state_mandatory
		}, function( wcap_atc_enable_response ) {
			$switch_mandatory.removeClass('wcap-loading');
		});
	});
});