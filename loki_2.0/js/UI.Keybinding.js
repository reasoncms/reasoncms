/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a keybinding. For extending only.
 */
UI.Keybinding = function()
{
	this.test; // function
	this.action; // function

	this.init = function(loki)
	{
		this._loki = loki;
		return this;
	};

	/**
	 * Returns whether the given keycode matches that 
	 * of the given event. 
	 */
	this.matches_keycode = function(e, keycode, XXX)
	{
		/*
		if ( e.keyCode == keycode ||  // keydown (IE)
			 ( e.keyCode == 0 &&      // keypress (Gecko)
			   ( e.charCode == keycode ||
			     ( ( e.charCode >= 65 || e.charCode <= 90 ) && // is uppercase alpha
			         e.charCode == keycode + 32 ) ) ) ) // keypress (Gecko)
		*/

		if ( e.type == 'keydown' && e.keyCode == keycode ) // IE
			return true;
		else if ( e.type == 'keypress' && (e.charCode == keycode || (((e.charCode >= 65 || e.charCode <= 90) && e.charCode == keycode + 32))) ) // Gecko
			return true;
		else
			return false;
	//this.test = function(e) { return ( e.charCode == 98 || e.charCode == 66 ) && e.ctrlKey; }; // Ctrl-B
	//this.test = function(e) { return ( e.keyCode == 98 || e.charCode == 66 ) && e.ctrlKey; }; // Ctrl-B
	};
};
