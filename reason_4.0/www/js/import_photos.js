/**
 * Manage display of upload fields for batch image uploads - Does two primary things:
 * 
 * 1. Makes sure the next upload field is shown anytime an upload field is updated
 * 2. On load, ensure there is an upload field without a file or error available
 *
 * @requires Jquery
 * @author Nathan White
 */

$(document).ready(function()
{
	//hide_fields(); // hide the fields to start with
	var upload_rows = $("tr[id^='upload'][id$='Row']");
	var error_last_index = $(upload_rows).index($("tr.error:last")[0]);
	var file_last_index = $(upload_rows).index($("tr:has(input[name*='tmp_file']):last")[0]);
	
	if (file_last_index > error_last_index) var hide_after_index = (file_last_index + 1);
	else if (error_last_index > "-1") var hide_after_index = error_last_index;
	else var hide_after_index = 0;
	
	$(upload_rows).each(function()
	{
		var index = $(upload_rows).index(this); // what is the index in the set of upload field rows?
		if (index > hide_after_index) $(this).hide();
		$(this).change(function() // change event to show next index
		{
			$(upload_rows).eq(index+1).show();
		});
	});
});