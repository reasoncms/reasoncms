var rowIds = {'other' 			  : '#othermediaItem', 
			  'reason_image' 	  : '#imageItem',
			  'reason_media_work' : '#mediaworkItem',
			  'reason_location'   : '#locationItem'};

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