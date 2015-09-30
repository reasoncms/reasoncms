/**
 * This jQuery file hides and shows the excess relationships in the entity info displayer.
 *
 * @author Marcus Huderle
 * @requires jQuery
 */

$(document).ready(function() 
{
	$(".EntityInfo table.entityInfoTable").each(function() 
	{
		if (countRows(this) > 10)
		{
			shortenMe(this);
		}
	});
	
	
	function countRows(table)
	{
		return $("tr", table).length;
	}

	function shortenMe(table)
	{
		num = 0;
		$("tr", table).each(function(index) 
		{
			if (index > 9)
			{
				$(this).hide();
			}
			num++;
		});
		
		mylink = $("<a href=\'\'>Show All "+num+"</a>");

		$(table).after(mylink);
		mylink.click(function()
			{
				showMe(table);
				$(this).remove();
				return false;
			});
	}
	
	function showMe(table)
	{
		$("tr", table).each(function(index) 
		{
			if (index > 9)
			{
				$(this).show();
			}
		});
		
		mylink = $("<a href=\'\'>Collapse</a>");
		$(table).after(mylink);
		mylink.click(function()
			{
				shortenMe(table);
				$(this).remove();
				return false;
			});
	}
});