/** 
 * group.js - Javascript for group content manager
 *
 * @author Mark Heiman
 * @requires jQuery
 */

var limit_top =  "tr#limitcommentRow";
var details_top =  "tr#audiencescommentRow";
var name_top = "tr#namecommentRow";
 
$(document).ready(function()
{	
	$('input[name="require_authentication"]').change(function(){ handle_require_auth();});
	$('input[name="limit_authorization"]').change(function(){ handle_limit_auth();});

	// Toggle visibility on initial page load based on existing values
	handle_require_auth();
	handle_limit_auth();
});


function handle_require_auth()
{
	if ($('input[name="require_authentication"]:checked').val() == "true")
	{
		toggle_range(limit_top, details_top, "show");
	}
	else
	{
		toggle_range(limit_top, name_top, "hide");
		
		// Set the next radio choice to the (closed) default value
		$('input[name="limit_authorization"]').filter('[value="false"]').attr('checked', true);
	}
	
}

function handle_limit_auth()
{
	if ($('input[name="limit_authorization"]:checked').val() == "true")
		toggle_range(details_top, name_top, "show");
	else
		toggle_range(details_top, name_top, "hide");
}


/**
 * Hide or show a range of sibling elements
 *
 * @param start The selector for the first element in the range
 * @param end_before The selector for the element after the last in the range
 * @param action hide|show|toggle
 */
function toggle_range(start, end_before, action)
{
	if ((action == "show" ) || (action == "toggle" && $(start).css('display') == "none"))
		$(start).show();
	else
		$(start).hide();
	
	$(start).nextUntil(end_before).each(function()
	{
		if ((action == "show" ) || (action == "toggle" && $(this).css('display') == "none"))
			$(this).show();
		else
			$(this).hide();
	});
}
