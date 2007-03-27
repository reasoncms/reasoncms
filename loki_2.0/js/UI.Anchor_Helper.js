/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an anchor. Contains code
 * common to both the button and the menu item.
 */
UI.Anchor_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.init = function(loki)
	{
		this._loki = loki;
		this._anchor_masseuse = (new UI.Anchor_Masseuse()).init(this._loki);
		return this;
	};

	this.is_selected = function()
	{
		return this.get_selected_item() != null;
	};

	var _get_selected_anchor = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		//var surrounding_node = Util.Range.get_common_ancestor(rng);

		var selected_anchor;
		// IE
		var selected_image = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'IMG');
		if ( selected_image != null )
		{
			selected_anchor = self._anchor_masseuse.get_real_elem(selected_image);
		}
		// Gecko
		if ( selected_anchor == null )
		{
			var selected_a = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'A');
			if ( selected_a != null )
			{
				selected_anchor = self._anchor_masseuse.get_real_elem(selected_a);
			}
		}

		return selected_anchor;
	};

	this.get_selected_item = function()
	{
		var selected_anchor = _get_selected_anchor();
		var selected_item;
		if ( selected_anchor != null )
			selected_item = { name : selected_anchor.getAttribute('name') }; 

		return selected_item;
	};

	this.open_dialog = function()
	{
		var selected_item = self.get_selected_item();

		if ( this._dialog == null )
			this._dialog = new UI.Anchor_Dialog;
		this._dialog.init({ base_uri : self._loki.settings.base_uri,
							submit_listener : self.insert_anchor,
							remove_listener : self.remove_anchor,
							selected_item : selected_item });
		this._dialog.open();
	};

	this.insert_anchor = function(anchor_info)
	{
		// Create the anchor
		var anchor = self._loki.document.createElement('A');
		anchor.name = anchor_info.name;

		// Create the dummy
		var dummy = self._anchor_masseuse.get_fake_elem(anchor);

		// Insert the dummy
		var sel = Util.Selection.get_selection(self._loki.window);
		Util.Selection.collapse(sel, true); // to beg
		Util.Selection.paste_node(sel, dummy);

		self._loki.window.focus();
	};

	this.remove_anchor = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var fake_anchor = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'IMG');

		// Move cursor
		Util.Selection.select_node(sel, fake_anchor);
		Util.Selection.collapse(sel, false); // to end
		self._loki.window.focus();

		if ( fake_anchor.parentNode != null )
			fake_anchor.parentNode.removeChild(fake_anchor);
	};
};
