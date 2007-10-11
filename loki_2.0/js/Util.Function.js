Util.Function = {
	bind: function(function_)
	{
		if (arguments.length < 2 && arguments[0] === undefined)
			return function_;
		
		var args = Util.Array.from(arguments).slice(1), object = args.shift();
		return function() {
			return function_.apply(object, args.concat(Util.Array.from(arguments)));
		}
	},
	
	bind_to_event: function(function_)
	{
		var args = Util.Array.from(arguments), object = args.shift();
		return function(event) {
			return function_.apply(object, [event || window.event].concat(args));
		}
	},
	
	curry: function(function_)
	{
		if (!arguments.length)
			return function_;
		
		var args = Util.Array.from(arguments).slice(1);
		
		return function() {
			return function_.apply(this, args.concat(Util.Array.from(arguments)));
		}
	},
	
	dynamic_curry: function(function_)
	{
		if (!arguments.length)
			return function_;
		
		var args = Util.Array.from(arguments).slice(1).map(function (a) {
			return (typeof(a) == 'function')
				? a()
				: a;
		});
		
		return function() {
			return function_.apply(this, args.concat(Util.Array.from(arguments)));
		}
	},
	
	methodize: function(function_)
	{
		if (!function_.methodized) {
			function_.methodized = function() {
				return function_.apply(null, [this].concat(Util.Array.from(arguments)));
			}
		}
		
		return function_.methodized;
	},
	
	delay: function(function_, delay)
	{
		return Util.Scheduler.delay(function_, delay);
	},
	
	defer: function(function_)
	{
		return Util.Scheduler.defer(function_);
	},
	
	empty: function()
	{
		
	},
	
	constant: function(k)
	{
		return k;
	},
	
	unimplemented: function()
	{
		throw new Error('Function not implemented!');
	}
};

Util.Function.bindToEvent = Util.Function.bind_to_event;

for (var name in Util.Function) {
	Function.prototype[name] = Util.Function.methodize(Util.Function[name]);
}