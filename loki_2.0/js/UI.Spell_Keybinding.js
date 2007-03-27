/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Spell_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);
	this.test = function(e) { return e.keyCode == 118; }; // F7
	this.action = function() { this._spell_helper.open_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._spell_helper = (new UI.Spell_Helper).init(this._loki);
		return this;
	};
};
