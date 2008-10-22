/**
 * Disable submit javascript - when a form is submitted, replaces the submit button with a "Please Wait" message.
 *
 * Features include:
 *
 * - forms can be specified with a name, id, or class
 * - a reset time (in milliseconds) can optionally be provided
 *
 * @author Nathan White
 * @todo ensure keypress submissions are properly handled (i think they are)
 * @requires jQuery
 */ 

$(document).ready(function()
{
	var js_src = $('script[src*=disable_submit]:first').attr("src");
	
	// we grab and validate user input
	var valid_names = verifyString(queryString( "name", js_src ));
	var valid_ids = verifyString(queryString( "id", js_src ));
	var valid_classes = verifyString(queryString( "class", js_src ));
	var valid_reset_time = parseInt(queryString ('reset_time', js_src), 10);
	var selector = Array();
	
	if (valid_names) selector[selector.length] = $.map(valid_names.split(","), function (item) { return "form[name='" + item + "']"; }).toString();
	if (valid_ids) selector[selector.length] = $.map(valid_ids.split(","), function (item) { return "form#" + item; }).toString();
	if (valid_classes) selector[selector.length] = $.map(valid_classes.split(","), function (item) { return "form." + item; }).toString();
	
	if (selector.length > 0) $(selector.toString()).submit(function() // sets up a submit event for valid forms
	{
		var buttons = new Array();
		buttons["text"] = new Array();
		buttons["refs"] = new Array();
		
		$("input[type=submit]", this).each(function(index)
		{
			buttons["refs"][index] = $(this);
			buttons["text"][index] = ($(this).attr("value"));
			if (valid_reset_time) setTimeout( function() { enableSubmit(buttons["refs"][index], buttons["text"][index]); }, valid_reset_time);
			setTimeout( function() { disableSubmit(buttons["refs"][index], "Please wait..."); }, 100);
		});
	});
	
	function enableSubmit(button, value)
	{
		$(button).attr("value",value);
		$(button).attr("disabled",false);
	}
	
	function disableSubmit(button, value)
	{
		$(button).attr("value",value);
		$(button).attr("disabled",true);
	}
	
	function queryString( key, url )
	{
		if ( (url.search( RegExp( "[?&]" + key + "=([^&$]*)", "i" ) )) > -1 ) return RegExp.$1;
		else return false;
	}
	
	function verifyString( string )
	{
		if (string)
		{
			if (string.match(/^[a-z0-9,_-]*$/i)) return string;
		}
		return false;
	}
});