/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "italic" toolbar button.
 */
UI.Italic_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'italic.gif';
	this.title = 'Emphasis (Ctrl+I)';
	this.click_listener = function() { self._loki.exec_command('Italic'); };
	this.state_querier = function() { return self._loki.query_command_state('Italic'); };
};
