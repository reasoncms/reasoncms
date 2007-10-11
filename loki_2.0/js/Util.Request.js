/**
 * @class  Asynchronus HTTP requests (an XMLHttpRequest wrapper).
 *         Deprecates Util.HTTP_Reader.
 * @author Eric Naeseth
 */
Util.Request = function(url, options)
{
	var self = this;
	var timeout = null;
	var timed_out = false;
	
	this.options = options || {};
		
	for (var option in Util.Request.Default_Options) {
		if (!this.options[option])
			this.options[option] = Util.Request.Default_Options[option];
	}
	
	function create_transport()
	{
		try {
			return new XMLHttpRequest();
		} catch (e) {
			try {
				return new ActiveXObject('Msxml2.XMLHTTP');
			} catch (f) {
				try {
					return new ActiveXObject('Microsoft.XMLHTTP');
				} catch (g) {
					throw 'Util.Request: Unable to create a HTTP request object!';
				}
			}
		}
	}
	
	var empty = Util.Function.empty;
	
	function ready_state_changed()
	{
		var state = self.transport.readyState;
		var name = Util.Request.Events[state];
		
		(self.options['on_' + state] || empty)(self, self.transport);
		
		if (name == 'complete')
			completed();
	}
	
	function completed()
	{
		if (timeout) {
			timeout.cancel();
			timeout = null;
		}
		
		(self.options['on_'] + self.get_status()
			|| self.options['on_' + (self.succeeded() ? 'success' : 'failure')]
			|| empty)(self, self.transport);
		self.transport.onreadystatechange = empty;
	}
	
	function internal_abort(send_notification)
	{
		this.transport.onreadystatechange = empty;
		
		try {
			if (send_notificiation) {
				try {
					(this.options.on_abort || empty)(this, this.transport);
				} catch (handler_exception) {
					// ignore
				}
			}
			
			this.transport.abort();
		} catch (e) {
			// do nothing
		}
	}
	
	this.get_status = function()
	{
		try {
			return this.transport.status || 0;
		} catch (e) {
			return 0;
		}
	}
	
	this.get_status_text = function()
	{
		try {
			return (timed_out)
				? 'Operation timed out.'
				: (this.transport.statusText || '');
		} catch (e) {
			return '';
		}
	}
	
	this.get_header = function(name)
	{
		try {
			return this.transport.getResponseHeader(name);
		} catch (e) {
			return null;
		}
	}
	
	this.succeeded = function()
	{
		var status = this.get_status();
		return !status || (status >= 200 && status < 300);
	}
	
	this.abort = function()
	{
		internal_abort.call(this, true);
	}
	
	timed_out = false;
	
	if (this.options.timeout) {
		timeout = Util.Scheduler.delay(function() {
			internal_abort.call(this, false);
			(this.options.on_timeout || this.options.on_failure || empty)
				(this, this.transport);
		}.bind(this), this.options.timeout);
	}
	
	this.transport = create_transport();
	this.url = url;
	this.method = this.options.method;
	this.transport.onreadystatechange = ready_state_changed;
	
	try {
		this.transport.open(this.method.toUpperCase(), this.url,
			this.options.asynchronus);
		this.transport.send(this.options.body || null);
	} catch (e) {
		if (timeout) {
			timeout.cancel();
			timeout = null;
		}
		
		throw e;
	}
	
};

Util.Request.Default_Options = {
	method: 'post',
	asynchronus: true,
	content_type: 'application/x-www-form-urlencoded',
	encoding: 'UTF-8',
	parameters: '',
	timeout: null
};

Util.Request.Events =
	['uninitialized', 'ready', 'send', 'interactive', 'complete'];