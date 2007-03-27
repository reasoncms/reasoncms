/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an spell. Most
 * of this and the other spell check code is verbatim
 * from the old version (first: 14/May/2004) of Loki.
 */
UI.Spell_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.open_dialog = function()
	{
		if ( this._dialog == null )
			this._dialog = new UI.Spell_Dialog;
		this._dialog.init({ base_uri : self._loki.settings.base_uri,
							submit_listener : self.update_body,
		                    uncorrected_html : self._loki.get_html(),
							spell_uri : 'auxil/spell_iframe.php' });
		this._dialog.open();
	};

	this.update_body = function(spell_info)
	{
		self._loki.set_html(spell_info.corrected_html);

		var sel = Util.Selection.get_selection(self._loki.window);
		Util.Selection.move_cursor_to_end(sel, self._loki.body);
		self._loki.window.focus();
	};
};
