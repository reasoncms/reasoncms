$(document).ready(function()
{
	$("div.quotes").each(function(index)
	{
		// We append our link, and add a click event which invokes the module_api
		$(this).append($('<a href="#">New Quote</a>')).click(function()
		{
			// lets setup our request_params, using $.reasonAjax.get_module_identifier to get the module_identifier for our div
			request_params = { 'module_api' : 'quote',
							   'module_identifier' : $.reasonAjax.get_module_identifier($(this)),
							   'last_quote' : $(this).find("span.quoteText").data("quote-id") };
			
			// we define a success funtion, note we reference the original jquery object ($this) in the selector context
			success_function = function(data, textStatus, XMLHttpRequest)
			{
				var quote = data.quotes[0];
				
				$("div.quotes > ul").fadeTo(100, 0, function() {
					$(this).find("span.quoteText").text(quote.text).data("quote-id", quote.reasonID);
					$(this).find("span.quoteAuthor").text(quote.author);
					$(this).fadeTo(300, 1);
				});
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