/** 
 * adjust_dimensions.js checks each list item and adjusts the height of list items in rows where content clipping occurs
 *
 * @author Nathan White
 * @requires jQuery
 */

$(document).ready(function()
{

	
	function adjustHeights()
	{
		var row_number = 0;
		var row_position;
		var row_number = 1;
		var row_max = 0;
		var regex = /row[0-9]/;
		
		
		
		$('ul#imageGalleryItemList li').each(function() {
			class_str = $(this).attr('class');
			if (class_str != undefined)
				$(this).attr('class', class_str.replace( regex, '' ) );
        });
        
		$("ul#imageGalleryItemList li").each(function(index)
		{
			$(this).height('auto');
			if(row_position != undefined && (row_position != $(this).position().top))
			{
				$('ul#imageGalleryItemList li.row' + row_number).height(row_max);
				row_number++;
				row_max = 0;
			}
			$(this).addClass('row' + row_number);
			row_position = $(this).position().top;
			row_max = Math.max($(this).height(),row_max);
		}); 
		$('ul#imageGalleryItemList li.row' + row_number).height(row_max);
	};
	
	function calculateColumns()
	{
		item_width = $("ul#imageGalleryItemList li").outerWidth(true);
		gallery_width = $("ul#imageGalleryItemList").width();
		return Math.floor(gallery_width / item_width);
	};
	
	adjustHeights();
	
	current_columns = calculateColumns();
	
	$(window).resize(function() {
		new_columns = calculateColumns();
		if(new_columns != current_columns)
		{
			adjustHeights();
			current_columns = new_columns;
		}
	});
});