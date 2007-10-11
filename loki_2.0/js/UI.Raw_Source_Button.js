/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "source" toolbar button.
 */
UI.Raw_Source_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'debug.png';
	this.title = 'Alert raw source';
	this.show_on_source_toolbar = true;
	this.click_listener = function() { Util.Window.alert_debug(self._loki.get_dirty_html()); };
};
