/**
 * This javascript allows for a more dynamic experience with the Vimeo-integrated media work
 * content manager.  It gives a preview for any urls entered, and autofills some fields.
 *
 * @author Marcus Huderle
 * @requires jQuery
 */
 function post_upload(val)
{
	// Hide the filename changing row when we pick a new file; it won't
	// have any effect if changed when received along with a new file.
	$("tr#emailnotificationRow").show();
}

$(document).ready(function() {
	var title_field = $("input#nameElement");
	var url_field = $("input#vimeo_urlElement");
	var prev_url_val = url_field.val();
	var preview_row = '<tr id="urlpreviewRow"><td class="words" align="right">Url Preview</td><td class="element" align="left">No preview available...</td></tr>';
	$('tr#vimeourlRow').after(preview_row);
	
	var embed_row = $("<tr class=\"embed_toggler\"></tr>");
	var embed_pre_col = $("<td class=\"words\" align=\"right\"></td>");
	var embed_col = $("<td class=\"element\" align=\"left\"></td>");
	var show_embed = $("<a href=\"\" class=\"toggler\">Embedding</a>");
	embed_row.append(embed_pre_col);
	embed_row.append(embed_col.append(show_embed));
	$('tr#urlpreviewRow').after(embed_row);
	
	var transcript_row = $("<tr class=\"transcript_toggler\"></tr>");
	var transcript_pre_column = $("<td class=\"words\" align=\"right\"></td>");
	var transcript_column = $("<td class=\"element\" align=\"left\"></td>");
	var transcript_toggler = $("<a href=\"\" class=\"toggler\">Transcript</a>");
	transcript_row.append(transcript_pre_column);
	transcript_row.append(transcript_column.append(transcript_toggler));
	$("tr#contentRow").before(transcript_row);
	
	var rights_row = $("<tr class=\"rights_toggler\"></tr>");
	var rights_pre_column = $("<td class=\"words\" align=\"right\"></td>");
	var rights_column = $("<td class=\"element\" align=\"left\"></td>");
	var rights_toggler = $("<a href=\"\" class=\"toggler\">Rights Statement</a>");
	rights_row.append(rights_pre_column);
	rights_row.append(rights_column.append(rights_toggler));
	$("tr#rightsstatementRow").before(rights_row);
	
	// add upload toggler
	var upload_row = $("<tr class=\"upload_toggler\"></tr>");
	var upload_pre_column = $("<td class=\"words\" align=\"right\"></td>");
	var upload_column = $("<td class=\"element\" align=\"left\"></td>");
	var upload_toggler = $("<a href=\"\" class=\"toggler\">Upload a Video</a>");
	upload_row.append(upload_pre_column);
	upload_row.append(upload_column.append(upload_toggler));
	$("tr#uploadfileRow").after(upload_row);
	
	// add a link-to-video toggler
	var link_row = $("<tr class=\"link_toggler\"></tr>");
	var link_pre_column = $("<td class=\"words\" align=\"right\"></td>");
	var link_column = $("<td class=\"element\" align=\"left\"></td>");
	var link_toggler = $("<a href=\"\" class=\"toggler\">Link to a Video on Vimeo</a>");
	link_row.append(link_pre_column);
	link_row.append(link_column.append(link_toggler));
	embed_row.before(link_row);
	if ($("tr#vimeourlRow").hasClass("error"))
		link_toggler.hide();
	
	hide_elements();
		
	function hide_elements()
	{
		hide($("tr#urlpreviewRow"));
		hide($("tr#uploadfileRow"));
		hide($("tr#vimeourlRow"));
		hide($("tr#embedsmallRow"));
		hide($("tr#embedmediumRow"));
		hide($("tr#embedlargeRow"));
		hide($("tr#showembedRow"));
		hide($("tr#uploadurlRow"));
		hide($("tr#emailnotificationRow"));
		$("#contentRow").addClass('offScreen');
		$("#transcriptstatusRow").addClass('offScreen');
		$("#rightsstatementRow").addClass('offScreen');
	}
	
	function hide(element)
	{
		if (!element.hasClass('error'))
		{
			element.hide();
		}
	}
	
	function get_video_key(url) {
		regExp = '(https?:\/\/)?(www.)?(player.)?vimeo.com\/([a-z]*\/)*([0-9]{1,15})([?](.*))?';
		match = url.match(regExp);
		if (match && match[0].length == url.length && !isNaN(match[5])) {
			return String(match[5]);
		} else {
			return false;
		}
	}
	
	function valid_url(url) {
		key = get_video_key(url);
		if (key) {
			$.getJSON('https://vimeo.com/api/v2/video/' + encodeURIComponent(key) + '.json?callback=?', {format: "json"}, function(data) {
				$('tr#urlpreviewRow td.element').html(generate_iframe(key, true));
				update_title(data);
			});
		} else {
			valid_key(url);
		}
	}
	
	function valid_key(key) {
		$.getJSON('https://vimeo.com/api/v2/video/' + encodeURIComponent(key) + '.json?callback=?', {format: "json"}, function(data) {
			$('tr#urlpreviewRow td.element').html(generate_iframe(key, true));
			update_title(data);
		});
	}
	
	function generate_iframe(url_or_key, is_key) {
		if (is_key) {
			return '<iframe width="320" height="240" src="https://player.vimeo.com/video/'+encodeURIComponent(url_or_key)+'" frameborder="0"></iframe>';
		}
		key = get_video_key(url_or_key);
		if (key) {
			return '<iframe width="320" height="240" src="https://player.vimeo.com/video/'+encodeURIComponent(key)+'" frameborder="0"></iframe>';
		} else {
			return 'No preview available...';
		}
	}
	
	function update_title(data) {
		title_field.val(data[0].title);
	}

	url_field.bind("change paste keyup", function() {
		if ($(this).val().trim() != prev_url_val)
		{
			$("tr#urlpreviewRow").show();
			$("tr#urlpreviewRow td.element").html(generate_iframe(''));
			$("tr#urlpreviewRow td.words").html('Url Preview');
			valid_url($(this).val().trim());
			prev_url_val = $(this).val().trim();
		}
	});

	show_embed.click(function() {
		embed_row.hide();
		$("tr#embedsmallRow").show();
		$("tr#embedmediumRow").show();
		$("tr#embedlargeRow").show();
		$("tr#showembedRow").show();
		return false;
	});
	
	transcript_toggler.click(function() {
		$("#contentRow").removeClass('offScreen');
		$("#transcriptstatusRow").removeClass('offScreen');
		transcript_toggler.hide();
		return false;
	});

	rights_toggler.click(function() {
		$("tr#rightsstatementRow").removeClass('offScreen');
		rights_toggler.hide();
		return false;
	});	
	
	upload_toggler.click(function() {
		$("tr#uploadfileRow").show();
		$("tr#uploadurlRow").show();
		upload_toggler.hide();
		link_toggler.show();
		$("tr#vimeourlRow td.element input").val('');
		$("tr#vimeourlRow").hide();
		$("tr#urlpreviewRow td.element").html(generate_iframe(''));
		$("tr#urlpreviewRow").hide();
		return false;
	});
	
	link_toggler.click(function() {
		$("tr#vimeourlRow").show();
		link_toggler.hide();
		upload_toggler.show();
		$("tr#uploadfileRow").hide();
		$("tr#uploadurlRow").hide();
		$("tr#urlpreviewRow").hide();
		return false;
	});
	
	$("#uploadfileRow .file_upload").bind('uploadSuccess', function() {
        post_upload($(this).val());
    });
    
    $("div.file_upload input").bind('change', function() {
        post_upload($(this).val());
    });

});