/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for running spell check.
 */
UI.Spell_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'spellCheck.gif';
	this.title = 'Spell check (F7)';
	this.click_listener = function() { self._spell_helper.open_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._spell_helper = (new UI.Spell_Helper).init(this._loki);
		return this;
	};
};
