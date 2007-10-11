/**
 * @class Home to RSS-related facilities.
 */
Util.RSS = {
	
}

/**
 * @class A replacement for Util.RSS_Reader that doesn't suck quite so much.
 *
 * @constructor Creates a new RSS 2.0 feed reader for the given URL.
 * @param	url	The URL of the RSS feed. You may pass in a function returning the URI instead
 *				of the URL itself. To permit the chunking of results, this function must accept
 *				two parameters: the offset to start on will be passed in as the first parameter
 *				and the number of items to retrieve will be passed in as the second.
 *
 * @author Eric Naeseth
 */
Util.RSS.Reader = function(url)
{
	this.url = url;
	
	var offset = 0;
	var listeners = {
		load: [],
		error: [],
		timeout: []
	};
	var aborted = false;
	
	this.feed = null;
	
	function handle_result(document)
	{
		if (aborted || !document)
			return;
		
		var rss = document.documentElement;
		var channel = (function() {
			try {
				return rss.getElementsByTagName('channel')[0];
			} catch (e) {
				handle_error('RSS feed lacks a channel element!', 0);
			}
		})();
		var items = rss.getElementsByTagName('item');
		
		function get_text(node)
		{
			var text = null;
			
			for (var i = 0; i < node.childNodes.length; i++) {
				var child = node.childNodes[i];
				if (child.nodeType == Util.Node.TEXT_NODE) {
					if (text)
						text = text + child.nodeValue;
					else
						text = child.nodeValue;
				}
			}
			
			return text || '';
		}
		
		function get_text_child(container, name)
		{
			var nodes = container.getElementsByTagName(name);
			return (nodes.length == 0)
				? null
				: get_text(nodes[0]);
		}
		
		function to_number(text)
		{
			return (!text || text.length == 0)
				? null
				: new Number(text);
		}
		
		function to_date(text)
		{
			return (text && text.length > 0)
				? new Date(text)
				: null;
		}
		
		if (!this.feed) {
			this.feed = new Util.RSS.Feed();
			this.feed.version = rss.getAttribute('version');
			
			this.feed.channel = new Util.RSS.Channel();
			var channel_object = this.feed.channel;
			channel_object.title = get_text_child(channel, 'title');
			channel_object.link = get_text_child(channel, 'link');
			channel_object.description = get_text_child(channel, 'description');
			channel_object.language = get_text_child(channel, 'language');
			channel_object.copyright = get_text_child(channel, 'copyright');
			channel_object.managing_editor = get_text_child(channel, 'managingEditor');
			channel_object.webmaster = get_text_child(channel, 'webMaster');
			channel_object.publication_date = to_date(get_text_child(channel, 'pubDate'));
			channel_object.last_build_date = to_date(get_text_child(channel, 'lastBuildDate'));
			channel_object.category = get_text_child(channel, 'category');
			channel_object.generator = get_text_child(channel, 'generator');
			channel_object.docs = get_text_child(channel, 'docs');
			channel_object.time_to_live = to_number(get_text_child(channel, 'ttl'));
			channel_object.rating = get_text_child(channel, 'rating');
		}
		
		var new_items = [];
		var item_elements = channel.getElementsByTagName('item');
		
		function get_source(node)
		{
			try {
				return {
					name: get_text(node),
					url: node.getAttribute('url')
				};
			} catch (e) {
				return null;
			}
		}
		
		function get_enclosure(node)
		{
			try {
				return {
					url: node.getAttribute('url'),
					length: to_number(node.getAttribute('length')),
					type: node.getAttribute('type')
				};
			} catch (e) {
				return null;
			}
		}
		
		for (var i = 0; i < item_elements.length; i++) {
			var item = item_elements[i];
			var item_object = new Util.RSS.Item();
			
			for (var j = 0; j < item.childNodes.length; j++) {
				var node = item.childNodes[j];
				
				if (node.nodeType != Util.Node.ELEMENT_NODE)
					continue;
				
				var nn = node.nodeName;
				if (nn == 'pubDate') {
					item_object.publication_date = to_date(get_text(node));
				} else if (nn == 'source') {
					item_object.source = get_source(node);
				} else if (nn == 'enclosure') {
					item_object.enclosure = get_enclosure(node);
				} else {
					item_object[nn] = get_text(node);
				}
			}
			
			new_items.push(item_object);
			this.feed.items.push(item_object);
		}
		
		listeners.load.each(function(l) {
			l(this.feed, new_items);
		}.bind(this));
	}
	
	function handle_error(message, code)
	{
		if (aborted)
			return;
		
		listeners.error.each(function(l) {
			l(message, code);
		});
	}
	
	function handle_timeout()
	{
		listeners.timeout.each(function (l) {
			l('Operation timed out.', 0);
		});
	}
	
	/**
	 * Adds an event listener.
	 */
	this.add_event_listener = function(type, func)
	{
		if (!listeners[type]) {
			throw new Error('Unknown listener type "' + type + '".');
		}
		
		listeners[type].push(func);
		return true;
	}
	
	/**
	 * Loads items from the feed. If the "num" parameter is provided and the URL has been set up
	 * to support chunking (see description of the construtor), only requests that many items.
	 */
	this.load = function(num, timeout)
	{
		if (!num)
			var num = null;
		if (!timeout)
			var timeout = null;
			
		aborted = false;
		
		var url = (typeof(this.url) == 'function')
			? (num ? this.url(offset, num) : this.url())
			: this.url;
		
		this.request = new Util.Request(url, {
			method: 'GET',
			timeout: timeout,
			
			on_success: function(req, t) {
				if (aborted)
					return;
				if (!(t.responseXML && t.responseXML.documentElement.nodeName == 'rss')) {
					handle_error('Server did not respond with an RSS document.', 0);
				}
				handle_result.call(this, t.responseXML); 
			}.bind(this),
			
			on_failure: function(req, transport) {
				handle_error(req.get_status_text(), req.get_status());
			},
			
			on_abort: function(req, transport) {
				aborted = true;
			},
			
			on_timeout: function(req, transport) {
				if (listeners.timeout.length > 0) {
					aborted = true;
					handle_timeout();
				} else {
					handle_error(req.get_status_text(), req.get_status());
					aborted = true;
				}
			}
		});
	}
}

/**
 * @constructor Creates a new feed object.
 *
 * @class An RSS feed.
 * @author Eric Naeseth
 */
Util.RSS.Feed = function()
{
	this.version = null;
	this.channel = null;
	this.items = [];
}

/**
 * @constructor Creates a new channel object.
 *
 * @class An RSS channel.
 * @author Eric Naeseth
 */
Util.RSS.Channel = function()
{
	// required elements
	this.title = null;
	this.link = null;
	this.description = null;
	
	// optional elements
	this.language = null;
	this.copyright = null;
	this.managing_editor = null;
	this.webmaster = null;
	this.publication_date = null;
	this.last_build_date = null;
	this.category = null;
	this.generator = null;
	this.docs = null;
	this.cloud = null;
	this.time_to_live = null;
	this.image = null;
	this.rating = null;
	this.text_input = null;
	this.skip_hours = null;
	this.skip_days = null;
}

/**
 * @constructor Creates a new feed object.
 *
 * @class An RSS feed.
 * @author Eric Naeseth
 */
Util.RSS.Item = function()
{
	this.title = null;
	this.link = null;
	this.description = null;
	this.author = null;
	this.category = null;
	this.comments = null;
	this.enclosure = null;
	this.guid = null;
	this.publication_date = null;
	this.source = null;
}