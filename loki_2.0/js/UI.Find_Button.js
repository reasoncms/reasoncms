/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for a find-and-replace button.
 */
UI.Find_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'findReplace.gif';
	this.title = 'Find and replace (Ctrl+F)';
	this.click_listener = function() { self._find_helper.open_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._find_helper = (new UI.Find_Helper).init(this._loki);
		return this;
	};
};
