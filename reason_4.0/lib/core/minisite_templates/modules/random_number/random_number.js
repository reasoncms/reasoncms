/**
 * ajaxReason takes the parameters of jquery $.ajax and auto adds the module_identifier to the data
 *
 * - honestly this is maybe overkill ... we could just provide a plugin to grab the module identifier and let you do the request
 *   yourself using normal jquery AJAX stuff.
 *
 * - only posts if a module_location is found on the element
 */
(function($)
{
	$.fn.ajaxReason = function(options)
	{
		// define options
		options = (options == undefined) ? {} : options;
		reason_module_identifier_substring = 'module_identifier-';
		module_api = (options.api) ? options.api : false;
		options.type = (options.type) ? options.type : "GET"; // GET is our default
		options.data = (options.data) ? options.data : "";
		if ( (typeof options.data) != "string" ) options.data = $.param(options.data);
		
		// find the module location
		var classes = this.attr('class').split(" ");
		var module_identifier = false;
		$.each(classes, function(i)
		{
			if (classes[i].substring(0,reason_module_identifier_substring.length) == reason_module_identifier_substring)
			{
				module_identifier = classes[i].substring(reason_module_identifier_substring.length);
				return false;
			}
		});
		
		if (module_identifier && module_api)
		{
			// add our module ID to data
			//options.data = $.extend( { "module_api" : module_api, "module_identifier" : module_identifier }, options.data );
			
			// we want to support options.data as an object or a string ... sort
			//if (options.data == "") options.data = "module_api="+module_api+"&module_identifier="+module_identifier;
			
			options.data = (options.data.length > 0) 
						 ? "module_api="+module_api+"&module_identifier="+module_identifier+"&"+options.data 
						 : "module_api="+module_api+"&module_identifier="+module_identifier;

			$.ajax({
				url: options.url,
				type: options.type,
				data: options.data,
				dataType: options.dataType,
				beforeSend: function()
				{
				},
				success: function(thedata, textStatus, XMLHttpRequest)
				{
					if(typeof options.success==='function')
					{
						options.success(thedata, textStatus, XMLHttpRequest);
					}
				}
			});
		}
		return this;
	};
})(jQuery);
/**
 * AjaxSample Module JavaScript
 *
 * Demonstrates best practices for AJAX within a Reason module.
 *
 * @author Nathan White
 */
$(document).ready(function()
{
	// for each one I want to add a link that requests a new number.
	$("div.random_number").each(function(index)
	{
		var $this = $(this);
		mylink = $('<a href="#">get a new number</a>');
		$this.append(mylink);
		$(mylink).click(function()
		{
			success_function = function(data, textStatus, XMLHttpRequest)
			{
				$("span.random_number", $this).text(data.random_number);
			};
			
			$this.ajaxReason( { api : "random_number", 
							    //data : "action=get_random_number",
							    dataType : "json",
							    success: success_function } );
			return false;
		});
		
	});
});