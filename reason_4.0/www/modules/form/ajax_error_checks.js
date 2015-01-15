/**
 * Ajax Error Checking for Reason Forms
 * 
 * @author Mark Heiman
 * @since Dec 2014
 *
 * Requires jQuery
 *
 * This is a jQuery plugin that can be applied to any form that uses the standard
 * Reason form MVC framework. It will allow error checks to be done via ajax
 * rather than requiring a page submission.
 *
 * Works with both table-based and tableless forms.
 *
 * Usage:
 *
 * Call the plugin on a form object, and pass a callback function to be run on
 * completion as the parameter:
 
	$( "#disco_form" ).runErrorChecks(function(data){
		// Do your actions here. If data.error_count is positive, then
		// there were errors on the form. If the error count is negative, there was
		// some failure in the ajax process and we haven't done a successful check.
		if (data.error_count === 0)
		{
			//success!
		}
	});
 */

(function( $ ) {
	$.fn.runErrorChecks = function(callback)
	{
		// Only run if we're attached to a form.
		if (!this.is('form'))
		{
			console.log('runErrorChecks attached to non-form element.');
			return this;
		}
		
		// Grab all the form values as an array, then add the required api parameters
		var values= this.serializeArray();
		values.push(
			{ name: "module_identifier", value: $.reasonAjax.get_module_identifier(this.parent()) },
			{ name: "module_api", value: "standalone" },
			{ name: "module_api_action", value: "run_error_checks"}
		);
		
		var formObject = this;
		var tableless = (!$("table.thorTable", formObject).length);
		
		// Post the array to the form, and deal with the response.		
		$.post(document.URL, $.param(values), null, 'json')
			.done(function(data, textStatus, reqObj) {
					// Error checking failed
					if (!data)
					{
						console.log('runErrorChecks post request returned no data');
						callback({ error_count: -1 });
					}
					else if (typeof data !== 'object')
					{
						console.log('runErrorChecks post request returned non-object');
						callback({ error_count: -1 });
					}
					// No errors found
					else if (data.errors === false)
					{
						// clear all error labels
						$(".error", formObject).removeClass('error');
						$(".ajaxFormError", formObject).remove();
						callback({ error_count: 0, error_data: data });
					}
					// Errors found
					else
					{
						// clear preexisting error labels
						$(".error", formObject).removeClass('error');
						$(".ajaxFormError", formObject).remove();
						
						var first_error;
						var message = data.header_text + "\n\n";
						
						// Display the errors above each errored element
						$.each(data.errors, function(name, errs)
						{
							var loc = (tableless) ? "div#"+name.replace(/_/g,'')+"Item" : "tr#"+name.replace(/_/g,'')+"Row";
							
							// If this element location doesn't exist, this is a general
							// error, so we add a div at the top of the form to hold it.
							if (!$(loc).length) formObject.prepend($("<div id='"+name.replace(/_/g,'')+"Item' />"));
							
							$(loc, formObject).addClass('error');
							if (!first_error) first_error = loc;
							$.each(errs, function(index, errText)
							{
								var container = (tableless) ? $(loc, formObject) : $(loc + ' td.words', formObject);
								message = message + errText.message + "\n";
								container.prepend('<div class="ajaxFormError">'
									+((errText.type == 'required') 
										? 'This is a required field'
										: errText.message)
									+'</div>');
							});
						});					

						$('html,body').animate({
							scrollTop: $(first_error).offset().top - 50
						}, {
							duration: 500, 
							complete: function(){
								/* We could put up an alert here, but it seems like just
									scrolling to the error is a better experience. Maybe
									a future version could allow enabling this in a setting. */
								// if (this.nodeName == "BODY") { return; }
								// alert(message);
							}
						});
						
						callback({ error_count: Object.keys(data.errors).length, error_data: data });
					}
				})
				.fail(function(data, textStatus, reqObj) {
						console.log('runErrorChecks post request failed: ' + textStatus);
						callback({ error_count: -1 });
				});

		return this;
	}
}( jQuery ));
