/**
 * @class  Asynchronus HTTP requests (an XMLHttpRequest wrapper).
 *         Deprecates Util.HTTP_Reader.
 * @author Eric Naeseth
 */
Util.Request = function(url, options)
{
	var self = this;
	
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
	
	function empty() {}
	
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
		(self.options['on_'] + self.get_status()
			|| self.options['on_' + (self.succeeded() ? 'success' : 'failure')]
			|| empty)(self, self.transport);
		self.transport.onreadystatechange = empty;
	}
	
	this.get_status = function()
	{
		try {
			return this.transport.status || 0;
		} catch (e) {
			return 0;
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
		try {
			this.transport.abort();
		} catch (e) {
			// do nothing
		}
		
		this.transport.onreadystatechange = empty;
	}
	
	
	this.transport = create_transport();
	this.url = url;
	this.method = this.options.method;
	this.transport.onreadystatechange = ready_state_changed;
	
	this.transport.open(this.method.toUpperCase(), this.url,
		this.options.asynchronus);
	this.transport.send(this.options.body || null);
}

Util.Request.Default_Options = {
	method: 'post',
	asynchronus: true,
	content_type: 'application/x-www-form-urlencoded',
	encoding: 'UTF-8',
	parameters: '',
};

Util.Request.Events =
	['uninitialized', 'ready', 'send', 'interactive', 'complete'];