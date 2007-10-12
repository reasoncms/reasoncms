/**
 * Does nothing.
 *
 * @class Container for functions relating to arrays.
 */
Util.Array = function()
{
};

/**
 * Forms a legitimate JavaScript array from an array-like object
 * (eg NodeList objects, function argument lists).
 */
Util.Array.from = function(iterable)
{
	if (!iterable)
		return [];
	if (iterable.toArray)
		return iterable.toArray();
	
	var results = [];
	for (var i = 0, length = iterable.length; i < length; i++) {
		results.push(iterable[i]);
	}
	return results;
};

$A = Util.Array.from; // convenience alias

/**
 * Creates an array of integers from start up to (but not including) stop.
 */
Util.Array.range = function(start, stop)
{
	if (arguments.length == 1) {
		stop = start;
		start = 0;
	}
	
	var ret = [];
	for (var i = start; i < stop; i++) {
		ret.push(i);
	}
	return ret;
}

$R = Util.Array.range; // convenience alias

/**
 * Methods that are callable by two methods:
 *  - Util.Array.method_name(some_array, ...)
 *  - some_array.methodName(...)
 * Note the change in naming convention! When added to
 * Array's prototype it is changed to use the JavaScript
 * naming convention (camelCase) instead of Loki's
 * (underscore_separated).
 */
Util.Array.Methods = {
	/**
	 * Executes the given function for each element in the array.
	 * (Available as the "each" method of arrays.)
	 * @param	array	the array over which for_each will loop
	 * @param	func	the function which will be called
	 * @param	thisp	optional "this" context
	 * @see	http://tinyurl.com/ds8lo
	 */
	for_each: function(array, func, thisp)
	{
		if (typeof(thisp) == 'undefined')
			var thisp = null;
		if (typeof(func) != 'function')
			throw new TypeError();

		//if (typeof(array.forEach) == 'function')
		//	return array.forEach(func, thisp);

		var len = array.length;
		for (var i = 0; i < len; i++) {
			if (i in array)
				func.call(thisp, array[i], i, array);
		}
	},
	
	/**
	 * [a, b, c, ...] -> [func(a), func(b), func(c), ...]
	 */
	map: function(array, func, thisp)
	{
		if (typeof(thisp) == 'undefined')
			thisp = null;

		var len = array.length;
		var ret = new Array(len);
		for (var i = 0; i < len; i++) {
			if (i in array)
				ret[i] = func.call(thisp, array[i], i, array);
		}

		return ret;
	},
	
	/**
	 * @see http://tinyurl.com/yq3c9f
	 */
	reduce: function(array, func, initial_value)
	{
		if (typeof(func) != 'function')
			throw new TypeError();
		
		var value;
		
		array.each(function(v, i, a) {
			if (value === undefined && initial_value === undefined) {
				value = v;
			} else {
				value = func.call(null, value, v, i, a);
			}
		});
		
		return value;
	},
	
	/**
	 * Returns the first item in the array for which the test function
	 * returns true.
	 * @param	array	the array to search
	 * @param	test	the function which will be called
	 * @param	thisp	optional "this" context
	 */
	find: function(array, test, thisp)
	{
		if (typeof(thisp) == 'undefined')
			thisp = null;
		if (typeof(test) != 'function')
			throw new TypeError();

		var len = array.length;

		for (var i = 0; i < len; i++) {
			if (i in array && test.call(thisp, array[i]))
				return array[i]
		}
	},
	
	/**
	 * Returns all items in the array for which the test function
	 * returns true.
	 * @param	array	the array to search
	 * @param	test	the function which will be called
	 * @param	thisp	optional "this" context
	 */
	find_all: function(array, test, thisp)
	{
		if (typeof(thisp) == 'undefined')
			thisp = null;
		if (typeof(test) != 'function')
			throw new TypeError();

		var len = array.length;
		var results = [];

		for (var i = 0; i < len; i++) {
			if (i in array && test.call(thisp, array[i]))
				results.push(array[i]);
		}

		return results;
	},
	
	min: function(array, key_func)
	{
		return array.reduce(function(a, b) {
			if (key_func) {
				return (key_func(b) < key_func(a))
					? b
					: a;
			} else {
				return (b < a)
					? b
					: a;
			}
		});
	},
	
	max: function(array, key_func)
	{
		return array.reduce(function(a, b) {
			if (key_func) {
				return (key_func(b) > key_func(a))
					? b
					: a;
			} else {
				return (b > a)
					? b
					: a;
			}
		});
	},
	
	pluck: function(array, property_name)
	{
		return array.map(function(obj) {
			return obj[property_name];
		});
	},
	
	sum: function(array)
	{
		return array.reduce(function(a, b) {
			return a + b;
		});
	},
	
	product: function(array)
	{
		return array.reduce(function(a, b) {
			return a * b;
		});
	}
}

for (var name in Util.Array.Methods) {
	function transform_name(name)
	{
		var new_name = '';
		parts = name.split(/_+/);
		
		new_name += parts[0];
		for (var i = 1; i < parts.length; i++) {
			new_name += parts[1].substr(0, 1).toUpperCase();
			new_name += parts[1].substr(1);
		}
		
		return new_name;
	}
	
	Util.Array[name] = Util.Array.Methods[name];
	
	var new_name = (name == 'for_each') ? 'each' : transform_name(name);
	Array.prototype[new_name] = Util.Array.Methods[name].methodize();
}