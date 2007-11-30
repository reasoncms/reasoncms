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
	Util.OOP.inherits(this, UI.Listbox);
	
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
	this._create_item_chunk = function(item)
	{
		function use_enclosure_url()
		{
			if (!item.enclosure || !item.enclosure.type || !item.enclosure.url)
				return false;
			
			return item.enclosure.type.match(/^image\//);
		}
		
		//var item_chunk = this._doc_obj.createElement('DIV');
		var item_chunk = this._doc_obj.createElement('A');
		item_chunk.href = 'javascript:void(0);';
		Util.Element.add_class(item_chunk, 'item_chunk');

		// Image
		var image_elem = this._doc_obj.createElement('IMG');
		var uri = (use_enclosure_url())
			? item.enclosure.url
			: item.link;
		var src = Util.URI.strip_https_and_http(uri);
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
	}
	
	/**
	 * Modify the item chunk as appropriate for its place in the set of
	 * currently displayed items. In particular, we need to add a class to
	 * every third item_chunk.
	 *
	 * @param	item_chunk	the item_chunk to modify
	 * @param	cur_i		the index of this item in relation to other items
	 *                      in the current display
	 */
	this._modify_item_chunk = function(item_chunk, cur_i)
	{
		if ( cur_i % 4 == 0 )
		{
			var doc = item_chunk.ownerDocument;
			var spacer_elem = doc.createElement('DIV');
			Util.Element.add_class(spacer_elem, 'force_clear_for_ie');
			item_chunk.parentNode.insertBefore(spacer_elem, item_chunk);
	//		Util.Element.add_class(item_chunk, 'force_clear_for_ie');
		}
	}
};
