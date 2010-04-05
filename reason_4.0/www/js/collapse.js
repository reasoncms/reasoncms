///<summary>
///Expand or collapse rows in Reason selection pages
///with using a cookie to store the state during the brower session
///</summary>
///<remarks>
///By: Henry Gross
///April 23, 2007
///</remarks>

$(document).ready(function()
{
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

	//IE doesn't have this object, which is needed, so we are just creating what we need
	if (!window.Node) {
		Node = new Object();
		Node.ELEMENT_NODE = 1;
	}

	
	// run the thing
	init();
	
	///<summary>
	///Collapse a node, hiding all of its descendents and setting the cookie appropriately
	///</summary>
	///<param name="node">The node to collapse</param>
	function collapse(node) {
		node.expanded = false;
		images = node.getElementsByTagName("img");
		if (images.length == 1)
			if (images[0].parentNode.className.match("Toggler"))
				images[0].src = CLOSE_TOGGLER;
		setCookie(node);
		for (var i = 0; i < node.childs.length; i++)
			hide(node.childs[i]);
	}
	
	///<summary>
	///Hides a node and all of its descendents
	///</summary>
	///<param name="node">The node to hide</param>
	function hide(node) {
		node.style.display = "none";
		for (var i = 0; i < node.childs.length; i++)
			hide(node.childs[i]);
	}
	
	///<summary>
	///Expands a node, showing all of its descendents (that are descended through only expanded nodes) and setting the cookie appropriately
	///</summary>
	///<param name="node">The node to expand</param>
	function expand(node) {
		node.expanded = true;
		images = node.getElementsByTagName("img");
		if (images.length == 1)
			if (images[0].parentNode.className.match("Toggler"))
				images[0].src = OPEN_TOGGLER;
		setCookie(node);
		for (var i = 0; i < node.childs.length; i++)
			show(node.childs[i]);	
	}
	
	///<summary>
	///Shows a node and if its descendents are expanded, shows them too
	///</summary>
	///<param name="node">The node to show</param>
	function show(node) {
		node.style.display = "";
		if (node.expanded)
			for (var i = 0; i < node.childs.length; i++)
				show(node.childs[i]);
	}
	
	///<summary>
	///Marks all the ancestors of a node to be expanded so that the node will be visible
	///</summary>
	///<param name="node">the node to be visible</param>
	function expandTo(node) {
		node = node.parent;
		while (node != null && node.toExpand == false) {
			node.toExpand = true;
			node = node.parent;
		}
	}
	
	///<summary>
	///Traverse through the tree expanding the nodes that have been mmarked to be expanded
	///</summary>
	///<param name="node">The node to start expanding with</param>
	function expandSelected(node) {
		if (node.toExpand) {
			node.expanded = true;
			images = node.getElementsByTagName("img");
			if (images.length == 1)
				if (images[0].parentNode.className.match("Toggler"))
					images[0].src = OPEN_TOGGLER;
			for (var i = 0; i < node.childs.length; i++) {
				node.childs[i].style.display = "";
				if (node.childs[i].toExpand == true)
					expandSelected(node.childs[i]);
			}
		}
	}
	
	///<summary>
	///Gets the value of the cookie that contains which rows are expanded, and the expanded value of the node specified
	///</summary>
	///<param name="node">The node to find the expanded value of</param>
	///<returns>An array containing [0] The full value of the cookie containing which rows are expanded
	///				[1] Everything until the expanded value of the specified node
	///				[2] The expanded value of the specified node
	///				[3] Everything after the expanded value of the specified node
	///				or undefined if the cookie isn't set
	///</returns>
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
	
	///<summary>
	///Set the expanded value of the specified node in the cookie, creating the cookie if needed
	///</summary>
	///<param name="node">The node to set the expanded value of in the cookie</param>
	function setCookie(node) {
		var cookie;
		
		cookie = getCookie(node);
		if (cookie == undefined) {
			cookie = COOKIE_REGEX.exec(document.cookie);
			if (cookie == undefined)
				document.cookie = "expansion=" + node.id + "=" + node.expanded + "; path=/";
			else {
				cookie = cookie[1];
				document.cookie = "expansion=" + cookie + "," + node.id + "=" + node.expanded + "; path=/";
			}
		}
		else {
			document.cookie = "expansion=" + cookie[1] + node.expanded + cookie[3] + "; path=/";
		}
	}
	
	
	
	///<summary>
	///Get the expanded value of the specified node from the cookie
	///</summary>
	///<param name="node">The node to find the expanded value of</param>
	///<returns>True if the node is to be expanded, false if it is to be collapsed or if it doesn't exist in the cookie</returns>
	function readCookie(node) {
		var cookie;
		
		cookie = getCookie(node);
		if (cookie == undefined)
			return false;
		else if (cookie[2] == "true")
			return true
		else if (cookie[2] == "false")
			return false
	}
	
	///<summary>
	///Add a click listener to the toggler links to toggle the expanded state of the row
	///</summary>
	function addListener(node) {
		var links;
		
		links = node.getElementsByTagName("a");
		for (var j = 0; j < links.length; j++)
			if(links[j].className.match("Toggler") != null)
				links[j].onclick = toggle;
	}
	
	///<summary>
	///Toggle the expanded state of the row based upon the click event received
	///</summary>
	///<param name="e">The click event which triggered the toggle</param>
	function toggle(e) {
		var target;
		
		target = (e != undefined) ? e.target : window.event.srcElement;
		while (target.tagName.toLowerCase() != "tr")
			target = target.parentNode;
		if (target.expanded)
			collapse(target);
		else
			expand(target);
	}
	
	///<summary>
	///Takes a html table and creates a treee showing the parent-child relationships of its rows.
	///Also gets previous state from a cookie and only shows the rows that were previously visible.
	///Will also expand so items that were searched for are visible.
	///</summary>
	///<param name="table"> A HTML table element to create a tree from</param>
	function buildTree(table) {
		for (var i = 0; i < table.childNodes.length; i++)
			if (String(table.childNodes[i].tagName).toLowerCase() == "tbody") {
				var node;
				var currParent;
	
				node = table.childNodes[i].firstChild;
				while (node != null) {
					if (node.nodeType == Node.ELEMENT_NODE && node.tagName.toLowerCase() == "tr")
						if (node.id.match("row")) {
							node.key = node.id.substring(0, node.id.length - 3);
							node.childs = new Array();
							node.parent = null;
							node.toExpand = false;
							addListener(node);
							if (readCookie(node)) {
								node.expanded = true;
								images = node.getElementsByTagName("img");
								if (images.length == 1)
									if (images[0].parentNode.className.match("Toggler"))
										images[0].src = OPEN_TOGGLER;
							}
							else
								node.expanded = false;
							if (!node.className.match("childOf")) {
								tree.push(node);
								currParent = node;
							} else {
								var nodeClasses = node.className.split(" ");
									for (var nodeClass in nodeClasses)
									if (nodeClasses[nodeClass].substring(0, 7) == "childOf")
										var parentKey = nodeClasses[nodeClass].substring(7);
								while (parentKey != currParent.key)
									currParent = currParent.parent;
								node.parent = currParent;
								currParent.childs.push(node);
								currParent = node;
							}
							if (node.className.match("highlightRow"))
								expandTo(node);
						}
					node = node.nextSibling;
				}
			}
		for (var i = 0; i < tree.length; i++) {
			//printTree(tree[i]);
			if (tree.length == 1)
				tree[i].expanded = true;
			syncTree(tree[i], true);
			expandSelected(tree[i]);
		}
	}
	
	///<summary>
	///traverses the tree and hides or shows each node depending on its visibility
	///</summary>
	///<param name="node">the node to hide or show</param>
	///<param name="show">True if the node should be visible</param>
	function syncTree(node, show) {
		if (!show)
			node.style.display = "none";
		for (var i = 0; i < node.childs.length; i++)
			if (!show)
				syncTree(node.childs[i], false);
			else
				syncTree(node.childs[i], node.expanded);
	}
	
	///<summary>
	///Print the tree depth-first starting from node using alerts
	///</summary>
	///<param name="node">the node to strat printing the tree from</param>
	function printTree(node) {
		alert (node.key)
		for (var i = 0; i < node.childs.length; i++) {
			printTree(node.childs[i]);
		}
	}
	
	///<summary>
	///Things that need to be taken care of on load
	///Loads the previous state, or expands it if it didn't have a prevous state
	///Add listeners so that we can actually expand/collapse 
	///</summary>
	function init() {
		var divs;
		var rows;
		var hidden;
		
		table = new Array();
		tree = new Array();
		divs = document.getElementsByTagName("div");
		for (var i = 0; i < divs.length; i++)
			if(divs[i].className == "list") {
				var tables = divs[i].getElementsByTagName("table");
				for (var j = 0; j < tables.length; j++)
					buildTree(tables[j]);
			}
	}
	

});
