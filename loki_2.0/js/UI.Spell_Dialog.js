/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A spell dialog window.
 */
UI.Spell_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);
	var self = this;

	this._dialog_window_width = 800;
	this._dialog_window_height = 300;

	this.init = function(params)
	{
		this._spell_uri = params.spell_uri;
		this._uncorrected_html = params.uncorrected_html;
		this.superclass.init.call(this, params);
	};

	this._append_style_sheets = function()
	{
		this.superclass._append_style_sheets.call(this);
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Spell_Dialog.css');
	};

	/**
	 * Called when the iframe finishes loading the spellchecked document.
	 */
	this.finish_init_async = function(suggestion_list, words)
	{
		this._suggestion_list = suggestion_list;
		this._spell_iframe_document = Util.Iframe.get_content_document(this._spell_iframe);
		//this._words = Util.Document.get_elements_by_tag_name_ns(this._spell_iframe_document, 'http://www.carleton.edu/spell', 'WORD');
		this._words = words;
		messagebox('words', this._words);
		this._current_word_index = -1; // incremented to 0 in this._next
		this._done = false;
		this._enable_buttons();
		this._next();
	};

	this._set_title = function()
	{
		this._dialog_window.document.title = "Spell check";
	};

	this._populate_main = function()
	{
		this._append_spell_chunk();
		this._load_spell_data();
		this._append_submit_and_cancel_chunk('Apply Changes', 'Cancel Changes');
		var self = this;
		setTimeout(function () { self._resize_dialog_window(false, true); }, 1000);
	};

	this._append_spell_chunk = function()
	{
		var doc = this._dialog_window.document;
		var self = this;

		// Options

		var misspelled_label = doc.createElement('LABEL');
		misspelled_label.htmlFor = 'misspelled_input';
		misspelled_label.innerHTML = 'Misspelled word:';

		this._misspelled_input = doc.createElement('INPUT');
		this._misspelled_input.id = 'misspelled_input';
		this._misspelled_input.disabled = true;

		var replacement_label = doc.createElement('LABEL');
		replacement_label.htmlFor = 'replacement_input';
		replacement_label.innerHTML = 'Replacement:';

		this._replacement_input = doc.createElement('INPUT');
		this._replacement_input.id = 'replacement_input';

		var suggestions_label = doc.createElement('LABEL');
		suggestions_label.htmlFor = 'suggestions_select';
		suggestions_label.innerHTML = 'Suggestions:';

		this._suggestions_select = doc.createElement('SELECT');
		this._suggestions_select.id = 'suggestions_select';
		this._suggestions_select.size = 5;
		Util.Event.add_event_listener(this._suggestions_select, 'change', function() { self._replacement_input.value = self._suggestions_select.value; });

		var options_div = doc.createElement('DIV');
		Util.Element.add_class(options_div, 'options');
		options_div.appendChild(misspelled_label);
		options_div.appendChild(this._misspelled_input);
		options_div.appendChild(replacement_label);
		options_div.appendChild(this._replacement_input);
		options_div.appendChild(suggestions_label);
		options_div.appendChild(this._suggestions_select);

		// Actions

		this._replace_button = doc.createElement('BUTTON');
		this._replace_button.setAttribute('type', 'button');
		this._replace_button.appendChild( doc.createTextNode('Replace') );
		Util.Event.add_event_listener(this._replace_button, 'click', function(e) { self.replace(); });

		this._replace_all_button = doc.createElement('BUTTON');
		this._replace_all_button.setAttribute('type', 'button');
		this._replace_all_button.appendChild( doc.createTextNode('Replace all') );
		Util.Event.add_event_listener(this._replace_all_button, 'click', function() { self.replace_all(); });

		this._ignore_button = doc.createElement('BUTTON');
		this._ignore_button.setAttribute('type', 'button');
		this._ignore_button.appendChild( doc.createTextNode('Ignore') );
		Util.Event.add_event_listener(this._ignore_button, 'click', function() { self.ignore(); });

		this._ignore_all_button = doc.createElement('BUTTON');
		this._ignore_all_button.setAttribute('type', 'button');
		this._ignore_all_button.appendChild( doc.createTextNode('Ignore all') );
		Util.Event.add_event_listener(this._ignore_all_button, 'click', function() { self.ignore_all(); });

		this._disable_buttons();

		var replace_div = doc.createElement('DIV');
		Util.Element.add_class(replace_div, 'replace');
		replace_div.appendChild(this._replace_button);
		replace_div.appendChild(this._replace_all_button);

		var ignore_div = doc.createElement('DIV');
		Util.Element.add_class(ignore_div, 'ignore');
		ignore_div.appendChild(this._ignore_button);
		ignore_div.appendChild(this._ignore_all_button);

		var actions_div = doc.createElement('DIV');
		Util.Element.add_class(actions_div, 'actions');
		actions_div.appendChild(replace_div);
		actions_div.appendChild(ignore_div);

		// Document

		var spell_label = doc.createElement('DIV');
		spell_label.innerHTML = 'Document:';

		this._spell_iframe = doc.createElement('IFRAME');
		this._spell_iframe.setAttribute('style', 'width:100%; height:20ex;'); // XXX tmp
		//Util.Event.add_event_listener(this._spell_iframe, 'load', function() { self.finish_init_async() });
		this._dialog_window.window.do_onframeload = function(suggestion_list, words) { self.finish_init_async(suggestion_list, words); };
		this._spell_iframe.src = this._base_uri + 'auxil/loki_blank.html';

		/* The old way:
		this._spell_iframe = doc.createElement('IFRAME');
		this._spell_iframe.setAttribute('style', 'width:100%; height:20ex;'); // XXX tmp
		//Util.Event.add_event_listener(this._spell_iframe, 'load', function() { self.finish_init_async() });
		this._dialog_window.window.do_onframeload = function(suggestion_list, words) { self.finish_init_async(suggestion_list, words); };
		this._spell_iframe.src = this._base_uri + this._spell_uri + '?text=' + encodeURIComponent(this._uncorrected_html);
		*/

		var spell_container = doc.createElement('DIV'); // XXX tmp
		spell_container.setAttribute('style', 'width:100%; height:20ex;'); // XXX tmp
		spell_container.appendChild(this._spell_iframe); // XXX tmp

		var document_div = doc.createElement('DIV');
		Util.Element.add_class(document_div, 'document');
		document_div.appendChild(spell_label);
		//document_div.appendChild(this._spell_iframe);
		document_div.appendChild(spell_container);

		// (the div-based layout breaks in IE--the iframe wraps no matter 
		// how wide the dialog--, and I can't figure out how to fix it, 
		// so just make a table)
		var table = this._dialog_window.document.createElement('TABLE');
		table.setAttribute('cellspacing', '0px');
		table.setAttribute('cellpadding', '0px');
		table.setAttribute('border', '0px');
		table.setAttribute('width', '100%');
		var tbody = this._dialog_window.document.createElement('TBODY');
		var tr = this._dialog_window.document.createElement('TR');
		var td = this._dialog_window.document.createElement('TD');
		td.setAttribute('valign', 'top');

		var options_td = td.cloneNode(true);
		Util.Element.add_class(options_td, 'options_td');
		options_td.appendChild(options_div);

		var actions_td = td.cloneNode(true);
		Util.Element.add_class(actions_td, 'actions_td');
		actions_td.appendChild(actions_div);

		var document_td = td.cloneNode(true);
		Util.Element.add_class(document_td, 'document_td');
		document_td.appendChild(document_div);

		tr.appendChild(options_td);
		tr.appendChild(actions_td);
		tr.appendChild(document_td);
		tbody.appendChild(tr);
		table.appendChild(tbody);

		// Heading and fieldset
		var h1 = this._dialog_window.document.createElement('H1');
		h1.innerHTML = 'Spell check';
		this._main_chunk.appendChild(h1);

		var fieldset = new Util.Fieldset({legend : '', document : this._dialog_window.document});
		fieldset.fieldset_elem.appendChild(table);
		/*
		fieldset.fieldset_elem.appendChild(options_div);
		fieldset.fieldset_elem.appendChild(actions_div);
		fieldset.fieldset_elem.appendChild(document_div);
		*/
		this._main_chunk.appendChild(fieldset.chunk);
	};

	this._load_spell_data = function()
	{
		this._spell_http_reader = new Util.HTTP_Reader;
		var self = this;
		this._spell_http_reader.add_load_listener(function () { self._load_spell_data_async(); });
		this._spell_http_reader.load(this._base_uri + this._spell_uri, this._uncorrected_html);
		
	};

	this._load_spell_data_async = function()
	{
		var iframe_doc = Util.Iframe.get_content_document(this._spell_iframe);
		var iframe_html = this._spell_http_reader.request.responseText;
		iframe_doc.write(iframe_html);
		iframe_doc.close();
		var iframe_win = Util.Iframe.get_content_window(this._spell_iframe);
		if ( document.all ) // This works for IE. XXX this is sort of a hack
			setTimeout(function() { iframe_win.spell_iframe__do_onload(); }, 1000);
	};

	this._enable_buttons = function()
	{
		this._replace_button.disabled = false;
		this._replace_all_button.disabled = false;
		this._ignore_button.disabled = false;
		this._ignore_all_button.disabled = false;
	};

	this._disable_buttons = function()
	{
		this._replace_button.disabled = true;
		this._replace_all_button.disabled = true;
		this._ignore_button.disabled = true;
		this._ignore_all_button.disabled = true;
	};

	this._internal_submit_listener = function()
	{
		var html = this._spell_iframe_document.getElementsByTagName('BODY')[0].innerHTML;
		// XXX use dom?
		html = html.replace(new RegExp('<spell:word( [^>]*)>', 'gi'), '');
		html = html.replace(new RegExp('<\/spell:word>', 'gi'), '');
		html = html.replace(new RegExp('<\?xml( [^>]*)spell( [^>]*)>', 'gi'), '');
		this._external_submit_listener({corrected_html : html});
		this._dialog_window.window.close();
	};


	this.replace = function()
	{
		if ( this._done )
			return;

		var word = this._words[this._current_word_index];
		word.innerHTML = this._replacement_input.value;
		word.setAttribute('done', 'done');
		this._next();
	};

	this.replace_all = function()
	{
		if ( this._done )
			return;

		var word = this._words[this._current_word_index];
		// When we write to innerHTML below, <word> will, sadly, be
		// destroyed and recreated (although our indices for this._words 
		// will still work in the updated NodeList), so our
		// reference to it will be lost.
		// Therefore we get what we want from <word> here.
		var word_innerHTML = word.innerHTML;
		for ( var i = 0; i < this._words.length; i++ )
		{
			var cur = this._words[i];
			if ( !cur.getAttribute('done') && cur.innerHTML == word_innerHTML )
			{
				cur.innerHTML = this._replacement_input.value;
				cur.setAttribute('done', 'done');
			}
		}
		this._next();
	};

	this.ignore = function()
	{
		if ( this._done )
			return;

		this._next();
	};

	// not sure if this one is working
	this.ignore_all = function()
	{
		if ( this._done )
			return;

		var word = this._words[this._current_word_index];
		for ( var i = 0; i < this._words.length; i++ )
		{
			var cur = this._words[i];
			if ( !cur.getAttribute('done') && cur.innerHTML == word.innerHTML )
			{
				cur.setAttribute('done', 'done');
			}
		}
		this._next();
	};

	this._next = function()
	{
		// 1. Unhighlight the old word and unload suggestions for it
		if ( this._current_word_index > -1 )
		{
			Util.Element.remove_all_classes(this._words[this._current_word_index]);
		}	
		while ( this._suggestions_select.firstChild != null )
			this._suggestions_select.removeChild(this._suggestions_select.firstChild);

		// 2. Advance word_index
		do
		{
			this._current_word_index++;
			if ( this._current_word_index >= this._words.length )
			{
				if ( this._words.length == 0 )
					this._dialog_window.window.alert('No misspelled words have been found.');
				else
					this._dialog_window.window.alert('All words have been corrected or ignored.');
				this._disable_buttons();
				this._misspelled_input.value = '';
				this._replacement_input.value = '';
				this._replacement_input.disabled = true;
				this._suggestions_select.disabled = true;
				this._done = true;
				return false;
			}
		}
		while ( this._words[this._current_word_index].getAttribute('done') )

		// 3. Highlight and scroll to the new word
		var word = this._words[this._current_word_index];
		Util.Element.add_class(word, 'current');
		/*
		var text_iframe_window = window.frames[0]; // I don't know how to get at the scroll_to_word function using 
		text_iframe_window.scroll_to_word(word);   // W3 stuff like document.getElementById('text_iframe').
		*/
		// XXX try this
		var spell_iframe_window = Util.Iframe.get_content_window(this._spell_iframe);
		spell_iframe_window.scroll_to_word(word);

		// 4. Load suggestions into the suggestions listbox and the replacement textbox
		var suggestions = eval( 'this._suggestion_list.' + word.getAttribute('id') );
		if ( suggestions.length > 0 )
		{
			for (var i = 0; i < suggestions.length; i++)
			{
				var the_item = this._dialog_window.document.createElement('OPTION');
				this._suggestions_select.appendChild(the_item);
				the_item.value = suggestions[i];
				the_item.innerHTML = suggestions[i];
			}
			this._suggestions_select.selectedIndex = 0;
			this._replacement_input.value = this._suggestions_select.value;
		}
		else
		{
			this._replacement_input.value = word.innerHTML;
		}

		// 5. Update misspelled word textbox
		this._misspelled_input.value = word.innerHTML;
	};
};
