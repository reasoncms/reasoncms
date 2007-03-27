/**
 * Declares instance variables. <code>this.iframe</code>,
 * <code>this.window</code> <code>this.document</code>, and
 * <code>this.body</code> are not initialized until the method
 * <code>this.open</code> is called.
 *
 * @constructor
 *
 * @class A wrapper to DOM iframe elements. Provides extra and
 * cross-browser functionality.
 */
Util.Iframe = function()
{
	this.iframe_elem;
	this.content_window;
	this.content_document;
	this.body_elem;
};

/**
 * Creates an iframe element and inits instance variables.
 *
 * @param	doc_obj			the document object with which to create the iframe.
 * @param	uri				(optional) the uri of the page to open in the
 *							iframe. Defaults to about:blank, with the result
 *							that no page is initially opened in the iframe.
 * 							NOTE: if you plan to use this behind https, as
 * 							we do Loki, you must specify a uri, not just 
 * 							about:blank, or IE will pop up an alert about
 * 							combining https and http.
 */
Util.Iframe.prototype.init = function(doc_obj, uri)
{
	// Provide defaults for optional arguments
	if ( uri == null || uri == '' )
		// When under https, this causes an alert in IE about combining https and http (see above):
		uri = 'about:blank';

	// Creates iframe
	this.iframe_elem = doc_obj.createElement('IFRAME');

	// Set source
	this.iframe_elem.src = uri;

	this.iframe_elem.onload = function()
	{

		alert('loaded'); return true;

	// Set up reference to iframe's content document
	this.content_window = Util.Iframe.get_content_window(this.iframe_elem);
	this.content_document = Util.Iframe.get_content_document(this.iframe_elem);

	// If we just want to load about:blank, there's no need for an
	// asynchronous call. 
	//
	// By writing the document's initial HTML out ourself and then
	// closing the document (that's the important part), we
	// essentially make the "src" loading synchronous rather than
	// asynchronous. And if we're just trying to open an empty window,
	// this is not dangerous. (It might be dangerous otherwise, since
	// a synchronous "src" loading that involved a request to the web
	// server might cause the script to effectively hang if the web
	// server didn't respond.)
	//
	// If we are given a URI to request from the web server, we skip
	// this, so the loading "src" is asynchronous, so before we do
	// anything with the window's contents, we need to make sure that
	// the content document has loaded. One way to do this is to add a
	// "load" event listener, and then do everything we want to in the
	// listener. Beware, though: this can cause royal
	// (cross-)browser-fucked pains.
	if ( uri == '' )
	{
		this.content_document.write('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' +
									'<html><head><title></title></head><body>' +
									'</body></html>');
		this.content_document.close();

		// We can only set a reference to the body element if the
		// document has finished loading, and here we can only be sure
		// of that across browsers if we've called document.close().
		//
		// One upshot is that if we are given a URI to load in the
		// iframe, we have to wait until the load event is fired to
		// get a reference to the body tag, and I don't want to muck
		// around with that here. So in that case we just don't get
		// such a reference here. (Notice that the assignment below is
		// still in the if block.) You have to get the reference
		// yourself if you want it.
		this.body_elem = this.content_document.getElementsByTagName('BODY').item(0);
	}

	};
};



Util.Iframe.get_content_window = function(iframe_elem)
{
	return iframe_elem.contentWindow;
};

Util.Iframe.get_content_document = function(iframe_elem)
{
	var content_document;

	if ( iframe_elem.contentDocument != null )
	{
		content_document = iframe_elem.contentDocument;
	}
	else if ( iframe_elem.document != null )
	{
		content_document = iframe_elem.contentWindow.document;
	}
	else
	{
		throw new Error('Util.Iframe.get_content_document: Neither the W3C method of accessing ' +
						'the iframe\'s content document ' +
						'nor a workaround for IE worked.');
	}

	return content_document;
};
