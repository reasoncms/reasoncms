/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping perform action. Contains code
 * common to both the button and the menugroup for doing whatever
 * the action is.
 */
UI.Helper = function()
{	
	this.init = function(loki)
	{
		this._loki = loki;
		return this;
	};
};
