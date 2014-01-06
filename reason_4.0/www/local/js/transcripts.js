// Javascript code to for the transcript requests form
//
// @author Steve Smith 2/8/2011

$(document).ready(function() {
    //hide  fields
   // hide_field('official_paper_comment');
    hide_field('official_escrip_comment');
    //hide_field('number_of_official');
    hide_field('institution_name');
    hide_field('institution_attn');
    hide_field('institution_email');
    hide_field('address');
    hide_field('city');
    hide_field('state_province');
    hide_field('zip');
    hide_field('country');

    //type choices
//    showTranscriptTypeBoxes();
//    $("#radio_official_type_0").change(function(){
//        showTranscriptTypeBoxes();
//    });
//    $("#radio_official_type_1").change(function(){
//        showTranscriptTypeBoxes();
//    });
    //delivery choices
    showDeliveryInfo();
    $("#radio_deliver_to_0").change(function(){
        showDeliveryInfo();
    });
    $("#radio_deliver_to_1").change(function(){
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
//function showTranscriptTypeBoxes()
//{
//    var id = $("input[name=official_type]:checked").attr('id');
//
//    switch(id){
//        case 'radio_official_type_0': //
//            show_field('number_of_official');
//            show_field('official_paper_comment');
//            showDeliveryInfo();
//            break;
//
//        case 'radio_official_type_1':  //deliver to a company or institution
//            // if sending eScrip show only Institution name and email address fields
//            // else show physical address fields and no email address field
//            hide_field('official_paper_comment');
//            hide_field('number_of_official');
//            showDeliveryInfo();
//            break;
//    }
//}
function showDeliveryInfo()
{

    var id = $("input[name=deliver_to]:checked").attr('id');


    switch(id){
        // deliver to the requestor //
        case 'radio_deliver_to_0':
            // if sending paper, display only address fields
            // no need to show email field as wwe have already collected it
//            if ($("input[name='official_type']:checked").val() == 'paper'){
                hide_field('institution_name');
                hide_field('institution_attn');
                hide_field('institution_email');
                show_field('address');
                show_field('city');
                show_field('state_province');
                show_field('zip');
                show_field('country');
//            } else {
//                hide_field('institution_name');
//                hide_field('institution_attn');
//                hide_field('institution_email');
//                hide_field('address');
//                hide_field('city');
//                hide_field('state_province');
//                hide_field('zip');
//                hide_field('country');
//            }
            break;

        // deliver to a company or institution //
        case 'radio_deliver_to_1':
            // if sending eScrip show only Institution name and email address fields
            // else show physical address fields and no email address field            
//            if ($("input[name='official_type']:checked").val() == 'eScrip'){
//                show_field('institution_name');
//                show_field('institution_attn');
//                show_field('institution_email');
//                hide_field('address');
//                hide_field('city');
//                hide_field('state_province');
//                hide_field('zip');
//                hide_field('country');
//            } else {
                show_field('institution_name');
                show_field('institution_attn');
                hide_field('institution_email');
                show_field('address');
                show_field('city');
                show_field('state_province');
                show_field('zip');
                show_field('country');
//            }
            break;
    }
}
function toggle_unofficial_address() {
    if ($("input[name='unofficial']:checked").val() == 'true') {
        show_field('unofficial_address');
        show_field('unofficial_email');
        show_field('unofficial_delivery_type');
        //$("input[name='deliver_to']:checked").val() = true;
        //$('#radio_deliver_to_0').checked = true;
    } else {
        hide_field('unofficial_address');
        hide_field('unofficial_email');
        hide_field('unofficial_delivery_type');
        //$("input[name='deliver_to']:checked") = false;
        //$('#radio_deliver_to_0').checked = false;
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