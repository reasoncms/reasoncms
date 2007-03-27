/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Page_Link_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return this.matches_keycode(e, 75) && e.ctrlKey; }; // Ctrl-K
	this.action = function() { this._link_helper.open_page_link_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._link_helper = (new UI.Link_Helper).init(loki);
		return this;
	};
};
