/**
 * Does nothing.
 *
 * @class Container for functions relating to nodes.
 */
Util.Document = function()
{
};

/**
 * Imitates W3CDOM Document.importNode, which IE doesn't
 * implement. See spec for more details.
 *
 * Note: Do not give a DocumentFragment as old_node, at least not one
 * with more than one child node. It will break in IE--will only
 * return the first child node. (You shouldn't be using document
 * fragments in IE anyway.)
 *
 * NOTE: This is broken for IE ... it surounds the new node in a div,
 * which is distinctly not what is wanted sometimes (read: usually).
 *
 * @param	new_document	the document to import the node to
 * @param	old_node		the node to import
 * @param	deep			boolean indicating whether to import child
 *							nodes (currently ignored in IE ... is always true)
 */
Util.Document.import_node = function(new_document, old_node, deep)
{
	var new_node;
	
	try
	{
		new_node = new_document.importNode(old_node, deep)
	}
	catch(e)
	{
		try
		{
			var container_elem = new_document.createElement('DIV');
			container_elem.innerHTML = old_node.outerHTML;
			new_node = container_elem.firstChild;
		}
		catch(f)
		{
			throw new Error('Util.Document.import_node: Neither the W3C document.importNode method ' +
							'nor a workaround for IE worked. When the W3C way was tried, this ' +
							'exception was thrown: <<' + e.message + '>>. When the IE workaround ' +
							'was tried, this exception was thrown: <<' + f.message + '>>.');
		}
	}
	
	return new_node;
};

/**
 * Append the style sheet at the given location to the head of the
 * given document
 *
 * @param	location	the location of the stylesheet to add
 * @static
 */
Util.Document.append_style_sheet = function(doc, location)
{
	var head_elem = doc.getElementsByTagName('head').item(0);
	var link_elem = doc.createElement('link');

	link_elem.setAttribute('href', location);
	link_elem.setAttribute('rel', 'stylesheet');
	link_elem.setAttribute('type', 'text/css');

	head_elem.appendChild(link_elem);
};

/**
 * Returns an array (not a DOM NodeList!) of elements that match the given
 * namespace URI and local name.
 *
 * XXX Doesn't work
 */
Util.Document.get_elements_by_tag_name_ns = function(doc, ns_uri, tagname)
{
	var elems = new Array();
	try // W3C
	{
		var all = doc.getElementsByTagNameNS(ns_uri, tagname);
		messagebox('doc' ,doc);
		messagebox('all', all);
		for ( var i = 0; i < all.length; i++ )
			elems.push(all[i]);
	}
	catch(e)
	{
		try // IE
		{
			var all = doc.getElementsByTagName(tagname);
			for ( var i = 0; i < all.length; i++ )
			{
				if ( all[i].tagUrn == ns_uri )
					elems.push(all[i]);
			}
		}
		catch(f)
		{
			throw('Neither the W3C nor the IE way of getting the element by namespace worked. When the W3C way was tried, an error with the following message was thrown: ' + e.message + '. When the IE way was tried, an error with the following message was thrown: ' + f.message + '.');
		}
	}
	return elems;
};
