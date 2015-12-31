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
	$("tr#emailnotificationRow").show();
	
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
	// temporarily disable direct uploads on media works
	$("<div/>").html("There is currently an issue with uploading files to Reason directly. Web Services Group is working on fixing the issue, but if you need to upload a file now, you can use the webpub method described here.").insertBefore($("tr#uploadfileRow"));
	$("tr#uploadfileRow").css("display", "none");
	setTimeout(function() {
		$("tr#importfileRow").css("display", "table-row");
	}, 100);

	$.my_extension_data = {
		vid_extensions : new Array("flv", "f4v", "mov", "wmv", "qt", "m4v", "avi", "3gp", "asf", "wvm", "mpg", "m1v", "m2v", "mkv", "webm", "ogv", "m4v"),
		aud_extensions : new Array("mp3", "aiff", "wav", "m4a", "aac"),
		media_type : $("tr#avtypeRow"),
		vid_radio : $("input#radio_av_type_1"),
		aud_radio : $("input#radio_av_type_0"),
		media_type_text : $("tr#avtypeRow td.words")
	
	};
	
	var embed_row = $("<tr class=\"embed_toggler\"></tr>");
	var embed_pre_col = $("<td class=\"words\" align=\"right\"></td>");
	var embed_col = $("<td class=\"element\" align=\"left\"></td>");
	var show_embed = $("<a href=\"\" class=\"toggler\">Embedding &amp; Downloading</a>");
	
	embed_row.append(embed_pre_col);
	embed_row.append(embed_col.append(show_embed));
	$("tr#emailnotificationRow").after(embed_row);

	$("tr#emailnotificationRow").hide();
	
	var transcript_row = $("<tr class=\"transcript_toggler\"></tr>");
	var transcript_pre_column = $("<td class=\"words\" align=\"right\"></td>");
	var transcript_column = $("<td class=\"element\" align=\"left\"></td>");
	var transcript_toggler = $("<a href=\"\" class=\"toggler\">Transcript</a>");
	
	transcript_row.append(transcript_pre_column);
	transcript_row.append(transcript_column.append(transcript_toggler));
	
	var rights_row = $("<tr class=\"rights_toggler\"></tr>");
	var rights_pre_column = $("<td class=\"words\" align=\"right\"></td>");
	var rights_column = $("<td class=\"element\" align=\"left\"></td>");
	var rights_toggler = $("<a href=\"\" class=\"toggler\">Rights Statement</a>");
	
	rights_row.append(rights_pre_column);
	rights_row.append(rights_column.append(rights_toggler));
	
	$("tr#contentRow").before(transcript_row);
	$("tr#rightsstatementRow").before(rights_row);
	
	hide_elements();
		
	function hide_elements()
	{
		$("tr#embedsmallRow").hide();
		$("tr#embedmediumRow").hide();
		$("tr#embedlargeRow").hide();
		$("tr#showdownloadRow").hide();
		$("tr#showembedRow").hide();
		$("tr#downloadlinksRow").hide();
		$("#contentRow").addClass('offScreen');
		$("#transcriptstatusRow").addClass('offScreen');
		$("#rightsstatementRow").addClass('offScreen');
	}

	function show_transcript()
	{
		$("#contentRow").removeClass('offScreen');
		$("#transcriptstatusRow").removeClass('offScreen');
	}
	
	function show_rights()
	{
		$("tr#rightsstatementRow").removeClass('offScreen');
	}
	
	show_embed.click(function()
	{
		embed_row.hide();
		$("tr#embedsmallRow").show();
		$("tr#embedmediumRow").show();
		$("tr#embedlargeRow").show();
		$("tr#showdownloadRow").show();
		$("tr#showembedRow").show();
		$("tr#downloadlinksRow").show();
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
