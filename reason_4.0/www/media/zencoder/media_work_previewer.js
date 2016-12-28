/**
 * This hides and shows the embedding fields of the media work content previewer for Zencoder integrated
 * media works.
 *
 * @author Marcus Huderle
 * @requires jQuery
 */

$(document).ready(function() 
{
	var embed_row = $("<div class=\"embed_toggler\"></div>");
	var show_embed = $("<a href=\"\">Show Embedding Code</a>");
	
	embed_row.append(show_embed);
	$("#small_embedding_code_preview_field").before(embed_row);
		
	hide_elements();
	
	function hide_elements()
	{
		if ($("#medium_embedding_code_preview_field").html() != null)
		{
			$("#small_embedding_code_preview_field").hide();
			$("#medium_embedding_code_preview_field").hide();
			$("#large_embedding_code_preview_field").hide();
		}
		else
		{
			embed_row.hide();
		}
	}
	
	show_embed.click(function()
	{
		embed_row.hide();
		$("#small_embedding_code_preview_field").show();
		$("#medium_embedding_code_preview_field").show();
		$("#large_embedding_code_preview_field").show();
		return false;
	});
	
});