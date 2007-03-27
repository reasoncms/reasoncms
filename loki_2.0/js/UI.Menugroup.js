/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a menugroup. For extending only.
 */
UI.Menugroup = function()
{
	var self = this;
	this._loki;

	this.init = function(loki)
	{
		this._loki = loki;
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
	};
};
