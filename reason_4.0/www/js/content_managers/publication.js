$(document).ready(function()
{
	if($('#checkbox_allow_front_end_posting').is(':checked')) {
		$('[id^="notifyuponpost"]').show();
		$('[id^="holdpostsforreview"]').show();
	} else {
		$('[id^="notifyuponpost"]').hide();
		$('[id^="holdpostsforreview"]').hide();	
	}	
	$('#checkbox_allow_front_end_posting').click(function() {
		if($(this).is(':checked')) {
			$('[id^="notifyuponpost"]').show();
			$('[id^="holdpostsforreview"]').show();
		} else {
			$('[id^="notifyuponpost"]').hide();
			$('[id^="holdpostsforreview"]').hide();	
		}	
	});
	if($('#checkbox_allow_comments').is(':checked')) {
		$('[id^="notifyuponcomment"]').show();
		$('[id^="holdcommentsforreview"]').show();
	} else {
		$('[id^="notifyuponcomment"]').hide();
		$('[id^="holdcommentsforreview"]').hide();	
	}	
	$('#checkbox_allow_comments').click(function() {
		if($(this).is(':checked')) {
			$('[id^="notifyuponcomment"]').show();
			$('[id^="holdcommentsforreview"]').show();
		} else {
			$('[id^="notifyuponcomment"]').hide();
			$('[id^="holdcommentsforreview"]').hide();	
		}	
	});
	if($('#reminder_daysElement').val()>0) {
		$('[id^="reminderemails"]').show();
	} else {
		$('[id^="reminderemails"]').hide();	
	}	
	$('#reminder_daysElement').keyup(function() {
		if($(this).val()>0) {
			$('[id^="reminderemails"]').show();
		} else {
			$('[id^="reminderemails"]').hide();
		}	
	});			
});
