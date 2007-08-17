/**
 * Does nothing.
 *
 * @class Container for functions relating to nodes.
 */
Util.Node = function()
{
};

// Since IE doesn't expose these constants, they are reproduced here
Util.Node.ELEMENT_NODE                   = 1;
Util.Node.ATTRIBUTE_NODE                 = 2;
Util.Node.TEXT_NODE                      = 3;
Util.Node.CDATA_SECTION_NODE             = 4;
Util.Node.ENTITY_REFERENCE_NODE          = 5;
Util.Node.ENTITY_NODE                    = 6;
Util.Node.PROCESSING_INSTRUCTION_NODE    = 7;
Util.Node.COMMENT_NODE                   = 8;
Util.Node.DOCUMENT_NODE                  = 9;
Util.Node.DOCUMENT_TYPE_NODE             = 10;
Util.Node.DOCUMENT_FRAGMENT_NODE         = 11;
Util.Node.NOTATION_NODE                  = 12;

// Constants which indicate which direction to iterate through a node
// list, e.g. in get_nearest_non_whitespace_sibling_node
Util.Node.NEXT							 = 1;
Util.Node.PREVIOUS						 = 2;

/**
 * Removes child nodes of <code>node</code> for which
 * <code>boolean_test</code> returns true.
 *
 * @param	node			the node whose child nodes are in question
 * @param	boolean_test	(optional) A function which takes a node 
 *                          as its parameter, and which returns true 
 *                          if the node should be removed, or false
 *                          otherwise. If boolean_test is not given,
 *                          all child nodes will be removed.
 */
Util.Node.remove_child_nodes = function(node, boolean_test)
{
	if ( boolean_test == null )
		boolean_test = function(node) { return true; };

	while ( node.childNodes.length > 0 )
		if ( boolean_test(node.firstChild) )
			node.removeChild(node.firstChild);
};


/**
 * <p>Recurses through the ancestor nodes of the specified node,
 * until either (a) a node is found which meets the conditions
 * specified inthe function boolean_test, or (b) the root of the
 * document tree isreached. If (a) obtains, the found node is
 * returned; if (b)obtains, null is returned.</p>
 * 
 * <li>Example usage 1: <code>var nearest_ancestor = this._get_nearest_ancestor_element(node, function(node) { return node.tagName == 'A' });</code></li>
 * <li>Example usage 2: <pre>
 *
 *          var nearest_ancestor = this._get_nearest_ancestor_element(
 *              node,
 *              function(node, extra_args) {
 *                  return node.tagName == extra_args.ref_to_this.something
 *              },
 *              { ref_to_this : this }
 *          );
 *
 * </pre></li>
 *
 * @param	node			the starting node
 * @param	boolean_test	<p>the function to use as a test. The given function should
 *                          accept the following paramaters:</p>
 *                          <li>cur_node - the node currently being tested</li>
 *                          <li>extra_args - (optional) any extra arguments this function
 *                          might need, e.g. a reference to the calling object (deprecated:
 *                          use closures instead)</li>
 * @param	extra_args		any extra arguments the boolean function might need (deprecated:
 *                          use closures instead)
 * @return					the nearest matching ancestor node, or null if none matches
 */
Util.Node.get_nearest_ancestor_node = function(node, boolean_test, extra_args)
{
	var cur_node = node.parentNode;
	while ( true )
	{
		if ( cur_node == null || // added only 2006-03-07; should this actually be here, 
								 // after all this time, or am I just missing something?
			 cur_node.nodeType == Util.Node.DOCUMENT_NODE ||
			 cur_node.nodeType == Util.Node.DOCUMENT_FRAGMENT_NODE ) // reached the top of the tree
		{
			return null;
		}
		else if ( boolean_test(cur_node, extra_args) )
		{
			return cur_node;
		}
		else
		{
			cur_node = cur_node.parentNode;
		}
	}
};

/**
 * Returns true if there exists an ancestor of the given node 
 * that satisfies the given boolean_test. Paramaters same as for
 * get_nearest_ancestor_node.
 */
Util.Node.has_ancestor_node = function(node, boolean_test, extra_args)
{
	return Util.Node.get_nearest_ancestor_node(node, boolean_test, extra_args) != null;
};

/**
 * Gets the nearest ancester of node which is a block-level
 * element. (Uses get_nearest_ancestor_node.)
 *
 * @param	node		the starting node
 */
Util.Node.get_nearest_bl_ancestor_element = function(node)
{
	return Util.Node.get_nearest_ancestor_node(node, Util.Node.is_block_level_element);
};

/**
 * Gets the given node's nearest ancestor which is an element whose
 * tagname matches the one given.
 *
 * @param	node			the starting node
 * @param	tag_name		the desired tag name	
 * @return					the matching ancestor, if any
 */
Util.Node.get_nearest_ancestor_element_by_tag_name = function(node, tag_name)
{
	var boolean_test = function(node2)
	{
		return ( node2.nodeType == Util.Node.ELEMENT_NODE &&
			     node2.tagName == tag_name );
	};
	return Util.Node.get_nearest_ancestor_node(node, boolean_test);
};

/**
 * Iterates previouss through the given node's children, and returns
 * the first node which matches boolean_test.
 *
 * @param	node			the starting node
 * @param	boolean_test	the function to use as a test. The given function should
 *                          accept one paramater:
 *                          <li>cur_node - the node currently being tested</li>
 * @return					the last matching child, or null if none matches
 */
Util.Node.get_last_child_node = function(node, boolean_test)
{
	var children = node.childNodes;
	for ( var i = children.length - 1; i >= 0; i-- )
	{
		var child = children.item(i);
		if ( boolean_test(child) )
			return child;
	}
	return null;
};

Util.Node.has_child_node = function(node, boolean_test)
{
	return Util.Node.get_last_child_node(node, boolean_test) != null;
};

/**
 * Returns true if the node is an element node and its node name matches the
 * tag parameter.
 *
 * @param	node	node on which the test will be run
 * @param	tag		tag name to look for
 * @return			true or false
 */
Util.Node.is_tag = function(node, tag)
{
	return (node.nodeType == Util.Node.ELEMENT_NODE && node.nodeName == tag);
};

/**
 * Creates a function that calls is_tag using the given tag.
 */
Util.Node.curry_is_tag = function(tag)
{
	return function(node) { return Util.Node.is_tag(node, tag); };
}

Util.Node.non_whitespace_regexp = new RegExp('[^\f\n\r\t\v]', 'gi');
Util.Node.is_non_whitespace_text_node = function(node)
{
	// [^\f\n\r\t\v] should be the same as \S, but at least on
	// Gecko/20040206 Firefox/0.8 for Windows, \S doesn't always match
	// what the explicitly specified character class matches--and what
	// \S should match.

	return ( node.nodeType != Util.Node.TEXT_NODE ||
			 Util.Node.non_whitespace_regexp.test(node.nodeValue) );
};

/**
 * Gets the last child node which is other than mere whitespace. (Uses
 * get_last_child_node.)
 *
 * @param	node	the node to look for
 * @return			the last non-whitespace child node
 */
Util.Node.get_last_non_whitespace_child_node = function(node)
{
	node.ownerDocument.normalizeDocument();
	return Util.Node.get_last_child_node(node, Util.Node.is_non_whitespace_text_node);
};

/**
 * Returns the given node's nearest sibling which is not a text node
 * that contains only whitespace.
 *
 * @param	node					the node to look for
 * @param	next_or_previous		indicates which direction to look,
 *                                  either Util.Node.NEXT or
 *                                  Util.Node.PREVIOUS
 */
Util.Node.get_nearest_non_whitespace_sibling_node = function(node, next_or_previous)
{
	// [^\f\n\r\t\v] should be the same as \S, but at least on
	// Gecko/20040206 Firefox/0.8 for Windows, \S doesn't always match
	// what the explicitly specified character class matches--and what
	// \S should match.
	var non_whitespace_regexp = new RegExp('[^\f\n\r\t\v]', 'gi');

	do
	{
		if ( next_or_previous == Util.Node.NEXT )
			node = node.nextSibling;
		else if ( next_or_previous == Util.Node.PREVIOUS )
			node = node.previousSibling;
		else
			throw("Util.get_nearest_non_whitespace_sibling_node: Argument next_or_previous must have Util.Node.NEXT or Util.Node.PREVIOUS as its value.");
	}
	while (!( node == null ||
			  node.nodeType != Util.Node.TEXT_NODE ||
			  non_whitespace_regexp.test(node.nodeValue)
		   ))

	return node;
};

/**
 * Determines whether the given node is a block-level element.
 *
 * @param	node	the node in question
 * @return			boolean indicating whether the given node is a block-level element
 */
Util.Node.is_block_level_element = function(node)
{
	return node.nodeType == Util.Node.ELEMENT_NODE && Util.BLE_Rules.all_ble.regexp.test(node.tagName);
};

/**
 * Determines whether the given node, in addition to being a block-level
 * element, is also one that it we can nest inside any arbitrary block.
 * It is generally not permitted to surround the elements in the list below 
 * with most other blocks. E.g., we don't want to surround a TD with BLOCKQUOTE.
 */
Util.Node.is_nestable_block_level_element = function(node)
{
	return Util.Node.is_block_level_element && !(new RegExp('(BODY|TBODY|THEAD|TR|TH|TD)', 'i')).test(node.tagName);
};

/**
 * Returns the rightmost descendent of the given node.
 */
Util.Node.get_rightmost_descendent = function(node)
{
	var rightmost = node;
	while ( rightmost.lastChild != null )
		rightmost = rightmost.lastChild;
	return rightmost;
};

Util.Node.get_leftmost_descendent = function(node)
{
	var leftmost = node;
	while ( leftmost.firstChild != null )
		leftmost = leftmost.firstChild;
	return leftmost;
};

Util.Node.is_rightmost_descendent = function(node, ref)
{
	return Util.Node.get_rightmost_descendent(ref) == node;
};

Util.Node.is_leftmost_descendent = function(node, ref)
{
	return Util.Node.get_leftmost_descendent(ref) == node;
};

/**
 * Inserts the given new node after the given reference node.
 * (Similar to W3C Node.insertBefore.)
 */
Util.Node.insert_after = function(new_node, ref_node)
{
	if ( ref_node == ref_node.parentNode.lastChild )
		ref_node.parentNode.appendChild(new_node);
	else
		ref_node.parentNode.insertBefore(new_node, ref_node.nextSibling);
};

// XXX: think of better names for the next two fxns? and more 
// generalized, so the first works with any node not just
// a tag. "surround_node_with_new_node" is too clunky and
// not quite correct anyway...what happens if the new node
// already has children. "remove_node_but_keep_its_children"
// is also too clunky.

/**
 * Surrounds the given node with an element of the given tagname, 
 * and returns the new surrounding elem.
 */
Util.Node.surround_with_tag = function(node, tagname)
{
	var new_elem = node.ownerDocument.createElement(tagname);
	Util.Node.surround_with_node(node, new_elem);
	return new_elem;
};

/**
 * Surrounds the given inner node with the given outer node.
 */
Util.Node.surround_with_node = function(inner_node, outer_node)
{
	inner_node.parentNode.insertBefore(outer_node, inner_node);
	outer_node.appendChild(inner_node);
};

/**
 * Replaces given node with its children, e.g.
 * lkj <em>asdf</em> jkl becomes, after replace_with_children(em_node),
 * lkj asdf jkl
 */
Util.Node.replace_with_children = function(node)
{
	// XXX XXX: this doesn't work correctly right now

	// if the node's parent is null, it's already been removed
	if ( node.parentNode == null )
	{
		return;
	}

/*
	// Take all the children of node and move them, 
	// one at a time, immediately before node in the tree.
	// Then, node being empty, remove node.
	var a = [];
	for ( var i = 0; i < node.childNodes.length; i++ )
		//a.push(node.removeChild(node.firstChild)); // this is dangerous...can result in data 
													 // loss if there's an error
		a.push(node.firstChild);
	for ( var i = 0; i < a.length; i++ )
	{
		if ( node.nextSibling != null )
		{
			alert('node.nextSibling != null');
			node.nextSibling.insertBefore(a[i], node.nextSibling);
		}
		else
		{
			alert('node.nextSibling == null');
			node.appendChild(a[i]);
		}
		//node.parentNode.insertBefore(a[i], node);
	}
	node.parentNode.removeChild(node);
*/

	while ( node.hasChildNodes() )
	{
		node.parentNode.insertBefore( node.removeChild(node.firstChild), node);
	}
	node.parentNode.removeChild(node);
};

/**
 * Moves all children and attributes from old_node to new_node. 
 *
 * If old_node is within a DOM tree (i.e., has a non-null parentNode),
 * it is replaced in the tree with new_node. (Since new_node now has
 * all of old_node's former children, the tree is otherwise exactly as 
 * it was before.)
 *
 * If old_node is not within a DOM tree (i.e., has a null parentNode),
 * old_node's children and attrs are moved to new_node, but new_node
 * is not added to any DOM tree (nor is any error thrown).
 * 
 * E.g.,
 *   asdf <i>inside</i> jkl;    
 * becomes, after swap_node(em_elem, i_elem),
 *   asdf <em>inside</em> jkl;
 */
Util.Node.swap_node = function(new_node, old_node)
{
	for ( var i = 0; i < old_node.attributes.length; i++ )
	{
		var attr = old_node.attributes.item(i);
		new_node.setAttributeNode(attr.cloneNode(true));
	}
	while ( old_node.firstChild != null )
	{
		new_node.appendChild( old_node.removeChild(old_node.firstChild) );
	}
	if ( old_node.parentNode != null )
		old_node.parentNode.replaceChild(new_node, old_node);
};

/**
 * Returns the previous sibling of the node that matches the given test,
 * or null if there is none.
 */
Util.Node.previous_matching_sibling = function(node, boolean_test)
{	
	for (var sib = node.previousSibling; sib != null; sib = sib.previousSibling) {
		if (boolean_test(sib))
			return sib;
	}
	
	return null;
};

/**
 * Returns the next sibling of the node that matches the given test,
 * or null if there is none.
 */
Util.Node.next_matching_sibling = function(node, boolean_test)
{	
	for (var sib = node.nextSibling; sib != null; sib = sib.nextSibling) {
		if (boolean_test(sib))
			return sib;
	}
	
	return null;
};

/**
 * Returns the previous sibling of the node that is an element node,
 * or null if there is none.
 */
Util.Node.previous_element_sibling = function(node)
{
	return Util.Node.previous_matching_sibling(node, function(n) {
		return n.nodeType == Util.Node.ELEMENT_NODE;
	})
};

/**
 * Returns the next sibling of the node that is an element node,
 * or null if there is none.
 */
Util.Node.next_element_sibling = function(node)
{
	return Util.Node.next_matching_sibling(node, function(n) {
		return n.nodeType == Util.Node.ELEMENT_NODE;
	})
};

// end file Util.Node.js

