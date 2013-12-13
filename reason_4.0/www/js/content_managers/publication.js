$(document).ready(function()
{
	if($('#checkbox_allow_front_end_posting').is(':checked')) {
		$("#notifyuponpostRow").show();
		$("#holdpostsforreviewRow").show();
	} else {
		$("#notifyuponpostRow").hide();
		$("#holdpostsforreviewRow").hide();	
	}	
	$('#checkbox_allow_front_end_posting').click(function() {
		if($(this).is(':checked')) {
			$("#notifyuponpostRow").show();
			$("#holdpostsforreviewRow").show();
		} else {
			$("#notifyuponpostRow").hide();
			$("#holdpostsforreviewRow").hide();	
		}	
	});
	if($('#checkbox_allow_comments').is(':checked')) {
		$("#notifyuponcommentRow").show();
		$("#holdcommentsforreviewRow").show();
	} else {
		$("#notifyuponcommentRow").hide();
		$("#holdcommentsforreviewRow").hide();	
	}	
	$('#checkbox_allow_comments').click(function() {
		if($(this).is(':checked')) {
			$("#notifyuponcommentRow").show();
			$("#holdcommentsforreviewRow").show();
		} else {
			$("#notifyuponcommentRow").hide();
			$("#holdcommentsforreviewRow").hide();	
		}	
	});
	if($('#reminder_daysElement').val()>0) {
		$("#reminderemailsRow").show();
	} else {
		$("#reminderemailsRow").hide();	
	}	
	$('#reminder_daysElement').keyup(function() {
		if($(this).val()>0) {
			$("#reminderemailsRow").show();
		} else {
			$("#reminderemailsRow").hide();
		}	
	});			
});
