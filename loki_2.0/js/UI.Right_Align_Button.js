/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "right align" toolbar button.
 */
UI.Right_Align_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'rightalign.gif';
	this.title = 'Right align (Ctrl+R)';
	this.click_listener = function() { self._loki.exec_command('JustifyRight'); };
	this.state_querier = function() { return self._loki.query_command_state('JustifyRight'); };
};
