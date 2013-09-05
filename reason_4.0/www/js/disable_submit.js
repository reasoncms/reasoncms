/**
 * Disable submit javascript - when a form is submitted, replaces the submit button with a "Please Wait" message.
 *
 * Features include:
 *
 * - forms can be specified with a name, id, or class
 * - a reset time (in milliseconds) can optionally be provided
 * - if no form is specified, all forms on the page will be considered
 * - a cancelDisableSubmit() function is available for external processes to
 *   interrupt the disabled countdown.
 *
 * @author Nathan White
 * @author Mark Heiman
 * @requires jQuery
 */ 

jQuery(function($) {
	var selector_string = get_form_selector();
	
	/**
	  * Define an event on the form object(s) that can be triggered to disable 
	  * the submit buttons and put up a message.
	  */
	$(selector_string).on('disable_buttons', function(e, wait_msg) {
		$(this).data('submit_buttons').each(function() {$(this).prop("disabled", "disabled");});
		
		if ($(this).data('submit_row')) {
			var message = $('<span class="submit_waiting">' + wait_msg + '</span>');
			var target = $("td:last", $(this).data('submit_row'));
			if ($(".submit_waiting, .submitting", target).length <= 0)
				target.append(message);
		} else {
			$(this).data('submit_buttons').each(function change_caption() {
				var button = $(this);
				button.data('original_caption', (this.nodeName == "BUTTON") ?
					button.html() :
					button.prop("value"));
				if (this.nodeName == "BUTTON")
					button.html(wait_msg);
				else
					// Set the value to the entity-decoded version of the message
					button.prop("value", $("<div/>").html(wait_msg).text());
			});
		}			
	});
	
	/**
	  * Define an event on the form object(s) that can be triggered to enable 
	  * the submit buttons and update the message.
	  */
	$(selector_string).on('enable_buttons', function(e, msg) {
		if ($(this).data('submit_row')) {
			$('span.submit_waiting', this).html(msg);
		}
		$(this).data('submit_buttons').each(function() {
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
	});
	
	/**
	  * Attach a function to the submit event that sets up the disable/reenable
	  * cycle on the form.
	  */
	$(selector_string).on('submit', function(e)
	{
		// Get the list of submit buttons on this form; store the list 
		// on the form object for other events to refer to.
		var buttons = $("button[type=submit], input[type=submit], " +
			"input[type=image]", this);
		if (buttons.length <= 0) return; // Bail out if no submit buttons
		$(this).data('submit_buttons', buttons);
		
		var valid_reset_time = get_reset_time();
		var form = this;
		
		// Figure out if our buttons are in a Disco submit row, 
		// and if they are, save that row object -- that's where
		// we'll put the status message
		var submit_row = null;
		buttons.each(function() {
			var row = $(this).parents('#discoSubmitRow').eq(0);
			if (!submit_row && row.length)
				submit_row = row;
			else if (!row)
				submit_row = null;
		});
		$(this).data('submit_row', submit_row);
		
		var wait_msg = 'Please wait&hellip;';
		var still_working_msg = 'Still working&hellip;';
		var lengthy_msg = 'Still working?';
		
		var reassurance_time = (valid_reset_time) ?
			Math.min(valid_reset_time, 10000) :
			10000;
		
		// Set up the timers for each of the steps:
		//	1. Initial disabling
		// 	2. Midway reassurance text change
		//	3. Timeout reenabling of buttons
		// We store references to the timers on the form object so that
		// other processes can intercept them if needed.
		
		var timer2 = setTimeout(function reassure_user() {
			$('span.submit_waiting', form).html(still_working_msg);
		}, reassurance_time);
		$(this).data('timer2', timer2);
		
		if (valid_reset_time) {
			var timer3 = setTimeout(function(){ $(form).trigger('enable_buttons', lengthy_msg)}, valid_reset_time);
			$(this).data('timer3', timer3);
		}
		
		// Disable the buttons right after the form submit action goes through.
		var timer1 = setTimeout(function(){$(form).trigger('disable_buttons',wait_msg)}, 0);
		$(this).data('timer1', timer1);
		
		return true;
	});
});

/**
  * Parse the URL by which this script was called to construct the JQuery selector
  * that will define which form(s) the submit disabler is attached to.
  */
function get_form_selector()
{
	var js_src = $('script[src*="disable_submit.js"]:first').attr("src");
	
	// we grab and validate user input
	var valid_names = verifyString(queryString( "name", js_src ));
	var valid_ids = verifyString(queryString( "id", js_src ));
	var valid_classes = verifyString(queryString( "class", js_src ));
	var selector = [];
	
	if (valid_names) selector[selector.length] = $.map(valid_names.split(","), function (item) { return "form[name='" + item + "']"; }).toString();
	if (valid_ids) selector[selector.length] = $.map(valid_ids.split(","), function (item) { return "form#" + item; }).toString();
	if (valid_classes) selector[selector.length] = $.map(valid_classes.split(","), function (item) { return "form." + item; }).toString();
	
	return (selector.length > 0) ? selector.toString() : "form";	
}

/**
  * Parse the URL by which this script was called to find a custom reset time.
  */
function get_reset_time()
{
	var js_src = $('script[src*="disable_submit.js"]:first').attr("src");
	return parseInt(queryString ('reset_time', js_src), 10);
}

/**
  * This function can be called to interrupt the disable_submit process on
  * a particular form, or on all affected forms (if no form object is passed)
  *
  * @param form Jquery form object
  */
function cancelDisableSubmit(form)
{
	if (!form) var form = $(get_form_selector());
	
	form.each(function(){
		// Only run this if timers have been set on this form
		if ($(this).data('timer1'))
		{
			// Clear all the timers
			clearTimeout($(this).data('timer1'));
			clearTimeout($(this).data('timer2'));
			clearTimeout($(this).data('timer3'));
			// Trigger the enable_buttons event in case any timers
			// have already fired.
			$(this).triggerHandler('enable_buttons', '');
		}
	});
}

/**
  * Pull a query string value out of the given URL.
  */
function queryString( key, url )
{
	if ( (url.search( RegExp( "[?&]" + key + "=([^&$]*)", "i" ) )) > -1 ) return RegExp.$1;
	else return false;
}

/**
  * Return the passed string if it only contains alphanumeric and _ -
  */
function verifyString( string )
{
	if (string)
	{
		if (string.match(/^[a-z0-9,_-]*$/i)) return string;
	}
	return false;
}
