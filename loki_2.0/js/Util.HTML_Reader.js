/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Reads an HTML file and exposes its document, without
 * displaying it.
 *
 * Structure:
 * - These may be called any time
 *   - add_load_listener
 *   - load
 *   - destroy
 * - These must not be accessed till after load
 *   - document
 *
 */
Util.HTML_Reader = function()
{
	/**
	 * @param	document 	(optional) the document. Defaults to global document.
	 * @param 	blank_uri	(optional) the uri to use as a blank uri, which is displayed 
	 * 						for an instant before whatever uri is passed to this.load
	 * 						is displayed. Defaults to about:blank.
	 *						But NOTE: if blank_uri is left as about:blank, when called
	 * 						from a page under https IE will complain about mixing 
	 *						https and http.
	 */
	this.init = function(params)
	{
		if (typeof(params) == 'undefined')
			var params = {};
		
		this._owner_document = params.document == null ? document : params.document;
		this._blank_uri = params.blank_uri == null ? 'about:blank' : params.blank_uri;
		this._load_listeners = new Array();

		return this;
	};

	this.add_load_listener = function(listener)
	{
		this._load_listeners.push(listener);
	};

	this.load = function(uri)
	{
		if ( this._iframe == null )
			this._append_iframe(uri);

		this._iframe.src = uri;
	};

	/**
	 * If you load a large document, you might want to call this when
	 * you're done with it to free up memory.
	 */
	this.destroy = function()
	{
		//this._iframe.parentNode.removeChild(this._iframe);
		this._iframe = null;
		this.window = null; // not sure these are necessary, but it doesn't hurt
		this.document = null;
	};

	this._fire_listeners = function()
	{
		this.window = this._iframe.contentWindow;
		this.document = this.window.document;

		for ( var i = 0; i < this._load_listeners.length; i++ )
			this._load_listeners[i]();
	};

	this._append_iframe = function()
	{
		this._iframe = this._owner_document.createElement('IFRAME');
		//this._iframe.setAttribute('style', 'height:1px; width:1px; display:none;');
/*
		this._iframe.style.height = '2px';
		this._iframe.style.width = '2px';
		this._iframe.style.left = '-500px';
		this._iframe.style.position = 'absolute';
*/
		var self = this;
		this._iframe.onload = function() { self._fire_listeners() };
		this._iframe.onreadystatechange = function() 
		{
			if ( self._iframe.readyState == 'complete' )
				self._fire_listeners();
		};
		mb('this._blank_uri: ', this._blank_uri);
		this._iframe.uri = this._blank_uri;
		this._owner_document.body.appendChild(this._iframe);
	};
};
