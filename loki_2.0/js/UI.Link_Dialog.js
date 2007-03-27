/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class An email link dialog window.
 */
UI.Link_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);

	/**
	 * Populates the main chunk. You'll want to do something more
	 * here in descendents.
	 */
	this._populate_main = function()
	{
		this._append_link_information_chunk()
		this._append_submit_and_cancel_chunk();
		this._append_remove_link_chunk();
	};

	/**
	 * Appends a chunk with extra options for links.
	 */
	this._append_link_information_chunk = function()
	{
		// Link title
		this._link_title_input = this._dialog_window.document.createElement('INPUT');
		this._link_title_input.size = 40;
		this._link_title_input.id = 'link_title_input';
		this._link_title_input.value = this._initially_selected_item.title;

		var lt_label = this._dialog_window.document.createElement('LABEL');
		var strong = this._dialog_window.document.createElement('STRONG');
		strong.appendChild( this._dialog_window.document.createTextNode('Description: ') );
		lt_label.appendChild(strong);
		lt_label.htmlFor = 'link_title_input';

		lt_comment = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(lt_comment, 'comment');
		lt_comment.innerHTML = '(Will appear in some browsers when mouse is held over link.)';

		var lt_chunk = this._dialog_window.document.createElement('DIV');
		lt_chunk.appendChild(lt_label);
		lt_chunk.appendChild(this._link_title_input);
		lt_chunk.appendChild(lt_comment);

		// "Other options"
		this._other_options_chunk = this._dialog_window.document.createElement('DIV');
		this._other_options_chunk.id = 'other_options';
		if ( this._initially_selected_item.new_window == true )
			this._other_options_chunk.style.display = 'block';
		else
			this._other_options_chunk.style.display = 'none';

		var other_options_label = this._dialog_window.document.createElement('H3');
		var other_options_a = this._dialog_window.document.createElement('A');
		other_options_a.href = 'javascript:void(0);';
		other_options_a.innerHTML = 'More Options';
		var self = this;
		Util.Event.add_event_listener(other_options_a, 'click', function() {
			if ( self._other_options_chunk.style.display == 'none' )
				self._other_options_chunk.style.display = 'block';
			else
				self._other_options_chunk.style.display = 'none';
		});
		other_options_label.appendChild(other_options_a);
		
		// Checkbox
		this._new_window_checkbox = this._dialog_window.document.createElement('INPUT');
		this._new_window_checkbox.type = 'checkbox';
		this._new_window_checkbox.id = 'new_window_checkbox';
		this._new_window_checkbox.checked = this._initially_selected_item.new_window;

		var nw_label = this._dialog_window.document.createElement('LABEL');
		nw_label.appendChild( this._dialog_window.document.createTextNode('Open in new browser window') );
		nw_label.htmlFor = 'new_window_checkbox';

		var nw_chunk = this._dialog_window.document.createElement('DIV');
		nw_chunk.appendChild(this._new_window_checkbox);
		nw_chunk.appendChild(nw_label);

		this._other_options_chunk.appendChild(nw_chunk);

		// Create fieldset and its legend, and append to fieldset
		var fieldset = new Util.Fieldset({legend : 'Link information', document : this._dialog_window.document});
		fieldset.fieldset_elem.appendChild(lt_chunk);
		fieldset.fieldset_elem.appendChild(other_options_label);
		fieldset.fieldset_elem.appendChild(this._other_options_chunk);

		// Append fieldset chunk to dialog
		this._main_chunk.appendChild(fieldset.chunk);
	};

	/**
	 * Creates and appends a chunk containing a "remove link" button. 
	 * Also attaches 'click' event listeners to the button.
	 */
	this._append_remove_link_chunk = function()
	{
		var button = this._dialog_window.document.createElement('BUTTON');
		button.setAttribute('type', 'button');
		button.appendChild( this._dialog_window.document.createTextNode('Remove link') );

		var self = this;
		var listener = function()
		{
			self._external_submit_listener({uri : '', new_window : false, title : ''});
			self._dialog_window.window.close();
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
	 * button. You'll want to do something more here in descendents.
	 */
	this._internal_submit_listener = function()
	{
		// Call external event listener
		this._external_submit_listener({uri : '', // in descendents change this
										new_window : this._new_window_checkbox.checked, 
										title : this._link_title_input.value});

		// Close dialog window
		this._dialog_window.window.close();
	};
};
