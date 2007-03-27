Util.Input = function()
{
};

/**
 * Creates a DOM input element and adds the given name attribute. This
 * is necessary because of a bug in IE which doesn't allow the name
 * attribute to be set on created input elements.
 *
 * @static
 * @param	params	object containing the following named paramaters:
 *                  <ul>
 *                  <li>doc - the document object with which to create the input</li>
 *                  <li>name - the desired name of the input</li>
 *                  <li>checked - (optional) boolean indicating whether the input should be checked</li>
 *                  </ul>
 * @return			a DOM input element
 */
Util.Input.create_named_input = function(params)
{
	var doc = params.document;
	var name = params.name;
	var checked = params.checked;

	// Make sure required arguments are given
	if ( doc == null || name == '' )
		throw(new Error('Util.Input.create_named_input: Missing argument.'));

	// First try to create the input and add its name attribute
	// normally
	var input = doc.createElement('INPUT');
	input.setAttribute('name', name);
	if ( checked )
		input.setAttribute('checked', 'checked');
	

	// If that didn't work, create it in the IE way
	if ( input.outerHTML != null && input.outerHTML.indexOf('name') == -1 )
	{
		var checked_str = checked ? ' checked="checked"' : '';
		input = doc.createElement('<INPUT name="' + name + '"' + checked_str + '>');
	}

	// Make sure it worked
	if ( input == null || input.getAttribute('name') == '' )
		throw(new Error('Util.Input.create_named_input: Couldn\'t create named input.'));
		
	return input;
};