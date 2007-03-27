/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "left align" toolbar button.
 */
UI.Left_Align_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'leftalign.gif';
	this.title = 'Left align (Ctrl-L)';
	this.click_listener = function() { self._loki.exec_command('JustifyLeft'); };
	this.state_querier = function() { return self._loki.query_command_state('JustifyLeft'); };
};
