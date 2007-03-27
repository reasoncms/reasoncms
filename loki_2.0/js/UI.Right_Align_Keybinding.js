/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Right_Align_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);
	this.test = function(e) { return this.matches_keycode(e, 82) && e.ctrlKey; }; // Ctrl-R
	//this.action = function() { this._loki.exec_command('JustifyRight'); };
	this.action = function() { this._align_helper.align_right(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._align_helper = (new UI.Align_Helper).init(this._loki);
		return this;
	};
};
