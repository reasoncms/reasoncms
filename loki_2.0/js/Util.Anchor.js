Util.Anchor = function()
{
};

/**
 * Creates a DOM anchor element and adds the given name attribute. This
 * is necessary because of a bug in IE which doesn't allow the name
 * attribute to be set on created anchor elements.
 *
 * @static
 * @param	params	object containing the following named paramaters:
 *                  <ul>
 *                  <li>doc - the document object with which to create the anchor</li>
 *                  <li>name - the desired name of the anchor</li>
 *                  </ul>
 * @return			a DOM anchor element
 */
Util.Anchor.create_named_anchor = function(params)
{
	var doc = params.document;
	var name = params.name;

	// Make sure required arguments are given
	if ( doc == null || name == '' )
		throw(new Error('Util.Anchor.create_named_anchor: Missing argument.'));

	// First try to create the anchor and add its name attribute
	// normally
	var anchor = doc.createElement('A');
	anchor.setAttribute('name', name);
	

	// If that didn't work, create it in the IE way
	if ( anchor.outerHTML != null && anchor.outerHTML.indexOf('name') == -1 )
	{
		anchor = doc.createElement('<A name="' + name + '">');
	}

	// Make sure it worked
	if ( anchor == null || anchor.getAttribute('name') == '' )
		throw(new Error('Util.Anchor.create_named_anchor: Couldn\'t create named anchor.'));
		
	return anchor;
};
