/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a body masseuse, to replace elements 
 * inconvenient to edit with fake elements that are convenient 
 * to edit. For extending only.
 */
UI.Masseuse = function()
{
	this._loki;

	/**
	 * Massages the given node's descendants, replacing any elements inconvenient 
	 * to edit with convenient ones.
	 */
	this.massage_node_descendants = function(node)
	{
	};
	
	/**
	 * Unmassages the given node's descendants, replacing any convenient but fake
	 * elements with real ones.
	 */
	this.unmassage_node_descendants = function(node)
	{
	};

	/**
	 * For convenience.
	 */
	this.massage_body = function()
	{
		this.massage_node_descendants(this._loki.document);
	};

	/**
	 * For convenience.
	 */
	this.unmassage_body = function()
	{
		this.unmassage_node_descendants(this._loki.document);
	};
};

UI.Masseuse.prototype.init = function(loki)
{
	this._loki = loki;
	return this;
};
