/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "blockquote" toolbar button.
 */
UI.Blockquote_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'blockquote.gif';
	this.title = 'Blockquote';
	this.click_listener = function() { self._helper.toggle_blockquote_paragraph(); };
	this.state_querier = function() { return self._helper.query_blockquote_paragraph(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._helper = (new UI.Blockquote_Highlight_Helper).init(this._loki, 'blockquote');
		return this;
	};
};
