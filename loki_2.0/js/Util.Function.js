Util.Function = {
	/**
	 * Synchronizes calls to the function; i.e. prevents it from being called
	 * more than once at the same time.
	 * @author Eric Naeseth
	 * @see http://taylor-hughes.com/?entry=112
	 */
	synchronize: function(function_)
	{
		var sync = Util.Function.synchronize;
		
		if (!sync.next_id) {
			sync.next_id = 0;
			sync.wait_list = {};
			sync.next = function(k) {
				for (i in sync.wait_list) {
					if (!k)
						return sync.wait_list[i];
					if (k == i)
						k = null;
				}

				return null;
			}
		}
		
		return function() {
			var lock = {
				id: ++sync.next_id,
				enter: false
			};

			sync.wait_list[lock.id] = lock;

			lock.enter = true;
			lock.number = (new Date()).getTime();
			lock.enter = false;
			
			var context = [this, arguments];

			function attempt(start)
			{
				for (var j = start; j != null; j = sync.next(j.id)) {
					if (j.enter ||
						(j.number && j.number < lock.number ||
							(j.number == lock.number && j.id < lock.id))) 
					{
						(function () { attempt(j); }).delay(100);
						return;
					}
				}

				// run with exclusive access
				function_.apply(context[0], context[1]);
				// release
				lock.number = 0;
				sync.wait_list[lock.id] = null;
			}
			
			attempt(sync.next());
		}
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

var $S = Util.Function.synchronize;

Util.Function.Methods = {
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
	}
};

Util.Function.bindToEvent = Util.Function.bind_to_event;

for (var name in Util.Function.Methods) {
	Function.prototype[name] =
		Util.Function.Methods.methodize(Util.Function.Methods[name]);
	Util.Function[name] = Util.Function.Methods[name];
}