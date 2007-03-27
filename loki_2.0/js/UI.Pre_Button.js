/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "pre" toolbar button.
 */
UI.Pre_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'pre.gif';
	this.title = 'Preformatted';
	this.click_listener = function() { self._loki.toggle_block('pre'); };
	this.state_querier = function() { return self._loki.query_command_state('FormatBlock') == 'pre'; };
};
