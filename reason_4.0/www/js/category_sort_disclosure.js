$(document).ready(function(){
	var cutoff_fields = $("span[id*=field_cutoffs]");
	$.each( cutoff_fields, function(key, span) {
		var field_id = span.innerHTML;
		var hidden_check = $("input[value="+field_id+"]");
		var top_row = hidden_check.parent().parent();
		var bottom_row = top_row.parent().children().last();
		toggle_range(top_row, bottom_row.next(), "toggle");
		top_row.before("<tr id='toggle' class='"+field_id+"'><td></td><td><a href='#'>More Options</a></td></tr>");
		var toggle= $("tr#toggle."+field_id);	
		toggle.nextUntil(bottom_row.next()).each(function()
		{
			if (this.cells.item(0).firstChild.checked)
				toggle_range(top_row, bottom_row.next(),"show");
		});
		toggle.click(function(event){
			event.preventDefault();
			var toggle_row = $(this).closest("tr");
			if (toggle_row.next().css('display') == "none")
				toggle.children().last().html('<a href="#">Fewer Options</a>');
			else
				toggle.children().last().html('<a href="#">More Options</a>');
			toggle_range(toggle_row.next(), toggle_row.parent().children().last().next(), "toggle");
		});

	});
function toggle_range(start, end_before, action)
{
	if ((action == "show" ) || (action == "toggle" && $(start).css('display') == "none"))
		$(start).show();
	else
		$(start).hide();
	
	$(start).nextUntil(end_before).each(function()
	{
		if ((action == "show" ) || (action == "toggle" && $(this).css('display') == "none"))
			$(this).show();
		else
			$(this).hide();
	});
}

});

