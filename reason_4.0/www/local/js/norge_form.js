// Javascript code to for the transcript requests form
//
// @author Steve Smith 2/8/2011

$(document).ready(function() {
        //hide  fields
        hide_field('school');

        //show school field
        showSchool();
        $("#radio_registration_type_2").change(function(){showSchool()});

        toggle_billing_address();
	$("input[name='billing_address']").change(function(){toggle_billing_address()});
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
function showSchool()
{
    if ($("input[name=registration_type]:checked").val() == 'Student'){
        show_field('school');
    } else {
        hide_field('school');
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