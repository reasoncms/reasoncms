// Request quote via ajax request and display the quote - requires jQuery
// @author Nathan White

viewed_quotes = [];
pending_quote = '';
pending_author = '';

$(document).ready(function()
{
	var js_src = $('script[src*=quote_retrieve]:first').attr("src");
	
	// define the filepath to which we will post data
	js_post_path = getDirectory(js_src)+"quote_retrieve.php";
	
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
	var refresh_div = '<div id="refresh_link"><p><a href="#">New Quote</a></p></div>';
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
			$(data).find('text').each(function(){
				var quote_text = $(this).text();
				pending_quote = quote_text;
				});
			$(data).find('author').each(function(){
				var quote_author = $(this).text();
				pending_author = quote_author;
				});
			$(data).find('id').each(function(){
				js_cur_quote_id = $(this).text();
				add_viewed_quote(js_cur_quote_id);
				});
			link_enabled = true;
		}, "xml");
}

function replace_quote()
{
	if (link_enabled)
	{
		link_enabled = false;
		text_html = '<p class="quoteText">'+pending_quote+'</p>';
		if (pending_author != "") text_html += '<p class="quoteAuthor">'+pending_author+'</p>';
		$("div#quotes .quote").fadeTo(100, 0, function() {
			$(this).html(text_html).fadeTo(300, 1);
		});
		grab_quote();
	}
}

function getDirectory( url )
{
	strpos = url.lastIndexOf("/");
	return url.substring(0, strpos)+"/";
}

function queryString( key, url )
{
	if ( (url.search( RegExp( "[?&]" + key + "=([^&$]*)", "i" ) )) > -1 ) return RegExp.$1;
	else return null;
}