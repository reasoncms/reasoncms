/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class An image dialog window.
 */
UI.Image_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);

	this._dialog_window_width = 625;
	this._dialog_window_height = 600;
	this._tn_regexp =  new RegExp('_tn\.', ''); // matches the "thumbnail" portion
												// of a URI

	this.init = function(params)
	{
		// use rss integration only if data_source is given:
		this._use_rss = params.data_source ? true : false;
		this.superclass.init.call(this, params);
		return this;
	};

	this._set_title = function()
	{
		if ( !this._initially_selected_item )
			this._dialog_window.document.title = 'Insert image';
		else
			this._dialog_window.document.title = 'Edit image';
	};

	this._append_style_sheets = function()
	{
		this.superclass._append_style_sheets.call(this);
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Listbox.css');
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Image_Listbox.css');
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Tabset.css');
	};

	this._populate_main = function()
	{
		this._append_heading();
		this._append_tabset();
		if ( this._use_rss )
			this._append_image_listbox();
		this._append_image_custom();
		if ( this._use_rss )
			this._append_image_options_chunk('listbox');
		this._append_image_options_chunk('custom');
		this._append_remove_image_chunk();
		var self = this;
		setTimeout(function () { self._resize_dialog_window(false, true); }, 1000);
		this.superclass._populate_main.call(this);
	};

	this._append_heading = function()
	{
		var h1 = this._dialog_window.document.createElement('H1');
		if ( !this._initially_selected_item )
			h1.innerHTML = 'Insert:';
		else
			h1.innerHTML = 'Edit:';
		this._main_chunk.appendChild(h1);
	};

	this._append_tabset = function()
	{
		this._tabset = new Util.Tabset({document : this._dialog_window.document});		
		if ( this._use_rss )
			this._tabset.add_tab('listbox', 'existing image');
		this._tabset.add_tab('custom', 'image at web address');
		this._main_chunk.appendChild(this._tabset.tabset_elem);
	};

	this._append_image_listbox = function()
	{
		// Instantiate a listbox to display the images 
		this._image_listbox = new UI.Image_Listbox;
		this._image_listbox.init('image_listbox', this._dialog_window.document);

		// Append the listbox's root element. (Do
		// this here rather than later so that the listbox items are
		// displayed as they load.)
		var listbox_elem = this._image_listbox.get_listbox_elem();
		this._tabset.get_tabpanel_elem('listbox').appendChild(listbox_elem);

		// Setup test for initially selected item
		var self = this;
		var initially_selected_item_boolean_test = function(item)
		{
			if ( !item || !item.link || !self._initially_selected_item || !self._initially_selected_item.uri )
				return false;
			else
			{
				var item_uri = Util.URI.strip_https_and_http(item.link);
				var initial_uri = Util.URI.strip_https_and_http(self._initially_selected_item.uri);
				if ( item_uri.replace(self._tn_regexp, '.') ==
					   initial_uri.replace(self._tn_regexp, '.') )
				/*
				if ( item && item.link && 
					 self._initially_selected_item && self._initially_selected_item.uri &&
					 item.link.replace(self._tn_regexp, '.') ==
					   self._initially_selected_item.uri.replace(self._tn_regexp, '.') )
				*/
				{
					self._tabset.select_tab('listbox');
					return true;
				}
				else
					return false;
			}
		};

		// Append to the listbox items retrieved using an RSS feed
		var rss_buffered_reader = (new Util.RSS_Reader).init(this._data_source, 25);
		this._image_listbox.append_items_from_buffered_reader(rss_buffered_reader, initially_selected_item_boolean_test);
	};

	this._append_image_custom = function()
	{
		// Create widgets
		var custom_uri_label = this._doc.createElement('LABEL');
		custom_uri_label.appendChild(this._doc.createTextNode('Location: '));
		custom_uri_label.htmlFor = 'custom_uri_input';

		this._custom_uri_input = this._doc.createElement('INPUT');
		this._custom_uri_input.id = 'custom_uri_input';
		this._custom_uri_input.type = 'text';
		this._custom_uri_input.setAttribute('size', '40');

		var custom_uri_div = this._doc.createElement('DIV');
		custom_uri_div.appendChild(custom_uri_label);
		custom_uri_div.appendChild(this._custom_uri_input);

		var custom_alt_label = this._doc.createElement('LABEL');
		custom_alt_label.appendChild(this._doc.createTextNode('Description: '));
		custom_alt_label.htmlFor = 'custom_alt_input';

		this._custom_alt_input = this._doc.createElement('INPUT');
		this._custom_alt_input.id = 'custom_alt_input';
		this._custom_alt_input.type = 'text';
		this._custom_alt_input.setAttribute('size', '40');

		var custom_alt_label2 = this._doc.createElement('DIV');
		custom_alt_label2.appendChild(this._doc.createTextNode('This description will be used if the image cannot be displayed or the user is visually disabled.'));

		var custom_alt_div = this._doc.createElement('DIV');
		custom_alt_div.appendChild(custom_alt_label);
		custom_alt_div.appendChild(this._custom_alt_input);

		// Create table
		var table = this._doc.createElement('TABLE');
		var tbody = table.appendChild(this._doc.createElement('TBODY'));

		var tr = tbody.appendChild(this._doc.createElement('TR'));
		var td = tr.appendChild(this._doc.createElement('TD'))
		td.appendChild(custom_uri_label);
		var td = tr.appendChild(this._doc.createElement('TD'))
		td.appendChild(this._custom_uri_input);

		var tr = tbody.appendChild(this._doc.createElement('TR'));
		var td = tr.appendChild(this._doc.createElement('TD'))
		td.appendChild(custom_alt_label);
		var td = tr.appendChild(this._doc.createElement('TD'))
		td.appendChild(this._custom_alt_input);

		// Append it all
		var custom_tabpanel = this._tabset.get_tabpanel_elem('custom');
		var fieldset = new Util.Fieldset({legend : '', document : this._dialog_window.document});
		custom_tabpanel.appendChild(fieldset.fieldset_elem);
		fieldset.fieldset_elem.appendChild(table);

		// Init
		if ( !this._initially_selected_item || !this._initially_selected_item.uri ) 
		{
			this._custom_uri_input.value = 'http://';
		}
		else
		{
			this._tabset.select_tab('custom');
			this._custom_uri_input.value = this._initially_selected_item.uri;
			this._custom_alt_input.value = this._initially_selected_item.alt;
		}
	};

	/**
	 * Appends a chunk containing image options.
	 */
	this._append_image_options_chunk = function(tabname)
	{
		// Create fieldset
		var fieldset = new Util.Fieldset({legend : 'Image options', document : this._dialog_window.document});

		// Add to fieldset
		if ( tabname == 'listbox' )
			fieldset.fieldset_elem.appendChild(this._create_size_chunk(tabname));
		fieldset.fieldset_elem.appendChild(this._create_align_chunk(tabname));
		//this._append_border_chunk();

		// We need to add a dummy div styled clear:both so the CSS works
		// right
		var clearer = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(clearer, 'clearer');
		fieldset.fieldset_elem.appendChild(clearer);

		// Append it all
		this._tabset.get_tabpanel_elem(tabname).appendChild(fieldset.fieldset_elem);
	};
	

	/**
	 * Creates a chunk containing radio inputs asking whether to use a
	 * thumbnail or full-sized image.
	 */
	this._create_size_chunk = function(tabname)
	{
		// Check for initial value
		var is_full = ( this._initially_selected_item &&
						this._initially_selected_item.uri &&
						!this._initially_selected_item.uri.match(this._tn_regexp) );

		// Create radios
		this['_' + tabname + '_tn_size_radio'] = new Util.Radio({
			id : tabname + '_tn_size_radio', 
			tabname : tabname + '_size', 
			label : 'Thumbnail', 
			value : 'tn', 
			checked: !is_full, 
			document : this._dialog_window.document
		});
		this['_' + tabname + '_full_size_radio'] = new Util.Radio({
			id : tabname + '_full_size_radio', 
			tabname : tabname + '_size', 
			label : 'Full', 
			value : 'full',  
			checked: is_full, 
			document : this._dialog_window.document
		});

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : 'Size', document : this._dialog_window.document});

		// Append radios and labels to fieldset
		fieldset.fieldset_elem.appendChild(this['_' + tabname + '_tn_size_radio'].chunk);
		fieldset.fieldset_elem.appendChild(this['_' + tabname + '_full_size_radio'].chunk);

		// Return fieldset chunk
		return fieldset.chunk;
	};

	/**
	 * Creates a chunk containing image align options.
	 */
	this._create_align_chunk = function(tabname)
	{
		// Check for initial value
		if ( this._initially_selected_item &&
			 this._initially_selected_item.align )
		{
			var align_left = this._initially_selected_item.align == 'left';
			var align_right = this._initially_selected_item.align == 'right';
		}
		var align_none = !align_left && !align_right;

		// Create radios
		this['_' + tabname + '_align_none_radio'] = new Util.Radio({
			id : tabname + '_align_none_radio', 
			name : tabname + '_align', 
			label : 'None', 
			value : 'none', 
			checked : align_none, 
			document : this._dialog_window.document
		});
		this['_' + tabname + '_align_left_radio'] = new Util.Radio({
			id : tabname + '_align_left_radio', 
			name : tabname + '_align', 
			label : 'Left', 
			value : 'left', 
			checked : align_left, 
			document : this._dialog_window.document
		});
		this['_' + tabname + '_align_right_radio'] = new Util.Radio({
			id : tabname + '_align_right_radio', 
			name : tabname + '_align', 
			label : 'Right', 
			value : 'right', 
			checked : align_right, 
			document : this._dialog_window.document
		});

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : 'Alignment', document : this._dialog_window.document});

		// Append radios and labels to fieldset
		fieldset.fieldset_elem.appendChild(this['_' + tabname + '_align_none_radio'].chunk);
		fieldset.fieldset_elem.appendChild(this['_' + tabname + '_align_left_radio'].chunk);
		fieldset.fieldset_elem.appendChild(this['_' + tabname + '_align_right_radio'].chunk);

		// Return fieldset chunk
		return fieldset.chunk;
	};

	/**
	 * Appends a chunk containing image border options.
	 */
	this._append_border_chunk = function()
	{
		// Create radios
		this._border_yes_radio = new Util.Radio({id : 'border_yes_radio', name : 'border', label : 'Yes', value : 'yes', checked: true, document : this._dialog_window.document});
		this._border_no_radio = new Util.Radio({id : 'border_no_radio', name : 'border', label : 'No', value : 'no', document : this._dialog_window.document});

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : 'Border', document : this._dialog_window.document});

		// Append radios and labels to fieldset
		fieldset.fieldset_elem.appendChild(this._border_yes_radio.chunk);
		fieldset.fieldset_elem.appendChild(this._border_no_radio.chunk);

		// Append fieldset chunk to dialog
		this._image_options_chunk.appendChild(fieldset.chunk);
	};

	/**
	 * Creates and appends a chunk containing a "remove image" button. 
	 * Also attaches 'click' event listeners to the button.
	 */
	this._append_remove_image_chunk = function()
	{
		var button = this._dialog_window.document.createElement('BUTTON');
		button.setAttribute('type', 'button');
		button.appendChild( this._dialog_window.document.createTextNode('Remove image') );

		var self = this;
		var listener = function()
		{
			/* not really necessary for just an image
			if ( confirm('Really remove image? WARNING: This cannot be undone.') )
			{
			*/
				self._remove_listener();
				self._dialog_window.window.close();
			//}
		}
		Util.Event.add_event_listener(button, 'click', listener);

		// Setup their containing chunk
		var chunk = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(chunk, 'remove_chunk');
		chunk.appendChild(button);

		// Append the containing chunk
		this._dialog_window.body.appendChild(chunk);
	};

	/**
	 * Called as an event listener when the user clicks the submit
	 * button. 
	 */
	this._internal_submit_listener = function()
	{
		if ( this._tabset.get_name_of_selected_tab() == 'listbox' )
		{
			// Get selected item
			var img_item = this._image_listbox.get_selected_item();
			if ( img_item == null )
			{
				this._dialog_window.window.alert('Please select an image to insert');
				return false;
			}

			// Determine uri
			var uri = Util.URI.strip_https_and_http(img_item.link);
			if ( this._listbox_full_size_radio.input_elem.checked )
				uri = uri.replace( this._tn_regexp, '.' );

			// Determine alt text
			var alt = img_item.title;
		}
		else // if ( this._tabset.get_name_of_selected_tab() == 'custom' )
		{
			var uri = this._custom_uri_input.value;
			var alt = this._custom_alt_input.value;

			if ( uri == '' )
			{
				this._dialog_window.window.alert("Please enter the image's location.");
				return false;
			}
			if ( alt == '' )
			{
				this._dialog_window.window.alert("Please enter the image's description (alt text).");
				return false;
			}
		}

		// Determine align
		var tabname = this._tabset.get_name_of_selected_tab()
		var align;
		if ( this['_' + tabname + '_align_left_radio'].input_elem.checked )
			align = 'left';
		else if ( this['_' + tabname + '_align_right_radio'].input_elem.checked )
			align = 'right';
		else //if ( this['_' + tabname + '_align_none_radio'].input_elem.checked )
			align = '';

	/*
		// Determine border
		var border;
		if ( this._border_yes_radio.input_elem.checked )
			border = 'yes';
		else //if ( this._border_no_radio.input_elem.checked )
			border = 'no';
	*/

		// TODO: Determine height and width of image

		// Call external event listener
		this._external_submit_listener({uri : uri, alt : alt, align : align});

		// Close dialog window
		this._dialog_window.window.close();
	};
};
