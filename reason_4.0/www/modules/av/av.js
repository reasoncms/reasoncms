/**
 * This shows and hides the share/download info for the active video in the av module.  It also 
 * allows for dynamic switching of video sizes.
 *
 * @author Marcus Huderle
 * @requires jQuery
 */

$(document).ready(function() 
{
	var small_video_height = $("div.size_links ul li.small_link").attr("data-size");
	var medium_video_height = $("div.size_links ul li.medium_link").attr("data-size");
	var large_video_height = $("div.size_links ul li.large_link").attr("data-size");

	var show_link_p = $("<h4 class=\"share_download_header\"></h4>")
	var show_link = $("<a href=\"\">Share/Download</a>");
	
	download = $("div.share_download_info div.download").html() != null;
	embed = $("div.share_download_info div.embed").html() != null;
	
	if (download == true && embed == true)
	{
		show_link = $("<a href=\"\">Share/Download</a>");
    }
    else if (embed == true)
    {
    	show_link = $("<a href=\"\">Share</a>");
    }
    
    var small_link = $("div.size_links ul li.small_link");
    $("a", small_link).bind('click', small_click);
    
    var medium_link = $("div.size_links ul li.medium_link");
    $("a", medium_link).bind('click', medium_click);
    
	var large_link = $("div.size_links ul li.large_link");
    $("a", large_link).bind('click', large_click);
    
    var last_link = null;

	if ($("a", small_link).html() == null)
		last_link = small_link;
	else if ($("a", medium_link).html() == null)
		last_link = medium_link;
	else
		last_link = large_link;
    
    if (download == true || embed == true)
	{ 
		$("div.share_download_info").before(show_link_p.append(show_link));
    	$("div.share_download_info").hide();
    }
    
    var iframe = $("div.displayer iframe.media_work_iframe");
    var iframe_height = -1;
    var iframe_width = -1;
        
    show_link.click(function() 
    {
    	$("div.share_download_info").show();
    	$(this).hide();
    	return false;
    });
    
    function small_click() 
    {
    	return perform_click(small_video_height, small_link);
    };    
  
    function medium_click() 
    {
    	return perform_click(medium_video_height, medium_link);
    };    
  
    function large_click() 
    {
    	return perform_click(large_video_height, large_link);
    };  
    
    function perform_click(height, button)
    {
    	ratio = iframe.attr('width')/iframe.attr('height');
    	new_width = ratio * height;
    	change_iframe(height, new_width);
    	
    	linkify_button(last_link);
    	unlinkify_button(button);
    	
    	old_embed_code = $("div.embed textarea.embed_code").html();
    	if (old_embed_code != null)
    	{
    		new_embed_code = old_embed_code.replace(/size=[0-9][0-9][0-9]/g, "size=" + height);
    		new_embed_code = new_embed_code.replace(/width=[0-9][0-9][0-9]/g, "width=" + new_width);
    		new_embed_code = new_embed_code.replace(/height=[0-9][0-9][0-9]/g, "height=" + height);
    	
    		new_embed_code = $("div.embed textarea.embed_code").html(new_embed_code);
    	}
    	
    	return false;
    }
    
    function linkify_button(button)
    {
    	if (button != null)
    	{
    		html = $("strong", button).html();
 	   		$("strong", button).replaceWith('<a href="' + button.attr("data-link") + '">' + html + '</a>');
 	   		
			if (button  == small_link)
				button.bind('click', small_click);
			else if (button == medium_link)
				button.bind('click', medium_click);
			else if (button == large_link)
				button.bind('click', large_click);
 	   	}
    }
    
    function unlinkify_button(button)
    {
    	last_link = button;
    	html = $("a", button).html();
    	$("a", button).replaceWith("<strong>" + html + "</strong>");
    }
    
    function change_iframe(new_height, new_width)
    {	
    	oldsrc = iframe.attr("src");
    	parsed = parseQueryString(oldsrc);
    	parsed['height'] = new_height;
    	parsed['width'] = new_width;
    	
    	query_index = oldsrc.indexOf('?');
    	oldsrc = oldsrc.slice(0, query_index+1);
    	
    	newsrc = oldsrc + $.param(parsed);

    	iframe.attr("src", newsrc);
    	iframe_height = new_height;
    	iframe_width = new_width;
    }
    
    iframe.load(function()
    {
    	if (iframe_height != -1)
    	{
    		iframe.attr("height", iframe_height);
    		iframe.attr("width", iframe_width);
    		
    		size = null;
    		if (iframe_height == small_video_height)
    		{
    			size = 'small';
    		}
    		else if (iframe_height == medium_video_height)
    		{
    			size = 'medium';
    		}
    		else if (iframe_height == large_video_height)
    		{
    			size = 'large';
    		}
    		else 
    		{
    			size = 'small';
    		}
    		
    		// Updates the download links
    		mp4_link = $("ul.media_file_list li.mp4_li a");
    		webm_link = $("ul.media_file_list li.webm_li a");
    		
    		mp4_link.attr("href", mp4_link.attr("data-"+size+"-url"));
    		webm_link.attr("href", webm_link.attr("data-"+size+"-url"));
    	}
    });
    
    // slightly modified from http://paulgueller.com/2011/04/26/parse-the-querystring-with-jquery/
    function parseQueryString(query)
    {
    	var nvpair = {};
    	var index = query.indexOf('?');
		var qs = query.slice(index+1, query.length);
		var pairs = qs.split('&');
		$.each(pairs, function(i, v){
		  var pair = v.split('=');
		  nvpair[pair[0]] = pair[1];
		});
		return nvpair;
    }
    
    $("textarea.embed_code").click(function() {
		$(this).select();
	});
        
});