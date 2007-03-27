/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A table dialog window..
 */
UI.Cell_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);

	this._dialog_window_width = 615;
	this._dialog_window_width = 585;

	this._bgs = ['bgFFFFCC', 'bgFFFF99', 'bg99CCFF', 'bgCCCCCC', 'bgE8E8E8'];
	this._bg_radios = new Array();

	this._set_title = function()
	{
		this._dialog_window.document.title =  "Table cell properties";
	};

	this._append_style_sheets = function()
	{
		this.superclass._append_style_sheets.call(this);
		//Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/cssSelector.css');
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Table_Dialog.css');
	};

	this._populate_main = function()
	{
		this._append_td_properties();
		//this._append_table_color_properties();
		this.superclass._populate_main.call(this);
	};

	/**
	 * Appends a chunk containing table properties.
	 */
	this._append_td_properties = function()
	{
		var self = this;

		// Create generic label element
		var generic_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(generic_label, 'label');

		// Align
		this._align_select = this._dialog_window.document.createElement('SELECT');
		this._align_select.setAttribute('id', 'align_select');
		
		var align_label = generic_label.cloneNode(false);
		align_label.appendChild( this._dialog_window.document.createTextNode('Alignment: ') );
		align_label.setAttribute('for', 'align_select');

		Util.Select.append_options(this._align_select, [{l : 'Left', v : 'left'}, {l : 'Center', v : 'center'}, {l : 'Right', v : 'right'}]);

		var align_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(align_div, 'field');
		align_div.appendChild(align_label);
		align_div.appendChild(this._align_select);

		// Valign
		this._valign_select = this._dialog_window.document.createElement('SELECT');
		this._valign_select.setAttribute('id', 'valign_select');
		
		var valign_label = generic_label.cloneNode(false);
		valign_label.appendChild( this._dialog_window.document.createTextNode('Vertical alignment: ') );
		valign_label.setAttribute('for', 'valign_select');

		Util.Select.append_options(this._valign_select, [{l : 'Top', v : 'top'}, {l : 'Middle', v : 'middle'}, {l : 'Bottom', v : 'bottom'}]);

		var valign_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(valign_div, 'field');
		valign_div.appendChild(valign_label);
		valign_div.appendChild(this._valign_select);

		// Wrap
		this._wrap_select = this._dialog_window.document.createElement('SELECT');
		this._wrap_select.setAttribute('id', 'wrap_select');

		var wrap_label = generic_label.cloneNode(false);
		wrap_label.appendChild( this._dialog_window.document.createTextNode('Wrap: ') );
		wrap_label.setAttribute('for', 'wrap_select');

		Util.Select.append_options(this._wrap_select, [{l : 'Yes', v : 'yes'}, {l : 'No', v : 'no'}]);

		var wrap_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(wrap_div, 'field');
		wrap_div.appendChild(wrap_label);
		wrap_div.appendChild(this._wrap_select);

		// Create heading
		var h1 = this._dialog_window.document.createElement('H1');
		h1.innerHTML = 'Table cell properties';

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : '', document : this._dialog_window.document});

		// Append all the above to fieldset
		fieldset.fieldset_elem.appendChild(align_div);
		fieldset.fieldset_elem.appendChild(valign_div);
		fieldset.fieldset_elem.appendChild(wrap_div);

		// Append fieldset chunk to dialog
		this._main_chunk.appendChild(h1);
		this._main_chunk.appendChild(fieldset.chunk);
	};

	/**
	 * Appends a chunk containing table color properties.
	 */
	this._append_table_color_properties = function()
	{
		// Create generic elements
		var generic_bg_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(generic_bg_label, 'bg_label');
		//generic_bg_label.appendChild( this._dialog_window.document.createTextNode(' ') );
		generic_bg_label.innerHTML = '&nbsp;';

		var generic_bg_radio = Util.Input.create_named_input({document : this._dialog_window.document, name : 'bg_radio'});
		generic_bg_radio.setAttribute('type', 'radio');

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : 'Cell color properties:', document : this._dialog_window.document});

		// Create and append the "no bgcolor" radio and label
		this._no_bg_radio = generic_bg_radio.cloneNode(true);
		this._no_bg_radio.setAttribute('id', 'no_bg_radio');

		var no_bg_label = this._dialog_window.document.createElement('LABEL');
		no_bg_label.appendChild( this._dialog_window.document.createTextNode('Use no background color') );
		no_bg_label.setAttribute('for', 'no_bg_radio');
		Util.Element.add_class(no_bg_label, 'label');

		fieldset.fieldset.appendChild(this._no_bg_radio);
		fieldset.fieldset.appendChild(no_bg_label);

		// Create and append the bgcolor radios and labels
		var bg_labels = new Array();
		for ( var i = 0; i < this._bgs.length; i++ )
		{
			bg_labels[i] = generic_bg_label.cloneNode(true);
			bg_labels[i].setAttribute('for', 'bg_' + this._bgs[i] + '_radio');
			Util.Element.add_class(bg_labels[i], this._bgs[i]);

			this._bg_radios[i] = generic_bg_radio.cloneNode(true);
			this._bg_radios[i].setAttribute('id', 'bg_' + this._bgs[i] + '_radio');

			fieldset.fieldset_elem.appendChild(this._bg_radios[i]);
			fieldset.fieldset_elem.appendChild(bg_labels[i]);
		}

		// Append fieldset chunk to dialog
		this._main_chunk.appendChild(fieldset.chunk);
	};

	/**
	 * Sets initial values.
	 */
	this._apply_initially_selected_item = function()
	{
		messagebox('UI.Cell_Dialog.apply_initially_selelcted_item: initially_selected_item.align', this._initially_selected_item.align);
		messagebox('UI.Cell_Dialog.apply_initially_selelcted_item: initially_selected_item.valign', this._initially_selected_item.valign);

		this._align_select.value = this._initially_selected_item.align == '' ? 'left' : this._initially_selected_item.align;
		this._valign_select.value = this._initially_selected_item.valign == '' ? 'top' : this._initially_selected_item.valign;
		this._wrap_select.value = this._initially_selected_item.wrap == '' ? 'yes' : this._initially_selected_item.wrap;
		
		messagebox('UI.Cell_Dialog.apply_initially_selelcted_item: this._align_select.value', this._align_select.value);
		messagebox('UI.Cell_Dialog.apply_initially_selelcted_item: this._valign_select.value', this._valign_select.value);

		/*
		// Apply background
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

	/**
	 * Called as an event listener when the user clicks the submit
	 * button. 
	 */
	this._internal_submit_listener = function()
	{
		var align = this._align_select.value;
		var valign = this._valign_select.value;
		var wrap = this._wrap_select.value;
		
		/*
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

		//this._external_submit_listener({align : align, valign : valign, wrap : wrap, bg : bg});
		this._external_submit_listener({align : align, valign : valign, wrap : wrap});
		this._dialog_window.window.close();
	};
};
