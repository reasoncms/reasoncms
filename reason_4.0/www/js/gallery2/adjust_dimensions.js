/** 
 * adjust_dimensions.js checks each list item and adjusts the height of list items in rows where content clipping occurs
 *
 * @author Nathan White
 * @requires jQuery
 */

$(document).ready(function()
{
	var row_number = 0;
	var row_position;
	var item_to_row = new Array();
	var row_to_size_original = new Array();
	var row_to_size_new = new Array();
	
	init();
	run();
	
	function init()
	{
		$("ul#imageGalleryItemList li.item").each(function(index)
		{
			var scrollHeight = $(this).attr('scrollHeight');
			var actualHeight = $(this).height();
			var new_row = (row_position != undefined) ? (row_position != $(this).position().top) : false;
			
			row_number = (new_row) ? (row_number + 1) : row_number;
			row_position = $(this).position().top;
			item_to_row[index] = row_number;
			row_to_size_original[row_number] = (row_to_size_original[row_number] == undefined) 
											 ? actualHeight 
											 : row_to_size_original[row_number];
			row_to_size_new[row_number] = (row_to_size_new[row_number] == undefined) 
										? scrollHeight 
										: Math.max(scrollHeight, row_to_size_new[row_number]);
		});
	}
	
	function run()
	{
		$("ul#imageGalleryItemList li.item").each(function(index)
		{
			var row = item_to_row[index];
			var original_size = row_to_size_original[row];
			var new_size = row_to_size_new[row];
			if (original_size != new_size) $(this).height(new_size);
		});
	}
});