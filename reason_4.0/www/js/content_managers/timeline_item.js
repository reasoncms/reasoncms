$(document).ready(function() {	
	$("tr#othermediaRow").hide();
	
	$("select#mediaElement").change(function() {
		if ($(this).val() == "other") {
			$("tr#othermediaRow").show();
		} else {
			 $("tr#othermediaRow").hide();
		}
	});
});


