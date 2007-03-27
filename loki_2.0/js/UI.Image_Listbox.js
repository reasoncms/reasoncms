/**
 * Does nothing, since all necessary instance variables are declared in Listbox's constructor.
 *
 * @constructor
 *
 * @class Represents a listbox for images. This was designed for use
 * in Loki's image-insertion dialog box, but may be useful for other
 * applications.
 */
UI.Image_Listbox = function()
{
	//YOU ARE HERE: you need to copy all the below manually from
	//UI.Listbox, otherwise you can't have multiple injstantiations.
	//This is because UI.Image_Listbox.prototype = new UI.Listbox only
	//happens once. Whis is rather obnoxious.
	//
	// XXX: this can easily be fixed using Util.OOP. do it


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
UI.Image_Listbox.prototype = new UI.Listbox;

/**
 * Creates the document chunk for each item. Differs from
 * Listbox._create_item_chunk in that it displays the image at
 * <code>item.link</code>. Requires that each <code>item</code> contain
 * at least <code>title</code>, <code>description</code>, and
 * <code>link</code> properties.
 *
 * @param	item	the item from which to create the chunk
 * @private
 */
UI.Image_Listbox.prototype._create_item_chunk = function(item)
{
	//var item_chunk = this._doc_obj.createElement('DIV');
	var item_chunk = this._doc_obj.createElement('A');
	item_chunk.href = 'javascript:void(0);';
	Util.Element.add_class(item_chunk, 'item_chunk');

	// Image
	var image_elem = this._doc_obj.createElement('IMG');
	var src = Util.URI.strip_https_and_http(item.link);
	image_elem.setAttribute('src', src);
	image_elem.setAttribute('alt', '[Image: ' + item.title + ']');
	Util.Image.set_max_size(image_elem, 125, 125); // this needs to be here for IE, and in the load handler for Gecko
	Util.Event.add_event_listener(image_elem, 'load', function() { Util.Image.set_max_size(image_elem, 125, 125); });
	item_chunk.appendChild(image_elem);

	// Title
	var title_elem = this._doc_obj.createElement('DIV');

	var title_label_elem = this._doc_obj.createElement('STRONG');
	title_elem.appendChild(title_label_elem);

	var title_value_elem = this._doc_obj.createElement('SPAN');
	title_value_elem.appendChild(
		this._doc_obj.createTextNode(item.title)
	);
	title_elem.appendChild(title_value_elem);

	item_chunk.appendChild(title_elem);

	return item_chunk;
};

/**
 * Modify the item chunk as appropriate for its place in the set of
 * currently displayed items. In particular, we need to add a class to
 * every third item_chunk.
 *
 * @param	item_chunk	the item_chunk to modify
 * @param	cur_i		the index of this item in relation to other items
 *                      in the current display
 */
UI.Image_Listbox.prototype._modify_item_chunk = function(item_chunk, cur_i)
{
	if ( cur_i % 4 == 0 )
	{
		var doc = item_chunk.ownerDocument;
		var spacer_elem = doc.createElement('DIV');
		Util.Element.add_class(spacer_elem, 'force_clear_for_ie');
		item_chunk.parentNode.insertBefore(spacer_elem, item_chunk);
//		Util.Element.add_class(item_chunk, 'force_clear_for_ie');
	}
};
