/**
 * Declares instance variables. You must call <code>init</code> to
 * initialize instance variables.
 *
 * @constructor
 *
 * @class Represents a listbox. Is intended to replace native HTML
 * elements like select boxes or checkboxes, but (a) be able to
 * display more complicated items, and (b) be more easy to navigate,
 * by having a built in filter and pager.
 *
 * @author	Nathanael Fillmore
 * @version 2004-08-23
 * 
 */
UI.Listbox = function()
{
	// Permanent listbox instance properties
	this._doc_obj; // reference to the document object for the document this listbox will be added to
	this._root_elem; // the root listbox element
	this._items = new Array(); // holds the list items (their data, that is, not their document fragments)
	this._item_chunks = new Array(); // holds the document chunk for each list item
	this._selected_index; // holds index in this._items of the currently selected item

	this._filtered_indices = new Array(); // holds indices of the items which match the _filter_string
	this._cur_page_num;
	this._num_results_per_page;
	this._filter_string;

	this._items_chunk_elem;
	this._next_page_elem;
	this._prev_page_elem;
	this._page_num_elem;

	this._event_listeners = new Object();
};

/**
 * Initializes instance variables.  Also appends chunks for the
 * various parts of the listbox to the root element.
 *
 * @param	listbox_id	the desired id of the root listbox HTML element.  
 * @param	doc_obj		a reference to the document object for the document
 *                      this listbox will be added to.
 */
UI.Listbox.prototype.init = function(listbox_id, doc_obj)
{
	// Permanent listbox instance properties
	this._doc_obj = doc_obj;
	this._create_root_elem(listbox_id);

	// Current state of listbox
	this._cur_page_num = 0; // zero-based
	//this._num_results_per_page = 40;
	this._num_results_per_page = 8;
	this._filter_string = '';
	this._selected_index = -1;

	// Append chunks
	this._append_page_chunk();
	this._append_filter_chunk();
	this._append_items_chunk();
};

/**
 * Adds an item to the listbox. (It isn't displayed, though, until
 * refresh is called.)
 *
 * @param	item	the item to append. Item should have whatever properties
 *                  set are needed by this._create_item_chunk. For Listbox proper, 
 *                  these are title and description, but for extensions these
 *                  might be different.
 */
UI.Listbox.prototype.append_item = function(item)
{
	this._items.push(item);
};

/**
 * Inserts an item to the listbox at the specified index. See on
 * append_item() for more info
 *
 * @param	item	the item to insert
 * @param	index	the desired index of this item. The indices of all
 *                  items with indices greater than index will be
 *                  increased by 1.
 */
UI.Listbox.prototype.insert_item = function(item, index)
{
	this._items.splice(index, 0, item);
};

/**
 * Removes an item from the listbox.
 */
UI.Listbox.prototype.remove_item = function(index)
{
	// Remove item
	this._items.splice(index, 1);
	this._item_chunks.splice(index, 1);

	// Fix selected index
	if ( this._selected_index == index )
		this._selected_index = -1;
	else if ( this._selected_index > index )
		this._selected_index--;
};

/**
 * Removes all items from the listbox.
 */
UI.Listbox.prototype.remove_all_items = function()
{
	while ( this._items.length > 0 )
		this.remove_item(0);
};

/**
 * Returns the item at the given index.
 *
 * @return	the item at the given index
 */
UI.Listbox.prototype.get_item = function(index)
{
	return this._items[index];
};

/**
 * Returns the index of the given item. (Note: this is obviously a lot
 * slower than get_item, so it's better to keep track of the index
 * of the item you want than to keep track of the item itself and get
 * its index with this method.)
 *
 * @param	item	the item to get the index of 
 * @return			index of the given item
 * @throws	Error	if no item is found
 */
UI.Listbox.prototype.get_index_of_item = function(item)
{
	for ( var i = 0; i < this._items.length; i++ )
	{
		if ( this._items[i] == item )
			return i;
	}
	throw new Error("UI.Listbox.get_index_of_item: no such item was found");
};

/**
 * Sets which item is currently selected, based on the given index.
 *
 * @param	index			the index of the item to select
 */
UI.Listbox.prototype.select_item_by_index = function(index, dont_refresh, debug)
{
	var item_chunk = this._get_item_chunk(index);

	// Deselect old item, if there is one
	if ( this.get_selected_index() != -1 )
	{
		var formerly_selected_item_chunk = this._item_chunks[ this.get_selected_index() ];
		Util.Element.remove_class(formerly_selected_item_chunk, 'selected');
	}

	// Select new item
	this._selected_index = index;
	Util.Element.add_class(item_chunk, 'selected');

	// Trigger change listeners
	var self = this;
	var async = function() { self._trigger_event_listeners('change'); }
	setTimeout(async, 10);
};

/**
 * Returns the index of the currently selected item. (For
 * Multiple_Listbox, use get_selected_indices() instead.)
 *
 * @return		index of the currently selected item, or -1 if
 *              no item is currently selected
 */
UI.Listbox.prototype.get_selected_index = function()
{
	return this._selected_index;
};

/**
 * Returns the currently selected item. (For Multiple_Listbox, use
 * get_selected_items() instead.)
 *
 * @return		the currently selected item, or null if no item is
 *              currently selected
 */
UI.Listbox.prototype.get_selected_item = function()
{
	var selected_index = this.get_selected_index();

	if ( selected_index > -1 )
		return this.get_item( selected_index );
	else
		return null;
};

/**
 * Returns the number of items in the listbox.
 *
 * @return	the number of items in the listbox
 */
UI.Listbox.prototype.get_length = function()
{
	return this._items.length;
};

/**
 * Changes the current page such that the selected item is displayed.
 */
UI.Listbox.prototype.page_to_selected_item = function()
{
	var desired_page_num = Math.floor(this.get_selected_index() /
									  this._num_results_per_page);
	this._cur_page_num = desired_page_num;	
	this.refresh();
};

/**
 * Refreshes the listbox to reflect added items, changed filters,
 * current page number, and so on.
 */
UI.Listbox.prototype.refresh = function()
{
	this._refresh_items_chunk();
	this._refresh_page_chunk();
};

/**
 * Returns the root element of the listbox, which can then be added to
 * the document tree as appropriate.
 *
 * @return		the root element of the listbox
 */
UI.Listbox.prototype.get_listbox_elem = function()
{
	messagebox('UI.Listbox: this._root_elem', this._root_elem);
	return this._root_elem;
};

/**
 * Loads items from a buffered reader and appends them all to the listbox.
 *
 * @param	buffered_reader			the buffered reader
 * @param	initially_selected_item_boolean_test	(optional) is passed a reader_item, and returns true if the item should be initially selected
 */
UI.Listbox.prototype.append_items_from_buffered_reader = function(buffered_reader, initially_selected_item_boolean_test)
{
	var self = this;
	var cumulative_i = 0;
	var original_listbox_length = this._items.length;
	function load_listener()
	{
		// Get the current reader items
		var reader_items = buffered_reader.get_cur_items();
		var initially_selected_index = -1;

		// Append each item from the reader to the list box
		for ( var i = 0; i < reader_items.length; i++ )
		{
			var reader_item = reader_items[i];
			self.append_item(reader_item);

			// Check whether this is the initially selected item; if
			// so, mark it
			if ( initially_selected_item_boolean_test != null &&
				 initially_selected_item_boolean_test(reader_item) )
			{
				initially_selected_index = original_listbox_length + cumulative_i;
			}

			cumulative_i++;
		}

		// Refresh the listbox
		self.refresh();

		// Select the item marked as initially selected
		if ( initially_selected_index > -1 )
		{
			self.select_item_by_index(initially_selected_index);
			self.page_to_selected_item();
		}

		// Load the next set of items.
		// Note: This should work, but does not. What it should do is
		// start the process to load the next set of items, which will
		// eventually call this listener again, at least if there are
		// more items. Instead, calling load_next_items from here
		// hangs Gecko and causes an error in IE. If anyone knows why,
		// it is god.
		if ( reader_items.length != 0 )
			buffered_reader.load_next_items();
	}
	
	buffered_reader.add_load_listener(load_listener);
	buffered_reader.load_next_items();
};

/**
 * Adds a listener to be called on some event.
 */
UI.Listbox.prototype.add_event_listener = function(event_type, listener)
{
	if ( this._event_listeners[event_type] == null )
		this._event_listeners[event_type] = new Array();

	this._event_listeners[event_type].push(listener);
};

/**
 * Triggers the event listeners.
 */
UI.Listbox.prototype._trigger_event_listeners = function(event_type)
{
	if ( this._event_listeners[event_type] != null )
	{
		for ( var i = 0; i < this._event_listeners[event_type].length; i++ )
		{
			this._event_listeners[event_type][i]();
		}
	}
};


///////////////////////////////////
//
// ROOT SECTION
//
///////////////////////////////////

/**
 * Creates the root element.
 *
 * @param	listbox_id	the id of the root element
 */
UI.Listbox.prototype._create_root_elem = function(listbox_id)
{
	messagebox('Listbox: this._doc_obj', this._doc_obj);
	this._root_elem = this._doc_obj.createElement('DIV');
	messagebox('Listbox: created root elem', this._root_elem);
	this._root_elem.id = listbox_id;
	Util.Element.add_class(this._root_elem, 'listbox');
	messagebox('Listbox: created root elem', this._root_elem);
};

///////////////////////////////////
//
// FILTER SECTION
//
///////////////////////////////////

/**
 * Appends to the root_elem the chunk which holds the filter.
 *
 * @private
 */
UI.Listbox.prototype._append_filter_chunk = function()
{
	// create filter chunk
	var filter_chunk_elem = this._doc_obj.createElement('DIV');
	Util.Element.add_class(filter_chunk_elem, 'filter_chunk');

	// create label
	var filter_label_elem = this._doc_obj.createElement('SPAN');
	Util.Element.add_class(filter_label_elem, 'label');
	filter_label_elem.appendChild( this._doc_obj.createTextNode('Search:') );

	// create input elem ... 
	this._filter_input_elem = this._doc_obj.createElement('INPUT');
	this._filter_input_elem.setAttribute('size', '20');
	this._filter_input_elem.setAttribute('name', 'filter_input_elem');

	// .. and create event listeners to check the filter ...
	var self = this;
	var event_listener = function() { self._set_filter_string( self._filter_input_elem.value ); };

	// ... and add the listeners to the input elem
	Util.Event.add_event_listener(this._filter_input_elem, 'mouseup', event_listener);
	Util.Event.add_event_listener(this._filter_input_elem, 'change', event_listener);
	Util.Event.add_event_listener(this._filter_input_elem, 'keyup', event_listener);
	Util.Event.add_event_listener(this._filter_input_elem, 'click', event_listener);

	// ... and disable pressing enter
	var event_listener = function(event)
	{
		event = event == null ? _window.event : event;
		return ( event.keyCode != event.DOM_VK_RETURN &&
				 event.keyCode != event.DOM_VK_ENTER );
	};
	this._filter_input_elem.onkeydown = event_listener;
	this._filter_input_elem.onkeypress = event_listener;
	this._filter_input_elem.onkeyup = event_listener;

	// append label and input elem
	filter_chunk_elem.appendChild(filter_label_elem);
	filter_chunk_elem.appendChild(this._filter_input_elem);
	
	// append filter chunk
	this._root_elem.appendChild(filter_chunk_elem);
};

/**
 * Sets the filter string, resets the cur_page to the first one, and
 * tells the listbox to display appropriate items. Usually called from
 * an event listener on filter_input_elem.
 *
 * @private
 */
UI.Listbox.prototype._set_filter_string = function(filter_string)
{
	// only change things if the filter_string is different from
	// what's already there
	if ( this._filter_string != filter_string )
	{
		this._filter_string = filter_string;
		this._cur_page_num = 0;
		this.refresh();
	}
};

/**
 * Sets this._filtered_indices to contain indices of only those items
 * which match the current filter.
 *
 * @private
 */
UI.Listbox.prototype._update_filtered_indices = function()
{
	this._filtered_indices = new Array();

	if ( this._filter_string == '' )
	{
		for ( var i = 0; i < this._items.length; i++ )
			this._filtered_indices.push(i);
	}
	else
	{
		var cur_item, item_property_name, item_property_lc;
		var filter_string_lc = this._filter_string.toLowerCase();
		for ( var i = 0; i < this._items.length; i++ )
		{
			cur_item = this._items[i];
			for ( item_property_name in cur_item )
			{
				item_property_lc = cur_item[item_property_name].toLowerCase();
				if ( item_property_lc.indexOf(filter_string_lc) != -1 ) // if matches
				{
					this._filtered_indices.push(i);
					break;
				}
			}
		}
	}
};

///////////////////////////////////
//
// ITEMS SECTION
//
///////////////////////////////////

/**
 * Appends to the root_elem the chunk which holds the list of items
 *
 * @private
 */
UI.Listbox.prototype._append_items_chunk = function()
{
	this._items_chunk_elem = this._doc_obj.createElement('DIV');
	Util.Element.add_class(this._items_chunk_elem, 'items_chunk');
	this._root_elem.appendChild(this._items_chunk_elem);
};

/**
 * Clears out the children of items_chunk, and replaces them with
 * chunks made from items which match the current filter/page.  (N.B.:
 * _append_items_chunk must be called before this.)
 *
 * @private
 */
UI.Listbox.prototype._refresh_items_chunk = function()
{
	// Determine starting and ending indices
	var starting_index = this._cur_page_num * this._num_results_per_page;
	var ending_index = (this._cur_page_num + 1) * this._num_results_per_page;

	// Make sure to use items which match the current filter
	this._update_filtered_indices();

	// Clear list of old displayed items 
	Util.Node.remove_child_nodes(this._items_chunk_elem);

	// Display new list of items
	var item_index, item, item_chunk;
	for ( var i = starting_index; i < ending_index && i < this._filtered_indices.length; i++ )
	{
		item_index = this._filtered_indices[i];
		item_chunk = this._get_item_chunk(item_index);
		this._items_chunk_elem.appendChild(item_chunk);
		this._modify_item_chunk(item_chunk, i);
	}

	// Display message if there are no items
	if ( this._filtered_indices.length == 0 )
	{
		var no_items_chunk = this._get_no_items_chunk();
		this._items_chunk_elem.appendChild(no_items_chunk);
	}
};

/**
 * Returns a chunk to be displayed when no items match the current
 * filter criteria, etc.
 *
 * @return		the chunk
 * @private
 */
UI.Listbox.prototype._get_no_items_chunk = function()
{
	var item_chunk = this._doc_obj.createElement('DIV');
	item_chunk.appendChild( this._doc_obj.createTextNode('No matching items.') );
	return item_chunk;
};

/**
 * If an item chunk corresponding to the given index has already been
 * created, returns that item chunk; otherwise, creates one. If you
 * want to muck with how item chunks are created, overload
 * create_item_chunk rather than this method.
 *
 * @param	item_index	the index of the item for which to get an item_chunk
 * @private
 */
UI.Listbox.prototype._get_item_chunk = function(item_index)
{
	var item = this._items[item_index];
	var item_chunk;
	
	if ( this._item_chunks[item_index] != null )
	{
		item_chunk = this._item_chunks[item_index];
	}
	else
	{
		item_chunk = this._create_item_chunk(item);
		this._add_event_listeners_to_item_chunk(item_chunk, item_index);

		this._item_chunks[item_index] = item_chunk;
	}

	return item_chunk;
};

/**
 * Modify the item chunk as appropriate for its place in the set of
 * currently displayed items. (In Image_Listbox, for example, we need
 * to add a class to every third item_chunk.)
 *
 * @param	item_chunk	the item_chunk to modify
 * @param	cur_i		the index of this item in relation to other items
 *                      in the current display
 */
UI.Listbox.prototype._modify_item_chunk = function(item_chunk, cur_i)
{
};

/**
 * Creates a document chunk for the given item.  N.B.: This is a
 * useful method to overload.
 *
 * @param	item	the item for which to create a document chunk
 * @return			the created chunk
 * @private
 */
UI.Listbox.prototype._create_item_chunk = function(item)
{
	//var item_chunk = this._doc_obj.createElement('DIV');
	var item_chunk = this._doc_obj.createElement('A');
	item_chunk.href = 'javascript:void(0);';
	Util.Element.add_class(item_chunk, 'item_chunk');
	item_chunk.appendChild(
		this._doc_obj.createTextNode('Title: ' + item.title + '; description: ' + item.description)
	);
	return item_chunk;
};

/**
 * This adds the appropriate event listeners to the given item_chunk.
 * N.B.: This is a useful method to overload.
 *
 * @param	item_chunk	the item_chunk to which the event listeners will be added
 * @param	item_index	the index of the item (in the array this._items)
 * @private
 */
UI.Listbox.prototype._add_event_listeners_to_item_chunk = function(item_chunk, item_index)
{
	// Hover
	Util.Event.add_event_listener(item_chunk, 'mouseover', function() { Util.Element.add_class(item_chunk, 'hover'); });
	Util.Event.add_event_listener(item_chunk, 'mouseout', function() { Util.Element.remove_class(item_chunk, 'hover'); });

	// Select
	var self = this;
	Util.Event.add_event_listener(item_chunk, 'click', function() { self.select_item_by_index(item_index); });
};

/**
 * Returns true if this item is selected, false otherwise.
 *
 * @param	item	the item which may be selected
 * @return			true if the given item is selected, false otherwise
 * @deprecated		use the public methods above instead
 * @private
 */
UI.Listbox.prototype._is_item_selected = function(item)
{
	for ( var i = 0; i < this._selected_items.length; i++ )
	{
		if ( item == this._selected_items[i] )
			return true;
	}
	return false;
};



///////////////////////////////////
//
// PAGE SECTION
//
///////////////////////////////////

/**
 * Appends to the root_elem the chunk which holds (a) information
 * about which page of items we're currently on, and (b) controls to
 * change pages
 *
 * @private
 */
UI.Listbox.prototype._append_page_chunk = function()
{
	var self = this;

	// create page chunk
	var page_chunk_elem = this._doc_obj.createElement('DIV');
	Util.Element.add_class(page_chunk_elem, 'page_chunk');

	// create and append prev page elem.
	this._prev_page_elem = this._doc_obj.createElement('A');
	this._prev_page_elem.href = 'javascript:void(0);';
	this._prev_page_elem.onclick = function() { self._goto_prev_page(); return false; };
	this._prev_page_elem.appendChild(this._doc_obj.createTextNode('<< Prev'));
	page_chunk_elem.appendChild(this._prev_page_elem);

	this._page_num_elem = this._doc_obj.createElement('SPAN');
	page_chunk_elem.appendChild(this._page_num_elem);

	// create and append next page elem
	this._next_page_elem = this._doc_obj.createElement('A');
	this._next_page_elem.href = 'javascript:void(0);';
	this._next_page_elem.onclick = function() { self._goto_next_page(); return false; };
	this._next_page_elem.appendChild(this._doc_obj.createTextNode('Next >>'));
	page_chunk_elem.appendChild(this._next_page_elem);

	// append page chunk
	this._root_elem.appendChild(page_chunk_elem);
};

/**
 * Refreshes the page chunk with current information. For example, if
 * a user added a filter and there are now fewer pages than there were
 * before, this causes that to be reflected.
 *
 * TEMP: you might want to just gray out the text, rather than hide
 * the element entirely
 *
 * @private
 */
UI.Listbox.prototype._refresh_page_chunk = function()
{
	var total_num_of_pages = Math.ceil( this._filtered_indices.length / this._num_results_per_page );

	// Calculate displayable cur page num
	if ( total_num_of_pages == 0 )
		displayable_cur_page_num = 0;
	else
		displayable_cur_page_num = this._cur_page_num + 1; // +1 because cur_page_num is zero-based

	// Show or hide prev page elem
	if ( displayable_cur_page_num <= 1 )
		this._prev_page_elem.style.visibility = 'hidden';
	else
		this._prev_page_elem.style.visibility = 'visible';

	// Display the current page number and the total number of pages
	if ( this._page_num_elem.hasChildNodes() )
		this._page_num_elem.removeChild(this._page_num_elem.firstChild);

	this._page_num_elem.appendChild(
		this._doc_obj.createTextNode(' ' + displayable_cur_page_num  + ' of ' + total_num_of_pages + ' ')
	);

	// Show or hide next page elem
	if ( displayable_cur_page_num >= total_num_of_pages )
		this._next_page_elem.style.visibility = 'hidden';
	else
		this._next_page_elem.style.visibility = 'visible';
};

/**
 *
 * Displays the next page of items in items_chunk. Is called onclick
 * of the prev_page_elem.
 *
 * @private
 */
UI.Listbox.prototype._goto_prev_page = function()
{
	this._cur_page_num--;
	this.refresh();
};

/**
 * Displays the previous page of items in items_chunk. Is called
 * onclick of the next_page_elem.
 *
 * @private
 */
UI.Listbox.prototype._goto_next_page = function()
{
	this._cur_page_num++;
	this.refresh();
};
