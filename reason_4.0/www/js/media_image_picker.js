/**
 * This updates the custom thumbnail preview in the media image picker module
 *
 * @author Marcus Huderle
 * @requires jQuery
 */

$(document).ready(function() 
{
	var thumb_width = 250;
	var entry_id = $("#entry_idElement").attr("value");
	var service_url = $("#service_urlElement").attr("value");
	var partner_id = $("#partner_idElement").attr("value");
	
	var init_seconds = $("#secondsElement").val();
	var my_image = $('<img id="custom_image" src="'+get_thumbnail_url(init_seconds, entry_id)+'" />');
	
	// Insert a new row into the disco table that holds the dynamic still frame image
	var my_tr = $('<tr class="imageRow" style="right">');
	var my_td = $('<td>');
	var my_image_td = $('<td class="image">');
	
	$("#secondsRow").after(my_tr);
	my_tr.append(my_td).append(my_image_td);
	my_image_td.html(my_image);
	
	// Initialize the dynamic still frame image
	$("#secondsElement").trigger("change");
	
	function get_thumbnail_url(seconds, entry_id)
	{
		return service_url + '/p/' + partner_id + '/thumbnail/entry_id/' + entry_id + '/vid_sec/' + seconds + '/width/'+thumb_width;
	}
	
	function update_img(seconds)
	{				
		my_image.attr("src", get_thumbnail_url(seconds, entry_id));
	}
	
	$("#secondsElement").change(function()
	{
		update_img($(this).val());
	});
	
	// This ensures that the "change" event fires.  (It gets lost in IE9 with Jquery.)
    fdSlider.addEvent(document.getElementById("secondsElement"), "change", function(e) {
    	update_img(document.getElementById("secondsElement").value);
    });
        
});