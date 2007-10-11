/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Reads items from an RSS feed, but gets them only one page at a time.
 * 
 * -- DEPRECATED by Util.RSS.Reader! --
 *
 * New structure:
 * - Variables
 *   - Private
 *     - _uri
 *     - _start
 *     - _num
 *     - _num
 *     - _load_listeners
 *   - Public
 * - These may be called any time
 *   - load_next_items
 * - These must not be called until the xml file has loaded.
 *   - get_cur_items_as_node_list 
 *   - get_cur_items_as_array
 *
 */
Util.RSS_Reader = function()
{
	this._uri;
	this._start;
	this._num;
	this._max;
	this._load_listeners;
	this._xml_reader;
};

/**
 * Initializes instance variables.
 *
 * @param	uri		the uri of the RSS feed. Warning: the given URI <strong>must</strong>
 *                  accept as get variables <code>start</code> (specifying the index of
 *                  the first item to get) and <code>num</code> (specifying the number of
 *                  items to get).
 * @param	num		the number of items to get at a time. Defaults to 10000.
 * @param	max		(optional) the maximum number of items to get total (prevents infinite
 *                  loops). Defaults to 10000.
 */
Util.RSS_Reader.prototype.init = function(uri, num, max)
{
	this._uri = uri;
	this._start = 0;
	this._num = num != null ? num : 10000;
	this._max = max != null ? max : 10000;
	this._load_listeners = Array();
	return this;
};

Util.RSS_Reader.prototype.load_next_items = function()
{
	// If the maximum number of items have already been gotten, return
	// false. In this case, the load listeners are therefore never
	// called.
	if ( this._start >= this._max )
	{
		return false;
	}
	else
	{
		// If the difference between the maximum number of items to
		// get and the number of items already gotten is less than the
		// default number of items to get at a time, only get as many
		// as are needed to reach the maximum
		if ( this._max - this._start < this._num )
			var num = this._max - this._start;
		else
			var num = this._num;

		// Build the uri
		var uri;
		if ( this._uri.indexOf('?') > -1 )
			uri = this._uri + '&start=' + this._start + '&num=' + num;
		else
			uri = this._uri + '?start=' + this._start + '&num=' + num;

		// Load the feed
		this._xml_reader = new Util.XML_Reader;
		for ( var i = 0; i < this._load_listeners.length; i++ )
		{
			this._xml_reader.add_load_listener(this._load_listeners[i]);
		}
		this._xml_reader.load(uri);

		// Increment starting index and max index
		this._start += num;
	}
};

/**
 * Adds a listener fired when the reader finishes loading a
 * set of items.
 *
 * @param	listener	the listener function
 */
Util.RSS_Reader.prototype.add_load_listener = function(listener)
{
	this._load_listeners.push(listener);
};

/**
 * Returns the current set of items as a W3CDOM NodeList.
 *
 * @return	a <code>NodeList</code> containing references to
 *			<code>item</code> elements.
 */
Util.RSS_Reader.prototype.get_cur_items_as_node_list = function()
{
	return this._xml_reader.document.getElementsByTagName('item');
};

/**
 * Returns the current set of items as an array of generic objects.
 *
 * @return	an array of generic objects, each of which has properties
 *			corresponding to the child nodes of the each RSS
 *			<code>item</code> Element
 */
Util.RSS_Reader.prototype.get_cur_items = function()
{
	// Get a NodeList containing the next items, and create an array for them
	var item_elems = this.get_cur_items_as_node_list();
	var items = new Array(item_elems.length);

	// For each item element, create a generic item object.
	for ( var i = 0; i < item_elems.length; i++ )
	{
		var item_elem = item_elems.item(i);
		var item = new Object;

		// For each child element of the current item element, create a property of
		// the current generic item object whose name is the tagName of the child
		// element, and whose value is the innerHTML of the child element.
		for ( var j = 0; j < item_elem.childNodes.length; j++ )
		{
			var child_node = item_elem.childNodes.item(j);
			if ( child_node.nodeType == Util.Node.ELEMENT_NODE )
			{
				if ( child_node.firstChild != null )
					item[child_node.tagName] = child_node.firstChild.nodeValue;
				else
					item[child_node.tagName] = '';
			}
		}

		items[i] = item;
	}

	// Return the array of generic item objects
	return items;
};
