/**
 * This updates the custom thumbnail preview in the zencoder media image picker module.
 *
 * @author Marcus Huderle
 * @requires jQuery
 */

$(document).ready(function() 
{
	var thumb_width = 250;

	var init_index = $("#thumbnailsElement").val();
	var my_image = $('<img id="custom_image" src="'+get_thumbnail_url(init_index)+'" />');
	
	// Insert a new row into the disco table that holds the dynamic still frame image
	var my_tr = $('<tr class="imageRow" style="right">');
	var my_td = $('<td>');
	var my_image_td = $('<td class="image">');
	
	$("#thumbnailsRow").after(my_tr);
	my_tr.append(my_td).append(my_image_td);
	my_image_td.html(my_image);
	
	// Initialize the dynamic still frame image
	$("#thumbnailsElement").trigger("change");
	
	function get_thumbnail_url(index)
	{
		return $('#thumbnail_data span.' + index).attr('data-src');
	}
	
	function update_img(index)
	{				
		my_image.attr("src", get_thumbnail_url(index));
	}
	
	$("#thumbnailsElement").change(function()
	{
		update_img($(this).val());
	});
	
	// This ensures that the "change" event fires.  (It gets lost in IE9 with Jquery.)
    fdSlider.addEvent(document.getElementById("thumbnailsElement"), "change", function(e) {
    	update_img(document.getElementById("thumbnailsElement").value);
    });
        
});