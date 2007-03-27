// Scripts for Date Conversions
// Year-Month-Day -> Day of Week and Week of Month
// Developed for the Reason event content manager, Dec. 2003 by NF, BK, MR

// get the elements by the Ids we set up for them
var radiodiv = document.getElementById( 'monthly_repeat_container' );
var month = document.getElementById( 'datetimemonthElement' );
var day = document.getElementById( 'datetimedayElement' );
var year = document.getElementById( 'datetimeyearElement' );

// set the onchange action for the 3 text fields
month.onchange = update_radio_buttons;
day.onchange = update_radio_buttons;
year.onchange = update_radio_buttons;

// update the radio button options according to the date
function update_radio_buttons()
{
	//var labels = document.getElementsByTagName( 'label' );
	var radiodiv = document.getElementById( 'monthly_repeat_container' );
	var labels = radiodiv.getElementsByTagName( 'label' );
	var goodlabels = Array();
	var j = 0;
	for( var i = 0; i < labels.length; i++ )
	{
		if( isAncestorOf( radiodiv,labels[i] ) )
		{
			goodlabels.push( labels[i] );
			j++;
		}
	}
	var wom = Math.floor(day.value/7)+1;
	var dow = ymd_to_dow( year.value,month.value,day.value );
	goodlabels[0].innerHTML = "On the "+wom+suffix( wom )+" "+dow+" of the month";
	goodlabels[1].innerHTML = "On the "+day.value+suffix( day.value )+" of the month";
}

// append the correct pair of letters to a number
function suffix( number )
{
	if( number > 10 && number < 20 )
		return "th";
	else if( number % 10 == 1 )
		return "st";
	else if( number % 10 == 2 )
		return "nd";
	else if( number % 10 == 3 )
		return "rd";
	else
		return "th";
}
	
// determine if the second object is further down the same branch of the DOM
// tree as the first object
function isAncestorOf( elem1,elem2 )
{
	if( elem1.id == elem2.id )
		return true;
	else if( elem2.parentNode == null )
		return false;
	else
		return isAncestorOf( elem1,elem2.parentNode )
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
