/*
 * Expand or collapse rows in the Reason pages content manager. 
 * Allows the use of cookies to store tree state, and auto-expands 
 * to rows that are results of searches (highlighted rows). 
 * 
 * This is an overhauled and jQuerified version of a script 
 * written by Henry Gross for use with Reason, April 23, 2007.  
 * 
 * @author Henry Gross (original version)
 * @author Andrew Bacon (2010 revamp)
 */

$(document).ready(function()
{

	/** 
	 * Just defining a few useful variables.
	 */
	var webPath = new String();
	$("script[src$='/collapse.js']:first").each(function()
	{
	  var srcParts = $(this).attr("src").split("/js/");
	  webPath = srcParts[0];
	});
	
	var COOKIE_REGEX = /expansion=(.*?)(;|$)/
	var CLOSE_TOGGLER = webPath + "/ui_images/item_closed.gif";
	var OPEN_TOGGLER = webPath + "/ui_images/item_open.gif";
	var tree;

	/**
	 * And now we run the thing!
	 */
	init();

	/**
	 * A simple wrapper for hiding an element and its children in the (NOT DOM) tree.
	 * Adds a class which should be set to display: none;
	 */
	
	function hideNode(nodes)
	{
		$(nodes).addClass('hidden');
		$(nodes).each(function ()
		{
			hideNode(this.kids);
		});
	}
	
	/**
	 * 'Collapses' a given node by assigning a css class which has 
	 * { display: none; }, marks the internal representation of the node 
	 * as being collapsed, changes the image, and stores the state of the
	 * node in the cookie. 
	 * 
	 * @param object node the node to be collapsed.
	 * @return void
	 */
	function collapse(node)
	{
		node.expand = false;
		images = $('[class$="Toggler"] img', node);
		images.attr("src", CLOSE_TOGGLER);
		hideNode(node.kids)
	//	$(node.kids).hide();
		setCookie(node);
	}

	function showNode(nodes)
	{
		$(nodes).removeClass('hidden');
		$(nodes).each(function()
		{
			if (this.expand == true)
				showNode(this.kids);
		});
	}

	/**
	 * 'Expands' a given node by removing the the css class with 
	 * { display: none; }, marks the internal representation of the node 
	 * as being expanded, changes the image, and stores the state of the
	 * node in the cookie. 
	 * 
	 * @param object node the node to be collapsed.
	 * @return void
	 */	
	function expand(node)
	{
		node.expand = true;
		images = $('[class$="Toggler"] img', node);
		images.attr("src", OPEN_TOGGLER);
		showNode($(node.kids));
	//	$(node.kids).show();
		setCookie(node);
	};
	
	/**
	 * Expands all of a given node's direct ancestor nodes, so that we can
	 * see it properly. For highlighted nodes from the search.
	 * 
	 * Keep in mind that we're talking about 'node' ancestry,
	 * not DOM. 
	 *
	 * @param object node the node that we want to be able to see.
	 */
	function expandAncestors(node)
	{
		if (node.parent != undefined)
		{
			expand(node.parent);
			expandAncestors(node.parent);
		}
	}
	
	/**
	 * Loads in the cookie and slices it up twice: once to get only expansion
	 * values, and then once to get only the value for the relevant node. 
	 * 
	 * Returns a four-member array:
	 * [0] The full value of the cookie containing which rows are expanded
	 * [1] Everything until the expanded value of the specified node
	 * [2] The expanded value of the specified node
	 * [3] Everything after the expanded value of the specified node,
	 *     or undefined if the cookie isn't set
	 * 
	 * @param object node the node whose expanded value we want to get.
	 */
	function getCookie(node) {
		var cookie;
		var regex;
		
		cookie = COOKIE_REGEX.exec(document.cookie);
		if (cookie == undefined)
			return undefined;
		cookie = cookie[1];
		regex = new RegExp("(^.*?" + node.id + "=)(.*?)(,.*$|$)");
		return regex.exec(cookie);
	}
	
	
	/**
	 * Load the cookie, then add the value of node.expand.
	 * 
	 * 
	 * @param node The node that we're talking about
	 * @return void
	 */
	function setCookie(node) {
		var cookie;
		// Load the cookie.
		cookie = getCookie(node);
		// If no cookie is set for this node
		if (cookie == undefined) {
			cookie = COOKIE_REGEX.exec(document.cookie);
			// AND no cookie is set for any node
			if (cookie == undefined)
				// Set a new cookie with this node.
				document.cookie = "expansion=" + node.id + "=" + node.expand + "; path=/";
			// If there are other nodes set,
			else {
				// Stick this one at the end and append the rest of the (non-collapse.js-related) cookies at the end. 
				cookie = cookie[1];
				document.cookie = "expansion=" + cookie + "," + node.id + "=" + node.expand + "; path=/";
			}
		}
		else {
			// Stick this cookie in the middle. 
			document.cookie = "expansion=" + cookie[1] + node.expand + cookie[3] + "; path=/";
		}
	}
	

	/**
	 * Is there an expand value in the cookie for this node? If not, return false.
	 * If so, return its value.
	 * 
	 * @param node the node whose cookie we want to look for.  
	 * @return bool the expand value of the node or undefined if it's not set. 
	 */
	function readCookie(node) {
		cookie = getCookie(node);
		if (cookie == undefined)
			return false;
		else 
			return cookie[2]
	}
	
	
	function buildTree(table) {
		$(table).children("tbody").each(function () {
			var node;
			var lastPossibleParent;
			$(this).children('tr[id$="row"]').each(function () {
				node = this;
				id = $(this).attr("id")
				node.key = id.substr(0, id.length - 3);
				node.kids = new Array;
				node.parent = null;
			
				// You need to bind to an event handler for open/close toggle here.

				$('a[class$="Toggler"]', node).click(function (event) {
					node = $(this).parent().parent()[0];
					if (node.expand == true)
					{
						node.expand = false;
						collapse(node);
					} else {
						node.expand = true;
						expand(node);
					}
				});
								
				// If there's a cookie, check the cookie for the presence of this
				// node. If it's there,
				// --> node.expanded = true; 
				// --> change image to OPEN_TOGGLER
				// else set node.expanded = false;
				if (readCookie(node) == "true")
				{
					node.expand = true;
					images = $('[class$="Toggler"] img', node);
					images.attr("src", OPEN_TOGGLER);
				}
				
				// Is the node a child of another node?
				// If no, add it to the top of the tree and set it as the last possible parent!  
				if (!$(this).attr("class").match('childOf'))
				{
					tree.push(node);
					lastPossibleParent = node;
				} else {
					// Check the classes applied to the node. Find the 'childOf' element
					var nodeClasses = $(node).attr("class").split(" ");
					for (var nodeClass in nodeClasses)
						if (nodeClasses[nodeClass].substring(0, 7) == "childOf")
						// Get the entity id of the parent from the class.
							var parentKey = nodeClasses[nodeClass].substring(7);
					// Was the last node touched the parent? If not, try that node's parent!
					while (lastPossibleParent.key != parentKey)
						lastPossibleParent = lastPossibleParent.parent;
					node.parent = lastPossibleParent;
					lastPossibleParent.kids.push(node);
					lastPossibleParent = node;
				}
			});
				for (var i = 0; i < tree.length; i++) {
					if (tree.length == 1)
						tree[i].expand = true;
					syncTree(tree[i], true);
				}
				$(".highlightRow").each(function () {
					expandAncestors(this);
				})
		})
	}

	/** 
	 * Loops through the tree. Hides nodes with expand=false.
	 * Run once after buildTree().
	 * 
	 * @param object node the node in question
	 * @param show the value of the parent's node.expand. 
	 */
	function syncTree(node, show) {
		if (!show)
			//$(node).hide();
			$(node).addClass('hidden');
		for (var i = 0; i < node.kids.length; i++)
			if (!show)
				syncTree(node.kids[i], false);
			else
				syncTree(node.kids[i], node.expand);
	}
	
	/**
	 * A wrapper to do it all. Init some vars, then run buildTree
	 * on the relevant elements.
	 * 
	 * @return void
	 */
	function init() {
		var divs;
		var rows;
		var hidden;
		
		table = new Array();
		tree = new Array();
		tables = $("div.list table");
		tables.each(function () {
			buildTree(this);
		});
	}
	

});
