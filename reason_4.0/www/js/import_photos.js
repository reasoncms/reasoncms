/**
 * Manage display of upload fields for batch image uploads - Does two primary things:
 * 
 * 1. Makes sure the next upload field is shown anytime an upload field is updated
 * 2. On load, ensure there is an upload field without a file or error available
 *
 * @requires jQuery
 * @author Nathan White
 */

jQuery(function($) {
	var upload_rows = $("tr[id^='upload'][id$='Row']");
	var error_last_index = upload_rows.index($("tr.error:last"));
	var file_last_index = upload_rows.index($("tr:has(.uploaded_file):last"));
	
	var hide_after_index = Math.max(0, error_last_index, file_last_index + 1);
	
	upload_rows.each(function() {
		var index = $(upload_rows).index(this); // what is the index in the set of upload field rows?
		if (index > hide_after_index)
			$(this).hide();
		
		// Show the next element when the value of the INPUT in this row
		// changes.
		$("input[type=file]", this).change(function() {
			$(upload_rows).eq(index+1).show();
		});
	});
});
