/**
 * Does nothing.
 * @constructor
 *
 * @class <p>Contains methods related to producing clean, valid,
 * elegant HTML from the mess produced by the designMode = 'on'
 * components. </p>
 *
 * <p>JSDoc doesn't work well with this file. See the code for more
 * details about how it works.</p>
 */
UI.Clean = new Object;

// List originally taken from <http://hacks.oreilly.com/pub/h/4110>, by 
// Mark Pilgrim <http://diveintomark.org/projects/greasemonkey/>
UI.Clean.special_char_replacements = 
{
	"\xa0": " ",
	"\xa9": "(c)",
	"\xae": "(r)",
	"\xb7": "*",
	"\u2018": "'",
	"\u2019": "'",
	"\u201c": '"',
	"\u201d": '"',
	"\u2026": "?",
	"\u2002": " ",
	"\u2003": " ",
	"\u2009": " ",
	"\u2013": "-",
	"\u2014": "--",
	"\u2122": "(tm)",
	"\u2026": " . . . "
};
UI.Clean.special_char_regexps = {};
for (var k in UI.Clean.special_char_replacements)
{
	UI.Clean.special_char_regexps[k] = new RegExp(k, 'g');
}

/**
 * Cleans the children of the given root.
 *
 * @param	root	reference to the node whose children should be cleaned
 */
UI.Clean.clean = function(root, settings)
{
	/**
	 * Removes the given node from the tree.
	 */
	function remove_node(node)
	{
		// if the node's parent is null, it's already been removed
		if ( node.parentNode == null )
			return;

		node.parentNode.removeChild(node);
	}

	/**
	 * Remove the tag from the given node. (See description in
	 * fxn body how this is done.) E.g.,
	 * node.innerHTML = '<p><strong>Well</strong>&emdash;three thousand <em>ducats</em>!</p>'
	 *   -->
	 * node.innerHTML = '<strong>Well</strong>&emdash;three thousand <em>ducats</em>!'
	 */
	function remove_tag(node) 
	{
		Util.Node.replace_with_children(node);
	}

	/**
	 * Change the tag of the given node to being one with the given tagname. E.g.,
	 * node.innerHTML = '<p><b>Well</b>&emdash;three thousand <em>ducats</em>!</p>'
	 *   -->
	 * node.innerHTML = '<p><strong>Well</strong>&emdash;three thousand <em>ducats</em>!</p>'
	 */
	function change_tag(node, new_tagname)
	{
		// if the node's parent is null, it's already been removed or changed
		// (possibly not necessary here)
		if ( node.parentNode == null )
			return;

		// Create new node
		var new_node = node.ownerDocument.createElement(new_tagname);

		// Take all the children of node and move them, 
		// one at a time, to the new node.
		// Then, node being empty, remove node.
		while ( node.hasChildNodes() )
		{
			new_node.appendChild(node.firstChild);
		}
		node.parentNode.replaceChild(new_node, node);

		// TODO: take all attributes from old node -> new node
	}

	/**
	 * Remove the given attributes from the given node.
	 */ 
	function remove_attributes(node, attrs)
	{
		try
		{
		for ( var i = 0; i < attrs.length; i++ )
		{
			if ( node.getAttribute(attrs[i]) != null )
				node.removeAttribute(attrs[i]);
		}
		}
		catch(e) { mb('error in remove_attributes: ', e.message); }
	}

	/**
	 * Checks whether the given node has the given attributes.
	 * Returns false or an array of attrs (names) that are had.
	 */
	function has_attributes(node, all_attrs)
	{
		var had_attrs = [];
		if ( node.nodeType == Util.Node.ELEMENT_NODE )
		{
			for ( var i = 0; i < all_attrs.length; i++ )
			{
				// Sometimes in IE node.getAttribute throws an "Invalid argument"
				// error here. I have _no_ idea why, but we want to catch it
				// here so that the rest of the tests run.  XXX figure out why?
				try
				{
					if ( node.getAttribute(all_attrs[i]) != null )
						had_attrs.push(all_attrs[i]);
				}
				catch(e) { /*mb('error in has_attributes: [node, e.message]: ', [node, e.message]);*/ }
			}
		}
		
		return ( had_attrs.length > 0 )
			? had_attrs
			: false;
	}

	/**
	 * Checks whether the given node has one of the given tagnames.
	 */
	function has_tagname(node, tagnames)
	{
		if ( node.nodeType == Util.Node.ELEMENT_NODE )
		{
			for ( var i = 0; i < tagnames.length; i++ )
			{
				if ( node.tagName == tagnames[i] )
				{
					return true;
				}
			}
		}
		// otherwise
		return false;
	}

	/**
	 * Checks whether the given node does not have one of the 
	 * given tagnames.
	 */
	function doesnt_have_tagname(node, tagnames)
	{
		if ( node.nodeType == Util.Node.ELEMENT_NODE )
		{
			for ( var i = 0; i < tagnames.length; i++ )
			{
				if ( node.tagName == tagnames[i] )
				{
					return false;
				}
			}
			// otherwise, it's a tag that doesn't have the tagname
			return true
		}
		// otherwise, it's not a tag
		return false;
	}

	/**
	 * Checks whether the given node has any attributes
	 * matching the given strings.
	 */
	function has_class(node, strs)
	{
		var matches = [];
		if ( node.nodeType == Util.Node.ELEMENT_NODE )
		{
			for ( var i = 0; i < strs.length; i++ )
			{
				if ( Util.Element.has_class(node, strs[i]) )
					matches.push(strs[i]);
			}
		}
		
		if ( matches.length > 0 )
			return matches;
		else
			return false;
	}

	/**
	 * Removes all attributes matching the given strings.
	 */
	function remove_class(node, strs)
	{
		for ( var i = 0; i < strs.length; i++ )
		{
			Util.Element.remove_class(node, strs[i]);
		}
	}

	/**
	 * Checks whether the tag has a given (e.g., MS Office) prefix.
	 */
	function has_prefix(node, prefixes)
	{
		if ( node.nodeType == Util.Node.ELEMENT_NODE )
		{
			for ( var i = 0; i < prefixes.length; i++ )
			{
				if ( node.tagName.indexOf(prefixes[i] + ':') == 0 ||
					 node.scopeName == prefixes[i] )
					return true;
			}
		}
		// otherwise
		return false;
	};
	
	var allowable_tags = settings.allowable_tags ||
		['A', 'ABBR', 'ACRONYM', 'ADDRESS', 'AREA', 'B', 'BDO', 'BIG', 'BLOCKQUOTE', 'BR', 'BUTTON', 'CAPTION', 'CITE', 'CODE', 'COL', 'COLGROUP', 'DD', 'DEL', 'DIV', 'DFN', 'DL', 'DT', 'EM', 'FIELDSET', 'FORM', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'HR', 'I', 'IMG', 'INPUT', 'INS', 'KBD', 'LABEL', 'LI', 'MAP', 'NOSCRIPT', 'OBJECT', 'OL', 'OPTGROUP', 'OPTION', 'P', 'PARAM', 'PRE', 'Q', 'SAMP', 'SCRIPT', 'SELECT', 'SMALL', 'SPAN', 'STRONG', 'SUB', 'SUP', 'TABLE', 'TBODY', 'TD', 'TEXTAREA', 'TFOOT', 'TH', 'THEAD', 'TR', 'TT', 'U', 'UL', 'VAR'];

	tests =
	[
		// description : a text description of the test and action
		// test : function that is passed node in question, and returns
		//        false if the node doesn`t match, and whatever it wants 
		//        to be passed to the action otherwise.
		// action : function that is passed node and return of action, and 

		{
			description : 'Remove all comment nodes.',
			test : function(node) { return node.nodeType == Util.Node.COMMENT_NODE; },
			action : remove_node
		},
		{
			description : 'Remove all style nodes.',
			test : function(node) { return has_tagname(node, ['STYLE']); },
			action : remove_node
		},
		{
			description : 'Remove all bad attributes. (v:shape from Ppt)',
			test : function (node) { return has_attributes(node, ['style', 'v:shape']); },
			action : remove_attributes
		},
		{
			description : 'Remove all classes that include Mso (from Word) or O (from Powerpoint) in them.',
			test : function(node) { return has_class(node, ['O', 'Mso']); },
			action : remove_class
		},
		{
			description : 'Remove all miscellaneous bad tags.',
			test : function(node) { return has_tagname(node, ['SPAN']); },
			action : remove_tag
		},
		{
			description : 'Remove all miscellaneous non-good tags (strip_tags).',
			test : function(node) { return doesnt_have_tagname(node, allowable_tags); },
			action : remove_tag
		},
		// STRONG -> B, EM -> I should be in a Masseuse; then exclude B and I here
		// CENTER -> P(align="center")
		// H1, H2 -> H3; H5, H6 -> H4(? or -> P)
		// Axe form elements?
		{
			description : "Remove U unless there's an appropriate option set.",
			test : function(node) { return !settings.options.test('underline') && has_tagname(node, ['U']); },
			action : function(node) 
			{
				/* buggy (actually it's not--the bug is in remove_tag--but leave this
				   commented until we figure out that bug)
				// We don't want to replace with EM if it's already EM'd; but otherwise we do
				var boolean_test = function(node)
				{
					return ( node.nodeType == Util.Node.ELEMENT_NODE &&
							 ( node.tagName == 'EM' || node.tagName == 'I' ) );
				};
				if ( !Util.Node.has_ancestor_node(node, boolean_test) &&
					 !Util.Node.has_child_node(node, boolean_test) )
					change_tag(node, 'EM');
				else
					remove_tag(node);
				*/
				remove_tag(node);
			}
		},
		{
			description : 'Remove all tags that have Office namespace prefixes.',
			test : function(node) { return has_prefix(node, ['o', 'O', 'w', 'W', 'st1', 'ST1']); },
			action : remove_tag
		},
		{
			description : 'Remove width and height attrs on images and tables.',
			test : function(node) { return has_tagname(node, ['TABLE', 'IMG']); },
			action : function(node) { remove_attributes(node, ['height', 'width']); }
		},
		/*
		{
			description : 'Strip https and http in img.src',
			test : function(node) { return has_tagname(node, ['IMG']); },
			action : function(node) 
			{
				if ( node.getAttribute('src') != null )
					node.setAttribute('src', Util.URI.strip_https_and_http(node.getAttribute('src')));
			}
		},
		{
			description : 'Strip https and http in a.href',
			test : function(node) { return has_tagname(node, ['A']); },
			action : function(node) 
			{
				if ( node.getAttribute('href') != null )
					node.setAttribute('href', Util.URI.strip_https_and_http(node.getAttribute('href')));
			}
		},
		*/
		{
			description: 'Remove protocol from links on the current server',
			test: function(node) { return has_tagname(node, ['A']); },
			action: function(node)
			{
				var href = node.getAttribute('href');
				if (href != null) {
					node.setAttribute('href',
						UI.Clean.cleanURI(href));
				}
			}
		},
		{
			description : 'Convert curly quotes and such to normal ones',
			test : function(node) { return node.nodeType == Util.Node.TEXT_NODE; },
			action : function(node) 
			{
				var text = node.data;
				for (var k in UI.Clean.special_char_replacements)
				{
					text = text.replace(UI.Clean.special_char_regexps[k], UI.Clean.special_char_replacements[k]);
				}
				node.data = text;
			}
		}
		// TODO: deal with this?
		// In content pasted from Word, there may be 
		// ...<thead><tr><td>1</td></tr></thead>...
		// instead of
		// ...<thead><tr><th>1</th></tr></thead>...
	];

	function _clean_recursive(root)
	{
/*
		var children = [];
		for ( var i = 0; i < root.childNodes.length; i++ )
			children.push(root.childNodes[i]);
*/
		var children = root.childNodes;
		// we go backwards because remove_tag uses insertBefore,
		// so if we go forwards some nodes will be skipped
		//for ( var i = 0; i < children.length; i++ )
		for ( var i = children.length - 1; i >= 0; i-- )
		{
			var child = children[i];
			_clean_recursive(child); // we need depth-first, or remove_tag
			                         // will cause some nodes to be skipped
			_run_tests(child);
		}
	}

	function _run_tests(node)
	{
		for ( var i = 0; i < tests.length; i++ )
		{
			var result = tests[i].test(node);
			if ( result !== false )
			{
				// We do this because we don't want any errors to
				// result in lost content!
				try
				{
					tests[i].action(node, result);
					mb('did action "' + tests[i].description + '" on node with result', [node, result]);
				}
				catch(e)
				{
					mb('UI.Clean: tests failed: [node, result, error]', [node, result, e]);
					throw(e); // XXX tmp, for testing
				}
			}
		}
	}

	// We do this because we don't want any errors to result in lost content!
	try
	{
		_clean_recursive(root);
	}
	catch(e)
	{
		mb('UI.Clean: _clean_recursive failed: error', e.message);
		//throw(e); // XXX tmp, for testing
	}
};

UI.Clean.cleanURI = function(uri)
{
	var local = Util.URI.extract_domain(uri) ==
		Util.URI.extract_domain(window.location);
		
	return (local)
		? Util.URI.strip_https_and_http(uri)
		: uri;
}

UI.Clean.cleanHtml = function(html, settings)
{
    // empty elements (as defined by HTML 4.01)
    var empty_elems = '(br|area|link|img|param|hr|input|col|base|meta)';

	var tests =
	[
		// description : a text description of the test and action
        // test: only do the replacement if this is true 
        //       (optional--if omitted, the replacement will always be performed)
		// pattern : either a regexp or a string to match
		// replacement : a string to replace pattern with

		{
			description : 'Forces all empty elements (with attributes) to include trailing slash',
            //                     [ ]      : whitespace between element name and attrs
            //                     [^>]*    : any chars until one char before the final >
            //                     [^>/]    : the char just before the the final >. 
            //                                This excludes elements that already include trailing slashes.
            test : function() { return settings.use_xhtml },
			pattern : new RegExp('<' + empty_elems + '([ ][^>]*[^>/])>', 'gi'),
			replacement : '<$1$2 />'
		},
		{
			description : 'Forces all empty elements (without any attributes) to include trailing slash',
            test : function() { return settings.use_xhtml },
			pattern : new RegExp('<' + empty_elems + '>', 'gi'),
			replacement : '<$1 />'
		}
    ];


    mb('UI.Clean.cleanHtml: html before clean', html);
    for (var i in tests)
        if (tests[i].test == null || tests[i].test())
            html = html.replace(tests[i].pattern, tests[i].replacement);
    //alert(html);
    mb('UI.Clean.cleanHtml: html after clean', html);

    return html;
};
