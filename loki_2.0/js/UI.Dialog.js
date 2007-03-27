/**
 * Declares instance variables. <code>init</code> must be called to
 * initialize instance variables.
 *
 * @constructor
 *
 * @class Base class for classes which represent dialog windows. Example usage:
 * <p>
 * <pre>
 * var dialog = new UI.Image_Dialog;   <br />
 * dialog.init({ data_source : '/fillmorn/feed.rss',   <br />
 *               submit_listener : this._insert_image,  <br />
 *               selected_item : { link : '/global_stock/images/1234.jpg' }   <br />
 * });   <br />
 * dialog.display();
 * </pre>
 */
UI.Dialog = function()
{
	this._external_submit_listener;
	this._data_source;
	this._base_uri;
	this._initially_selected_item;
	this._dialog_window;
	this._doc;

	this._dialog_window_width = 600;
	this._dialog_window_height = 300;

	/**
	 * Initializes the dialog.
	 *
	 * @param	params	object containing the following named paramaters:
	 *                  <ul>
	 *                  <li>data_source - the RSS feed from which to read this file</li>
	 *                  <li>submit_listener - the function which will be called when
	 *					the dialog's submit button is pressed</li>
	 *                  <li>selected_item - an object with the same properties as
	 *                  the object passed by this._internal_submit_handler (q.v.) to
	 *                  submit_handler (i.e., this._external_submit_handler). Used e.g. to
	 *					determine which if any image is initially selected.</li>
	 *                  </ul>
	 */
	this.init = function(params)
	{
		this._data_source = params.data_source;
		this._base_uri = params.base_uri;
		this._external_submit_listener = params.submit_listener;
		this._remove_listener = params.remove_listener;
		this._initially_selected_item = params.selected_item;

		return this;
	};

	this.open = function()
	{
		if ( this._dialog_window != null && 
			 this._dialog_window.window != null &&
			 this._dialog_window.window.closed != true )
		{
			this._dialog_window.window.focus();
		}
		else
		{
			this._dialog_window = new Util.Window;

			var success = this._dialog_window.open(this._base_uri + 'auxil/loki_blank.html', '_blank', 'status=1,scrollbars=1,toolbars=1,resizable,width=' + this._dialog_window_width + ',height=' + this._dialog_window_height + ',dependent=yes,dialog=yes', Util.Window.FORCE_SYNC);
			if ( !success ) // e.g., the popup was blocked
				return false;

			this._doc = this._dialog_window.document; // added 28/12/2005 NF--do we want this?

			// XXX tmp possibly:
			this._root = this._doc.createElement('DIV');
			this._dialog_window.body.appendChild(this._root);
			
			this._dialog_window.body.style.display = 'none'; // don`t render till we`ve built the document -- fixes IE display bug
			this._set_title();
			this._append_style_sheets();
			this._add_dialog_listeners();
			this._append_main_chunk();
			this._apply_initially_selected_item();
			this._dialog_window.body.style.display = 'block';
		}
	};

	/**
	 * Sets the page title
	 */
	this._set_title = function()
	{
		this._dialog_window.document.title = "Dialog";
	};

	/**
	 * Appends all the style sheets needed for this dialog.
	 */
	this._append_style_sheets = function()
	{
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Dialog.css');
	};

	/**
	 * Adds all the dialog event listeners for this dialog.
	 */
	this._add_dialog_listeners = function()
	{
		var self = this;
		//Util.Event.add_event_listener(this._dialog_window.body, 'keyup', function(event) 
		this._dialog_window.document.onkeydown = function(event)
		{ 
			event = event == null ? self._dialog_window.window.event : event;
			var target = event.srcElement == null ? event.target : event.srcElement;

			// This is too dangerous, because those who use the
			// keyboard to change options might press enter for
			// reasons other than to confirm the dialog.
			/*
			if ( event.keyCode == 13 && // enter
				 !( target != null && target.nodeType == Util.Node.ELEMENT_NODE && ( target.tagName == 'TEXTAREA' || target.tagName == 'BUTTON' ) ) )
			{
				self._internal_submit_listener();	
				return false;
			}
			*/
			
			if ( event.keyCode == 27 ) // escape
			{
				self._internal_cancel_listener();	
				return false;
			}

			// (IE) Disable refresh shortcut
			// [I should think IE and Gecko could be covered
			// together; but can't figure it out right now, tired.]
			if ( event.ctrlKey == true && event.keyCode == 82 ) // ctrl-r
			{
				return false;
			}
		};
		//});
		this._dialog_window.document.onkeypress = function(event)
		{
			event = event == null ? self._dialog_window.window.event : event;
			// (Gecko) Disable refresh shortcut
			if ( event.ctrlKey == true && event.charCode == 114 ) // ctrl-r
			{
				return false;
			}
		};

		/*
		this._dialog_window.window.onbeforeunload = 
		this._dialog_window.document.body.onbeforeunload = function(event)
		{
			event = event == null ? self._dialog_window.window.event : event;
			event.returnValue = "If you do navigate away, your changes in this dialog will be lost, and the dialog may close.";
			return event.returnValue;
		};

		this._dialog_window.window.onunload = function(event)
		{
			self._internal_cancel_listener();
		};
		*/
	};

	/**
	 * Appends the main part of the page, i.e. the children of the body element.
	 */
	this._append_main_chunk = function()
	{
		this._main_chunk = this._dialog_window.document.createElement('FORM');
		this._main_chunk.action = 'javascript:void(0);';
		//this._dialog_window.body.appendChild(this._main_chunk);
		this._root.appendChild(this._main_chunk);

		this._populate_main();
	};

	/**
	 * Stub for adding the main content of the dialog.
	 */
	this._populate_main = function()
	{
		this._append_submit_and_cancel_chunk();
	};

	/**
	 * Creates and appends a chunk containing submit and cancel
	 * buttons. Also attaches 'click' event listeners to the submit and
	 * cancel buttons: this._internal_submit_listener for submit, and
	 * this._internal_cancel_listener for cancel.
	 *
	 * @param	submit_text		(optional) the text to use on the submit button. Defaults to "OK".
	 * @param	cancel_text		(optional) the text to use on the cancel button. Defaults to "Cancel".
	 */
	this._append_submit_and_cancel_chunk = function(submit_text, cancel_text)
	{
		// Init submit and cancel text
		submit_text = submit_text == null ? 'OK' : submit_text;
		cancel_text = cancel_text == null ? 'Cancel' : cancel_text;


		// Setup submit and cancel buttons

		var submit_button = this._dialog_window.document.createElement('BUTTON');
		var cancel_button = this._dialog_window.document.createElement('BUTTON');

		submit_button.setAttribute('type', 'button');
		cancel_button.setAttribute('type', 'button');

		submit_button.appendChild( this._dialog_window.document.createTextNode(submit_text) );
		cancel_button.appendChild( this._dialog_window.document.createTextNode(cancel_text) );

		var self = this;
		Util.Event.add_event_listener(submit_button, 'click', function() { self._internal_submit_listener(); });
		Util.Event.add_event_listener(cancel_button, 'click', function() { self._internal_cancel_listener(); });

		Util.Element.add_class(submit_button, 'ok');
		

		// Setup their containing chunk
		var submit_and_cancel_chunk = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(submit_and_cancel_chunk, 'submit_and_cancel_chunk');
		submit_and_cancel_chunk.appendChild(cancel_button);
		submit_and_cancel_chunk.appendChild(submit_button);


		// Append their containing chunk
		//this._dialog_window.body.appendChild(submit_and_cancel_chunk);
		this._root.appendChild(submit_and_cancel_chunk);
	};

	/**
	 * Apply the initially selected item. Extending functions should do things
	 * like setting the link_input's value to the initially_selected_item's uri.
	 */
	this._apply_initially_selected_item = function()
	{
	};

	/**
	 * This resizes the window to its content. 
	 *
	 * @param	horizontal	(boolean) Sometimes we don't want to resize horizontally 
	 *						to the content, because since the content is not fixed-width, 
	 * 						it will expand to take up the whole screen, which is ugly. So
	 *						false here disables horiz resize.
	 * @param	vertical	(boolean) same thing
	 *						
	 */
	this._resize_dialog_window = function(horizontal, vertical)
	{
		// Skip IE // XXX bad
		if ( document.all )
			return;

		if ( horizontal == null )
			horizontal = true;
		if ( vertical == null )
			vertical = true;

		// From NPR.org
		var win = this._dialog_window.window;
		var doc = this._dialog_window.document;

		if (win.sizeToContent)  // Gecko
		{
			var w = win.outerWidth;
			var h = win.outerHeight;

            //win.resizeBy(win.innerWidth * 2, win.innerHeight * 2);
			//win.sizeToContent();  
			//win.sizeToContent();  
			//win.resizeBy(win.innerWidth + 10, win.innerHeight + 10);
			win.resizeBy(doc.documentElement.clientWidth + 10 + (win.outerWidth - win.innerWidth) - win.outerWidth, 
					     doc.documentElement.clientHeight + 20 + (win.outerHeight - win.innerHeight) - win.outerHeight);
			//win.resizeBy(this._root.clientWidth + 10 - win.outerWidth, 
			//			 this._root.clientHeight + 10 - win.outerHeight);
			//win.resizeBy(win.innerWidth + 10 - win.outerWidth, 
			//			 win.innerHeight + 10 - win.outerHeight);
			//win.resizeBy(10,0); 

/*
		try {
			win.scrollBy(1000, 1000);
			if (win.scrollX > 0 || win.scrollY > 0) {
				win.resizeBy(win.innerWidth * 2, win.innerHeight * 2);
				win.sizeToContent();
				win.scrollTo(0, 0);
				var x = parseInt(screen.width / 2.0) - (win.outerWidth / 2.0);
				var y = parseInt(screen.height / 2.0) - (win.outerHeight / 2.0);
				win.moveTo(x, y);
			}
			mb('resized dialog');
		} catch(e) { mb('error in resize_dialog_window:' + e.message); throw(e); }
*/


			if ( !horizontal )
				win.outerWidth = w;
			if ( !vertical )
				win.outerWidth = h;
		}
		else  // IE
		{  
			//old ie method, doesn't work for dialogs:
			win.resizeTo(100,100);  
			docWidth = Math.max(this._main_chunk.offsetWidth + 70, 200);  
			docHeight = Math.max(this._main_chunk.offsetHeight + 40, doc.body.scrollHeight) + 18;
			win.resizeTo(docWidth,docHeight);
			// not tested yet ...:
/*
			docWidth = Math.max(this._main_chunk.offsetWidth + 70, 200);  
			docHeight = Math.max(this._main_chunk.offsetHeight + 40, doc.body.scrollHeight) + 18;
			if ( horizontal )
				win.dialogWidth = docWidth;
			if ( vertical )
				win.dialogHeight = docHeight;
*/
		}
	};

	/**
	 * Called as an event listener when the user clicks the submit
	 * button. Extending functions should (a) gather information needed
	 * to call the function referenced by this._submit_listener, (b) call
	 * that function, and (c) close this dialog.
	 */
	this._internal_submit_listener = function()
	{
		// Close dialog window
		this._dialog_window.window.close();
	};

	/**
	 * Called as an event listener when the user clicks the submit
	 * button. Extending functions may (a) gather information needed to
	 * call the function referenced by this._submit_listener, and (b) call
	 * that function. They should (c) close this dialog.
	 */
	this._internal_cancel_listener = function()
	{
		// Close dialog window
		this._dialog_window.window.close();
	};
};
