/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Copy_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return this.matches_keycode(e, 67) && e.ctrlKey; }; // Ctrl-C
	this.action = function() 
	{
		// try-catch so that if anything should go wrong, copy
		// still happens
		try
		{
			this._clipboard_helper.copy();
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
