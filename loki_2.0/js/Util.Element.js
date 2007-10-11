Util.Element = function()
{
};

// Adds a class to the list contained in the element's class attribute.
Util.Element.add_class = function(elem, class_name)
{
	var classes = Util.Element.get_class_array(elem);
	classes.push(class_name);
	Util.Element.set_class_array(elem, classes);
};

// Removes the given class_name from the list contained in the
// element's class attribute.
Util.Element.remove_class = function(elem, removable_class)
{
	var classes = Util.Element.get_class_array(elem);
	
	for (var i = 0; i < classes.length; i++) {
		if (classes[i] == removable_class)
			classes.splice(i, 1);
	}
	
	Util.Element.set_class_array(elem, classes);
};

// Returns true if the given element has the given class
Util.Element.has_class = function(elem, class_name)
{
	return (Util.Element.get_all_classes(elem) + '').indexOf(class_name) > -1;
};

// Returns string containing all this element's classes, or null
// string if no such attribute is set.
Util.Element.get_all_classes = function(elem)
{
	if (elem == null)
		return null;
	
	var cache = elem.getAttribute('class');
	return (cache != null)
		? cache
		: elem.getAttribute('className');
};

/**
 * Returns an array whose members are the element's classes.
 */
Util.Element.get_class_array = function(elem)
{
	return elem.className.split(/\s+/);
};

// Sets the class attribute of an element to the given string which
// contains a list of class names. It is necessary to set className
// because for elements added using the DOM, IE requires one to set a
// special property, className, in order for the styles associated
// with that class to be applied. (Stuupid, eh?)
//
// N.B.: Consider using add_class() instead of set_all_classes() if
// all you want to do is make an element part of a class. That way, if
// the element is already part of another class, you won't nuke
// it. (Cf. difference between using "element.onclick = xxx" and
// "element.addEventListener('click', xxx, false)".)
Util.Element.set_all_classes = function(elem, all_classes)
{
	if ( document.all ) // TEMP: the existence of document.all isn't really related to 'className', so I should use something else ... but what?
	{
		elem.setAttribute('className', all_classes);
	}
	elem.setAttribute('class', all_classes);
};

Util.Element.set_class_array = function(elem, classes)
{
	elem.className = classes.join(' ');
};

// Removes the given element's class attribute. For info about
// "className", see on set_all_classes().
Util.Element.remove_all_classes = function(elem)
{
	if ( document.all ) // TEMP: the existence of document.all isn't really related to 'className', so I should use something else ... but what?
	{
		elem.removeAttribute('className');
	}
	elem.removeAttribute('class');
};

/**
 * Either returns the prefix or empty string if there is none.
 * E.g.:  <o:p> --> 'o'
 *        <p>   --> ''
 */
Util.Element.get_prefix = function(node)
{
	if ( node.prefix != null ) // W3C way
	{
		return node.prefix;
	}
	else if ( node.scopeName != null ) // IE way
	{
		return node.scopeName;
	}
	else // Gecko way
	{
		var tagname = node.tagName;
		arr = tagname.split(':');
		if ( arr.length == 2 )
			return arr[0];
		else
			return '';
	}
};

/**
 * Returns the absolute position of the element, 
 * i.e. its position relative to the window.
 *
 * Algorithm from FCK.
 */
Util.Element.get_position = function(elem)
{
	var x = 0, y = 0;
	
	// Loop through the offset chain.
	while ( elem )
	{
		x += elem.offsetLeft == null ? elem.screenLeft : elem.offsetLeft;
		y += elem.offsetTop == null ? elem.screenTop : elem.offsetTop;

		elem = elem.offsetParent;
	}
	
	return { x : x, y : y };
};
