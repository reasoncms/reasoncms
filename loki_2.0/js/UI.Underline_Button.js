/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "underline" toolbar button.
 */
UI.Underline_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'underline.gif';
	this.title = 'Underline (Ctrl+U)';
	this.click_listener = function() { self._loki.exec_command('Underline'); };
	this.state_querier = function() { return self._loki.query_command_state('Underline'); };
};
