/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert a br. Contains code
 * common to both the button and the menu item.
 */
UI.BR_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.insert_br = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var br = self._loki.document.createElement('BR');
		if ( document.all ) // XXX bad
			Util.Selection.paste_node(sel, br);
		else
			_paste_node_for_br_in_gecko(sel, br);
/*
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		rng.setStart(br, 0);
		rng.setEnd(br, 0);
*/
//		Util.Selection.select_node(sel, br);
	//	Util.Selection.collapse(sel, false);
/*
		var rng = Util.Range.create_range(sel);
		var rng2 = Util.Range.clone_range(rng);
		Util.Selection.select_node(sel, self._loki.document.documentElement);
		Util.Selection.select_range(sel, rng2);
		//Util.Selection.collapse(sel, true);
*/
		self._loki.window.focus();
	};

	/**
	 * This function is intended to work around the problem, in Gecko,
	 * that when you click the BR button, a BR is always inserted, but 
	 * the cursor doesn't always move down a line until you start typing--
	 * which is confusing. This doesn't _totally_ fix that problem, but
	 * it's better. XXX more work needed, and get rid of this hack.
	 */
	var _paste_node_for_br_in_gecko = function(sel, to_be_inserted)
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
};
