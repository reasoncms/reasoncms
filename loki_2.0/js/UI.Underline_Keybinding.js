/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Underline_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);
	this.test = function(e) { return this.matches_keycode(e, 73) && e.ctrlKey; }; // Ctrl-U
	this.action = function() { this._loki.exec_command('Underline'); };
};
