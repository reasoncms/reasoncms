Util.OOP = {};

/**
 * Sets up inheritance from parent to child. To use:
 * - Create parent and add parent's methods and properties.
 * - Do not require any arguments to parent's constructor--use an init method instead.
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
	var parent_prototype = new parent;
	for ( var name in parent_prototype )
	{
		child[name] = parent_prototype[name];
	}
	child['superclass'] = parent_prototype;
	
	// call the superclass' constructor on the child object
	parent.apply(child, Util.Array.from(arguments).slice(2));
};

/**
 * Sets up inheritance from parent to child, but only copies over the elements
 * in the parent's prototype provided as arguments after the parent class.
 */
Util.OOP.swiss = function (child, parent)
{
	var parent_prototype = new parent;
    for (var i = 2; i < arguments.length; i += 1) {
        var name = arguments[i];
        child[name] = parent_prototype[name];
    }
    return child;
};


/**
 * Sets up inheritance from parent to child. To use:
 * - Create parent and add parent's methods and properties
 * - Create child
 * - Call inherits(parent, child)
 * - Add child's new methods and properties
 *
 * Slightly modified from <http://www.crockford.com/javascript/inheritance.html>.
 */
Util.OOP.inherits_old = function(parent, child)
{
    var d = 0, p = (child.prototype = new parent());
    child.prototype.uber = function uber(name) {
        var f, r, t = d, v = parent.prototype;
        if (t) {
            while (t) {
                v = v.constructor.prototype;
                t -= 1;
            }
            f = v[name];
        } else {
            f = p[name];
            if (f == child[name]) {
                f = v[name];
            }
        }
        d += 1;
        r = f.apply(child, Array.prototype.slice.apply(arguments, [1]));
        d -= 1;
        return r;
    };
    return child;
};

