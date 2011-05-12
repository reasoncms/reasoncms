/**
* This file is used to control the feature navigtion
* There are slide show controls as well as next and prev controls
*
* @author Frank McQuarry
*
* @author Nathan White
*
* - Added proper z-index handling and modified peekaboo to handle multiple clicks.
* - Established relative positioning for the container ul to handle page resize better.
* - Fixed first feature bug (no transition)
*
* @todo the use of "curr" seems confusing and ids - why do we need a special mapping??
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
			element_index = $($this).index(top_element)
			if (element_index != -1) $($this[element_index]).css('z-index', 999999)
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
	var curr=0; //the current feature being played in the slide show
	var ids= new Array(); //an array to hold the ids of features.
	var timeInterval=-1; //-1 means that autoplay is not running.
	var autoplay_timer; //how long in seconds to play each feature
	var looping; //determines if slides loop or not
	var arrows_toggle=false; //if it doesn't loop then show > on first slide
		                     // < on last slide, and <> inbetween
		                     
	init_features();
	
	
	/**
	* grabs the links in the feature navigation and 
	* implements showing the selected feature
	*/
	$("div.featureNav > span.navBlock > a").click(function(event)
	{
		event.preventDefault();
		
		//get the feature id encoded in the url
		var url = $(this).attr('href');
		var id = _queryString("feature",url);
		var new_feature = true;
		
 		if (id != null)
		{
			//turn off autoplay if its running
			if(timeInterval!=-1)
			{
				toggle_play_pause("play");
				clearInterval(timeInterval);
				timeInterval=-1;
			}
			if(id == ids[curr])
			{
				if(!$(this).hasClass('prev') && !$(this).hasClass('next'))
				{
					new_feature = false;
				}
			}	

			//find the current feature
			n=ids.length;
			for(i=0;i<n;i++)
			{
				if(ids[i]==id)
				{
					curr=i;
					break;
				}
			}
			
			//show the selected feature	
			if(new_feature)
			{
				peekaboo(id);
			}
		}
		else if (id == null)
		{
			if($(this).hasClass('play'))
			{
				toggle_play_pause("pause");
				adjust_current(1);
				timeInterval=setInterval(play_feature_slideshow,autoplay_timer*1000);
			}
			else if($(this).hasClass('pause'))
			{
				toggle_play_pause("play");
				adjust_current(-1);
				clearInterval(timeInterval);
				timeInterval=-1;
			}			
		}
		if(arrows_toggle)
		{
			toggle_arrows();
		}

	});
	
	$("div.featureNav > span.play-pause > a").click(function(event){
		event.preventDefault();
		if($(this).hasClass('play'))
		{
			toggle_play_pause("pause");
			adjust_current(1);
			timeInterval=setInterval(play_feature_slideshow,autoplay_timer*1000);
		}
		else if($(this).hasClass('pause'))
		{
			toggle_play_pause("play");
			adjust_current(-1);
			clearInterval(timeInterval);
			timeInterval=-1;
		}			
	
	});
	
	/**
	 * display the feature with the given feature_id - fade out all others
	 */
	function peekaboo(feature_id)
	{
		$('#feature-'+feature_id+':first').each(function()
		{
			$("ul.features li.feature").normalize_z_index($(this));
			$(this).siblings().stop(true).fadeTo(600, 0, function()
			{
				$(this).removeClass('active').addClass('inactive');
			});
			$(this).stop(true).fadeTo(600, 1, function()
			{
				$(this).removeClass('inactive').addClass('active');
			});
		});
	}
	
	/**
	* set offset the current feature index "curr" by num
	* tie the beginning to the end
	* NOTE: this function expects num to be less than ids.length
	* unexpected behavior if you try to big a jump
	* generally use +1 or -1 as num
	*/
	function adjust_current(num)
	{
		var n=ids.length;
		curr=curr+num;
		if(num>0)
		{
			if(curr>=n)
			{
				curr=0;
			}
		}
		else if(num<0)
		{
			if(curr<0)
			{
				curr=(n-1);
			}
			
		}	
	}
	
	/**
	* toggles the prev and next arrows
	* for when looping is turned off
	*/
	function toggle_arrows()
	{
		prev=$("a[class~=prev]");
		next=$("a[class~=next]");

		n=ids.length-1;
		if(curr==0)
		{
			prev.hide();
			next.show();
		}
		else if(curr==n)
		{
			prev.show();
			next.hide();
		}
		else
		{
			prev.show();
			next.show();
		}

	}
	
	/**
	* only show the play or the pause controls
	* never show them together.
	*/
	function toggle_play_pause(show)
	{
		playbutton=$("a[class~=play]");
		pausebutton=$("a[class~=pause]");

		if(show=="play")
		{
			playbutton.show();
			pausebutton.hide();
		}
		else if (show=="pause")
		{
			playbutton.hide();
			pausebutton.show();
		}
	}
	
	/**
	* controls features when in slide show mode
	*/
	function play_feature_slideshow()
	{
		var n=ids.length;
		if(curr<n && curr>=0)
		{
			peekaboo(ids[curr])
		}
		adjust_current(1);
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
	* initializes features slide show
	*/
	function init_features()
	{
		$("ul.features").css("position: relative");
		$("ul.features li.feature").normalize_z_index($("ul.features li.feature.active")).css('position', 'absolute');
		$("ul.features li.feature.active").css('opacity', '1'); // set the initial opacity otherwise first transition is not smooth
		$("ul.features li.feature.inactive").css('opacity', '0'); // set the initial opacity otherwise first transition is not smooth
		features= $("ul.features li.feature");	
		
		//get the value of autoplay
		//then  set the global ids array
		//then set the callback to play
		//the slide show--0 means no slideshow
		if(features.length>=2)//no need to bother for only 1 feature
		{
			autoplay=$("div[class*=autoplay]");
			//get the value of class out as a string
			temp1=autoplay.attr('class');
			//turn the string into an array
			temp2=temp1.split(' ');
			//get the autplay-x string
			temp3=temp2[1].split('-');
			//now get the value of autoplay
			autoplay_timer= +temp3[1];
			/* autoplay_timer is in seconds */
			
			//determine whether looping is turned on
			temp3=temp2[2].split('-');
			looping=temp3[1];
			if(looping=="off")
			{
				arrows_toggle=true;
			}
			
			features.each(function(index){
				var tmp=$(this).attr('id');
				tmp2=tmp.split('-');
				id=tmp2[1];
				ids[index]=id;
			})
			
			if(arrows_toggle)
			{
				toggle_arrows();
			}
			
			
			if(autoplay_timer>0)
			{
				toggle_play_pause("pause");
				curr=1;
				timeInterval=setInterval(play_feature_slideshow,autoplay_timer*1000);
			}
			else if(autoplay_timer==0)
			{
				playbutton=$("a[class~=play]");
				playbutton.hide();
				pausebutton=$("a[class~=pause]");
				pausebutton.hide();
			}
			
			//now determine the navigation
			//if the feature navigation is so long it is wrapping
			//into two or more lines, there pull out all the links
			//and only use the arrow keys.
			var nav_block=$('.navBlock');
			var features=$('.featureInfo');
			var nav_w=nav_block.width();
			var feat_w=features.width();
			
			if(nav_w!=0 && feat_w!=0)//only Anarchists divide by zero.
			{
				if( (nav_w/feat_w)>0.8 )
				{
					var prev=$('.button.prev');
					var next=$('.button.next');
					var nav_link =$('.featureNav > span.navBlock > a')
					nav_link.hide();
					prev.show();
					next.show();
				}
			}			
		}	
	} //end init_features
	
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
			processHandler:size_modal_window
		});
	}
});