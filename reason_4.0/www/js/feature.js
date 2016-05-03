/**
* This file is used to control the feature navigation
* There are slide show controls as well as next and prev controls
* October, 2010
* @author Frank McQuarry
*
* Largely rewritten to use the DOM itself to track state, removing lots of complexity and fixing bugs - 7/21/11
* @author Nathan White
*
* - Added proper z-index handling and modified peekaboo to handle multiple clicks.
* - Established relative positioning for the container ul to handle page resize better.
* - Fixed transition bug when moving from first feature (no transition)
* - Eliminated use of "curr" in favor of looking at the DOM.
* - Eliminated building of an ids array - the complexity is not needed.
* - Modified to theoretically support multiple instances of the module on the page - though problems remain.
*
* @todo fix multiple instance problems with video / layout / autoplay_interval.
*/

/**
 * Little plug-in to normalize or establish (ordered going down to 1) the z-index on a set of elements
 *
 * - You can optionally specify an element to go to the top
 * - Indexing starts at the lowest existing value in the set, or if there are no values, 1
 *
 * @author Nathan White
 */
(function($)
{
	$.fn.normalize_z_index = function( top_element )
	{
		var zindex = new Array();
		var baseline = 1;
		var $this = $(this);

		// force our element to the top immediately with a huge z-index value
		if (top_element)
		{
			element_index = $($this).index(top_element);
			if (element_index != -1) $($this[element_index]).css('z-index', 999999);
		}

		// build zindex - reduce baseline if necessary
		$($this).each(function(index)
		{
			if (!isNaN(parseInt($(this).css('z-index'), 10)))
			{
				zindex[index] = $(this).css('z-index');
				if (zindex[index] < baseline) baseline = zindex[index];
			}
			else zindex[index] = (index * -1);
		});

		/// iterate through a sorted copy of zindex
		$.each(zindex.slice(0).sort(function(a,b){return a - b}), function(i, z)
		{
			$($this[$.inArray(z,zindex)]).css('z-index', (baseline*1+i*1));
		});
		return this;
	};
})(jQuery);

$(document).ready(function()
{
	var autoplay_interval; // we declare a variable to reference setInterval if autoplay is used.

	/**
	* Setup everything for any instanc(es) of the features module on the page
	*/
	$("div.featuresModule").each(function()
	{
		var features_module = $(this);

		// add active class to the first child of each
		// featureModule div (for displaying multiple features on a page)
		$("ul.features li.feature:first-child").removeClass( "inactive" ).addClass( "active" );

		// setup initial z-index and opacity settings
		$("ul.features", features_module).css("position: relative");
		$("ul.features li.feature", features_module).normalize_z_index($("ul.features li.feature.active")).css('position', 'absolute');
		$("ul.features li.feature.active", features_module).css('opacity', '1'); // set the initial opacity otherwise first transition is not smooth
		$("ul.features li.feature.inactive", features_module).css('opacity', '0'); // set the initial opacity otherwise first transition is not smooth

		// click events for numbered items and arrows
		$("div.featureNav > span.navBlock > a", features_module).click(function()
		{
			//get the feature id encoded in the url
			var url = $(this).attr('href');
			var id = _queryString("feature",url);
			stop_autoplay(features_module);
			peekaboo($('#feature-'+id+':first', features_module));
			return false;
		});

		// click event for play button
		$("div.featureNav > span.play-pause > a.play", features_module).click(function()
		{
			start_autoplay(features_module);
			return false;
		});

		// click event for pause button
		$("div.featureNav > span.play-pause > a.pause", features_module).click(function()
		{
			stop_autoplay(features_module);
			return false;
		});

		// setup start over button if it exists
		$("div.featureNav > span.play-pause > a.startOver").each(function()
		{
			$(this).show();
			$(this).click(function()
			{
				peekaboo($('ul.features li.feature:first', features_module));
				return false;
			});
		});

		make_navigation_friendly(features_module);
		setup_looping(features_module);
		start_autoplay(features_module);
	});

	/**
	 * A few alterations to make the navigation more friendly
	 * - If the length of our navigation is too long - just show forward / back arrows.
	 * - If there is only one feature and we aren't showing a title or text lets hide the nav div entirely.
	 */
	function make_navigation_friendly(features_module)
	{
		if ($("ul.features li.feature", features_module).length > 1)
		{
			// grab width of navBlock and featureInfo div
			var nav_w=$('ul.features li.feature:first div.featureNav > span.navBlock', features_module).width();
			var feat_w=$('ul.features li.feature:first .featureInfo', features_module).width();

			// if the nav is taking up too much space lets just show the arrows:
			if ( ((nav_w != 0) && (feat_w != 0)) && ((nav_w / feat_w) > 0.8) )
			{
				$('ul.features li.feature div.featureNav > span.navBlock > a', features_module).hide();
				$('ul.features li.feature div.featureNav > span.navBlock > a.button.prev', features_module).show();
				$('ul.features li.feature div.featureNav > span.navBlock > a.button.next', features_module).show();
			}
		}
		if ($("ul.features li.feature", features_module).length == 1)
		{
			if ($('ul.features li.feature div.featureNav', features_module).siblings().length == 0)
			{
				$('ul.features li.feature div.featureNav', features_module).hide();
			}
		}
	}

	/**
	 * If the features module disables looping, hide the navigation elements from the first and last feature.
	 */
	function setup_looping(features_module)
	{
		if (!looping_enabled(features_module))
		{
			// selectively hide arrows
			$("ul.features li.feature:first .featureNav .navBlock a.prev", features_module).hide();
			$("ul.features li.feature:last .featureNav .navBlock a.next", features_module).hide();
		}
	}

	/**
	 * set an interval at which we call play feature slideshow and set the button to pause mode.
	 */
	function start_autoplay(features_module)
	{
		if (timer = get_autoplay_timer(features_module))
		{
			play_func = function()
			{
				play_feature_slideshow(features_module);
			}
			autoplay_interval = setInterval(play_func, timer);
			$("div.featureNav > span.play-pause > a.play", features_module).hide();
			$("div.featureNav > span.play-pause > a.pause", features_module).show();
		}
	}

	/**
	 * clear the interval we use to call play feature slideshow and set the button to play mode.
	 */
	function stop_autoplay(features_module)
	{
		clearInterval(autoplay_interval);
		$("div.featureNav > span.play-pause > a.play", features_module).show();
		$("div.featureNav > span.play-pause > a.pause", features_module).hide();
	}

	/**
	 * Does a quick regexp to see if there is an autoplay timer set - returns the timer value which represents the delay between slides
	 */
	function get_autoplay_timer(features_module)
	{
		var autoplay_regex = 'autoplay-[1-9][0-9]*';
		var features_module_class = features_module.attr('class');
		var regex = new RegExp(autoplay_regex);
		var match = regex.exec(features_module_class);
		return (match) ? (match[0].match(/\d+/)*1000) : false;
	}

	function looping_enabled(features_module)
	{
		looping_off = features_module.hasClass("looping-off");
		return (looping_off) ? false : true;
	}

	/**
	 * if our feature is not already active, display the feature - fade out all others
	 */
	function peekaboo(feature)
	{
		if (!feature.hasClass("active"))
		{
			$("li.feature", $(feature).parent()).normalize_z_index(feature);
			feature.siblings().stop(true).fadeTo(600, 0, function()
			{
				$(this).removeClass('active').addClass('inactive');
			});
			feature.stop(true).fadeTo(600, 1, function()
			{
				$(this).removeClass('inactive').addClass('active');
			});
		}
	}

	/**
	 * If the length of the combined active and first feature selector is equal to 1, we are on the first feature.
	 */
	function is_first_feature(feature)
	{
		return ($("li.active, li.feature:first", $(feature).parent()).length == 1);
	}

	/**
	 * If the length of the combined active and last feature selector is equal to 1, we are on the last feature.
	 */
	function is_last_feature(feature)
	{
		return ($("li.active, li.feature:last", $(feature).parent()).length == 1);
	}

	/**
	 * If the length of the combined active and first feature selector is equal to 1, we are on the first feature.
	 */
	function on_first_feature(features_module)
	{
		return ($("li.active, li.feature:first", features_module).length == 1);
	}

	/**
	 * If the length of the combined active and last feature selector is equal to 1, we are on the last feature.
	 */
	function on_last_feature(features_module)
	{
		return ($("li.active, li.feature:last", features_module).length == 1);
	}

	/**
	* controls features when in slide show mode
	*/
	function play_feature_slideshow(features_module)
	{
		// if we are on the last feature, loop to first, otherwise peekaboo next feature
		if (on_last_feature(features_module) && looping_enabled(features_module))
		{
			peekaboo($("li.feature:first", features_module));
		}
		else
		{
			peekaboo($("li.active", features_module).next());
			if (on_last_feature(features_module) && !looping_enabled(features_module))
			{
				stop_autoplay(features_module);
			}
		}
	}

	/**
	 * Helper function to grab the value that corresponds to a key in a url
	 */
	function _queryString( key, url )
	{
		if ( (url.search( RegExp( "[?&]" + key + "=([^&$]*)", "i" ) )) > -1 ) return RegExp.$1;
		else return null;
	}

	function size_modal_window(settings)
	{
		settings.height=700;
		$('#nyroModalWrapper').css('height','700px');
	}

	var box=$('.featureTypeVideo a.anchor');
	if(box.length>0)
	{
		// lets find the reason_package_http_base path and thus, our cross icon by looking at the nyroModal location
		var silk_icon_src = new String();
		$("script[src*='/nyroModal/']:first").each(function()
		{
	  		var srcParts = $(this).attr("src").split("nyroModal/");
	  		silk_icon_src = srcParts[0]+"silk_icons/cross.png";
		});
		box.nyroModal(
		{
			bgColor:'#000000',
			closeButton: '<a href="#" class="nyroModalClose" id="closeBut" title="close"><img src="'+silk_icon_src+'" alt="Close" width="16" height="16" class="closeImage" /><span class="closeText">Close</span></a>',
			processHandler:size_modal_window,
			zIndexStart:9999,
		});
	}
});