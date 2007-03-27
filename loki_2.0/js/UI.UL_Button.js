/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "ul" toolbar button.
 */
UI.UL_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'ul.gif';
	this.title = 'Unordered list';
	this.click_listener = function() { self._loki.toggle_list('ul'); };
};
