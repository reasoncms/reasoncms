// Request quote via ajax request and display the quote - requires jQuery
// @author Nathan White

viewed_quotes = [];
pending_quote = '';
pending_author = '';
pending_divider = '';

$(document).ready(function()
{
	var js_src = $('script[src*="quote_retrieve.js"]:first').attr("src");
	// define the filepath to which we will post data
	js_post_path = getRelativeDirectory(js_src)+"quote_retrieve.php";
	
	// define global vars - parse all in integerse
	js_site_id = parseInt(queryString ('site_id', js_src), 10);
	js_page_id = parseInt(queryString ('page_id', js_src), 10);
	js_cur_quote_id = parseInt(queryString ('quote_id', js_src), 10);
	js_page_category_mode = parseInt(queryString ('page_category_mode', js_src), 10);
	js_cache_lifespan = parseInt(queryString ('cache_lifespan', js_src), 10);
	js_prefer_short_quotes = parseInt(queryString ('prefer_short_quotes', js_src), 10);
	
	// add the current quote to the list of viewed quotes
	if (js_cur_quote_id > 0) add_viewed_quote(js_cur_quote_id);
	
	// add the refresh link to the DOM
	create_refresh_link();
	
	// queue up the next quote
	grab_quote();
});

function create_refresh_link()
{
	var refresh_div = '<div id="refresh_link"><a href="#">New Quote</a></div>';
	$("div#quotes_footer").prepend(refresh_div);
	$("div#refresh_link a").click(function() {
		replace_quote();
		return false;
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
	$.post(js_post_path,
		{ 
			viewed_quote_ids: get_viewed_quotes(), 
			site_id: js_site_id,
			page_id: js_page_id,
			quote_id: js_cur_quote_id,
			page_category_mode: js_page_category_mode,
			prefer_short_quotes: js_prefer_short_quotes,
			cache_lifespan: js_cache_lifespan
		}, 
		function(data, statusText) //
		{
			// grab id
			js_cur_quote_id = $(data).find('id').text();
			
			// add quote id to the list of viewed quotes
			add_viewed_quote(js_cur_quote_id);

			// grab text, author, and divider
			pending_quote = $(data).find('text').text();
			pending_author = $(data).find('author').text();
			pending_divider = (pending_author != "") ? $(data).find('divider').text() : '';
			
			// re-enable the link
			link_enabled = true;
		}, "xml");
}

function replace_quote()
{
	if (link_enabled)
	{
		link_enabled = false;
		$("div#quotes ul li:first").fadeTo(100, 0, function() {
			$(this).find("span.quoteText").text(pending_quote);
			$(this).find("span.quoteAuthor").text(pending_author);
			$(this).find("span.quoteDivider").text(pending_divider);
			$(this).fadeTo(300, 1);
		});
		grab_quote();
	}
}

// get the directory location relative to the server base
function getRelativeDirectory( url )
{
		abs_test = url.indexOf("//");
		if (abs_test != -1) // trim server name
		{
			url = url.substring( (abs_test+2) );
			url = url.substring(url.indexOf("/"));
		}
        return url.substring(0, url.lastIndexOf("/") )+"/";
}

function queryString( key, url )
{
	if ( (url.search( RegExp( "[?&]" + key + "=([^&$]*)", "i" ) )) > -1 ) return RegExp.$1;
	else return null;
}
