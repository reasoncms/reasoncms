/**
 * A mobile-friendly version of the Denis Howlett's TableDnD plug-in for JQuery. Allows you
 * to drag and drop table rows via either click-and-hold or click to pick up/click to drop.
 * Unfortunately, this will need a little bit of work before you can apply it to anything
 * other than Reason's lib/core/classes/admin/modules/sorter.php. 
 *
 * You can set up various options to control how the system will work
 * Copyright (c) Denis Howlett <denish@isocra.com>
 * Licensed like jQuery, see http://docs.jquery.com/License.
 *
 * Configuration options:
 * 
 * onDragStyle
 *     This is the style that is assigned to the row during drag. There are limitations to the styles that can be
 *     associated with a row (such as you can't assign a border--well you can, but it won't be
 *     displayed). (So instead consider using onDragClass.) The CSS style to apply is specified as
 *     a map (as used in the jQuery css(...) function).
 * onDropStyle
 *     This is the style that is assigned to the row when it is dropped. As for onDragStyle, there are limitations
 *     to what you can do. Also this replaces the original style, so again consider using onDragClass which
 *     is simply added and then removed on drop.
 * onDragClass
 *     This class is added for the duration of the drag and then removed when the row is dropped. It is more
 *     flexible than using onDragStyle since it can be inherited by the row cells and other content. The default
 *     is class is tDnD_whileDrag. So to use the default, simply customise this CSS class in your
 *     stylesheet.
 * onDrop
 *     Pass a function that will be called when the row is dropped. The function takes 2 parameters: the table
 *     and the row that was dropped. You can work out the new order of the rows by using
 *     table.rows.
 * onDragStart
 *     Pass a function that will be called when the user starts dragging. The function takes 2 parameters: the
 *     table and the row which the user has started to drag.
 * onAllowDrop
 *     Pass a function that will be called as a row is over another row. If the function returns true, allow 
 *     dropping on that row, otherwise not. The function takes 2 parameters: the dragged row and the row under
 *     the cursor. It returns a boolean: true allows the drop, false doesn't allow it.
 * scrollAmount
 *     This is the number of pixels to scroll if the user moves the mouse cursor to the top or bottom of the
 *     window. The page should automatically scroll up or down as appropriate (tested in IE6, IE7, Safari, FF2,
 *     FF3 beta
 * dragHandle
 *     This is the name of a class that you assign to one or more cells in each row that is draggable. If you
 *     specify this class, then you are responsible for setting cursor: move in the CSS and only these cells
 *     will have the drag behaviour. If you do not specify a dragHandle, then you get the old behaviour where
 *     the whole row is draggable.
 * 
 * Other ways to control behaviour:
 *
 * Add class="nodrop" to any rows for which you don't want to allow dropping, and class="nodrag" to any rows
 * that you don't want to be draggable.
 *
 * Inside the onDrop method you can also call $.tableDnD.serialize() this returns a string of the form
 * <tableID>[]=<rowID1>&<tableID>[]=<rowID2> so that you can send this back to the server. The table must have
 * an ID as must all the rows.
 *
 * Other methods:
 *
 * $("...").tableDnDUpdate() 
 * Will update all the matching tables, that is it will reapply the mousedown method to the rows (or handle cells).
 * This is useful if you have updated the table rows using Ajax and you want to make the table draggable again.
 * The table maintains the original configuration (so you don't have to specify it again).
 *
 * $("...").tableDnDSerialize()
 * Will serialize and return the serialized string as above, but for each of the matching tables--so it can be
 * called from anywhere and isn't dependent on the currentTable being set up correctly before calling
 *
 * Known problems:
 * - Auto-scoll has some problems with IE7  (it scrolls even when it shouldn't), work-around: set scrollAmount to 0
 * 
 * Version 0.2: 2008-02-20 First public version
 * Version 0.3: 2008-02-07 Added onDragStart option
 *                         Made the scroll amount configurable (default is 5 as before)
 * Version 0.4: 2008-03-15 Changed the noDrag/noDrop attributes to nodrag/nodrop classes
 *                         Added onAllowDrop to control dropping
 *                         Fixed a bug which meant that you couldn't set the scroll amount in both directions
 *                         Added serialize method
 * Version 0.5: 2008-05-16 Changed so that if you specify a dragHandle class it doesn't make the whole row
 *                         draggable
 *                         Improved the serialize method to use a default (and settable) regular expression.
 *                         Added tableDnDupate() and tableDnDSerialize() to be called when you are outside the table
 */
jQuery.tableDnD = {
    /** Keep hold of the current table being dragged */
    currentTable : null,
    /** Keep hold of the current drag object if any */
    dragObject: null,
    /** The current mouse offset */
    mouseOffset: null,
    /** Remember the old value of Y so that we don't do too much processing */
    oldY: 0,

    /** Actually build the structure */
    build: function(options) {
        // Set up the defaults if any

        this.each(function() {
            // This is bound to each matching table, set up the defaults and override with user options
            this.tableDnDConfig = jQuery.extend({
                onDragStyle: null,
                onDropStyle: null,
				// Add in the default class for whileDragging
				onDragClass: "tDnD_whileDrag",
                onDrop: null,
                onDragStart: null,
                scrollAmount: 5,
				serializeRegexp: /[^\-]*$/, // The regular expression to use to trim row IDs
				serializeParamName: null, // If you want to specify another parameter name instead of the table ID
                dragHandle: null // If you give the name of a class here, then only Cells with this class will be draggable
            }, options || {});
            // Now make the rows draggable
            jQuery.tableDnD.makeDraggable(this);
        });

        // Now we need to capture the mouse up and mouse move event
        // We can use bind so that we don't interfere with other event handlers
        jQuery(document)
            .bind('mousemove', jQuery.tableDnD.mousemove)
            .bind('mouseup', jQuery.tableDnD.mouseup);

        // Don't break the chain
        return this;
    },

    /** This function makes all the rows on the table draggable apart from those marked as "NoDrag" */
    makeDraggable: function(table) {
        var config = table.tableDnDConfig;
		if (table.tableDnDConfig.dragHandle) {
			// We only need to add the event to the specified cells
			var cells = jQuery("td."+table.tableDnDConfig.dragHandle, table);
			cells.each(function() {
				// The cell is bound to "this"
                jQuery(this).mousedown(function(ev) {
                	jQuery.tableDnD.mouseDownLoc = jQuery.tableDnD.mouseCoords(ev);
					if (jQuery.tableDnD.keepMe == true)
						return false;
                    jQuery.tableDnD.dragObject = this.parentNode;
                    jQuery.tableDnD.currentTable = table;
                    jQuery.tableDnD.mouseOffset = jQuery.tableDnD.getMouseOffset(this, ev);
                    if (config.onDragStart) {
                        // Call the onDrop method if there is one
                        config.onDragStart(table, this);
                    }
                    return false;
                });
			})
		} else {
			// For backwards compatibility, we add the event to the whole row
	        var rows = jQuery("tr", table); // get all the rows as a wrapped set
	        rows.each(function() {
				// Iterate through each row, the row is bound to "this"
				var row = jQuery(this);
				if (! row.hasClass("nodrag")) {
	                row.mousedown(function(ev) {
	                    if (ev.target.tagName == "TD") {
							jQuery.tableDnD.mouseDownLoc = jQuery.tableDnD.mouseCoords(ev);
							if (jQuery.tableDnD.keepMe == true)
								return false;
							jQuery.tableDnD.dragObject = this;
							jQuery.tableDnD.currentTable = table;
							jQuery.tableDnD.mouseOffset = jQuery.tableDnD.getMouseOffset(this, ev);
							if (config.onDragStart) {
								// Call the onDrop method if there is one
								config.onDragStart(table, this);
							}
							return false;
	                    }
	                }).css("cursor", "move"); // Store the tableDnD object
				}
			});
		}
	},

	updateTables: function() {
		this.each(function() {
			// this is now bound to each matching table
			if (this.tableDnDConfig) {
				jQuery.tableDnD.makeDraggable(this);
			}
		})
	},

    /** Get the mouse coordinates from the event (allowing for browser differences) */
    mouseCoords: function(ev){
        if(ev.pageX || ev.pageY){
            return {x:ev.pageX, y:ev.pageY};
        }
        return {
            x:ev.clientX + document.body.scrollLeft - document.body.clientLeft,
            y:ev.clientY + document.body.scrollTop  - document.body.clientTop
        };
    },

    /** Given a target element and a mouse event, get the mouse offset from that element.
        To do this we need the element's position and the mouse position */
    getMouseOffset: function(target, ev) {
        ev = ev || window.event;

        var docPos    = this.getPosition(target);
        var mousePos  = this.mouseCoords(ev);
        return {x:mousePos.x - docPos.x, y:mousePos.y - docPos.y};
    },

    /** Get the position of an element by going up the DOM tree and adding up all the offsets */
    getPosition: function(e){
        var left = 0;
        var top  = 0;
        /** Safari fix -- thanks to Luis Chato for this! */
        if (e.offsetHeight == 0) {
            /** Safari 2 doesn't correctly grab the offsetTop of a table row
            this is detailed here:
            http://jacob.peargrove.com/blog/2006/technical/table-row-offsettop-bug-in-safari/
            the solution is likewise noted there, grab the offset of a table cell in the row - the firstChild.
            note that firefox will return a text node as a first child, so designing a more thorough
            solution may need to take that into account, for now this seems to work in firefox, safari, ie */
            e = e.firstChild; // a table cell
        }

        while (e.offsetParent){
            left += e.offsetLeft;
            top  += e.offsetTop;
            e     = e.offsetParent;
        }

        left += e.offsetLeft;
        top  += e.offsetTop;

        return {x:left, y:top};
    },

    mousemove: function(ev) {
        if (jQuery.tableDnD.dragObject == null) {
            return;
        }
        var dragObj = jQuery(jQuery.tableDnD.dragObject);
        var config = jQuery.tableDnD.currentTable.tableDnDConfig;
        var mousePos = jQuery.tableDnD.mouseCoords(ev);
        var keepMe = jQuery.tableDnD.keepMe;
        if (jQuery.tableDnD.keepMe == true)
        	var y = mousePos.y;
        else
        	var y = mousePos.y - jQuery.tableDnD.mouseOffset.y;
        //auto scroll the window
	    var yOffset = window.pageYOffset;
	 	if (document.all) {
	        // Windows version
	        //yOffset=document.body.scrollTop;
	        if (typeof document.compatMode != 'undefined' &&
	             document.compatMode != 'BackCompat') {
	           yOffset = document.documentElement.scrollTop;
	        }
	        else if (typeof document.body != 'undefined') {
	           yOffset=document.body.scrollTop;
	        }

	    }

		if (mousePos.y-yOffset < config.scrollAmount) {
	    	window.scrollBy(0, -config.scrollAmount);
	    } else {
            var windowHeight = window.innerHeight ? window.innerHeight
                    : document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight;
            if (windowHeight-(mousePos.y-yOffset) < config.scrollAmount) {
                window.scrollBy(0, config.scrollAmount);
            }
        }
				//!debug
//		$('#deebug').remove();
//		currentTarget = jQuery.tableDnD.findDropTargetRow(dragObj, y);
//		$('.contentArea').before('<div style="float: right; display: float;" id="deebug">Current Target: ' + $(currentTarget).attr('class') + '; <br />mousePos.y: ' + mousePos.y + ';<br /> mouseOffset: ' + jQuery.tableDnD.mouseOffset.y + '<br />rowTop: ' + jQuery.tableDnD.rowTop + '<br />rowBot: ' + jQuery.tableDnD.rowBot + '</div>');


        if (y != jQuery.tableDnD.oldY) {
            // work out if we're going up or down...
            var movingDown = y > jQuery.tableDnD.oldY;
            // update the old value
            jQuery.tableDnD.oldY = y;
            // update the style to show we're dragging
			if (jQuery.tableDnD.keepMe == false)
				if (config.onDragClass) {
					dragObj.addClass(config.onDragClass);
				} else {
		            dragObj.css(config.onDragStyle);
				}
            // If we're over a row then move the dragged row to there so that the user sees the
            // effect dynamically
            var currentRow = jQuery.tableDnD.findDropTargetRow(dragObj, y);
           	$(".highlightedTarget").removeClass("highlightedTarget");


			// If you're in touch-mode, and you're carrying something, and you're mousing over a dropTarget, change dropTarget's color.
			if (dragObj != null && $(currentRow).hasClass("dropTarget") == true && keepMe == true)
            {
            	$(currentRow).addClass("highlightedTarget");
				//!debug
//				$("#debug").remove();
//				$(".contentArea").append('<div id="debug" style="float: right;">' + $(currentRow).attr('id') + '</div>');
            }
				
            if (jQuery.tableDnD.keepMe)
            {
            	return false;
            }
            if (currentRow) {
                // TODO worry about what happens when there are multiple TBODIES
                if (movingDown && jQuery.tableDnD.dragObject != currentRow) {
                    jQuery.tableDnD.dragObject.parentNode.insertBefore(jQuery.tableDnD.dragObject, currentRow.nextSibling);
                } else if (! movingDown && jQuery.tableDnD.dragObject != currentRow) {
                    jQuery.tableDnD.dragObject.parentNode.insertBefore(jQuery.tableDnD.dragObject, currentRow);
                }
            }
        }

        return false;
    },

    /** We're only worried about the y position really, because we can only move rows up and down */
    findDropTargetRow: function(draggedRow, y) {
        var rows = jQuery.tableDnD.currentTable.rows;
        for (var i=0; i<rows.length; i++) {
            var row = rows[i];
            var rowY = this.getPosition(row).y;
            var rowHeight = parseInt(row.offsetHeight)/2;
            if (row.offsetHeight == 0) {
                rowY = this.getPosition(row.firstChild).y;
                rowHeight = parseInt(row.firstChild.offsetHeight)/2;
            }
			rowTop = rowY;
			rowBot = rowY + (2*rowHeight);			
			if (jQuery.tableDnD.keepMe == false)
			{
				rowTop = rowY - rowHeight; 
				rowBot = rowY + rowHeight;			
			}
			//!debug
			jQuery.tableDnD.rowTop = rowTop;
			jQuery.tableDnD.rowBot = rowBot;
            // Because we always have to insert before, we need to offset the height a bit
            
            if ((y > rowTop) && (y < rowBot)) {
                // that's the row we're over
				
				//! TODO: If it's the same as the current row, attach it to the mouse.
				//if (row == draggedRow) {return null;}
				
				
                var config = jQuery.tableDnD.currentTable.tableDnDConfig;
                if (config.onAllowDrop) {
                    if (config.onAllowDrop(draggedRow, row)) {
                        return row;
                    } else {
                        return null;
                    }
                } else {
					// If a row has nodrop class, then don't allow dropping (inspired by John Tarr and Famic)
                    var nodrop = jQuery(row).hasClass("nodrop");
                    if (! nodrop) {
                        return row;
                    } else {
                        return null;
                    }
                }
				//window.f.log(row);
                return row;
            }
        }
        return null;
    },
	
    mouseup: function(e) {
		if (jQuery.tableDnD.currentTable && jQuery.tableDnD.dragObject) {
			var droppedRow = jQuery.tableDnD.dragObject;
			jQuery.tableDnD.mouseUpLoc = jQuery.tableDnD.mouseCoords(e);
            
            // If you've mousedowned and mouseupped on the same location
			if (jQuery.tableDnD.mouseDownLoc.y == jQuery.tableDnD.mouseUpLoc.y)
			{
				// We have to know which row we're trying to drop onto while keepMe is still at its original state.
				// What's the row that we're trying to drop onto?
				targetRow = jQuery.tableDnD.findDropTargetRow(droppedRow, jQuery.tableDnD.mouseUpLoc.y);
				// Invert the value of keepMe. If you're picking up, this means keepMe is now true; if dropping, keepMe is now false.
	        	jQuery.tableDnD.keepMe = !(jQuery.tableDnD.keepMe);
		        ///////////////// PICKING UP ///////////////
	        	if (jQuery.tableDnD.keepMe == true)
	        	{
	        		// Create the dropTargets.
	        		$("tr[id^='drag']", jQuery.tableDnD.currentTable).each(function()
	        			{
	        				currentCount = $(this).children(".count").html();

							$(this).before('<tr class="dropTarget" name="movePre' + currentCount + '"><td></td><td class="entityName">Move here</tr>');

	        			}
	        		);
	        		count = currentCount - 1;
	        		$("tr[id^='drag']", jQuery.tableDnD.currentTable).eq(count).after('<tr class="dropTarget noDrag" name="movePre' + currentCount + '"><td></td><td class="entityName">Move here</tr>');
	        		$(jQuery.tableDnD.dragObject).next().remove();
	        		$(jQuery.tableDnD.dragObject).prev().remove();
	        		// Make normal rows undroppable.
	        		$("tr", jQuery.tableDnD.currentTable).not('.dropTarget').addClass("noDrop");
			        var config = jQuery.tableDnD.currentTable.tableDnDConfig;
		            // update the style to show we're dragging
					if (config.onDragClass) {
						$(droppedRow).addClass(config.onDragClass);
					} else {
			            $(droppedRow).css(config.onDragStyle);
					}

	        		
	        		// Update tableDnD
					$("#jQueryChildrenSort").tableDnDUpdate();
					
		        	return;
		        	
		        ///////////////// DROPPING ///////////////
				// If keepMe == false, we're dropping! 
	        	} else {
					// We're only allowed to drop the thing if we're over a dropTarget!
					if ($(targetRow).is(".dropTarget"))
					{
						jQuery.tableDnD.dragObject.parentNode.insertBefore(jQuery.tableDnD.dragObject, targetRow);
					}
			        $(".dropTarget").remove();
	        		$("tr", jQuery.tableDnD.currentTable).has('td').removeClass("noDrop");
	        	}
	        } 

            var config = jQuery.tableDnD.currentTable.tableDnDConfig;
            // If we have a dragObject, then we need to release it,
            // The row will already have been moved to the right place so we just reset stuff
			if (config.onDragClass) {
	            jQuery(droppedRow).removeClass(config.onDragClass);
			} else {
	            jQuery(droppedRow).css(config.onDropStyle);
			}
			$(".dropTarget").remove()
    		$("tr", jQuery.tableDnD.currentTable).has('td').removeClass("noDrop");
            jQuery.tableDnD.dragObject   = null;
            if (config.onDrop) {
                // Call the onDrop method if there is one
                config.onDrop(jQuery.tableDnD.currentTable, droppedRow);
            }
            jQuery.tableDnD.currentTable = null; // let go of the table too
			jQuery.tableDnD.keepMe = false;
		} else {
			$(".dropTarget").remove()
			$("tr", jQuery.tableDnD.currentTable).has('td').removeClass("noDrop");
			jQuery.tableDnD.keepMe = false;
		}
    },

    serialize: function() {
        if (jQuery.tableDnD.currentTable) {
            return jQuery.tableDnD.serializeTable(jQuery.tableDnD.currentTable);
        } else {
            return "Error: No Table id set, you need to set an id on your table and every row";
        }
    },

	serializeTable: function(table) {
        var result = "";
        var tableId = table.id;
        var rows = table.rows;
        for (var i=0; i<rows.length; i++) {
            if (result.length > 0) result += "&";
            var rowId = rows[i].id;
            if (rowId && rowId && table.tableDnDConfig && table.tableDnDConfig.serializeRegexp) {
                rowId = rowId.match(table.tableDnDConfig.serializeRegexp)[0];
            }

            result += tableId + '[]=' + rowId;
        }
        return result;
	},

	serializeTables: function() {
        var result = "";
        this.each(function() {
			// this is now bound to each matching table
			result += jQuery.tableDnD.serializeTable(this);
		});
        return result;
    }

}
jQuery.fn.extend(
	{
		tableDnD : jQuery.tableDnD.build,
		tableDnDUpdate : jQuery.tableDnD.updateTables,
		tableDnDSerialize: jQuery.tableDnD.serializeTables
	}
);