/**
 * Does nothing.
 *
 * @class Container for functions relating to arrays.
 */
Util.Array = function()
{
};

/**
 * Executes the given function for each element in the array.
 * @param	array	the array over which for_each will loop
 * @param	func	the function which will be called
 * @param	thisp	optional "this" context
 * @see	http://tinyurl.com/ds8lo
 */
Util.Array.for_each = function(array, func, thisp)
{
	if (typeof(thisp) == 'undefined')
		thisp = null;
	
	if (typeof(array.forEach) == 'function')
		return array.forEach(func, thisp);
	
	var len = array.length;
	for (var i = 0; i < len; i++) {
		if (i in array)
			func.call(thisp, array[i], i, array);
	}
};

