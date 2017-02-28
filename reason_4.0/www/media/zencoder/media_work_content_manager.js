/**
 * This hides and shows the less-important field of the media work content manager
 *
 * @author Marcus Huderle
 * @requires jQuery
 */
function post_upload(val)
{
	// Hide the filename changing row when we pick a new file; it won't
	// have any effect if changed when received along with a new file.
	$("#emailnotificationItem").show();
	
	parts = val.split(".");
	len = parts.length;
	extension = parts[len-1].toLowerCase();
	
	if ($.inArray(extension, $.my_extension_data.vid_extensions) != -1)
	{
		$.my_extension_data.aud_radio.prop('checked', false);
		$.my_extension_data.vid_radio.prop('checked', true);;
	}
	else if ($.inArray(extension, $.my_extension_data.aud_extensions) != -1)
	{
		$.my_extension_data.vid_radio.prop('checked', false);
		$.my_extension_data.aud_radio.prop('checked', true);
	}
}

$(document).ready(function() 
{	
	/*
	// temporarily disable direct uploads on media works
	$("<tr/>").html("<td colspan=2>There is currently an issue with uploading files to Reason directly. Web Services Group is working on fixing the issue, but if you need to upload a file now, you can use the webpub method described here.</td>").insertBefore($("tr#uploadfileRow"));
	$("tr#uploadfileRow").css("display", "none");
	setTimeout(function() {
		$("tr#importfileRow").css("display", "table-row");
	}, 100);
	*/

	$.my_extension_data = {
		vid_extensions : new Array("flv", "f4v", "mov", "wmv", "qt", "m4v", "avi", "3gp", "asf", "wvm", "mpg", "m1v", "m2v", "mkv", "webm", "ogv", "m4v"),
		aud_extensions : new Array("mp3", "aiff", "wav", "m4a", "aac"),
		media_type : $("#avtypeItem"),
		vid_radio : $("input#radio_av_type_1"),
		aud_radio : $("input#radio_av_type_0"),
		media_type_text : $("#avtypeItem div.words")
	
	};
	
	var embed_row = $("<div class=\"embed_toggler formElement\"></div>");
	var show_embed = $("<a href=\"\" class=\"toggler\">Embedding &amp; Downloading</a>");
	
	embed_row.append(show_embed);
	$("#emailnotificationItem").after(embed_row);

	$("#emailnotificationItem").hide();
	
	var transcript_row = $("<div class=\"transcript_toggler formElement\"></div>");
	var transcript_toggler = $("<a href=\"\" class=\"toggler\">Transcript</a>");
	
	transcript_row.append(transcript_toggler);
	
	var rights_row = $("<div class=\"rights_toggler formElement\"></div>");
	var rights_toggler = $("<a href=\"\" class=\"toggler\">Rights Statement</a>");
	
	rights_row.append(rights_toggler);
	
	$("#contentItem").before(transcript_row);
	$("#rightsstatementItem").before(rights_row);
	
	hide_elements();
		
	function hide_elements()
	{
		$("#embedsmallItem").hide();
		$("#embedmediumItem").hide();
		$("#embedlargeItem").hide();
		$("#showdownloadItem").hide();
		$("#showembedItem").hide();
		$("#downloadlinksItem").hide();
		$("#contentItem").addClass('offScreen');
		$("#transcriptstatusItem").addClass('offScreen');
		$("#rightsstatementItem").addClass('offScreen');
	}

	function show_transcript()
	{
		$("#contentItem").removeClass('offScreen');
		$("#transcriptstatusItem").removeClass('offScreen');
	}
	
	function show_rights()
	{
		$("#rightsstatementItem").removeClass('offScreen');
	}
	
	show_embed.click(function()
	{
		embed_row.hide();
		$("#embedsmallItem").show();
		$("#embedmediumItem").show();
		$("#embedlargeItem").show();
		$("#showdownloadItem").show();
		$("#showembedItem").show();
		$("#downloadlinksItem").show();
		return false;
	});
	
	transcript_toggler.click(function()
	{
		show_transcript();
		transcript_toggler.hide();
		return false;
	});
	
	rights_toggler.click(function() 
	{
		show_rights();
		rights_toggler.hide();
		return false;
	});	
	
	
    $("#uploadfileRow .file_upload").bind('uploadSuccess', function() {
        post_upload($("span.filename", $(this)).html());
    });
    
    $("div.file_upload input").bind('change', function() {
        post_upload($(this).val());
    });
    
    $("input#embed_smallElement, input#embed_mediumElement, input#embed_largeElement").click(function() {
		$(this).select();
	});
	
});
