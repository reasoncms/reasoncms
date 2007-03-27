/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "bold" toolbar button.
 */
UI.Bold_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'bold.gif';
	this.title = 'Strong (Ctrl+B)';
	this.click_listener = function() { self._loki.exec_command('Bold'); };
	this.state_querier = function() { return self._loki.query_command_state('Bold'); };
};
