// Javascript code to for the giving form 
//
// @author Mark Heiman

$(document).ready(function() {
	/** PageOne **/
	if ($("div#giftForm.pageOne").length)
	{
		toggle_recur_fields();
		$("input[type='checkbox']").not("#checkbox_specific_fund").not("#checkbox_match_gift").change(function(){ show_amount_fields(); });
		
		$("input[name='installment_type']").change(function(){ toggle_recur_fields(); });

		// Set the initial state for employer name field
		$("#employernameItem").hide(500);
		
		// Show/hide employer name based on match status
		$("input#checkbox_match_gift").change(function(){
				if ($("input#checkbox_match_gift:checked").val())
				$("#employernameItem").show(500);
			else
				$("#employernameItem").hide(500);
		});
		
		//$("input#checkbox_match_gift").change();
		$("input#checkbox_annual_fund").change();
		
		// Show/hide specific designations
		toggle_specific_designations();
		$("input[name='specific_fund']").change(function(){ toggle_specific_designations(); });
		$("input[name='norse_athletic_association']").change(function(){ toggle_specific_designations(); });
		$("input[name='other']").change(function(){ toggle_specific_designations(); });

		toggle_gift_prompt();
		$("#gift_promptElement").change(function(){ toggle_gift_prompt(); });

	}
	
	/** PageTwo **/
	if ($("div#giftForm.pageTwo").length) {
		set_name_field_prompt();
		$("input[name$='_name']").focus(function(){ clear_name_field_prompt($(this)); });
		$("input[name$='_name']").blur(function(){ set_name_field_prompt(); });
		$("form#disco_form").submit(function(){
			clear_name_field_prompt($("input[name='first_name']"));
			clear_name_field_prompt($("input[name='last_name']"));	
		});
		
		
		// Show class year when alum affiliation chosen
		toggle_class_year();
			
		$("input#checkbox_luther_affiliation_0").change(function(){ toggle_class_year(); });
		$("input#checkbox_luther_affiliation_3").change(function(){ toggle_class_year(); });
		

		// Show/hide and populate Country field based on state/province choice
		$("select#state_provinceElement").change(function(){ toggle_country_field("select#state_provinceElement","#countryItem" ); });
		
		// Set the initial state for the Country field
		$("select#state_provinceElement").change();
		$("#countryItem").hide(500);
	}

	/** PageThree **/
	if ($("div#giftForm.pageThree").length) {
		// Add the controls to open and close the gift detail.
		if ($("div#giftForm h3#yearlyTotalsHeading").length)
		{
			$("div#giftForm div#reviewGiftDetails").hide(500);
			
			$("div#giftForm div#reviewGiftOverview").append('<p><a id="showGiftDetails" href="#">Yearly totals for this gift</a></p>');
			$("div#giftForm #reviewGiftDetails").append('<a id="hideGiftDetails" href="#"><i class="fa fa-times"></i></a>');

			$("a#showGiftDetails").click(function(event){
				$("a#showGiftDetails").hide(500);
				$("div#reviewGiftDetails").show(500);
				event.preventDefault();
			});
		
			$("a#hideGiftDetails").click(function(event){
				$("a#showGiftDetails").show(500);
				$("div#reviewGiftDetails").hide(500);
				event.preventDefault();
			});
		}
		
		toggle_billing_address();
		
		$("input[name='billing_address']").change(function(){ toggle_billing_address(); });

		// Show/hide and populate Country field based on state/province choice
		$("select#billing_state_provinceElement").change(function(){ toggle_country_field("select#billing_state_provinceElement","#billingcountryItem"); });
		
		// Set the initial state for the Country field
		$("select#billing_state_provinceElement").change();
	}

	/** PageFour **/
	$("p.printConfirm").html("<input type='submit' value='"+ $("p.printConfirm").html() + "' />");
	$("p.printConfirm input").click(function(event){
		window.print();
		event.preventDefault();
	});
});

function set_name_field_prompt()
{
	if ($("input[name='first_name']").val() == '')
	{
		$("input[name='first_name']").addClass("unfocused_label").val('First');		
	}
	if ($("input[name='last_name']").val() == '')
	{
		$("input[name='last_name']").addClass("unfocused_label").val('Last');		
	}
	
	if ($("input[name='spouse_first_name']").val() == '')
	{
		$("input[name='spouse_first_name']").addClass("unfocused_label").val('First');		
	}
	if ($("input[name='spouse_last_name']").val() == '')
	{
		$("input[name='spouse_last_name']").addClass("unfocused_label").val('Last');		
	}

}

function clear_name_field_prompt(field)
{
	if (field.val() == 'First' || field.val() == 'Last')
	{
		field.removeClass("unfocused_label").val('');		
	}
}

function toggle_class_year()
{
	if ($("input#checkbox_luther_affiliation_0:checked").val() ||
	    $("input#checkbox_luther_affiliation_3:checked").val())
                $("#classyearItem").show(500);
        else
                $("#classyearItem").hide(500);
}


function toggle_country_field(stateElementSelector, countryItemSelector)
{
	// Show/hide and populate Country field based on state/province choice
	// If not US or Canada, show the Country field
	if ($(stateElementSelector).val() == "XX")
	{
   	    $(countryItemSelector + " select").val('');
    	$("#countryItem").show(500);
   		$("#billingcountryItem").show(500);
	}
	// If US or Canada, populate Country but hide it
	else
	{
	    //$(countryItemSelector).hide(500);
	    // If a Canadian province...
	    if (/^(?:AB|BC|MB|NB|NL|NT|NS|NU|ON|PE|QC|SK|YT)$/.test($(stateElementSelector).val())) 
		$(countryItemSelector + " select").val("CAN");
	    // If anything else (other than unset)
	    else if ($(stateElementSelector).val() != "")
		$(countryItemSelector + " select").val('USA');
	}
}

function toggle_recur_fields()
{
	if (!$("input[name='installment_type']:checked").val() ||
	     $("input[name='installment_type']:checked").val() == 'Onetime')
	{
		$("#recurgroupItem").hide(500);
	} else {
		$("#recurgroupItem").show(500);
	}
}

function toggle_recur_fields_old()
{
	if (!$("input[name='installment_type']:checked").val() ||
	     $("input[name='installment_type']:checked").val() == 'Onetime')
	{
		$("#installmentstartdateItem").hide(500);	
		$("#installmentenddateItem").hide(500);	
	} else {
		$("#installmentstartdateItem").show(500);	
		$("#installmentenddateItem").show(500);	
	}
}

function toggle_billing_address()
{
	if (!$("input[name='billing_address']:checked").val() ||
	     $("input[name='billing_address']:checked").val() == 'entered')
	{
		$("#billingstreetaddressItem").hide(500);	
		$("#billingcityItem").hide(500);	
		$("#billingstateprovinceItem").hide(500);	
		$("#billingzipItem").hide(500);	
		$("#billingcountryItem").hide(500);	
	} else {
		$("#billingstreetaddressItem").show(500);	
		$("#billingcityItem").show(500);	
		$("#billingstateprovinceItem").show(500);	
		$("#billingzipItem").show(500);	
		$("#billingcountryItem").show(500);
		$("select#billing_state_provinceElement").change();
	}
}

function toggle_specific_designations()
{
	if ( $("input[name='specific_fund']:checked").val() || $("input[name='specific_fund']:checked").val() == 'true' ) {
		$("#designationnoteItem").show(500);
		$("#norseathleticassociationItem").show(500);
		$("#baseballgroupItem").show(500);
		$("#softballgroupItem").show(500);
		$("#scholarshipgroupItem").show(500);
		$("#othergroupItem").show(500);
		$("#commentsspecialinstructionsItem").show(500);
	} else {
		$("#designationnoteItem").hide(500);
		$("#norseathleticassociationItem").hide(500);
		$("#baseballgroupItem").hide(500);
		$("#softballgroupItem").hide(500);
		$("#scholarshipgroupItem").hide(500);
		$("#othergroupItem").hide(500);
		$("#commentsspecialinstructionsItem").hide(500);
	}
	if ( $("input[name='norse_athletic_association']:checked").val() || $("input[name='norse_athletic_association']:checked").val() == "true" )
	{
		$("#naagroupItem").show(500);
	} else {
		$("#naagroupItem").hide(500);
	}
}

function toggle_gift_prompt() {
	if ($("#gift_promptElement").val() == 'staff_visit') {
		$("#giftpromptdetailsItem").show(500);
		$("#gift_prompt_detailsElement").parent().prev().html('Name of development officer');
	}
	else if ($("#gift_promptElement").val() == 'other') {
		$("#giftpromptdetailsItem").show(500);
		$("#gift_prompt_detailsElement").parent().prev().html('Please tell us more');
	} else {
		$("#giftpromptdetailsItem").hide(500);
		$("#gift_prompt_detailsElement").val('');
	}
}

function show_amount_fields() {
	amount_elements = $(".inlineElement").find("input[id*='amountElement']").parent().hide();
	// if more than one designation is checked,
	// show designation amount boxes for those checked.
	// Look for all checkboxes ignore matching gifts and specific funds checkboxes
	checked_boxes = $("input[type='checkbox']:checked").not("#checkbox_specific_fund").not("#checkbox_match_gift");
	checked_boxes.each(function(){
		if ( checked_boxes.length > 1 ) {
			amount_selector = $("#"+$(this).prop('name')+"_amountElement");
			$(amount_selector).parent().show();
		} else {
			amount_selector = $("#"+$(this).prop('name')+"_amountElement");
			$(amount_selector).parent().hide();
		}
	});
}
