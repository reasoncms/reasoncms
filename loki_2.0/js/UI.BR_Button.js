/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "Insert BR" toolbar button.
 */
UI.BR_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'break.gif';
	this.title = 'Single line break (Shift+Enter)';
	this.click_listener = function() { self._br_helper.insert_br(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._br_helper = (new UI.BR_Helper).init(this._loki);
		return this;
	};
};
