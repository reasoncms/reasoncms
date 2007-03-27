/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "highlight" toolbar button.
 */
UI.Highlight_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'highlight.gif';
	this.title = 'Highlight';
	this.click_listener = function() { self._helper.toggle_blockquote_paragraph(); };
	this.state_querier = function() { return self._helper.query_blockquote_paragraph(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._helper = (new UI.Blockquote_Highlight_Helper).init(this._loki, 'highlight');
		return this;
	};
};
