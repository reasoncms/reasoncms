/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class An anchor dialog window.
 */
UI.Anchor_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);

	this._dialog_window_width = 615;
	this._dialog_window_height = 200;

	this._set_title = function()
	{
		if ( !this._initially_selected_item )
			this._dialog_window.document.title = 'Insert anchor';
		else
			this._dialog_window.document.title = 'Edit anchor';
	};

	this._populate_main = function()
	{
		this._append_anchor_chunk();
		this._append_submit_and_cancel_chunk();
		this._append_remove_anchor_chunk();
		var self = this;
		setTimeout(function () { self._resize_dialog_window(false, true); }, 1000);
		//this._resize_dialog_window(false, true);
	};

	this._append_anchor_chunk = function()
	{
		this._anchor_input = this._dialog_window.document.createElement('INPUT');
		this._anchor_input.setAttribute('size', '40');
		this._anchor_input.id = 'anchor_input';

		var anchor_label = this._dialog_window.document.createElement('LABEL');
		anchor_label.innerHTML = 'Anchor name: ';
		anchor_label.htmlFor = 'anchor_input';

		var anchor_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(anchor_div, 'field');
		anchor_div.appendChild(anchor_label);
		anchor_div.appendChild(this._anchor_input);

		var long_label = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(long_label, 'label');
		long_label.appendChild( this._dialog_window.document.createTextNode('Please provide a descriptive name for this anchor. The name should begin with a letter (a-z). The rest of the name can contain letters, numbers, and these characters: hyphens (-), underscores (_), colons(:), and periods(.). Other characters can\'t be used in an anchor name.') );

		var h1 = this._dialog_window.document.createElement('H1');
		if ( !this._initially_selected_item )
			h1.innerHTML = 'Create anchor';
		else
			h1.innerHTML = 'Edit anchor';

		var fieldset = new Util.Fieldset({legend : '', document : this._dialog_window.document});
		fieldset.fieldset_elem.appendChild(anchor_div);
		fieldset.fieldset_elem.appendChild(long_label);

		this._main_chunk.appendChild(h1);
		this._main_chunk.appendChild(fieldset.chunk);
	};

	this._append_remove_anchor_chunk = function()
	{
		var button = this._dialog_window.document.createElement('BUTTON');
		button.setAttribute('type', 'button');
		button.appendChild( this._dialog_window.document.createTextNode('Remove anchor') );

		var self = this;
		var listener = function()
		{
			/* not really necessary for just an anchor
			if ( confirm('Really remove anchor? WARNING: This cannot be undone.') )
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
		//this._dialog_window.body.appendChild(chunk);
		this._root.appendChild(chunk);
	};

	this._apply_initially_selected_item = function()
	{
		if ( this._initially_selected_item != null )
		{
			this._anchor_input.value = this._initially_selected_item.name;
		}
	};

	this._internal_submit_listener = function()
	{
		// Get anchor name 
		var anchor_name = this._anchor_input.value;
		if ( anchor_name.replace( new RegExp('[a-zA-Z0-9_:.-]+', ''), '') != '' ||
			 !anchor_name.match( new RegExp('^[a-zA-Z]', '') ) )
		{
			this._dialog_window.window.alert('You haven\'t entered a valid name. The name should begin with a Roman letter, and be followed by any number of digits, hyphens, underscores, colons, periods, and Roman letters. The name should include no other characters.');
			return false;
		}

		this._external_submit_listener({name : anchor_name});
		this._dialog_window.window.close();
	};
};
