/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Cut_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return this.matches_keycode(e, 88) && e.ctrlKey; }; // Ctrl-X
	this.action = function() 
	{
		// try-catch so that if anything should go wrong, cut
		// still happens
		try
		{
			this._clipboard_helper.cut();
			return false;
		}
		catch(e)
		{
			return true;
		}
	};

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._clipboard_helper = (new UI.Clipboard_Helper).init(this._loki);
		return this;
	};
};
