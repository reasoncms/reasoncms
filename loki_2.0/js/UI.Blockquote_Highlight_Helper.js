/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Helper for blockquote and highlight buttons: contains logic common to both.
 * N.B.: I use "blockquote" below as a convenient shorthand for "blockquote_or_highlight_or_etc".
 */
UI.Blockquote_Highlight_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	/**
	 * @param	kind	either "blockquote" or "highlight"
	 */
	this.init = function(loki, kind)
	{
		this.superclass.init.call(this, loki);
		this._kind = kind;
		this._paragraph_helper = (new UI.Paragraph_Helper()).init(this._loki);
		return this;
	};

	this.is_blockquoted = function()
	{
		return _get_blockquote_elem() != null;
	};

	this.toggle_blockquote_paragraph = function()
	{
		// Make sure we're not directly within BODY
		self._paragraph_helper.possibly_paragraphify();

		_remove_improper_blockquote_class_from_p();
		var blockquote = _get_blockquote_elem();

		//mb('_toggle_blockquote_paragraph: blockquote', blockquote);
		if ( blockquote == null )
		{
			//if ( self.is_blockquoteable() )
				_blockquote_paragraph();
			// else do nothing
		}
		else
		{
			/* works, but is undesired behavior:
			mb('found blockquote; replacing with children; blockquote:', blockquote);
			Util.Node.replace_with_children(blockquote);
			*/
			_unblockquote_paragraph(blockquote);
		}
	};

	/**
	 * Sometimes, despite my best efforts, in IE it seems that the callOut
	 * class gets transferred from div to p. This seems ludicrous, but 
	 * happens. So here we check for a callOut'd p and if found, remove 
	 * the callOut, since that's probably what the user will want.
	 */
	var _remove_improper_blockquote_class_from_p = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var p = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'P');

		if ( Util.Element.has_class(p, 'callOut') )
			Util.Element.remove_class(p, 'callOut');
	};

	var _is_blockquote_elem = function(node)
	{
		if ( self._kind == "blockquote" )
			return ( node.nodeType == Util.Node.ELEMENT_NODE &&
					 node.tagName == 'BLOCKQUOTE' );
		else
			return ( node.nodeType == Util.Node.ELEMENT_NODE &&
					 node.tagName == 'DIV' &&
					 Util.Element.has_class(node, 'callOut') );
	};

	var _create_blockquote_elem = function(doc)
	{
		if ( self._kind == "blockquote" )
			return doc.createElement('BLOCKQUOTE');
		else
		{
			var div = doc.createElement('DIV');
			Util.Element.add_class(div, 'callOut');
			return div;
		}
	};

	/**
	 * Gets the element contained by current selection 
	 * that is blockquoteable. If no such exists, returns null.
	 */
	this.is_blockquoteable = function()
	{
		var is_table_elem = function(node)
		{
			 return ( (new RegExp('ol', 'i')).test(node.tagName) ||
					  (new RegExp('ul', 'i')).test(node.tagName) ||
					  (new RegExp('li', 'i')).test(node.tagName) ||
				  	  (new RegExp('td', 'i')).test(node.tagName) ||
					  (new RegExp('table', 'i')).test(node.tagName) );
		};

		var is_highlightable = function(node)
		{
			return ( node.nodeType == Util.Node.ELEMENT_NODE &&
					 Util.Node.is_nestable_block_level_element(node) &&
					 !Util.Node.has_ancestor_node(node, is_table_elem) );
		};

		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		// This doesn\'t work if, e.g., we have: <body><p>aasd^ad</p><p>asdfas$asdf</p></body>,
		// because the nearest common ancestor is BODY ... :
		//var elem = Util.Range.get_nearest_ancestor_node(rng, is_highlightable);
		var start_container = Util.Range.get_start_container(rng);
		var elem = Util.Node.get_nearest_ancestor_node(start_container, is_highlightable);

		return elem != null;
	};

	var _get_blockquote_elem = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return Util.Range.get_nearest_ancestor_node(rng, _is_blockquote_elem);
	};

	/**
	 * Blockquotes the current paragraph.
	 */
	var _blockquote_paragraph = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var blocks = Util.Range.get_intersecting_blocks(rng);

		if ( blocks.length > 0 )
		{
			// Create and append the blockquote elem
			var blockquote = _create_blockquote_elem(blocks[0].ownerDocument);
			blocks[0].parentNode.insertBefore(blockquote, blocks[0]);

			// Append the blocks to the blockquote
			for ( var i = 0; i < blocks.length; i++ )
			{
				blockquote.appendChild(blocks[i]);
			}
		}

		if ( !document.all ) // XXX doesn't work in IE right now, so just make user click in iframe again:
		{
			Util.Selection.move_cursor_to_end(sel, blockquote);
			self._loki.window.focus();
		}
	};

	var _unblockquote_paragraph = function(blockquote)
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var blocks = Util.Range.get_intersecting_blocks(rng);

		var blockquote1 = blockquote.cloneNode(false); // clone blockquote elem twice
		var blockquote2 = blockquote.cloneNode(false);
		var non_blockquoted = []; // make array for non-blockquoted nodes
		var node = blockquote.firstChild
		var next;

		// loop through blockquote elem's children, adding each child to first clone
		// until first selected block (or last child) is found
		while ( node != blocks[0] && node != null )
		{
			next = node.nextSibling;
			if ( Util.Node.is_non_whitespace_text_node(node) )
				blockquote1.appendChild(node);
			node = next;
		}

		// keep looping, adding each child to array of non blockquoted
		// children, until last selected block (or last child) is found
		while ( node != blocks[blocks.length - 1] && node != null )
		{
			next = node.nextSibling;
			if ( Util.Node.is_non_whitespace_text_node(node) )
				non_blockquoted.push(node);
			node = next;
		}
		// (add the last non-blockquoted child)
		if ( node != null )
		{
			next = node.nextSibling;
			if ( Util.Node.is_non_whitespace_text_node(node) )
				non_blockquoted.push(node);
			node = next;
		}
		
		// keep looping, adding each child to second clone, 
		// until last child is found
		while ( node != null )
		{
			next = node.nextSibling;
			if ( Util.Node.is_non_whitespace_text_node(node) )
				blockquote2.appendChild(node);
			node = next;
		}


		// replace blockquote with placeholder
		var parent = blockquote.parentNode;
		var placeholder = blockquote.ownerDocument.createElement('DIV');
		parent.replaceChild(placeholder, blockquote);
		
		// insert first clone before placeholder
		if ( blockquote1.childNodes.length > 0 )
			parent.insertBefore(blockquote1, placeholder);

		// insert each element in non-blockquoted array before placeholder
		for ( var i = 0; i < non_blockquoted.length; i++ )
			parent.insertBefore(non_blockquoted[i], placeholder);

		// insert second clone before placeholder
		if ( blockquote2.childNodes.length > 0 )
			parent.insertBefore(blockquote2, placeholder);

		// remove placeholder
		parent.removeChild(placeholder);


		// move cursor
		if ( !document.all ) // XXX doesn't work in IE right now, so just make user click in iframe again:
		{
			Util.Selection.move_cursor_to_end(sel, blockquote2);
			self._loki.window.focus();
		}
	};

	/**
	 * Queries whether the current paragraph is highlightable, 
	 * or highlighted. Returns accordingly.
	 */
	this.query_blockquote_paragraph = function()
	{
		// see UI.Highlight_Button
	};
};
