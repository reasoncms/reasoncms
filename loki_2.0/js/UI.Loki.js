/**
 * Declares instance variables. <code>init</code> must be called to initialize them.
 * @constructor
 *
 * @param	textarea	the textarea to replace with Loki
 *
 * @class A WYSIWYG HTML editor.
 */
UI.Loki = function(textarea, settings)
{
	var _owner_document; // that of _textarea etc.
	var _window;     //
	var _document;   // _window, _document, and _body are those of _iframe's content
	var _body;       //

	var _root;               // + root (div)
	var _toolbar;            // |--- toolbar (div)
	var _textarea_toolbar;   // |--- textarea_toolbar (div)
	var _textarea;           // |---get_nearest_bl_ancestor_element textarea
	var _statusbar;          // |--- statusbar (div)
	var _grippy_wrapper;     // |--+ grippy_wrapper (div)
	var _grippy;             //    |--- grippy (img)
	var _iframe_wrapper;     // |--+ iframe_wrapper (table)
	var _iframe;             //    |--- iframe
	var _hidden;             // |--- hidden (input)

	var _settings;
	var _use_p_hack;
	var _state_change_listeners = Array();
	var _masseuses = Array();
	var _menugroups = Array();
	var _keybindings = Array();
	var _editor_domain;

	var self = this;


	/**
	 * Returns the HTML of the document currently being edited.
	 *
	 * @return	string	the HTML of the document currently being edited.
	 */
	this.get_html = function()
	{
		// For some reason, this doesn't work (clean still cleans
		// _body, not clone) ... but it's not really necessary, either
// 		var clone = _body.cloneNode(true);
// 		UI.Clean.clean(clone, _settings);
// 		return clone.innerHTML;

		_unmassage_body();
		UI.Clean.clean(_body, _settings);
		html = _body.innerHTML;
		html = UI.Clean.cleanHtml(html, _settings);
		_massage_body();
		return html;

/*
		// added NF 10/21 for TinyMCE
		var control = new TinyMCEControl();
		control.init(_window, _iframe);
		var tinyMCE = new TinyMCE();
		tinyMCE.init(_window, control);

		tinyMCE._cleanupHTML(tinyMCE.selectedInstance, tinyMCE.contentWindow.document, null, tinyMCE.contentWindow.document.body, null, false);
		//TinyMCE.prototype._cleanupHTML = function(inst, doc, config, element, visual, on_save) 
*/
	};

	this.get_dirty_html = function()
	{
		return _body.innerHTML;
	};

	/**
	 * Sets the HTML of the document.
	 *
	 * @param	html	the HTML of the document
	 */
	this.set_html = function(html)
	{
		_body.innerHTML = html;
		UI.Clean.clean(_body, _settings);
		_massage_body();
	};

	/**
	 * Copies the value of the iframe to the value of the textarea.
	 */
	this.copy_iframe_to_hidden = function()
	{
		_hidden.value = self.get_html();
	};

	/**
	 * Returns whether the textarea (vs the editable iframe)
	 * is currently active.
	 */
	var _is_textarea_active = function()
	{
		return _textarea.parentNode == _root;
	};

	/**
	 * Toggles textarea and iframe.
	 */
	this.toggle_iframe_textarea = function()
	{
		if ( _is_textarea_active() )
		{
			self.textarea_to_iframe();
		}
		else
		{
			self.iframe_to_textarea();
		}
	};
	
	/**
	 * Shows textarea instead of iframe.
	 */
	this.iframe_to_textarea = function()
	{
		_textarea.value = self.get_html(); // this runs the cleaning code
		_root.replaceChild(_textarea, _iframe_wrapper);
		_root.removeChild(_hidden);

		// recreate the toolbars before swapping in the new one,
		// in order to get rid of any lingering "hover"-class'd buttons.
		old_toolbar = _toolbar;
		_create_toolbars(); 
		_root.replaceChild(_textarea_toolbar, old_toolbar);
		_textarea.focus();
	};

	/**
	 * Shows iframe instead of textarea.
	 */
	this.textarea_to_iframe = function()
	{
		self.set_html(_textarea.value);
		_root.replaceChild(_iframe_wrapper, _textarea);
		_root.appendChild(_hidden);
		_init_async();

		// recreate the toolbars before swapping in the new one,
		// in order to get rid of any lingering "hover"-class'd buttons.
		old_toolbar = _textarea_toolbar;
		_create_toolbars();
		_root.replaceChild(_toolbar, old_toolbar);
		_window.focus();
	};


	/**
	 * Initializes instance variables.
	 *
	 * @param	textarea	the textarea to replace with Loki
	 */
	this.init = function(textarea, settings)
	{
		_settings = settings;

		_textarea = textarea;
		_owner_document = _textarea.ownerDocument;

		_use_p_hacks = _use_p_hacks();

		// Create the various elements
		_create_root();
		_create_toolbars();
		_create_iframe();
		if ( _settings.options.test('statusbar') )
			_create_statusbar();
		_create_grippy();
		_create_hidden();

		// And append them to root
		_root.appendChild( _toolbar );
		_root.appendChild( _iframe_wrapper );
		if ( _settings.options.test('statusbar') )
			_root.appendChild( _statusbar );
		_root.appendChild( _grippy_wrapper );
		_root.appendChild( _hidden );

		// Replace the textarea with root
		_replace_textarea();

		// Append style sheets
		_append_owner_document_style_sheets();

		// Add document massagers
		_add_masseuses();

		// Init possible menugroups, for the context menu
		_init_menugroups();

		// Continue the initialization, but asynchronously
		_init_async();
	};

	/**
	 * Finishes initializing instance variables, but does so
	 * asynchronously. All initing that requires _window or _document
	 * to be available should be done in this function, because this
	 * function waits until _window and _document are available to do
	 * anything.
	 */
	var _init_async = function()
	{
		try
		{
			// Try to init references to iframe content's window and
			// document ...
			try
			{
				_window = _iframe.contentWindow;
				_document = _window.document;
				if ( _window == null || _document == null )
					throw(new Error('UI.Loki._init_iframe: Couldn\'t init iframe. Will try again.'));
			}
			// ... but if the window or document aren't available yet
			// (because the 'about:blank' document hasn't finished
			// loading), try again in a few milliseconds.
			//
			// Be sure that if you change the name of the present
			// function, you also change what you call in setTimeout
			// below.
			catch(f)
			{
				setTimeout(_init_async, 10);
				return;
			}

			// Do things that require _window or _document

			// Write out a blank document
			_clear_document();

			_document.close();

			// Append style sheets for the iframe
			_append_document_style_sheets();

			// Init reference to that document's body
			_body = _document.getElementsByTagName('BODY').item(0);
			Util.Element.add_class(_body, 'contentMain'); // so front-end stylesheets work

			// Add public members // XXX the private ones should just be replaced to public ones
			self.window = _window;
			self.document = _document;
			self.body = _body;
			self.owner_document = _owner_document;
			self.root = _root;
			self.hidden = _hidden;
			self.settings = _settings;
			self.exec_command = _exec_command;
			self.query_command_state = _query_command_state;
			self.query_command_value = _query_command_value;
			self.focus = function() { _window.focus() };
			
			// Set body's html to textarea's value
			self.set_html( _textarea.value );

			// Make the document editable
			_make_document_editable();

			// Add certain event listeners to the document and elsewhere
			_add_document_listeners();
			_add_state_change_listeners();
			_add_grippy_listeners();

			// Add keybindings
			_add_keybindings();
		}
		catch(e)
		{
			// If anything goes wrong during initialization, first
			// revert to the textarea before re-throwing the error
			self.iframe_to_textarea();
			throw(new Error('UI.Loki._init_async: I reverted to source view because the following ' +
							'error prevented Loki from initializing: <<' + e.message + '>>.'));
		}
	};
	
	/**
	 * Returns the domain under which this editor instance exists.
	 */
	this.editor_domain = function()
	{
		if (null == self._editor_domain) {
			self._editor_domain = Util.URI.extract_domain(window.location);
		}
		
		return self._editor_domain;
	};

	/**
	 *
	 */
	var _use_p_hacks = function()
	{
		return navigator.product == 'Gecko';
	};

	/**
	 * Creates the root element for Loki.
	 */
	var _create_root = function()
	{
		_root = _owner_document.createElement('DIV');
		Util.Element.add_class(_root, 'loki');
	};

	/**
	 * Creates the toolbar, populated with the appropriate buttons.
	 */
	var _create_toolbars = function()
	{
		// Create the toolbar itself
		_toolbar = _owner_document.createElement('DIV');
		_textarea_toolbar = _owner_document.createElement('DIV');
		Util.Element.add_class(_toolbar, 'toolbar');
		Util.Element.add_class(_textarea_toolbar, 'toolbar');

		// Function to add a button to a the toolbars
		var add_button = function(button_class)
		{
			var b = new button_class();
			b.init(self);

			function create_button()
			{
				var button = _owner_document.createElement('A');
				button.href = 'javascript:void(0);';

				Util.Event.add_event_listener(button, 'mouseover', function() { Util.Element.add_class(button, 'hover'); });
				Util.Event.add_event_listener(button, 'mouseout', function() { Util.Element.remove_class(button, 'hover'); });
				Util.Event.add_event_listener(button, 'mousedown', function() { Util.Element.add_class(button, 'active'); });
				Util.Event.add_event_listener(button, 'mouseup', function() { Util.Element.remove_class(button, 'active'); });
				Util.Event.add_event_listener(button, 'click', function() { b.click_listener(); });

				// make the button appear depressed whenever the current selection is in a relevant (bold, heading, etc) region
				if ( b.state_querier != null )
				{
					_state_change_listeners.push( 
						function()
						{
							if ( b.state_querier() &&	(Util.Element.get_all_classes(button)).indexOf('active') == -1 ) 
								Util.Element.add_class(button, 'active' /*'selected'*/);
							else
								if ( !b.state_querier() && (Util.Element.get_all_classes(button)).indexOf('active') > -1 )
									Util.Element.remove_class(button, 'active' /*'selected'*/);
						}
					);
				}

				var img = _owner_document.createElement('IMG');
				img.src = _settings.base_uri + 'images/nav/' + b.image;
				img.title = b.title;
				img.width = 21;
				img.height = 20;
				img.border = 0;
				img.setAttribute('unselectable', 'on');
				button.appendChild(img);

				return button;
			};

			_toolbar.appendChild(create_button());
			if ( b.show_on_source_toolbar == true )
				_textarea_toolbar.appendChild(create_button());
		};

		// Add each button to the toolbars
		
		var button_map = {
			strong: [UI.Bold_Button],
			em: [UI.Italic_Button],
			underline: [UI.Underline_Button],
			headline: [UI.Headline_Button],
			pre: [UI.Pre_Button],
			linebreak: [UI.BR_Button],
			hrule: [UI.HR_Button],
			clipboard: [UI.Copy_Button, UI.Cut_Button, UI.Paste_Button],
			// align: [UI.Left_Align_Button, UI.Center_Align_Button, UI.Right_Align_Button],
			highlight: [UI.Highlight_Button],
			blockquote: [UI.Blockquote_Button],
			olist: [UI.OL_Button],
			ulist: [UI.UL_Button],
			indenttext: [UI.Indent_Button, UI.Outdent_Button],
			findtext: [UI.Find_Button],
			table: [UI.Table_Button],
			image: [UI.Image_Button],
			link: [UI.Page_Link_Button],
			anchor: [UI.Anchor_Button],
			source: [UI.Source_Button, UI.Raw_Source_Button]
		};
		
		for (var s in button_map) {
			if (_settings.options.test(s)) {
				Util.Array.for_each(button_map[s], add_button);
			}
		}
	};

	/**
	 * Creates the iframe
	 */
	var _create_iframe = function()
	{
		// EN: why wrap it in a table?
		_iframe_wrapper = _owner_document.createElement('TABLE');
		var tbody = _owner_document.createElement('TBODY');
		var tr = _owner_document.createElement('TR');
		var td = _owner_document.createElement('TD');
		tr.appendChild(td);
		tbody.appendChild(tr);
		_iframe_wrapper.appendChild(tbody);
		Util.Element.add_class(_iframe_wrapper, 'iframe_wrapper');

		_iframe = _owner_document.createElement('IFRAME');
		_iframe.src = _settings.base_uri + 'auxil/loki_blank.html';
		_iframe.frameBorder = '0'; // otherwise, IE puts an extra border around the iframe that css cannot erase

		td.appendChild(_iframe);

		// Take styles from textarea
		var h = _textarea.clientHeight;
		//_set_height(h);
		// We also need to try again in a second, because in some 
		// versions of FF (e.g. 1.0.6 on win, and some on mac), 
		// the above doesn't work
		setTimeout( function () { _set_height(h); }, 1000 );
		//_set_width(_textarea.clientWidth); // XXX you should check here whether it's width = 100% (or another percentage), then actually copy that; otherwise you can base the new width on clientWidth as here.
	};

	/**
	 * Creates the statusbar
	 */
	var _create_statusbar = function()
	{
		_statusbar = _owner_document.createElement('DIV');
		Util.Element.add_class(_statusbar, 'statusbar');
	};

	/**
	 * Creates the grippy
	 */
	var _create_grippy = function()
	{
		// Actually create the elem
		_grippy_wrapper = _owner_document.createElement('DIV');
		Util.Element.add_class(_grippy_wrapper, 'grippy_wrapper');
		_grippy = _owner_document.createElement('IMG');
		_grippy.src = _settings.base_uri + 'images/grippy.gif';
		Util.Element.add_class(_grippy, 'grippy');
		_grippy_wrapper.appendChild(_grippy);
		//_grippy.innerHTML = 'grippy';
	};

	/**
	 * Adds listeners to make the grippy actually resize the document.
	 */
	var _add_grippy_listeners = function()
	{
		var orig_coords;
		Util.Event.add_event_listener(_grippy, 'mousedown', start_resize);

		// The point of this resize mask is to catch the mouseups with _owner_document,
		// not the iframe's _document, because the coordinates returned when the mouseup is in
		// the iframe's _document, the returned coordinates are buggy in Gecko. If we figure out
		// how to calculate those coordinates accurately--I'm pretty sure it is possible, just
		// tricky--we could remove this resize_mask code.
		var resize_mask = _owner_document.createElement('DIV');
		resize_mask.setAttribute('style', 'position: absolute; top: 0px; left: 0px; height: 20000px; width: 20000px; background: transparent; z-index: 10000;');

		function start_resize(event)
		{
			event = event == null ? window.event : event;
			orig_coords = prev_coords = determine_coords(event);
			Util.Event.add_event_listener(_owner_document, 'mousemove', resize);
			Util.Event.add_event_listener(_owner_document, 'mouseup', stop_resize);
			Util.Event.add_event_listener(_document, 'mousemove', resize);
			Util.Event.add_event_listener(_document, 'mouseup', stop_resize);

			if ( !document.all ) // XXX bad
				_owner_document.documentElement.appendChild(resize_mask);

			return Util.Event.prevent_default(event);
		}
		function resize(event)
		{
			event = event == null ? window.event : event;
			return Util.Event.prevent_default(event);
		}
		function stop_resize(event)
		{
			event = event == null ? window.event : event;

			if ( !document.all ) // XXX bad
				_owner_document.documentElement.removeChild(resize_mask);

			var coords = determine_coords(event);
			//_iframe_wrapper.style.height = _iframe_wrapper.clientHeight + ( coords.y - orig_coords.y ) + 'px';
			_set_height(_get_height() + (coords.y - orig_coords.y));

			Util.Event.remove_event_listener(_owner_document, 'mousemove', resize);
			Util.Event.remove_event_listener(_owner_document, 'mouseup', stop_resize);
			Util.Event.remove_event_listener(_document, 'mousemove', resize);
			Util.Event.remove_event_listener(_document, 'mouseup', stop_resize);
			orig_coords = null;

			return Util.Event.prevent_default(event);
		}
		function determine_coords(event)
		{
			//// Modified from the _show_contextmenu function below.
			//// XXX: Maybe combine this code with that slightly different
			//// code into a fxn in Util.Event, if it's not too difficult.
			//
			// Determine coordinates
			// (Code modified from TinyMCE.)
			var x, y;
			if ( event.pageX != null ) // Gecko
			{
				// If the event is fired from within the iframe,
				// add iframe's position to the reported position.
				var pos;
				var target = Util.Event.get_target(event);
				if ( target.ownerDocument == _document )
					pos = Util.Element.get_position(_iframe);
				else
					pos = { x : 0, y : 0 };

				var body = _owner_document.body;
				/// works, sort of:
				//x = pos.x + (event.clientX - body.scrollLeft);
				//y = pos.y + (event.clientY - body.scrollTop);
				x = pos.x + event.pageX;
				y = pos.y + event.pageY;
			}
			else // IE
			{
				/// works, sort of:
				x = event.screenX + 2;
				y = event.screenY + 2;
				////x = event.clientX + body.scrollLeft.
				////x = event.clientY + body.scrollTop;
			}
			return { x : x, y : y };
		}
	};

	/**
	 * This sets the height of both the possibly editable areas, whether
	 * the textarea or iframe.
	 */
	var _set_height = function(new_height)
	{
		if ( new_height > 40 )
			_iframe_wrapper.style.height = _textarea.style.height = new_height + 'px';
	};

	/**
	 * This gets the height of the actually editable area, whether
	 * the textarea or iframe (their heights should always be the same,
	 * but whichever is not currently in the document hierarchy will have
	 * its height reported incorrectly).
	 */
	var _get_height = function()
	{
		if ( _is_textarea_active() )
		{
			return _textarea.clientHeight;
		}
		else
		{
			return _iframe_wrapper.clientHeight;
		}
	};

	/**
	 * This sets the width of both the possibly editable areas, whether
	 * the textarea or iframe.
	 */
	var _set_width = function(new_width)
	{
		if ( new_width > 40 )
		{
			_iframe_wrapper.style.width = _textarea.style.width = new_width + 'px';
			_root.style.width = new_width + 2 + 'px'; // XXX what this number should be changes depending on style sheet..
		}
	};

	/**
	 * Creates the hidden element for Loki, and sets the hidden
	 * element's name, id, and value to those of the textarea element.
	 */
	var _create_hidden = function()
	{
		_hidden = _owner_document.createElement('INPUT');
		_hidden.setAttribute('type', 'hidden');

		if ( _textarea.getAttribute('id') )
			_hidden.setAttribute('id', _textarea.getAttribute('id'));

		if ( _textarea.getAttribute('name') )
			_hidden.setAttribute('name', _textarea.getAttribute('name'));

		if ( _textarea.getAttribute('value') )
			_hidden.setAttribute('value', _textarea.getAttribute('value'));
	};

	/**
	 * Replaces the textarea with the root.
	 */
	var _replace_textarea = function()
	{
		_textarea.parentNode.replaceChild(_root, _textarea);
	};

	/**
	 * Append style sheets to format the main Loki box (not for
	 * dialogs etc.) to owner_document's head.
	 */
	var _append_owner_document_style_sheets = function()
	{
		Util.Document.append_style_sheet(_owner_document, _settings.base_uri + 'css/Loki.css');
	};

	/**
	 * Append style sheets to format the innards of the loki iframe
	 */
	var _append_document_style_sheets = function()
	{
		Util.Document.append_style_sheet(_document, _settings.base_uri + 'css/cssSelector.css');
		if ( !document.all ) // XXX bad
			Util.Document.append_style_sheet(_document, _settings.base_uri + 'css/cssSelector_gecko.css');
		Util.Document.append_style_sheet(_document, '/global_stock/css/modules.css'); //this should perhaps be more generalized
		Util.Document.append_style_sheet(_document, '/global_stock/css/default_styles.css'); //this should perhaps be more generalized
// 		Util.Document.append_style_sheet(_document, '/global_stock/css/minisites_styles.css'); //this should perhaps be more generalized
		Util.Document.append_style_sheet(_document, _settings.base_uri + 'css/Loki_Document.css');
	};
	
	/**
	 * Write out blank document. The key here is that we *close*
	 * the document. That way, we don't have to wait for any more
	 * load events, dealing with which is exceedingly annoying due
	 * to cross-browser issues. Cf note in Util.Window.open.
	 */
	var _clear_document = function()
	{
		_document.open();
		_document.write(
			'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' +
			'<html><head><title></title></head><body>' +
			'</body></html>'
		);
		_document.close();
	};

	/**
	 * Make the document editable. Mozilla doesn't support
	 * contentEditable. Both IE and Mozilla support
	 * designMode. However, in IE if designMode is set on an iframe's
	 * contentDocument, the iframe's ownerDocument will be denied
	 * permission to access it (even if otherwise it *would* have
	 * permission). So for IE we use contentEditable, and for Mozilla
	 * designMode.
	 */
	var _make_document_editable = function()
	{
		// IE way
		try
		{
			_body.contentEditable = true;
			// If the document isn't editable, this will throw an
			// error. If the document is editable, this is perfectly
			// harmless.
			_query_command_state('Bold');
		}
		// Mozilla way
		catch(e)
		{
			try
			{
				// Turn on design mode.  N.B.: designMode has to be
				// set after the iframe_elem's src is set (or its
				// document is closed). ... Otherwise the designMode
				// attribute will be reset to "off", and things like
				// execCommand won't work (though, due to Mozilla bug
				// #198155, the iframe's new document will be
				// editable)
				_document.designMode = 'on';
				_document.execCommand('undo', false, null);
				//_query_command_state('Bold');
			}
			catch(f)
			{
				throw(new Error('UI.Loki._init_editor_iframe: Neither the IE nor the Mozilla way of starting the editor worked.'+
								'When the IE way was tried, the following error was thrown: <<' + e.message + '>>. ' +
								'When the Mozilla way was tried, the following error was thrown: <<' + f.message + '>>.'));
			}
		}

		// Tell Mozilla to use CSS.  Wrap in try block because IE
		// doesn't have a useCSS command, nor do some older versions
		// of Mozilla (even ones that support designMode),
		// e.g. Gecko/20030312 Mozilla 1.3 OS X
		try {
			_document.execCommand('useCSS', false, true);
		} catch (e) {}
	};

	/**
	 * Add masseuses. The purpose of a masseuse is to replace elements 
	 * inconvenient to edit with fake elements that are convenient 
	 * to edit, and vice versa. 
	 *
	 * This is the wrong place to add code designed to clean up bad 
	 * HTML; that should be done in UI.Clean.
	 */
	var _add_masseuses = function()
	{
		function add_masseuse(masseuse_class)
		{
			var masseuse = new masseuse_class;
			masseuse.init(self);
			_masseuses.push(masseuse);
		};

		// innerHTML masseuses must go first, ...
		if ( _settings.options.test('table') ) add_masseuse(UI.Table_Masseuse);
		// ... before any add_event_listener masseuses
		if ( _settings.options.test('anchor') ) add_masseuse(UI.Anchor_Masseuse);
		if ( _settings.options.test('olist') || _settings.options.test('ulist') ) add_masseuse(UI.UL_OL_Masseuse);
		if ( _settings.options.test('image') ) add_masseuse(UI.Image_Masseuse);
		if ( _settings.options.test('hrule') ) add_masseuse(UI.HR_Masseuse);
		add_masseuse(UI.Italic_Masseuse);
		add_masseuse(UI.Bold_Masseuse);
	};

	/**
	 * Run the massage_node_descendants methods of the masseuses 
	 * added in _add_masseuses on _body.
	 */
	var _massage_body = function()
	{
		_massage_node_descendants(_body);
	};

	/**
	 * Run the unmassage_node_descendants methods of the masseuses 
	 * added in _add_masseuses on _body.
	 */
	var _unmassage_body = function()
	{
		_unmassage_node_descendants(_body);
	};

	/**
	 * Run the massage_node_descendants methods of the masseuses 
	 * added in _add_masseuses.
	 */
	var _massage_node_descendants = this.massage_node_descendants = function(node)
	{
		for ( var i = 0; i < _masseuses.length; i++ )
		{
			_masseuses[i].massage_node_descendants(node);
		}
	};

	/**
	 * Run the unmassage_node_descendants methods of the masseuses 
	 * added in _add_masseuses.
	 */
	var _unmassage_node_descendants = this.unmassage_node_descendants = function(node)
	{
		for ( var i = 0; i < _masseuses.length; i++ )
		{
			_masseuses[i].unmassage_node_descendants(node);
		}
	};

	/**
	 * Add certain event listeners to the document, e.g. to listen to
	 * key strokes, mouse clicks, and so on.
	 */
	var _add_document_listeners = function()
	{
		// added NF 10/14 for TinyMCE
		var control = new TinyMCEControl();
		control.init(_window, _iframe, self);
		var tinyMCE = new TinyMCE();
		tinyMCE.init(_window, control);

		var paragraph_helper = (new UI.Paragraph_Helper).init(self);
		Util.Event.add_event_listener(_document, 'keypress', function(event)
		{
			event = event == null ? _window.event : event;
			paragraph_helper.possibly_paragraphify();
		});

		Util.Event.add_event_listener(_document, 'keypress', function(event)
		{
			event = event == null ? _window.event : event;
			if ( !_document.all ) // XXX bad
			{
				Util.Fix_Keys.fix_delete_and_backspace(event, _window);
				//Util.Fix_Keys.fix_enter(event, _window);
				tinyMCE.handleEvent(event);
			}
		});

		// XXX make this a keybinding instead?
		Util.Event.add_event_listener(_document, 'keypress', function(event)
		{
			event = event == null ? _window.event : event;
			if ( _document.all ) // XXX bad
			{
				return Util.Fix_Keys.fix_enter_ie(event, _window, self);
			}
		});

		Util.Event.add_event_listener(_document, 'keydown', function(event)
		{
			return;
			/*
			event = event == null ? _window.event : event;
			//Util.Fix_Keys.fix_enter_keydown(event, _window);
			Util.Fix_Keys.tinymce_fix_keyupdown(event, _window);

			// This does fix the display-spacing bug,--but breaks 
			// the motion keys 
			//_body.style.display = 'none';
			//_body.style.display = 'block';
			*/
		});

		/* 2006-07-11 commented for testing: not at all sure it should be commented.
		(Nothing seems to have changed after commenting, so I will leave commented.
		Util.Event.add_event_listener(_document, 'keyup', function(event) 
		{
			event = event == null ? _window.event : event;
			//Util.Fix_Keys.fix_enter_keyup(event, _window);
			Util.Fix_Keys.tinymce_fix_keyupdown(event, _window);
		});
		*/

		Util.Event.add_event_listener(_document, 'contextmenu', function(event) 
		{
			return _show_contextmenu(event || _window.event);
		});

		// XXX: perhaps you should put these two in classes similar to UI.Keybinding
		if ( _settings.options.test('link') )
		{
			var link_helper = (new UI.Link_Helper).init(self);
			Util.Event.add_event_listener(_body, 'dblclick', function(event)
			{
				if ( link_helper.is_selected() )
					link_helper.open_page_link_dialog();
			});
		}

		if ( _settings.options.test('anchor') )
		{
			var anchor_helper = (new UI.Anchor_Helper).init(self);
			Util.Event.add_event_listener(_body, 'dblclick', function(event)
			{
				if ( anchor_helper.is_selected() )
					anchor_helper.open_dialog();
			});
		}

		if ( _settings.options.test('image') )
		{
			var image_helper = (new UI.Image_Helper).init(self);
			Util.Event.add_event_listener(_body, 'dblclick', function(event)
			{
				if ( image_helper.is_selected() )
					image_helper.open_dialog();
			});
		}


		if ( _settings.options.test('statusbar') )
		{
			Util.Event.add_event_listener(_document, 'keyup', function() { _update_statusbar(); });
			Util.Event.add_event_listener(_document, 'click', function() { _update_statusbar(); });
			Util.Event.add_event_listener(_toolbar, 'click', function() { _update_statusbar(); });
		}
		
		function submit_handler(ev)
		{
			try {
				self.copy_iframe_to_hidden();
			} catch (ex) {
				alert("Loki encountered an error and was unable to translate " +
					"your document into normal HTML.\n\n" + ex);
				Util.Event.prevent_default(ev);
				return false;
			}
			
			return true;
		}

		// this copies the changes made in the iframe back to the hidden form element
		Util.Event.add_event_listener(_hidden.form, 'submit',
			Util.Event.listener(submit_handler));
	};

	/**
	 * Add listeners to all events which might change the state of the
	 * window (e.g., change where the current selection is in the
	 * document tree). This is useful for updating the toolbar
	 * (updating which buttons appear depressed) and the statusbar.
	 *
	 * The listeners added are stored in _state_change_listeners. We
	 * store them there and then add them all at once at the end of
	 * initialization (when this function should be called) instead of
	 * just adding them when we need them because it is convenient to
	 * add some of the listeners before _document actually points at
	 * some non-null thing.
	 *
	 * I do not like the name "state_change", but couldn't come up
	 * with anything better.
	 */
	var _add_state_change_listeners = function()
	{
		// I commented this out because it makes Loki really slow
		/*
		for ( var i = 0; i < _state_change_listeners.length; i++ )
		{
			Util.Event.add_event_listener(_document, 'keyup', function() { _state_change_listeners[i]; });
			Util.Event.add_event_listener(_document, 'click', function() { _state_change_listeners[i]; });
			Util.Event.add_event_listener(_toolbar, 'click', function() { _state_change_listeners[i]; });
		}
		*/
	};

	/**
	 * Update the statusbar with our current place in the document tree.
	 */
	var _update_statusbar = function()
	{
		var sel = Util.Selection.get_selection(_window);
		var rng = Util.Range.create_range(sel);
		var cur_node = Util.Range.get_common_ancestor(rng);
		var status = '';
		var i = 0;
		
		do
		{
			if ( i > 0 )
				status = ' > ' + status;

			if ( cur_node.nodeType == Util.Node.TEXT_NODE )
				status = '[TEXT]' + status;
			else if ( cur_node.nodeType == Util.Node.ELEMENT_NODE )
				status = cur_node.tagName + status;

			cur_node = cur_node.parentNode;
			i++;
		}
		while ( cur_node != null &&
				( cur_node.nodeType != Util.Node.ELEMENT_NODE ||
				  cur_node.tagName != 'HTML' ) )

		_statusbar.innerHTML = status;
	};

	var _add_keybindings = function()
	{
		function add_keybinding(keybinding_class)
		{
			var keybinding = (new keybinding_class).init(self);
			_keybindings.push(keybinding);
		};

		// return value indicates whether to continue bubbling of event or not
		function fire_keybindings(event)
		{
			for ( var i = 0; i < _keybindings.length; i++ )
			{
				if ( _keybindings[i].test(event) )
				{
					// return the value of action, so the keybinding can
					// choose not to cancel the browser's default event handler
					var ret_value = _keybindings[i].action();
					if ( ret_value === true || ret_value === false )
						return ret_value;
					else
						return false; // don't bubble
				}
			}
			return true; // bubble
		};

		if ( _settings.options.test('strong') ) add_keybinding(UI.Bold_Keybinding); // Ctrl-B
		if ( _settings.options.test('em') ) add_keybinding(UI.Italic_Keybinding); // Ctrl-I
		if ( _settings.options.test('underline') ) add_keybinding(UI.Underline_Keybinding); // Ctrl-U
		if ( _settings.options.test('clipboard') ) add_keybinding(UI.Cut_Keybinding); // Ctrl-X
		if ( _settings.options.test('clipboard') ) add_keybinding(UI.Copy_Keybinding); // Ctrl-C
		if ( _settings.options.test('clipboard') ) add_keybinding(UI.Paste_Keybinding); // Ctrl-V
		if ( _settings.options.test('align') ) add_keybinding(UI.Left_Align_Keybinding); // Ctrl-L
		if ( _settings.options.test('align') ) add_keybinding(UI.Center_Align_Keybinding); // Ctrl-E
		if ( _settings.options.test('align') ) add_keybinding(UI.Right_Align_Keybinding); // Ctrl-R
		if ( _settings.options.test('findtext') ) add_keybinding(UI.Find_Keybinding); // Ctrl-F (H?)
		if ( _settings.options.test('link') ) add_keybinding(UI.Page_Link_Keybinding); // Ctrl-K
		//if ( _settings.options.test('source') ) add_keybinding(UI.Source_Keybinding);
		if ( _settings.options.test('spell') ) add_keybinding(UI.Spell_Keybinding); // F7
		add_keybinding(UI.Delete_Element_Keybinding); // Delete image, anchor, HR, or table when selected
		add_keybinding(UI.Tab_Keybinding); // Tab

		// We need to listen for different key events for IE and Gecko,
		// because their default actions are on different events.
		if ( document.all ) // IE // XXX: hack
		{
			Util.Event.add_event_listener(_document, 'keydown', function(event) 
			{ 
				event = event == null ? _window.event : event;
				var bubble = fire_keybindings(event);
				if ( !bubble )
				{
					event.cancleBubble = true;
					return Util.Event.prevent_default(event);
				}
			});
		}
		else // Gecko
		{
			Util.Event.add_event_listener(_document, 'keypress', function(event) 
			{ 
				var bubble = fire_keybindings(event);
				if ( !bubble )
				{
					return Util.Event.prevent_default(event);
				}
			});
		}
	};

	var _init_menugroups = function()
	{
		function add_menugroup(menugroup_class)
		{
			var menugroup = (new menugroup_class).init(self);
			_menugroups.push(menugroup);
		};

		if ( _settings.options.test('headline') ) add_menugroup(UI.Headline_Menugroup);
		if ( _settings.options.test('image') ) add_menugroup(UI.Image_Menugroup);
		if ( _settings.options.test('anchor') ) add_menugroup(UI.Anchor_Menugroup);
		if ( _settings.options.test('link') ) add_menugroup(UI.Link_Menugroup);
		if ( _settings.options.test('table') ) add_menugroup(UI.Table_Menugroup);
		if ( _settings.options.test('align') ) add_menugroup(UI.Align_Menugroup);
		// This doesn't work properly right now:
		//if ( _settings.options.test('highlight') ) add_menugroup(UI.Highlight_Menugroup);
		if ( _settings.options.test('clipboard') ) add_menugroup(UI.Clipboard_Menugroup);
	};

	/**
	 * Shows a context menu.
	 */
	var _show_contextmenu = function(event)
	{
		var menu = (new UI.Menu).init(self);

		// Get appropriate menuitems
		for ( var i = 0; i < _menugroups.length; i++ )
		{
			var menuitems = _menugroups[i].get_contextual_menuitems();
			menu.add_menuitems(menuitems);
		}

		// Determine coordinates
		// (Code modified from TinyMCE.)
		var x, y;
		if ( event.pageX != null ) // Gecko
		{
			var pos = Util.Element.get_position(_iframe);
			x = pos.x + (event.clientX - _body.scrollLeft);
			y = pos.y + (event.clientY - _body.scrollTop);
		}
		else // IE
		{
			x = event.screenX + 2;
			y = event.screenY + 2;
		}

		menu.display(x, y);

		Util.Event.prevent_default(event);
		return false; // IE
	};

	/**
	 * Runs execCommand on _document. The motivation for this wrapper
	 * is to avoid issues when execCommand is used in event listeners.
	 * (If _document isn't yet initialized when "function() {
	 * _document.execCommand(xxx) }" is added as an event listener, an
	 * error results, because (in addition to its arguments) the
	 * listener when executed has access only to those variables which
	 * it had access to when it was defined.
	 *
	 * Also consult <a href="http://www.mozilla.org/editor/midas-spec.html">Mozilla's</a>
	 * and <a href="http://msdn.microsoft.com/workshop/author/dhtml/reference/methods/execcommand.asp">IE's</a>
	 * documentation.
	 *
	 * @param	command		the command to execute
	 * @param	iface		boolean indicating whether to use an interface. Not
	 *                      supported by Mozilla, so always provide false.
	 * @param	value		the value to pass the command
	 */
	var _exec_command = function(command, iface, value)
	{
		_window.focus();
		_document.execCommand(command, iface, value);
		_window.focus();
	};

	/**
	 * Returns the value of _document.queryCommandValue (see the
	 * links on execCommands doc for more info). But first modifies
	 * the return value so that IE's is the same as Mozilla's. (On
	 * this see <a href="http://www.mozilla.org/editor/ie2midas.html">here</a>, 
	 * bullet 8.)
	 *
	 * See also on _exec_command.
	 *
	 * @param	command		the command whose value to query (this only works for 
	 *                      some of the commands)
	 * @return				the (possibly-modified) return value of queryCommandValue
	 */
	var _query_command_value = function(command)
	{
		// Not sure if the window.focus is actually helpful here ...
		// and it makes annoying things happen like dialogs popping up
		// behind the editor's containing window.
		//_window.focus();
		var value = _document.queryCommandValue(command);

		if ( command == 'FormatBlock' )
		{
			var mappings = 
			{
				// IE : Mozilla
				'Normal' : 'p',
				'Formatted' : 'pre',
				'Heading 1' : 'h1',
				'Heading 2' : 'h2',
				'Heading 3' : 'h3',
				'Heading 4' : 'h4',
				'Heading 5' : 'h5',
				'Heading 6' : 'h6',
				'Preformatted' : 'pre',
				'Address' : 'address'
			};
			if ( mappings[value] != null )
				value = mappings[value];
		}

		return value;
	}

	/**
	 * See on _exec_command.
	 */
	var _query_command_state = function(command)
	{
		// Not sure if the window.focus is actually helpful here ...
		// and it makes annoying things happen like dialogs popping up
		// behind the editor's containing window.
		//_window.focus();
		return _document.queryCommandState(command);
	}

	/**
	 * Formats a block as specified if it's not so, and if it is so,
	 * formats it as a normal paragraph.
	 *
	 * @param   tag     the tag name corresponding to how you want
     *                  the block to be formatted. See <code>mappings</code>
     *                  variable inside the function.
     *
	 */
	this.toggle_block = function(tag)
	{
		if ( _query_command_value('FormatBlock') != tag )
		{
			_exec_command('FormatBlock', false, '<' + tag + '>');
		}
		else
		{
			_exec_command('FormatBlock', false, '<p>');
		}

		_window.focus();
	};

	/**
	 * Formats a block as a list of the given type if it's not so, and
	 * if it is so, formats it as a normal paragraph. This is
	 * necessary because in Mozilla, if a block is already formatted
	 * as a list, the Insert[Un]orderedList commands simply remove the
	 * block's block-level formatting, rather than changing it to a
	 * paragraph.
	 *
     * @param   tag     the tag name corresponding to how you want
     *                  the block to be formatted. See mappings variable 
     *                  inside the function
     */
	this.toggle_list = function(tag)
	{
		var command = tag == 'ol' ? 'InsertOrderedList' : 'InsertUnorderedList';

		if ( _query_command_state(command) )
		{
			_exec_command(command); // turn off the list
			this.toggle_block('p');
		}
		else
		{
			_exec_command(command); // turn on the list
		}
	};
};
