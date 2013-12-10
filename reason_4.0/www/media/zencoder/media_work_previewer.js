/**
 * This hides and shows the embedding fields of the media work content previewer for Zencoder integrated
 * media works.
 *
 * @author Marcus Huderle
 * @requires jQuery
 */

$(document).ready(function() 
{
	var embed_row = $("<tr class=\"embed_toggler\"></tr>");
	var embed_col = $("<td class=\"words\" align=\"right\"></td>");
	var show_embed = $("<a href=\"\">Show Embedding Code</a>");
	
	embed_row.append(embed_col.append(show_embed));
	$("tr#Small_Embedding_Code_Row").before(embed_row);
		
	hide_elements();
	
	function hide_elements()
	{
		if ($("tr#Medium_Embedding_Code_Row").html() != null)
		{
			$("tr#Small_Embedding_Code_Row").hide();
			$("tr#Medium_Embedding_Code_Row").hide();
			$("tr#Large_Embedding_Code_Row").hide();
		}
		else
		{
			embed_row.hide();
		}
	}
	
	show_embed.click(function()
	{
		embed_row.hide();
		$("tr#Small_Embedding_Code_Row").show();
		$("tr#Medium_Embedding_Code_Row").show();
		$("tr#Large_Embedding_Code_Row").show();
		return false;
	});
	
});