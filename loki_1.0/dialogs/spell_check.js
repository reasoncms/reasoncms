//
// This prototype is used to mediate between the dialog and the php
// script in the iframe with the spell checker (spell_check.php).
//
// init must be called before anything else, in lieu of a real
// constructor, which wouldn't inherit properly
//
// Methods which only have a stub, such as _append_item_to_listbox,
// must be overloaded for things to work properly.
//
// Nathanael Fillmore, 14/May/2004
//
function spell_check()
{
	// declare member variables
	this._suggestion_list = null;
	this._words = null;
	this._current_word_index = null;
	this._done = false;
}
spell_check.prototype.init = function(suggestion_list, words)
{
	this._suggestion_list = suggestion_list;
	this._words = words;
	this._current_word_index = -1; // incremented to 0 in this._next
	this._next();
}
spell_check.prototype.replace = function()
{
	if ( !this._done )
	{
		var word = this._words[this._current_word_index];
		word.innerHTML = document.getElementById('replacement_textbox').value;
		word.setAttribute('done', 'done');
		this._next();
	}
};
spell_check.prototype.replace_all = function()
{
	if ( !this._done )
	{
		var word = this._words[this._current_word_index];
		word_innerHTML = word.innerHTML;
		var cur;
		for ( var i = 0; i < this._words.length; i++ )
		{
			cur = this._words[i];
			if ( !cur.getAttribute('done') && cur.innerHTML == word_innerHTML )
			{
				cur.innerHTML = document.getElementById('replacement_textbox').value;
				cur.setAttribute('done', 'done');
			}
		}
		this._next();
	}
};
spell_check.prototype.ignore = function()
{
	if ( !this._done )
	{
		this._next();
	}
};
// not sure if this one is working
spell_check.prototype.ignore_all = function()
{
	if ( !this._done )
	{
		var word = this._words[this._current_word_index];
		word_innerHTML = word.innerHTML;
		var cur;
		for ( var i = 0; i < this._words.length; i++ )
		{
			cur = this._words[i];
			if ( !cur.getAttribute('done') && cur.innerHTML == word_innerHTML )
			{
				cur.setAttribute('done', 'done');
			}
		}
		this._next();
	}
};
spell_check.prototype._next = function()
{
	var suggestions_listbox = document.getElementById('suggestions_listbox');

	// 1. Unhighlight the old word and unload suggestions for it
	if ( this._current_word_index > -1 )
	{
		this._remove_class_of_element(this._words[this._current_word_index]);
	}	
	this._remove_all_items_from_listbox(suggestions_listbox)

	// 2. Advance word_index
	do
	{
		this._current_word_index++;
		if ( this._current_word_index >= this._words.length )
		{
			if ( this._words.length == 0 )
				alert('No misspelled words have been found.');
			else
				alert('All words have been corrected or ignored.');
			this._done = true;
			return false;
		}
	}
	while ( this._words[this._current_word_index].getAttribute('done') )

	// 3. Highlight and scroll to the new word
	var word = this._words[this._current_word_index];
	this._set_class_of_element(word, 'current');
	var text_iframe_window = window.frames[0]; // I don't know how to get at the scroll_to_word function using 
	text_iframe_window.scroll_to_word(word);   // W3 stuff like document.getElementById('text_iframe').

	// 4. Load suggestions into the suggestions listbox and the replacement textbox
	var suggestions = eval( 'this._suggestion_list.' + word.getAttribute('id') );
	if ( suggestions.length > 0 )
	{
		for (var i = 0; i < suggestions.length; i++)
		{
			this._append_item_to_listbox(suggestions_listbox, suggestions[i], suggestions[i]);
		}
		suggestions_listbox.selectedIndex = 0;
		document.getElementById('replacement_textbox').value = this._get_value_of_listbox(suggestions_listbox);
	}
	else
	{
		document.getElementById('replacement_textbox').value = word.innerHTML;
	}

	// 5. Update misspelled word textbox
	document.getElementById('misspelled_textbox').value = word.innerHTML;
};
spell_check.prototype._remove_all_items_from_listbox = function(listbox)
{
	// stub
};
spell_check.prototype._append_item_to_listbox = function(listbox, label, value)
{
	// stub
};
spell_check.prototype._get_value_of_listbox = function(listbox)
{
	// stub
};
spell_check.prototype._set_class_of_element = function(the_element, the_class_name)
{
	// stub
};
spell_check.prototype._remove_class_of_element = function(the_element)
{
	// stub
};