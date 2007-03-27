/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "Insert HR" toolbar button.
 */
UI.HR_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'hr.gif';
	this.title = 'Horizontal rule';
	this.click_listener = function() { self._hr_helper.insert_hr(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._hr_helper = (new UI.HR_Helper).init(this._loki);
		return this;
	};
};
