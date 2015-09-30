$(window).load(function () {
	appendText = new String;
	$("#discoTable>tbody>tr[id!='discoSubmitRow']").hide();
	rows = $('#discoTable tr[id^="sortOrder"] td.words');
	if (rows.length != 0)
	{
		appendText = '<p>You can use the table below to reorder pages via dragging and dropping. Alternatively, you can click once to pick an item up, and click again to drop it.</p>'
		appendText += '<table id="jQueryChildrenSort" cellspacing="0" cellpadding="" border="0" bgcolor="#fffff">';
		appendText += '<tbody><tr class="nodrop nodrag">';
		appendText += '<th class="listHead">Number</th>';
		appendText += '<th class="listHead">Name</th>';
		appendText += '</tr>';
		count = 1;
		rows.each(function() 
			{
				newID = $(this).next().children("select").attr("id");
				newID = newID.substring(0, newID.length-7);
				text = $(this).html();
				firstDot = text.indexOf(".");
				lastChar = text.length;
				entityName = text.slice(firstDot+2, lastChar-1)
				appendText += '<tr id="drag' + newID + '"><td class="count">' + count + '</td><td class="entityName">' + entityName + '</tr>';
				count++;
			});
		appendText += '</tbody>';
		appendText += '</table>';
	}
	$('.pageTitle').after(appendText);
	$("#jQueryChildrenSort").tableDnD(
	{onDrop: function(table, row) {
			count = 1;
			tableRows = $("#jQueryChildrenSort tr[id^='drag']");
			tableRows.each(function() {
				id = $(this).attr("id");
				id = id.replace(/dragsortOrder_/, "sortOrder_");
				$(this).children(".count").html(count);
				thisRow = $("select[name='" + id + "']", $("#discoTable"));
				thisRow.children().remove();
				thisRow.append('<option value="' + count + '">' + count + '</option>').attr("value", count);
				count++;
			})
	    }}
	);
	
	
});
/*$(document).ready(function() {

function findDropTargetRow(y, table)
{
		var rows = jQuery("tr", table);
        for (var i=0; i<rows.length; i++) {
            var row = rows[i];
            var rowY = jQuery.tableDnD.getPosition(row).y;
            var rowHeight = parseInt(row.offsetHeight)/2;
            if (row.offsetHeight == 0) {
                rowY = jQuery.tableDnD.getPosition(row.firstChild).y;
                rowHeight = parseInt(row.firstChild.offsetHeight)/2;
            }
            // Because we always have to insert before, we need to offset the height a bit
            // If the mouse is higher than 
            if ((y > rowY - rowHeight) && (y < (rowY + rowHeight))) {
                // that's the row we're over
				
				//! TODO: If it's the same as the current row, attach it to the mouse.
				//if (row == draggedRow) {return null;}
				
				
				// If a row has nodrop class, then don't allow dropping (inspired by John Tarr and Famic)
                var nodrop = jQuery(row).hasClass("nodrop");
                if (! nodrop) {
                    return row;
                } else {
                    return null;
                }
				//window.console.log(row);
                return row;
            }
        }
        return null;
}

$(document).bind('mousemove', function(ev)
{
        var dragObj = jQuery(jQuery.tableDnD.dragObject);
        var mousePos = jQuery.tableDnD.mouseCoords(ev);
        var keepMe = jQuery.tableDnD.keepMe;
        var y = mousePos.y - 40;


		//!debug
		$('#deebug').remove();
		table = $("#jQueryChildrenSort");
		currentTarget = findDropTargetRow(y, table);
		$('.contentArea').before('<div id="deebug">Current Target: ' + $(currentTarget).attr('id') + '; <br />mousePos.y: ' + mousePos.y + ';<br /> </div>');


}


)});*/