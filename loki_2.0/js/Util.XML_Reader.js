Util.XML_Reader = function()
{
	this.document;
	this._init();
};

Util.XML_Reader.prototype._init = function()
{
	if ( document.implementation && document.implementation.createDocument)
	{
		this.document = document.implementation.createDocument('', 'doc', null);
	}
	else
	{
		try
		{
			this.document = new ActiveXObject('Microsoft.XMLDOM');
		}
		catch(e)
		{
			throw "Util.XML_Reader._init(): Your browser supports neither the W3C method nor the MS method of creating an XML document.";
		}
	}
	//this.document.async = false; // setting this to true causes Microsoft XMLDOM to fail
};

/**
 * Loads an XML document asynchronously.
 * N.B.: This must be asynchronous, in order to deal with an IE bug
 * involving HTTPS over SSL:
 * <http://support.microsoft.com/kb/272359/en>.
 */
Util.XML_Reader.prototype.load = function(uri)
{
	this.document.load(uri);
};

/**
 * Adds an onload listener to thie XML document. The normal
 * add_event_listener cannot be used because IE doesn't have a load
 * event for xml documents, but instead has an onreadystatechange
 * event.
 * 
 * IMPORTANT NOTE: Right now, this isn't really "add" load listener
 * for IE, but rather "set" load listener. This should be fixed, but
 * it will be a pain, since IE attachEvent doesn't work with document
 * and onreadystatechange.
 *
 * @param	listener	a function which will be called when the event is fired, and which receives as a paramater an
 *                      Event object (or, in IE, a Util.Event.DOM_Event object)
 */
Util.XML_Reader.prototype.add_load_listener = function(listener)
{
	var node = this.document;

	try
	{
		Util.Event.add_event_listener(node, 'load', function() { listener(); });
	}
	catch(e)
	{
		try
		{
			node.onreadystatechange = function() { if ( node.readyState == '4' || node.readyState == 'complete' ) listener(); };
//			Util.Event.add_event_listener(node, 'readystatechange', function() { if ( node.readyState == 4 ) listener(); });
		}
		catch(f)
		{
			throw(new Error('Util.XML.add_load_listener(): Your browser supports neither the W3C method nor the MS method of adding a load listener. ' +
							'When the W3C way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
};
