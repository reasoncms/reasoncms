/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an hr. Contains code
 * common to both the button and the menu item.
 */
UI.HR_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.is_selected = function()
	{
		return _get_selected_hr() != null;
	};

	var _get_selected_hr = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'HR');
	};

	this.insert_hr = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var hr = self._loki.document.createElement('HR');
		Util.Selection.paste_node(sel, hr);
		//Util.Selection.select_node(sel, hr);
		//Util.Selection.collapse(sel, false);
		window.focus();
		self._loki.window.focus();
	};

	this.remove_hr = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var hr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'HR');

		// Move cursor
		Util.Selection.select_node(sel, hr);
		Util.Selection.collapse(sel, false); // to end
		self._loki.window.focus();

		if ( hr.parentNode != null )
			hr.parentNode.removeChild(hr);
	};
};
