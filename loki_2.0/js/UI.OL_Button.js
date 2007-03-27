/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "ol" toolbar button.
 */
UI.OL_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'ol.gif';
	this.title = 'Ordered list';
	this.click_listener = function() { self._loki.toggle_list('ol'); };
};
