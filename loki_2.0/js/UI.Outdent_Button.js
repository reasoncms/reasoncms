/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "outdent" toolbar button.
 */
UI.Outdent_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'outdent.gif';
	this.title = 'Remove indent';
	// XXX: eventually take the duplicated code here and that in Indent_Button and combine it
	// into a helper. Not worth it right now.
	this.click_listener = function() 
	{
		// Only outdent if we're inside a UL or OL 
		// (Do this to avoid misuse of BLOCKQUOTEs.)
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		if ( Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'UL') != null ||
			 Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'OL') != null )
		{
			self._loki.exec_command('Outdent');
		}
	};
	this.state_querier = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		if ( Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'UL') == null ||
			 Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'OL') == null )
		{
			// XXX: *should* this be so? -EN
			// return disabled
		}
	};
};
