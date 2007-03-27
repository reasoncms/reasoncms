/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a menuitem. Can be extended or used as it is.
 */
UI.Separator_Menuitem = function()
{
	var _label;

	this.init = function()
	{
		return this;
	};

	/**
	 * Returns an appendable chunk to render the menuitem.
	 */
	this.get_chunk = function(doc)
	{
		var sep = doc.createElement('HR');
		Util.Element.add_class(sep, 'separator_menuitem');
		return sep;
	};
};
