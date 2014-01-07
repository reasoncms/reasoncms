// Javascript code to for the transcript requests form
//
// @author Steve Smith 2/8/2011

$(document).ready(function() {
    //hide  fields
   // hide_field('official_paper_comment');
    hide_field('unofficial_email');
    hide_field('unofficial_address');
    hide_field('official_email');
    hide_field('institution_name');
    hide_field('institution_attn');
    hide_field('institution_email');
    hide_field('address');
    hide_field('city');
    hide_field('state_province');
    hide_field('zip');
    hide_field('country');

    //delivery choices
    showDeliveryInfo();
    $("input[name='delivery_type']").change(function(){
        showDeliveryInfo();
    });
    $("input[name='deliver_to']").change(function(){
        showDeliveryInfo();
    });

    toggle_unofficial_address();
    $("input[name='unofficial']").change(function(){ 
        toggle_unofficial_address()
    });
        
    toggle_billing_address();
    $("input[name='billing_address']").change(function(){
        toggle_billing_address()
        });

    // Show/hide and populate Country field based on state/province choice
    $("select#state_provinceElement").change(function(){
        toggle_country_field("select#state_provinceElement","tr#countryRow" )});

    // Set the initial state for the Country field
    $("select#state_provinceElement").change();
    $("#countryRow").hide();
});

function hide_field(element)
{
    element = "tr#"+element.replace(/_/g,"")+"Row";
    $(element).hide();
}
function show_field(element)
{
    element = "tr#"+element.replace(/_/g,"")+"Row";
    $(element).show();
}
function showDeliveryInfo()
{
    // if sending paper, display only address fields
   if ($("input[name='delivery_type']:checked").val() == 'postal'
      && $("input[name=deliver_to]:checked").val() == 'Your address')
    {
        hide_field('institution_name');
        hide_field('institution_attn');
        hide_field('official_email');
        show_field('address');
        show_field('city');
        show_field('state_province');
        show_field('zip');
        show_field('country');
        $('#official_emailElement').val('');
    }
    if ($("input[name='delivery_type']:checked").val() == 'email'
      && $("input[name=deliver_to]:checked").val() == 'Your address')
    {
        var email = $('#emailElement').val();
        $('#official_emailElement').val(email);
        hide_field('institution_name');
        hide_field('institution_attn');
        show_field('official_email');
        hide_field('address');
        hide_field('city');
        hide_field('state_province');
        hide_field('zip');
        hide_field('country');
    }
    if ($("input[name='delivery_type']:checked").val() == 'postal'
      && $("input[name=deliver_to]:checked").val() == 'institution')
    {
        show_field('institution_name');
        show_field('institution_attn');
        hide_field('official_email');
        show_field('address');
        show_field('city');
        show_field('state_province');
        show_field('zip');
        show_field('country');
        $('#official_emailElement').val('');
    }
    if ($("input[name='delivery_type']:checked").val() == 'email'
      && $("input[name=deliver_to]:checked").val() == 'institution')
    {
        show_field('institution_name');
        show_field('institution_attn');
        show_field('official_email');
        hide_field('address');
        hide_field('city');
        hide_field('state_province');
        hide_field('zip');
        hide_field('country');
        $('#official_emailElement').val('');
    }
}
function toggle_unofficial_address() {
    var unofficial_type = $("input[name=unofficial]:checked").val();
    switch(unofficial_type){
        case 'email':
            hide_field('unofficial_address');
        break;
        case 'postal':
            show_field('unofficial_address');
        break;
        default:
            hide_field('unofficial_address');
        break;
    }
}
function toggle_billing_address() {
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