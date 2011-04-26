$(document).ready(function(){
	//Hide rows by id
	$("#transfercollegeRow").css("display", "none");
	$("#meetfacultydetailsRow").css("display", "none");
	$("#meetsecondfacultydetailsRow").css("display", "none");
	$("#observeclassdetailsRow").css("display", "none");
	$("#meetcoachdetailsRow").css("display", "none");
	$("#musicauditiondetailsRow").css("display", "none");
	$("#meetmusicfacultydetailsRow").css("display", "none");
	$("#overnightnoteRow").css("display", "none");
	$("#overnightdayRow").css("display", "none");
	$("#overnightpriorarrivaltimeRow").css("display", "none");
        $("#emergencycontactRow").css("display","none");
        $("#emergencyphonenumberRow").css("display","none");



	// Add onclick handler to radiobuttons with name 'transfer'
	$("input[name='transfer']").change(function()
	{
		// If "Yes" is checked
		if ($("#radio_transfer_0").is(":checked"))
		{
			//show "transfer college" element
			$("#transfercollegeRow").show();
		}
		else
		{
			//otherwise, hide it
			$("#transfercollegeRow").hide();
		}
	})

	// Add onclick handler to checkbox w/id checkbox_meet_faculty
	$("#checkbox_meet_faculty").click(function()
	{
        // If checked
		if ($("#checkbox_meet_faculty").is(":checked"))
		{
			//show the hidden element(s)
			$("#meetfacultydetailsRow").show();
		}
		else
		{
			//otherwise, hide it
			$("#meetfacultydetailsRow").hide();
		}
	})

	// Add onclick handler to checkbox w/id checkbox_meet_second_faculty
	$("#checkbox_meet_second_faculty").click(function()
	{
        // If checked
		if ($("#checkbox_meet_second_faculty").is(":checked"))
		{
			//show the hidden element(s)
			$("#meetsecondfacultydetailsRow").show();
		}
		else
		{
			//otherwise, hide it
			$("#meetsecondfacultydetailsRow").hide();
		}
	})

	// Add onclick handler to checkbox w/id checkbox_observe_class
	$("#checkbox_observe_class").click(function()
	{
        // If checked
		if ($("#checkbox_observe_class").is(":checked"))
		{
			//show the hidden element(s)
			$("#observeclassdetailsRow").show();
		}
		else
		{
			//otherwise, hide it
			$("#observeclassdetailsRow").hide();
		}
	})

	// Add onclick handler to checkbox w/id checkbox_meet_coach
	$("#checkbox_meet_coach").click(function()
	{
        // If checked
		if ($("#checkbox_meet_coach").is(":checked"))
		{
			//show the hidden element(s)
			$("#meetcoachdetailsRow").show();
		}
		else
		{
			//otherwise, hide it
			$("#meetcoachdetailsRow").hide();
		}
	})

	// Add onclick handler to checkbox w/id checkbox_meet_music_faculty
	$("#checkbox_meet_music_faculty").click(function()
	{
        // If checked
		if ($("#checkbox_meet_music_faculty").is(":checked"))
		{
			//show the hidden element(s)
			$("#meetmusicfacultydetailsRow").show();
		}
		else
		{
			//otherwise, hide it
			$("#meetmusicfacultydetailsRow").hide();
		}
	})

	// Add onclick handler to checkbox w/id checkbox_music_audition
	$("#checkbox_music_audition").click(function()
	{
        // If checked
		if ($("#checkbox_music_audition").is(":checked"))
		{
			//show the hidden element(s)
			$("#musicauditiondetailsRow").show();
		}
		else
		{
			//otherwise, hide it
			$("#musicauditiondetailsRow").hide();
		}
	})

	// Add onclick handler to checkbox w/id checkbox_overnight_housing
	$("#checkbox_overnight_housing").click(function()
	{
        // If checked
		if ($("#checkbox_overnight_housing").is(":checked"))
		{
			//show the hidden elements
			$("#overnightnoteRow").show();
			$("#overnightdayRow").show();
        $("#emergencycontactRow").show();
        $("#emergencyphonenumberRow").show();
			//$("#overnightpriorarrivaltimeRow").show();
		}
		else
		{
			//otherwise, hide it
			$("#overnightnoteRow").hide();
			$("#overnightdayRow").hide();
        $("#emergencycontactRow").hide();
        $("#emergencyphonenumberRow").hide();
			//$("#overnightpriorarrivaltimeRow").hide();
		}
	})

	// Add onclick handler to radiobuttons with name 'overnight_day'
	$("input[name='overnight_day']").change(function()
	{
		// If "Night Prior to visit" is checked
		if ($("#radio_overnight_day_1").is(":checked"))
		{
			//show "Arrival Time" element
			$("#overnightpriorarrivaltimeRow").show();
		}
		else
		{
			//otherwise, hide it
			$("#overnightpriorarrivaltimeRow").hide();
		}
	})
})
