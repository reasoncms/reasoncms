Util.HTTP_Reader = function()
{
	this._load_listeners = [];
};

/**
 * Loads http(s) data asynchronously.
 * N.B.: This must be asynchronous, in order to deal with an IE bug
 * involving HTTPS over SSL:
 * <http://support.microsoft.com/kb/272359/en>.
 *   (Not sure this is true for XMLHTTP--but async makes much more
 *   sense usually, anyway, so the app doesn't hang.)
 *
 * See <http://developer.apple.com/internet/webcontent/xmlhttpreq.html>
 * for good overview.
 *
 * The actual XMLHttpRequest object will be available as this.request.
 *
 * XXX: This code is icky! Use Util.Request. -EN
 *
 * @param	uri				The URI to load
 * @param	post_data		(optional) string containing post data
 */
Util.HTTP_Reader.prototype.load = function(uri, post_data)
{
	if (window.XMLHttpRequest)
	{
		this.request = new XMLHttpRequest();
	}
	else
	{
		try
		{
			this.request = new ActiveXObject('Microsoft.XMLHTTP');
		}
		catch(e)
		{
			throw "Util.HTTP_Reader.load: Your browser supports neither the W3C method nor the MS method of reading data over http.";
		}
	}
	
	this._really_add_load_listeners();
	
	if (post_data) {
		this.request.open('POST', uri, true);
		this.request.send(post_data);
	} else {
		this.request.open('GET', uri, true);
		this.request.send();
	}
};

/**
 * Adds an onload listener to the data. The normal
 * add_event_listener cannot be used because IE doesn't have a load
 * event for xml documents, but instead has an onreadystatechange
 * event.
 *
 * @param	listener	a function which will be called when the event is fired, and which receives as a paramater the
 *                      request object
 */
Util.HTTP_Reader.prototype.add_load_listener = function(listener)
{
	this._load_listeners.push(listener);
}

Util.HTTP_Reader.prototype._really_add_load_listeners = function()
{
	var self = this;
	
	this.request.onreadystatechange = function()
	{
		var state = self.request.readyState;
		if (state == 4 || state == 'complete') {
			for (var i = 0; i < self._load_listeners.length; i++) {
				self._load_listeners[i](self.request);
			}
		}
	}
};
