/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Shift_Tab_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return e.keyCode == 9 && e.shiftKey && this._tab_helper.is_no_default(); }; // Tab
	this.action = function() { this._tab_helper.shift_tab(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._tab_helper = (new UI.Tab_Helper).init(this._loki);
		return this;
	};
};
