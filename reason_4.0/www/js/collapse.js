///<summary>
///Expand or collapse rows in Reason selection pages
///with using a cookie to store the state during the brower session
///</summary>
///<remarks>
///By: Henry Gross
///April 13, 2007
///</remarks>

var COOKIE_REGEX = /expansion=(.*?)(;|$)/
var CLOSE_TOGGLER = "/reason_package/reason_4.0/www/ui_images/item_closed.gif";
var OPEN_TOGGLER = "/reason_package/reason_4.0/www/ui_images/item_open.gif";
var table;

///<summary>
///collapse a node hiding all of it's children, setting expanded to false, and updating the cookie
///</summary>
///<remarks>
///There is a hidden optional parameter which should be set to false if we don't want to set the cookie
///</remarks>
///<param name="node">the node to collapse</param>
function collapse(node) {
	var images;
	var recurse;
	var changeCookie;

	changeCookie = true;
	if (arguments.length ==2)
		changeCookie = arguments[1]
	if (node.display == "none")
		recurse = false;
	else
		recurse = true;
	node.expanded = false;
	images = node.getElementsByTagName("img");
	if (images.length == 1)
		if (images[0].parentNode.className.match("Toggler"))
			images[0].src = CLOSE_TOGGLER;
	if (recurse)
		for (var i in table)
			if (isChildOf(table[i], node))
				hide(table[i]);
	if (changeCookie)
		setCookie(node);
}

///<summary>
///hide a node and all of it's descendents
///</summary>
///<param name="node">the node to hide</param>
function hide(node) {
	node.style.display = "none";
	for (var i in table)
		if (isChildOf(table[i], node))
			hide(table[i]);
}

///<summary>
///expand a node by showing all of it's children, setting expanded to true, and updateing the cookie
///</summary>
///<remarks>
///There is a hidden optional parameter which should be set to false if we don't want to set the cookie
///</remarks>
///<param name="node">the node to expand</param>
function expand(node) {
	var images;
	var changeCookie;

	changeCookie = true;
	if (arguments.length ==2)
		changeCookie = arguments[1]
	node.expanded = true;
	images = node.getElementsByTagName("img");
	if (images.length == 1)
		if (images[0].parentNode.className.match("Toggler"))
			images[0].src = OPEN_TOGGLER;
	for (var i in table)
		if (isChildOf(table[i], node))
			show(table[i]);
	if (changeCookie)
		setCookie(node);
}

///<summary>
///expand nodes as needed to reveal the specified node
///</summary>
///param name="node">the node to reveal</param>
function expandTo(node) {
	var parentId;
	var parent;

	parentId = /childOf([0-9]*)/.exec(node.className)[1] + "row";
	parent = document.getElementById(parentId);
	expand(parent, false);
	if (parent.className.match("childOf"))
		expandTo(parent);
}

///<summary>
///display the node and if any of it's children are suppossed to be expanded, show their children also
///</summary>
///<param name="node">the node to show</param>
function show(node) {
	node.style.display = "";
	if (node.expanded)
		for (var i in table)
			if (isChildOf(table[i], node))
				show(table[i]);
}

///<summary>
///Check to see if child is actually a child of parent based on the childOf class and the parents Id
///</summary>
///<param name="child">The alleged child</param>
///<param name="parent">The alleged parent</param>
///<returns>True if child is a child of parent</returns>
function isChildOf(child, parent) {
	var parentId;
	var childClassName;
	var childClasses;
	var childClass;
	
	parentId = parent.id.substring(0, parent.id.length - 3)
	childClassName = child.className;
	childClasses = childClassName.split(" ");
	for (var i in childClasses)
		if (childClasses[i].substring(0, 7) == "childOf")
			childClass = childClasses[i].substring(7);
	if (parentId == childClass)
		return true;
	else
		return false;
}

///<summary>
///Gets the value of the cookie that contains which rows are expanded, and the expanded value of the node specified
///</summary>
///<param name="node">The node to find the expanded value of</param>
///<returns>An array containing [0] The full value of the cookie containing which rows are expanded
///				[1] Everything until the expanded value of the specified node
///				[2] The expanded value of the specified node
///				[3] Everything after the expanded value of the specified node
///			or undefined if the cookie isn't set
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
///<returns>True if the node is to be expanded, false if it is to be collapsed, and undefined if it doesn't exist in the cookie</returns>
function readCookie(node) {
	var cookie;
	
	cookie = getCookie(node);
	if (cookie == undefined)
		return undefined;
	else if (cookie[2] == "true")
		return true
	else if (cookie[2] == "false")
		return false
}

///<summary>
///Add a click listener to the toggler links to toggle the expanded state of the row
///</summary>
function addListeners() {
	var links;
	for (var i in table) {
		links = table[i].getElementsByTagName("a");
		for (var j = 0; j < links.length; j++)
			if(links[j].className.match("Toggler") != null)
				links[j].onclick = toggle;
	}
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
///Things that need to be taken care of on load
///Loads the previous state, or expands it if it didn't have a prevous state
///Add listeners so that we can actually expand/collapse 
///</summary>
function init() {
	var divs;
	var rows;
	var hidden;
	
	table = new Array();
	divs = document.getElementsByTagName("div");
	for (var i = 0; i < divs.length; i++)
		if(divs[i].className == "list") {
			rows = divs[i].getElementsByTagName("tr");
			for (var j = 0; j < rows.length; j++) {
				table.push(rows[j]);
			}
			for (var j = 0; j < rows.length; j++) {
				if (rows[j].style.display == "none")
					hidden = true;
				else
					hidden = false;
				if (readCookie(rows[j]) == undefined)
					collapse(rows[j]);
				else if (readCookie(rows[j]))
					expand(rows[j]);
				else
					collapse(rows[j]);
				if (hidden)
					hide(rows[j]);
				if (!rows[j].className.match("childOf"))
					expand(rows[j]);
				if (rows[j].className.match("highlightRow"))
					expandTo(rows[j]);
			}
		}
	addListeners();
}

if (window.addEventListener)
	window.addEventListener("load", init, true);
else if (window.attachEvent)
	window.attachEvent("onload", init);
