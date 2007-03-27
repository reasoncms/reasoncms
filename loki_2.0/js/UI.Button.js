/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a button. For extending only.
 */
UI.Button = function()
{
	this.image; // string to location in base_uri/img/
	this.title; // string
	this.click_listener; // function
	this.state_querier; // function (optional)
	this.show_on_source_toolbar = false; // boolean (optional)

	this.init = function(loki)
	{
		this._loki = loki;
		return this;
	};
};
