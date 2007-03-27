/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Bold_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);
	this.test = function(e) { return this.matches_keycode(e, 66) && e.ctrlKey; }; // Ctrl-B
	this.action = function() { this._loki.exec_command('Bold'); };
};
