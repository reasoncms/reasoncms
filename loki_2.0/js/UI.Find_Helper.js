/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for finding and replacing.
 */
UI.Find_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.open_dialog = function()
	{
		if ( this._dialog == null )
			this._dialog = new UI.Find_Dialog;
		this._dialog.init({ base_uri : self._loki.settings.base_uri,
							 	   find_listener : self.find,
							 	   replace_listener : self.replace,
							 	   replace_all_listener : self.replace_all,
		                           select_beginning_listener : self.select_beginning });
		this._dialog.open();
	};

	this.find = function(search_str, match_case, match_backwards, wrap)
	{
		try // Gecko
		{
			// window.find( searchString, caseSensitive, backwards, wrapAround, showDialog, wholeWord, searchInFrames ) ;
			var was_found = self._loki.window.find(search_str, match_case, match_backwards, true, false, false);
			return was_found ? UI.Find_Helper.FOUND : UI.Find_Helper.NOT_FOUND;
	//oEditor.FCK.EditorWindow.find( document.getElementById('txtFind').value, bCase, false, false, bWord, false, false ) ;
		}
		catch(e)
		{
			try // IE
			{
				var flags = 0;
				//if ( whole_words_only )
				//	flags += 2;
				if ( match_case )
					flags += 4;

				var sel = Util.Selection.get_selection(self._loki.window);
				var rng = Util.Range.create_range(sel);

				if ( rng != null )
				{
					rng.collapse(false);
					var was_found = rng.findText(search_str, 10000000, flags);
					if ( was_found )
						rng.select();
				}

				return was_found ? UI.Find_Helper.FOUND : UI.Find_Helper.NOT_FOUND;
			}
			catch(f)
			{
				throw(new Error('UI.Find_Helper.find: Neither the Gecko nor the IE way of finding text worked. When the Mozilla way was tried, an error with the following message was thrown: <<' + e.message + '>>. When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
			}
		}
	};

	this.replace = function(search_str, replace_str, match_case, match_backwards, wrap)
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		
		// If the search string isn't already selected,
		// this is presumably the first time the user is 
		// clicking the "replace" button (and hasn't already
		// clicked "find"), so we need to do that before we
		// replace anything.
		if ( Util.Range.get_text(rng).toLowerCase() != search_str.toLowerCase() )
		{
			/*
			if ( match_backwards )
				Util.Selection.collapse(sel, false); // to end
			else
				Util.Selection.collapse(sel, true); // to start

			var matched = self.find(search_str, match_case, match_backwards, wrap);
			if ( matched == UI.Find_Helper.NOT_FOUND )
				return UI.Find_Helper.NOT_FOUND;
			*/

			return self.find(search_str, match_case, match_backwards, wrap);
		}
		else
		{
			sel = Util.Selection.get_selection(self._loki.window);
			Util.Selection.paste_node(sel, self._loki.document.createTextNode(replace_str));

			var matched = self.find(search_str, match_case, match_backwards, wrap);
			if ( matched == UI.Find_Helper.NOT_FOUND )
				return UI.Find_Helper.REPLACED_LAST_MATCH;

			return UI.Find_Helper.REPLACED;
		}
	};

	this.replace_all = function(search_str, replace_str, match_case, match_backwards)
	{
		self.select_beginning();

		var matched = true;
		var i = 0;
		while ( matched != UI.Find_Helper.NOT_FOUND && i < 500 ) // to be safe
		{
			matched = self.replace(search_str, replace_str, match_case, match_backwards, false);
			if ( matched == UI.Find_Helper.REPLACED || matched == UI.Find_Helper.REPLACED_LAST_MATCH )
				i++;
		}
		return i;
	};

	this.select_beginning = function()
	{
		sel = Util.Selection.get_selection(self._loki.window);
		//Util.Selection.select_node(sel, self._loki.document.getElementsByTagName('BODY')[0]);
		Util.Selection.select_node_contents(sel, self._loki.document.getElementsByTagName('BODY')[0]);
		Util.Selection.collapse(sel, true);
	};
};

UI.Find_Helper.FOUND = 1;
UI.Find_Helper.NOT_FOUND = 2;
UI.Find_Helper.REPLACED = 3;
UI.Find_Helper.REPLACED_LAST_MATCH = 4;
