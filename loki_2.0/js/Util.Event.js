/**
 * Does nothing.
 *
 * @class A container for functions relating to events. (Not that it
 * matters much, but it makes sense for even functions that work
 * primarily on something other than an event (for example,
 * add_event_listener works primarily on a node) to be in here rather
 * than elsewhere (for example, Util.Node) because all evente-related
 * function are in the DOM2+ standards defined in non-core modules,
 * i.e.
 */
Util.Event = function()
{
};

/**
 * Creates a wrapper around a function that ensures it will always be called
 * with the event object as its sole parameter.
 *
 * @param	func	the function to wrap
 */
Util.Event.listener = function(func)
{	
	return function(e)
	{
		if (!e)
			var e = window.event;
		
		return func(e);
	};
}

/**
 * Adds an event listener to a node. 
 * <p>
 * N.B., for reference, that it is dangerous in IE to attach as a
 * listener a public method of an object. (The browser may crash.) See
 * Loki's Listbox.js for a workaround.
 *
 * @param	node		the node to which to add the event listener
 * @param	type		a string indicating the type of event to listen for, e.g. 'click', 'mouseover', 'submit', etc.
 * @param	listener	a function which will be called when the event is fired, and which receives as a paramater an
 *                      Event object (or, in IE, a Util.Event.DOM_Event object)
 */
Util.Event.add_event_listener = function(node, type, listener)
{
	try
	{
		//bubble = bubble == null ? false : bubble;
		//node.addEventListener(type, listener, bubble);
		node.addEventListener(type, listener, false);
	}
	catch(e)
	{
		try
		{
// 			node.attachEvent('on' + type, function() { listener(new Util.Event.DOM_Event(node)); });
			node.attachEvent('on' + type, listener);
		}
		catch(f)
		{
			throw(new Error('Util.Event.add_event_listener(): Neither the W3C nor the IE way of adding an event listener worked. ' +
							'When the W3C way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
};

/**
 * Removes an event listener from a node. Doesn't work at present.
 *
 * @param	node		the node from which to remove the event listener
 * @param	type		a string indicating the type of event to stop listening for, e.g. 'click', 'mouseover', 'submit', etc.
 * @param	listener	the listener function to remove
 */
Util.Event.remove_event_listener = function(node, type, listener)
{
	try
	{
		node.removeEventListener(type, listener, false); // I think that with "false" this is equivalent to the IE way below
	}
	catch(e)
	{
		try
		{
			node.detachEvent('on' + type, listener);
		}
		catch(f)
		{
			throw(new Error('Util.Event.remove_event_listener(): Neither the W3C nor the IE way of removing an event listener worked. ' +
							'When the W3C way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
};

/**
 * Calls the listeners which have been "attached" to the
 * event.currentTarget using add_event_listener. This function is
 * intended for use primarily by add_event_listener.
 *
 * @param	event	the event object, to pass to the listeners
 */
Util.Event.call_wrapped_listeners = function(event)
{
	var node = event.currentTarget;
	var type = event.type;
	var listener, extra_args;

	for ( var i = 0; i < node.Event__listeners[type].length; i++ )
	{
		listener = node.Event__listeners[type][i]['listener'];
		extra_args = node.Event__listeners[type][i]['extra_args'];

		listener(event, extra_args);
	}
};

/**
 * Constructor for a mimic'd DOM Event object, primarly for use in the
 * IE version of Util.Event.add_event_listener. Properties which are
 * initialized below to null are in the W3C spec but haven't yet
 * needed to be implemented in this mimic'd object.
 *
 * @param	currentTarget	the document node which is the target of the event
 * @param	type			the type of the event, e.g. 'click'
 */
Util.Event.IE_DOM_Event = function(currentTarget, type)
{
	this.type = type;
// 	this.target = window.event.srcElement; // doesn't work if the event's target belongs to another window than the one referenced by "window", e.g. a popup window
	this.currentTarget = currentTarget;
	this.eventPhase = null;
	this.bubbles = null;
	this.cancelable = null;
	this.timeStamp = null;
	this.initEvent = null;
	this.initEvent = function(eventTypeArg, canBubbleArg, cancelableArg) { return null; };
	this.preventDefault = function() { window.event.returnValue = false; };
	this.stopPropogation = function() { window.event.cancelBubble = true; };
};

Util.Event.prevent_default = function(event)
{
	try // W3C
	{
		event.preventDefault();
	}
	catch(e)
	{
		try // IE
		{
			//event = event == null ? _window.event : event;
			event.returnValue = false;
			event.cancelBubble = true;
			//return false;
		}
		catch(f)
		{
			throw('Util.Event.prevent_default: Neither the W3C nor the IE way of preventing the event\'s default action. ' +
				  'When the W3C way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
				  'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.');
		}
	}
	return false;

	/*
	if ( document.all ) // IE // XXX: hack
	{
		//event = event == null ? _window.event : event;
		event.returnValue = false;
		event.cancelBubble = true;
		return false;
	}
	else // Gecko
	{
		event.preventDefault();
	}
	*/
};

/**
 * Returns the target.
 * Taken from quirksmode.org, by Peter-Paul Koch.
 */
Util.Event.get_target = function(e)
{
	var targ;
	//if (!e) var e = window.event;
	if (e.target) targ = e.target;
	else if (e.srcElement) targ = e.srcElement;
	if (targ.nodeType == 3) // defeat Safari bug
		targ = targ.parentNode;
	return targ;
};
