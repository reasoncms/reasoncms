/**
 * This jQuery file hides and shows the option decription editor for inline editing in a 
 * publications module.
 *
 * @author Marcus Huderle
 * @requires jQuery
 */
 
$(window).load(function() 
{
	var clone_row = $("tr#descriptionofstoryRow").clone().attr("id","showdescriptionRow").find("*").html("").end();
	var link = $("<a href=\'\'>Show excerpt/teaser</a>");
	
	link.click(function()
	{
		if (link.text() == "Show excerpt/teaser")
		{
			set_heights_and_visibility();
			link.text("Hide excerpt/teaser");
		}
		else
		{
			set_heights_and_visibility();
			link.text("Show excerpt/teaser");
		}
		//matchColumns();
		return false;
	});
	
	$("td.words", clone_row).html(link);

	// Place the "show/hide description" link before the disco editor
	$("#descriptionofstoryRow").before(clone_row);

	set_heights_and_visibility();
	
	function set_heights_and_visibility()
	{
		var cur = $("#descriptionofstoryRow").css("display");
		if (cur == 'none')
		{
			$("#descriptionofstoryRow").css("display", "");
		}
		else
		{
			$("tr#showdescriptionRow td.words").width($("#descriptionofstoryRow td.words").width());
			$("#descriptionofstoryRow").css("display", "none");
		}
		
		// attempt to fire a resize event
		var evt = document.createEvent('UIEvents');
		evt.initUIEvent('resize', true, false,window,0);
		window.dispatchEvent(evt);
	}
});