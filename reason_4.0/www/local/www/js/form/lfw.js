$(document).ready(function(){
	
	// Highlight Description Rows
	$(".words h3").css("color", "#123074");
	$(".words h2").css("color", "#6688BB");
	
	//Hide Dietary Needs rows
	if (!$("#radio_attend_banquet_0").is(":checked")){
		$("#dietarynoteRow").css("display", "none");
		$("#dietaryneedsRow").css("display", "none");
	}

	// Add onclick handler to radiobuttons with name 'attended_banquet'
	$("input[name='attend_banquet']").change(function()
	{
		// If "Yes" is checked
		if ($("#radio_attend_banquet_0").is(":checked")){
			$("#dietarynoteRow").show(); 
			$("#dietaryneedsRow").show(); 
		}
		// If "No" is checked
		if ($("#radio_attend_banquet_1").is(":checked")){
			$("#dietarynoteRow").hide(); 
			$("#dietaryneedsRow").hide();
			$("#guest_classElement").val('').attr('selected', 'selected');
		}
	});
	
		//Hide Student Lodging Rows
	if ($("radio[name='conference_fee']").val('-')||$("radio[name='conference_fee']").val('95')){
		$("#studenthousingRow").css("display", "none");
		$("#housinggenderRow").css("display", "none");
		$("#housingstudenttypeRow").css("display", "none");
		$("#housingnightsRow").css("display", "none");
	}		
	
	$("input[name='conference_fee']").change(function()
	{
		//if a student option is checked show lodging rows
		if ($("#radio_conference_fee_1").is(":checked")||$("#radio_conference_fee_3").is(":checked")){
			$("#studenthousingRow").show(); 
			$("#housinggenderRow").show();
			$("#housingstudenttypeRow").show(); 
			$("#housingnightsRow").show();
		}
		
		//if general public or Luther faculty/staff is selected
		if ($("#radio_conference_fee_0").is(":checked")||$("#radio_conference_fee_2").is(":checked")){
			$("#studenthousingRow").hide(); 
			$("#housinggenderRow").hide();
			$("#housingstudenttypeRow").hide(); 
			$("#housingnightsRow").hide();
		}
	});
})
