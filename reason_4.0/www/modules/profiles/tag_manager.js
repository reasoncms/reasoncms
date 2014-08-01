$(document).ready(function(){
	
	var module_id = $.reasonAjax.get_module_identifier($('#tagManagerModule'));
	var base_url = window.location.protocol.replace(/\:/g, '') + "://" + window.location.host + window.location.pathname + '?module_identifier=' + module_id + '&module_api=standalone';
	
	var $tabs = $("#tabs").tabs();
	$tabs.show();
	
	// Make tags editable when edit icon is clicked
	$("span.edit").click(function(){
		$(this).siblings("span.tagName").draggable({disabled: true}).attr('contenteditable','true');	
	});
	
	// Disable editing and trigger update (blur) when Enter or Return is pressed
	$("span.tagName").keypress(function(event){
		if (event.keyCode == 10 || event.keyCode == 13)  
		{
			$(this).removeAttr('contenteditable').draggable({disabled: false});
			$(this).blur();
			event.preventDefault();
		}
	});

	$("span.tagName").click(function() { 
		// If we're editing the tag content, don't handle clicks
		if ($(this).attr('contenteditable')) { return; }
		
		// If this element is already in the tag box, ignore the click
		if ($("#0" + $(this).parent().attr("id"), "ul#tagBoxList").length) { return; }
		
		// when tags are clicked, copy them to the tag box;
		// We add a zero to the start of the id to maintain DOM uniqueness but
		// still pass as the same value;
		$(this).parent().clone()
			.attr( "class", "tagBoxTag" )
			.removeAttr("aria-disabled")
			.attr("id", '0' + $(this).parent().attr("id"))
			.appendTo("ul#tagBoxList");
	});
	
	$("li.tag span.tagName").draggable({ 
		addClasses: false,
		revert: true, 
		revertDuration: 200,
		opacity: 0.7, 
		zIndex: 100,
		appendTo: "body", 
		helper: "clone",
		drag: function( event, ui ) {
			// This code turns off refreshPositions if it's on, allowing us to just
			// set it once to refresh when needed.	
			if($(this).data('stopRefresh')) {
				$(this).draggable('option','refreshPositions',false);
				$(this).data('stopRefresh',false);
			}
			if($(this).draggable('option','refreshPositions')) {
				$(this).data('stopRefresh',true);
			}
		}
	});
	
	$("ul#tagBoxList").draggable({
		addClasses: false,
		revert: true, 
		revertDuration: 200,
		opacity: 0.7, 
		zIndex: 100,
		appendTo: "body", 
		helper: "clone",
	});
			
	
	// Make tag names droppable targets
	$("li.tag span.tagName").droppable({
		hoverClass: "dropTarget",
		tolerance: "pointer",
		addClasses: false,
		over: function( event, ui) {
			if (event.shiftKey)
			{
				$('.ui-draggable-dragging').addClass('merging');
			} else {
				$('.ui-draggable-dragging').removeClass('merging');
			}
		},
		drop: function( event, ui ) {
			var drop_object = $(this).parent();
			var drop_id = drop_object.attr('id');
			
			// If we've dragged a single tag, find the parent li and extract the id
			if (ui.draggable.is('span.tagName'))
			{
				var drag_object = ui.draggable.parent();
				var drag_id = drag_object.attr('id');
			}
			// If we've dragged a list, extract the integer ids of the first level li elements.
			else if (ui.draggable.is('ul.tagBoxList'))
			{
				var drag_object = ui.draggable;
				var drag_id = $('ul.ui-draggable-dragging > li').map(function(){
					return parseInt(this.id);
				}).get();				
			}
			else
			{
				return;
			}
			
			drag_object.hide();
			
			// Merge tags if the shift key is down
			if (event.shiftKey)
			{
				$.getJSON(base_url, { tagid: JSON.stringify(drag_id), action: "mergewith", value: drop_id, callback: "?" })
					.done(function(data){
						if (data.completed)
						{
							// If we've merged a single item, remove it
							if (drag_object.is('li'))
							{
								drag_object.remove();
							} 
							// If we've merged a list of items from the tagBox, remove 
							// the original and the copy
							else 
							{
								for (var id in drag_id) {
									$("li#"+drag_id[id]).remove();
									$("li#0"+drag_id[id]).remove();
								};										
							}
							// update the tag count of the merge target
							drop_object.find("span.tagCount a").filter(":first").text(data.new_count);
						}
					});
			} 
			// Otherwise, make the dragged tag(s) a child of the drop point
			else 
			{
				$.getJSON(base_url, { tagid: JSON.stringify(drag_id), action: "set_parent", value: drop_id, callback: "?" })
					.done(function(data){
						if (data.completed)
						{
							if (typeof data.changes.adopted == "object")
							{
								// In needed, create the list to add the dragged item(s) to
								if (!drop_object.find("ul").length)
								{
									$('<ul/>', {id: "children"+drop_id }).appendTo( drop_object );
								}
							
								for (var id in data.changes.adopted) {
									add_to_list(drop_object.find("ul"), $("li#"+data.changes.adopted[id]));
									// Clear the item from the dropBox if present
									$("li#0"+data.changes.adopted[id]).remove();
								}
							}
						}
					});
			}
			drag_object.show();
		}
	});
	
	// Make tabs droppable, and define the hover behavior to switch to the chosen tab
	var $tab_items = $( "ul:first li", $tabs ).droppable({
		accept: "li.tag span.tagName, ul#tagBoxList",
		tolerance: "pointer",
		hoverClass: "ui-state-hover",
		over: function( event, ui ) {
			$tabs.tabs( "option", "active", $tab_items.index( $(this) ) );
			// turn refreshPositions on momentarily so that targets on the new tab will be enabled
			$(ui.draggable).draggable('option','refreshPositions',true);
		}
	});
	
	// Make the tab panel droppable, for removing tags from their parents
	$("div#tagBox").droppable({
		accept: "li.tag span.tagName",
		hoverClass: "dropTarget",
		tolerance: "pointer",
		addClasses: false,
		drop: function( event, ui ) {
			var drag_object = ui.draggable.parent();
			var drag_id = drag_object.attr('id');
			var parent_id = drag_object.closest('ul').parent().attr('id');
			
			drag_object.hide();

			$.getJSON(base_url, { tagid: drag_id, action: "set_parent", value: parent_id, callback: "?" })
				.done(function(data){
					if (data.completed)
					{
						var tagAlpha = ui.draggable.text().charAt(0).toLowerCase();
						add_to_list($("div#tabs-" + tagAlpha + ">ul.tags"), drag_object);
					}
					drag_object.show();
				});

		}
	});
	
	// If tags in the tag box are clicked, delete them from the box
	$("ul#tagBoxList").on("click", "li", function(){
		$(this).remove();		
	});
	
	// Delete a tag
	$("span.delete").click(function(){
		var id = $(this).parent().attr('id');
		$.getJSON(base_url, { tagid: id, action: "delete", callback: "?" })
			.done(function(data){
				if (data.completed)
				{
					if (typeof data.changes !== "undefined")
					{
						data.changes.forEach(function(id) {
							$("li#"+id).remove();
						});
					}
				}
			});
		$(this).parent("li.tag").draggable({disabled: false});
	});


	// Rename a tag via Ajax on blur
	$("span.tagName").blur(function(){
		$.getJSON(base_url, { tagid: $(this).parent().attr('id'), action: "rename", value: $(this).text(), callback: "?" })
			.done(function(data){
				if (data.completed)
				{
					
				}
			});
		$(this).parent("li.tag").draggable({disabled: false});
	});
	
	$("input#careerListNormalize").click(function(){
		var elements = [];
		$("ul#tagBoxList li").each(function(){
			elements.push(parseInt($(this).attr('id')));
		});
		$.getJSON(base_url, { action: "normalize_set", tagid: JSON.stringify(elements), callback: "?" })
			.done(function(data){
				if (data.completed)
				{
					if (typeof data.changes == "object")
					{
						for (var id in data.changes) {
							$("li#"+id+" span.tagName").text(data.changes[id]);
							$("li#0"+id).remove();
						};
					}					
				}
			});				
	});
	
	$("input#careerListAdd").click(function(){
		var elements = [];
		$("ul#tagBoxList li").each(function(){
			elements.push(parseInt($(this).attr('id')));
		});
		$.getJSON(base_url, { action: "bucketize_set", tagid: JSON.stringify(elements), value: $("select#careerList").val(), callback: "?" })
			.done(function(data){
				if (data.completed)
				{
					if (typeof data.changes.added !== "undefined")
					{
						data.changes.added.forEach(function(id) {
							$("li#"+id).addClass('inBucket');
							$("li#0"+id).remove();
						});
					}
					if (typeof data.changes.removed !== "undefined")
					{
						data.changes.removed.forEach(function(id) {
							$("li#"+id).removeClass('inBucket');
							$("li#0"+id).remove();
						});
					}
				}
			});				
	});

	$("input#careerListClear").click(function(){
		$("ul#tagBoxList li").remove();
	});
	
	// Given a jQuery element representing an HTML list, and an element representing
	// a list item, this function will place the item in the appropriate alphabetical 
	// position in the list. The list must have a unique id.
	function add_to_list(list, element)
	{
		var text = $("span.tagName", element).text();
		$("ul#" + list.attr('id') + ">li").each(function(){
			var listText = $("span.tagName", $(this)).text();
			if (text < listText)
			{
				$(this).before(element);
				text = '';
				return false;
			}
		});
		
		if (text != '')	{ list.append(element); }
	}
});