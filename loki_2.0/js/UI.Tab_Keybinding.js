/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Tab_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return e.keyCode == 9 && !e.shiftKey && 
							  !document.all &&  // XXX: bad
							  this._tab_helper.is_no_default(); }; // Tab
	this.action = function() { this._tab_helper.focus_next(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._tab_helper = (new UI.Tab_Helper).init(this._loki);
		return this;
	};
};
