/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Paragraph helper
 */
UI.Paragraph_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.needs_paragraphifying = function(node)
	{
		return node != null && node.nodeName == 'BODY';
		//return ( Util.Node.get_nearest_bl_ancestor_element(node).nodeName == 'BODY' )
	};

	this.possibly_paragraphify = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var container = Util.Range.get_start_container(rng);

		if ( this.needs_paragraphifying(container) )
		{
			this._loki.toggle_block('p');
		}
	};
};
