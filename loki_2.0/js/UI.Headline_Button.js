/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "headline" toolbar button.
 */
UI.Headline_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'header.gif';
	this.title = 'Headline';
	this.click_listener = function() { self._loki.toggle_block('h3'); };
	this.state_querier = function() { return self._loki.query_command_state('FormatBlock') == 'h3'; };
};
