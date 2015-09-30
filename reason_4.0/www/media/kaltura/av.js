/**
 * This shows and hides the share/download info for the active video in the av module.
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
	
	var size = -1;
	
	download = $("div.share_download_info div.download").html() != null;
	embed = $("div.share_download_info div.embed").html() != null;
	
	var iframe = $("iframe.media_work_iframe");
	
	if (download == true && embed == true)
	{
		show_link = $("<a href=\"\">Share/Download</a>");
    }
    else if (embed == true)
    {
    	show_link = $("<a href=\"\">Share</a>");
    }
    
    if (download == true || embed == true)
	{ 
		$("div.share_download_info").before(show_link_p.append(show_link));
    	$("div.share_download_info").hide();
    }
        
    show_link.click(function() 
    {
    	$("div.share_download_info").show();
    	$(this).hide();
    	return false;
    });
        
	var small_link = $("div.size_links ul li.small_link");
    $("a", small_link).bind('click', small_click);
    
    var medium_link = $("div.size_links ul li.medium_link");
    $("a", medium_link).bind('click', medium_click);
    
	var large_link = $("div.size_links ul li.large_link");
    $("a", large_link).bind('click', large_click);
    
    function small_click() 
    {
    	size = "small";
    	return perform_click(small_video_height);
    };    
  
    function medium_click() 
    {
    	size = "medium";
    	return perform_click(medium_video_height);
    };    
  
    function large_click() 
    {
    	size = "large";
    	return perform_click(large_video_height);
    };  
    
    function perform_click(height)
    {
    	old_embed_code = $("div.embed textarea.embed_code").html();
    	if (old_embed_code != null)
    	{
    		ratio = iframe.attr('width')/iframe.attr('height');
    		new_width = Math.round(ratio * height);
    		new_embed_code = old_embed_code.replace(/height="[0-9][0-9][0-9]"/g, 'height="' + height+'"');
    		new_embed_code = new_embed_code.replace(/width="[0-9][0-9][0-9]"/g, 'width="' + new_width+'"');
    		new_embed_code = new_embed_code.replace(/height=[0-9][0-9][0-9]/g, "height=" + height);
    		new_embed_code = new_embed_code.replace(/width=[0-9][0-9][0-9]/g, "width=" + new_width);
    	
    		$("div.embed textarea.embed_code").html(new_embed_code);
    	}
    	return false;
    }
    
    iframe.load(function()
    {		
		// Updates the download links
		mp4_link = $("ul.media_file_list li.mp4_li a");
		webm_link = $("ul.media_file_list li.webm_li a");
		mp4_link.attr("href", mp4_link.attr("data-"+size+"-url"));
		webm_link.attr("href", webm_link.attr("data-"+size+"-url"));
		
    });
    
    
    $("textarea.embed_code").click(function() {
		$(this).select();
	});
        
});