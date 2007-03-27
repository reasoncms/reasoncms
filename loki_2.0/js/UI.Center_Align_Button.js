/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "center align" toolbar button.
 */
UI.Center_Align_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'center.gif';
	this.title = 'Center align (Ctrl+E)';
	this.click_listener = function() { self._loki.exec_command('JustifyCenter'); };
	this.state_querier = function() { return self._loki.query_command_state('JustifyCenter'); };
};
