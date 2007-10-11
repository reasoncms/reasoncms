/**
 * Declares instance variables. <code>this.window</code>,
 * <code>this.document</code>, and <code>this.body</code> are not
 * initialized until the method <code>this.open</code> is called.
 *
 * @constructor
 *
 * @class A wrapper to <code>window</code>. Provides extra and
 * cross-browser functionality.
 */
Util.Window = function()
{
	this.window = null;
	this.document = null;
	this.body = null;
};
Util.Window.FORCE_SYNC = true;
Util.Window.DONT_FORCE_SYNC = false;

/**
 * Opens a window.
 *
 * @param	uri				(optional) the uri of the page to open in the
 *							window. Defaults to empty string, with the result
 *							that no page is initially opened in the window.
 *							But NOTE: if you leave this blank, if this is called 
 * 							from a page under https IE will complain about mixing 
 *							https and http.
 * @param	window_name		(optional) the name of the window. Defaults to
 *							'_blank'.
 * @param	window_options	(optional) a string of options as to how the window
 *                          is displayed. This is the same string as is passed
 *                          to window.open. Defaults to a fairly minimal set of
 *                          options.
 * @param	force_async		(optional) if Util.Window.FORCE_ASYNC, forces the 
 * 							function to write over the document at uri with a blank 
 * 							page and close the new document, even if uri isn't ''. This is
 *							useful if we're behind https, since setting the uri
 *							to '' from an https page causes IE to warn the user
 *							about mixing https and http.
 * @return					returns false if we couldn't open the window (e.g.,
 *							if it was blocked), or true otherwise
 */
Util.Window.prototype.open = function(uri, window_name, window_options, force_sync)
{
	// Provide defaults for optional arguments
	if ( uri == null )
		uri = '';

	if ( window_name == null )
		window_name = '_blank';

	if ( window_options == null )
		window_options = 'status=1,scrollbars=1,resizable,width=600,height=300';
	
	if ( force_sync == null )
		force_sync = Util.Window.DONT_FORCE_SYNC;

	// Open window
	this.window = window.open(uri, window_name, window_options);

	// Make sure the window opened successfully
	if ( this.window == null )
	{
		alert('I couldn\'t open a window. Please disable your popup blocker for this page. Then give me another try.');
		return false;
	}

	// Set up reference to window's document
	this.document = this.window.document;

	// By writing the document's initial HTML out ourself and then
	// closing the document (that's the important part), we
	// essentially make the "open" method synchronous rather than
	// asynchronous. And if we're just trying to open an empty window,
	// this is not dangerous. (It might be dangerous otherwise, since
	// a synchronous "open" method that involved a request to the web
	// server might cause the script to effectively hang if the web
	// server didn't respond.)
	//
	// If we are given a URI to request from the web server, we skip
	// this, so the "open" method is asynchronous, so before we do
	// anything with the window's contents, we need to make sure that
	// the content document has loaded. One way to do this is to add a
	// "load" event listener, and then do everything we want to in the
	// listener. Beware, though: this can cause extreme 
	// cross-browser pains.
	if ( uri == '' || force_sync == Util.Window.FORCE_SYNC )
	{
		this.document.write('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' +
							'<html><head><title></title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head><body>' +
							'<div id="util_window_error">You tried to reload a dialog page that exists only ephemerally. Please close the dialog and open it again.</div>' +
							// for debugging; turn off when live (make sure to get the event listener below, too):
							//'<div><a id="util_window_alert" href="#" onclick="return false;">View virtual source</a></div><hr />' + // the event which pops up the source is added below
							'</body></html>');
		this.document.close();

		// We can only set a reference to the body element if the
		// document has finished loading, and here we can only be sure
		// of that across browsers if we've called document.close().
		//
		// One upshot is that if we are given a URI to load in the
		// window, we have to wait until the load event is fired to
		// get a reference to the body tag, and I don't want to muck
		// around with that here. So in that case we just don't get
		// such a reference here. (Notice that the assignment below is
		// still in the if block.) You have to get the reference
		// yourself if you want it.
		this.body = this.document.getElementsByTagName('BODY').item(0);

		// We also add an onclick event to view source which uses
		// Util.Window.alert, not window.alert
		//var self = this;
		//Util.Event.add_event_listener(this.document.getElementById('util_window_alert'), 'click', function() { Util.Window.alert(self.document.getElementsByTagName('html').item(0).innerHTML); });

		// We need the error message because if people do things like
		// press refresh, they just get what's written by
		// document.write above, and that is very confusing. The
		// following line hides the message except after they've
		// pressed reload, because none of this is run on reload.
		this.document.getElementById('util_window_error').style.display = 'none';

// 		// for debugging; turn off when live:
// 		var a = this.document.createElement('DIV');
// 		a.appendChild( this.document.createTextNode('View virtual source') );
// 		a.href = '#';
// 		var self = this;
// 		var handler = function() { Util.Window.alert(self.body.innerHTML); }
// 		Util.Event.add_event_listener(a, 'click', function() { handler(); });
// 		this.body.appendChild(a);
	}

	return true; // success
};


Util.Window.prototype.add_load_listener = function(listener)
{
		mb('Util.Window.add_load_listener: this', this);
	Util.Event.add_event_listener(this.document, 'load', listener);
};


/**
 * Alerts a message. Supercedes window.alert, since allows scrolling,
 * accepts document nodes rather than just strings, etc.
 *
 * @param	alertandum	the string or document chunk (i.e., node with
 *                      all of its children) to alert
 * @static
 */ 
Util.Window.alert = function(alertandum)
{
	// Open window
	var alert_window = new Util.Window;
	alert_window.open('', '_blank', 'status=1,scrollbars=1,resizable,width=600,height=300');

	// Add the alertatandum to a document chunk
	var doc_chunk = alert_window.document.createElement('DIV'); // use a div because document frags don't work as expected on IE
	if ( typeof(alertandum) == 'string' )
	{
		var text = alertandum.toString();
		var text_arr = text.split("\n");
		for ( var i = 0; i < text_arr.length; i++ )
		{
			doc_chunk.appendChild(
				alert_window.document.createElement('DIV')
			).appendChild(
				alert_window.document.createTextNode(text_arr[i].toString())
			);
		}
	}
	else
	{
		// FIXME: leftover debugging crud
		// alert(alertandum.firstChild.firstChild.firstChild.nodeValue);
		doc_chunk.appendChild(
			Util.Document.import_node(alert_window.document, alertandum, true)
		);
		alert(doc_chunk.firstChild.nodeName);
	}

	// Append the document chunk to the window
	alert_window.body.appendChild(doc_chunk);
};

Util.Window.alert_debug = function(message)
{
	var alert_window = new Util.Window;
	alert_window.open('', '_blank', 'status=1,scrollbars=1,resizable,width=600,height=300');
	
	var text_chunk = alert_window.document.createElement('P');
	text_chunk.style.fontFamily = 'monospace';
	text_chunk.appendChild(alert_window.document.createTextNode(message));
	alert_window.body.appendChild(text_chunk);
}