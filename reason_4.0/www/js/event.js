/** 
 * event.js - Show / hide fields based upon "Repeat this Event" setting in the Reason event content manager
 *
 * @author Nathan White
 * @requires jQuery
 */

$(document).ready(function()
{
	var menuState = new Array();
	menuState["none"]= new Array("frequencyRow","sundayRow", "mondayRow", "tuesdayRow", "wednesdayRow", "thursdayRow", "fridayRow", "saturdayRow", "enddateRow", "monthlyrepeatRow");
	menuState["daily"]= new Array("sundayRow", "mondayRow", "tuesdayRow", "wednesdayRow", "thursdayRow", "fridayRow", "saturdayRow", "monthlyrepeatRow");
	menuState["weekly"]= new Array("monthlyrepeatRow");
	menuState["monthly"]= new Array("sundayRow", "mondayRow", "tuesdayRow", "wednesdayRow", "thursdayRow", "fridayRow", "saturdayRow");
	menuState["yearly"]= new Array("sundayRow", "mondayRow", "tuesdayRow", "wednesdayRow", "thursdayRow", "fridayRow", "saturdayRow", "monthlyrepeatRow");
	
	var freqState = new Array();
	freqState["none"] = '';
	freqState["daily"] = 'day(s)';
	freqState["weekly"] = 'week(s)';
	freqState["monthly"] = 'month(s)';
	freqState["yearly"] = 'year(s)';

	hide_fields(); // hide the fields to start with	
	selectElm = $("select#recurrenceElement");
	update_display(selectElm);
	update_radio_buttons();

	$(selectElm).change(function()
	{
		update_display(this);
		update_radio_buttons();
	});
	
	$("input[name='datetime[month]']").change(function()
	{
		update_radio_buttons();
	});
	$("input[name='datetime[day]']").change(function()
	{
		update_radio_buttons();
	});
	$("input[name='datetime[year]']").change(function()
	{
		update_radio_buttons();
	});

	function show_fields()
	{	
		for (var i = 0; i < menuState["none"].length; i++)
		{
			$("tr#"+menuState["none"][i]).show();
		}
	}
	
	function hide_fields(index)
	{	
		index = index || "none";
		for (var i = 0; i < menuState[index].length; i++)
		{
			$("tr#"+menuState[index][i]).hide();
		}
	}
	
	function update_display(selectElement)
	{
		index = $(selectElement).val(); // determine what was selected
		show_fields(); // show all fields
		hide_fields(index); // hide fields as specified for the index in menuState
		$("span#frequencyComment").text(freqState[index]);
	}
	
	// The remainer of this file used to be ymd_to_dow__wom.js
	
	// Scripts for Date Conversions
	// Year-Month-Day -> Day of Week and Week of Month
	// Developed for the Reason event content manager, Dec. 2003 by NF, BK, MR
	// jQuerified, Feb 1, 2008 - Nathan White
	function update_radio_buttons()
	{
		var day = $("input[name='datetime[day]']").val();
		var month = $("input[name='datetime[month]']").val();
		var year = $("input[name='datetime[year]']").val();
		var wom = Math.floor(day/7)+1;
		var dow = ymd_to_dow( year,month,day );	
		$("div#monthly_repeat_container label[for='radio_monthly_repeat_0']").text("On the "+wom+suffix( wom )+" "+dow+" of the month");
		$("div#monthly_repeat_container label[for='radio_monthly_repeat_1']").text("On the "+day+suffix( day )+" of the month");
	}
	
	// append the correct pair of letters to a number
	function suffix( number )
	{
		if( number > 10 && number < 20 ) return "th";
		else if( number % 10 == 1 ) return "st";
		else if( number % 10 == 2 ) return "nd";
		else if( number % 10 == 3 ) return "rd";
		else return "th";
	}
	
	// determine what day of the week the specified day falls on
	function ymd_to_dow(dayear, damonth, daday) {
		damonth -= 1; // JavaScript months are zero-indexed
		var date = new Date(dayear, damonth, daday);
		var danewday = date.getDay();
	
		if ( danewday < 0 ) return '(error!)';
		if ( danewday == 0 ) return 'Sunday';
		if ( danewday == 1 ) return 'Monday';
		if ( danewday == 2 ) return 'Tuesday';
		if ( danewday == 3 ) return 'Wednesday';
		if ( danewday == 4 ) return 'Thursday';
		if ( danewday == 5 ) return 'Friday';
		if ( danewday == 6 ) return 'Saturday';
		if ( danewday > 6 ) return '(error!)';
	}
});

