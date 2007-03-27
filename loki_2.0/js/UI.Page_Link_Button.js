/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "link to page" toolbar button.
 */
UI.Page_Link_Button = function()
{
	var self = this;
	Util.OOP.inherits(this, UI.Button);

	this.image = 'link.gif';
	this.title = 'Insert link (Ctrl+K)';
	this.click_listener = function() { self._helper.open_page_link_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._helper = (new UI.Link_Helper).init(this._loki);
		return this;
	};
};
