/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a menu.
 */
UI.Menu = function()
{
	var self = this;
	var _loki;
	var _chunk;
	var _menuitems = new Array();

	self.init = function(loki)
	{
		_loki = loki;
		return self;
	};

	self.add_menuitem = function(menuitem)
	{
		_menuitems.push(menuitem);
	};
	

	self.add_menuitems = function(menuitems)
	{
		if ( menuitems != null )
		{
			for ( var i = 0; i < menuitems.length; i++ )
			{
				self.add_menuitem(menuitems[i]);
			}
		}
	};

	var _get_chunk = function(popup_document)
	{
		var menu_chunk = popup_document.createElement('DIV');
		Util.Event.add_event_listener(menu_chunk, 'contextmenu', 
			function(event)
			{ 
				// Stop the normal context menu from displaying
				try { event.preventDefault(); } catch(e) {} // Gecko
				return false; // IE
			});
		menu_chunk.style.zindex = 1000;
		Util.Element.add_class(menu_chunk, 'contextmenu');

		for ( var i = 0; i < _menuitems.length; i++ )
		{
			menu_chunk.appendChild(_menuitems[i].get_chunk(popup_document));
		}

		//menu_chunk.innerHTML = 'This is the context menu.'
		return menu_chunk;
	};

	/**
	 * Renders the menu.
	 * 
	 * Much of this code, especially the Gecko part, is lightly 
	 * modified from FCK; some parts are modified from TinyMCE;
	 * some parts come from Brian's Loki menu code.
	 */
	self.display = function(x, y)
	{
		// IE way
		try
		{
			// Make the popup and append the menu to it
			var popup = window.createPopup();
			var menu_chunk = _get_chunk(popup.document);
			var popup_body = popup.document.body;
			Util.Element.add_class(popup_body, 'loki');
			Util.Document.append_style_sheet(popup.document, _loki.settings.base_uri + 'css/Loki.css');
			popup_body.appendChild(menu_chunk);

			// Get width and height of the menu
			//
			// We use this hack (first appending a copy of the menu directly in the document,
			// and getting its width and height from there rather than from the copy of
			// the menu appended to the popup) because we append the "Loki.css" style sheet to 
			// the popup, but that may not have loaded by the time we want to find the width 
			// and height (even though it will probably be stored in the cache). Since "Loki.css"
			// has already been loaded for the main editor window, we can reliably get the dimensions
			// there.
			//
			// We surround the menu chunk here in a table so that the menu chunk div shrinks
			// in width as appropriate--since divs normally expand width-wise as much as they
			// can.
			var tmp_container = _loki.owner_document.createElement('DIV');
			tmp_container.style.position = 'absolute';
			tmp_container.innerHTML = '<table><tbody><tr><td></td></tr></tbody></table>';
			var tmp_menu_chunk = _get_chunk(_loki.owner_document);
			tmp_container.firstChild.firstChild.firstChild.firstChild.appendChild(tmp_menu_chunk);
			_loki.root.appendChild(tmp_container);
			var width = tmp_menu_chunk.offsetWidth;
			var height = tmp_menu_chunk.offsetHeight;
			_loki.root.removeChild(tmp_container);

			// This simple method of getting width and height would work, if we hadn't
			// loaded a stylesheet for the popup (see above):
			// (NB: we could also use setTimeout for the below, but that would break if 
			// the style sheet wasn't stored in the cache and thus had to be actually
			// downloaded.)
			//popup.show(x, y, 1, 1);
			//var width = menu_chunk.offsetWidth;
			//var height = menu_chunk.offsetHeight;

			Util.Event.add_event_listener(popup.document, 'click', function() { popup.hide(); });

			// Show the popup
			popup.show(x, y, width, height);
		}
		catch(e)
		{
			// Gecko way
			try
			{
				// Create menu, hidden
				var menu_chunk = _get_chunk(_loki.owner_document);
				_loki.root.appendChild(menu_chunk);
				menu_chunk.style.position = 'absolute';
				menu_chunk.style.visibility = 'hidden';

				// Position menu
				menu_chunk.style.left = x + 'px';
				menu_chunk.style.top = y + 'px';

				// Watch the "click" event for all windows to close the menu
				var close_menu = function() 
				{
					// We're adding the listener to several windows,
					// and aren't controlling bubbling, so the event may be triggered
					// several times.
					if ( menu_chunk.parentNode != null )
						menu_chunk.parentNode.removeChild(menu_chunk);
				};
				var cur_window = _loki.window;
				while ( cur_window )
				{
					Util.Event.add_event_listener(cur_window.document, 'click', close_menu);
					Util.Event.add_event_listener(cur_window.document, 'contextmenu', close_menu);
					if ( cur_window != cur_window.parent )
						cur_window = cur_window.parent;
					else
						break;
				}
		
				// Show menu
				menu_chunk.style.visibility	= '';
			}
			catch(f)
			{
				throw(new Error('UI.Menu.display(): Neither the IE nor the Gecko way of displaying a menu worked. ' +
								'When the IE way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
								'When the Gecko way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
			}
		}
	};
};
