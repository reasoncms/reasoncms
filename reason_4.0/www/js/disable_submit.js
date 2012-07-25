/**
 * Disable submit javascript - when a form is submitted, replaces the submit button with a "Please Wait" message.
 *
 * Features include:
 *
 * - forms can be specified with a name, id, or class
 * - a reset time (in milliseconds) can optionally be provided
 * - if no form is specified, all forms on the page will be considered
 *
 * @author Nathan White
 * @todo ensure keypress submissions are properly handled (i think they are)
 * @requires jQuery
 */ 

jQuery(function($) {
	var js_src = $('script[src*="disable_submit.js"]:first').attr("src");
	
	// we grab and validate user input
	var valid_names = verifyString(queryString( "name", js_src ));
	var valid_ids = verifyString(queryString( "id", js_src ));
	var valid_classes = verifyString(queryString( "class", js_src ));
	var valid_reset_time = parseInt(queryString ('reset_time', js_src), 10);
	var selector = [];
	
	if (valid_names) selector[selector.length] = $.map(valid_names.split(","), function (item) { return "form[name='" + item + "']"; }).toString();
	if (valid_ids) selector[selector.length] = $.map(valid_ids.split(","), function (item) { return "form#" + item; }).toString();
	if (valid_classes) selector[selector.length] = $.map(valid_classes.split(","), function (item) { return "form." + item; }).toString();
	
	var selector_string = (selector.length > 0) ? selector.toString() : "form";
	
	$(selector_string).submit(function(e) // sets up a submit event for valid forms
	{
		var buttons = $("button[type=submit], input[type=submit], " +
			"input[type=image]", this);
		if (buttons.length <= 0)
			return;
		
		var submit_row = null;
		buttons.each(function() {
			var row = $(this).parents('#discoSubmitRow').eq(0);
			if (!submit_row && row)
				submit_row = row;
			else if (!row)
				submit_row = null;
		});
		
		var wait_msg = 'Please wait&hellip;';
		var still_working_msg = 'Still working&hellip;';
		var lengthy_msg = 'Still working?';
		var message;
		if (submit_row) {
		    message = $('<span class="submit_waiting">' + wait_msg + '</span>');
		}
		
		var reassurance_time = (valid_reset_time) ?
			Math.min(valid_reset_time, 10000) :
			10000;
		
		setTimeout(function reassure_user() {
			message.html(still_working_msg);
		}, reassurance_time);
		
		if (valid_reset_time) {
			setTimeout(function reenable_buttons() {
				buttons.each(function() {
					var button = $(this);
					button.prop("disabled", false);
					var original_caption = button.data("original_caption");
					if (original_caption) {
						if (this.nodeName == "BUTTON")
							button.html(original_caption);
						else
							button.attr("value", original_caption);
					}
				});
				
				if (message) {
					message.html(lengthy_msg);
				}
			}, valid_reset_time);
		}
		
		// Disable the buttons right after the form submit action goes through.
		setTimeout(function disable_buttons() {
			var target;
			buttons.each(function() {$(this).prop("disabled", "disabled");});
			
			if (message) {
				target = $("td:last", submit_row);
				if ($(".submit_waiting, .submitting", target).length <= 0)
					target.append(message);
			} else {
				buttons.each(function change_caption() {
					var button = $(this);
					button.data('original_caption', (this.nodeName == "BUTTON") ?
						button.html() :
						button.attr(value));
					if (this.nodeName == "BUTTON")
						button.html(wait_msg);
					else
						button.attr("value", wait_msg);
				});
			}
		}, 0);
		
		return true;
	});
	
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
