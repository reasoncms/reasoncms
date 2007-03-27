/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for inserting an anchor.
 */
UI.Anchor_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'anchorInNav.gif';
	this.title = 'Insert named anchor';
	this.click_listener = function() { self._anchor_helper.open_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._anchor_helper = (new UI.Anchor_Helper).init(this._loki);
		return this;
	};
};
