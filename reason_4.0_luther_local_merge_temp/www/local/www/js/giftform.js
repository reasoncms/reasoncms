// Javascript code to for the giving form 
//
// @author Mark Heiman

$(document).ready(function() {
	/** PageOne **/
	toggle_recur_fields();
	
	$("input[name='installment_type']").change(function(){toggle_recur_fields()});

	// Set the initial state for employer name field
	$("tr#employernameRow").hide();
	
	// Show/hide employer name based on match status
	$("input#checkbox_match_gift").change(function(){
			if ($("input#checkbox_match_gift:checked").val())
			$("tr#employernameRow").show();
		else
			$("tr#employernameRow").hide();
	});
	
	//$("input#checkbox_match_gift").change();
	$("input#checkbox_annual_fund").change();
	
	// Show/hide specific designations
	toggle_specific_designations();
	$("input#checkbox_specific_fund").change(function(){toggle_specific_designations()});
	$("input#checkbox_norse_athletic_association").change(function(){toggle_specific_designations()});
	
	
	/** PageTwo **/
	
	set_name_field_prompt();
	$("input[name$='_name']").focus(function(){clear_name_field_prompt($(this))});
	$("input[name$='_name']").blur(function(){set_name_field_prompt()});
	$("form#disco_form").submit(function(){
		clear_name_field_prompt($("input[name='first_name']"));
		clear_name_field_prompt($("input[name='last_name']"));	
	});
	
	
	// Show class year when alum affiliation chosen
	toggle_class_year();
		
	$("input#checkbox_luther_affiliation_0").change(function(){toggle_class_year()});
	$("input#checkbox_luther_affiliation_3").change(function(){toggle_class_year()});
	

	// Show/hide and populate Country field based on state/province choice
	$("select#state_provinceElement").change(function()
		{toggle_country_field("select#state_provinceElement","tr#countryRow" )});
	
	// Set the initial state for the Country field
	$("select#state_provinceElement").change();
	$("#countryRow").hide();
	
	/** PageThree **/
	
	// Add the controls to open and close the gift detail.
	if ($("div#giftForm h3#yearlyTotalsHeading").length)
	{
		$("div#giftForm div#reviewGiftDetails").hide();
		
		$("div#giftForm div#reviewGiftOverview p").append('<p><a id="showGiftDetails" href="#">Yearly totals for this gift</a></p>');
		$("div#giftForm h3#yearlyTotalsHeading").append('<a id="hideGiftDetails" href="#">Close</a>');

		$("a#showGiftDetails").click(function(event){
			$("a#showGiftDetails").hide();
			$("div#reviewGiftDetails").show();
			event.preventDefault();
		});
	
		$("a#hideGiftDetails").click(function(event){
			$("a#showGiftDetails").show();
			$("div#reviewGiftDetails").hide();
			event.preventDefault();
		});
	}
	
	toggle_billing_address();
	
	$("input[name='billing_address']").change(function(){toggle_billing_address()});

	// Show/hide and populate Country field based on state/province choice
	$("select#billing_state_provinceElement").change(function()
		{toggle_country_field("select#billing_state_provinceElement","tr#billingcountryRow" )});
	
	// Set the initial state for the Country field
	$("select#billing_state_provinceElement").change();
	
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
                $("tr#classyearRow").show();
        else
                $("tr#classyearRow").hide();
}

/*
function toggle_class_year_note()
{
	if (($("input[name='spouse_first_name']").val() == "First" ||
		$("input[name='spouse_last_name']").val() == "Last") &&
		$("input#checkbox_luther_affilation_0:checked").val() ||
		$("input#checkbox_luther_affilation_3:checked").val())
	{
		$("tr#classyearnoteRow").hide();
	} else {
		$("tr#classyearnoteRow").show();
	}
}
*/

function toggle_country_field(stateElementSelector, countryRowSelector)
{
	// Show/hide and populate Country field based on state/province choice
	// If not US or Canada, show the Country field
	if ($(stateElementSelector).val() == "XX")
	{
   	    $(countryRowSelector + " select").val('');
    	$("#countryRow").show();
   		$("tr#billingcountryRow").show();
	}
	// If US or Canada, populate Country but hide it
	else
	{
	    //$(countryRowSelector).hide();
	    // If a Canadian province...
	    if (/^(?:AB|BC|MB|NB|NL|NT|NS|NU|ON|PE|QC|SK|YT)$/.test($(stateElementSelector).val())) 
		$(countryRowSelector + " select").val("CAN");
	    // If anything else (other than unset)
	    else if ($(stateElementSelector).val() != "")
		$(countryRowSelector + " select").val('USA');
	}
}

function toggle_recur_fields()
{
	if (!$("input[name='installment_type']:checked").val() ||
	     $("input[name='installment_type']:checked").val() == 'Onetime')
	{
		$("input#installment_start_date").parent().parent().hide();	
		$("select#installment_end_dateElement").parent().parent().hide();	
	} else {
		$("input#installment_start_date").parent().parent().show();	
		$("select#installment_end_dateElement").parent().parent().show();	
	}
}

function toggle_recur_fields_old()
{
	if (!$("input[name='installment_type']:checked").val() ||
	     $("input[name='installment_type']:checked").val() == 'Onetime')
	{
		$("tr#installmentstartdateRow").hide();	
		$("tr#installmentenddateRow").hide();	
	} else {
		$("tr#installmentstartdateRow").show();	
		$("tr#installmentenddateRow").show();	
	}
}

function toggle_billing_address()
{
	if (!$("input[name='billing_address']:checked").val() ||
	     $("input[name='billing_address']:checked").val() == 'entered')
	{
		$("tr#billingstreetaddressRow").hide();	
		$("tr#billingcityRow").hide();	
		$("tr#billingstateprovinceRow").hide();	
		$("tr#billingzipRow").hide();	
		$("tr#billingcountryRow").hide();	
	} else {
		$("tr#billingstreetaddressRow").show();	
		$("tr#billingcityRow").show();	
		$("tr#billingstateprovinceRow").show();	
		$("tr#billingzipRow").show();	
		$("tr#billingcountryRow").show();
		$("select#billing_state_provinceElement").change();
	}
}

function toggle_specific_designations()
{
	if ($("input#checkbox_specific_fund:checked").val() ||
		$("input#checkbox_specific_fund:checked").val() == 'entered')
		{
			$("tr#designationnoteRow").show();
			$("tr#aquaticcenterRow").show();
			$("tr#sesqscholarshipfundRow").show();
			$("tr#sesqstudyabroadfundRow").show();
			$("tr#transformteachingfundRow").show();
			$("tr#sustainablecommunitiesRow").show();
			$("tr#norseathleticassociationRow").show();
			$("tr#otherdesignationnoteRow").show();
			$("tr#otherdesignationdetailsRow").show();
		}
		else
		{
			$("tr#designationnoteRow").hide();
			$("tr#aquaticcenterRow").hide();
			$("tr#sesqscholarshipfundRow").hide();
			$("tr#sesqstudyabroadfundRow").hide();
			$("tr#transformteachingfundRow").hide();
			$("tr#sustainablecommunitiesRow").hide();
			$("tr#norseathleticassociationRow").hide();
			$("tr#otherdesignationnoteRow").hide();
			$("tr#otherdesignationdetailsRow").hide();			
		}
		
	if ($("input#checkbox_norse_athletic_association:checked").val()||
		$("input#checkbox_norse_athletic_association:checked").val() == "entered")
		{
			$("tr#naadesignationdetailsRow").show();
		} else {
			$("tr#naadesignationdetailsRow").hide();
		}
}
