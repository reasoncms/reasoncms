/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Paste_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return this.matches_keycode(e, 86) && e.ctrlKey; }; //Ctrl-V
	this.action = function() 
	{
		// try-catch so that if anything should go wrong, paste
		// still happens
		try
		{
			this._clipboard_helper.paste();
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
