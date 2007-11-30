/**
 * @class Container for methods that allow standard OOP thinking to be
 * shoehorned into JavaScript, for better or worse.
 */
Util.OOP = {};

/**
 * "Mixes in" an object's properties.
 * @param	{object}	target	The object into which things will be mixed
 * @param	{object}	source	The object providing the properties
 * @type object
 * @return target
 */
Util.OOP.mixin = function(target, source)
{
	var names = Util.Object.names(source);
	for (var i = 0; i < names.length; i++) {
		target[names[i]] = source[names[i]];
	}
	
	return target;
}

/**
 * Sets up inheritance from parent to child. To use:
 * - Create parent and add parent's methods and properties.
 * - Create child
 * - At beginning of child's constructor, call inherits(parent, child)
 * - Add child's new methods and properties
 * - To call method foo in the parent: this.superclass.foo.call(this, params)
 * - Be careful where you use self and this: in inherited methods, self
 *   will still refer to the superclass, whereas this will refer, properly, to the
 *   child class. If you must use self, e.g. for event listeners, define self
 *   only inside methods, not directly inside the constructor. (Note: The existing
 *   code doesn't follow this advice perfectly; follow this advice, not that code.)
 *
 * Changed on 2007-09-13 by EN: Now calls the parent class's constructor! Any
 * arguments that need to be passed to the constructor can be provided after
 * the child and parent.
 *
 * Inspired by but independent of <http://www.crockford.com/javascript/inheritance.html>.
 *
 * The main problem with just doing something like
 *     child.prototype = new parent();
 * is that methods inherited from the parent can't set properties accessible
 * by methods defined in the child.
 */
Util.OOP.inherits = function(child, parent)
{
	var parent_prototype = null;
	var nargs = arguments.length;
	
	if (nargs < 2) {
		throw new TypeError('Must provide a child and a parent class.');
	} else if (nargs == 2) {
		parent_prototype = new parent;
	} else {
		// XXX: Is there really no better way to do this?!
		//      Something involving parent.constructor maybe?
		var arg_list = $R(2, nargs).map(function (i) {
			return 'arguments[' + String(i) + ']';
		});
		eval('parent_prototype = new parent(' + arg_list.join(', ') + ')')
	}
	
	Util.OOP.mixin(child, parent_prototype);
	child.superclass = parent_prototype;
};

/**
 * Sets up inheritance from parent to child, but only copies over the elements
 * in the parent's prototype provided as arguments after the parent class.
 */
Util.OOP.swiss = function(child, parent)
{
	var parent_prototype = new parent;
    for (var i = 2; i < arguments.length; i += 1) {
        var name = arguments[i];
        child[name] = parent_prototype[name];
    }
    return child;
};