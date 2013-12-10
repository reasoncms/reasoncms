/**
 *  This js allows for dynamic switching of video sizes for integrated media works with youtube.
 *
 * @author Marcus Huderle
 * @requires jQuery
 */

$(document).ready(function() 
{
	var small_video_height = $("div.size_links ul li.small_link").attr("data-size");
	var medium_video_height = $("div.size_links ul li.medium_link").attr("data-size");
	var large_video_height = $("div.size_links ul li.large_link").attr("data-size");
	
    var small_link = $("div.size_links ul li.small_link");
    $("a", small_link).bind('click', small_click);
    
    var medium_link = $("div.size_links ul li.medium_link");
    $("a", medium_link).bind('click', medium_click);
    
	var large_link = $("div.size_links ul li.large_link");
    $("a", large_link).bind('click', large_click);
    
    var last_link = null;
	
	if ($("a", small_link).attr("href") == null)
		last_link = small_link;
	else if ($("a", medium_link).attr("href") == null)
		last_link = medium_link;
	else
		last_link = large_link;
        
    var iframe = $("iframe.media_work_iframe");
    var iframe_height = -1;
    var iframe_width = -1;
    
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
    	if ( !$("strong", button).eq(0).is('strong') )
    	{
			ratio = iframe.attr('width')/iframe.attr('height');
			new_width = ratio * height;
			change_iframe(height, new_width);
			
			linkify_button(last_link);
			unlinkify_button(button);	
			
			if ($.fn.updateFitVid) { //if fitvids is there
				iframe.updateFitVid(new_width); //update the style on the video
			}
		}
		return false;
    }
    
    function linkify_button(button)
    {
    	if (button != null)
    	{
    		$("a", button).unwrap();
 	   		$("a", button).attr("href", button.attr("data-link"));
 	   		
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
    	$("a", button).removeAttr("href");
    	$("a", button).wrap("<strong />");
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
        
});