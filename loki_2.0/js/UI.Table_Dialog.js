/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A table dialog window..
 */
UI.Table_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);

	this._dialog_window_width = 615;
	this._dialog_window_width = 585;

	this._bgs = ['bgFFFFCC', 'bgFFFF99', 'bg99CCFF', 'bgCCCCCC', 'bgE8E8E8'];
	this._bg_radios = new Array();
	//this._desc_blank = '(Write your summary here.)';
	this._desc_blank = '';

	this._set_title = function()
	{
		if ( this._initially_selected_item.is_new )
			this._dialog_window.document.title = 'Make a table';
		else
			this._dialog_window.document.title = 'Table properties';
	};

	this._append_style_sheets = function()
	{
		this.superclass._append_style_sheets.call(this);
		//Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/cssSelector.css');
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Table_Dialog.css');
	};

	this._populate_main = function()
	{
		this._append_table_properties();
		this._append_table_color_properties();
		this._append_remove_table_button();
		this.superclass._populate_main.call(this);
	};

	/**
	 * Appends a chunk containing table properties.
	 */
	this._append_table_properties = function()
	{
		// Create function to check for digit
		var self = this;
		var is_digit = function(event) 
		{
			// Gecko uses keyCode for alphanumeric codes, charCode for special codes.
			// IE uses charCode for alphanumeric codes, and doesn`t use keyCode at all.
			event = event == null ? self._dialog_window.window.event : event;
			var char_code = event.charCode == null ? event.keyCode : event.charCode;
			// In Gecko, char_code (== event.keyCode) will be 0 if a special key has been pressed.
			return char_code == 0 || ( char_code >= 48 && char_code <=57 ); // is digit
		};

		// Create generic label element
		var generic_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(generic_label, 'label');

		// Create rows input
		this._rows_input = this._dialog_window.document.createElement('INPUT');
		this._rows_input.size = 3;
		this._rows_input.maxlength = 2;
		this._rows_input.id = 'rows_input';
		this._rows_input.onkeypress = is_digit;
		this._rows_input.value = this._initially_selected_item.rows == null ? 0 : this._initially_selected_item.rows;

		var self = this;
		if ( this._initially_selected_item.is_new == false )
		{
			Util.Event.add_event_listener(this._rows_input, 'change', function()
			{
				if ( self._rows_input.value < self._initially_selected_item.rows )
				{
					self._dialog_window.window.alert('Sorry, you cannot decrease the number of rows here--otherwise, you might accidentally delete data. \n\nIf you really want to remove a row, right click in it and select "Delete row".');
					self._rows_input.value = self._initially_selected_item.rows;
					self._rows_input.focus();
				}
			});
		}
		else 
		{
			Util.Event.add_event_listener(this._rows_input, 'change', function()
			{
				if ( self._rows_input.value < 2 )
				{
					self._dialog_window.window.alert('Sorry, at least two rows are required.');
					self._rows_input.value = 2;
					self._rows_input.focus();
				}
			});
		}

		// Create rows label
		var rows_label = generic_label.cloneNode(false);
		rows_label.appendChild( this._dialog_window.document.createTextNode('Rows: ') );
		rows_label.htmlFor = 'rows_input';

		// Create cols input
		this._cols_input = this._rows_input.cloneNode(false);
		this._cols_input.id = 'cols_input';
		this._cols_input.onkeypress = is_digit;
		this._cols_input.value = this._initially_selected_item.cols == null ? 0 : this._initially_selected_item.cols;

		var self = this;
		if ( this._initially_selected_item.is_new == false )
		{
			Util.Event.add_event_listener(this._cols_input, 'change', function()
			{
				if ( self._cols_input.value < self._initially_selected_item.cols )
				{
					self._dialog_window.window.alert('Sorry, you cannot decrease the number of columns here--otherwise, you might accidentally delete data. \n\nIf you really want to remove a column, right click in it and select "Delete column".');
					self._cols_input.value = self._initially_selected_item.cols;
					self._cols_input.focus();
				}
			});
		}
		else
		{
			Util.Event.add_event_listener(this._cols_input, 'change', function()
			{
				if ( self._cols_input.value < 2 )
				{
					self._dialog_window.window.alert('Sorry, at least two columns are required.');
					self._cols_input.value = 2;
					self._cols_input.focus();
				}
			});
		}

		// Create cols label
		var cols_label = generic_label.cloneNode(false);
		cols_label.appendChild( this._dialog_window.document.createTextNode('  Columns: ') );
		cols_label.htmlFor = 'cols_input';

		// Create rows and cols div
		var rows_and_cols_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(rows_and_cols_div, 'field');
		rows_and_cols_div.appendChild(rows_label);
		rows_and_cols_div.appendChild(self._rows_input);
		rows_and_cols_div.appendChild(cols_label);
		rows_and_cols_div.appendChild(self._cols_input);

		// Create border input
		this._border_checkbox = this._dialog_window.document.createElement('INPUT');
		this._border_checkbox.type = 'checkbox';
		this._border_checkbox.id = 'border_checkbox';
		this._border_checkbox.checked = this._initially_selected_item.border == null ? false : this._initially_selected_item.border;

		// Create border label
		var border_label = generic_label.cloneNode(false);
		border_label.appendChild( this._dialog_window.document.createTextNode('Show border:') );
		border_label.htmlFor = 'border_checkbox';

		// Create border div
		var border_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(border_div, 'field');
		border_div.appendChild(border_label);
		border_div.appendChild(self._border_checkbox);

		// Create description textarea
		this._desc_textarea = this._dialog_window.document.createElement('TEXTAREA');
		this._desc_textarea.cols = 25;
		this._desc_textarea.rows = '5';
		this._desc_textarea.id = 'desc_textarea';
		this._desc_textarea.value = this._initially_selected_item.desc == null ? self._desc_blank : this._initially_selected_item.desc;
		/* // This would toggle desc_blank onfocus/blur
		var self = this;
		Util.Event.add_event_listener(this._desc_textarea, 'focus', function()
		{
			if ( self._desc_textarea.value == self._desc_blank )
				self._desc_textarea.value = '';
		});
		Util.Event.add_event_listener(this._desc_textarea, 'blur', function()
		{
			if ( self._desc_textarea.value == '' )
				self._desc_textarea.value = self._desc_blank;
		});
		*/

		// Create description label
		var desc_label = generic_label.cloneNode(false);
		desc_label.appendChild( this._dialog_window.document.createTextNode('Summarize the contents of this table:') );
		desc_label.htmlFor = 'desc_textarea';

		// Create description div
		var desc_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(desc_div, 'field');
		desc_div.appendChild(desc_label);
		desc_div.appendChild(this._dialog_window.document.createElement('BR'));
		desc_div.appendChild(self._desc_textarea);

		// Create heading
		var h1 = this._dialog_window.document.createElement('H1');
		if ( this._initially_selected_item.is_new )
			h1.innerHTML = 'Make a table';
		else
			h1.innerHTML = 'Table properties';

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : '', document : this._dialog_window.document});

		// Append all the above to fieldset
		fieldset.fieldset_elem.appendChild(rows_and_cols_div);
		fieldset.fieldset_elem.appendChild(desc_div);
		fieldset.fieldset_elem.appendChild(border_div);

		// Append fieldset chunk to dialog
		this._main_chunk.appendChild(h1);
		this._main_chunk.appendChild(fieldset.chunk);
	};

	/**
	 * Appends a chunk containing table color properties.
	 */
	this._append_table_color_properties = function()
	{
		//
		// We only show the bg section if the table being
		// edited already has a bg, and the user might want
		// to get rid of it. (for legacy)
		//
		if ( this._initially_selected_item.bg )
		{
			// Create generic elements
			var generic_bg_label = this._dialog_window.document.createElement('LABEL');
			Util.Element.add_class(generic_bg_label, 'bg_label');
			//generic_bg_label.appendChild( this._dialog_window.document.createTextNode(' ') );
			generic_bg_label.innerHTML = '&nbsp;';

			var generic_bg_radio = Util.Input.create_named_input({document : this._dialog_window.document, name : 'bg_radio'});
			generic_bg_radio.type = 'radio';

			// Create fieldset and its legend
			var fieldset = new Util.Fieldset({legend : 'Table color properties:', document : this._dialog_window.document});

			// Create the "remove bgcolor" radio and label
			this._no_bg_radio = generic_bg_radio.cloneNode(true);
			this._no_bg_radio.id = 'no_bg_radio';

			var no_bg_label = this._dialog_window.document.createElement('LABEL');
			no_bg_label.appendChild( this._dialog_window.document.createTextNode('Remove background color') );
			no_bg_label.htmlFor = 'no_bg_radio';
			Util.Element.add_class(no_bg_label, 'label');

			// Create the "keep bgcolor" radio and label
			this._keep_bg_radio = generic_bg_radio.cloneNode(true);
			this._keep_bg_radio.id = 'keep_bg_radio';
			this._keep_bg_radio.checked = true; // otherwise we wouldn't be showing any of this at all

			var keep_bg_label = this._dialog_window.document.createElement('LABEL');
			keep_bg_label.appendChild( this._dialog_window.document.createTextNode('Keep background color') );
			keep_bg_label.htmlFor = 'keep_bg_radio';
			Util.Element.add_class(keep_bg_label, 'label');

			// Append them
			fieldset.fieldset_elem.appendChild(this._no_bg_radio);
			fieldset.fieldset_elem.appendChild(no_bg_label);
			fieldset.fieldset_elem.appendChild(this._keep_bg_radio);
			fieldset.fieldset_elem.appendChild(keep_bg_label);

			// Append fieldset chunk to dialog
			this._main_chunk.appendChild(fieldset.chunk);
		}
		
		/* Uncomment if bgs are reinstated
		// Create generic elements
		var generic_bg_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(generic_bg_label, 'bg_label');
		//generic_bg_label.appendChild( this._dialog_window.document.createTextNode(' ') );
		generic_bg_label.innerHTML = '&nbsp;';

		var generic_bg_radio = Util.Input.create_named_input({document : this._dialog_window.document, name : 'bg_radio'});
		generic_bg_radio.type = 'radio';

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : 'Table color properties:', document : this._dialog_window.document});

		// Create and append the "no bgcolor" radio and label
		this._no_bg_radio = generic_bg_radio.cloneNode(true);
		this._no_bg_radio.id = 'no_bg_radio';

		var no_bg_label = this._dialog_window.document.createElement('LABEL');
		no_bg_label.appendChild( this._dialog_window.document.createTextNode('Use no background color') );
		no_bg_label.htmlFor = 'no_bg_radio';
		Util.Element.add_class(no_bg_label, 'label');

		fieldset.fieldset_elem.appendChild(this._no_bg_radio);
		fieldset.fieldset_elem.appendChild(no_bg_label);

		// Create and append the bgcolor radios and labels
		var bg_labels = new Array();
		for ( var i = 0; i < this._bgs.length; i++ )
		{
			bg_labels[i] = generic_bg_label.cloneNode(true);
			bg_labels[i].htmlFor = 'bg_' + this._bgs[i] + '_radio';
			Util.Element.add_class(bg_labels[i], this._bgs[i]);

			this._bg_radios[i] = generic_bg_radio.cloneNode(true);
			this._bg_radios[i].id = 'bg_' + this._bgs[i] + '_radio';

			fieldset.fieldset_elem.appendChild(this._bg_radios[i]);
			fieldset.fieldset_elem.appendChild(bg_labels[i]);
		}

		// Append fieldset chunk to dialog
		this._main_chunk.appendChild(fieldset.chunk);
		*/
	};

	/**
	 * Creates and appends a chunk containing a "remove table" button. 
	 * Also attaches 'click' event listeners to the button.
	 */
	this._append_remove_table_button = function()
	{
		var button = this._dialog_window.document.createElement('BUTTON');
		button.setAttribute('type', 'button');
		button.appendChild( this._dialog_window.document.createTextNode('Remove table') );

		var self = this;
		var listener = function()
		{
			if ( confirm('Really remove table? WARNING: This cannot be undone.') )
			{
				self._remove_listener();
				self._dialog_window.window.close();
			}
		}
		Util.Event.add_event_listener(button, 'click', listener);

		// Setup their containing chunk
		var chunk = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(chunk, 'remove_chunk');
		chunk.appendChild(button);

		// Append the containing chunk
		this._dialog_window.body.appendChild(chunk);
	};

	this._apply_initially_selected_item = function()
	{
		// Apply background
		// (we have to set checked after all the radios are added;
		// otherwise, IE will uncheck what we check.)
		/* Uncomment if bgs are reinstated
		this._no_bg_radio.checked = true;
		for ( var i = 0; i < this._bgs.length; i++ )
		{
			if ( this._bgs[i] == this._initially_selected_item.bg )
			{
				this._bg_radios[i].checked = true;
			}
		}
		*/
	};

	this._internal_submit_listener = function()
	{
		// Determine rows
		if ( this._rows_input.value == '' )
		{
			this._dialog_window.window.alert('Please specify a number of rows.');
			this._rows_input.focus();
			return false;
		}
		var rows = this._rows_input.value;

		// Determine cols
		if ( this._cols_input.value == '' )
		{
			this._dialog_window.window.alert('Please specify a number of columns.');
			this._cols_input.focus();
			return false;
		}
		var cols = this._cols_input.value;
			
		// Determine border
		var border = this._border_checkbox.checked ? true : false;
		
		// Determine description
		if ( this._desc_textarea.value == this._desc_blank || this._desc_textarea.value == '' )
		{
			this._dialog_window.window.alert('Please provide a brief summary of the data in the table.');
			this._desc_textarea.focus();
			return false;
		}
		var desc = this._desc_textarea.value;
		
		// Determine whether the user wants to keep 
		// the background (for legacy)
		var bg = false;
		if ( this._keep_bg_radio != null )
			bg = this._keep_bg_radio.checked;
		/* Uncomment if bgs are reinstated
		// Determine background
		var bg = '';
		for ( var i = 0; i < this._bgs.length; i++ )
		{
			if ( this._bg_radios[i].checked == true )
			{
				bg = this._bgs[i];
			}
		}
		*/

		// Call external event listener
		this._external_submit_listener({rows : rows, cols : cols, border : border, desc : desc, bg : bg});

		// Close dialog window
		this._dialog_window.window.close();
	};
};
