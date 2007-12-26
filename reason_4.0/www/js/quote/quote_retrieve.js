// Request quote via ajax request and display the quote - requires jQuery
// @author Nathan White

viewed_quotes = [];
pending_quote = '';

$(document).ready(function()
{
	var js_src = $('script[src*=quote_retrieve]:first').attr("src");
	
	// define global vars
	js_site_id = parseInt(queryString ('site_id', js_src), 10);
	js_page_id = parseInt(queryString ('page_id', js_src), 10);
	js_cur_quote_id = parseInt(queryString ('quote_id', js_src), 10);
	
	if (js_cur_quote_id > 0) add_viewed_quote(js_cur_quote_id);
	create_refresh_link();
	grab_quote();
});

function create_refresh_link()
{
	var refresh_div = '<div id="refresh_link"><p><a href="#">New Quote</a></p></div>';
	$("div#quotes").append(refresh_div);
	$("div#refresh_link a").click(function(e) {
		replace_quote();
	});
}

function get_viewed_quotes()
{
	return viewed_quotes.toString();
}

function add_viewed_quote(id)
{
	if (id in objectify(viewed_quotes)) // gotta be a better way
	{
		viewed_quotes = [];
	}
	viewed_quotes[viewed_quotes.length] = id;
}

function objectify(a)
{
	var obj = {};
	for(var i=0; i<a.length; i++)
	{
		obj[a[i]]='';
	}
	return obj;
}

function grab_quote()
{
	$.post("/reason_package/reason_4.0/www/js/quote/quote_retrieve.php",
		{ 
			viewed_quote_ids: get_viewed_quotes(), 
			site_id: js_site_id,
			page_id: js_page_id,
			quote_id: js_cur_quote_id
		}, 
		function(data, statusText) //
		{
			$(data).find('quote_text').each(function(){
				var quote_text = $(this).text();
				pending_quote = quote_text;
				});
			$(data).find('quote_id').each(function(){
				js_cur_quote_id = $(this).text();
				add_viewed_quote(js_cur_quote_id);
				});
		}, "xml");
}

function replace_quote()
{
	update_quote();
	grab_quote();
}

function update_quote()
{
	$("div#quote").replaceWith('<div id="quote">'+pending_quote+'</div>');
}

function queryString( key, url )
{
	if ( (url.search( RegExp( "[?&]" + key + "=([^&$]*)", "i" ) )) > -1 ) return RegExp.$1;
	else return null;
}