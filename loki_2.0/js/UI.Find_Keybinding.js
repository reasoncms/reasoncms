/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Find_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return ( this.matches_keycode(e, 70) || this.matches_keycode(e, 72) ) && e.ctrlKey; }; // Ctrl-F or Ctrl-H
	this.action = function() { this._find_helper.open_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._find_helper = (new UI.Find_Helper).init(this._loki);
		return this;
	};
};
