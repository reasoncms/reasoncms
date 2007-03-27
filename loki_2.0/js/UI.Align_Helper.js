/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an anchor. Contains code
 * common to both the button and the menu item.
 */
UI.Align_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.init = function(loki)
	{
		this._loki = loki;
		this._paragraph_helper = (new UI.Paragraph_Helper()).init(this._loki);
		return this;
	};

	var _get_alignable_elem = function()
	{
		// Make sure we're not directly within BODY
		self._paragraph_helper.possibly_paragraphify();

		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var ble = Util.Range.get_nearest_bl_ancestor_element(rng);
		return ble;
	};

	this.is_alignable = function()
	{
		return _get_alignable_elem() != null;
	};

	this.align_left = function()
	{
		if ( this.is_alignable() )
		{
			// We assume that the elem is left aligned by default, 
			// if there's no align attr. This is not necessarily true,
			// but we've decided it's better to assume it anyway rather
			// than have lots of unnecessary align="left" attrs popping 
			// up. But maybe we should check "runtime" styles (can we
			// in Gecko ... ?). Good enough for now.
			var elem = _get_alignable_elem();
			if ( elem.getAttribute('align') != null )
				elem.removeAttribute('align');
		}
	};

	this.align_center = function()
	{
		if ( this.is_alignable() )
		{
			var elem = _get_alignable_elem();
			elem.setAttribute('align', 'center');
		}
	};

	this.align_right = function()
	{
		if ( this.is_alignable() )
		{
			var elem = _get_alignable_elem();
			elem.setAttribute('align', 'right');
		}
	};
};
