/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "source" toolbar button.
 */
UI.Source_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'source.gif';
	this.title = 'Toggle source';
	this.show_on_source_toolbar = true;
	this.click_listener = function() { self._loki.toggle_iframe_textarea(); };
};
