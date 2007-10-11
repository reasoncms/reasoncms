/**
 * @class A canned state for a Util.State_Machine for displaying errors.
 */
UI.Error_State = function(message_container)
{
	var doc = message_container.ownerDocument;
	var dh = new Util.Document(doc);
	
	this.display = null;
	var error = null;
	
	/**
	 * Sets the error message. Note that in order for the message to really be
	 * displayed, the machine must enter this state.
	 *
	 * @param	message	Error message to display (either a string or a
	 * 					DocumentFragment).
	 * @param	retry	If provided, the error message will include a "retry"
	 *					link that, if clicked on by the user, will call the
	 *					function provided here.
	 */
	this.set = function(message, retry)
	{
		error = {message: message, retry: (retry || null)};
	}
	
	this.enter = function()
	{
		if (!error) {
			throw new Error('Entered error state, but there is no error!');
		}

		var children = [error.message];
		if (error.retry) {
			var link = dh.create_element('a',
				{
					href: '#',
					className: 'retry',
					style: {display: 'block'}
				},
				['Retry']);
			Util.Event.add_event_listener(link, 'click', function(e) {
				if (!e)
					var e = window.event;

				try {
					error.retry();
				} finally {
					return Util.Event.prevent_default(e);
				}
			});
			children.push(link);
		}

		this.display = dh.create_element('p', {className: 'error'}, children);
		message_container.appendChild(this.display);
	}
	
	this.exit = function()
	{
		if (this.display)
			this.display.parentNode.removeChild(this.display);
		error = null;
	}
}