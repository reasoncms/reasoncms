/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class An anchor dialog window.
 */
UI.Find_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);

	this._dialog_window_width = 615;
	this._dialog_window_height = 200;

	this.init = function(params)
	{
		this._find_listener = params.find_listener;
		this._replace_listener = params.replace_listener;
		this._replace_all_listener = params.replace_all_listener;
		this._select_beginning_listener = params.select_beginning_listener;
		this.superclass.init.call(this, params);
	};

	this._set_title = function()
	{
		this._dialog_window.document.title = "Find and replace";
	};

	this._append_style_sheets = function()
	{
		this.superclass._append_style_sheets.call(this);
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Find_Dialog.css');
	};

	this._populate_main = function()
	{
		this._append_find_chunk();
		this._append_submit_and_cancel_chunk();
		var self = this;
		setTimeout(function () { self._resize_dialog_window(true, true); }, 1000);
		//this._resize_dialog_window(false, true);
	};

	this._append_find_chunk = function()
	{
		var self = this;

		// Create Search input and label
		this._search_input = this._dialog_window.document.createElement('INPUT');
		this._search_input.setAttribute('size', '40');
		this._search_input.setAttribute('id', 'search_input');
		//this._search_input.value = 'as'; // XXX tmp

		var search_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(search_label, 'label');
		search_label.setAttribute('for', 'search_input');
		search_label.innerHTML = 'Search&nbsp;for:&nbsp;';
		//search_label.appendChild( this._dialog_window.document.createTextNode('Search for: ') );

		var search_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(search_div, 'field');
		search_div.appendChild(search_label);
		search_div.appendChild(this._search_input);
		
		// Create Replace input and label
		this._replace_input = this._dialog_window.document.createElement('INPUT');
		this._replace_input.setAttribute('size', '40');
		this._replace_input.setAttribute('id', 'replace_input');
		//this._replace_input.value = 'hmm'; // XXX tmp

		var replace_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(replace_label, 'label');
		replace_label.setAttribute('for', 'replace_input');
		replace_label.innerHTML = 'Replace&nbsp;with:&nbsp;';
		//replace_label.appendChild( this._dialog_window.document.createTextNode('Replace with: ') );

		var replace_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(replace_div, 'field');
		replace_div.appendChild(replace_label);
		replace_div.appendChild(this._replace_input);

		// Create Match Case checkbox and label
		this._matchcase_checkbox = this._dialog_window.document.createElement('INPUT');
		this._matchcase_checkbox.setAttribute('type', 'checkbox');
		this._matchcase_checkbox.setAttribute('id', 'matchcase_checkbox');

		var matchcase_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(matchcase_label, 'label');
		matchcase_label.setAttribute('for', 'matchcase_checkbox');
		matchcase_label.appendChild( this._dialog_window.document.createTextNode('Match case') );

		// Create match case div
		var matchcase_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(matchcase_div, 'field');
		matchcase_div.appendChild(this._matchcase_checkbox);
		matchcase_div.appendChild(matchcase_label);

		// Create options div
		var options_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(options_div, 'options');
		options_div.appendChild(search_div);
		options_div.appendChild(replace_div);
		options_div.appendChild(matchcase_div);


		// Create Find Next button
		this._find_button = this._dialog_window.document.createElement('BUTTON');
		Util.Element.add_class(this._find_button, 'ok');
		this._find_button.setAttribute('type', 'submit');
		this._find_button.appendChild(this._dialog_window.document.createTextNode('Find Next'));
		Util.Event.add_event_listener(this._find_button, 'click', 
			function(event)
			{
				// Since this is a submit button (in order for "enter" in the inputs
				// to cause this button to be fired), the javascript:void(0) form
				// will be submitted when this button in clicked, and in FF 1.0 
				// that causes an error about transferring data from an encrypted
				// page over an unencrypted connection.
				// So prevent the form from being submitted.
				if ( event.preventDefault )
					event.preventDefault();

				var ret = self._find_listener( self._search_input.value, 
											   self._matchcase_checkbox.checked, 
											   false, //self._findbackwards_checkbox.checked,
											   true );
				if ( ret == UI.Find_Helper.NOT_FOUND && 
					 self._dialog_window.window.confirm('Match not found. Continue from beginning?') )
				{
					self._select_beginning_listener();
					var ret = self._find_listener( self._search_input.value, 
												   self._matchcase_checkbox.checked, 
												   false, //self._findbackwards_checkbox.checked,
												   true );
					if ( ret == UI.Find_Helper.NOT_FOUND )
						self._dialog_window.window.alert('Match not found.');
				}
			}
		);

		// Create Replace button
		this._replace_button = this._dialog_window.document.createElement('BUTTON');
		this._replace_button.setAttribute('type', 'button');
		this._replace_button.appendChild(this._dialog_window.document.createTextNode('Replace'));
		Util.Event.add_event_listener(this._replace_button, 'click', 
			function()
			{
				var ret = self._replace_listener( self._search_input.value, 
												  self._replace_input.value, 
												  self._matchcase_checkbox.checked, 
												  false, //self._findbackwards_checkbox.checked,
												  true );
				if ( ret == UI.Find_Helper.NOT_FOUND && 
					 self._dialog_window.window.confirm('Match not found. Continue from beginning?') )
				{
					self._select_beginning_listener();
					var ret = self._replace_listener( self._search_input.value, 
													  self._replace_input.value, 
													  self._matchcase_checkbox.checked, 
													  false, //self._findbackwards_checkbox.checked,
													  true );
					if ( ret == UI.Find_Helper.NOT_FOUND )
						self._dialog_window.window.alert('Match not found.');
				}

				if ( ret == UI.Find_Helper.REPLACED_LAST_MATCH && 
					 self._dialog_window.window.confirm('Replaced last match. Continue from beginning?') )
				{
					self._select_beginning_listener();
					var ret = self._find_listener( self._search_input.value, 
												   self._matchcase_checkbox.checked, 
												   false, //self._findbackwards_checkbox.checked,
												   true );
					if ( ret == UI.Find_Helper.NOT_FOUND )
						self._dialog_window.window.alert('Match not found.');
				}
			}
		);

		// Create Replace All button
		this._replaceall_button = this._dialog_window.document.createElement('BUTTON');
		this._replaceall_button.setAttribute('type', 'button');
		this._replaceall_button.appendChild(this._dialog_window.document.createTextNode('Replace All'));
		Util.Event.add_event_listener(this._replaceall_button, 'click', 
			function()
			{
				var i = self._replace_all_listener( self._search_input.value, 
													self._replace_input.value, 
													self._matchcase_checkbox.checked, 
													false, //self._findbackwards_checkbox.checked,
													true );
				if ( i < 1 )
					self._dialog_window.window.alert('Not found.');
				else
					self._dialog_window.window.alert('Replaced ' + i + ' instances.');
			}
		);

		/*
		// Create Cancel button
		this._cancel_button = this._dialog_window.document.createElement('BUTTON');
		this._cancel_button.setAttribute('type', 'button');
		this._cancel_button.appendChild(this._dialog_window.document.createTextNode('Close'));
		Util.Event.add_event_listener(this._cancel_button, 'click', function() { self._internal_cancel_listener(); });
		*/

		// Create actions div
		var actions_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(actions_div, 'actions');

		var actions_ul = this._dialog_window.document.createElement('UL');
		actions_div.appendChild(actions_ul);

		var find_button_li = this._dialog_window.document.createElement('LI');
		var replace_button_li = this._dialog_window.document.createElement('LI');
		var replaceall_button_li = this._dialog_window.document.createElement('LI');
		actions_ul.appendChild(find_button_li);
		actions_ul.appendChild(replace_button_li);
		actions_ul.appendChild(replaceall_button_li);

		find_button_li.appendChild(this._find_button);
		replace_button_li.appendChild(this._replace_button);
		replaceall_button_li.appendChild(this._replaceall_button);

	/*
		// Create Find Backwards checkbox and label
		this._findbackwards_checkbox = this._dialog_window.document.createElement('INPUT');
		this._findbackwards_checkbox.setAttribute('type', 'checkbox');
		this._findbackwards_checkbox.setAttribute('id', 'findbackwards_checkbox');

		var findbackwards_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(findbackwards_label, 'label');
		findbackwards_label.setAttribute('for', 'findbackwards_checkbox');
		findbackwards_label.appendChild( this._dialog_window.document.createTextNode('Find backwards') );
	*/

		// Create heading
		var h1 = this._dialog_window.document.createElement('H1');
		h1.innerHTML = 'Find and replace';

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : '', document : this._dialog_window.document});

		// Append options and actions to fieldset
		fieldset.fieldset_elem.appendChild(options_div);
		fieldset.fieldset_elem.appendChild(actions_div);
	/*
		fieldset.fieldset_elem.appendChild(this._findbackwards_checkbox);
		fieldset.fieldset_elem.appendChild(findbackwards_label);
	*/

		// Append h1 and fieldset chunk to dialog
		this._main_chunk.appendChild(h1);
		this._main_chunk.appendChild(fieldset.chunk);
	};

	this._append_submit_and_cancel_chunk = function(submit_text, cancel_text)
	{
		// Init submit and cancel text
		submit_text = submit_text == null ? 'OK' : submit_text;
		cancel_text = cancel_text == null ? 'Close' : cancel_text;


		// Setup submit and cancel buttons

		var submit_button = this._dialog_window.document.createElement('BUTTON');
		var cancel_button = this._dialog_window.document.createElement('BUTTON');

		submit_button.setAttribute('type', 'button');
		cancel_button.setAttribute('type', 'button');

		submit_button.appendChild( this._dialog_window.document.createTextNode(submit_text) );
		cancel_button.appendChild( this._dialog_window.document.createTextNode(cancel_text) );

		var self = this;
		Util.Event.add_event_listener(submit_button, 'click', function() { self._internal_submit_listener(); });
		Util.Event.add_event_listener(cancel_button, 'click', function() { self._internal_cancel_listener(); });

		Util.Element.add_class(submit_button, 'ok');
		

		// Setup their containing chunk
		var submit_and_cancel_chunk = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(submit_and_cancel_chunk, 'submit_and_cancel_chunk');
		submit_and_cancel_chunk.appendChild(cancel_button);
		//submit_and_cancel_chunk.appendChild(submit_button);


		// Append their containing chunk
		//this._dialog_window.body.appendChild(submit_and_cancel_chunk);
		this._root.appendChild(submit_and_cancel_chunk);
	};
};
