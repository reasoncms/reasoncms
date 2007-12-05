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
	
	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._masseuse = (new UI.HR_Masseuse).init(this._loki);
		return this;
	};

	this.is_selected = function()
	{
		return !!_get_selected_hr();
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
		Util.Selection.paste_node(sel, self._masseuse.wrap(hr));
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
		var target = self._removal_target(hr);

		// Move cursor
		Util.Selection.select_node(sel, target);
		Util.Selection.collapse(sel, false); // to end
		self._loki.window.focus();

		if ( target.parentNode != null )
			target.parentNode.removeChild(target);
	};
	
	this._removal_target = function(hr)
	{
		var p = hr.parentNode;
		return (Util.Node.is_tag(p, 'DIV') && 'hr' == p.getAttribute('loki:container'))
			? p
			: hr;
	};
};
