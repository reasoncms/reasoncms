/**
 * @class Provides a nicely-formatted inline error display.
 * @constructor
 * @param {HTMLElement} the element into which the message will be inserted
 */
UI.Error_Display = function(message_container)
{
	var doc = message_container.ownerDocument;
	var dh = new Util.Document(doc);
	
	this.display = null;
	
	function create(message, retry)
	{
		if (!retry)
			var retry = null;
		
		var children = [message];
		if (retry) {
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
					retry();
				} catch (e) {
					this.show('Failed to retry: ' + String(e), retry);
				} finally {
					return Util.Event.prevent_default(e);
				}
			});
			children.push(link);
		}

		this.display = dh.create_element('p', {className: 'error'}, children);
		message_container.appendChild(this.display);
	}
	
	function remove()
	{
		this.display.parentNode.removeChild(this.display);
		this.display = null;
	}
	
	this.show = function(message, retry)
	{
		if (!retry)
			var retry = null;
		
		if (this.display)
			remove();
		
		create(message, retry);
	}
	
	this.clear = function()
	{
		if (this.display)
			remove();
	}
}