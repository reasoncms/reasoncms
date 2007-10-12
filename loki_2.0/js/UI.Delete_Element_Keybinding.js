/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Delete_Element_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return ( this.matches_keycode(e, 8) || this.matches_keycode(e, 127) ); }; // Backspace or delete

	this.action = function()
	{
		if ( this._image_helper.is_selected() )
		{
			this._image_helper.remove_image();
			return false; // cancel event's default action
		}
		else if ( this._anchor_helper.is_selected() )
		{
			this._anchor_helper.remove_anchor();
			return false;
		}
		else if ( this._hr_helper.is_selected() )
		{
			this._hr_helper.remove_hr();
			return false;
		}
		else if ( this._table_helper.is_table_selected() && 
				  !this._table_helper.is_cell_selected() && 
				  confirm('Really remove table? WARNING: This cannot be undone.') )
		{
			this._table_helper.remove_table();
			return false;
		}
		else
		{
			// Prevent the following IE bug: "When there is no apparent focus (e.g. when the page first 
			// loads and you haven't done anything yet), clicking below the last element in the Loki 
			// area) and hitting backspace zaps all of the content in the Loki area and you lose the 
			// cursor."
			if (Util.Browser.IE) // not sure this restraint is necessary, but there's 
								 // no point risking unexpected behavior in Gecko
			{
				this._loki.window.focus();
				//this._loki.exec_command('SelectAll');
				//var sel = Util.Selection.get_selection(this._loki.window);
				//Util.Selection.collapse(sel, false); // to end
			}
		}

		return true; // don't cancel event's default action
	};

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._image_helper = (new UI.Image_Helper).init(this._loki);
		this._anchor_helper = (new UI.Anchor_Helper).init(this._loki);
		this._hr_helper = (new UI.HR_Helper).init(this._loki);
		this._table_helper = (new UI.Table_Helper).init(this._loki);
		return this;
	};
};
