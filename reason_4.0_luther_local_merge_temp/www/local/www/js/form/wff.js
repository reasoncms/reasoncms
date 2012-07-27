//<script type ="text/javascript">
$(document).ready(function(){
	
	//Hide registrants dietary needs row
	$("#id5Y2262K351Row").css("display", "none");
	
	//First Guest
	$("#id6v1B5MY87Row").css("display", "none"); //Header
	$("#id67g3r101yZRow").css("display", "none"); //Name
	$("#idk225z360R9Row").css("display", "none"); //Address
	$("#id2T68lef07Row").css("display", "none"); //City
	$("#id122o098S7BRow").css("display", "none"); //State
	$("#id2H46bG1pn5Row").css("display", "none"); //Zip
	$("#ido8191w6z2Row").css("display", "none"); //Phone
	$("#id0SF5r8nV0Row").css("display", "none"); //Email
	$("#id5q26O7qw96Row").css("display", "none"); //Luncheon
	$("#id1jpa21Q662Row").css("display", "none"); //Diet needs
	
	//Second Guest
	$("#id7AB20Q28XRow").css("display", "none"); //Header
	$("#idY339526AXRow").css("display", "none"); //Name 
	$("#id65p38600z6Row").css("display", "none"); //Address
	$("#id6W727b3625Row").css("display", "none"); //City
	$("#id3O296daPL0Row").css("display", "none"); //State
	$("#id513O9ZV7S8Row").css("display", "none"); //Zip
	$("#id512E494E2ZRow").css("display", "none"); //Phone
	$("#idD193Fy6571Row").css("display", "none"); //Email
	$("#id8667su387Row").css("display", "none"); //Luncheon
	$("#idC53140g240Row").css("display", "none"); //Diet Needs
	
	

	// Add onclick handler to radiobuttons with name 'Registration Amount (i.e. id_16vn006_22)' 
	$("input[name='id_16vn006_22']").change(function()
	{
		// If "$25" is checked
		if ($("#radio_id_16vn006_22_0").is(":checked")){
			//First Guest
			$("#id6v1B5MY87Row").hide(); //Header
			$("#id67g3r101yZRow").hide(); //Name
			$("#idk225z360R9Row").hide(); //Address
			$("#id2T68lef07Row").hide(); //City
			$("#id122o098S7BRow").hide(); //State
			$("#id2H46bG1pn5Row").hide(); //Zip
			$("#ido8191w6z2Row").hide(); //Phone
			$("#id0SF5r8nV0Row").hide(); //Email
			$("#id5q26O7qw96Row").hide(); //Luncheon
			$("#id1jpa21Q662Row").hide(); //Diet needs
			
			//Second Guest
			$("#id7AB20Q28XRow").hide(); //Header
			$("#idY339526AXRow").hide(); //Name 
			$("#id65p38600z6Row").hide(); //Address
			$("#id6W727b3625Row").hide(); //City
			$("#id3O296daPL0Row").hide(); //State
			$("#id513O9ZV7S8Row").hide(); //Zip
			$("#id512E494E2ZRow").hide(); //Phone
			$("#idD193Fy6571Row").hide(); //Email
			$("#id8667su387Row").hide(); //Luncheon
			$("#idC53140g240Row").hide(); //Diet Needs
		}
		
		// If "$40" is checked
		if ($("#radio_id_16vn006_22_1").is(":checked")){
			//First Guest
			$("#id6v1B5MY87Row").show(); //Header
			$("#id67g3r101yZRow").show(); //Name
			$("#idk225z360R9Row").show(); //Address
			$("#id2T68lef07Row").show(); //City
			$("#id122o098S7BRow").show(); //State
			$("#id2H46bG1pn5Row").show(); //Zip
			$("#ido8191w6z2Row").show(); //Phone
			$("#id0SF5r8nV0Row").show(); //Email
			$("#id5q26O7qw96Row").show(); //Luncheon
						
			//Second Guest
			$("#id7AB20Q28XRow").hide(); //Header
			$("#idY339526AXRow").hide(); //Name 
			$("#id65p38600z6Row").hide(); //Address
			$("#id6W727b3625Row").hide(); //City
			$("#id3O296daPL0Row").hide(); //State
			$("#id513O9ZV7S8Row").hide(); //Zip
			$("#id512E494E2ZRow").hide(); //Phone
			$("#idD193Fy6571Row").hide(); //Email
			$("#id8667su387Row").hide(); //Luncheon
		}
		
		// If "$60" is checked
		if ($("#radio_id_16vn006_22_2").is(":checked")){
			//First Guest
			$("#id6v1B5MY87Row").show(); //Header
			$("#id67g3r101yZRow").show(); //Name
			$("#idk225z360R9Row").show(); //Address
			$("#id2T68lef07Row").show(); //City
			$("#id122o098S7BRow").show(); //State
			$("#id2H46bG1pn5Row").show(); //Zip
			$("#ido8191w6z2Row").show(); //Phone
			$("#id0SF5r8nV0Row").show(); //Email
			$("#id5q26O7qw96Row").show(); //Luncheon
			
			//Second Guest
			$("#id7AB20Q28XRow").show(); //Header
			$("#idY339526AXRow").show(); //Name 
			$("#id65p38600z6Row").show(); //Address
			$("#id6W727b3625Row").show(); //City
			$("#id3O296daPL0Row").show(); //State
			$("#id513O9ZV7S8Row").show(); //Zip
			$("#id512E494E2ZRow").show(); //Phone
			$("#idD193Fy6571Row").show(); //Email
			$("#id8667su387Row").show(); //Luncheon
		}	
	})
	
	// Show/Hide Registrant Dietary Needs options
	$("input[name='id_61031_9797[2]']").change(function()
	{
		if ($("input[name='id_61031_9797[2]']").is(":checked")){
			$("#id5Y2262K351Row").show();
		}else{
			$("#id5Y2262K351Row").hide();
		}
	})
	// Show/Hide Guest1 Dietary Needs options
	$("input[name='id_5q26O7qw96[2]']").change(function()
	{
		if ($("input[name='id_5q26O7qw96[2]']").is(":checked")){
			$("#id1jpa21Q662Row").show();
		}else{
			$("#id1jpa21Q662Row").hide();
		}
	})
	// Show/Hide Guest2 Dietary Needs options
	$("input[name='id__8667su387[2]']").change(function()
	{
		if ($("input[name='id__8667su387[2]']").is(":checked")){
			$("#idC53140g240Row").show();
		}else{
			$("#idC53140g240Row").hide();
		}
	})
	
	
})
