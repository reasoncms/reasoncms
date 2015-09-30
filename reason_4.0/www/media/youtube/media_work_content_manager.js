/**
 * This javascript allows for a more dynamic experience with the YouTube-integrated media work
 * content manager.  It gives a preview for any urls entered, and autofills some fields.
 *
 * @author Marcus Huderle
 * @requires jQuery
 * // TODO: autofill description fill.  But how?
 */
$(document).ready(function() {
	var title_field = $("input#nameElement");
	var title_initially_empty = title_field.val().length == 0;
	var url_field = $("input#youtube_urlElement");
	var prev_url_val = url_field.val();
	var preview_row = '<tr id="urlpreviewRow"><td class="words" align="right"></td><td class="element" align="left"></td></tr>';
	$('tr#youtubeurlRow').after(preview_row);
	
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
	
	hide_elements();
		
	function hide_elements()
	{
		$("tr#embedsmallRow").hide();
		$("tr#embedmediumRow").hide();
		$("tr#embedlargeRow").hide();
		$("tr#showembedRow").hide();
		$("tr#downloadlinksRow").hide();
		$("#contentRow").addClass('offScreen');
		$("#transcriptstatusRow").addClass('offScreen');
		$("#rightsstatementRow").addClass('offScreen');
	}
	
	function get_video_key(url) {
		regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
		match = url.match(regExp);
		if (match && match.length >= 3 && match[2].length==11) {
			return String(match[2]);
		} else {
			return false;
		}
	}
	
	function valid_url(url) {
		key = get_video_key(url);
		$.ajax({
			url: "https://gdata.youtube.com/feeds/api/videos/"+encodeURIComponent(key)+"?v=2&alt=jsonc",
			dataType: "json",
			success: function(data) {
				$('tr#urlpreviewRow td.element').html(generate_iframe(url));
				update_title(key);
			},
			error: function(dat) {
				valid_key(url);
			}
		});
	}
	
	function valid_key(key) {
		$.ajax({
			url: "https://gdata.youtube.com/feeds/api/videos/"+encodeURIComponent(key)+"?v=2&alt=jsonc",
			dataType: "json",
			success: function(data) {
				$('tr#urlpreviewRow td.element').html(generate_iframe(key, true));
				update_title(key);
			},
			error: function(dat) {
				$('tr#urlpreviewRow td.element').html(generate_iframe(''));
			}
		});
	}
	
	function generate_iframe(url_or_key, is_key) {
		if (is_key) {
			return '<iframe width="320" height="240" src="http://www.youtube.com/embed/'+encodeURIComponent(url_or_key)+'" frameborder="0"></iframe>';
		}
		key = get_video_key(url_or_key);
		if (key) {
			return '<iframe width="320" height="240" src="http://www.youtube.com/embed/'+key+'" frameborder="0"></iframe>';
		} else {
			return 'Provided URL not a valid YouTube URL.';
		}
	}
	
	function update_title(vid_key) {
		if (vid_key)
		{
			$.getJSON('https://gdata.youtube.com/feeds/api/videos/'+encodeURIComponent(vid_key)+'?v=2&alt=jsonc', function(json) {
				if (!title_field.val() || !title_initially_empty) {
					title_field.val(json.data.title);
					$().val(json.data.description);
				}
			});
		}
	}

	url_field.bind("change paste keyup", function() {
		if ($(this).val().trim() != prev_url_val)
		{
			$('tr#urlpreviewRow td.words').html('Url Preview');
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

});