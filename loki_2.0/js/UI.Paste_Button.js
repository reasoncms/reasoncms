/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents toolbar button.
 */
UI.Paste_Button = function()
{
	Util.OOP.inherits(this, UI.Button);

	this.image = 'paste.gif';
	this.title = 'Paste (Ctrl+V)';
	this.click_listener = function()
	{
		try
		{
			this._clipboard_helper.paste();
		}
		catch(e)
		{
			this._clipboard_helper.alert_helpful_message();
		}
	};

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._clipboard_helper = (new UI.Clipboard_Helper).init(this._loki);
		return this;
	};
};
