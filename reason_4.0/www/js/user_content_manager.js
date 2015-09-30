/**
 * If the authoritative source is Reason, we want to show all the user information fields, otherwise we do not.
 *
 * @author Nathan White
 * @requires jQuery
 */
 
$(document).ready(function()
{
	var authoritative_source_element = "#user_authoritative_sourceElement";
	var visibility_toggle_rows = new Array("#usersurnameRow",
											   "#usergivennameRow",
											   "#useremailRow",
											   "#userphoneRow",
											   "#passwordRow",
											   "#confirmpasswordRow");
	function toggleVisibility()
	{
		if ($(authoritative_source_element).val().toLowerCase() == "reason")
		{
			$(visibility_toggle_rows.join(",")).show();
		}
		else $(visibility_toggle_rows.join(",")).hide();
	}
	toggleVisibility(); // run it once to hide or show fields as appropriate
	$(authoritative_source_element).change(toggleVisibility); // run it again when value changes
});