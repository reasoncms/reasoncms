/**
 * This hides and shows some elements in the Kaltura front-end uploader.  Dynamically determines av_type.
 *
 * @author Marcus Huderle
 * @requires jQuery
 */
$(document).ready(function() 
{
	var show_import_link = $("<a href=\"\" class=\"formComment\">Larger than 100 MB?</a>");
	$("div.maxUploadSizeNotice").append(show_import_link);
	
	$.my_extension_data = {
		vid_extensions : new Array("flv", "f4v", "mov", "wmv", "qt", "m4v", "avi", "3gp", "asf", "wvm", "mpg", "m1v", "m2v", "mkv", "webm", "ogv"),
		aud_extensions : new Array("mp3", "aiff", "wav", "m4a", "aac"),
		media_type : $("tr#avtypeRow"),
		vid_radio : $("input#radio_av_type_1"),
		aud_radio : $("input#radio_av_type_0"),
		media_type_text : $("tr#avtypeRow td.words")
	};
	
	show_import_link.click(function() 
	{
		$("div#urlItem").show();
		$(this).hide();
		return false;
	});
	
	hide_elements();
	
	function hide_elements()
	{
		// clear contents of url field and hide it from the user
		$("input#urlElement").val('');
		$("div#urlItem").hide();
	}
	
	function post_upload(val)
	{	
		parts = val.split(".");
		len = parts.length;
		extension = parts[len-1].toLowerCase();
		
		if ($.inArray(extension, $.my_extension_data.vid_extensions) != -1)
		{
			$.my_extension_data.aud_radio.removeAttr("checked");
			$.my_extension_data.vid_radio.attr("checked", "checked");
			//$.my_extension_data.media_type.hide();
		}
		else if ($.inArray(extension, $.my_extension_data.aud_extensions) != -1)
		{
			$.my_extension_data.vid_radio.removeAttr("checked");
			$.my_extension_data.aud_radio.attr("checked", "checked");
			//$.my_extension_data.media_type.hide();
		}
	}	
		
	$("#uploadfileItem .file_upload").bind('uploadSuccess', function() {
        post_upload($("span.filename", $(this)).html());
    });
    $("div.file_upload input").bind('change', function() {
        post_upload($(this).val());
    });
});