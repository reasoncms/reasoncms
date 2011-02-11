/**
 * Random_Number Module JavaScript
 *
 * Demonstrates best practices for AJAX within a Reason module.
 *
 * - We look for any div with class random_number, and add a link to get a new number
 * - We utilize jquery.reasonAjax.js to get the module identifier from the class attribute of our random_number div
 * - We use standard jquery ajax and send a module_api and module_identifier string with our request
 * 
 * Note our code is designed to handle an arbitrary number of random number modules that might appear on a page.
 *
 * @author Nathan White
 * @requires jquery.reasonAjax.js
 */
$(document).ready(function()
{
	// for each one I want to add a link that requests a new number.
	$("div.random_number").each(function(index)
	{
		// Lets store in local variable $this a jquery object containing the random_number div
		var $this = $(this);
		
		// We append our link, and add a click event which invokes the module_api
		$this.append($('<a href="#">get a new number</a>')).click(function()
		{
			// lets setup our request_params, using $.reasonAjax.get_module_identifier to get the module_identifier for our div
			request_params = { 'module_api' : 'random_number',
							   'module_identifier' : $.reasonAjax.get_module_identifier($this),
							   'last_number' : $("span.random_number", $this).text() };
			
			// we define a success funtion, note we reference the original jquery object ($this) in the selector context
			success_function = function(data, textStatus, XMLHttpRequest)
			{
				$("span.random_number", $this).text(data.random_number);
			};
			
			// we perform our ajax request - the cache false adds something to the query string to stymie any caching
			$.ajax( { data : request_params,
					  success: success_function,
					  cache: false } );
			
			// we return false to negate the default behavior of the click
			return false;
		});
	});
});