// Javascript code to for the dorian JH camps form
//
// @author Lucas Welper 1/27/2011

$(document).ready(function() {
	/** PageTwo **/
	$("input[name='band_participant']").change(function(){toggle_fields('band_participant','band_instrument')});
});

function toggle_fields(trigger, element)
{
        element = element.replace("_","");

        if ($("input#"+trigger+":checked").val()){
            $("tr#"+element+"Row").show();
        }else{
            $("tr#"+element+"Row").hide();
        }
}