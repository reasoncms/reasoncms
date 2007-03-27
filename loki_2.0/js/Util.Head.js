/**
 * Does nothing
 * @constructor
 *
 * @class Contains functions pertaining to head elements.
 */
Util.Head = function()
{
};

/**
 * Append the style sheet at the given location with the given id
 *
 * @param	location	the location of the stylesheet to add
 * @static
 */
Util.Head._append_style_sheet = function(location)
{
	var head_elem = this._dialog_window.document.getElementsByTagName('head').item(0);
	var link_elem = this._dialog_window.document.createElement('link');

	link_elem.setAttribute('href', location);
	link_elem.setAttribute('rel', 'stylesheet');
	link_elem.setAttribute('type', 'text/css');

	head_elem.appendChild(link_elem);
};
