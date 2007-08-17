Util.Element = function()
{
};

// Adds a class to the list contained in the element's class attribute.
Util.Element.add_class = function(elem, class_name)
{
	if ( Util.Element.get_all_classes(elem) == null )
	{
		Util.Element.set_all_classes(elem, class_name);
	}
	else
	{
		Util.Element.set_all_classes(elem, Util.Element.get_all_classes(elem) + ' ' + class_name);
	}
};

// Removes the given class_name from the list contained in the
// element's class attribute.
Util.Element.remove_class = function(elem, removable_class)
{
	var all_classes = Util.Element.get_all_classes(elem);

	// If there aren't any classes, no need to try to remove any
	if ( all_classes == null )
		return;

	var all_classes_arr = all_classes.split(' ');
	var new_classes = '';

	// Loop through the array of classes. For each class that doesn't
	// match removable_class, add that class to new_classes.
	for ( var i = 0; i < all_classes_arr.length; i++ )
	{
		if ( all_classes_arr[i] != removable_class )
		{
			if ( new_classes == '' )
				new_classes = all_classes_arr[i];
			else
				new_classes += ' ' + all_classes_arr[i];
		}
	}

	// Don't want an empty class
	if ( new_classes != '' )
		Util.Element.set_all_classes(elem, new_classes);
	else
		Util.Element.remove_all_classes(elem);
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
