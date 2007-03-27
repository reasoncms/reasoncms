/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an anchor. Contains code
 * common to both the button and the menu item.
 */
UI.Tab_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.is_no_default = function()
	{
		// not in table
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		if ( Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE') != null )
			return false;

		// not at beg of li
		var li = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'LI');
		if ( li != null && Util.Range.is_at_beg_of_block(rng, li) )
			return false;

		// not in pre
		if ( Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'PRE') != null )
			return false;

		return true;
	};

	this.focus_next = function()
	{
		var form = this._loki.hidden.form;
		for ( var i = 0; i < form.elements.length; i++ )
		{
			if ( form.elements[i] == this._loki.hidden &&
				 i + 1 < form.elements.length )
			{
				var next_elem = form.elements[i + 1];
				next_elem.focus();
			}
		}
	};
};
