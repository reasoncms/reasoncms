/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "indent" toolbar button.
 */
UI.Indent_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'indent.gif';
	this.title = 'Indent text';
	this.click_listener = function() 
	{
		// Only indent if we're inside a UL or OL 
		// (Do this to avoid misuse of BLOCKQUOTEs.)
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);

		var ul = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'UL');
		var ol = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'OL');
		var ul_or_ol = ul == null ? ol : ul;
		var li = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'LI');

		if ( ul_or_ol != null )
		{
			mb('ul_or_ol', ul_or_ol);
			var more_distant_ul = Util.Node.get_nearest_ancestor_element_by_tag_name(ul_or_ol, 'UL');
			var more_distant_ol = Util.Node.get_nearest_ancestor_element_by_tag_name(ul_or_ol, 'OL');
			var more_distant_ul_or_ol = more_distant_ul == null ? more_distant_ol : more_distant_ul;
			mb('more_distant_ul_or_ol', more_distant_ul_or_ol);

			// Don't indent first element in a list, if it is not in a nested list.
			// This is because in such a situation, Gecko "indents" by surrounding
			// the UL/OL with a BLOCKQUOTE tag. I.e. <ul><li>as|df</li></ul>
			// --> <blockquote><ul><li>as|df</li></ul></blockquote>
			if ( li.previousSibling != null || more_distant_ul_or_ol != null )
			{
				self._loki.exec_command('Indent');
				self._loki.document.normalize();
			}
		}
	};
	this.state_querier = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		if ( Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'UL') == null ||
			 Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'OL') == null )
		{
			// return disabled
		}
	};
};
