/**
 * This jQuery file hides and shows the option decription editor for inline editing in a 
 * publications module.
 *
 * @author Marcus Huderle
 * @requires jQuery
 */
 
$(window).load(function() 
{
	var clone_row = $("tr#descriptionRow").clone().attr("id","showdescriptionRow").find("*").html("").end();
	var link = $("<a href=\'\'>Show Excerpt/Teaser</a>");
	
	link.click(function()
	{
		if (link.text() == "Show Excerpt/Teaser")
		{
			set_heights_and_visibility();
			link.text("Hide Excerpt/Teaser");
		}
		else
		{
			set_heights_and_visibility();
			link.text("Show Excerpt/Teaser");
		}
		//matchColumns();
		return false;
	});
	
	$("td.words", clone_row).html(link);

	// Place the "show/hide description" link before the disco editor
	$("#descriptionRow").before(clone_row);

	set_heights_and_visibility();
	
	function set_heights_and_visibility()
	{
		var cur = $("#descriptionRow").css("display");
		if (cur == 'none')
		{
			$("#descriptionRow").css("display", "");
		}
		else
		{
			$("tr#showdescriptionRow td.words").width($("#descriptionRow td.words").width());
			$("#descriptionRow").css("display", "none");
		}
		
		// attempt to fire a resize event
		var evt = document.createEvent('UIEvents');
		evt.initUIEvent('resize', true, false,window,0);
		window.dispatchEvent(evt);
	}
});