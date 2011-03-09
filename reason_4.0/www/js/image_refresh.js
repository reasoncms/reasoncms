/**
 * jQuery image refresh script
 *
 * The script will operate on all images on a page with have a class that contains the substring refresh.
 *
 * The script can be called with parameters - the following are valid:
 *
 * @param refresh_time string change the refresh interval used by the class from the default 30 seconds to something else
 * @param class_substring string change the class substring used by the class from the default "refresh" to something else
 * @param src_contains string additionally include images that have an src containing the substring src_contains
 *
 * Example Script Load - Refresh all images on a page with src's that include webcam (or have class refresh) every 10 seconds
 *
 * <script type="text/javaScript" src="/js/image_refresh.js?src_contains=webcam&refresh_time=10"></script>
 *
 * Note that src_contains must contain only numbers, letters, _, ., #, -, and % characters.
 * Also note that any class_substring replacement must contain only number, letters, _, and - characters.
 *
 * Each image can set its own refresh time (in seconds) by including the class name refresh (or the name specified in class_substring)
 * followed by an integer.
 *
 * Example Image Syntax
 *
 * <img class="refresh25" src="/my_image.jpg">
 *
 * @author Nathan White
 */

$(document).ready(function()
{
	// defaults
	var default_refresh_time = 30;
	var default_refresh_class = "refresh";
	
	var js_src = $('script[src*="image_refresh.js"]:first').attr("src");
	
	// grab variables from query string
	var js_refresh_time = parseInt(_queryString('refresh_time', js_src), 10);
	var js_class_substring = _verifyClassSubstring(_queryString('class_substring', js_src));
	var js_src_contains = _verifySrcContains(_queryString('src_contains', js_src));

	// setup locals based upon query string values or defaults
	var refresh_time = (js_refresh_time) ? js_refresh_time : default_refresh_time;
	var class_substring = (js_class_substring) ? js_class_substring : default_refresh_class;
	var src_selector = (js_src_contains) ? ",img[src*="+js_src_contains+"]" : "";
	
	// select images with the class_substring and set them up to refresh
	$("img[class*="+class_substring+"]"+src_selector).each(function()
	{
		var image = $(this);
		
		// extract only digits from the custom class name ... probably a simpler regexp to do it in a single step but oh well.
		var image_class = image.attr('class');
		var refresh_check = (image_class) ? image_class.match(new RegExp(class_substring+"\\d*")) : false;
		var img_refresh_time = (refresh_check) ? refresh_check.toString().replace(/\D/g, "").toString() : refresh_time;
		
		setInterval(function()
		{
			refreshImage(image);
		}, (img_refresh_time * 1000) );
	});
	
	/**
	 * Change the src of the image using a unique id, triggering a reload
	 */
	function refreshImage(image)
	{
		var unique_id = ((new Date()).getTime().toString() + Math.floor(Math.random() * 1000000)).substr(0, 18);
		var image_src = image.attr("src");
		var extra_bit = "image_refresh_bit="+unique_id;
		var new_src = image_src.replace(/image_refresh_bit=\d*/, extra_bit);
		if (image_src == new_src)
		{
			var pre_extra_bit = (image_src.match(/\?/)) ? "&" : "?";
			var new_src =image_src+pre_extra_bit+extra_bit;
		}
		image.attr("src", new_src);
	}
	
	/** 
	 * Helper function to grab the value that corresponds to a key in a url
	 */
	function _queryString( key, url )
	{
		if ( (url.search( RegExp( "[?&]" + key + "=([^&$]*)", "i" ) )) > -1 ) return RegExp.$1;
		else return null;
	}
	
	/**
	 * Helper to verify the integrity of the class_substring parameter
	 */
	function _verifyClassSubstring( string )
	{
		if (string)
		{
			if (string.match(/^[a-z0-9_-]*$/i))
			{
				return string;
			}
		}
		return false;
	}
	
	/**
	 * Helper to verify the integrity of the src_contains parameter
	 */
	function _verifySrcContains( string )
	{
		if (string)
		{
			if (string.match(/^[a-z0-9_%.#-]*$/i))
			{
				return string;
			}
		}
		return false;
	}
});
