$(document).ready(function(){
	
	// Highlight Description Rows
	$(".words h3").css("color", "#123074");
	
	//Hide Guest Class row
	if (!$("#radio_attended_luther_0").is(":checked")){
		$("#guestclassRow").css("display", "none");
	}
	
	
	//Hide Reservation info
	//Luncheon Header Row
	if (!$("#attend_luncheonElement").val()){
		$("#luncheonheaderRow").css("display", "none");
		//Tickets for Luncheon Row
		$("#attendluncheonRow").css("display", "none"); 
	}
	//Dinner Header Row 
	$("#dinnerheaderRow").css("display", "none");

	//Dinner Tickets
	if (!$("#attend_dinner_50_to_25Element").val()){
		$("#attenddinner50to25Row").css("display", "none");
	}
	if (!$("#attend_dinner_20_to_10Element").val()){	
		$("#attenddinner20to10Row").css("display", "none");	
	}
	if (!$("#attend_dinner_5Element").val()){
		$("#attenddinner5Row").css("display", "none"); 
	}
	
	//Parade Row
	if ((!$("#radio_ride_in_parade_0").is(":checked")) || (!$("#radio_ride_in_parade_1").is(":checked"))){
		$("#rideinparadeRow").css("display", "none");
	}		

	// Add onclick handler to radiobuttons with name 'attended_luther' from Guest Info 
	$("input[name='attended_luther']").change(function()
	{
		// If "Yes" is checked
		if ($("#radio_attended_luther_0").is(":checked")){
			$("#guestclassRow").show(); 
		}
		// If "No" is checked
		if ($("#radio_attended_luther_1").is(":checked")){
			$("#guestclassRow").hide();
			$("#guest_classElement").val('').attr('selected', 'selected');
		}
	})
	
	
	//Add select handler to Class Year select element
	//if year selected is a 5 - 75 year reunion, show options
	$('#class_yearElement').change(function() {
		var date = new Date();
		var year = date.getFullYear();
		var class_year = $("select[name='class_year']").val(); 
		
		//if class year == 50 show Parade options
		if ((year - parseInt(class_year)) == 50)
		{
			$("#rideinparadeRow").show();
		}else{
			$("#rideinparadeRow").hide();
			$("#radio_ride_in_parade_0").removeAttr('checked');
			$("#radio_ride_in_parade_1").removeAttr('checked');
		}
		
		//if 75 - 50 show Luncheon header and tickets
		if ((year - parseInt(class_year)) == 75||
			(year - parseInt(class_year)) == 70||
			(year - parseInt(class_year)) == 65||
			(year - parseInt(class_year)) == 60||
			(year - parseInt(class_year)) == 55||
			(year - parseInt(class_year)) == 50)
			{
				$("#luncheonheaderRow").show();
				$("#attendluncheonRow").show();	
			}else{
				$("#luncheonheaderRow").hide();
				$("#attendluncheonRow").hide();
				$("#attend_luncheonElement").val('').attr('selected', 'selected');
			}
					
		if ((year - parseInt(class_year)) == 55||
			(year - parseInt(class_year)) == 50||
			(year - parseInt(class_year)) == 45||
			(year - parseInt(class_year)) == 40||
			(year - parseInt(class_year)) == 35||
			(year - parseInt(class_year)) == 30||
			(year - parseInt(class_year)) == 25)
			{
				$("#dinnerheaderRow").show();
				$("#attenddinner50to25Row").show();	
			}else{
				$("#dinnerheaderRow").hide();
				$("#attenddinner50to25Row").hide();
				$("#attend_dinner_50_to_25Element").val('');
			}
		
		// if 20 - 10  show Reception header and tickets	
		if ((year - parseInt(class_year)) == 20||
			(year - parseInt(class_year)) == 15||
			(year - parseInt(class_year)) == 10)
			{
				$("#dinnerheaderRow").show();
				$("#attenddinner20to10Row").show();
			}else{
				$("#dinnerheaderRow").hide();
				$("#attenddinner20to10Row").hide();
				$("#attend_dinner_20_to_10Element").val('').attr('selected', 'selected');			}
			
		// if 5 year show 5 Year Reception header and tickets
		if ((year - parseInt(class_year)) == 5)
		{
			$("#dinnerheaderRow").show();
			$("#attenddinner5Row").show();
		}else{
			$("#dinnerheaderRow").hide();
			$("#attenddinner5Row").hide();
			$("#attend_dinner_5Element").val('').attr('selected', 'selected');
		}
			
	});
})
