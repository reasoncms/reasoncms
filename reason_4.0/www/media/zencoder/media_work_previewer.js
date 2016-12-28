/**
 * This hides and shows the embedding fields of the media work content previewer for Zencoder integrated
 * media works.
 *
 * @author Marcus Huderle
 * @requires jQuery
 */
 
$(document).ready(function() 
{
	var small_field = $("#small_embedding_code_preview_field");
	var medium_field = $("#medium_embedding_code_preview_field");
	var large_field = $("#large_embedding_code_preview_field");
	
	var embed_row = $('<div class="embed_toggler"></div>');
	var show_embed = $('<a href="#">Embedding Code</a>');
	
	embed_row.append(show_embed);
	
	small_field.before(embed_row);
		
	hide_elements();
	
	function hide_elements()
	{
		if (medium_field.html() != null)
		{
			small_field.hide();
			medium_field.hide();
			large_field.hide();
		}
		else
		{
			embed_row.hide();
		}
	}
	
	show_embed.click(function()
	{
		embed_row.hide();
		small_field.show();
		medium_field.show();
		large_field.show();
		return false;
	});
	
	// links are defined as anything between the large field and the id
	var links = large_field.nextUntil('#id_preview_field');
	
	var links_row = $('<div class="links_toggler"></div>');
	var show_links = $('<a href="#">Raw Media Files</a>"');
	links_row.append(show_links);
	links.first().before(links_row);
	
	links.hide();
	
	show_links.click(function()
	{
		links_row.hide();
		links.show();
		return false;
	});
	
});