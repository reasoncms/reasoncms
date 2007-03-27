/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for inserting an image.
 */
UI.Image_Button = function()
{
	var self = this;
	Util.OOP.inherits(this, UI.Button);

	this.image = 'image.gif';
	this.title = 'Insert image';
	this.click_listener = function() { self._helper.open_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._helper = (new UI.Image_Helper).init(this._loki);
		return this;
	};
};
