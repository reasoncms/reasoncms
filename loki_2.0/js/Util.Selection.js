Util.Selection = function()
{
};

Util.Selection.CONTROL_TYPE = 1;
Util.Selection.TEXT_TYPE = 2;

/**
 * Gets the current selection in the given window.
 *
 * @param	window_obj	the window object whose selection is desired
 * @return				the current selection
 */
Util.Selection.get_selection = function(window_obj)
{
	try
	{
		return window_obj.getSelection();
	}
	catch(e)
	{
		try
		{
			return window_obj.document.selection;
		}
		catch(f)
		{
			throw(new Error('Util.Selection.get_selection(): Neither the Mozilla nor the IE way of getting the selection worked. ' +
							'When the Mozilla way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
};

/**
 * Inserts a node at the current selection. The original contents of
 * the selection are is removed. A text node is split if needed.
 *
 * @param	sel				the selection
 * @param	new_node		the node to insert
 */
Util.Selection.paste_node = function(sel, new_node)
{
	// Remember node or last child of node, for selection manipulation below
	if ( new_node.nodeType == Util.Node.DOCUMENT_FRAGMENT_NODE )
		var selectandum = new_node.lastChild;
	else
		var selectandum = new_node;

	// Actually paste the node
	var rng = Util.Range.create_range(sel);
	Util.Range.delete_contents(rng);
	//sel = Util.Selection.get_selection(self._loki.window);
	rng = Util.Range.create_range(sel);
	Util.Range.insert_node(rng, new_node);

	// IE
	if ( document.all ) // XXX bad
	{
		rng.collapse(false);
		rng.select();
	}
	// In Gecko, move selection after node
	{
		// Select all first, to avoid the annoying Gecko
		// quasi-random highlighting bug
		try // in case document isn't editable
		{
			selectandum.ownerDocument.execCommand('selectall', false, null);
			Util.Selection.collapse(sel, true); // to beg
		} catch(e) {}

		// Move the cursor where we want it
		Util.Selection.select_node(sel, selectandum); // works
		Util.Selection.collapse(sel, false); // to end
	}
};

Util.Selection.paste_node__experimental_do_not_use = function(sel, to_be_inserted)
{
	//var range = this._create_range(sel);
	var range = Util.Range.create_range(sel);
	// remove the current selection
	sel.removeAllRanges();
	range.deleteContents();
	var node = range.startContainer;
	var pos = range.startOffset;
	//range = this._create_range();
	//var range = Util.Range.create_range(sel);
	range = node.ownerDocument.createRange();
	switch (node.nodeType)
	{
	case 3: // Node.TEXT_NODE
			// we have to split it at the caret position.
		if (to_be_inserted.nodeType == 3)
		{
			// do optimized insertion
			node.insertData(pos, to_be_inserted.data);
			range.setEnd(node, pos + to_be_inserted.length);
			range.setStart(node, pos + to_be_inserted.length);
		}
		else
		{
			node = node.splitText(pos);
			node.parentNode.insertBefore(to_be_inserted, node);
			range.setStart(node, 0);
			range.setEnd(node, 0);
		}
		break;
	case 1: // Node.ELEMENT_NODE
		node = node.childNodes[pos];
		node.parentNode.insertBefore(to_be_inserted, node);
		range.setStart(node, 0);
		range.setEnd(node, 0);
		break;
	}
	sel.addRange(range);
};
/**
 * Removes all ranges from the given selection.
 *
 * @param	sel		the selection
 */
Util.Selection.remove_all_ranges = function(sel)
{
	// Mozilla
	try
	{
		sel.removeAllRanges();
	}
	catch(e)
	{
		// IE
		try
		{
			sel.empty();
		}
		catch(f)
		{
			throw(new Error('Util.Selection.remove_all_ranges(): Neither the W3C nor the IE way of removing all ranges from the given selection worked. ' +
							'When the W3C way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
};

/**
 * Sets the selection to be the current range
 */
Util.Selection.select_range = function(sel, rng)
{
	// Mozilla
	try
	{
		sel.removeAllRanges(); // should this be here? (yes, I think)
		sel.addRange(rng);
	}
	catch(e)
	{
		// IE
		try
		{
			rng.select();
		}
		catch(f)
		{
			throw(new Error('Util.Selection.remove_all_ranges(): Neither the W3C nor the IE way of removing all ranges from the given selection worked. ' +
							'When the W3C way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
};

/**
 * Selects the given node.
 */
Util.Selection.select_node = function(sel, node)
{
	// Mozilla
	try
	{
		// Select all first, to avoid the annoying Gecko
		// quasi-random highlighting bug
		try // in case document isn't editable
		{
			node.ownerDocument.execCommand('selectall', false, null);
			Util.Selection.collapse(sel, true); // to beg
		} catch(e) {}

		var rng = Util.Range.create_range(sel);
		rng.selectNode(node);
	}
	catch(e)
	{
		// IE
		try
		{
			mb('Util.Selection.select_node: in IE chunk: node', node);
			// This definitely won't work in most cases:
			/*
			if ( node.createTextRange != null )
				var rng = node.createTextRange();
			else if ( node.ownerDocument.body.createControlRange != null )
				var rng = node.ownerDocument.body.createControlRange();
			else
				throw('Util.Selection.select_node: node has neither createTextRange() nor createControlRange().');
			*/

			/*
			try
			{
				var rng = node.createTextRange();
			}
			catch(g)
			{
				var rng = node.createControlRange();
			}
			*/
			rng.select();
		}
		catch(f)
		{
			throw(new Error('Util.Selection.select_node: Neither the Gecko nor the IE way of selecting the node worked. ' +
							'When the Gecko way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
};


/**
 * Selects the contents of the given node. See 
 * Util.Range.select_node_contents for more information.
 */
Util.Selection.select_node_contents = function(sel, node)
{
	var rng = Util.Range.create_range(sel);
	Util.Range.select_node_contents(rng, node);
	Util.Selection.select_range(sel, rng);
};

/**
 * Collapses the given selection.
 *
 * @param	to_start	boolean: true for start, false for end
 */
Util.Selection.collapse = function(sel, to_start)
{
	// Gecko
	try
	{
		if ( to_start )
			sel.collapseToStart();
		else
			sel.collapseToEnd();
	}
	catch(e)
	{
		// IE
		try
		{
			var rng = Util.Range.create_range(sel);
			if ( rng.collapse != null )
			{
				rng.collapse(to_start);
				rng.select();
			}
			// else it's a controlRange, for which collapsing doesn't make sense (?)
		}
		catch(f)
		{
			throw(new Error('Util.Selection.collapse: Neither the Gecko nor the IE way of collapsing the selection worked. ' +
							'When the Gecko way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}

	}
};

/**
 * Returns whether the given selection is collapsed.
 */
Util.Selection.is_collapsed = function(sel)
{
	var rng = Util.Range.create_range(sel);
	if ( rng.text != null )
		return rng.text == '';
	else if ( rng.length != null )
		return rng.length == 0;
	else if ( rng.collapsed != null )
		return rng.collapsed;
	else
		throw("Util.Selection.is_selection_collapsed: Couldn't determine whether selection is collapsed.");
};

/**
 * Gets the given selection's nearest ancestor which maches the given
 * test. (Sort of imitates FCK code.)
 *
 * @param	sel				the selection
 * @param	boolean_test	the test
 * @return					the matching ancestor, if any
 */
Util.Selection.get_nearest_ancestor_node = function(sel, boolean_test)
{
	Util.Object.print_r(sel);

/*
	var container = Util.Selection.get_selected_element(sel);
	if ( container === null )
	{
		try { container = sel.getRangeAt(0).startContainer; } catch(e) {}
	}
	
	


	// Gecko
	var oContainer = this.GetSelectedElement() ;
	if ( ! oContainer && FCK.EditorWindow )
	{
		try		{ oContainer = FCK.EditorWindow.getSelection().getRangeAt(0).startContainer ; }
		catch(e){}
	}

	return false ;

	// IE
	var oContainer ;

	if ( FCK.EditorDocument.selection.type == "Control" )
	{
		oContainer = this.GetSelectedElement() ;
	}
	else
	{
		var oRange  = FCK.EditorDocument.selection.createRange() ;
		oContainer = oRange.parentElement() ;
	}

	while ( oContainer )
	{
		if ( oContainer.nodeType == 1 && oContainer.tagName == nodeTagName ) return true ;
		oContainer = oContainer.parentNode ;
	}


	return false ;



	var ancestor = Util.Range.get_common_ancestor(rng);
	if ( boolean_test(ancestor) )
	{
		return ancestor;
	}
	else
	{
		// TEMP: commented out 2005-05-23
		//alert("Util.Range.get_nearest_ancestor_node: just before recursing with boolean_test; \n" +
			  //"ancestor.outerHTML is " + ancestor.outerHTML + "\n" +
			  //"ancestor == null is " + (ancestor == null ? 'true' : 'false'));
		return Util.Node.get_nearest_ancestor_node(ancestor, boolean_test);
	}
*/
};

/**
 * Returns the selected element, if any. Otherwise returns null.
 * Imitates FCK code.
 */
Util.Selection.get_selected_element = function(sel)
{
	if ( Util.Selection.get_selection_type(sel) == Util.Selection.CONTROL_TYPE )
	{
		// Gecko
		if ( sel.anchorNode != null && sel.anchorOffset != null )
		{
			return sel.anchorNode.childNodes[sel.anchorOffset];
		}
		// IE
		else
		{
			var rng = Util.Range.create_range(sel);
			if ( rng != null && rng.item != null )
				return rng.item(0);
		}
	}
};

/**
 * Gets the type of currently selection.
 * Imitates FCK code.
 */
Util.Selection.get_selection_type = function(sel)
{
	var type;

	// IE
	if ( sel.type != null )
	{
		if ( sel.type == 'Control' )
			type = Util.Selection.CONTROL_TYPE;
		else
			type = Util.Selection.TEXT_TYPE;
	}

	// Gecko
	else
	{
		type = Util.Selection.TEXT_TYPE;

		if ( sel.rangeCount == 1 )
		{
			var rng = sel.getRangeAt(0);
			if ( rng.startContainer == rng.endContainer && ( rng.endOffset - rng.startOffset ) == 1 )
			{
				type = Util.Selection.CONTROL_TYPE;
			}
		}
	}

	return type;
};

/**
 * Moves the cursor to the end (but still inside) the given
 * node. This is useful to call after performing operations 
 * on nodes.
 */
Util.Selection.move_cursor_to_end = function(sel, node)
{
	// Move cursor
	var rightmost = Util.Node.get_rightmost_descendent(node);
	if ( rightmost.nodeName == 'BR' && rightmost.previousSibling != null )
		rightmost = Util.Node.get_rightmost_descendent(rightmost.previousSibling);
	mb('rightmost', rightmost);

	// XXX This doesn't really work right in IE, although it is close
	// enough for now
	if ( rightmost.nodeType == Util.Node.TEXT_NODE )
		Util.Selection.select_node(sel, rightmost);
	else
		Util.Selection.select_node_contents(sel, rightmost);

	Util.Selection.collapse(sel, false); // to end
};
