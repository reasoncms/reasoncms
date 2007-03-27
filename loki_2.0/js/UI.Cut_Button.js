/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents toolbar button.
 */
UI.Cut_Button = function()
{
	Util.OOP.inherits(this, UI.Button);

	this.image = 'cut.gif';
	this.title = 'Cut (Ctrl+X)';
	this.click_listener = function()
	{
		try
		{
			this._clipboard_helper.cut();
		}
		catch(e)
		{
			this._clipboard_helper.alert_helpful_message();
			throw(e); // XXX tmp
		}
	};

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._clipboard_helper = (new UI.Clipboard_Helper).init(this._loki);
		return this;
	};
};
