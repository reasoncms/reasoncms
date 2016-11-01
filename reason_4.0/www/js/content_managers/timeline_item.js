var rowIds = {'other' 			  : 'tr#othermediaRow', 
			  'reason_image' 	  : 'tr#imageRow',
			  'reason_media_work' : 'tr#mediaworkRow',
			  'reason_location'   : 'tr#locationRow'};

$(document).ready(function() {	
	hideAll();
	$(rowIds[$('select#mediaElement').val()]).show();
	
	$('select#mediaElement').change(function() {
		hideAll();
		$(rowIds[$(this).val()]).show();
	});
});

function hideAll() {
	for (var row in rowIds) {
		$(rowIds[row]).hide();
	}
}