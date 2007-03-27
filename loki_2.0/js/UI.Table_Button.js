/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for inserting an table.
 */
UI.Table_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'insertTable.gif';
	this.title = 'Insert table';
	this.click_listener = function() { self._table_helper.open_table_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._table_helper = (new UI.Table_Helper).init(this._loki);
		return this;
	};
};
