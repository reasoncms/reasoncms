/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Italic_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);
	this.test = function(e) { return this.matches_keycode(e, 73) && e.ctrlKey; }; // Ctrl-I
	this.action = function() { this._loki.exec_command('Italic'); };
};
