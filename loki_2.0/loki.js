// Loki WYSIWIG Editor 2.0.4-reason
// Copyright (c) 2006 Carleton College

// Compiled 2011-03-29 15:27:18 
// http://loki-editor.googlecode.com/


// file TinyMCE.js
/**
 * $RCSfile: tiny_mce_src.js,v $
 * $Revision: 1.233 $
 * $Date: 2005/08/26 15:20:32 $
 *
 * @author Moxiecode. Extracts made by NF starting 2005/10/14.
 * @copyright Copyright � 2004, Moxiecode Systems AB, All rights reserved.
 */
function TinyMCE() {};

/**
 * This new function written by NF to integrate with Loki.
 */
TinyMCE.prototype.init = function(win, selectedInstance)
{
	this.contentWindow = win;
	this.selectedInstance = selectedInstance;
	this.settings = { force_p_newlines : true };
	this.isGecko = true; // because we only call any of this from Gecko
	this.blockRegExp = new RegExp("^(h[1-6]|p|div|address|pre|form|table|li|ol|ul|td|blockquote)$", "i"); // nf: added blockquote
};

TinyMCE.prototype.isBlockElement = function(node) {
        return node != null && node.nodeType == 1 && this.blockRegExp.test(node.nodeName);
};

TinyMCE.prototype.getParentBlockElement = function(node) {
	// Search up the tree for block element
	while (node) {
		if (this.blockRegExp.test(node.nodeName))
			return node;

		node = node.parentNode;
	}

	return null;
};

TinyMCE.prototype.getNodeTree = function(node, node_array, type, node_name) {
	if (typeof(type) == "undefined" || node.nodeType == type && (typeof(node_name) == "undefined" || node.nodeName == node_name))
		node_array[node_array.length] = node;

	if (node.hasChildNodes()) {
		for (var i=0; i<node.childNodes.length; i++)
			tinyMCE.getNodeTree(node.childNodes[i], node_array, type, node_name);
	}

	return node_array;
};

TinyMCE.prototype.getAbsPosition = function(node) {
	var pos = {absLeft: 0, absTop: 0};
	
	// if (node.nodeType != 1)
	// 	node = node.parentNode;
	for (var n = node; n; n = n.offsetParent) {
		pos.absLeft += n.offsetLeft;
		pos.absTop += n.offsetTop;
	}
	
	return pos;
};

TinyMCE.prototype.cancelEvent = function(e) {
	if (tinyMCE.isMSIE) {
		e.returnValue = false;
		e.cancelBubble = true;
	} else
		e.preventDefault();
};


TinyMCE.prototype.handleEvent = function(e) {
	tinyMCE = this; // NF: because we don't want a global

	// Remove odd, error
	if (typeof(tinyMCE) == "undefined")
		return true;

	//tinyMCE.debug(e.type + " " + e.target.nodeName + " " + (e.relatedTarget ? e.relatedTarget.nodeName : ""));

	switch (e.type) {
		case "blur":
			if (tinyMCE.selectedInstance)
				tinyMCE.selectedInstance.execCommand('mceEndTyping');

			return;

		case "submit":
			tinyMCE.removeTinyMCEFormElements(tinyMCE.isMSIE ? window.event.srcElement : e.target);
			tinyMCE.triggerSave();
			tinyMCE.isNotDirty = true;
			return;

		case "reset":
			var formObj = tinyMCE.isMSIE ? window.event.srcElement : e.target;

			for (var i=0; i<document.forms.length; i++) {
				if (document.forms[i] == formObj)
					window.setTimeout('tinyMCE.resetForm(' + i + ');', 10);
			}

			return;

		case "keypress":
			/* NF: irrelevant
			if (e.target.editorId) {
				tinyMCE.selectedInstance = tinyMCE.instances[e.target.editorId];
			} else {
				if (e.target.ownerDocument.editorId)
					tinyMCE.selectedInstance = tinyMCE.instances[e.target.ownerDocument.editorId];
			}

			if (tinyMCE.selectedInstance)
				tinyMCE.selectedInstance.switchSettings();
			*/

			// Insert space instead of &nbsp;
			/*			
			if (tinyMCE.isGecko && e.charCode == 32) {
				if (tinyMCE.selectedInstance._insertSpace()) {
					// Cancel event
					e.preventDefault();
					return false;
				}
			}
			*/

			//Util.Object.print_r(tinyMCE);
			//alert(tinyMCE.settings['force_p_newlines']);

			// Insert P element
			if (tinyMCE.isGecko && tinyMCE.settings['force_p_newlines'] && e.keyCode == 13 && !e.shiftKey) {
				// Insert P element instead of BR
				if (tinyMCE.selectedInstance._insertPara(e)) {
					// Cancel event
					//tinyMCE.execCommand("mceAddUndoLevel"); // NF: irrelevant
					tinyMCE.cancelEvent(e);
					return false;
				}
			}

			// Handle backspace
			if (tinyMCE.isGecko && tinyMCE.settings['force_p_newlines'] && (e.keyCode == 8 || e.keyCode == 46) && !e.shiftKey) {
				// Insert P element instead of BR
				if (tinyMCE.selectedInstance._handleBackSpace(e.type)) {
					// Cancel event
					//tinyMCE.execCommand("mceAddUndoLevel"); // NF: irrelevant
					e.preventDefault();
					return false;
				}
			}
/*
			// Mozilla custom key handling
			if (tinyMCE.isGecko && e.ctrlKey && tinyMCE.settings['custom_undo_redo']) {
				if (tinyMCE.settings['custom_undo_redo_keyboard_shortcuts']) {
					if (e.charCode == 122) { // Ctrl+Z
						tinyMCE.selectedInstance.execCommand("Undo");

						// Cancel event
						e.preventDefault();
						return false;
					}

					if (e.charCode == 121) { // Ctrl+Y
						tinyMCE.selectedInstance.execCommand("Redo");

						// Cancel event
						e.preventDefault();
						return false;
					}
				}

				if (e.charCode == 98) { // Ctrl+B
					tinyMCE.selectedInstance.execCommand("Bold");

					// Cancel event
					e.preventDefault();
					return false;
				}

				if (e.charCode == 105) { // Ctrl+I
					tinyMCE.selectedInstance.execCommand("Italic");

					// Cancel event
					e.preventDefault();
					return false;
				}

				if (e.charCode == 117) { // Ctrl+U
					tinyMCE.selectedInstance.execCommand("Underline");

					// Cancel event
					e.preventDefault();
					return false;
				}
			}

			// Return key pressed
			if (tinyMCE.isMSIE && tinyMCE.settings['force_br_newlines'] && e.keyCode == 13) {
				if (e.target.editorId)
					tinyMCE.selectedInstance = tinyMCE.instances[e.target.editorId];

				if (tinyMCE.selectedInstance) {
					var sel = tinyMCE.selectedInstance.getDoc().selection;
					var rng = sel.createRange();

					if (tinyMCE.getParentElement(rng.parentElement(), "li") != null)
						return false;

					// Cancel event
					e.returnValue = false;
					e.cancelBubble = true;

					// Insert BR element
					rng.pasteHTML("<br />");
					rng.collapse(false);
					rng.select();

					tinyMCE.execCommand("mceAddUndoLevel");
					tinyMCE.triggerNodeChange(false);
					return false;
				}
			}

			// Backspace or delete
			if (e.keyCode == 8 || e.keyCode == 46) {
				tinyMCE.selectedElement = e.target;
				tinyMCE.linkElement = tinyMCE.getParentElement(e.target, "a");
				tinyMCE.imgElement = tinyMCE.getParentElement(e.target, "img");
				tinyMCE.triggerNodeChange(false);
			}

			return false;
		break;

		case "keyup":
		case "keydown":
			if (e.target.editorId)
				tinyMCE.selectedInstance = tinyMCE.instances[e.target.editorId];
			else
				return;

			if (tinyMCE.selectedInstance)
				tinyMCE.selectedInstance.switchSettings();

			var inst = tinyMCE.selectedInstance;

			// Handle backspace
			if (tinyMCE.isGecko && tinyMCE.settings['force_p_newlines'] && (e.keyCode == 8 || e.keyCode == 46) && !e.shiftKey) {
				// Insert P element instead of BR
				if (tinyMCE.selectedInstance._handleBackSpace(e.type)) {
					// Cancel event
					tinyMCE.execCommand("mceAddUndoLevel");
					e.preventDefault();
					return false;
				}
			}

			tinyMCE.selectedElement = null;
			tinyMCE.selectedNode = null;
			var elm = tinyMCE.selectedInstance.getFocusElement();
			tinyMCE.linkElement = tinyMCE.getParentElement(elm, "a");
			tinyMCE.imgElement = tinyMCE.getParentElement(elm, "img");
			tinyMCE.selectedElement = elm;

			// Update visualaids on tabs
			if (tinyMCE.isGecko && e.type == "keyup" && e.keyCode == 9)
				tinyMCE.handleVisualAid(tinyMCE.selectedInstance.getBody(), true, tinyMCE.settings['visual'], tinyMCE.selectedInstance);

			// Run image/link fix on Gecko if diffrent document base on paste
			if (tinyMCE.isGecko && tinyMCE.settings['document_base_url'] != "" + document.location.href && e.type == "keyup" && e.ctrlKey && e.keyCode == 86)
				tinyMCE.selectedInstance.fixBrokenURLs();

			// Fix empty elements on return/enter, check where enter occured
			if (tinyMCE.isMSIE && e.type == "keydown" && e.keyCode == 13)
				tinyMCE.enterKeyElement = tinyMCE.selectedInstance.getFocusElement();

			// Fix empty elements on return/enter
			if (tinyMCE.isMSIE && e.type == "keyup" && e.keyCode == 13) {
				var elm = tinyMCE.enterKeyElement;
				if (elm) {
					var re = new RegExp('^HR|IMG|BR$','g'); // Skip these
					var dre = new RegExp('^H[1-6]$','g'); // Add double on these

					if (!elm.hasChildNodes() && !re.test(elm.nodeName)) {
						if (dre.test(elm.nodeName))
							elm.innerHTML = "&nbsp;&nbsp;";
						else
							elm.innerHTML = "&nbsp;";
					}
				}
			}

			// Check if it's a position key
			var keys = tinyMCE.posKeyCodes;
			var posKey = false;
			for (var i=0; i<keys.length; i++) {
				if (keys[i] == e.keyCode) {
					posKey = true;
					break;
				}
			}

			//tinyMCE.debug(e.keyCode);

			// MSIE custom key handling
			if (tinyMCE.isMSIE && tinyMCE.settings['custom_undo_redo']) {
				var keys = new Array(8,46); // Backspace,Delete
				for (var i=0; i<keys.length; i++) {
					if (keys[i] == e.keyCode) {
						if (e.type == "keyup")
							tinyMCE.triggerNodeChange(false);
					}
				}

				if (tinyMCE.settings['custom_undo_redo_keyboard_shortcuts']) {
					if (e.keyCode == 90 && e.ctrlKey && e.type == "keydown") { // Ctrl+Z
						tinyMCE.selectedInstance.execCommand("Undo");
						tinyMCE.triggerNodeChange(false);
					}

					if (e.keyCode == 89 && e.ctrlKey && e.type == "keydown") { // Ctrl+Y
						tinyMCE.selectedInstance.execCommand("Redo");
						tinyMCE.triggerNodeChange(false);
					}

					if ((e.keyCode == 90 || e.keyCode == 89) && e.ctrlKey) {
						// Cancel event
						e.returnValue = false;
						e.cancelBubble = true;
						return false;
					}
				}
			}

			// Handle Undo/Redo when typing content

			// Start typing (non position key)
			if (!posKey && e.type == "keyup")
				tinyMCE.execCommand("mceStartTyping");

			// End typing (position key) or some Ctrl event
			if (e.type == "keyup" && (posKey || e.ctrlKey))
				tinyMCE.execCommand("mceEndTyping");

			if (posKey && e.type == "keyup")
				tinyMCE.triggerNodeChange(false);
		break;

		case "mousedown":
		case "mouseup":
		case "click":
		case "focus":
			if (tinyMCE.selectedInstance)
				tinyMCE.selectedInstance.switchSettings();

			// Check instance event trigged on
			var targetBody = tinyMCE.getParentElement(e.target, "body");
			for (var instanceName in tinyMCE.instances) {
				if (typeof(tinyMCE.instances[instanceName]) == 'function')
					continue;

				var inst = tinyMCE.instances[instanceName];

				// Reset design mode if lost (on everything just in case)
				inst.autoResetDesignMode();

				if (inst.getBody() == targetBody) {
					tinyMCE.selectedInstance = inst;
					tinyMCE.selectedElement = e.target;
					tinyMCE.linkElement = tinyMCE.getParentElement(tinyMCE.selectedElement, "a");
					tinyMCE.imgElement = tinyMCE.getParentElement(tinyMCE.selectedElement, "img");
					break;
				}
			}

			if (tinyMCE.isSafari) {
				tinyMCE.selectedInstance.lastSafariSelection = tinyMCE.selectedInstance.getBookmark();
				tinyMCE.selectedInstance.lastSafariSelectedElement = tinyMCE.selectedElement;

				var lnk = tinyMCE.getParentElement(tinyMCE.selectedElement, "a");

				// Patch the darned link
				if (lnk && e.type == "mousedown") {
					lnk.setAttribute("mce_real_href", lnk.getAttribute("href"));
					lnk.setAttribute("href", "javascript:void(0);");
				}

				// Patch back
				if (lnk && e.type == "click") {
					window.setTimeout(function() {
						lnk.setAttribute("href", lnk.getAttribute("mce_real_href"));
						lnk.removeAttribute("mce_real_href");
					}, 10);
				}
			}

			// Reset selected node
			if (e.type != "focus")
				tinyMCE.selectedNode = null;

			tinyMCE.triggerNodeChange(false);
			tinyMCE.execCommand("mceEndTyping");

			if (e.type == "mouseup")
				tinyMCE.execCommand("mceAddUndoLevel");

			// Just in case
			if (!tinyMCE.selectedInstance && e.target.editorId)
				tinyMCE.selectedInstance = tinyMCE.instances[e.target.editorId];

			// Run image/link fix on Gecko if diffrent document base
			if (tinyMCE.isGecko && tinyMCE.settings['document_base_url'] != "" + document.location.href)
				window.setTimeout('tinyMCE.getInstanceById("' + inst.editorId + '").fixBrokenURLs();', 10);

			return false;
*/
		break;
    } // end switch
}; // end function



// TinyMCEControl
function TinyMCEControl() {}

/**
  * This function written by NF to integrate with Loki.
  */
TinyMCEControl.prototype.init = function(win, targetElement, loki) {
	this.contentWindow = win;
	this.targetElement = targetElement;
	this.loki = loki;
};

TinyMCEControl.prototype._insertPara = function(e) {
	function isEmpty(para) {
		function isEmptyHTML(html) {
			return html.replace(new RegExp('[ \t\r\n]+', 'g'), '').toLowerCase() == "";
		}

		// Check for images
		if (para.getElementsByTagName("img").length > 0)
			return false;

		// Check for tables
		if (para.getElementsByTagName("table").length > 0)
			return false;

		// Check for HRs
		if (para.getElementsByTagName("hr").length > 0)
			return false;

		// Check all textnodes
		var nodes = tinyMCE.getNodeTree(para, new Array(), 3);
		for (var i=0; i<nodes.length; i++) {
			if (!isEmptyHTML(nodes[i].nodeValue))
				return false;
		}

		// No images, no tables, no hrs, no text content then it's empty
		return true;
	}

	// NF: added these to fit our way of doing things
	/*
	var doc = win.document;
	var sel = Util.Selection.get_selection(win);
	var rng = sel.getRangeAt(0);
	var body = doc.body;
	var rootElm = doc.documentElement;
	var blockName = "P";
	*/
	var doc = this.getDoc();
	var sel = this.getSel();
	var win = this.contentWindow;
	var rng = sel.getRangeAt(0);
	var body = doc.body;
	var rootElm = doc.documentElement;
	var self = this;
	var blockName = "P";

//	tinyMCE.debug(body.innerHTML);

//	debug(e.target, sel.anchorNode.nodeName, sel.focusNode.nodeName, rng.startContainer, rng.endContainer, rng.commonAncestorContainer, sel.anchorOffset, sel.focusOffset, rng.toString());

	// Setup before range
	var rngBefore = doc.createRange();
	rngBefore.setStart(sel.anchorNode, sel.anchorOffset);
	rngBefore.collapse(true);

	// Setup after range
	var rngAfter = doc.createRange();
	rngAfter.setStart(sel.focusNode, sel.focusOffset);
	rngAfter.collapse(true);

	// Setup start/end points
	var direct = rngBefore.compareBoundaryPoints(rngBefore.START_TO_END, rngAfter) < 0;
	var startNode = direct ? sel.anchorNode : sel.focusNode;
	var startOffset = direct ? sel.anchorOffset : sel.focusOffset;
	var endNode = direct ? sel.focusNode : sel.anchorNode;
	var endOffset = direct ? sel.focusOffset : sel.anchorOffset;

	startNode = startNode.nodeName == "BODY" ? startNode.firstChild : startNode;
	endNode = endNode.nodeName == "BODY" ? endNode.firstChild : endNode;

	// tinyMCE.debug(startNode, endNode);

	// Get block elements
	var startBlock = tinyMCE.getParentBlockElement(startNode);
	var endBlock = tinyMCE.getParentBlockElement(endNode);

	mb('startBlock, endBlock', [startBlock, endBlock]);
	// NF: But then check the parentBlock of the parentBlock, to see whether
	// it's a blockquote or highlight div. If so, then make that the start/endBlock.
	/*
	var startBlock2 = tinyMCE.getParentBlockElement(startBlock.parentNode);
	var endBlock2 = tinyMCE.getParentBlockElement(endBlock.parentNode);
	if ( startBlock2 != null &&
		 ( startBlock2.nodeName == 'BLOCKQUOTE' ||
		   ( startBlock2.nodeName == 'DIV' && 
		     Util.Element.has_class(startBlock2, 'callOut') ) ) )
	{
		mb('startBlock = startBlock2');
		startBlock = startBlock2;
	}
	if ( endBlock2 != null &&
		 ( endBlock2.nodeName == 'BLOCKQUOTE' ||
		   ( endBlock2.nodeName == 'DIV' && 
		     Util.Element.has_class(endBlock2, 'callOut') ) ) )
	{
		mb('endBlock = endBlock2');
		endBlock = endBlock2;
	}
	*/

	// Use current block name
	if (startBlock != null) {
		blockName = startBlock.nodeName;

		// Use P instead
		if (blockName == "TD" || blockName == "TABLE" || (blockName == "DIV" && new RegExp('left|right', 'gi').test(startBlock.style.cssFloat)))
		{
			blockName = "P";
		}
	}

	// NF: If we're inside pre, insert a BR instead of a new pre tag
	if ( blockName == 'PRE' )
	{
		var br_helper = (new UI.BR_Helper).init(this.loki);
		br_helper.insert_br();
		return true;
	}

	// NF: added this chunk, and changed all references below 
	// to block(Before|After)Name from blockName
	var blockBeforeName = blockName;
	var blockAfterName = blockName;
	if ( blockAfterName == "H1" || blockAfterName == "H3" || blockAfterName == "H4" || 
		 blockAfterName == "H5" || blockAfterName == "H6" ||
	     blockAfterName == "BLOCKQUOTE" || ( blockAfterName == "DIV" && Util.Element.has_class(startBlock, 'callOut') ) )
		var blockAfterName = 'P';

	// Within a list item (use normal behavior)
	if ((startBlock != null && startBlock.nodeName == "LI") || (endBlock != null && endBlock.nodeName == "LI"))
		return false;

	// Within a table create new paragraphs
	if ((startBlock != null && startBlock.nodeName == "TABLE") || (endBlock != null && endBlock.nodeName == "TABLE"))
		startBlock = endBlock = null;

	// Setup new paragraphs
	var paraBefore = (startBlock != null && startBlock.nodeName.toUpperCase() == blockBeforeName) ? startBlock.cloneNode(false) : doc.createElement(blockBeforeName);
	var paraAfter = (endBlock != null && endBlock.nodeName.toUpperCase() == blockAfterName) ? endBlock.cloneNode(false) : doc.createElement(blockAfterName);

	// Setup chop nodes
	//nf made these var startChop = startBlock == startBlock2 ? startNode.parentNode : startNode;
	// " var endChop = endBlock == endBlock2 ? endNode.parentNode : endNode;
	var startChop = startBlock;
	var endChop = endBlock;

	// Get startChop node
	node = startChop;
	do {
		if (node == body || node.nodeType == 9 || tinyMCE.isBlockElement(node))
			break;

		startChop = node;
	} while ((node = node.previousSibling ? node.previousSibling : node.parentNode));

	// Get endChop node
	node = endChop;
	do {
		if (node == body || node.nodeType == 9 || tinyMCE.isBlockElement(node))
			break;

		endChop = node;
	} while ((node = node.nextSibling ? node.nextSibling : node.parentNode));

	// Fix when only a image is within the TD
	if (startChop.nodeName == "TD")
		startChop = startChop.firstChild;

	if (endChop.nodeName == "TD")
		endChop = endChop.lastChild;

	// If not in a block element
	if (startBlock == null) {
		// Delete selection
		rng.deleteContents();
		sel.removeAllRanges();

		if (startChop != rootElm && endChop != rootElm) {
			// Insert paragraph before
			rngBefore = rng.cloneRange();

			if (startChop == body)
				rngBefore.setStart(startChop, 0);
			else
				rngBefore.setStartBefore(startChop);

			paraBefore.appendChild(rngBefore.cloneContents());

			// Insert paragraph after
			if (endChop.parentNode.nodeName == blockBeforeName)
				endChop = endChop.parentNode;

			rng.setEndAfter(endChop);
			if (endChop.nodeName != "#text" && endChop.nodeName != "BODY")
				rngBefore.setEndAfter(endChop);

			var contents = rng.cloneContents();
			if (contents.firstChild && (contents.firstChild.nodeName == blockBeforeName || contents.firstChild.nodeName == "BODY")) {
				var nodes = contents.firstChild.childNodes;
				for (var i=0; i<nodes.length; i++) {
					if (nodes[i].nodeName != "BODY")
						paraAfter.appendChild(nodes[i]);
				}
			} else
				paraAfter.appendChild(contents);

			/* NF: this is obnoxious; is it necessary? (appears not)
			// Check if it's a empty paragraph
			if (isEmpty(paraBefore))
				paraBefore.innerHTML = "&nbsp;";

			// Check if it's a empty paragraph
			if (isEmpty(paraAfter))
				paraAfter.innerHTML = "&nbsp;";
			*/

			// Delete old contents
			rng.deleteContents();
			rngAfter.deleteContents();
			rngBefore.deleteContents();

			// Insert new paragraphs
			paraAfter.normalize();
			rngBefore.insertNode(paraAfter);
			paraBefore.normalize();
			rngBefore.insertNode(paraBefore);

//			tinyMCE.debug("1: ", paraBefore.innerHTML, paraAfter.innerHTML);
		} else {
			body.innerHTML = "<" + blockBeforeName + ">&nbsp;</" + blockBeforeName + "><" + blockAfterName + ">&nbsp;</" + blockAfterName + ">";
			paraAfter = body.childNodes[1];
		}

		this.selectNode(paraAfter, true, true, true, false);

		return true;
	}

	// Place first part within new paragraph
	if (startChop.nodeName == blockBeforeName)
		rngBefore.setStart(startChop, 0);
	else
		rngBefore.setStartBefore(startChop);
	rngBefore.setEnd(startNode, startOffset);
	paraBefore.appendChild(rngBefore.cloneContents());

	// Place secound part within new paragraph
	rngAfter.setEndAfter(endChop);
	rngAfter.setStart(endNode, endOffset);
	var contents = rngAfter.cloneContents();
	if (contents.firstChild && contents.firstChild.nodeName == blockBeforeName) {
		/* NF: this skips every other node
		var nodes = contents.firstChild.childNodes;
		for (var i=0; i<nodes.length; i++) {
			if (nodes[i].nodeName.toLowerCase() != "body")
				paraAfter.appendChild(nodes[i]);
		*/
		var nodes = contents.firstChild.childNodes;
		while ( nodes.length > 0 )
		{
			if (nodes[0].nodeName.toLowerCase() != "body")
				paraAfter.appendChild(nodes[0]);
		}
	} else
		paraAfter.appendChild(contents);

	// Check if it's a empty paragraph
	if (isEmpty(paraBefore))
		paraBefore.innerHTML = "&nbsp;";

	// Check if it's a empty paragraph
	if (isEmpty(paraAfter))
		paraAfter.innerHTML = "&nbsp;";

	// Create a range around everything
	var rng = doc.createRange();

	if (!startChop.previousSibling && startChop.parentNode.nodeName.toUpperCase() == blockBeforeName) {
		rng.setStartBefore(startChop.parentNode);
	} else {
		if (rngBefore.startContainer.nodeName.toUpperCase() == blockBeforeName && rngBefore.startOffset == 0)
			rng.setStartBefore(rngBefore.startContainer);
		else
			rng.setStart(rngBefore.startContainer, rngBefore.startOffset);
	}

	if (!endChop.nextSibling && endChop.parentNode.nodeName.toUpperCase() == blockBeforeName)
		rng.setEndAfter(endChop.parentNode);
	else
		rng.setEnd(rngAfter.endContainer, rngAfter.endOffset);

	// Delete all contents and insert new paragraphs
	rng.deleteContents();
	rng.insertNode(paraAfter);
	rng.insertNode(paraBefore);
	// debug("2", paraBefore.innerHTML, paraAfter.innerHTML);

	// Normalize
	paraAfter.normalize();
	paraBefore.normalize();

	this.selectNode(paraAfter, true, true, true, false);

	return true;
};

TinyMCEControl.prototype._handleBackSpace = function(evt_type) {
	var doc = this.getDoc();
	var sel = this.getSel();
	if (sel == null)
		return false;

	var rng = sel.getRangeAt(0);
	var node = rng.startContainer;
	var elm = node.nodeType == 3 ? node.parentNode : node;

	if (node == null)
		return;

	// Empty node, wrap contents in paragraph
	if (elm && elm.nodeName == "") {
		var para = doc.createElement("p");

		while (elm.firstChild)
			para.appendChild(elm.firstChild);

		elm.parentNode.insertBefore(para, elm);
		elm.parentNode.removeChild(elm);

		var rng = rng.cloneRange();
		rng.setStartBefore(node.nextSibling);
		rng.setEndAfter(node.nextSibling);
		rng.extractContents();

		this.selectNode(node.nextSibling, true, true);
	}

	// Remove empty paragraphs
	var para = tinyMCE.getParentBlockElement(node);
	if (para != null && para.nodeName.toLowerCase() == 'p' && evt_type == "keypress") {
		var htm = para.innerHTML;
		var block = tinyMCE.getParentBlockElement(node);
		
		// Empty node, we do the killing!!
		if (htm == "" || htm == "&nbsp;" || block.nodeName.toLowerCase() == "li") {
			var prevElm = para.previousSibling;

			while (prevElm != null && prevElm.nodeType != 1)
				prevElm = prevElm.previousSibling;

			if (prevElm == null)
				return false;

			// Get previous elements last text node
			var nodes = tinyMCE.getNodeTree(prevElm, new Array(), 3);
			var lastTextNode = nodes.length == 0 ? null : nodes[nodes.length-1];
			
			// Select the last text node and move curstor to end
			if (lastTextNode != null)
				this.selectNode(lastTextNode, true, false, false, false);

			// Remove the empty paragrapsh
			para.parentNode.removeChild(para);

			//debug("within p element" + para.innerHTML);
			//showHTML(this.getBody().innerHTML);
			return true;
		}
	}

	// Remove BR elements
/*	while (node != null && (node = node.nextSibling) != null) {
		if (node.nodeName.toLowerCase() == 'br')
			node.parentNode.removeChild(node);
		else if (node.nodeType == 1) // Break at other element
			break;
	}*/

	//showHTML(this.getBody().innerHTML);

	return false;
};

TinyMCEControl.prototype.selectNode = function(node, collapse, select_text_node, to_start, scroll) {
	if (!node)
		return;

	if (typeof(collapse) == "undefined")
		collapse = true;

	if (typeof(select_text_node) == "undefined")
		select_text_node = false;

	if (typeof(to_start) == "undefined")
		to_start = true;
		
	if (typeof(scroll) == "undefined")
		scroll = true;

	if (tinyMCE.isMSIE) {
		var rng = this.getBody().createTextRange();

		try {
			rng.moveToElementText(node);

			if (collapse)
				rng.collapse(to_start);

			rng.select();
		} catch (e) {
			// Throws illigal agrument in MSIE some times
		}
	} else {
		var sel = this.getSel();

		if (!sel)
			return;

		if (tinyMCE.isSafari) {
			sel.realSelection.setBaseAndExtent(node, 0, node, node.innerText.length);

			if (collapse) {
				if (to_start)
					sel.realSelection.collapseToStart();
				else
					sel.realSelection.collapseToEnd();
			}

			if (scroll)
				this.scrollToNode(node);

			return;
		}

		var rng = this.getDoc().createRange();

		if (select_text_node) {
			// Find first textnode in tree
			var nodes = tinyMCE.getNodeTree(node, new Array(), 3);
			if (nodes.length > 0)
				rng.selectNodeContents(nodes[0]);
			else
				rng.selectNodeContents(node);
		} else
			rng.selectNode(node);

		if (collapse) {
			// Special treatment of textnode collapse
			if (!to_start && node.nodeType == 3) {
				rng.setStart(node, node.nodeValue.length);
				rng.setEnd(node, node.nodeValue.length);
			} else
				rng.collapse(to_start);
		}

		sel.removeAllRanges();
		sel.addRange(rng);
	}

	if (scroll)
		this.scrollToNode(node);

	// Set selected element
	tinyMCE.selectedElement = null;
	if (node.nodeType == 1)
		tinyMCE.selectedElement = node;
};

TinyMCEControl.prototype.scrollToNode = function(node) {
	// Scroll to node position
	var pos = tinyMCE.getAbsPosition(node);
	var doc = this.getDoc();
	var scrollX = doc.body.scrollLeft + doc.documentElement.scrollLeft;
	var scrollY = doc.body.scrollTop + doc.documentElement.scrollTop;
	var height = tinyMCE.isMSIE ? document.getElementById(this.editorId).style.pixelHeight : this.targetElement.clientHeight;

	// Only scroll if out of visible area
	if (!tinyMCE.settings['auto_resize'] && !(node.absTop > scrollY && node.absTop < (scrollY - 25 + height))) {
		this.contentWindow.scrollTo(pos.absLeft, pos.absTop - height + 25);
	}
};

TinyMCEControl.prototype.getBody = function() {
	return this.getDoc().body;
};

TinyMCEControl.prototype.getDoc = function() {
	return this.contentWindow.document;
};

TinyMCEControl.prototype.getWin = function() {
	return this.contentWindow;
};

TinyMCEControl.prototype.getSel = function() {
	if (tinyMCE.isMSIE)
		return this.getDoc().selection;

	var sel = this.contentWindow.getSelection();

	// Fake getRangeAt
	if (tinyMCE.isSafari && !sel.getRangeAt) {
		var newSel = new Object();
		var doc = this.getDoc();

		function getRangeAt(idx) {
			var rng = new Object();

			rng.startContainer = this.focusNode;
			rng.endContainer = this.anchorNode;
			rng.commonAncestorContainer = this.focusNode;
			rng.createContextualFragment = function (html) {
				// Seems to be a tag
				if (html.charAt(0) == '<') {
					var elm = doc.createElement("div");

					elm.innerHTML = html;

					return elm.firstChild;
				}

				return doc.createTextNode("UNSUPPORTED, DUE TO LIMITATIONS IN SAFARI!");
			};

			rng.deleteContents = function () {
				doc.execCommand("Delete", false, "");
			};

			return rng;
		}

		// Patch selection

		newSel.focusNode = sel.baseNode;
		newSel.focusOffset = sel.baseOffset;
		newSel.anchorNode = sel.extentNode;
		newSel.anchorOffset = sel.extentOffset;
		newSel.getRangeAt = getRangeAt;
		newSel.text = "" + sel;
		newSel.realSelection = sel;

		newSel.toString = function () {return this.text;};

		return newSel;
	}

	return sel;
};



// file mb.js
/**
 * For debugging.
 */
var messagebox = function() { };
var mb = messagebox;

// file Util.js
/**
 * @class This is merely a container which holds a library of utility
 * functions and classes. The library is organized around existing
 * DOM/JS classes, if they exist. For example, functions which extend
 * or provide cross-browser functionality on DOM Nodes are located in
 * Util.Node.
 */
var Util = {
	is: function is(type, objects)
	{
		for (var i = 0; i < objects.length; i++) {
			if (typeof(objects[i]) != type)
				return false;
		}
		
		return true;
	},
	
	is_boolean: function is_boolean()
	{
		return Util.is('boolean', arguments);
	},
	
	is_function: function is_function()
	{
		return Util.is('function', arguments);
	},
	
	is_string: function is_string()
	{
		return Util.is('string', arguments);
	},
	
	is_number: function is_number()
	{
		return Util.is('number', arguments);
	},
	
	is_object: function is_object()
	{
		return Util.is('object', arguments);
	},
	
	is_valid_object: function is_non_null_object()
	{
		for (var i = 0; i < arguments.length; i++) {
			if (typeof(arguments[i]) != 'object' || arguments[i] == null)
				return false;
		}
		
		return true;
	},
	
	is_undefined: function is_undefined()
	{
		return Util.is('undefined', arguments);
	},
	
	is_null: function is_null()
	{
		for (var i = 0; i < arguments.length; i++) {
			if (arguments[i] != null)
				return false;
		}
		
		return true;
	},
	
	is_blank: function is_blank()
	{
		for (var i = 0; i < arguments.length; i++) {
			if (typeof(arguments[i]) != 'undefined' || arguments[i] != null)
				return false;
		}
		
		return true;
	},
	
	is_enumerable: function is_enumerable()
	{
		for (var i = 0; i < arguments.length; i++) {
			var o = arguments[i];
			if (!o || typeof(o.length) != 'number')
				return false;
		}
		
		return true;
	},
	
	trim: function trim_string(str)
	{
		str = str.replace(/^\s+/, '');
		for (var i = str.length - 1; i >= 0; i--) {
			if (/\S/.test(str.charAt(i))) {
				str = str.substring(0, i + 1);
				break;
			}
		}
		return str;
	},
	
	regexp_escape: function escape_string_for_regexp(str)
	{
		// credit: Prototype
		return String(str).replace(/([.*+?^=!:${}()|[\]\/\\])/g, '\\$1');
	}
};

// file Util.Scheduler.js
/**
 * @class Provides a more convenient interface to setTimeout and clearTimeout.
 * @author Eric Naeseth
 */
Util.Scheduler = function Scheduler()
{
	throw new Error('This is a static class; it does not make sense to call its constructor.');
}

Util.Scheduler.Error = function SchedulerError(message)
{
	Util.OOP.inherits(this, Error, message);
	this.name = 'Util.Scheduler.Error';
}

Util.Scheduler.Task = function SchedulerTask(callable)
{
	this.id = null;
	this.invoke = callable;
	
	this.runDelayed = function run_task_delayed(delay)
	{
		this.id = setTimeout(callable, delay * 1000);
	}
	
	this.runPeriodically = function run_task_periodically(interval)
	{
		var self = this;
		interval *= 1000;
		
		function standin() {
			self.invoke.apply(this, arguments);
			self.id = setTimeout(standin, interval);
		}
		
		this.id = setTimeout(standin, interval);
	}
	
	this.cancel = function cancel_task()
	{
		if (this.id === null) {
			throw new Util.Scheduler.Error('Nothing has been scheduled.');
		}
		clearTimeout(this.id);
		this.id = null;
	}
}

Util.Scheduler.delay = function sched_delay(func, delay)
{
	var task = new Util.Scheduler.Task(func);
	task.runDelayed(delay);
	return task;
}

Util.Scheduler.defer = function sched_defer(func)
{
	var task = new Util.Scheduler.Task(func);
	task.runDelayed(0.01 /* 10ms */);
	return task;
}

Util.Scheduler.runPeriodically = function sched_run_periodically(func, interval)
{
	var task = new Util.Scheduler.Task(func);
	task.runPeriodically(interval);
	return task;
} 
// file Util.Function.js
Util.Function = {
	/**
	 * Synchronizes calls to the function; i.e. prevents it from being called
	 * more than once at the same time.
	 * @author Eric Naeseth
	 * @see http://taylor-hughes.com/?entry=112
	 */
	synchronize: function synchronize(function_)
	{
		var sync = Util.Function.synchronize;
		
		if (!sync.next_id) {
			sync.next_id = 0;
			sync.wait_list = {};
			sync.next = function(k) {
				for (i in sync.wait_list) {
					if (!k)
						return sync.wait_list[i];
					if (k == i)
						k = null;
				}

				return null;
			}
		}
		
		return function() {
			var lock = {
				id: ++sync.next_id,
				enter: false
			};

			sync.wait_list[lock.id] = lock;

			lock.enter = true;
			lock.number = (new Date()).getTime();
			lock.enter = false;
			
			var context = [this, arguments];

			function attempt(start)
			{
				for (var j = start; j != null; j = sync.next(j.id)) {
					if (j.enter ||
						(j.number && j.number < lock.number ||
							(j.number == lock.number && j.id < lock.id))) 
					{
						(function () { attempt(j); }).delay(100);
						return;
					}
				}

				// run with exclusive access
				function_.apply(context[0], context[1]);
				// release
				lock.number = 0;
				sync.wait_list[lock.id] = null;
			}
			
			attempt(sync.next());
		}
	},
	
	empty: function empty()
	{
		
	},
	
	constant: function constant(k)
	{
		return k;
	},
	
	optimist: function optimist()
	{
		return true;
	},
	
	pessimist: function pessimist()
	{
		return false;
	},
	
	unimplemented: function unimplemented()
	{
		throw new Error('Function not implemented!');
	}
};

var $S = Util.Function.synchronize;

Util.Function.Methods = {
	bind: function bind(function_)
	{
		if (arguments.length < 2 && arguments[0] === undefined)
			return function_;
		
		var args = Util.Array.from(arguments).slice(1), object = args.shift();
		return function binder() {
			return function_.apply(object, args.concat(Util.Array.from(arguments)));
		}
	},
	
	bind_to_event: function bind_to_event(function_)
	{
		var args = Util.Array.from(arguments), object = args.shift();
		return function event_binder(event) {
			return function_.apply(object, [event || window.event].concat(args));
		}
	},
	
	curry: function curry(function_)
	{
		if (arguments.length <= 1)
			return function_;
		
		var args = Util.Array.from(arguments).slice(1);
		
		return function currier() {
			return function_.apply(this, args.concat(Util.Array.from(arguments)));
		}
	},
	
	dynamic_curry: function dynamic_curry(function_)
	{
		if (arguments.length <= 1)
			return function_;
		
		var args = Util.Array.from(arguments).slice(1).map(function (a) {
			return (typeof(a) == 'function')
				? a()
				: a;
		});
		
		return function dynamic_currier() {
			return function_.apply(this, args.concat(Util.Array.from(arguments)));
		}
	},
	
	methodize: function methodize(function_)
	{
		if (!function_.methodized) {
			function_.methodized = function methodized() {
				return function_.apply(null, [this].concat(Util.Array.from(arguments)));
			}
		}
		
		return function_.methodized;
	},
	
	delay: function delay(function_, delay)
	{
		return Util.Scheduler.delay(function_, delay);
	},
	
	defer: function defer(function_)
	{
		return Util.Scheduler.defer(function_);
	}
};

Util.Function.bindToEvent = Util.Function.bind_to_event;

for (var name in Util.Function.Methods) {
	Function.prototype[name] =
		Util.Function.Methods.methodize(Util.Function.Methods[name]);
	Util.Function[name] = Util.Function.Methods[name];
} 
// file Util.Array.js
/**
 * Does nothing.
 *
 * @class Container for functions relating to arrays.
 */
Util.Array = function()
{
};

/**
 * Forms a legitimate JavaScript array from an array-like object
 * (eg NodeList objects, function argument lists).
 */
Util.Array.from = function array_from_iterable(iterable)
{
	if (!iterable)
		return [];
	if (iterable.toArray)
		return iterable.toArray();
	
	try {
		return Array.prototype.slice.call(iterable, 0);
	} catch (e) {
		// This doesn't work in Internet Explorer with iterables that are not
		// real JavaScript objects. But we still want to keep around the slice
		// version for performance on Gecko.
		
		var new_array = [];
		for (var i = 0; i < iterable.length; i++) {
			new_array.push(iterable[i]);
		}
		
		return new_array;
	}
	
};

var $A = Util.Array.from; // convenience alias

/**
 * Creates an array of integers from start up to (but not including) stop.
 */
Util.Array.range = function range(start, stop)
{
	if (arguments.length == 1) {
		stop = start;
		start = 0;
	}
	
	var ret = [];
	for (var i = start; i < stop; i++) {
		ret.push(i);
	}
	return ret;
}

var $R = Util.Array.range; // convenience alias

/**
 * Methods that are callable by two methods:
 *  - Util.Array.method_name(some_array, ...)
 *  - some_array.methodName(...)
 * Note the change in naming convention! When added to
 * Array's prototype it is changed to use the JavaScript
 * naming convention (camelCase) instead of Loki's
 * (underscore_separated).
 */
Util.Array.Methods = {
	/**
	 * Executes the given function for each element in the array.
	 * (Available as the "each" method of arrays.)
	 * @param	array	the array over which for_each will loop
	 * @param	func	the function which will be called
	 * @param	thisp	optional "this" context
	 * @see	http://tinyurl.com/ds8lo
	 */
	for_each: function each(array, func)
	{
		var thisp = arguments[2] || null;

		if (typeof(func) != 'function')
			throw new TypeError();

		//if (typeof(array.forEach) == 'function')
		//	return array.forEach(func, thisp);

		var len = array.length;
		for (var i = 0; i < len; i++) {
			if (i in array)
				func.call(thisp, array[i], i, array);
		}
	},
	
	/**
	 * Creates a new array by applying the given function to each element of
	 * the given array.
	 * i.e. [a, b, c, ...] -> [func(a), func(b), func(c), ...]
	 * @param {array} array the array over which map will loop
	 * @param {function} fund the function to apply to each element
	 * @param {object} thisp optional "this" context for the function
	 * @type array
	 * @see http://tinyurl.com/32ww7d
	 */
	map: function map(array, func)
	{
		var thisp = arguments[2] || null;

		var len = array.length;
		var ret = new Array(len);
		for (var i = 0; i < len; i++) {
			if (i in array)
				ret[i] = func.call(thisp, array[i], i, array);
		}

		return ret;
	},
	
	/**
	 * @see http://tinyurl.com/yq3c9f
	 */
	reduce: function reduce(array, func, initial_value)
	{
		if (typeof(func) != 'function')
			throw new TypeError();
		
		var value;
		
		array.each(function(v, i, a) {
			if (value === undefined && initial_value === undefined) {
				value = v;
			} else {
				value = func.call(null, value, v, i, a);
			}
		});
		
		return value;
	},
	
	/**
	 * Returns the first item in the array for which the test function
	 * returns true.
	 * @param	array	the array to search
	 * @param	test	the function which will be called
	 * @param	thisp	optional "this" context
	 */
	find: function find_in_array(array, test, thisp)
	{
		if (typeof(thisp) == 'undefined')
			thisp = null;
		if (typeof(test) != 'function')
			throw new TypeError();

		var len = array.length;

		for (var i = 0; i < len; i++) {
			if (i in array && test.call(thisp, array[i]))
				return array[i];
		}
	},
	
	/**
	 * Returns all items in the array for which the test function
	 * returns true.
	 * @param	array	the array to search
	 * @param	test	the function which will be called
	 * @param	thisp	optional "this" context
	 */
	find_all: function find_all_in_array(array, test, thisp)
	{
		if (typeof(thisp) == 'undefined')
			thisp = null;
		if (typeof(test) != 'function')
			throw new TypeError();

		var len = array.length;
		var results = [];

		for (var i = 0; i < len; i++) {
			if (i in array && test.call(thisp, array[i]))
				results.push(array[i]);
		}

		return results;
	},
	
	/**
	 * Converts the array to a "set": an object whose keys are the original
	 * array's values and whose values are all true. This allows efficient
	 * membership testing of the array when it needs to be done repeatedly.
	 */
	to_set: function array_to_set(array)
	{
		var s = {};
		var len = array.length;
		
		for (var i = 0; i < len; i++) {
			if (i in array)
				s[array[i]] = true;
		}
		
		return s;
	},
	
	min: function min_in_array(array, key_func)
	{
		return array.reduce(function(a, b) {
			if (key_func) {
				return (key_func(b) < key_func(a))
					? b
					: a;
			} else {
				return (b < a)
					? b
					: a;
			}
		});
	},
	
	max: function max_in_array(array, key_func)
	{
		return array.reduce(function(a, b) {
			if (key_func) {
				return (key_func(b) > key_func(a))
					? b
					: a;
			} else {
				return (b > a)
					? b
					: a;
			}
		});
	},
	
	pluck: function pluck_from_array(array, property_name)
	{
		return array.map(function(obj) {
			return obj[property_name];
		});
	},
	
	sum: function sum_of_array(array)
	{
		return array.reduce(function(a, b) {
			return a + b;
		});
	},
	
	product: function product_of_array(array)
	{
		return array.reduce(function(a, b) {
			return a * b;
		});
	},
	
	contains: function array_contains(array, item)
	{
		if (Util.is_function(array.indexOf)) {
			return -1 != array.indexOf(item);
		}
		
		return !!array.find(function(element) {
			return item == element;
		});
	},
	
	/**
	 * Returns true if the function test returns true when given any element
	 * in array.
	 * @param {array}	array	the array to examine
	 * @param {function}	test	the test to apply to the array's elements
	 * @param {object}	thisp	an optional "this" context in which the test
	 *							function will be called
	 * @type boolean
	 */
	some: function some(array, test)
	{
		var thisp = arguments[2] || null;
		
		for (var i = 0; i < array.length; i++) {
			if (i in array) {
				if (test.call(thisp, array[i])) {
					// Found one that works.
					return true;
				}
			}
		}
		
		return false;
	},
	
	/**
	 * Returns true if the function test returns true when executed for each
	 * element in array.
	 * @param {array}	array	the array to examine
	 * @param {function}	test	the test to apply to the array's elements
	 * @param {object}	thisp	an optional "this" context in which the test
	 *							function will be called
	 * @type boolean
	 */
	every: function every(array, test)
	{
		var thisp = arguments[2] || null;
		
		for (var i = 0; i < array.length; i++) {
			if (i in array) {
				if (!test.call(thisp, array[i])) {
					// Found one that doesn't work.
					return false;
				}
			}
		}
		
		return true;
	},
	
	/**
	 * Returns all of the elements of the array that passed the given test.
	 * @param {array}	array	the array to filter
	 * @param {function}	test	a function that will be called for each
	 *								element in the array to determine whether
	 *								or not it should be included
	 * @param {object}	thisp	an optional "this" context in which the test
	 *							function will be called
	 * @type array
	 */
	filter: function filter_array(array, test)
	{
		var thisp = arguments[2] || null;
		
		return array.reduce(function perform_filtration(matches, element) {
			if (test.call(thisp, element))
				matches.push(element);
			return matches;
		}, []);
	},
	
	remove: function remove_from_array(array, item)
	{
		var len = array.length;
		for (var i = 0; i < len; i++) {
			if (i in array && array[i] == item) {
				array.splice(i, 1);
				return true;
			}
		}
		
		return false;
	},
	
	remove_all: function remove_all_from_array(array, item)
	{
		var len = array.length;
		var found = false;
		
		for (var i = 0; i < len; i++) {
			if (i in array && array[i] == item) {
				found = true;
				array.splice(i, 1);
			}
		}
		
		return found;
	},
	
	append: function append_array(a, b)
	{
		// XXX: any more efficient way to do this using Array.splice?
		
		if (b.length === undefined || b.length === null) {
			throw new TypeError("Cannot append a non-iterable to an array.");
		}
		
		var len = b.length;
		for (var i = 0; i < len; i++) {
			if (i in b) {
				a.push(b[i]);
			}
		}
	}
}

for (var name in Util.Array.Methods) {
	function transform_name(name)
	{
		var new_name = '';
		parts = name.split(/_+/);
		
		new_name += parts[0];
		for (var i = 1; i < parts.length; i++) {
			new_name += parts[1].substr(0, 1).toUpperCase();
			new_name += parts[1].substr(1);
		}
		
		return new_name;
	}
	
	Util.Array[name] = Util.Array.Methods[name];
	
	var new_name;
	switch (name) {
		case 'map':
		case 'reduce':
		case 'filter':
		case 'every':
		case 'some':
			if (!Util.is_function(Array.prototype[name]))
				Array.prototype[name] = Util.Array.Methods[name].methodize();
			break;
		case 'for_each':
			Array.prototype.each = (Array.prototype.forEach ||
					Util.Array.Methods.for_each.methodize());
			break;
		default:
			new_name = transform_name(name);
			Array.prototype[new_name] = Util.Array.Methods[name].methodize();
	}
} 
// file Util.Node.js
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
 * Returns all children of the given node who match the given test.
 * @param {Node} node the node whose children will be traversed
 * @param {Function|String|Number} match either a boolean-test matching function,
 *        or a tag name, or a node type to be matched
 * @return {Node[]} all matching child nodes
 */
Util.Node.find_children = function find_matching_node_children(node, match) {
	var i, length, node_type;
	var children = [], child;
	
	if (!node || !node.nodeType) {
		throw new TypeError('Must provide Util.Node.find_children with a ' +
			'node to traverse.');
	}
	
	if (Util.is_string(match)) {
		match = Util.Node.curry_is_tag(match);
	} else if (Util.is_number(match)) {
		node_type = match;
		match = function is_correct_node_type(node) {
			return (node && node.nodeType == node_type);
		}
	} else if (!Util.is_function(match)) {
		throw new TypeError('Must provide Util.Node.find_children with ' +
			'something to match nodes against.');
	}
	
	for (i = 0, length = node.childNodes.length; i < length; i++) {
		child = node.childNodes[i];
		if (match(child))
			children.push(child);
	}
	
	return children;
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
	function terminal(node) {
		switch (node.nodeType) {
			case Util.Node.DOCUMENT_NODE:
			case Util.Node.DOCUMENT_FRAGMENT_NODE:
				return true;
			default:
				return false;
		}
	}
	
	for (var n = node.parentNode; n && !terminal(n); n = n.parentNode) {
		if (boolean_test(n, extra_args))
			return n;
	}
	
	return null;
};

/**
 * Returns true if there exists an ancestor of the given node 
 * that satisfies the given boolean_test. Paramaters same as for
 * get_nearest_ancestor_node.
 */
Util.Node.has_ancestor_node =
	function node_has_matching_ancestor(node, boolean_test, extra_args)
{
	return Util.Node.get_nearest_ancestor_node(node, boolean_test, extra_args) != null;
};

/**
 * Finds the node that is equal to or an ancestor of the given node that
 * matches the provided test.
 * @param	{Node}	node	the node to examine
 * @param	{function}	test	the test function that should return true when
 *								passed a suitable node
 * @return {Node}	the matching node if one was found, otherwise null
 */
Util.Node.find_match_in_ancestry =
	function find_matching_node_in_ancestry(node, test)
{
	function terminal(node) {
		switch (node.nodeType) {
			case Util.Node.DOCUMENT_NODE:
			case Util.Node.DOCUMENT_FRAGMENT_NODE:
				return true;
			default:
				return false;
		}
	}
	
	for (var n = node; n && !terminal(n); n = n.parentNode) {
		if (test(n))
			return n;
	}
	
	return null;
}

/**
 * Gets the nearest ancestor of the node that is currently being displayed as
 * a block.
 * @param {Node}	node		the node to examine
 * @param {Window}	node_window	the node's window
 * @type Element
 * @see Util.Node.get_nearest_bl_ancestor_element()
 * @see Util.Element.is_block_level()
 */
Util.Node.get_enclosing_block =
	function get_enclosing_block_of_node(node, node_window)
{
	// Sanity checks.
	if (!node || !node.nodeType) {
		throw new TypeError('Must provide a node to ' + 
			'Util.Node.get_enclosing_block.');
	} else if (!Util.is_valid_object(node_window)) {
		throw new TypeError('Must provide the node\'s window object to ' + 
			'Util.Node.get_enclosing_block.');
	} else if (node_window.document != node.ownerDocument) {
		throw new Error('The window provided to Util.Node.get_enclosing_block' +
			' is not actually the window in which the provided node resides.');
	}
	
	function is_block(node) {
		return (node.nodeType == Util.Node.ELEMENT_NODE &&
			Util.Element.is_block_level(window, node));
	}
	
	return Util.Node.get_nearest_ancestor_node(node, is_block);
}

/**
 * Gets the nearest ancester of node which is a block-level
 * element. (Uses get_nearest_ancestor_node.)
 *
 * @param {Node}	node		the starting node
 * @type Element
 * @see Util.Node.get_enclosing_block()
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
	// Yes, I could use curry_is_tag, but I'd rather only have one closure.
	function matches_tag_name(node)
	{
		return Util.Node.is_tag(node, tag_name);
	}
	
	return Util.Node.get_nearest_ancestor_node(node, matches_tag_name);
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
	for (var n = node.lastChild; n; n = n.previousSibling) {
		if (boolean_test(n))
			return n;
	}
	
	return null;
};

Util.Node.has_child_node = function(node, boolean_test)
{
	return Util.Node.get_last_child_node(node, boolean_test) != null;
};

/**
 * Returns true if the given node is an element node.
 * @param {Node} node node whose type will be tested
 * @returns {Boolean} true if "node" is an element node, false if otherwise
 */
Util.Node.is_element = function node_is_element(node) {
	return (node && node.nodeType == Util.Node.ELEMENT_NODE);
}

/**
 * Returns true if the given node is a text node.
 * @param {Node} node node whose type will be tested
 * @returns {Boolean} true if "node" is a text node, false if otherwise
 */
Util.Node.is_text = function node_is_text(node) {
	return (node && node.nodeType == Util.Node.TEXT_NODE);
}

/**
 * Returns true if the given node is a document node.
 * @param {Node} node node whose type will be tested
 * @returns {Boolean} true if "node" is a document node, false if otherwise
 */
Util.Node.is_document = function node_is_document(node) {
	return (node && node.nodeType == Util.Node.DOCUMENT_NODE);
}

/**
 * Returns true if the node is an element node and its node name matches the
 * tag parameter, false otherwise.
 *
 * @param	node	node on which the test will be run
 * @param	tag		tag name to look for
 * @type boolean
 */
Util.Node.is_tag = function(node, tag)
{
	return (node.nodeType == Util.Node.ELEMENT_NODE
		&& node.nodeName == tag.toUpperCase());
};

/**
 * Creates a function that calls is_tag using the given tag.
 */
Util.Node.curry_is_tag = function(tag)
{
	return function(node) { return Util.Node.is_tag(node, tag); };
}

/**
 * Finds the offset of the given node within its parent.
 * @param {Node}  node  the node whose offset is desired
 * @return {Number}     the node's offset
 * @throws {Error} if the node is orphaned (i.e. it has no parent)
 */
Util.Node.get_offset = function get_node_offset_within_parent(node)
{
	var parent = node.parentNode;
	
	if (!parent) {
		throw new Error('Node ' + Util.Node.get_debug_string(node) + ' has ' +
			' no parent.');
	}
	
	for (var i = 0; i < parent.childNodes.length; i++) {
		if (parent.childNodes[i] == node)
			return i;
	}
	
	throw new Error();
}

/**
 * Attempts to find the window that corresponds with a given node.
 * @param {Node}  node   the node whose window is desired
 * @return {Window}   the window object if it could be found, otherwise null.
 */
Util.Node.get_window = function find_window_of_node(node)
{
	var doc = (node.nodeType == Util.Node.DOCUMENT_NODE)
		? node
		: node.ownerDocument;
	var seen;
	var stack;
	var candidate;
	
	if (!doc)
		return null;
	
	if (doc._loki__document_window) {
		return doc._loki__document_window;
	}
	
	function accept(w)
	{
		if (!w)
			return false;
		
		if (!seen.contains(w)) {
			seen.push(w);
			return true;
		}
		
		return false;
	}
	
	function get_elements(tag)
	{
		return candidate.document.getElementsByTagName(tag);
	}
	
	seen = [];
	stack = [window];
	
	accept(window);
	
	while (candidate = stack.pop()) { // assignment intentional
		try {
			if (candidate.document == doc) {
				// found it!
				doc._loki__document_window = candidate;
				return candidate;
			}

			if (candidate.parent != candidate && accept(candidate)) {
				stack.push(candidate);
			}


			['FRAME', 'IFRAME'].map(get_elements).each(function (frames) {
				for (var i = 0; i < frames.length; i++) {
					if (accept(frames[i].contentWindow))
						stack.push(frames[i].contentWindow);
				}
			});
		} catch (e) {
			// Sometimes Mozilla gives security errors when trying to access
			// the documents.
		}
	}
	
	// guess it couldn't be found
	return null;
}

Util.Node.non_whitespace_regexp = /[^\f\n\r\t\v ]/gi;
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
			  Util.Node.non_whitespace_regexp.test(node.nodeValue)
		   ))

	return node;
};

/**
 * Determines whether the given node is a block-level element. Tries to use the
 * element's computed style, and if that fails, falls back on what the default
 * is for the element's tag.
 *
 * @see Util.Element.is_block_level
 * @see Util.Block.is_block
 * @param	{Node}	node	the node in question
 * @return	{Boolean}	true if the node is a block-level element
 */
Util.Node.is_block_level_element = function(node)
{
	var w;
	
	if (node.nodeType != Util.Node.ELEMENT_NODE)
		return false;
	
	try {
		w = Util.Node.get_window(node);
		return Util.Element.is_block_level(w, node);
	} catch (e) {
		return Util.Block.is_block(node);
	}
};

Util.Node.is_block = Util.Node.is_block_level_element;

/**
 * Determines whether the given node, in addition to being a block-level
 * element, is also one that it we can nest inside any arbitrary block.
 * It is generally not permitted to surround the elements in the list below 
 * with most other blocks. E.g., we don't want to surround a TD with BLOCKQUOTE.
 */
Util.Node.is_nestable_block_level_element = function(node)
{
	return Util.Node.is_block_level_element(node)
		&& !(/^(BODY|TBODY|THEAD|TR|TH|TD)$/i).test(node.tagName);
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
	var parent = ref_node.parentNode;
	if (ref_node.nextSibling)
		parent.insertBefore(new_node, ref_node.nextSibling);
	else
		parent.appendChild(new_node);
};

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
	var parent = node.parentNode;

	if (!parent)
		return; // node was removed already
	
	while (node.firstChild) {
		parent.insertBefore(node.removeChild(node.firstChild), node);
	}
	
	parent.removeChild(node);
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
	for (var sib = node.previousSibling; sib; sib = sib.previousSibling) {
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
	for (var sib = node.nextSibling; sib; sib = sib.nextSibling) {
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

/**
 * @return {String} a string that describes the node
 */
Util.Node.get_debug_string = function get_node_debug_string(node)
{
	var str;
	
	if (!Util.is_number(node.nodeType)) {
		return '(Non-node ' + node + ')';
	}
	
	switch (node.nodeType) {
		case Util.Node.ELEMENT_NODE:
			str = '<' + node.nodeName.toLowerCase();
			
			Util.Object.enumerate(Util.Element.get_attributes(node),
				function append_attribute(name, value) {
					str += ' ' + name + '="' + value + '"';
				}
			);
			
			str += '>';
			break;
		case Util.Node.TEXT_NODE:
			str = '"' + Util.trim(node.nodeValue.toString()) + '"';
			break;
		case Util.Node.DOCUMENT_NODE:
			str = '[Document';
			if (node.location)
				str += ' ' + node.location;
			str += ']';
			break;
		default:
			str = '[' + node.nodeName + ']';
	}
	
	return str;
}

// end file Util.Node.js


// file Util.Browser.js
Util.Browser = {
	IE:     !!(window.attachEvent && !window.opera),
	Opera:  !!window.opera,
	WebKit: (navigator.userAgent.indexOf('AppleWebKit/') > -1),
	Gecko:  (navigator.userAgent.indexOf('Gecko') > -1
		&& navigator.userAgent.indexOf('KHTML') == -1),
		
	Windows: (navigator.platform.indexOf('Win') > -1),
	Mac: (navigator.platform.indexOf('Mac') > -1),
	
	get_version: function get_browser_version() {
		var pattern, match;
		
		if (Util.Browser.IE) {
			pattern = /MSIE\s+([\d+\.]+)/;
		} else if (Util.Browser.Gecko) {
			pattern = /rv:([\d+\.]+)/;
		} else if (Util.Browser.WebKit) {
			if (/Safari/.test(navigator.userAgent)) {
				match = /Version\/([\d+\.]+)/.exec(navigator.userAgent);
				if (match && match.length >= 1)
					return match[1];
				match = /Safari\/([\d+\.]+)/.exec(navigator.userAgent);
				if (match && match.length >= 1) {
					if (Util.Browser._safari_versions[match[1]])
						return Util.Browser._safari_versions[match[1]];
				}
			}
			return '';
		} else if (Util.Browser.Opera) {
			pattern = /Opera[\/ ]([\d+\.]+)/;
		}
		
		match = pattern.exec(navigator.userAgent);
		return (match && match.length >= 1)
			? match[1]
			: '';
	},
	
	_safari_versions: {
		'525.19': '3.1.2',
		'525.18': '3.1.1',
		'525.7': '3.1',
		'523': '3.0.4',
		'418.8': '2.0.4',
		'417.9': '2.0.3',
		'416': '2.0.2',
		'412.7': '2.0.1',
		'412': '2.0',
		'312.8': '1.3.2',
		'312.5': '1.3.1',
		'312.1': '1.3',
		'125.5.5': '1.2.4',
		'125.4': '1.2.3',
		'125.2': '1.2.2',
		'100': '1.1',
		'85.8.2': '1.0.3',
		'85.7': '1.0.2'
	}
};


// file Util.Element.js
/**
 * @class Container for functions relating to document elements.
 */
Util.Element = {
	/**
	 * Set of empty elements
	 * @type Object
	 */
	empty: (['BR', 'AREA', 'LINK', 'IMG', 'PARAM', 'HR', 'INPUT', 'COL',
		'BASE', 'META'].toSet()),
		
	/**
	 * Determines if the given node or tag name represents an empty HTML tag.
	 * @param {Element|String}
	 * @return {Boolean}
	 */
	empty_tag: function is_empty_tag(el)
	{
		var tag = (el.nodeName || String(el)).toUpperCase();
		return (tag in Util.Element.empty);
	},
	
	/**
	 * Gets an element's computed styles.
	 * @param {Window}	window	the element's window
	 * @param {Element}	elem	the element whose computed style is desired
	 * @return {object}
	 */
	get_computed_style: function get_element_computed_style(window, elem)
	{
		if (!elem || !Util.is_valid_object(window)) {
			throw new TypeError('Valid window and element objects must be ' +
				'provided to Util.Element.get_computed_style.');
		}
		
		if (!elem.nodeType || elem.nodeType != Util.Node.ELEMENT_NODE) {
			throw new TypeError('An element node must be provided to ' + 
				'Util.Element.get_computed_style');
		}
		
		if (Util.is_function(window.getComputedStyle)) {
			return window.getComputedStyle(elem, null);
		} else if (Util.is_valid_object(elem.currentStyle)) {
			return elem.currentStyle;
		} else {
			throw new Util.Unsupported_Error('getting an element\'s computed ' +
				'style');
		}
	},
	
	/**
	 * Tests whether or not an element is at block-level.
	 * Cf. Util.Node.is_block_level_element; this uses different logic.
	 * @param {Window}	window	the element's window
	 * @param {Element}	elem	the element whose block level status is desired
	 * @return {boolean}
	 */
	is_block_level: function is_block_level_element(window, elem)
	{
		var s;
		
		try {
		    s = Util.Element.get_computed_style(window, elem);
		    if (s.display == 'inline' || s.display == 'none')
		        return false;
		    // Assume that everything else ('block', 'table-cell', 'list-item',
		    // etc.) is a block.
			return true;
		} catch (e) {
			var ex = new Error('Unable to get the computed style for ' +
				Util.Node.get_debug_string(elem) + '.');
			ex.cause = e;
			throw ex;
		}
	},
	
	/**
	 * Returns the attributes of an element.
	 * @param {Element}	elem
	 * @param {Boolean} [no_translation=false] if true, attribute names that may
	 * be language keywords (like "class" and "for") will not be translated
	 * @return {Object}	an object whose keys are attribute names and whose
	 *					values are the corresponding values
	 */
	get_attributes: function get_element_attributes(elem, no_translation)
	{
		var attrs = {};
		
		if (!elem) {
			throw new TypeError('No element provided; cannot get attributes.');
		}
		
		if (elem.nodeType != Util.Node.ELEMENT_NODE) {
			return attrs;
		} else if (elem.hasAttributes && !elem.hasAttributes()) {
			return attrs;
		}
		
		var names = Util.Element._get_attribute_names(elem);
		var i, name, v, length = names.length;
		for (i = 0; i < length; i++) {
			name = names[i];
			v = elem.getAttribute(name);
			try {
				v = v.toString();
			} catch (e) {
				// Why not just test for toString? Because IE will throw an
				// exception.
			}
			
			switch (name) {
				case 'class':
				case 'className':
					attrs[(no_translation) ? 'class' : 'className'] = v;
					break;
				case 'for':
				case 'htmlFor':
					attrs[(no_translation) ? 'for' : 'htmlFor'] = v;
					break;
				case 'style':
					attrs.style = elem.style.cssText;
					break;
				default:
					attrs[name] = v;
			}
		}
		
		return attrs;
	},
	
	/**
	 * Tests if the element is "basically empty".
	 * An element is basically empty if:
	 *    - It contains no image, horizontal rule, or table elements, and
	 *    - It contains no non-whitespace (spaces, tabs, or line breaks) text.
	 * @param {Element}	elem	the element whose emptiness will be tested
	 * @return {boolean}	true if the element is basically empty, false if not
	 *
	 * Logic from TinyMCE.
	 */
	is_basically_empty: function element_is_basically_empty(elem)
	{
		if (!elem || elem.nodeType != Util.Node.ELEMENT_NODE) {
			throw new TypeError('Must provide an element node to ' +
				'Util.Element.is_basically_empty(); instead got ' +
				Util.Node.get_debug_string(elem));
		}
		
		var doc = elem.ownerDocument;
		var non_whitespace = /[^ \t\r\n]/;
		var acceptable_tags;
		
		if (doc.createTreeWalker && NodeFilter) {
			// Browser supports DOM Level 2 Traversal; use it in the hope that
			// it will be faster than the other branch which uses string
			// manipulations.
			
			// This map must stay in sync with the pattern in the next branch.
			acceptable_tags = {IMG: true, HR: true, TABLE: true};
			
			var filter = {
				acceptNode: function accept_node_for_emptiness_check(node) {
					switch (node.nodeType) {
						case Util.Node.TEXT_NODE:
							// Allow text nodes through if they have
							// non-whitespace characters so that the code below
							// can safely return false whenever it receives a
							// text node.
							return (non_whitespace.test(node.nodeValue))
								? NodeFilter.FILTER_ACCEPT
								: NodeFilter.FILTER_REJECT
						case Util.Node.ELEMENT_NODE:
							// Similarly, allow elements through only if they're
							// one of the acceptable tags so that the code below
							// will know what to do instantly. But, skip a non-
							// acceptable element instead of rejecting it
							// outright so that any of its descendant text nodes
							// can be processed.
							return (node.tagName in acceptable_tags)
								? NodeFilter.FILTER_ACCEPT
								: NodeFilter.FILTER_SKIP;
						default:
							// No other types should be making it through
							// because of our choice of whatToShow below, but
							// be defensive anyway.
							return NodeFilter.FILTER_SKIP;
					}
				}
			};
			
			var walker = doc.createTreeWalker(elem,
				NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT, filter, false);
			
			// Because of our filtering above, if we get any next node back
			// (the next node can be any node below our root, which is the
			// element being tested), we know that the element is not empty.
			// If we get nothing back, that means that the tree walker went
			// through all of the ancestors without finding a node that our
			// filter accepted, and thus the element is empty.
			return !walker.nextNode();
		} else {
			// No traversal support. Look at the element's inner HTML.
			
			// This pattern must be kept in sync with the map in the previous
			// branch.
			acceptable_tags = /^<(img|hr|table)$/ig;
			
			var html = elem.innerHTML;
			
			// Preserve our acceptable tags from being eliminated on the next
			// replacement.
			html = html.replace(acceptable_tags, 'k');
			
			// Remove all non-preserved tags.
			html = html.replace(/<[^>]+>/g, '');
			
			// Check to see if what's remaining contains any non-whitespace
			// characters; if it does, then the element is non-empty.
			return !non_whitespace.test(html);
		}
	},
	
	/**
	 * Adds a class to an element.
	 * @param {Element}	elem	the element to which the class will be added
	 * @param {string}	class_name	the name of the class to add
	 * @return {void}
	 */
	add_class: function add_class_to_element(elem, class_name)
	{
		var classes = Util.Element.get_class_array(elem);
		classes.push(class_name);
		Util.Element.set_class_array(elem, classes);
	},
	
	/**
	 * Removes a class from an element.
	 * @param {Element}	elem	the element from which the class will be removed
	 * @param {string}	class_name	the name of the class to remove
	 * @return {void}
	 */
	remove_class: function remove_class_from_element(elem, class_name)
	{
		var classes = Util.Element.get_class_array(elem);

		for (var i = 0; i < classes.length; i++) {
			if (classes[i] == class_name)
				classes.splice(i, 1);
		}

		Util.Element.set_class_array(elem, classes);
	},
	
	/**
	 * Checks if an element has a particular class.
	 * @param {Element}	elem	the element to check
	 * @param {string}	class_name	the name of the class to check for
	 * @return true if the element has the class, false otherwise
	 * @return {boolean}
	 */
	has_class: function element_has_class(elem, class_name)
	{
		return Util.Element.get_class_array(elem).contains(class_name);
	},
	
	/**
	 * Checks if an element has all of the given classes.
	 * @param {Element}	elem	the element to check
	 * @param {mixed}	classes	either a string or an array of class names
	 * @return true if the element has all of the classes, false if otherwise
	 * @return {boolean}
	 */
	has_classes: function element_has_classes(elem, classes)
	{
		if (Util.is_string(classes))
			classes = classes.split(/s+/);
		
		var element_classes = Util.Element.get_class_array(elem);
		return classes.every(function check_one_element_class(class_name) {
			return element_classes.contains(class_name);
		});
	},
	
	/**
	 * Returns a string with all of an element's classes or null.
	 * @param {Element}	elem
	 * @return {string}
	 */
	get_all_classes: function get_all_classes_from_element(elem)
	{
		return (Util.is_valid_object(elem))
			? elem.getAttribute('class') || elem.getAttribute('className')
			: null;
	},
	
	/**
	 * Gets all of an element's classes as an array.
	 * @param {Element}	elem
	 * @return {array}
	 */
	get_class_array: function get_array_of_classes_from_element(elem)
	{
		return (elem.className && elem.className.length > 0)
			? elem.className.split(/\s+/)
			: [];
	},
	
	/**
	 * Sets all of the classes on an element.
	 * @param {Element} elem
	 * @param {string} class_names
	 * @return {void}
	 */
	set_all_classes: function set_all_classes_on_element(elem, class_names)
	{
		elem.className = all_classes;
	},
	
	/**
	 * Sets all of the classes on an element.
	 * @param {Element} elem
	 * @param {array} class_names
	 * @return {void}
	 */
	set_class_array: function set_array_of_classes_on_element(elem, class_names)
	{
		if (class_names.length == 0)
			Util.Element.remove_all_classes(elem);
		else
			elem.className = class_names.join(' ');
	},
	
	/**
	 * Removes all of an element's classes.
	 * @param {Element}	elem
	 * @return {void}
	 */
	remove_all_classes: function remove_all_classes_from_element(elem)
	{
		elem.removeAttribute('className');
		elem.removeAttribute('class');
	},
	
	/**
	 * Find all elements below the given root with a matching class name.
	 * @param {Element|Document} root	the root element
	 * @param {string} classes	the class name(s) to search for
	 * @return {array}	an array (NOT a NodeList) of elements
	 */
	find_by_class: function find_elements_by_class_name(root, classes)
	{
		if (root.getElementsByClassName) { // use native impl. where available
			return Util.Array.from(root.getElementsByClassName(classes));
		}
		
		function xpath_evaluate(expression)
		{
			var results = [];
			var query;
			var i, length;
			
			if (!document.evaluate || !XPathResult) {
				throw new Util.Unsupported_Error("XPath");
			}
			
			query = document.evaluate(expression, root, null,
				XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
			for (i = 0, length = query.snapshotLength; i < length; i++) {
				results.push(query.snapshotItem(i));
			}
			return results;
		}
		
		classes = classes.toString().replace(/^\s*/, '').replace(/\s*$/, '');
		if (document.evaluate) {
			function convert(cn) {
				return (cn.length > 0) ? "[contains(concat(' ', @class, ' '), "
					+ "' " + cn + " ')]" : null;
			}
			var expr = classes.split(/\s+/).map(convert).join('');
			return (expr.length) ? xpath_evaluate('.//*' + expr) : [];
		} else {
			var found = [];
			var children = root.getElementsByTagName("*")
			var child;
			
			classes = classes.split(/\s+/);
			var test = (classes.length == 1)
				? function(e) { return Util.Element.has_class(e, classes[0]); }
				: function(e) { return Util.Element.has_classes(e, classes); };
			
			for (var i = 0; child = children[i]; i++) {
				if (test(child))
					found.push(child);
			}
			
			return found;
		}
	},
	
	/**
	 * Returns an element's name's prefix or an empty string if there is none.
	 * (e.g. <o:p> --> 'o';  <p> --> '')
	 * @param {Element}	elem
	 * @return {string}
	 */
	get_prefix: function get_element_name_prefix(elem)
	{
		function get_gecko_prefix()
		{
			var parts = node.tagName.split(':');
			return (parts.length >= 2) ? parts[0] : '';
		}
		
		return node.prefix || node.scopeName || get_gecko_prefix();
	},
	
	/**
	 * Finds the absolute position of the element; i.e. its position relative to
	 * the window.
	 * @param {HTMLElement} elem
	 * @return {object}
	 */
	get_position: function get_element_position(elem)
	{
		var pos = {x: 0, y: 0};
		
		// Loop through the offset chain.
		for (var e = elem; e; e = e.offsetParent) {
			pos.x += (Util.is_number(e.offsetLeft))
			 	? e.offsetLeft
				: e.screenLeft;
			pos.y += (Util.is_number(e.offsetTop))
			 	? e.offsetTop
				: e.screenTop;
		}
		
		return pos;
	},
	
	/**
	 * For each element out of the given element and its ancestors that has a
	 * CSS position of "relative", sums up their x and y offsets and returns
	 * them.
	 * @param {Window}	window	the element's window
	 * @param {HTMLElement}	elem	the element to test
	 * @return {object}	x and y offsets
	 */
	get_relative_offsets: function get_element_relative_offsets(window, elem)
	{
		if (!Util.Node.is_element(elem) || !Util.is_valid_object(window)) {
			throw new TypeError('Must provide valid window and element ' +
				'objects to Util.Event.get_relative_offsets().');
		}
		
		var pos = {x: 0, y: 0};
		
		for (var e = elem; e && e.nodeName != 'HTML'; e = e.parentNode) {
			var position = Util.Element.get_computed_style(window, e).position;
			if (position == 'relative') {
				pos.x += e.offsetLeft;
				if (!Util.Element._buggy_ie_offset_top())
					pos.y += e.offsetTop;
			}
		}
		
		return pos;
	},
	
	/**
	 * True if the browser is IE ≤ 7, which incorrectly calculates elements'
	 * offsetTop attribute.
	 * @see http://www.quirksmode.org/dom/w3c_cssom.html#offsetParent
	 * @type Boolean
	 */
	_buggy_ie_offset_top: function buggy_ie_offset_top() {
		var match, major;
		
		if (typeof(buggy_ie_offset_top.result) == 'undefined') {
			if (!Util.Browser.IE) {
				buggy_ie_offset_top.result = false;
			} else {
				match = /^(\d)/.exec(Util.Browser.get_version());
				if (match && match.length && match.length >= 1) {
					major = parseInt(match[1]);
					buggy_ie_offset_top.result =  (major <= 7);
				} else {
					buggy_ie_offset_top.result = false;
				}
			}
		}
		
		return buggy_ie_offset_top.result;
	}
};

Util.Element._get_attribute_names = (function has_outer_html() {
	var guinea_pig = document.createElement('P');
	var parser = null;
	var attrs;
	guinea_pig.className = "_foo";
	
	if (guinea_pig.outerHTML && (/_foo/.test(guinea_pig.outerHTML))) {
		return function _get_attribute_names_from_outer_html(el) {
			var result;
			
			if (!parser) {
				parser = new Util.HTML_Parser();
				parser.add_listener('open', function tag_opened(n, attributes) {
					attrs = Util.Object.names(attributes);
					parser.halt();
				});
			}
			
			parser.parse(el.outerHTML);
			result = attrs;
			attrs = null;
			return result;
		};
	} else if (Util.Browser.Gecko) {
		// It looks like at least Firefox 3 is giving us the attributes in
		// reversed declaration order, so we'll read them out backwards.
		return function _get_attribute_names_reversed(el) {
			var length = el.attributes.length;
			var attributes = {};
			var a;
			for (var i = (length - 1); i >= 0; i--) {
				a = el.attributes[i];
				if (!a.specified || a.nodeName in attributes)
					continue;
				attributes[a.nodeName] = true;
			}
			return Util.Object.names(attributes);	
		};
	} else {
		return function _get_attribute_names(el) {
			var length = el.attributes.length;
			var attributes = {};
			var a;
			for (var i = 0; i < length; i++) {
				a = el.attributes[i];
				if (!a.specified || a.nodeName in attributes)
					continue;
				attributes[a.nodeName] = true;
			}
			return Util.Object.names(attributes);	
		};
	}
})();

// file Util.Event.js
/**
 * Does nothing.
 *
 * @class A container for functions relating to events. (Not that it
 * matters much, but it makes sense for even functions that work
 * primarily on something other than an event (for example,
 * add_event_listener works primarily on a node) to be in here rather
 * than elsewhere (for example, Util.Node) because all evente-related
 * function are in the DOM2+ standards defined in non-core modules,
 * i.e.
 */
Util.Event = function()
{
};

/**
 * Creates a wrapper around a function that ensures it will always be called
 * with the event object as its sole parameter.
 *
 * @param	func	the function to wrap
 */
Util.Event.listener = function(func)
{	
	return function()
	{
		return func(arguments[0] || window.event);
	};
}

/**
 * Adds an event listener to a node. 
 * <p>
 * N.B., for reference, that it is dangerous in IE to attach as a
 * listener a public method of an object. (The browser may crash.) See
 * Loki's Listbox.js for a workaround.
 *
 * @param	node		the node to which to add the event listener
 * @param	type		a string indicating the type of event to listen for, e.g. 'click', 'mouseover', 'submit', etc.
 * @param	listener	a function which will be called when the event is fired, and which receives as a paramater an
 *                      Event object (or, in IE, a Util.Event.DOM_Event object)
 */
Util.Event.add_event_listener = function(node, type, listener)
{
	if (!Util.is_valid_object(node)) {
		throw new TypeError("Cannot listen for a '" + type + "' event on a " +
			"non-object.");
	} else if (!type || !listener) {
		throw new Error("Must provide an event type and a callback function " +
			"to add an event listener.");
	}
	
	if (node.addEventListener) {
		node.addEventListener(type, listener, false);
	} else if (node.attachEvent) {
		node.attachEvent('on' + type, listener);
	} else {
		throw new Util.Unsupported_Error('modern event handling');
	}
};

/**
 * (More intelligently and concisely) adds an event listener to a node.
 * @param {Node}	target	the node to which to add the event listener
 * @param {string}	type	the type of event to listen for
 * @param {function}	listener	the listener function that will be called
 * @param {object}	context	the "this context" in which to call the listener
 * @type void
 */
Util.Event.observe = function(target, type, listener, context)
{
	if (target.addEventListener) {
		if (context) {
			target.addEventListener(type, function event_listener_proxy() {
				listener.apply(context, arguments);
			}, false);
		} else {
			target.addEventListener(type, listener, false);
		}
	} else if (target.attachEvent) {
		target.attachEvent('on' + type, function ie_event_listener_proxy() {
			listener.call(context, (arguments[0] || window.event));
		});
	} else {
		throw new Util.Unsupported_Error('modern event handling');
	}
}

/**
 * Removes an event listener from a node. Doesn't work at present.
 *
 * @param	node		the node from which to remove the event listener
 * @param	type		a string indicating the type of event to stop listening for, e.g. 'click', 'mouseover', 'submit', etc.
 * @param	listener	the listener function to remove
 */
Util.Event.remove_event_listener = function(node, type, listener)
{
	try
	{
		node.removeEventListener(type, listener, false); // I think that with "false" this is equivalent to the IE way below
	}
	catch(e)
	{
		try
		{
			node.detachEvent('on' + type, listener);
		}
		catch(f)
		{
			throw(new Error('Util.Event.remove_event_listener(): Neither the W3C nor the IE way of removing an event listener worked. ' +
							'When the W3C way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
};

/**
 * Tests whether the given keyboard event matches the provided key code.
 * @param {Event}	e	the keyboard event
 * @param {integer} key_code	the key code
 * @return {boolean} true if the given event represented the code, false if not
 */
Util.Event.matches_keycode = function matches_keycode(e, key_code)
{
	if (['keydown', 'keyup'].contains(e.type) && e.keyCode == keycode) {
		return true;
	} else if (e.type == 'keypress') {
		var code = (e.charCode)
			? e.charCode
			: e.keyCode; // Internet Explorer instead puts the ASCII value here.
			
			return key_code == code ||
				(key_code >= 65 && key_code <= 90 && key_code + 32 == code);
	} else {
		throw new TypeError('The given event is not an applicable ' +
			'keyboard event.');
	}
};

/**
 * Gets the mouse coordinates of the given event.
 * @type object
 * @param {Event} event	the mouse event
 * @return {x: (integer), y: (integer)}
 */
Util.Event.get_coordinates = function get_coordinates(event)
{
	var doc = (event.currentTarget || event.srcElement).ownerDocument;
	
	var x = event.pageX || event.clientX + doc.body.scrollLeft +
		doc.documentElement.scrollLeft;
	var y = event.pageY || event.clientY + doc.body.scrollTop +
		doc.documentElement.scrollTop;
		
	return {x: x, y: y};
};

/**
 * Calls the listeners which have been "attached" to the
 * event.currentTarget using add_event_listener. This function is
 * intended for use primarily by add_event_listener.
 *
 * @param	event	the event object, to pass to the listeners
 */
Util.Event.call_wrapped_listeners = function(event)
{
	var node = event.currentTarget;
	var type = event.type;
	var listener, extra_args;

	for ( var i = 0; i < node.Event__listeners[type].length; i++ )
	{
		listener = node.Event__listeners[type][i]['listener'];
		extra_args = node.Event__listeners[type][i]['extra_args'];

		listener(event, extra_args);
	}
};

/**
 * Constructor for a mimic'd DOM Event object, primarly for use in the
 * IE version of Util.Event.add_event_listener. Properties which are
 * initialized below to null are in the W3C spec but haven't yet
 * needed to be implemented in this mimic'd object.
 *
 * @param	currentTarget	the document node which is the target of the event
 * @param	type			the type of the event, e.g. 'click'
 */
Util.Event.IE_DOM_Event = function(currentTarget, type)
{
	this.type = type;
// 	this.target = window.event.srcElement; // doesn't work if the event's target belongs to another window than the one referenced by "window", e.g. a popup window
	this.currentTarget = currentTarget;
	this.eventPhase = null;
	this.bubbles = null;
	this.cancelable = null;
	this.timeStamp = null;
	this.initEvent = null;
	this.initEvent = function(eventTypeArg, canBubbleArg, cancelableArg) { return null; };
	this.preventDefault = function() { window.event.returnValue = false; };
	this.stopPropogation = function() { window.event.cancelBubble = true; };
};

Util.Event.prevent_default = function(event)
{
	try // W3C
	{
		event.preventDefault();
	}
	catch(e)
	{
		try // IE
		{
			event.returnValue = false;
			//event.cancelBubble = true;
		}
		catch(f)
		{
			throw('Util.Event.prevent_default: Neither the W3C nor the IE way of preventing the event\'s default action. ' +
				  'When the W3C way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
				  'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.');
		}
	}
	return false;
};

/**
 * Returns the target.
 * Taken from quirksmode.org, by Peter-Paul Koch.
 */
Util.Event.get_target = function get_event_target(e)
{
	var targ;
	//if (!e) var e = window.event;
	if (e.target) targ = e.target;
	else if (e.srcElement) targ = e.srcElement;
	if (targ.nodeType == 3) // defeat Safari bug
		targ = targ.parentNode;
	return targ;
};

// file Util.Object.js
/**
 * Does nothing.
 *
 * @class Container for functions relating to objects.
 */
Util.Object = function()
{
};

/**
 * Returns the names of an object's properties as an array. Ignores properties
 * found on any object.
 */
Util.Object.names = function(obj)
{
	var names = [];
	var bare = {};
	
	// JavaScript doesn't really have a hash or dictionary type, only a
	// generic object type. This is a problem because the variables object
	// we're given can have properties that are intrinsic to objects which
	// shouldn't be added to the query string. To work around this, we
	// create a bare object and ignore any properties in variables that are
	// also found on the bare object.
	
	for (var name in obj) {
		if (name in bare)
			continue;
		names.push(name);
	}
	
	return names;
}

/**
 * Calls the given function once per property in the object. The function
 * should accept the property's name as the first argument and its value as
 * the second.
 */
Util.Object.enumerate = function(obj, func, thisp)
{
	if (!thisp)
		var thisp = null;
	
	Util.Object.names(obj).each(function (name)
	{
		func.call(thisp, name, obj[name]);
	});
}

/**
 * Clones (creates a copy of) the given object.
 */
Util.Object.clone = function(some_object)
{
	var new_obj;
	
	if (!some_object || typeof(some_object) != 'object')
		return some_object;
	
	try {
		new_obj = new some_object.constructor();
	} catch (e) {
		new_obj = new Object();
	}
	
	for (var name in some_object) {
		new_obj[name] = some_object[name];
	}
	
	return new_obj;
}

/**
 * Determines if two objects are equal.
 */
Util.Object.equal = function(a, b)
{
	if (typeof(a) != 'object') {
		return (typeof(b) == 'object')
			? false
			: (a == b);
	} else if (typeof(b) != 'object') {
		return false;
	}
	
	seen = {};
	
	for (var name in a) {
		if (!(name in b && Util.Object.equal(a[name], b[name])))
			return false;
		seen[name] = true;
	}
	
	for (var name in b) {
		if (!(name in seen))
			return false;
	}
	
	return true;
}

/**
 * Pops up a window whose contents are generated by get_print_r_chunk, q.v.
 *
 * @param	obj				the object to print_r
 * @param	max_deepness	(optional) how many levels of parameters to automatically open. Defaults to 1.
 * @return					a UL element which has as descendents a representation of the given object
 */
Util.Object.print_r = function(obj, max_deepness)
{
	var alert_win = new Util.Window;
	alert_win.open('', '_blank', 'status=1,scrollbars=1,resizable,width=600,height=300');
	var print_r_chunk = Util.Object.get_print_r_chunk(obj, alert_win.document, alert_win, max_deepness);
	alert_win.body.appendChild(print_r_chunk);
};

/**
 * Generates a UL element which has as descendents a representation of
 * the given object. The representation is similar to that exposed by
 * PHP's print_r or pray.
 *
 * @param	obj				the object to print_r
 * @param	doc_obj			(optional) the document object with which to create the print_r chunk. 
 *                          Defaults to the document refered to by <code>document</code>.
 * @param	max_deepness	(optional) how many levels of parameters to automatically open. Defaults to 1.
 * @return					a UL element which has as descendents a representation of the given object
 */
Util.Object.get_print_r_chunk = function(obj, doc_obj, win, max_deepness)
{
	if ( doc_obj == null )
	{
		doc_obj = document;
	}

	if ( max_deepness == null )
	{
		max_deepness = 1;
	}


	/**
	 * Displays or hides the properties of a property of an object being
	 * print_r'd. Should be called only when a click event is fired by the
	 * appropriate element in the print_r window.
	 *
	 * @param	event	The event object passed onclick
	 */
	var open_or_close_print_r_ul = function(event, variable)
	{
		event = event == null ? win.event : event;
		var span_elem = event.currentTarget == null ? event.srcElement : event.currentTarget;

		// If open, close
		if ( span_elem.nextSibling != null )
		{
			//alert('open, so close (nextSibling =' + span_elem.nextSibling);
			while ( span_elem.nextSibling != null )
				span_elem.parentNode.removeChild(span_elem.nextSibling);
		}
		// Else (if closed), open
		else
		{
			//alert('closed, so open (variable:' + variable + '); span_elem:' + span_elem);
			span_elem.parentNode.appendChild(
				Util.Object.get_print_r_chunk(variable, span_elem.ownerDocument, 1)
			);
		}
	};


	var ul_elem = doc_obj.createElement('UL');

	for ( var var_name in obj )
	{
		var variable, li_elem;
		try
		{
			variable = obj[var_name];

			li_elem = ul_elem.appendChild(
				doc_obj.createElement('LI')
			);
			span_elem = li_elem.appendChild(
				doc_obj.createElement('SPAN')
			);
			span_elem.appendChild(
				doc_obj.createTextNode(var_name + " => " + variable)
			);
			Util.Event.add_event_listener(span_elem, 'click', function (event) { open_or_close_print_r_ul(event, variable); });
			//span_elem.onclick = open_or_close_print_r_ul;

			var typeof_variable = typeof(variable);
			if ( typeof_variable == "object" &&
				 !( typeof_variable == "string" ||
					typeof_variable == "boolean" ||
					typeof_variable == "number" ) )
			{
				if ( max_deepness > 1 )
				{
					li_elem.appendChild(
						Util.Object.get_print_r_chunk(variable, doc_obj, win, max_deepness - 1)
					);
				}
			}
		}
		catch(e)
		{
			// Only stop for fatal errors, because some properties when
			// accessed will always throw an error, and to die for
			// all of these would make print_r useless.
			if ( e.name != 'InternalError' )
			{
				ul_elem.appendChild(
					doc_obj.createElement('LI')
				).appendChild(
					doc_obj.createTextNode(var_name + " => [[[Exception thrown: " + e.message + "]]]")
				);
			}
			else
			{
				throw e;
			}
		}
	}
	return ul_elem;
};

// file Util.OOP.js
/**
 * @class Container for methods that allow standard OOP thinking to be
 * shoehorned into JavaScript, for better or worse.
 */
Util.OOP = {};

/**
 * "Mixes in" an object's properties.
 * @param	{object}	target	The object into which things will be mixed
 * @param	{object}	source	The object providing the properties
 * @type object
 * @return target
 */
Util.OOP.mixin = function(target, source)
{
	var names = Util.Object.names(source);
	for (var i = 0; i < names.length; i++) {
		target[names[i]] = source[names[i]];
	}
	
	return target;
}

/**
 * Sets up inheritance from parent to child. To use:
 * - Create parent and add parent's methods and properties.
 * - Create child
 * - At beginning of child's constructor, call inherits(parent, child)
 * - Add child's new methods and properties
 * - To call method foo in the parent: this.superclass.foo.call(this, params)
 * - Be careful where you use self and this: in inherited methods, self
 *   will still refer to the superclass, whereas this will refer, properly, to the
 *   child class. If you must use self, e.g. for event listeners, define self
 *   only inside methods, not directly inside the constructor. (Note: The existing
 *   code doesn't follow this advice perfectly; follow this advice, not that code.)
 *
 * Changed on 2007-09-13 by EN: Now calls the parent class's constructor! Any
 * arguments that need to be passed to the constructor can be provided after
 * the child and parent.
 *
 * Inspired by but independent of <http://www.crockford.com/javascript/inheritance.html>.
 *
 * The main problem with just doing something like
 *     child.prototype = new parent();
 * is that methods inherited from the parent can't set properties accessible
 * by methods defined in the child.
 */
Util.OOP.inherits = function(child, parent)
{
	var parent_prototype = null;
	var nargs = arguments.length;
	
	if (nargs < 2) {
		throw new TypeError('Must provide a child and a parent class.');
	} else if (nargs == 2) {
		parent_prototype = new parent;
	} else {
		// XXX: Is there really no better way to do this?!
		//      Something involving parent.constructor maybe?
		var arg_list = $R(2, nargs).map(function (i) {
			return 'arguments[' + String(i) + ']';
		});
		eval('parent_prototype = new parent(' + arg_list.join(', ') + ')')
	}
	
	Util.OOP.mixin(child, parent_prototype);
	child.superclass = parent_prototype;
};

/**
 * Sets up inheritance from parent to child, but only copies over the elements
 * in the parent's prototype provided as arguments after the parent class.
 */
Util.OOP.swiss = function(child, parent)
{
	var parent_prototype = new parent;
    for (var i = 2; i < arguments.length; i += 1) {
        var name = arguments[i];
        child[name] = parent_prototype[name];
    }
    return child;
}; 
// file Util.Anchor.js
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

// file Util.Block.js
/**
 * Defines the behavior of the block level elements with regard to paragraphs.
 * Replaces Util.BLE_Rules.
 */
Util.Block = {
	/**
	 * Element is a block-level element.
	 * @type Number
	 */
	BLOCK: 1,
	
	/**
	 * Element is a paragraph. It cannot contain two line breaks in succession.
	 */
	PARAGRAPH: 2,
	
	/**
	 * Element can contain paragraphs (and, in fact, all inline content should
	 * be within them).
	 * @type Number
	 */
	PARAGRAPH_CONTAINER: 4,
	
	/**
	 * Inline content nodes should be direct children of this element unless
	 * multiple paragraphs are desired, in which case it should behave as a
	 * paragraph container.
	 * @type Number
	 */
	MULTI_PARAGRAPH_CONTAINER: 8,
	
	/**
	 * Directly contains inline content; should not contain paragraphs.
	 * @type Number
	 */
	INLINE_CONTAINER: 16,
	
	/**
	 * Block-level element that may not contain anything.
	 * @type Number
	 */
	EMPTY: 32,
	
	/**
	 * Can exist as either a block-level element or an inline child of a block-
	 * level element.
	 * @type Number
	 */
	MIXED: 64,
	
	/**
	 * Whitespace is preserved within these elements.
	 * @type Number
	 */
	PREFORMATTED: 128,
	
	get_flags: function get_flags(element)
	{
		return (this._get_flag_map()[element.tagName] || 0);
	},
	
	is_block: function is_block(element)
	{
		return !!(this.get_flags(element) & Util.Block.BLOCK);
	},
	
	is_paragraph_container: function is_paragraph_container(element)
	{
		return !!(this.get_flags(element) & Util.Block.PARAGRAPH_CONTAINER);
	},
	
	is_multi_paragraph_container: function is_multi_paragraph_container(element)
	{
		return !!(this.get_flags(element) &
			Util.Block.MULTI_PARAGRAPH_CONTAINER);
	},
	
	is_inline_container: function is_inline_container(element)
	{
		return !!(this.get_flags(element) & Util.Block.INLINE_CONTAINER);
	},
	
	is_empty: function is_empty(element)
	{
		return !!(this.get_flags(element) & Util.Block.EMPTY);
	},
	
	is_mixed: function is_mixed(element)
	{
		return !!(this.get_flags(element) & Util.Block.MIXED);
	},
	
	is_preformatted: function is_preformatted(element)
	{
		return !!(this.get_flags(element) & Util.Block.PREFORMATTED);
	},
	
	/**
	 * Accepts either an HTML document or an element and enforces paragraph
	 * behavior inside that node and its children.
	 * @param {Node}     root        an HTML document or element
	 * @param {object}	 [settings]  parameters that change enforcement settings
	 * @config {object}  [overrides] if specified, allows element flags to be
	 *                               overridden
	 * @return {void}
	 */
	enforce_rules: function enforce_paragraph_rules(root, settings)
	{
		var node;
		var waiting;
		var flags;
		var child;
		var descend;
		
		if (!settings)
			settings = {};
		
		if (root.nodeType == Util.Node.DOCUMENT_NODE) {
			root = root.body;
		} else if (root == root.ownerDocument.documentElement) {
			root = root.ownerDocument.body;
		} else if (root.tagName == 'HEAD') {
			throw new Error('Cannot enforce paragraph rules on a HEAD tag.');
		}
		
		function get_flags(element)
		{
			return (settings.overrides && settings.overrides[element.tagName])
				|| Util.Block.get_flags(element);
		}
		
		function is_relevant(node)
		{
			// The regular expression below is different than that used
			// in Util.Node.is_non_whitespace_text_node; the latter does
			// not include spaces. I'm not actually sure which is correct.
			
			return (node.nodeType == Util.Node.ELEMENT_NODE || 
				node.nodeType == Util.Node.TEXT_NODE &&
				/\S/.test(node.nodeValue));
		}
		
		function is_br(node)
		{
			return node && node.tagName == 'BR';
		}
		
		function is_breaker(node)
		{
			var breaker = null;
			
			if (!is_br(node))
				return false;
				
			// Mozilla browsers (at least) like to keep a BR tag at the end
			// of all paragraphs. As a result, if the user tries to insert a
			// line break at the end of a paragraph, the HTML will end up as:
			//    <p> ...<br><br></p>
			// This is bad because we will detect this as a "breaker" and
			// possibly insert a new paragraph afterwards and delete both the
			// user's line break and Mozilla's. As a workaround, we will only
			// treat two BR's as a breaker if they do not come at the end of
			// their parent.
				
			for (var s = node.nextSibling; s; s = s.nextSibling) {
				if (!breaker) {
					if (is_br(s))
						breaker = [node, s];
					else if (is_relevant(s))
						return false;
				} else if (is_relevant(s)) {
					// The breaker is not at the end of its parent.
					return breaker;
				}
			}
			
			return false;
		}
		
		function belongs_inside_paragraph(node)
		{
			var ok_types = [Util.Node.TEXT_NODE, Util.Node.COMMENT_NODE];
			var flags;
			
			if (ok_types.contains(node.nodeType))
				return true;
			
			flags = get_flags(node);
			return !(flags & Util.Block.BLOCK) || !!(flags & Util.Block.MIXED);
		}
		
		// Factored out this enforcement because both normal paragraph
		// containers and containers that can only contain 0 or ≥2 paragraphs
		// both potentially use the same behavior.
		function enforce_container_child(context, node, c)
		{
			var br;
			var next;
			var created_p;
			
			if (!context.p)
				context.p = null;
			if (context.created_p)
				delete context.created_p;
			
			if (br = is_breaker(c)) { // assignment intentional
				context.p = c.ownerDocument.createElement('P');
				next = br[1].nextSibling;
				br.each(function(b) {
					node.removeChild(b);
				});
				node.insertBefore(context.p, next);
			} else if (belongs_inside_paragraph(c)
				&& Util.Node.is_non_whitespace_text_node(c)) 
			{
				if (!context.p && is_relevant(c)) {
					context.p = c.ownerDocument.createElement('P');
					context.created_p = context.p;
					node.insertBefore(context.p, c);
				}
				
				if (context.p) {
					next = c.nextSibling;
					context.p.appendChild(c);
				}
			} else if (context.p) {
				delete context.p;
			}
			
			if (!next)
				next = c.nextSibling;
			
			return next;
		}
		
		var enforcers = {
			PARAGRAPH: function enforce_paragraph(node)
			{
				var new_p;
				var next;
				var br;
				
				function create_split_paragraph()
				{
					var next_s;
					
					new_p = node.ownerDocument.createElement('P');
					for (var c = next; c; c = next_s) {
						next_s = c.nextSibling;
						new_p.appendChild(c);
					}
					
					node.parentNode.insertBefore(new_p, node.nextSibling);
					return new_p;
				}
				
				for (var c = node.firstChild; c; c = next) {
					next = null;
					
					if (!belongs_inside_paragraph(c)) {
						if (!c.previousSibling) {
							node.parentNode.insertBefore(c, node);
						} else {
							next = c.nextSibling;
							
							if (next) {
								// Create a new paragraph, move all of the
								// children that followed the breaker into it,
								// and continue using that paragraph.
								node = create_split_paragraph();
								next = node.firstChild;
								
								// Move the item that does not belong in the
								// paragraph outside of it and place it between
								// the existing paragraph and the new split
								// paragraph.
								// (Remember, "node" now refers to the split-off
								// paragraph.)
								node.parentNode.insertBefore(c, node);
							} else {
								node.parentNode.insertBefore(c,
									node.nextSibling);
							}
						}
					} else if (br = is_breaker(c)) { // assignment intentional
						next = br[1].nextSibling;
						br.each(function(b) {
							b.parentNode.removeChild(b);
						});
						
						if (next) {
							// Create a new paragraph, move all of the
							// children that followed the breaker into it,
							// and continue using that paragraph.
							node = create_split_paragraph();
							next = node.firstChild;
						}
					}
					
					if (!next)
						next = c.nextSibling;
				}
				
				if (!node.hasChildNodes()) {
					node.parentNode.removeChild(node);
				}
				
				return false;
			},
			
			PARAGRAPH_CONTAINER: function enforce_p_container(node)
			{
				var context = {};
				var next;
				
				for (var c = node.firstChild; c; c = next) {
					next = enforce_container_child(context, node, c);
				}
				
				return node.hasChildNodes();
			},
			
			MULTI_PARAGRAPH_CONTAINER: function enforce_multi_p_container(node)
			{
				var paragraphs = [];
				var multi = get_paragraph_children(node).length > 1;
				var context = {};
				var br;
				var next;
				
				function get_paragraph_children(node)
				{
					var paras = [];
					
					for (var n = node.firstChild; n; n = n.nextSibling) {
						if (n.tagName == 'P')
							paras.push(n);
					}
					
					return paras;
				}
				
				function add_paragraph(para)
				{
					if (para)
						paragraphs.push(para);
					
					return !!para;
				}
				
				function replace_with_children(node)
				{
					while (node.firstChild) {
						node.parentNode.insertBefore(node.firstChild, node);
					}
					
					node.parentNode.removeChild(node);
				}
				
				function create_upto(stop)
				{
					var para = stop.ownerDocument.createElement('P');
					
					var c = node.firstChild;
					var worthwhile = false;
					var next;
					while (c && c != stop) {
						if (!worthwhile && is_relevant(c))
							worthwhile = true;
						
						next = c.nextSibling;
						para.appendChild(c);
						c = next;
					}
					
					if (worthwhile)
						return node.insertBefore(para, stop);
					
					return null;
				}
				
				for (var c = node.firstChild; c; c = next) {
					if (!multi) {
						next = c.nextSibling;
						
						if (!belongs_inside_paragraph(c)) {
							multi = add_paragraph(create_upto(c));
							if (c.tagName == 'P')
								multi = add_paragraph(c);
						} else if (br = is_breaker(c)) { // assignment intent.
							multi = add_paragraph(create_upto(c));
							next = br[1].nextSibling;
							br.each(function(b) {
								b.parentNode.removeChild(b);
							});
						}
					} else {
						next = enforce_container_child(context, node, c);
						if (context.created_p)
							paragraphs.push(context.created_p);
					}
				}
				
				if (paragraphs.length == 1) {
					replace_with_children(paragraphs[0]);
				}
				
				return node.hasChildNodes();
			},
			
			INLINE_CONTAINER: function enforce_inline_container(node)
			{
				// When we discover paragraphs in one of these containers, we
				// actually want to replace them with double line breaks.
				
				var next;
				var next_pc;
				
				function add_br_before(n)
				{
					var br = n.ownerDocument.createElement('BR');
					n.parentNode.insertBefore(br, n);
					return br;
				}
				
				function is_basically_first(n)
				{
					var m = n;
					while (m = m.previousSibling) { // assignment intentional
						if (m.nodeType == Util.Node.ELEMENT_NODE) {
							return false;
						}
						
						if (m.nodeType == Util.Node.TEXT_NODE &&
							(/\S/.test(m.nodeValue)))
						{
							return false;
						}
					}
					
					return true;
				}
				
				for (var c = node.firstChild; c; c = next) {
					next = c.nextSibling;
					if (c.tagName == 'P') {
						if (!is_basically_first(c)) {
							add_br_before(c);
							add_br_before(c);
						}
						
						for (var pc = c.firstChild; pc; pc = next_pc) {
							next_pc = pc.nextSibling;
							node.insertBefore(pc, c);
						}
						
						node.removeChild(c);
					}
				}
				
				return false;
			},
			
			EMPTY: function enforce_empty_block_level_element(node)
			{
				while (node.firstChild)
					node.removeChild(node.firstChild);
				
				return false;
			}
		};
		
		waiting = [root];
		
		while (node = waiting.pop()) { // assignment intentional
			flags = get_flags(node);
			
			if (!flags & Util.Block.BLOCK)
				continue;
				
			descend = true; // default to descend if we don't find an enforcer
			                // for the current node
			for (var name in enforcers) {
				if (flags & Util.Block[name]) {
					descend = enforcers[name](node);
					break;
				}
			}
			
			if (!descend)
				continue;
			
			// Add the node's children (if any) to the processing stack.
			for (child = node.lastChild; child; child = child.previousSibling) {
				if (child.nodeType == Util.Node.ELEMENT_NODE)
					waiting.push(child);
			}
		}
	},
	
	_get_flag_map: function _get_block_flag_map()
	{
		var map;
		var NORMAL = 0;
		
		if (!this._flag_map) {
			// Util.Block.BLOCK is added to all of these at the final step.
			map = {
				P: Util.Block.PARAGRAPH,
				
				BODY: Util.Block.PARAGRAPH_CONTAINER,
				BLOCKQUOTE: Util.Block.PARAGRAPH_CONTAINER,
				FORM: Util.Block.PARAGRAPH_CONTAINER,
				FIELDSET: Util.Block.PARAGRAPH_CONTAINER,
				BUTTON: Util.Block.PARAGRAPH_CONTAINER,
				MAP: Util.Block.PARAGRAPH_CONTAINER,
				NOSCRIPT: Util.Block.PARAGRAPH_CONTAINER,
				DIV: Util.Block.PARAGRAPH_CONTAINER, // changed from multi

				H1: Util.Block.INLINE_CONTAINER,
				H2: Util.Block.INLINE_CONTAINER,
				H3: Util.Block.INLINE_CONTAINER,
				H4: Util.Block.INLINE_CONTAINER,
				H5: Util.Block.INLINE_CONTAINER,
				H6: Util.Block.INLINE_CONTAINER,
				ADDRESS: Util.Block.INLINE_CONTAINER,
				PRE: Util.Block.INLINE_CONTAINER | Util.Block.PREFORMATTED,

				TH: Util.Block.MULTI_PARAGRAPH_CONTAINER,
				TD: Util.Block.MULTI_PARAGRAPH_CONTAINER,
				LI: Util.Block.MULTI_PARAGRAPH_CONTAINER,
				DT: Util.Block.MULTI_PARAGRAPH_CONTAINER,
				DD: Util.Block.MULTI_PARAGRAPH_CONTAINER, // changed from pc
				
				OBJECT: NORMAL,
				
				UL: NORMAL,
				OL: NORMAL,
				DL: NORMAL,
				
				TABLE: NORMAL,
				THEAD: NORMAL,
				TBODY: NORMAL,
				TFOOT: NORMAL,
				TR: NORMAL,
				NOFRAMES: NORMAL,
				
				HR: Util.Block.EMPTY,
				IFRAME: Util.Block.EMPTY,
				PARAM: Util.Block.EMPTY,
				
				// XXX: browsers seem to treat these as inline always
				INS: Util.Block.MIXED,
				DEL: Util.Block.MIXED
			};
			
			this._flag_map = {};
			for (var name in map) {
				this._flag_map[name] = (map[name] | Util.Block.BLOCK);
			}
		}
		
		return this._flag_map;
	}
};

// file Util.Chooser.js
/**
 * Constructs a new chooser.
 * @class Allows items and sets of those items to be easily chosen using
 * a simple string selector.
 * @constructor
 * @author Eric Naeseth
 */
Util.Chooser = function Chooser()
{
	this.sets = {
		all: []
	};
	
	this.items = {};
	this.aliases = {};
	
	var bundled_added = false;
	
	function dealias(aliases, name) {
		while (name in aliases)
			name = aliases[name];
		return name;
	}
	
	/**
	 * Retrieves the items requested by the given selector.
	 * @param {String} selector selector string
	 * @param {Boolean} [lenient=false] if true, will not throw an error on
	 * unknown items
	 * @return {Object[]} array of chosen items
	 * @throws {Error} unless lenient is set to true, throws an error when a
	 * selector is provided that does not correspond with an item or a set
	 */
	this.get = function get_from_chooser(selector, lenient)
	{
		var working = {};
		var self = this;
		
		if (!bundled_added && Util.is_function(this._add_bundled)) {
			bundled_added = true;
			this._add_bundled();
		}
		
		var operations = {
			'+': function(name) {
				if (name in self.sets) {
					self.sets[name].each(function (name) {
						name = dealias(self.aliases, name);
						if (name in self.sets)
							Util.OOP.mixin(working, self.get(name));
						else
							working[name] = self.items[name];
					});
				} else if (name in self.items) {
					working[name] = self.items[name];
				} else if (!lenient) {
					throw new Error('Unknown item or set "' + name + '".');
				}
			},
			
			'-': function(name) {
				if (name in self.sets) {
					self.sets[name].each(function (name) {
						var k;
						if (name in self.sets) {
							for (k in self.get(name)) {
								delete working[dealias(self.aliases, k)];
							}
						} else {
							delete working[dealias(name)];
						}
					});
				} else if (name in self.items) {
					delete working[name];
				} else if (!lenient) {
					throw new Error('Unknown item or set "' + name + '".');
				}
			}
		};
		
		var operation = operations['+'];
		var part_pattern = /([+-])?\s*(\w+)/;
		
		(selector || 'default').match(/([+-])?\s*(\w+)/g).each(function(part) {
			var breakdown = part.match(part_pattern);
			if (!breakdown) {
				throw new Error('Invalid selector component "' + part + '".');
			}
			
			if (breakdown[1]) {
				operation = operations[breakdown[1]];
				if (!operation) {
					throw new Error('Invalid operator "' + breakdown[1] + '".');
				}
			}
			
			operation(dealias(this.aliases, breakdown[2]));
		}, this);
		
		return working;
	}
	
	/**
	 * Registers an item.
	 * @param {string} the selectable name under which the item will be
	 *   available
	 * @param {mixed} the item being registered
	 * @return the registered item
	 * @type mixed
	 */
	this.add = function add_item_to_chooser(name, item)
	{
		if (name in this.items) {
			if (this.items[name] == item)
				return item;
			throw new Error('An item with the name "' + name + '" is ' +
				'already registered.');
		} else if (name in this.sets) {
			throw new Error('A set is registered under the name "' + name +
				'".');
		}
		
		this.items[name] = item;
		this.sets.all.push(name);
		
		return item;
	}
	
	/**
	 * Creates an alias.
	 * @param {String} actual
	 * @param {String} alias
	 * @return {void}
	 */
	this.alias = function create_alias(actual, alias) {
		this.aliases[alias] = actual;
	}
	
	/**
	 * Adds a new set, or adds new members to an existing set.
	 * @param {string} the set's name
	 * @param {array} the set's members
	 * @type void
	 */
	this.put_set = function put_set_into_chooser(name, members)
	{
		if (name in this.items) {
			throw new Error('An item is registered under the name "' +
				name + '"; cannot create a set with the same name.');
		}
		
		if (!this.sets[name])
			this.sets[name] = members.slice(0); // make a copy
		else
			this.sets[name].append(members);
	}
}

// file Util.Cookie.js
/**
 * @class Contains helper functions related to cookies.
 * @author Eric Naeseth
 */
Util.Cookie = {
	/**
	 * Gets either all available cookies or the value of a specific cookie.
	 * @param {string} [name] if only one cookie's value is desired, its name
	 *                        may be provided here
	 * @return {mixed} either an object whose keys are cookie names and values
	 *                 are the corresponding cookie values, or a string
	 *                 corresponding to the value of the cookie
	 */
	get: function get_cookies(name)
	{
		var cookies = document.cookie.split(';');
		var cookie_pattern = /(\S+)=(.+)$/;
		var data = {};
		
		for (var i = 0; i < cookies.length; i++) {
			var match = cookie_pattern.exec(cookies[i]);
			if (!match || !match[1] || !match[2])	
				continue;
			
			if (name && match[1] == name)
				return match[2];
			else if (!name)
				data[match[1]] = match[2];
		}
		
		if (!name)
			return data;
	},
	
	/**
	 * Sets a cookie.
	 * @param {string} name   the name of the cookie
	 * @param {string} value  the cookie's value
	 * @param {number} [days] the number of days for which the cookie should
	 *                        remain valid; if unspecified, the cookie remains
	 *                        valid only for the active browser session
	 * @return {void}
	 */
	set: function set_cookie(name, value, days)
	{
		var expires = '';
		
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			
			expires = '; expires=' + date.toGMTString();
		}
		
		document.cookie = name + '=' + value + expires + '; path=/';
	},
	
	/**
	 * Deletes a cookie.
	 * @param {string} name   the name of the cookie to delete
	 * @return {void}
	 */
	erase: function erase_cookie(name)
	{
		this.set(name, '', -1);
	}
}; 
// file Util.Document.js
/**
 * Wraps a DOM Document object to provide convenient functions.
 *
 * @class Container for functions relating to nodes.
 */
Util.Document = function(doc)
{
	for (var n in Util.Document) {
		if (n.charAt(0) == '_')
			continue;

		var a = Util.Document[n];
		if (typeof(a) != 'function')
			continue;

		this[n] = a.dynamic_curry(doc);
	}
};

/**
 * Creates an element in the document, optionally setting some attributes on it
 * and adding children.
 * @param	doc			document on which to create the element
 * @param	name		name of the tag to create
 * @param	attrs		any attributes to set on the new element
 * @param	children	any child nodes to add
 */
Util.Document.create_element = function(doc, name, attrs, children)
{
	// Internet Explorer cannot really set the name attribute on
	// an element. It can, however, be set on an element at the time
	// it is created using a proprietary IE syntax, for example:
	//     document.createElement('<INPUT name="foo">')
	// See http://tinyurl.com/8qsj2 for more information.
	function create_normal()
	{
		return doc.createElement(name.toUpperCase());
	}
	
	function create_ie()
	{
		try {
			return doc.createElement('<' + name.toUpperCase() +
				' name="' + attrs.name + '">');
		} catch (e) {
			return create_normal();
		}
	}
	
	var e = (attrs && attrs.name && Util.Browser.IE)
		? create_ie()
		: create_normal();
	
	function collapse(i, dom_text)
	{
		switch (typeof(i)) {
			case 'function':
				return collapse(i(), dom_text);
			case 'string':
				return (dom_text) ? doc.createTextNode(i) : i;
			default:
				return i;
		}
	}
	
	function dim(dimension)
	{
		return (typeof(dimension) == 'number') ? dimension + 'px' : dimension;
	}
	
	var style = {};
	
	for (var name in attrs || {}) {
		var dest_name = name;
		
		switch (name) {
			case 'className':
			case 'class':
				// In IE, e.setAttribute('class', x) does not work properly:
				// it will indeed set an attribute named "class" to x, but
				// the CSS for that class won't actually take effect. As a
				// workaround, we just set className directly, which works in
				// all browsers.
				
				// See http://tinyurl.com/yvsqbx for more information.
				
				var klass = attrs[name];
				
				// Allow an array of classes to be passed in.
				if (typeof(klass) != 'string' && klass.join)
					klass = klass.join(' ');
					
				e.className = klass;
				continue; // note that this continues the for loop!
			case 'htmlFor':
				dest_name = 'for';
				break;
			case 'style':
				if (typeof(style) == 'object') {
					style = attrs.style;
					continue; // note that this continues the for loop!
				}
		}
		
		var a = attrs[name];
		if (typeof(a) == 'boolean') {
			if (a)
				e.setAttribute(dest_name, dest_name);
			else
				continue;
		} else {
			e.setAttribute(dest_name, collapse(a, false));
		}
	}
	
	for (var name in style) {
		// Special cases
		switch (name) {
			case 'box':
				var box = style[name];
				e.style.left = dim(box[0]);
				e.style.top = dim(box[1]);
				e.style.width = dim(box[2]);
				e.style.height = dim(box[3] || box[2]);
				break;
			case 'left':
			case 'top':
			case 'right':
			case 'bottom':
			case 'width':
			case 'height':
				e.style[name] = dim(style[name]);
				break;
			default:
				e.style[name] = style[name];
		}
	}
	
	Util.Array.for_each(children || [], function(c) {
		e.appendChild(collapse(c, true));
	});
	
	return e;
}

/**
 * Make the document editable. Mozilla doesn't support
 * contentEditable. Both IE and Mozilla support
 * designMode. However, in IE if designMode is set on an iframe's
 * contentDocument, the iframe's ownerDocument will be denied
 * permission to access it (even if otherwise it *would* have
 * permission). So for IE we use contentEditable, and for Mozilla
 * designMode.
 * @param {HTMLDocument}	doc
 * @type void
 */
Util.Document.make_editable = function make_editable(doc)
{
	try {
		// Internet Explorer
		doc.body.contentEditable = true;
		// If the document isn't editable, this will throw an
		// error. If the document is editable, this is perfectly
		// harmless.
		doc.queryCommandState('Bold');
	} catch (e) {
		// Gecko (et al?)
		try {
			// Turn on design mode.  N.B.: designMode has to be
			// set after the iframe_elem's src is set (or its
			// document is closed). ... Otherwise the designMode
			// attribute will be reset to "off", and things like
			// execCommand won't work (though, due to Mozilla bug
			// #198155, the iframe's new document will be
			// editable)
			doc.designMode = 'on';
			doc.execCommand('undo', false, null);
			
			try {
				doc.execCommand('useCSS', false, true);
			} catch (no_use_css) {}
		} catch (f) {
			throw new Error('Unable to make the document editable. ' +
				'(' + e + '); (' + f + ')');
		}
	}
}

/**
 * Creates a new range on the document.
 * @param {Document}  doc   document on which the range will be created
 * @return {Range} the new range
 */
Util.Document.create_range = function create_range_on_document(doc)
{
	if (doc.createRange) {
		return doc.createRange();
	} else if (doc.body.createTextRange) {
		return doc.body.createTextRange();
	} else {
		throw new Util.Unsupported_Error('creating a range on a document');
	}
}

/**
 * Gets the HEAD element of a document.
 * @param	doc		document from which to obtain the HEAD
 */
Util.Document.get_head = function get_document_head(doc)
{
	try {
		return doc.getElementsByTagName('HEAD')[0];
	} catch (e) {
		return null;
	}
}

/**
 * Imitates W3CDOM Document.importNode, which IE doesn't
 * implement. See spec for more details.
 *
 * @param	new_document	the document to import the node to
 * @param	node			the node to import
 * @param	deep			boolean indicating whether to import child
 *							nodes
 */
Util.Document.import_node = function import_node(new_document, node, deep)
{
	if (new_document.importNode) {
		return new_document.importNode(node, deep);
	} else {
		var handlers = {
			// element nodes
			1: function import_element() {
				var new_node = new_document.createElement(node.nodeName);
				
				if (node.attributes && node.attributes.length > 0) {
					for (var i = 0, len = node.attributes.length; i < len; i++) {
						var a = node.attributes[i];
						if (a.specified)
							new_node.setAttribute(a.name, a.value);
					}
				}
				
				if (deep) {
					for (var i = 0, len = node.childNodes.length; i < len; i++) {
						new_node.appendChild(Util.Document.import_node(new_document, node.childNodes[i], true));
					}
				}
				
				return new_node;
			},
			
			// attribute nodes
			2: function import_attribute() {
				var new_node = new_document.createAttribute(node.name);
				new_node.value = node.value;
				return new_node;
			},
			
			// text nodes
			3: function import_text() {
				return new_document.createTextNode(node.nodeValue);
			}
		};
		
		if (typeof(handlers[node.nodeType]) == 'undefined')
			throw new Error("Workaround cannot handle the given node's type.");
		
		return handlers[node.nodeType]();
	}
};

/**
 * Append the style sheet at the given location to the head of the
 * given document
 *
 * @param	location	the location of the stylesheet to add
 * @static
 */
Util.Document.append_style_sheet = function append_style_sheet(doc, location)
{
	var head = Util.Document.get_head(doc);
	return head.appendChild(Util.Document.create_element(doc, 'LINK',
		{href: location, rel: 'stylesheet', type: 'text/css'}));
};

/**
 * Gets position/dimensions information of a document.
 * @return {object} an object describing the document's dimensions
 */
Util.Document.get_dimensions = function get_document_dimensions(doc)
{
	return {
		client: {
			width: doc.documentElement.clientWidth || doc.body.clientWidth,
			height: doc.documentElement.clientHeight || doc.body.clientHeight
		},
		
		offset: {
			width: doc.documentElement.offsetWidth || doc.body.offsetWidth,
			height: doc.documentElement.offsetHeight || doc.body.offsetHeight
		},
		
		scroll: {
			width: doc.documentElement.scrollWidth || doc.body.scrollWidth,
			height: doc.documentElement.scrollHeight || doc.body.scrollHeight,
			left: doc.documentElement.scrollLeft || doc.body.scrollLeft,
			top: doc.documentElement.scrollTop || doc.body.scrollTop
		}
	};
}

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

// file Util.Fieldset.js
/**
 * Creates a chunk containing a fieldset.
 * @constructor
 *
 * @param	params	an object with the following properties:
 *                  <ul>
 *                  <li>document - the DOM document object which will own the created DOM elements</li>
 *                  <li>legend - the desired legend text of the radio</li>
 *                  <li>id - (optional) the id of the DOM fieldset element</li>
 *                  </ul>
 *
 * @class Represents a radio button. Once instantiated, a Radio object
 * has the following properties:
 * <ul>
 * <li>all of the properties given to the constructor in <code>params</code></li>
 * <li>fieldset_elem - the DOM fieldset element. Use this when you want to get at the fieldset element qua fieldset element.</li>
 * <li>legend_elem - the DOM legend element</li>
 * <li>chunk - another reference to the DOM fieldset element. Use this when you want to get at the fieldset element qua chunk, e.g. to append the whole fieldset chunk.</li>
 * </ul>
 */
Util.Fieldset = function(params)
{
	this.document = params.document;
	this.legend = params.legend;
	this.id = params.id;

	// Create fieldset element
	this.fieldset_elem = this.document.createElement('DIV');
	Util.Element.add_class(this.fieldset_elem, 'fieldset');
	if ( this.id != null )
		this.fieldset_elem.setAttribute('id', this.id);

	// Create legend elem
	this.legend_elem = this.document.createElement('DIV');
	Util.Element.add_class(this.legend_elem, 'legend');
	this.legend_elem.appendChild( this.document.createTextNode( this.legend ) );

	// Append legend to fieldset
	this.fieldset_elem.appendChild(this.legend_elem);

	// Create "chunk"
	this.chunk = this.fieldset_elem;


	// Methods

	/**
	 * Sets this fieldset's legend.
	 *
	 * @param	value	the new value
	 */
	this.set_legend = function(value)
	{
		Util.Node.remove_child_nodes( this.legend_elem );
		this.legend_elem.appendChild( this.document.createTextNode(value) );
	};
};

// file Util.Fix_Keys.js
Util.Fix_Keys = function()
{
};

Util.Fix_Keys.NO_MERGE = /^(BODY|HEAD|TABLE|TBODY|THEAD|TR|TH|TD)$/;
Util.Fix_Keys.fix_delete_and_backspace = function(e, win)
{
	function is_not_at_end_of_body(rng)
	{
		var start_container = rng.startContainer;
		var start_offset = rng.startOffset;
		var rng2 = Util.Range.create_range(sel);
		rng2.selectNodeContents(start_container.ownerDocument.getElementsByTagName('BODY')[0]);
		rng2.setStart(start_container, start_offset);
		var ret = rng2.toString().length > 0;// != '';
		return ret;
	}

	function is_not_at_beg_of_body(rng)
	{
		var start_container = rng.startContainer;
		var start_offset = rng.startOffset;
		var rng2 = Util.Range.create_range(sel);
		rng2.selectNodeContents(start_container.ownerDocument.getElementsByTagName('BODY')[0]);
		rng2.setEnd(start_container, start_offset);
		var ret = rng2.toString().length > 0;// != '';
		return ret;
	}

	function move_selection_to_end(node, sel)
	{
		var rightmost = Util.Node.get_rightmost_descendent(node);
		Util.Selection.select_node(sel, rightmost);
		Util.Selection.collapse(sel, false); // to end
	}

	function remove_trailing_br(node)
	{
		if ( node.lastChild != null && 
			 node.lastChild.nodeType == Util.Node.ELEMENT_NODE && 
			 node.lastChild.tagName == 'BR' )
		{
			node.removeChild(node.lastChild);
		}
	}
	
	
	function merge_blocks(one, two)
	{
		while (two.firstChild)
			one.appendChild(two.firstChild);
		two.parentNode.removeChild(two);
	}
	
	/*
	 * If the node is a special Loki container (e.g. for a horizontal rule),
	 * we shouldn't merge with it.
	 */
	function is_container(node)
	{
		return (node && node.nodeType == Util.Node.ELEMENT_NODE &&
			node.getAttribute('loki:container'));
	}
	
	function is_empty_block(node)
	{
		return (Util.Node.is_block_level_element(node) && 
			Util.Node.is_basically_empty(node));
	}
	
	function is_unmergable(node)
	{
		return (is_container(node) ||
			is_empty_block(node) || 
			Util.Element.empty_tag(node));
	}

	function do_merge(one, two, sel)
	{
		function handle_unmergable(node)
		{
			if (is_unmergable(node)) {
				node.parentNode.removeChild(node);
				return true;
			}
			
			return false;
		}
		
		var tags = Util.Fix_Keys.NO_MERGE;
		if (!one || !two || one.nodeName.match(tags) || two.nodeName.match(tags)) {
			return;
		} else if (handle_unmergable(one) || handle_unmergable(two)) {
			return;
		} else {
			remove_trailing_br(one);
			move_selection_to_end(one, sel);
			merge_blocks(one, two);
			e.preventDefault();
		}
	}
	
	function remove_container(container)
	{
		container.parentNode.removeChild(container);
		e.preventDefault();
	}
	
	function remove_if_container(node)
	{
		if (is_container(node))
			remove_container(node);
	}

	var sel = Util.Selection.get_selection(win);
	var rng = Util.Range.create_range(sel);
	var cur_block;
	try {
	    cur_block = Util.Range.get_nearest_bl_ancestor_element(rng);
	} catch (e) {
	    cur_nlock = null;
	}
	
	function get_neighbor_element(direction)
	{
		if (rng.startContainer != rng.endContainer || rng.startOffset != rng.endOffset)
			return null;
		
		if (rng.startContainer.nodeType == Util.Node.TEXT_NODE) {
		    if (direction == Util.Node.NEXT) {
		        if (rng.endOffset == rng.endContainer.nodeValue.length)
		            return rng.endContainer.nextSibling;
		    } else if (direction == Util.Node.PREVIOUS) {
		        if (rng.startOffset == 0)
		            return rng.startContainer.previousSibling;
		    }
		    
		    // If we're in the middle of a text node; well, how did we reach
		    // this code?
		    return null;
		}
		
		if (direction == Util.Node.NEXT && rng.endContainer.childNodes[rng.endOffset])
			return rng.endContainer.childNodes[rng.endOffset];
		else if (direction == Util.Node.PREVIOUS && rng.startContainer.childNodes[rng.startOffset - 1])
			return rng.startContainer.childNodes[rng.startOffset - 1];
		else
			return null;
	}
	
	function is_named_anchor(element) {
	    return (element && element.tagName == 'A' && element.name &&
	        !Util.Node.get_last_non_whitespace_child_node(element));
	}
	
	function remove_anchor(anchor) {
	    var id = anchor.id, sibling = anchor.previousSibling, i, images;
	    
	    function is_marker(node) {
	        return (node.nodeName == 'IMG' &&
	            node.getAttribute('loki:anchor.id') == id);
	    }
	    
	    if (is_marker(sibling)) {
	        // easy case: marker is in its original position, we avoid a DOM
	        // search
	        sibling.parentNode.removeChild(sibling);
	    } else {
	        images = anchor.ownerDocument.getElementsByTagName('IMG');
	        for (i = 0; i < images.length; i++) {
	            if (is_marker(images[i])) {
	                images[i].parentNode.removeChild(images[i]);
	                break;
	            }
	        }
	    }
	    
	    anchor.parentNode.removeChild(anchor);
	}

	if ( rng.collapsed == true && !e.shiftKey )
	{
		var neighbor = null;
		
		if (e.keyCode == e.DOM_VK_DELETE) {
		    neighbor = get_neighbor_element(Util.Node.NEXT);
		    if (is_named_anchor(neighbor)) {
		        remove_anchor(neighbor);
		    } else if (cur_block && Util.Range.is_at_end_of_block(rng, cur_block)) {
				do_merge(cur_block, Util.Node.next_element_sibling(cur_block), sel);
			} else if (Util.Range.is_at_end_of_text(rng) && is_container(rng.endContainer.nextSibling)) {
				remove_container(rng.endContainer.nextSibling);
			} else if (neighbor) {
				remove_if_container(neighbor);
			}
		} else if (e.keyCode == e.DOM_VK_BACK_SPACE) {
		    neighbor = get_neighbor_element(Util.Node.PREVIOUS);
		    
			if (is_named_anchor(neighbor)) {
		        remove_anchor(neighbor);
			} else if (cur_block && Util.Range.is_at_beg_of_block(rng, cur_block) && rng.isPointInRange(rng.startContainer, 0)) {
				// Both the above range tests are necessary to avoid
    			// merge on B's here: <p>s<b>|a</b>h</p>
				do_merge(Util.Node.previous_element_sibling(cur_block), cur_block, sel);
			} else if (Util.Range.is_at_beg_of_text(rng) && is_container(rng.startContainer.previousSibling)) {
				remove_container(rng.endContainer.nextSibling);
			} else if (neighbor) {
				remove_if_container(neighbor);
			}
		}
	}

	return;
	//mb('rng.startContainer, rng.startContainer.parentNode.lastChild, rng.startContainer.parentNode.firstChild, rng.startOffset, rng.startContainer.length, sel.anchorNode, sel.anchorOffset, sel.focusNode, sel.focusOffset, rng, sel', [rng.startContainer, rng.startContainer.parentNode.lastChild, rng.startContainer.parentNode.firstChild, rng.startOffset, rng.startContainer.length, sel.anchorNode, sel.anchorOffset, sel.focusNode, sel.focusOffset, rng, sel]);
};

Util.Fix_Keys.fix_enter_ie = function(e, win, loki)
{
	// Do nothing if enter not pressed
	if (!( !e.shiftKey && e.keyCode == 13 ))
		return true;

	var sel = Util.Selection.get_selection(win);
	var rng = Util.Range.create_range(sel);
	var cur_block = Util.Range.get_nearest_bl_ancestor_element(rng);

	if ( cur_block && cur_block.nodeName == 'PRE' )
	{
		var br_helper = (new UI.BR_Helper).init(loki);
		br_helper.insert_br();
		return false; // prevent default
	}

	// else
	return true; // don't prevent default
};

// file Util.Form.js
/**
 * @constructor
 *
 * @class Form generation without fuss and with validation.
 * @author Eric Naeseth
 */
Util.Form = function(document, params)
{
	var dh = new Util.Document(document); // document helper
	
	this.document = document;
	this._dh = dh;
	this.name = params.name || '(untitled form)';
	this.form_element = params.form || dh.create_element('form',
		{method: params.method || 'POST',
		action: params.action || 'about:blank',
		className: 'generated'});
	this.section_heading_level = params.section_heading_level || 'H3';
	this.live_validation = true;
	
	this.sections = [];
	this.active_section = null;
	
	this.toString = function()
	{
		return '[object Util.Form name=' + this.name +
			', form_element=' + this.form_element + ']';
	}
	
	/**
	 * Constructs and returns a new form section.
	 * Form elements cannot be added directly to the form, but must be added
	 * to sections. The name parameter is optional, so to simulate a form with
	 * no sectional organization, create one single nameless section and add
	 * the fields to it.
	 */
	this.add_section = function(name)
	{
		if (arguments.length == 0)
			var name = null;
		
		var s = new Util.Form.FormSection(this, name);
		this.sections.push(s);
		this.active_section = s;
		s.append(document, dh);
		
		return s;
	}
}

/**
 * @constructor
 * @class Base class for form sections and compound form fields.
 */
Util.Form.FormElementContainer = function(form)
{
	this.new_container = Util.Function.unimplemented;
	this.fields = [];
	
	this.add_field = function(field)
	{
		var container = this.new_container(form, form.document, form._dh);
		field.append(form, form.document, form._dh, container);
		this.fields.push(field);
		return field;
	}
	
	// convenience methods
	
	this.add_text_field = function(name, params)
	{
		if (!params) var params = {};
		
		return this.add_field(new Util.Form.TextField(name,
			params.exposition || null, params));
	}
	
	this.add_blurb_field = function(name, params)
	{
		if (!params) var params = {};
		
		return this.add_field(new Util.Form.BlurbField(name,
			params.exposition || null, params));
	}
	
	this.add_select_field = function(name, values, params)
	{
		if (!params) var params = {};
		
		return this.add_field(new Util.Form.SelectField(name,
			params.exposition || null, params, values));
	}
	
	this.add_instructions = function(text)
	{
		if (!params) var params = {};
	
		return this.add_field(new Util.Form.Instructions(text));
	}
}

/**
 * @constructor
 * @class A section of a form.
 */
Util.Form.FormSection = function(form, name)
{
	Util.OOP.inherits(this, Util.Form.FormElementContainer, form);
	
	this.name = (arguments.length < 2)
		? null
		: name;
	var list = null;
	
	this.append = function(doc, dh)
	{
		var fe = form.form_element;
		
		if (this.name) {
			fe.appendChild(dh.create_element(form.section_heading_level,
				{className: 'section_heading'}, [this.name]));
		}
		
		list = dh.create_element('ul', {className: 'form_section'});
		fe.appendChild(list);
	}
	
	this.new_container = function(form, doc, dh)
	{
		var litem = dh.create_element('li');
		list.appendChild(litem);
		return litem;
	}
	
	this.add_compound_field = function()
	{
		return this.add_field(new Util.Form.CompoundField(form));
	}
}

/**
 * @constructor
 * @class A field on a form.
 */
Util.Form.FormField = function(name, exposition, validator)
{
	this.name = name || null;
	this.exposition = exposition || null;
	this.validate = validator || Util.Function.empty;
	this.element = null;
	
	this.append = function(form, doc, dh, target)
	{
		if (this.name) {
			target.appendChild(dh.create_element('label',
				{className: 'description'}, [this.name]));
		}
		
		if (this.exposition) {
			target.appendChild(dh.create_element('p',
				{className: 'exposition'}, [this.exposition]));
		}
		
		this.element = this.create_element(doc, dh);
		target.appendChild(this.element);
	}
	
	this.get_field_name = function() {
		if (arguments.length > 0) {
			var name = arguments[0];
			if (typeof(name) == 'object' && typeof(name.name) == 'string')
				return name.name;
		}
		
		if (typeof(this.name) != 'string') {
			throw new Error('No pretty name for this field is defined.');
		}
		
		return this.name.replace(/\W+/, '_').toLowerCase();
	}
	
	this._apply_validation = function(element) {
		var field = this;
		Util.Event.add_event_listener(element, 'change', function(e) {
			field.validate.call(this, e || window.event);
		})
		return element;
	}
	
	this.create_element = Util.Function.unimplemented;
}

Util.Form.TextField = function(name, exposition, params)
{
	Util.OOP.inherits(this, Util.Form.FormField, name, exposition, params.validator);
	
	this.create_element = function(doc, dh)
	{
		return this._apply_validation(dh.create_element('input', {
			type: 'text',
			name: this.get_field_name(params || {}),
			value: params.value || '',
			size: params.size || 20
		}));
	}
}

Util.Form.BlurbField = function(name, exposition, params)
{
	Util.OOP.inherits(this, Util.Form.FormField, name, exposition, params.validator);
	
	this.create_element = function(doc, dh)
	{
		return this._apply_validation(dh.create_element('textarea', {
			name: this.get_field_name(params || {}),
			cols: params.cols || 60,
			rows: params.rows || 5},
			[params.value || '']
		));
	}
}

Util.Form.SelectField = function(name, exposition, params, values)
{
	Util.OOP.inherits(this, Util.Form.FormField, name, exposition, params.validator);
	
	this.create_element = function(doc, dh)
	{
		var options = [];
		for (var i = 0; i < values.length; i++) {
			var v = values[i];
			var option = dh.create_element('option',
				{value: v.value, selected: (v.selected || false)});
			option.innerHTML = v.text;
			options.push(option);
		}
		
		return this._apply_validation(dh.create_element('select', 
			{name: this.get_field_name(params || {}),
			size: params.size || 1},
			options
		));
	}
}

Util.Form.CompoundField = function(form)
{
	Util.OOP.inherits(this, Util.Form.FormElementContainer, form);
	
	var container = null;
	var line_break = null;
	
	this.append = function(form, doc, dh, target)
	{
		container = target;
		line_break = dh.create_element('br', {className: 'compound_end'});
		container.appendChild(line_break);
	}
	
	this.new_container = function(form, doc, dh)
	{
		var item = dh.create_element('span');
		container.insertBefore(item, line_break);
		return item;
	}
	
	this.validate = function()
	{
		for (var i = 0; i < this.fields.length; i++) {
			this.fields[i].validate();
		}
	}
}

Util.Form.Instructions = function(text)
{
	Util.OOP.inherits(this, Util.Form.FormField);
	
	this.create_element = function(doc, dh)
	{
		return dh.create_element('p', {className: 'instructions'},
			[text]);
	}
} 
// file Util.HTML_Generator.js
/**
 * Constructs a new HTML generator.
 * @class Generates nicely-formatted HTML by traversing the DOM.
 * @param {Object} [options] generation options
 * @param {Boolean} [options.xhtml=true] generate XHTML output
 * @param {Boolean} [options.escape_non_ascii=true]
 * @param {Boolean} [options.indent_text="\t"]
 */
Util.HTML_Generator = function HTMLGenerator(options) {
	if (!options)
		options = {};
	this.xhtml = options.xhtml || true;
	this.escape_non_ascii = options.escape_non_ascii || true;
	this.indent_text = options.indent_text || "\t";
};

/**
 * Generates HTML.
 * @param {Node|Node[]} nodes
 * @return {String} the formatted source
 */
Util.HTML_Generator.prototype.generate = function generate_html(nodes) {
	var gen = this;
	var pattern = (gen.escape_non_ascii)
		? (/[\x00-\x1F\x80-\uFFFF&<>"]/g)
		: (/[\x00-\x1F&<>"]/g);
	
	function is_relevant(node) {
		if (!node)
			return false;
		return (node.nodeType == Util.Node.ELEMENT_NODE || 
			node.nodeType == Util.Node.TEXT_NODE &&
			/\S/.test(node.nodeValue));
	}
	
	function clean_text(text, in_attribute) {
		function html_escape(txt) {
			var c = txt.charCodeAt(0);
			if (c == 9 || c == 10 || c == 13)
				return txt;
			if (c == 34 && !in_attribute) // don't do " -> &quot; unless in attr
				return txt;
			var entity = Util.HTML_Generator.named_entities[c];
			return (typeof(entity) == "string")
				? '&' + entity + ';'
				: '&#' + c + ';'
		}
		
		return (text) ? text.replace(pattern, html_escape) : '';
	}
	
	function is_whitespace_irrelevant(node) {
		var parent = node.parentNode;
		var parent_is_block = Util.Block.is_block(parent);
		var results = [false, false];
		
		if (parent_is_block) {
			if (node == node.parentNode.firstChild)
				results[0] = true;
			if (node == node.parentNode.lastChild)
				results[1] = true;
			
			if (results[0] && results[1]) {
				return results;
			}
		}
		
		if (node.previousSibling && Util.Block.is_block(node.previousSibling))
			results[0] = true;
		if (node.nextSibling && Util.Block.is_block(node.nextSibling))
			results[1] = true;
		
		return results;
	}
	
	function make_text(buffer, text_node) {
		if (!Util.Node.is_text(text_node))
			throw new TypeError();
		
		var text = text_node.nodeValue, orig_text = text, irw;
		
		if (!buffer.flagged("preformatted")) {
			if (text_node == text_node.parentNode.firstChild)
				text = text.replace(/^[\t\r\n]+/g, '');
			if (text_node == text_node.parentNode.lastChild)
				text = text.replace(/[\t\r\n]+$/g, '');
			text = text.replace(/(\S)[\r\n]+(\S)/g, "$1 $2");
			text = text.replace(/(\s)[\r\n]+|[\r\n]+(\s)/g, "$1$2");
			text = text.replace(/[ ][ ]+/g, ' ');
			
			irw = is_whitespace_irrelevant(text_node);
			if (irw[0])
				text = text.replace(/^[\s\n]+/, '');
			if (irw[1])
				text = text.replace(/[\s\n]+$/, '');
		}
		
		text = clean_text(text);
		if (text.length > 0)
			buffer.write(text);
	}
	
	function make_comment(buffer, comment_node) {
		if (comment_node.nodeType != Util.Node.COMMENT_NODE)
			throw new TypeError();
		
		buffer.write('<!--' + clean_text(comment_node.nodeValue) + '-->');
	}
	
	function make_processing_instruction(buffer, pi_node) {
		if (pi_node.nodeType != Util.Node.PROCESSING_INSTRUCTION_NODE)
			throw new TypeError();
		
		buffer.write('<?' + pi_node.target + ' ' + pi_node.data + '?>');
	}
	
	function make_open_tag(buffer, element, xml_self_close) {
		if (!Util.Node.is_element(element))
			throw new TypeError();
			
		buffer.write('<', element.nodeName.toLowerCase());
		
		Util.Object.enumerate(Util.Element.get_attributes(element, true),
			function append_attr(name, value) {
				if (name.charAt(0) == "_")
					return;
				buffer.write(' ', name, '="', clean_text(value, true), '"');
			}
		);
		
		buffer.write((xml_self_close) ? ' />' : '>');
	}
	
	function make_close_tag(buffer, element) {
		if (!Util.Node.is_element(element))
			throw new TypeError();
			
		buffer.write('</' + element.nodeName.toLowerCase() + '>');
	}
	
	function make_empty_element(buffer, element) {
		if (!Util.Node.is_element(element))
			throw new TypeError();
		
		make_open_tag(buffer, element, gen.xhtml);
		if (element.nodeName == "PARAM")
			buffer.end_line();
	}
	
	function make_inline_element(buffer, element) {
		if (!Util.Node.is_element(element))
			throw new TypeError();
			
		make_open_tag(buffer, element);
		make_nodes(buffer, element.childNodes);
		make_close_tag(buffer, element);
	}
	
	function is_indented_block(element) {
		if (!Util.Block.is_block(element))
			return false;
		
		function is_block(node) {
			return Util.Block.is_block(node);
		}
		
		return (Util.Node.find_children(element, is_block).length > 0);
	}
	
	function make_block_element(buffer, element) {
		if (!Util.Node.is_element(element))
			throw new TypeError();
		
		if (!element.hasChildNodes() || buffer.flagged("preformatted")) {
			make_inline_element(buffer, element);
			return;
		}
		
		if (buffer.flagged('after_indented_block')) {
			buffer.end_line();
		}
		
		var block_children = is_indented_block(element);
		var child_buffer;
		
		buffer.end_line(true);
		make_open_tag(buffer, element);
		
		if (block_children) {
			child_buffer = buffer.spawn();
			make_nodes(child_buffer, element.childNodes);
			child_buffer.close();
			buffer.end_line(true);
		} else {
			make_nodes(buffer, element.childNodes);
		}
		
		make_close_tag(buffer, element);
		buffer.end_line();
		if (block_children)
			buffer.set_flag('after_indented_block', 'write');
	}
	
	function make_pre_element(buffer, element) {
		if (!Util.Node.is_element(element))
			throw new TypeError();
			
		buffer.set_flag('preformatted');
		make_inline_element(buffer, element);
		buffer.end_line(true);
		buffer.clear_flag('preformatted');
	}
	
	function make_element(buffer, element) {
		if (!Util.Node.is_element(element)) {
			throw new TypeError("Tried to make a non-element as an element: " +
				element);
		}
		
		if (is_relevant(element.previousSibling) && is_indented_block(element)) {
			if (!buffer.flagged('after_indented_block')) {
				buffer.end_line();
			}
		}
			
		if (Util.Node.is_tag(element, 'PRE'))
			return make_pre_element(buffer, element);
		else if (!element.hasChildNodes() && Util.Element.empty_tag(element))
			return make_empty_element(buffer, element);
		else if (Util.Block.is_block(element))
			return make_block_element(buffer, element);
		else
			return make_inline_element(buffer, element);
	}
	
	function make_node(buffer, node) {
		if (!Util.is_number(node.nodeType))
			throw new TypeError();
		
		switch (node.nodeType) {
			case Util.Node.TEXT_NODE:
				return make_text(buffer, node);
			case Util.Node.COMMENT_NODE:
				return make_comment(buffer, node);
			case Util.Node.PROCESSING_INSTRUCTION_NODE:
				return make_processing_instruction(buffer, node);
			case Util.Node.ELEMENT_NODE:
				return make_element(buffer, node);
			case Util.Node.DOCUMENT_NODE:
				return make_element(buffer, node.documentElement);
			default:
				return '';
		}
	}
	
	function make_nodes(buffer, nodes) {
		if (!Util.is_enumerable(nodes))
			throw new TypeError();
		
		for (var i = 0; i < nodes.length; i++) {
			make_node(buffer, nodes[i]);
		}
	}
	
	var buffer = new Util.HTML_Generator.Buffer(null, this.indent_text);
	if (!Util.is_enumerable(nodes))
		nodes = [nodes];
	make_nodes(buffer, nodes);
	return buffer.close().read();
};

Util.HTML_Generator.Buffer = function Buffer(parent, indent_text)
{
	this.parent = parent || null;
	this.depth = (parent) ? parent.depth + 1 : 0;
	this.lines = [];
	this.current_line = [];
	this.indent_text = indent_text || (parent && parent.indent_text) || "\t";
	this.closed = false;
	this.active_child = null;
	this.flags = {
		'manual': {},
		'write': {},
		'flush': {}
	};
	
	if (parent)
		parent.active_child = this;
}

Util.OOP.mixin(Util.HTML_Generator.Buffer.prototype, {
	flags: null,
	
	_verify_open: function _verify_buffer_is_open() {
		if (this.closed) {
			throw new Error("Buffer is closed!");
		} else if (this.active_child) {
			throw new Error("A child buffer is active!");
		}
	},
	
	_gen_indent: function _buffer_generate_indentation() {
		var indent = new Array(this.depth);
		for (var i = 0; i < this.depth; i++)
			indent[i] = this.indent_text;
		return indent.join('');
	},
	
	spawn: function spawn_child_buffer() {
		this.flush();
		return new Util.HTML_Generator.Buffer(this);
	},
	
	set_flag: function set_buffer_flag(name, cancellation, value) {
		if (cancellation)
			cancellation = cancellation.toLowerCase();
		else
			cancellation = 'manual';
		
		if (typeof(name) != 'string') {
			throw new Error('Illegal buffer flag name "' + name + '".');
		} else if (!cancellation in this.flags) {
			throw new Error('Unknown flag cancellation "' + cancellation +
				'".');
		}
		
		this.clear_flag(name);
		this.flags[cancellation][name] = value || true;
		return this;
	},
	
	get_flag: function get_buffer_flag(name) {
		for (var c in this.flags) {
			var value = this.flags[c][name];
			if (typeof(value) != 'undefined')
				return value;
		}
		
		return undefined;
	},
	
	clear_flag: function clear_buffer_flag(name) {
		for (var c in this.flags) {
			delete this.flags[c][name];
		}
	},
	
	flagged: function is_buffer_flagged(name) {
		return typeof(this.get_flag(name)) != 'undefined';
	},
	
	write: function write_to_buffer(text) {
		var i, arg;
		
		this._verify_open();
		
		for (var flag_name in this.flags.write)
			delete this.flags.write[flag_name];
		
		for (i = 0; i < arguments.length; i++) {
			arg = String(arguments[i]);
			if (arg.length > 0)
				this.current_line.push(arg);
		}
		
		return this;
	},
	
	flush: function flush_buffer(always_flush) {
		var line;
		
		this._verify_open();
		
		for (var flag_name in this.flags.flush)
			delete this.flags.flush[flag_name];
		
		if (this.current_line.length == 0 && !always_flush) {
			return this;
		}
		
		line = this._gen_indent() + this.current_line.join('');
		this.lines.push(line);
		this.current_line = [];
		return this;
	},
	
	end_line: function buffer_end_line(only_if_content) {
		return this.flush(!only_if_content);
	},
	
	close: function close_buffer() {
		this.flush(); // calls _verify_open
		this.closed = true;
		if (this.parent) {
			if (this.parent.closed) // should never happen, but be safe
				throw new Error("Parent buffer is closed!");
			this.parent.lines.append(this.lines);
			this.parent.active_child = null;
		}
		return this;
	},
	
	read: function read_buffer() {
		if (!this.closed) {
			throw new Error("Cannot read buffer contents: buffer still open.");
		}
		return this.lines.join("\n");
	}
});

Util.HTML_Generator.named_entities = {
	'34': 'quot', '38': 'amp', '60': 'lt', '62': 'gt', '127': '#127',
	'160': 'nbsp', '161': 'iexcl', '162': 'cent', '163': 'pound', '164':
	'curren', '165': 'yen', '166': 'brvbar', '167': 'sect', '168': 'uml', '169':
	'copy', '170': 'ordf', '171': 'laquo', '172': 'not', '173': 'shy', '174':
	'reg', '175': 'macr', '176': 'deg', '177': 'plusmn', '178': 'sup2', '179':
	'sup3', '180': 'acute', '181': 'micro', '182': 'para', '183': 'middot',
	'184': 'cedil', '185': 'sup1', '186': 'ordm', '187': 'raquo', '188':
	'frac14', '189': 'frac12', '190': 'frac34', '191': 'iquest', '192':
	'Agrave', '193': 'Aacute', '194': 'Acirc', '195': 'Atilde', '196': 'Auml',
	'197': 'Aring', '198': 'AElig', '199': 'Ccedil', '200': 'Egrave', '201':
	'Eacute', '202': 'Ecirc', '203': 'Euml', '204': 'Igrave', '205': 'Iacute',
	'206': 'Icirc', '207': 'Iuml', '208': 'ETH', '209': 'Ntilde', '210':
	'Ograve', '211': 'Oacute', '212': 'Ocirc', '213': 'Otilde', '214': 'Ouml',
	'215': 'times', '216': 'Oslash', '217': 'Ugrave', '218': 'Uacute', '219':
	'Ucirc', '220': 'Uuml', '221': 'Yacute', '222': 'THORN', '223': 'szlig',
	'224': 'agrave', '225': 'aacute', '226': 'acirc', '227': 'atilde', '228':
	'auml', '229': 'aring', '230': 'aelig', '231': 'ccedil', '232': 'egrave',
	'233': 'eacute', '234': 'ecirc', '235': 'euml', '236': 'igrave', '237':
	'iacute', '238': 'icirc', '239': 'iuml', '240': 'eth', '241': 'ntilde',
	'242': 'ograve', '243': 'oacute', '244': 'ocirc', '245': 'otilde', '246':
	'ouml', '247': 'divide', '248': 'oslash', '249': 'ugrave', '250': 'uacute',
	'251': 'ucirc', '252': 'uuml', '253': 'yacute', '254': 'thorn', '255':
	'yuml', '8364': 'euro'
};

// file Util.HTML_Parser.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A SAX-style tolerant HTML parser that doesn't rely on the browser.
 * @author Eric Naeseth
 */
Util.HTML_Parser = function SAX_HTML_Parser()
{
	var data = null;
	var parsing = false;
	var halted = false;
	var position = 0;
	var listeners = {
		open: [],
		close: [],
		text: [],
		cdata: [],
		comment: []
	};
	
	var self_closing_tags = Util.HTML_Parser.self_closing_tags.toSet();
	
	// -- Public Methods --
	
	this.add_listener = function add_html_parse_listener(type, func)
	{
		listeners[type.toLowerCase()].push(func);
	}
	
	// consistency
	this.add_event_listener = this.add_listener;
	
	this.parse = function parse_html(text)
	{
		data = text;
		position = 0;
		var state = starting_state;
		var len = data.length;
		
		parsing = true;
		halted = false;
		do {
			state = state();
		} while (state && position < len && !halted);
		parsing = halted = false;
	}
	
	this.halt = function halt_html_parser()
	{
		if (!parsing)
			return false;
		return (halted = true);
	}
	
	// -- Parsing Functions --
	
	function unscan_character()
	{
		position--;
	}
	
	function unscan_characters(number)
	{
		position -= number;
	}
	
	function ignore_character()
	{
		position++;
	}
	
	function ignore_characters(number)
	{
		position += number;
	}
	
	function scan_character()
	{
		return (position < data.length)
			? data.charAt(position++)
			: null;
	}
	
	function expect(s)
	{
		var len = s.length;
		if (position + len < data.length && data.indexOf(s, position) == position) {
			position += len;
			return true;
		}
		
		return false;
	}
	
	function scan_until_string(s)
	{
		var start = position;
		position = data.indexOf(s, start);
		if (position < 0)
			position = data.length;
		return data.substring(start, position);
	}
	
	function scan_until_characters(list)
	{
		var start = position;
		while (position < data.length && list.indexOf(data.charAt(position)) < 0) {
			position++;
		}
		return data.substring(start, position);
	}
	
	function ignore_whitespace()
	{
		while (position < data.length && " \n\r\t".indexOf(data.charAt(position)) >= 0) {
			position++;
		}
	}
	
	function character_data(data)
	{
		var cdata_listeners = (listeners.cdata.length > 0)
			? listeners.cdata
			: listeners.text;
		
		cdata_listeners.each(function(l) {
			l(data);
		});
	}
	
	function text_data(data)
	{
		listeners.text.each(function(l) {
			l(data);
		});
	}
	
	function comment(contents)
	{
		listeners.comment.each(function(l) {
			l(data);
		});
	}
	
	function tag_opened(name, attributes)
	{
		listeners.open.each(function(l) {
			l(name, attributes);
		});
	}
	
	function tag_closed(name)
	{
		listeners.close.each(function(l) {
			l(name);
		});
	}
	
	// -- State Functions --
	
	function starting_state()
	{
		var cdata = scan_until_string('<');
		if (cdata) {
			text_data(cdata);
		}
		
		ignore_character();
		return tag_state;
	}
	
	function tag_state()
	{
		switch (scan_character()) {
			case '/':
				return closing_tag_state;
			case '?':
				return processing_instruction_state;
			case '!':
				return escape_state;
			default:
				unscan_character();
				return opening_tag_state;
		}
	}
	
	function opening_tag_state()
	{
		function parse_attributes()
		{
			var attrs = {};
			
			do {
				ignore_whitespace();
				var name = scan_until_characters("=/> \n\r\t");
				if (!name)
					break;
				var value = null;
				ignore_whitespace();
				var next_char = scan_character();
				if (next_char == '=') {
					// value provided; figure out what (if any) quoting style
					// is in use
					
					ignore_whitespace();
					var quote = scan_character();
					if ('\'"'.indexOf(quote) >= 0) {
						// it's quoted; find the matching quote
						value = scan_until_string(quote);
						ignore_character(); // skip over the closer
					} else {
						// unquoted; find the end
						unscan_character();
						value = scan_until_characters("/> \n\r\t");
					}
				} else {
					// value implied (e.g. in <option selected>)
					unscan_character();
					value = name;
				}
				
				attrs[name] = value;
			} while (true);
			
			return attrs;
		}
		
		var tag = scan_until_characters("/> \n\r\t");
		if (tag) {
			var attributes = parse_attributes(); // last step ignores whitespace
			tag_opened(tag, attributes);
			
			var next_char = scan_character();
			if (next_char == '/') {
				// self-closing tag (XML-style)
				tag_closed(tag);
				ignore_whitespace();
				next_char = scan_character(); // advance to the "<"
			} else if (tag.toUpperCase() in self_closing_tags) {
				// self-closing tag (known HTML tag)
				tag_closed(tag);
			}
			
			if (next_char != '>') {
				// oh my, what on earth?
				throw new Util.HTML_Parser.Error('Opening tag not terminated ' +
					'by ">".');
			}
		}
		
		return starting_state;
	}
	
	function closing_tag_state()
	{
		var tag = scan_until_characters('/>');
		if (tag) {
			var next_char = scan_character();
			if (next_char == '/') {
				next_char = scan_character();
				if (next_char != '>') {
					// oh my, what on earth?
					throw new Util.HTML_Parser.Error('Closing tag not ' +
						'terminated by ">".');
				}
			}
			
			tag_closed(tag);
		}
		
		return starting_state;
	}
	
	function escape_state()
	{
		var data;
		
		if (expect('--')) {
			// comment
			data = scan_until_string('-->');
			if (data)
				comment(data);
			ignore_characters(2);
		} else if (expect('[CDATA[')) {
			// CDATA section
			data = scan_until_string(']]>');
			if (data)
				character_data(data);
			ignore_characters(2);
		} else {
			scan_until_string('>');
		}
		
		ignore_character();
		return starting_state;
	}
	
	function processing_instruction_state()
	{
		scan_until_string('?>');
		ignore_characters(2);
		
		return starting_state;
	}
}

/**
 * Constructs a new HTML parse error.
 * @class An HTML parse error.
 * @constructor
 * @extends Error
 */
Util.HTML_Parser.Error = function HTML_Parse_Error(message)
{
	Util.OOP.inherits(this, Error, message);
	this.name = 'HTML_Parse_Error';
}

Util.HTML_Parser.self_closing_tags = ['BR', 'AREA', 'LINK', 'IMG', 'PARAM',
	'HR', 'INPUT', 'COL', 'BASE', 'META'];

// file Util.HTML_Reader.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Reads an HTML file and exposes its document, without
 * displaying it.
 *
 * Structure:
 * - These may be called any time
 *   - add_load_listener
 *   - load
 *   - destroy
 * - These must not be accessed till after load
 *   - document
 *
 */
Util.HTML_Reader = function()
{
	/**
	 * @param	document 	(optional) the document. Defaults to global document.
	 * @param 	blank_uri	(optional) the uri to use as a blank uri, which is displayed 
	 * 						for an instant before whatever uri is passed to this.load
	 * 						is displayed. Defaults to about:blank.
	 *						But NOTE: if blank_uri is left as about:blank, when called
	 * 						from a page under https IE will complain about mixing 
	 *						https and http.
	 */
	this.init = function(params)
	{
		if (typeof(params) == 'undefined')
			var params = {};
		
		this._owner_document = params.document == null ? document : params.document;
		this._blank_uri = params.blank_uri == null ? 'about:blank' : params.blank_uri;
		this._load_listeners = new Array();

		return this;
	};

	this.add_load_listener = function(listener)
	{
		this._load_listeners.push(listener);
	};

	this.load = function(uri)
	{
		if ( this._iframe == null )
			this._append_iframe(uri);

		this._iframe.src = uri;
	};

	/**
	 * If you load a large document, you might want to call this when
	 * you're done with it to free up memory.
	 */
	this.destroy = function()
	{
		//this._iframe.parentNode.removeChild(this._iframe);
		this._iframe = null;
		this.window = null; // not sure these are necessary, but it doesn't hurt
		this.document = null;
	};

	this._fire_listeners = function()
	{
		this.window = this._iframe.contentWindow;
		this.document = this.window.document;

		for ( var i = 0; i < this._load_listeners.length; i++ )
			this._load_listeners[i]();
	};

	this._append_iframe = function()
	{
		this._iframe = this._owner_document.createElement('IFRAME');
		//this._iframe.setAttribute('style', 'height:1px; width:1px; display:none;');
/*
		this._iframe.style.height = '2px';
		this._iframe.style.width = '2px';
		this._iframe.style.left = '-500px';
		this._iframe.style.position = 'absolute';
*/
		var self = this;
		this._iframe.onload = function() { self._fire_listeners() };
		this._iframe.onreadystatechange = function() 
		{
			if ( self._iframe.readyState == 'complete' )
				self._fire_listeners();
		};
		mb('this._blank_uri: ', this._blank_uri);
		this._iframe.uri = this._blank_uri;
		this._owner_document.body.appendChild(this._iframe);
	};
};

// file Util.HTTP_Reader.js
Util.HTTP_Reader = function()
{
	this._load_listeners = [];
};

/**
 * Loads http(s) data asynchronously.
 * N.B.: This must be asynchronous, in order to deal with an IE bug
 * involving HTTPS over SSL:
 * <http://support.microsoft.com/kb/272359/en>.
 *   (Not sure this is true for XMLHTTP--but async makes much more
 *   sense usually, anyway, so the app doesn't hang.)
 *
 * See <http://developer.apple.com/internet/webcontent/xmlhttpreq.html>
 * for good overview.
 *
 * The actual XMLHttpRequest object will be available as this.request.
 *
 * XXX: This code is icky! Use Util.Request. -EN
 *
 * @param	uri				The URI to load
 * @param	post_data		(optional) string containing post data
 */
Util.HTTP_Reader.prototype.load = function(uri, post_data)
{
	if (window.XMLHttpRequest)
	{
		this.request = new XMLHttpRequest();
	}
	else
	{
		try
		{
			this.request = new ActiveXObject('Microsoft.XMLHTTP');
		}
		catch(e)
		{
			throw "Util.HTTP_Reader.load: Your browser supports neither the W3C method nor the MS method of reading data over http.";
		}
	}
	
	this._really_add_load_listeners();
	
	if (post_data) {
		this.request.open('POST', uri, true);
		this.request.send(post_data);
	} else {
		this.request.open('GET', uri, true);
		this.request.send();
	}
};

/**
 * Adds an onload listener to the data. The normal
 * add_event_listener cannot be used because IE doesn't have a load
 * event for xml documents, but instead has an onreadystatechange
 * event.
 *
 * @param	listener	a function which will be called when the event is fired, and which receives as a paramater the
 *                      request object
 */
Util.HTTP_Reader.prototype.add_load_listener = function(listener)
{
	this._load_listeners.push(listener);
}

Util.HTTP_Reader.prototype._really_add_load_listeners = function()
{
	var self = this;
	
	this.request.onreadystatechange = function()
	{
		var state = self.request.readyState;
		if (state == 4 || state == 'complete') {
			for (var i = 0; i < self._load_listeners.length; i++) {
				self._load_listeners[i](self.request);
			}
		}
	}
};

// file Util.Head.js
/**
 * Does nothing
 * @constructor
 *
 * @class Contains functions pertaining to head elements.
 */
Util.Head = function()
{
};

/**
 * Append the style sheet at the given location with the given id
 *
 * @param	location	the location of the stylesheet to add
 * @static
 */
Util.Head._append_style_sheet = function(location)
{
	var head_elem = this._dialog_window.document.getElementsByTagName('head').item(0);
	var link_elem = this._dialog_window.document.createElement('link');

	link_elem.setAttribute('href', location);
	link_elem.setAttribute('rel', 'stylesheet');
	link_elem.setAttribute('type', 'text/css');

	head_elem.appendChild(link_elem);
};

// file Util.Iframe.js
/**
 * Declares instance variables. <code>this.iframe</code>,
 * <code>this.window</code> <code>this.document</code>, and
 * <code>this.body</code> are not initialized until the method
 * <code>this.open</code> is called.
 *
 * @constructor
 *
 * @class A wrapper to DOM iframe elements. Provides extra and
 * cross-browser functionality.
 */
Util.Iframe = function()
{
	this.iframe_elem;
	this.content_window;
	this.content_document;
	this.body_elem;
};

/**
 * Creates an iframe element and inits instance variables.
 *
 * @param	doc_obj			the document object with which to create the iframe.
 * @param	uri				(optional) the uri of the page to open in the
 *							iframe. Defaults to about:blank, with the result
 *							that no page is initially opened in the iframe.
 * 							NOTE: if you plan to use this behind https, as
 * 							we do Loki, you must specify a uri, not just 
 * 							about:blank, or IE will pop up an alert about
 * 							combining https and http.
 */
Util.Iframe.prototype.init = function(doc_obj, uri)
{
	// Provide defaults for optional arguments
	if ( uri == null || uri == '' )
		// When under https, this causes an alert in IE about combining https and http (see above):
		uri = 'about:blank';

	// Creates iframe
	this.iframe_elem = doc_obj.createElement('IFRAME');

	// Set source
	this.iframe_elem.src = uri;

	this.iframe_elem.onload = function()
	{

		alert('loaded'); return true;

	// Set up reference to iframe's content document
	this.content_window = Util.Iframe.get_content_window(this.iframe_elem);
	this.content_document = Util.Iframe.get_content_document(this.iframe_elem);

	// If we just want to load about:blank, there's no need for an
	// asynchronous call. 
	//
	// By writing the document's initial HTML out ourself and then
	// closing the document (that's the important part), we
	// essentially make the "src" loading synchronous rather than
	// asynchronous. And if we're just trying to open an empty window,
	// this is not dangerous. (It might be dangerous otherwise, since
	// a synchronous "src" loading that involved a request to the web
	// server might cause the script to effectively hang if the web
	// server didn't respond.)
	//
	// If we are given a URI to request from the web server, we skip
	// this, so the loading "src" is asynchronous, so before we do
	// anything with the window's contents, we need to make sure that
	// the content document has loaded. One way to do this is to add a
	// "load" event listener, and then do everything we want to in the
	// listener. Beware, though: this can cause royal
	// (cross-)browser-fucked pains.
	if ( uri == '' )
	{
		this.content_document.write('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' +
									'<html><head><title></title></head><body>' +
									'</body></html>');
		this.content_document.close();

		// We can only set a reference to the body element if the
		// document has finished loading, and here we can only be sure
		// of that across browsers if we've called document.close().
		//
		// One upshot is that if we are given a URI to load in the
		// iframe, we have to wait until the load event is fired to
		// get a reference to the body tag, and I don't want to muck
		// around with that here. So in that case we just don't get
		// such a reference here. (Notice that the assignment below is
		// still in the if block.) You have to get the reference
		// yourself if you want it.
		this.body_elem = this.content_document.getElementsByTagName('BODY').item(0);
	}

	};
};



Util.Iframe.get_content_window = function(iframe_elem)
{
	return iframe_elem.contentWindow;
};

Util.Iframe.get_content_document = function(iframe_elem)
{
	var content_document;

	if ( iframe_elem.contentDocument != null )
	{
		content_document = iframe_elem.contentDocument;
	}
	else if ( iframe_elem.document != null )
	{
		content_document = iframe_elem.contentWindow.document;
	}
	else
	{
		throw new Error('Util.Iframe.get_content_document: Neither the W3C method of accessing ' +
						'the iframe\'s content document ' +
						'nor a workaround for IE worked.');
	}

	return content_document;
};

// file Util.Image.js
Util.Image = function()
{
};

var image_i = 0;

// Rescales an image such that its width equals the given width, while
// preserving the image's aspect ratio.
Util.Image.set_width = function(img_elem, width)
{
	var ratio = width / img_elem.width;
	img_elem.height = Math.floor( img_elem.height * ratio );
	img_elem.width = width;
};

// Rescales an image such that its height equals the given height,
// while preserving the image's aspect ratio.
Util.Image.set_height = function(img_elem, height)
{
	var ratio = height / img_elem.height;
	img_elem.width = Math.floor( img_elem.width * ratio );
	img_elem.height = height;
};

// Rescales an image to fit within max_width and max_height, while
// preserving the image's aspect ratio.
Util.Image.set_max_size = function(img_elem, max_width, max_height)
{
	// If only the image's width is greater than max_width, rescale
	// based on width
	if ( img_elem.width > max_width && !(img_elem.height > max_height) )
	{
		Util.Image.set_width(img_elem, max_width);
	}
	// If only the image's height is greater than max_height, rescale
	// based on height
	else if ( img_elem.height > max_height && !(img_elem.width > max_width) )
	{
		Util.Image.set_height(img_elem, max_height);
	}
	// If both are greater than their correspondant, ...
	else if ( img_elem.width > max_width && img_elem.height > max_height )
	{
		// If the difference between the image's width and max_width
		// is greater than the difference between the image's height
		// and max_height, rescale based on width
		if ( img_elem.width - max_width > img_elem.height - max_height )
		{
			Util.Image.set_width(img_elem, max_width);
		}
		// Else, rescale based on height
		else
		{
			Util.Image.set_height(img_elem, max_height);
		}
	}
	// Else (if the image's width and height are both less than their
	// correspondants), do nothing
};

// N.B.: I would not offer my life as pledge that this function works.
// (It's never used in Loki as of now, but made sense to write it up
// while writing the above.)
//
// Rescales an image to fit within max_width and max_height, while
// preserving the image's aspect ratio.
Util.Image.set_min_size = function(img_elem, max_width, max_height)
{
	// If only the image's width is less than max_width, rescale
	// based on width
	if ( img_elem.width < max_width && !(img_elem.height < max_height) )
	{
		Util.Image.set_width(img_elem, max_width);
	}
	// If only the image's height is less than max_height, rescale
	// based on height
	else if ( img_elem.height < max_height && !(img_elem.width < max_width) )
	{
		Util.Image.set_height(img_elem, max_height);
	}
	// If both are less than their correspondant, ...
	else if ( img_elem.width < max_width && img_elem.height < max_height )
	{
		// If the difference between the image's width and max_width
		// is greater than the difference between the image's height
		// and max_height, rescale based on width
		if (  max_width - img_elem.width >  max_height - img_elem.height )
		{
			Util.Image.set_width(img_elem, max_width);
		}
		// Else, rescale based on height
		else
		{
			Util.Image.set_height(img_elem, max_height);
		}
	}
	// Else (if the image's width and height are both greater than their
	// correspondants), do nothing
};



// SET MAX SIZE

// If only the image's width is greater than max_width, rescale based on width

// If only the image's height is greater than max_height, rescale based on height

// If both are greater than their correspondant,
//     if ( image's width - max_width > image's height - max_height ), rescale based on width
//     else, rescale based on height

// SET MIN SIZE

// same as for max size, but change "greater" to "less"

// SET SIZE

// If only the image's width is not equal to max_width, rescale based on width.
// If only the image's height is not equal to max_height, rescale based on height.
// If neither is equal to either,
//     if max_width is greater than max_height, rescale based on max_width;
//     else, rescale based on max_height.

// file Util.Input.js
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
// file Util.JSON.js
Util.JSON = (function JSON() {
	var special = {
		'\b': '\\b',
		'\t': '\\t',
		'\n': '\\n',
		'\f': '\\f',
		'\r': '\\r',
		'\\': '\\\\'
	};
	var indent = "    ";
	
	function str_repeat(string, count) {
		return count < 1 ? '' : new Array(count + 1).join(string);
	}
	
	function pad_number(num, length, radix) {
		var string = num.toString(radix || 10);
		return str_repeat('0', length - string.length) + string;
	}
	
	var primitive_dumpers = {
		"number": function json_dump_number(num) {
			return isFinite(num) ? num.toString() : null;
		},
		
		"string": function json_dump_string(s) {
			s = s.replace(/[\x00-\x1f\\]/g, function(c) {
				var character = special[c];
				return special[c] || '\\u00' + pad_number(c.charCodeAt(0), 2, 16);
			});
			return '"' + s.replace(/"/g, '\\"') + '"';
		},
		
		"boolean": function json_dump_boolean(b) {
			return (b) ? "true" : "false";
		},
		
		"undefined": function json_dump_undefined() {
			return "null";
		},
		
		"function": function json_dump_function(fn) {
			return "null";
		}
	};
	
	function json_dump_regexp(re) {
		return primitive_dumpers.string(re.toString());
	}
	
	function is_regexp(value) {
		return (value && typeof(value) == 'object' &&
			typeof(value.test) == "function" &&
			typeof(value.exec) == "function" &&
			typeof(value.global) == "boolean");
	}
	
	function _json_dump_child_value(buf, level, value) {
		var t = typeof(value), end;
		var is_re = is_regexp(value);
		if (value !== null && t == "object" && !is_re) {
			json_dump_object(buf, level + 1, value);
		} else {
			if (value === null)
				value = 'null';
			else if (is_re)
				value = json_dump_regexp(value);
			else
				value = primitive_dumpers[t](value);
			end = buf.length - 1;
			buf[end] = buf[end] + value;
		}
	}
	
	function json_dump_object(buf, level, object) {
		if (typeof(object.each) == "function") {
			json_dump_array(buf, level, object);
			return;
		}
		
		var last = buf.length - 1;
		buf[last] = buf[last] + '{';
		
		var ci = str_repeat(indent, level + 1);
		var name, start, value;
		var keys = Util.Object.names(object), i, t;
		last = keys.length - 1;
		for (i = 0; i < keys.length; i++) {
			name = keys[i];
			value = object[name];
			if (typeof(value) == "function")
				continue;
			buf.push(ci + primitive_dumpers.string(name) + ": ");
			_json_dump_child_value(buf, level, value);
			if (i < last)
				buf[buf.length - 1] = buf[buf.length - 1] + ",";
		}
		
		buf.push(str_repeat(indent, level) + "}");
	}
	
	function json_dump_array(buf, level, array) {
		var last = buf.length - 1;
		buf[last] = buf[last] + '[';
		var ci = str_repeat(indent, level + 1);
		var i, value, last = array.length - 1;
		for (i = 0; i < array.length; i++) {
			value = array[i];
			if (typeof(value) == "function")
				continue;
			buf.push(ci);
			_json_dump_child_value(buf, level, value);
			if (i < last)
				buf[buf.length - 1] = buf[buf.length - 1] + ",";
		}
		
		buf.push(str_repeat(indent, level) + "]");
	}
	
	return {
		dump: function json_dump(object) {
			var t = typeof(object), dumper, buf;
			if (object === null) {
				return 'null';
			} else if (t == "object") {
				if (is_regexp(object))
					return json_dump_regexp(object);
				buf = [''];
				json_dump_object(buf, 0, object);
				return buf.join("\n");
			} else {
				dumper = primitive_dumpers[t];
				if (!dumper)
					throw new TypeError("Cannot dump to JSON; unknown type " + t + ".");
				return dumper(object);
			}
		}
	};
})(); 
// file Util.Lock.js
/**
 * @class A synchronization object, based on Lamport's Bakery algorithm.
 * @see http://decenturl.com/en.wikipedia/lamport
 * @author Eric Naeseth
 * @constructor
 */
Util.Lock = function(name)
{
	var threads = {};
	var next_id = 0;
	var active_thread = null;
	
	function pair_less_than(a, b, c, d)
	{
		return (a < c) || (a == c && b < d);
	}
	
	function next_number()
	{
		var max = 0;
		
		for (var i in threads) {
			if (threads[i] && threads[i].number && threads[i].number > max)
				max = threads[i].number;
		}
		
		return 1 + max;
	}
	
	this.acquire = function()
	{
		var thread = {
			id: ++next_id,
			entering: false
		};
		
		threads[thread.id] = thread;
		
		thread.entering = true;
		thread.number = next_number();
		thread.entering = false;
		
		for (var i in threads) {
			if (!threads[i])
				continue;
				
			var t = threads[i];
			
			// wait until the thread receives its number
			while (t.entering) { /* wait */ }
			
			// wait until all threads with smaller numbers or with the same
			// number but higher priority finish their work with whatever has
			// been locked
			while (t.number &&
				pair_less_than(t.number, i, thread.number, thread.id))
			{
				// wait
			}
		}
		active_thread = thread;
		// the thread is now locked
	}
	
	this.release = function()
	{
		active_thread.number = 0;
	}
} 
// file Util.RSS.js
/**
 * @class Home to RSS-related facilities.
 */
Util.RSS = {
	
}

/**
 * @class A RSS 2.0 feed reader.
 *
 * @constructor Creates a new RSS 2.0 feed reader for the given URL.
 * @param	url	The URL of the RSS feed. You may pass in a function returning the URI instead
 *				of the URL itself. To permit the chunking of results, this function must accept
 *				two parameters: the offset to start on will be passed in as the first parameter
 *				and the number of items to retrieve will be passed in as the second.
 *
 * @author Eric Naeseth
 */
Util.RSS.Reader = function RSSReader(url)
{
	this.url = url;
	
	var offset = 0;
	var listeners = {
		load: [],
		error: [],
		timeout: []
	};
	var aborted = false;
	
	this.feed = null;
	
	function handle_result(document)
	{
		if (aborted || !document)
			return;
		
		var rss = document.documentElement;
		var channel = (function() {
			try {
				return rss.getElementsByTagName('channel')[0];
			} catch (e) {
				handle_error('RSS feed lacks a channel element!', 0);
			}
		})();
		var items = rss.getElementsByTagName('item');
		
		function get_text(node)
		{
			var text = null;
			
			for (var i = 0; i < node.childNodes.length; i++) {
				var child = node.childNodes[i];
				if (child.nodeType == Util.Node.TEXT_NODE) {
					if (text)
						text = text + child.nodeValue;
					else
						text = child.nodeValue;
				}
			}
			
			return text || '';
		}
		
		function get_text_child(container, name)
		{
			var nodes = container.getElementsByTagName(name);
			return (nodes.length == 0)
				? null
				: get_text(nodes[0]);
		}
		
		function to_number(text)
		{
			return (!text || text.length == 0)
				? null
				: new Number(text);
		}
		
		function to_date(text)
		{
			return (text && text.length > 0)
				? new Date(text)
				: null;
		}
		
		if (!this.feed) {
			this.feed = new Util.RSS.Feed();
			this.feed.version = rss.getAttribute('version');
			
			this.feed.channel = new Util.RSS.Channel();
			var channel_object = this.feed.channel;
			channel_object.title = get_text_child(channel, 'title');
			channel_object.link = get_text_child(channel, 'link');
			channel_object.description = get_text_child(channel, 'description');
			channel_object.language = get_text_child(channel, 'language');
			channel_object.copyright = get_text_child(channel, 'copyright');
			channel_object.managing_editor = get_text_child(channel, 'managingEditor');
			channel_object.webmaster = get_text_child(channel, 'webMaster');
			channel_object.publication_date = to_date(get_text_child(channel, 'pubDate'));
			channel_object.last_build_date = to_date(get_text_child(channel, 'lastBuildDate'));
			channel_object.category = get_text_child(channel, 'category');
			channel_object.generator = get_text_child(channel, 'generator');
			channel_object.docs = get_text_child(channel, 'docs');
			channel_object.time_to_live = to_number(get_text_child(channel, 'ttl'));
			channel_object.rating = get_text_child(channel, 'rating');
		}
		
		var new_items = [];
		var item_elements = channel.getElementsByTagName('item');
		
		function get_source(node)
		{
			try {
				return {
					name: get_text(node),
					url: node.getAttribute('url')
				};
			} catch (e) {
				return null;
			}
		}
		
		function get_enclosure(node)
		{
			try {
				return {
					url: node.getAttribute('url'),
					length: to_number(node.getAttribute('length')),
					type: node.getAttribute('type')
				};
			} catch (e) {
				return null;
			}
		}
		
		for (var i = 0; i < item_elements.length; i++) {
			var item = item_elements[i];
			var item_object = new Util.RSS.Item();
			
			for (var j = 0; j < item.childNodes.length; j++) {
				var node = item.childNodes[j];
				
				if (node.nodeType != Util.Node.ELEMENT_NODE)
					continue;
				
				var nn = node.nodeName;
				if (nn == 'pubDate') {
					item_object.publication_date = to_date(get_text(node));
				} else if (nn == 'source') {
					item_object.source = get_source(node);
				} else if (nn == 'enclosure') {
					item_object.enclosure = get_enclosure(node);
				} else {
					item_object[nn] = get_text(node);
				}
			}
			
			new_items.push(item_object);
			this.feed.items.push(item_object);
		}
		
		offset += i;
		
		listeners.load.each(function(l) {
			l(this.feed, new_items);
		}.bind(this));
	}
	
	function handle_error(message, code)
	{
		if (aborted)
			return;
		
		listeners.error.each(function(l) {
			l(message, code);
		});
	}
	
	function handle_timeout()
	{
		listeners.timeout.each(function (l) {
			l('Operation timed out.', 0);
		});
	}
	
	/**
	 * Adds an event listener.
	 */
	this.add_event_listener = function add_rss_event_listener(type, func)
	{
		if (!listeners[type]) {
			throw new Error('Unknown listener type "' + type + '".');
		}
		
		listeners[type].push(func);
		return true;
	}
	
	/**
	 * Loads items from the feed. If the "num" parameter is provided and the URL has been set up
	 * to support chunking (see description of the construtor), only requests that many items.
	 */
	this.load = function load_rss_feed(num, timeout)
	{
		if (!num)
			var num = null;
		if (!timeout)
			var timeout = null;
			
		aborted = false;
		
		var url = (typeof(this.url) == 'function')
			? (num ? this.url(offset, num) : this.url())
			: this.url;
		
		this.request = new Util.Request(url, {
			method: 'GET',
			timeout: timeout,
			
			on_success: function(req, t) {
				if (aborted)
					return;
				if (!(t.responseXML && t.responseXML.documentElement.nodeName == 'rss')) {
					handle_error('Server did not respond with an RSS document.', 0);
				}
				handle_result.call(this, t.responseXML); 
			}.bind(this),
			
			on_failure: function(req, transport) {
				handle_error(req.get_status_text(), req.get_status());
			},
			
			on_abort: function(req, transport) {
				aborted = true;
			},
			
			on_timeout: function(req, transport) {
				if (listeners.timeout.length > 0) {
					aborted = true;
					handle_timeout();
				} else {
					handle_error(req.get_status_text(), req.get_status());
					aborted = true;
				}
			}
		});
	}
}

/**
 * @constructor Creates a new feed object.
 *
 * @class An RSS feed.
 * @author Eric Naeseth
 */
Util.RSS.Feed = function RSSFeed()
{
	this.version = null;
	this.channel = null;
	this.items = [];
}

/**
 * @constructor Creates a new channel object.
 *
 * @class An RSS channel.
 * @author Eric Naeseth
 */
Util.RSS.Channel = function RSSChannel()
{
	// required elements
	this.title = null;
	this.link = null;
	this.description = null;
	
	// optional elements
	this.language = null;
	this.copyright = null;
	this.managing_editor = null;
	this.webmaster = null;
	this.publication_date = null;
	this.last_build_date = null;
	this.category = null;
	this.generator = null;
	this.docs = null;
	this.cloud = null;
	this.time_to_live = null;
	this.image = null;
	this.rating = null;
	this.text_input = null;
	this.skip_hours = null;
	this.skip_days = null;
}

/**
 * @constructor Creates a new feed object.
 *
 * @class An RSS feed.
 * @author Eric Naeseth
 */
Util.RSS.Item = function RSSItem()
{
	this.title = null;
	this.link = null;
	this.description = null;
	this.author = null;
	this.category = null;
	this.comments = null;
	this.enclosure = null;
	this.guid = null;
	this.publication_date = null;
	this.source = null;
} 
// file Util.Radio.js
/**
 * Creates a chunk containing a radio button.
 * @constructor
 *
 * @param	params	an object with the following properties:
 *                  <ul>
 *                  <li>document - the DOM document object which will own the created DOM elements
 *                  <li>id - the desired id of the radio's DOM input element</li>
 *                  <li>name - the desired name of the radio's DOM input element</li>
 *                  <li>value - the desired value of the radio's DOM input element</li>
 *                  <li>label - the desired label of the radio</li>
 *                  <li>checked - boolean indicating whether the radio is checked</li>
 *                  </ul>
 *
 * @class Represents a radio button. Once instantiated, a Radio object
 * has the following properties:
 * <ul>
 * <li>all of the properties given to the constructor in <code>params</code></li>
 * <li>id - the id of the DOM input element</li>
 * <li>label_elem - the DOM label element</li>
 * <li>input_elem - the DOM input element</li>
 * <li>chunk - the containing DOM span element. Use this to append the whole radio chunk.</li>
 * </ul>
 */
Util.Radio = function(params)
{
	this.document = params.document;
	this.id = params.id;
	this.name = params.name;
	this.value = params.value;
	this.label = params.label;
	this.checked = params.checked;

	// Create input element
	this.input_elem = Util.Input.create_named_input({document : this.document, name : this.name, checked : this.checked });
	this.input_elem.setAttribute('type', 'radio');
	this.input_elem.setAttribute('id', this.id);
	this.input_elem.setAttribute('value', this.value);

	// Create label elem
	this.label_elem = this.document.createElement('LABEL');
	this.label_elem.appendChild( this.document.createTextNode( this.label ) );
	this.label_elem.setAttribute('for', this.id);

	// Create chunk, and append to it the input and label elems
	this.chunk = this.document.createElement('SPAN');
	this.chunk.appendChild(this.input_elem);
	this.chunk.appendChild(this.label_elem);
};

// file Util.Range.js
/**
 * Does nothing.
 * @constructor
 *
 * @class Group of functions related to ranges. Useful links:
 * <li><a href="http://www.w3.org/TR/2000/REC-DOM-Level-2-Traversal-Range-20001113/ranges.html">W3C range spec</a></li>
 * <li><a href="http://www.mozilla.org/docs/dom/domref/dom_range_ref.html">Mozilla's Range interface reference</a></li>
 * <li><a href="http://msdn.microsoft.com/workshop/author/dhtml/reference/objects/obj_textrange.asp">Microsoft's documentation on TextRange objects</a></li>
 */
Util.Range = function()
{
};

/**
 * Creates a range from a selection.
 *
 * @param	sel		the selection from which to create range.
 * @return			the created range
 */
Util.Range.create_range = function create_range_from_selection(sel)
{
	// Safari only provides ranges for non-collapsed selections, but still
	// populates the (anchor|focus)(Node|Offset) properties of the selection.
	// Using this, if necessary, we can build our own range object.
	// XXX: I don't actually think that this is true anymore, but I hesitate to
	//      delete the code anyway. -Eric
	
	if (Util.is_function(sel.getRangeAt) && Util.is_number(sel.rangeCount)) {
		if (sel.rangeCount > 0) {
			return sel.getRangeAt(0);
		}
		
		// Try and roll our own.
		if (sel.anchorNode && sel.anchorNode.ownerDocument.createRange) {
			var doc = sel.anchorNode.ownerDocument;
			var range = doc.createRange();
			
			// The old Netscape selection object and DOM Range objects differ in
			// how they class the boundaries of the span of nodes. Selections
			// look at where the user started and finished dragging the mouse
			// while ranges look at which end is actually prior to the other in
			// the document. Because it is an error to set the start and end
			// "backwards" on a DOM range, we have to determine this manually.
			
			function create_range(node, offset)
			{
				var r = doc.createRange();
				r.setStart(node, offset);
				r.collapse(true);
				return r;
			}
			
			var anchor_rng = create_range(sel.anchorNode, sel.anchorOffset);
			var focus_rng = create_range(sel.focusNode, sel.focusOffset);
			
			var natural = anchor_rng.compareBoundaryPoints(Range.START_TO_END,
				focus_range) < 0;
			
			if (natural) {
				range.setStart(sel.anchorNode, sel.anchorOffset);
				range.setEnd(sel.focusNode, sel.focusOffset);
			} else {
				range.setStart(sel.focusNode, sel.focusOffset);
				range.setEnd(sel.anchorNode, sel.anchorOffset);
			}
			
			return range;
		} else {
			throw new Util.Unsupported_Error('getting a range from a ' +
				'collapsed selection');
		}
	} else if (sel.createRange) {
		// Internet Explorer TextRange
		return sel.createRange();
	} else {
		throw new Util.Unsupported_Error('creating a range from a selection');
	}
};

Util.Range.is_collapsed = function is_range_collapsed(rng) {
    var undefined;
    
    if (rng.text !== undefined && rng.text !== null)
        return rng.text == '';
    else if (rng.length !== undefined && rng.length !== null)
        return rng.length <= 0;
    else if (rng.collapsed !== undefined && rng.collapsed !== null)
        return rng.collapsed;
    else if (rng.startContainer && rng.endOffset)
        return (rng.startContainer == rng.endContainer &&
            rng.startOffset == rng.endOffset);
    else
        throw new Util.Unsupported_Error('checking if a range is collapsed');
};

/**
 * Gets the ancestor node which surrounds the given range.
 * XXX: probably better usually to use get_start_container, to
 * follow the convention used elsewhere in Loki. -NB
 *
 * @param	rng		the range in question
 * @return			the ancestor node which surrounds the range
 */
Util.Range.get_common_ancestor = function get_range_common_ancestor(rng)
{
	if (rng.commonAncestorContainer) // W3C
		return rng.commonAncestorContainer;
	else if (rng.parentElement) // Internet Explorer TextRange
		return rng.parentElement();
	else if (rng.item) // Internet Explorer ControlRange
		return rng.item(0);
	
	throw new Util.Unsupported_Error('getting a range\'s common ancestor');
};

/**
 * Returns the boundaries of the range. Uses somewhat different logic than
 * get_start_container; always returns a container and and offset for each
 * end of the range.
 *
 * Note that behavior regarding selections inside of an <input type="text">
 * element is undefined because its text does not exist as a child node of
 * the input element. Gecko won't even allow you to get anything out of the
 * window's selection. WebKit will pull a text node out of thin air for our
 * use. IE's TextRange objects won't be usable for coming up with the
 * representation that we need.
 * 
 * @param {Range}	rng	the range whose boundaries are desired
 * @return {object}
 */
Util.Range.get_boundaries = function get_range_boundaries(rng)
{
	if (!Util.is_valid_object(rng)) {
		throw new TypeError('Must provide a valid object to ' +
			'Util.Range.get_boundaries().');
	}
	
	var dupe; // duplicate of a range
	var parent; // some node's parent element
	
	function get_boundary(side)
	{		
		if (rng[side + 'Container']) {
			// W3C range
			
			return {
				container: rng[side + 'Container'],
				offset: rng[side + 'Offset']
			};
		} else if (rng.duplicate && rng.parentElement) {
			// IE text range
			
			dupe = rng.duplicate();
			dupe.collapse((side == 'start') ? true : false);
			
			// Find the text node in which the now-collapsed selection lies
			// by trying to move its starting point (i.e. the whole thing)
			// back really far, seeing how many characters were actually
			// moved, and then traversing the range's parent element's
			// text node children to find the text node that it refers to.
			
			// Establish a base by finding the position of the parent.
			parent = dupe.parentElement();
			var parent_range =
				parent.ownerDocument.body.createTextRange();
			parent_range.moveToElementText(parent);
			var base = Math.abs(parent_range.move('character',
				-0xFFFFFF));
			
			var offset = (Math.abs(dupe.move('character', -0xFFFFFF))
				- base);
			var travelled = 0;
			
			for (var i = 0; i < parent.childNodes.length; i++) {
				var child = parent.childNodes[i];
				
				if (child.nodeType == Util.Node.ELEMENT_NODE) {
					// IE counts each interspersed element as occupying
					// one character. We have to correct for this when
					// ending within a text node, but it conveniently
					// allows us to find when we're stopping at an
					// element.
					
					if (travelled < offset) {
						// Not this element; move on.
						travelled++;
						continue;
					}
					
					// Found it! It's an element!
					return {
						container: parent,
						offset: Util.Node.get_offset(child)
					}
				} else if (child.nodeType != Util.Node.TEXT_NODE) {
					// Not interested.
					continue;
				}
				
				var cl = child.nodeValue.length;
				if (travelled + cl < offset) {
					// The offset doesn't lie with this text node. Add its
					// length to the distance we've travelled and move on.
					travelled += cl;
					continue;
				}
				
				// Found it!
				return {
					container: child,
					offset: offset - travelled
				};
			}
			
			// End of the parent
			return {
				container: parent,
				offset: parent.childNodes.length
			};
		} else if (rng.item) {
			// IE control range
			
			// Note that this code is UNTESTED because I could not get
			// Internet Explorer to produce a control selection.
			
			var interesting_index = (side == 'start') ? 0 : (rng.length - 1);
			var node = rng.item(interesting_index);
			parent = node.parentNode;
			
			return {
				container: parent,
				offset: Util.Node.get_offset(node)
			};
		} else {
			throw new Util.Unsupported_Error('ranges');
		}
	}
	
	return {
		start: get_boundary('start'),
		end: get_boundary('end')
	};
};

/**
 * Gets the nearest block-level elements in the ancestry of each boundary of
 * the given range.
 * @param {Range} range the range of which the bounding blocks are desired
 * @param {Boolean} [as_bounds=false] if true, returns an object in the style
 * of {@link Util.Range.get_boundaries} specifying the blocks
 * @return {Object} the bounding blocks
 */
Util.Range.get_boundary_blocks = function get_range_boundary_blocks(range,
	as_bounds)
{
	var bounds = Util.Range.get_boundaries(range);
	var side;
	
	function get_block(boundary) {
		var container = boundary.container;
		var length = container.childNodes.length;
		var start;
		var node;
		
		if (container.nodeType == Util.Node.TEXT_NODE)
			start = container.parentNode;
		else if (container.childNodes[boundary.offset])
			start = container.childNodes[boundary.offset];
		else if (length == 0)
			start = container;
		else
			start = container.childNodes[boundary.offset - 1];
			
		for (var node = start; node; node = node.parentNode) {
			if (Util.Node.is_block(node))
				return node;
		}
		
		throw new Error('Could not find an enclosing block for the range ' +
			'boundary.');
	}
	
	function process_block(block) {
		if (!as_bounds)
			return block;
		
		return {
			container: block.parentNode,
			offset: Util.Node.get_offset(block)
		};
	}
	
	for (side in bounds) {
		bounds[side] = process_block(get_block(bounds[side]));
	}
	return bounds;
};

/**
 * Finds matching elements within the range.
 * @param {Range|Object} rng the range to search in, or a range boundary object
 *        like the one returned from {@link Util.Range.get_boundaries}.
 * @param {Function|String} [matcher] either a matching function or a tag name.
 * @param {Boolean} [up=false] also search up the tree from the range's common
 *        ancestor. It is an error to set this option if there is no matcher.
 * @throws {Error} if up is true but there is no matcher
 * @return {HTMLElement[]} all found matching elements
 */
Util.Range.find_nodes = function find_nodes_in_range(rng, matcher, up) {
	function process_boundary(bound) {
		var length;
		
		if (bound.container.nodeType == Util.Node.TEXT_NODE)
			return bound.container;
		
		if (bound.container.childNodes[bound.offset])
			return bound.container.childNodes[bound.offset];
		
		length = bound.container.childNodes.length;
		if (length == 0 || bound.offset == 0)
			return bound.container;
		else if (bound.offset >= length)
			return bound.container.childNodes[length - 1];
		else
			throw new Error('Unable to process boundary for find_nodes_in_range: ' +
				Util.Node.get_debug_string(bound.container) + ':' + bound.offset);
	}
	
	var bounds = (rng.start && rng.start.container && rng.end.container)
		? rng
		: Util.Range.get_boundaries(rng);
	var matched_nodes = [];
	var start = process_boundary(bounds.start);
	var end = process_boundary(bounds.end);
	var node;
	var ancestor;
	
	if (!matcher && up) {
		throw new Error('Cannot find nodes that are ancestors of the range ' +
			'if no matcher is selected.');
	}
	
	function next_node(n) {
		if (n.hasChildNodes()) {
			n = n.firstChild;
		} else if (n.nextSibling) {
			n = n.nextSibling;
		} else if (n.parentNode && n.parentNode.nextSibling) {
			n = n.parentNode.nextSibling;
		} else {
			n = null;
		}
		
		return n;
	}
	
	if (typeof(matcher) == 'string')
		matcher = Util.Node.curry_is_tag(matcher);
	else if (!matcher)
		matcher = Util.Function.optimist;
	else if (typeof(matcher) != 'function')
		throw new TypeError('Invalid matcher.');
	
	for (node = start; node; node = next_node(node)) {
		if (matcher(node))
			matched_nodes.push(node);
		if (node == end)
			break;
	}
	
	if (up) {
		ancestor = Util.Range.get_common_ancestor(rng);
		if (!ancestor)
			return matched_nodes;
		if (ancestor == start || ancestor == end)
			ancestor = ancestor.parentNode;
		end = start.ownerDocument;
		for (node = ancestor; node && node != end; node = node.parentNode) {
			if (matcher(node))
				matched_nodes.push(node);
		}
	}
	
	return matched_nodes;
};

/**
 * Returns the start container of the given range (if
 * the given range is a text range) or starting element
 * (i.e., first contained node, if the given range is a control 
 * range)
 * 
 * @param	rng		the range in question
 * @return			the start container of the range
 */
Util.Range.get_start_container = function get_range_start_container(rng)
{
	// Gecko
	try
	{
		// Control range
		//   This is not precisely like IE's control range. But it is
		//   like it in that if one entire element is selected, 
		//   this function returns that element (rng.item(0)),
		//   which does what we want. (Otherwise, for example editing 
		//   images and links breaks.)
		//   
		//   (Note: if this breaks, consult the archived versions--I've
		//   played with this a lot to get it to work right.)
		var frag = rng.cloneContents();
		if (frag && frag.firstChild == frag.lastChild &&
			 frag.firstChild != null &&
		     frag.firstChild.nodeType != Util.Node.TEXT_NODE &&
			 frag.lastChild != null &&
		     frag.lastChild.nodeType != Util.Node.TEXT_NODE)
		{
			var siblings = rng.commonAncestorContainer.childNodes;
			for (var i = 0; i < siblings.length; i++)
				if (rng.compareNode(siblings[i]) == rng.NODE_INSIDE)
					return siblings[i];
		}

		// Text range
		if (rng.startContainer.nodeType == Util.Node.TEXT_NODE) // imitate IE below
			return rng.startContainer.parentNode;
		else
			return rng.startContainer;
	}
	catch(e)
	{
		// IE
		try
		{
			// Control range
			if (rng.item != null)
			{
				return rng.item(0);
			}
			// Text range
			else if (rng.parentElement != null)
			{
				// original, works in most circumstances:
				//return rng.parentElement();
				var rng2 = rng.duplicate();
				rng2.collapse(true); // to start
				return rng2.parentElement();
			}
		}
		catch(f)
		{
			throw(new Error('Util.Range.get_start_container(): Neither the Mozilla nor the IE way of getting the start container worked. ' +
								'When the Mozilla way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
								'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
};

/**
 * Returns the end container of the given range (if
 * the given range is a text range) or ending element
 * (i.e., last contained node, if the given range is a 
 * control range)
 *
 * @param	rng		the range in question
 * @return			the end container of the range
 */
Util.Range.get_end_container = function get_range_end_container(rng)
{
	// Gecko
	try
	{
		// Control range
		//   This is not precisely like IE's control range. But it is
		//   like it in that if one entire element is selected, 
		//   this function returns that element (rng.item(0)),
		//   which does what we want. (Otherwise, for example editing 
		//   images and links breaks.)
		//   
		//   (Note: if this breaks, consult the archived versions--I've
		//   played with this a lot to get it to work right.)
		//
		//   (Note: this does precisely the same thing as get_start_container
		//   for control ranges, because the range is only considered a control
		//   range if the first and last elements are identical. Previous 
		//   versions didn't work this way.)
		var frag = rng.cloneContents();
		if (frag && frag.firstChild == frag.lastChild &&
			 frag.firstChild != null &&
		     frag.firstChild.nodeType != Util.Node.TEXT_NODE &&
			 frag.lastChild != null &&
		     frag.lastChild.nodeType != Util.Node.TEXT_NODE)
		{
			var siblings = rng.commonAncestorContainer.childNodes;
			for (var i = 0; i < siblings.length; i++)
				if (rng.compareNode(siblings[i]) == rng.NODE_INSIDE)
					return siblings[i];
		}

		// Text range
		if (rng.endContainer.nodeType == Util.Node.TEXT_NODE) // imitate IE below
			return rng.endContainer.parentNode;
		else
			return rng.endContainer;
	}
	catch(e)
	{
		// IE
		try
		{
			// Control range
			if (rng.item != null)
			{
				return rng.item(rng.length - 1);
			}
			// Text range
			else if (rng.parentElement != null)
			{
				var rng2 = rng.duplicate();
				rng2.collapse(false); // to end
				return rng2.parentElement();
			}
		}
		catch(f)
		{
			throw(new Error('Util.Range.get_start_container(): Neither the Mozilla nor the IE way of getting the start container worked. ' +
								'When the Mozilla way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
								'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
};


/**
 * Deletes the contents of the given range.
 *
 * @param	rng		the range
 */
Util.Range.delete_contents = function delete_range_contents(rng)
{
	if (Util.is_function(rng.deleteContents)) { // W3C
		rng.deleteContents();
	} else if (rng.pasteHTML) { // TextRange
		rng.pasteHTML('');
	} else if (rng.item && rng.remove) { // ControlRange
		while (rng.length > 0) {
			var item = rng.item(0);
			item.parentNode.removeChild(item);
			rng.remove(0);
		}
	} else {
		throw new Util.Unsupported_Error("deleting a range's contents");
	}
};

/**
 * Inserts a node at the beginning of the given range.
 *
 * @param	rng		the range
 * @param	node	the node to insert
 * @return {void}
 */
Util.Range.insert_node = function insert_node_in_range(rng, node)
{
	var bounds;
	var point;
	var target;
	
	if (rng.insertNode) {
		// W3C range
		rng.insertNode(node);
	} else {
		// Internet Explorer range
		bounds = Util.Range.get_boundaries(rng);
		
		if (bounds.start.container.nodeType == Util.Node.TEXT_NODE) {
			// Inserting the node into a text node; split it at the insertion
			// point.
			bounds.start.container.splitText(bounds.start.offset);
			point = bounds.start.container.nextSibling;
			
			// Now the node can be inserted between the two text nodes.
			target = bounds.start.container.parentNode;
		} else {
			point = (bounds.start.container.hasChildNodes())
				? bounds.start.container.childNodes[bounds.start.offset]
				: null;
			target = bounds.start.container;
		}
		
		// Don't remove this split; insertBefore SHOULD work with a null
		// second argument, but IE8 doesn't accept it.
		if (point) {
			target.insertBefore(node, point);
		} else {
			target.appendChild(node);
		}
	}
};

/**
 * Clones the given range.
 *
 * @param	rng		the range
 * @return			a clone of rng
 */
Util.Range.clone_range = function clone_range(rng)
{
	if (Util.is_function(rng.cloneRange)) {
		return rng.cloneRange();
	} else if (rng.duplicate) {
		return rng.duplicate();
	} else {
		throw new Util.Unsupported_Error("cloning a range");
	}
};

/**
 * Clones the contents of the given range.
 *
 * @param  {Range}  rng       the range whose contents are desired
 * @return {DocumentFragment} the range's contents
 */
Util.Range.clone_contents = function clone_range_contents(rng)
{
	var html;
	var doc;
	var hack;
	var frag;
	
	if (rng.cloneContents) {
		// W3C range
		return rng.cloneContents();
	} else if (html = rng.htmlText) { // assignment intentional
		// IE text range
		// This is just painfully hackish, but the option of writing the code
		// to properly traverse a range and clone its contents is far worse.
		
		doc = rng.parentElement().ownerDocument;
		
		hack = doc.createElement('DIV');
		hack.innerHTML = html;
		
		frag = doc.createDocumentFragment();
		while (hack.firstChild) {
			frag.appendChild(hack.firstChild);
		}
		
		return frag;
	} else {
		throw new Util.Unsupported_Error('cloning the contents of a range');
	}
}

/**
 * Deletes the contents of the given range.
 *
 * @param {Range}  rng   the range whose contents should be deleted
 * @return {void}
 */
Util.Range.delete_contents = function delete_range_contents(rng)
{
	if (rng.deleteContents) {
		// W3C range
		rng.deleteContents();
	} else if (rng.parentElement) {
		// IE text range
		rng.text = ''; // seriously.
	} else {
		throw new Util.Unsupported_Error('deleting the contents of a range');
	}
}

/**
 * Gets the html of the range.
 */
Util.Range.get_html = function get_html_of_range(rng)
{
	var html = '';
	try // Gecko
	{
		var frag = rng.cloneContents();
		var container = rng.startContainer.ownerDocument.createElement('DIV');
		container.appendChild(frag);
		html = container.innerHTML;
	}
	catch(e)
	{
		try // IE
		{
			if (rng.htmlText != null)
				html = rng.htmlText;
			else if (rng.length > 0)
			{
				for (var i = 0; i < rng.length; i++)
					html += rng.item(i).outerHTML;
			}
		}
		catch(f)
		{
			throw('Util.Range.get_html(): Neither the Gecko nor the IE way of getting the image worked. ' +
				  'When the Gecko way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
				  'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.');
		}
	}
	return html;
};

/**
 * Gets the given range's nearest ancestor which is a block-level
 * element
 *
 * @param	rng		the starting range
 * @return			the matching ancestor, if any
 */
Util.Range.get_nearest_bl_ancestor_element =
	function get_nearest_block_level_ancestor_element_of_range(rng)
{
	return Util.Range.get_nearest_ancestor_node(rng, Util.Node.is_block_level_element);
};

/**
 * Gets the given range's nearest ancestor which maches the given
 * test.
 *
 * @param	rng				the starting range
 * @param	boolean_test	the test
 * @return					the matching ancestor, if any
 */
Util.Range.get_nearest_ancestor_node =
	function get_nearest_ancestor_node_of_range(rng, boolean_test)
{
	// XXX: Do we really want this? -Eric
	var ancestor = Util.Range.get_start_container(rng);
	
	if (!ancestor)
		return null;
	
	if (boolean_test(ancestor)) {
		return ancestor;
	} else {
		return Util.Node.get_nearest_ancestor_node(ancestor, boolean_test);
	}
};

/**
 * Gets the given range's nearest ancestor which is an element whose
 * tagname matches the one given.
 *
 * @param	rng				the starting range
 * @param	tag_name		the desired tag name	
 * @return					the matching ancestor, if any
 */
Util.Range.get_nearest_ancestor_element_by_tag_name =
	function get_nearest_ancestor_element_of_range_by_tag_name(rng, tag_name)
{
	function boolean_test(node)
	{
		return (node.nodeType == Util.Node.ELEMENT_NODE &&
			     node.tagName == tag_name);
	}
	return Util.Range.get_nearest_ancestor_node(rng, boolean_test);
};

/**
 * Gets clones of the child nodes of the given range. Currently, this
 * will only work under IE if the given range is a controlRange
 * collection, but not if it's a textRange object. (If a textRange is
 * given, no error will be thrown, but an empty array will be
 * returned.)
 *
 * @param	rng		the range whose children to clone
 * @return			an array of clones of the given range's children
 */
Util.Range.get_cloned_children = function clone_children_of_range(rng)
{
	var child_nodes = new Array();
	try
	{
		var doc_frag = rng.cloneContents();
		var node_list = doc_frag.childNodes;
		for (var i = 0; i < node_list.length; i++)
			child_nodes.push(node_list.item(i));
	}
	catch(e)
	{
		try
		{
			if (rng.item) // if this is a controlRange collection rather than a textRange Object
			{
				for (var i = 0; i < rng.length; i++)
					child_nodes.push(rng.item(i).cloneNode(true));
			}
		}
		catch(f)
		{
			throw(new Error('Util.Range.get_cloned_children(): Neither the W3c nor the Mozilla way of getting the image worked. ' +
							'When the W3C way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
	return child_nodes;
};

/**
 * Returns the text contained in the given range.
 */
Util.Range.get_text = function get_range_text(rng)
{
	var text;
	try // Gecko
	{
		text = rng.toString();		
	}
	catch(e)
	{
		try // IE
		{
			if (rng.text != null) // text range
				text = rng.text;
			else // control range
				text = ''; // XXX is this desirable?
		}
		catch(f)
		{
			throw(new Error('Util.Range.get_text(): Neither the Gecko nor the IE way of getting the text worked. ' +
							'When the Gecko way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
	return text;
};

// XXX: These two functions might only work for Gecko right now (and only need to)
Util.Range.is_at_end_of_block = function is_range_at_end_of_block(rng, block)
{
	var ret =
		Util.Node.get_rightmost_descendent(block) == 
		Util.Node.get_rightmost_descendent(rng.startContainer) &&
		// either the start container is not a text node, or 
		// the range (i.e. cursor) is at the end of the text node
		(//rng.startContainer.nodeType != Util.Node.TEXT_NODE ||
		  rng.startOffset == rng.startContainer.length); // added - 1 // 
	return ret;
};

Util.Range.is_at_beg_of_block = function is_range_at_beginning_of_block(rng, block)
{
	var ret =
		// the start container is on the path to the leftmost descendent of the current block
		Util.Node.get_leftmost_descendent(block) == 
		Util.Node.get_leftmost_descendent(rng.startContainer) &&
		// either the start container is not a text node, or 
		// the range (i.e. cursor) is at the beginning of the text node
		(rng.startContainer.nodeType != Util.Node.TEXT_NODE ||
		  rng.startOffset == 0);
	return ret;
};

Util.Range.is_at_end_of_text = function is_range_at_end_of_text(rng)
{
	return (rng.endContainer.nodeType == Util.Node.TEXT_NODE && rng.endOffset == rng.endContainer.length);
};

Util.Range.is_at_beg_of_text = function is_range_of_beginning_of_text(rng)
{
	return (rng.startContainer.nodeType == Util.Node.TEXT_NODE && rng.startOffset == 0);
}

/**
 * @see Util.Range.surrounded_by_node
 */
Util.Range.intersects_node = function range_intersects_node(rng, node)
{
	var doc = node.ownerDocument;
	var node_rng;
	
	if (Util.is_function(rng.intersectsNode)) { // Gecko < 1.9
		return rng.intersectsNode(node);
	} else if (Util.is_function(doc.createRange)) { // W3C
		node_rng = doc.createRange();
		
		try {
			node_rng.selectNode(node);
		} catch (e) {
			node_rng.selectNodeContents(node);
		}
		
		return (rng.compareBoundaryPoints(Range.END_TO_START, node_rng) == -1
			&& rng.compareBoundaryPoints(Range.START_TO_END, node_rng) == 1);
	} else if (doc.body.createTextRange) {
		// This *might* work. -Eric
		
		node_rng = doc.body.createTextRange();
		node_rng.moveToNodeText(node);
		
		return (rng.compareEndPoints('EndToStart', node_rng) == -1 &&
			rng.compareEndPoints('StartToEnd', node_rng) == 1);
	} else {
		throw new Util.Unsupported_Error('testing whether a node intersects ' +
			' a range');
	}
}

// XXX doesn't work, I believe
/**
 * Returns a list of all descendant nodes that match boolean_test.
 */
Util.Range.get_descendant_nodes =
	function get_range_descendant_nodes(rng, boolean_test)
{
	var matches = [];

	// we use depth-first so that the matches are ordered 
	// according to their position in the document
	var search = function(node)
	{
		for (var i = 0; i < node.childNodes.length; i++)
		{
			search(node.childNodes[i]);
			if (Util.Range.intersects_node(rng, node.childNodes[i]) && boolean_test(node))
				matches.push(node.childNodes[i]);
		}
	}

	var ancestor = Util.Range.get_common_ancestor(rng);
	search(ancestor);

	return matches;
};

// XXX doesn't work
Util.Range.get_elements_within_range = Util.Function.unimplemented;
//Util.Range.get_elements_within_range = function(rng, boolean_test)

/**
 * Compares the boundary points of the two given ranges.
 * Modified from <http://msdn.microsoft.com/workshop/author/dhtml/reference/methods/compareendpoints.asp>:
 * @param	how		Util.Range constant that specifies one of the following values:
 * 						START_TO_START	Compare the start of rng1 with the start of rng2.
 * 						START_TO_END	Compare the start of rng1 with the end of rng2.
 * 						END_TO_START	Compare the end of rng1 with the start of rng2.
 * 						END_TO_END		Compare the end of rng1 with the end of rng2.
 * @return			Returns one of the following possible values:
 *						-1	The end point of rng1 is further to the left than the end point of rng2.
 *						0	The end point of rng1 is at the same location as the end point of rng2.
 *						1	The end point of rng1 is further to the right than the end point of rng2.
 */
Util.Range.START_TO_START = 2;
Util.Range.START_TO_END = 3;
Util.Range.END_TO_START = 4;
Util.Range.END_TO_END = 5;
Util.Range.LEFT = -1;
Util.Range.SAME = 0;
Util.Range.RIGHT = 1;
Util.Range.compare_boundary_points =
	function compare_range_boundary_points(rng1, rng2, how)
{
	if (!Util.is_valid_object(rng1, rng2)) {
		throw new TypeError('Two range objects must be passed to ' +
			'Util.Range.compare_boundary_points.');
	}
	
	if (!Util.is_number(how)) {
		throw new TypeError('A Util.Range comparison constant must be passed ' +
			'to Util.Range.compare_boundary_points.')
	}
	
	var real_how;
	if (Util.is_function(rng1.compareBoundaryPoints)) { // W3C
		if (how == Util.Range.START_TO_START)
			real_how = rng1.START_TO_START;
		else if (how == Util.Range.START_TO_END)
			real_how = rng1.START_TO_END;
		else if (how == Util.Range.END_TO_START)
			real_how = rng1.END_TO_START;
		else if (how == Util.Range.END_TO_END)
			real_how = rng1.END_TO_END;

		return rng1.compareBoundaryPoints(real_how, rng2);
	} else if (rng1.compareEndPoints) { // IE
		if (how == Util.Range.START_TO_START)
			real_how = "StartToStart";
		else if (how == Util.Range.START_TO_END)
			real_how = "StartToEnd";
		else if (how == Util.Range.END_TO_START)
			real_how = "EndToStart";
		else if (how == Util.Range.END_TO_END)
			real_how = "EndToEnd";

		return rng1.compareEndPoints(real_how, rng2);
	} else {
		throw new Util.Unsupported_Error("comparing two ranges' boundary " +
			"points");
	}
};

Util.Range.select_node = function range_select_node(rng, node)
{
	if (rng.selectNode) {
		rng.selectNode(node);
	} else {
		Util.Range.set_start_before(rng, node);
		Util.Range.set_start_after(rng, node);
	}
};

/**
 * A good explanation of what this does from <http://www.dotvoid.com/view.php?id=11>:
 * 
 * Sets the startContainer and endContainer to the supplied node 
 * with a startOffset of 0 and an endOffset of the number of child nodes 
 * the node contains or the number of characters that the node contains.
 */
Util.Range.select_node_contents = function range_select_node_contents(rng, node)
{
	if (Util.is_function(rng.selectNodeContents)) {
		rng.selectNodeContents(node);
	} else if (rng.moveToElementText) {
		rng.moveToElementText(node);
	} else {
		throw new Util.Unsupported_Error("selecting a node's contents with a " +
			"range");
	}
};

/**
 * Determines whether or not the range is entirely surrounded by the given
 * element.
 * @param {Range}	rng	range
 * @param {Element}	elem	element
 * @type boolean
 */
Util.Range.surrounded_by_node = 
	function range_surrounded_by_node(rng, elem)
{
	var n_rng;
	var doc = elem.ownerDocument;
	
	if (Util.is_function(doc.createRange)) {
		n_rng = doc.createRange();
		try {
			n_rng.selectNode(elem);
		} catch (e) {
			n_rng.selectNodeContents(elem);
		}
	} else if (doc.body.createTextRange) {
		n_rng = doc.body.createTextRange();
		n_rng.moveToNodeText(elem);
	} else {
		throw new Util.Unsupported_Error('checking if a range is entirely ' +
			'enclosed by an element');
	}
	
	var START_TO_START = Util.Range.START_TO_START;
	var END_TO_END = Util.Range.END_TO_END;
	
	return (Util.Range.compare_boundary_points(rng, n_rng, START_TO_START) >= 0
		&& Util.Range.compare_boundary_points(rng, n_rng, END_TO_END) <= 0);
}

/**
 * Determines whether or not the range contains the entirety of the given node.
 * @param {Range}	rng	range
 * @param {Node}	node	node
 * @type boolean
 */
Util.Range.contains_node = function range_contains_node(rng, node)
{
	var n_rng;
	var doc = node.ownerDocument;
	
	if (Util.is_function(doc.createRange)) {
		n_rng = doc.createRange();
		try {
			n_rng.selectNode(node);
		} catch (e) {
			n_rng.selectNodeContents(node);
		}
	} else if (doc.body.createTextRange) {
		n_rng = doc.body.createTextRange();
		n_rng.moveToNodeText(node);
	} else {
		throw new Util.Unsupported_Error('checking if a node is entirely ' +
			'enclosed by a range');
	}
	
	var START_TO_START = Util.Range.START_TO_START;
	var END_TO_END = Util.Range.END_TO_END;
	
	return (Util.Range.compare_boundary_points(n_rng, rng, START_TO_START) >= 0
		&& Util.Range.compare_boundary_points(n_rng, rng, END_TO_END) <= 0);
}

/**
 * Gets all blocks that this range encompasses in whole or part,
 * but that do not surround the range. In other words, gets the 
 * blocks that you probably intend to work on when performing a 
 * block-level operation on a range.
 */
Util.Range.get_intersecting_blocks = function get_range_intersecting_blocks(rng)
{
	// INIT

	// Determine start and end blocks
	var start_container = Util.Range.get_start_container(rng);
	var b1;
	if (Util.Node.is_block_level_element(start_container))
		b1 = start_container;
	else
		b1 = Util.Node.get_nearest_bl_ancestor_element(start_container);

	var end_container = Util.Range.get_end_container(rng);
	var b2;
	if (Util.Node.is_block_level_element(end_container))
		b2 = end_container;
	else
		b2 = Util.Node.get_nearest_bl_ancestor_element(end_container);

	// Determine b2_and_ancestors
	var b2_and_ancestors = [];
	var cur_block = b2;
	while (cur_block != null && cur_block.nodeName != 'BODY' && cur_block.nodeName != 'TD')
	{
		b2_and_ancestors.push(cur_block);
		cur_block = cur_block.parentNode;
	}

	// HELPER FUNCTIONS

	function is_b2_or_ancestor(block)
	{
		for (var i = 0; i < b2_and_ancestors.length; i++)
			if (block == b2_and_ancestors[i])
			{
				mb('found match in is_b2_ancestor: block', block);
				return true;
			}
		return false;
	}

	/**
	 * Looks for the branch of the DOM tree that is closest to b1, while still
	 * containing and either b2 or an ancestor of b2 (and b1 or anancestor of b1).
	 * Does this by climbing the tree, starting at b1's parent, looking for an
	 * ancestor of b2 among the current branch's child nodes.
	 *
	 * @return	object with properties branch, b1_or_ancestor, and b2_or_ancestor,
	 * 			the latter two being children of branch.
	 */
	function look_for_closest_branch_common_to_b1_and_b2(branch, b1_or_ancestor)
	{
		// Try this branch
		for (var i = 0; i < branch.childNodes.length; i++)
		{
			var cur = branch.childNodes[i];
			if (is_b2_or_ancestor(cur))
			{
				var b2_or_ancestor = cur;
				return { branch : branch, b1_or_ancestor : b1_or_ancestor, b2_or_ancestor : b2_or_ancestor };
			}
		}

		// Otherwise try parent branch
		return look_for_closest_branch_common_to_b1_and_b2(branch.parentNode, branch);
		// (branch will be the ancestor of b1 among the branch.parentNode.childNodes)
	}

	function get_intersecting_blocks(branch, b1_or_ancestor, b2_or_ancestor)
	{
		var blocks = [];
		var start = false;
		for (var i = 0; i < branch.childNodes.length; i++)
		{
			var cur = branch.childNodes[i];
			if (cur == b1_or_ancestor)
				start = true;
			if (start)
				blocks.push(cur);
			if (cur == b2_or_ancestor)
			{
				start = false;
				break;
			}
		}
		return blocks;
	}

	// DO IT

	var starting_branch = b1.parentNode;
	var ret = look_for_closest_branch_common_to_b1_and_b2(starting_branch, b1)
	return get_intersecting_blocks(ret.branch, ret.b1_or_ancestor, ret.b2_or_ancestor);
};

Util.Range._ie_set_endpoint =
	function _ie_text_range_set_endpoint(rng, which, node, offset)
{
	// Frustratingly, we cannot directly set the absolute end points of an
	// Internet Explorer text range; we can only set them in terms of an end
	// point of another text range. So, we create a text range whose start point 
	// will beat the desired node and offset and then set the given endpoint of
	// the range in terms of our new range.
	
	var marker = rng.parentElement().ownerDocument.body.createTextRange();
	var parent = (node.nodeType == Util.Node.TEXT_NODE)
		? node.parentNode
		: node;
	var node_of_interest;
	var char_offset;
	
	marker.moveToElementText(parent);
	
	// IE text ranges use the character as their principal unit. So, in order
	// to translate from the W3C container/offset convention, we must find
	// the number of characters a node is located from the start of "parent".
	function find_node_character_offset(node)
	{
		var stack = [parent];
		var offset = 0;
		var o;
		
		while (o = stack.pop()) { // assignment intentional
			if (node && o == node)
				return offset;
			
			if (o.nodeType == Util.Node.TEXT_NODE) {
				offset += o.nodeValue.length;
			} else if (o.nodeType == Util.Node.ELEMENT_NODE) {
				if (o.hasChildNodes()) {
					for (var i = o.childNodes.length - 1; i >= 0; i--) {
						stack.push(o.childNodes[i]);
					}
				} else {
					offset += 1;
				}
			}
		}
		
		if (!node)
			return offset;
		
		throw new Error('Could not find the node\'s offset in characters.');
	}
	
	if (node.nodeType == Util.Node.TEXT_NODE) {
		if (offset > node.nodeValue.length) {
			throw new Error('Offset out of bounds.');
		}
		
		char_offset = find_node_character_offset(node);
		char_offset += offset;
	} else {
		if (offset > node.childNodes.length) {
			throw new Error('Offset out of bounds.');
		}
		
		node_of_interest = (offset == node.childNodes.length)
			? null
			: node.childNodes[offset];
		char_offset = find_node_character_offset(node_of_interest);
	}
	
	marker.move('character', char_offset);
	rng.setEndPoint(which + 'ToEnd', marker);
}

Util.Range.set_start = function set_range_start(rng, start, offset)
{
	if (rng.setStart) {
		// W3C range
		rng.setStart(start, offset);
	} else if (rng.setEndPoint) {
		// IE text range
		Util.Range._ie_set_endpoint(rng, 'Start', start, offset);
	} else {
		throw new Util.Unsupported_Error('setting the start of a range');
	}
};

Util.Range.set_end = function set_range_end(rng, end, offset)
{
	if (rng.setEnd) {
		// W3C range
		rng.setEnd(end, offset);
	} else if (rng.setEndPoint) {
		// IE text range
		Util.Range._ie_set_endpoint(rng, 'End', end, offset);
	} else {
		throw new Util.Unsupported_Error('setting the end of a range');
	}
};

Util.Range.set_start_before = function set_range_start_before(rng, node)
{
	if (rng.setStartBefore) {
		// W3C range
		rng.setStartBefore(node);
	} else {
		// Fake it
		Util.Range.set_start(node.parentNode, Util.Node.get_offset(node));
	}
}

Util.Range.set_start_after = function set_range_start_after(rng, node)
{
	if (rng.setStartAfter) {
		// W3C range
		rng.setStartAfter(node);
	} else {
		// Fake it
		Util.Range.set_start(node.parentNode, Util.Node.get_offset(node) + 1);
	}
}

Util.Range.set_end_before = function set_range_end_before(rng, node)
{
	if (rng.setEndBefore) {
		// W3C range
		rng.setEndBefore(node);
	} else {
		// Fake it
		Util.Range.set_end(node.parentNode, Util.Node.get_offset(node));
	}
}

Util.Range.set_end_after = function set_range_end_after(rng, node)
{
	if (rng.setEndAfter) {
		// W3C range
		rng.setEndAfter(node);
	} else {
		// Fake it
		Util.Range.set_end(node.parentNode, Util.Node.get_offset(node) + 1);
	}
}

// file Util.Request.js
/**
 * @class  Asynchronus HTTP requests (an XMLHttpRequest wrapper).
 *         Deprecates Util.HTTP_Reader.
 * @author Eric Naeseth
 */
Util.Request = function(url, options)
{
	var self = this;
	var timeout = null;
	var timed_out = false;
	
	this.options = options || {};
		
	for (var option in Util.Request.Default_Options) {
		if (!this.options[option])
			this.options[option] = Util.Request.Default_Options[option];
	}
	
	function create_transport()
	{
		try {
			return new XMLHttpRequest();
		} catch (e) {
			try {
				return new ActiveXObject('Msxml2.XMLHTTP');
			} catch (f) {
				try {
					return new ActiveXObject('Microsoft.XMLHTTP');
				} catch (g) {
					throw new Util.Unsupported_Error('XMLHttpRequest');
				}
			}
		}
	}
	
	var empty = Util.Function.empty;
	
	function ready_state_changed()
	{
		var state = self.transport.readyState;
		var name = Util.Request.Events[state];
		
		(self.options['on_' + state] || empty)(self, self.transport);
		
		if (name == 'complete')
			completed();
	}
	
	function completed()
	{
		if (timeout) {
			timeout.cancel();
			timeout = null;
		}
		
		(self.options['on_'] + self.get_status()
			|| self.options['on_' + (self.succeeded() ? 'success' : 'failure')]
			|| empty)(self, self.transport);
		self.transport.onreadystatechange = empty;
	}
	
	function internal_abort(send_notification)
	{
		this.transport.onreadystatechange = empty;
		
		try {
			if (send_notificiation) {
				try {
					(this.options.on_abort || empty)(this, this.transport);
				} catch (handler_exception) {
					// ignore
				}
			}
			
			this.transport.abort();
		} catch (e) {
			// do nothing
		}
	}
	
	this.get_status = function()
	{
		try {
			return this.transport.status || 0;
		} catch (e) {
			return 0;
		}
	}
	
	this.get_status_text = function()
	{
		try {
			return (timed_out)
				? 'Operation timed out.'
				: (this.transport.statusText || '');
		} catch (e) {
			return '';
		}
	}
	
	this.get_header = function(name)
	{
		try {
			return this.transport.getResponseHeader(name);
		} catch (e) {
			return null;
		}
	}
	
	this.succeeded = function()
	{
		var status = this.get_status();
		return !status || (status >= 200 && status < 300);
	}
	
	this.abort = function()
	{
		internal_abort.call(this, true);
	}
	
	timed_out = false;
	
	if (this.options.timeout) {
		timeout = Util.Scheduler.delay(function() {
			internal_abort.call(this, false);
			(this.options.on_timeout || this.options.on_failure || empty)
				(this, this.transport);
		}.bind(this), this.options.timeout);
	}
	
	this.transport = create_transport();
	this.url = url;
	this.method = this.options.method;
	this.transport.onreadystatechange = ready_state_changed;
	
	try {
		this.transport.open(this.method.toUpperCase(), this.url,
			this.options.asynchronus);
		if (this.options.headers) {
			Util.Object.enumerate(this.options.headers, function(k, v) {
				this.transport.setRequestHeader(k, v);
			}, this);
		}
		this.transport.send(this.options.body || null);
	} catch (e) {
		if (timeout) {
			timeout.cancel();
			timeout = null;
		}
		
		throw e;
	}
	
};

Util.Request.Default_Options = {
	method: 'post',
	asynchronus: true,
	content_type: 'application/x-www-form-urlencoded',
	encoding: 'UTF-8',
	parameters: '',
	timeout: null
};

Util.Request.Events =
	['uninitialized', 'ready', 'send', 'interactive', 'complete'];

// file Util.Select.js
/**
 * @constructor Nothing
 *
 * @class Represents an HTML select element. Example usage:
 *
 *  var s = new Util.Select({ document : document, loading_str : 'Loading now ...', id : 's_id' });
 *  parent_elem.appendChild(s);
 *  s.start_loading();
 *  s.add_option({ key : 'One', value : 'Two', selected : false });
 *  s.add_option({ key : 'Three', value : 'Four', selected : false });
 *  s.add_option({ key : 'Five', value : 'Six', selected : true });
 *  s.end_loading();
 *
 */
Util.Select = function Select(params)
{
	this.document = params.document;
	this._loading_str = params.loading_str != null ? params.loading_str : 'Loading ...';
	this.id = params.id;

	this._options = [];

	// Create select element
	function default_factory() { return this.document.createElement('SELECT'); }
	
	this.select_elem = (params.factory || default_factory)();
	if ( this.id != null )
		this.select_elem.setAttribute('id', this.id);
		
	function create_loading_option()
	{
		var option = this.document.createElement('OPTION');
		option.value = '';
		option.appendChild(this.document.createTextNode(this._loading_str));
		
		return option;
	}

	// Methods

	/**
	 * Start loading. This removes all options, hides the actual select
	 * element, and shows a fake "loading" one.
	 */
	this.start_loading = function()
	{
		// Remove all options
		while ( this.select_elem.firstChild != null )
			this.select_elem.removeChild(this.select_elem.firstChild);
		this._options = [];

		// Add loading option
		this.select_elem.appendChild(create_loading_option());

/*
		// Create loading element
		this._loading_elem = this.select_elem.cloneNode(true);
		var o = this.document.createElement('OPTION');
		o.appendChild(this.document.createTextNode(this._loading_str));
		this._loading_elem.appendChild(o);

		// Hide actual select element
		if ( this.select_elem.parentNode != null )
			this.select_elem.parentNode.replaceChild(this._loading_elem, this.select_elem);
*/
	};

	/**
	 * Adds an option. Does not actually append an option element to the select
	 * element. (That happens all at once in end_loading.)
	 */
	this.add_option = function(value, key, selected)
	{
		this._options.push({k : key, v : value, s : selected});
	};

	/**
	 * Ends loading. This actually creates option elements from the added option
	 * key-value pairs, hides the fake "loading" select element, and shows the
	 * actual select element.
	 */
	this.end_loading = function()
	{
		// Create loading element
		this._loading_elem = this.select_elem.cloneNode(true);
		/*var o = this.document.createElement('OPTION');
		o.appendChild(this.document.createTextNode(this._loading_str));
		this._loading_elem.appendChild(o);*/

		// Hide actual select element
		if ( this.select_elem.parentNode != null )
			this.select_elem.parentNode.replaceChild(this._loading_elem, this.select_elem);


		// Remove all options
		while ( this.select_elem.firstChild != null )
			this.select_elem.removeChild(this.select_elem.firstChild);

		// Add options
		for ( var i = 0; i < this._options.length; i++ )
		{
			var o = this.document.createElement('OPTION');
			o.appendChild(this.document.createTextNode(this._options[i].v));
			o.value = this._options[i].k;
			this.select_elem.appendChild(o);
			o.selected = this._options[i].s;
		}
		/* // Doesn't work in IE:
		var html = '';
		for ( var i = 0; i < this._options.length; i++ )
		{
			var sel = this._options[i].s ? ' selected="selected"' : '';
			html += '<option value="' + this._options[i].k + '"' + sel + '>' + this._options[i].v + '</option>';
		}
		this.select_elem.innerHTML = html;
		*/
		this._options = [];


		// Show actual select element
		if ( this._loading_elem.parentNode != null )
			this._loading_elem.parentNode.replaceChild(this.select_elem, this._loading_elem);
	};
};

Util.Select.append_options = function append_options_to_select(el, options)
{
	function add_option(desc) {
		var opt = Util.Document.create_element(el.ownerDocument, 'option',
			{value: desc.v}, [desc.l]);
		el.appendChild(opt);
	}
	
	options.each(add_option);
}

// file Util.Selection.js
Util.Selection = function()
{
};

Util.Selection.CONTROL_TYPE = 1;
Util.Selection.TEXT_TYPE = 2;

/**
 * Gets the current selection in the given window.
 *
 * @param	window_obj	the window object whose selection is desired
 * @return				the current selection
 */
Util.Selection.get_selection = function get_window_selection(window_obj)
{
	if (!Util.is_valid_object(window_obj)) {
		throw new TypeError('Must pass an object to get_selection().');
	}
	
	if (typeof(window_obj.getSelection) == 'function') {
		return window_obj.getSelection();
	} else if (window_obj.document.selection) {
		return window_obj.document.selection;
	} else {
		throw new Util.Unsupported_Error('getting a window\'s selection');
	}
};

/**
 * Inserts a node at the current selection. The original contents of
 * the selection are is removed. A text node is split if needed.
 *
 * @param	sel				the selection
 * @param	new_node		the node to insert
 */
Util.Selection.paste_node = function paste_node_at_selection(sel, new_node)
{
	// Remember node or last child of node, for selection manipulation below
	if ( new_node.nodeType == Util.Node.DOCUMENT_FRAGMENT_NODE )
		var selectandum = new_node.lastChild;
	else
		var selectandum = new_node;

	// Actually paste the node
	var rng = Util.Range.create_range(sel);
	Util.Range.delete_contents(rng);
	//sel = Util.Selection.get_selection(self._loki.window);
	rng = Util.Range.create_range(sel);
	Util.Range.insert_node(rng, new_node);

	if ( Util.Browser.IE )
	{
		// We only want to do this pre IE 9 so we add this hack.
		if (parseInt(Util.Browser.get_version()) < 9)
		{
			rng.collapse(false);
			rng.select();
		}
		else
		{
			Util.Selection.collapse(sel, false);
		}
	}
	else
	// In Gecko, move selection after node
	{
		// Select all first, to avoid the annoying Gecko
		// quasi-random highlighting bug
		try // in case document isn't editable
		{
			selectandum.ownerDocument.execCommand('selectall', false, null);
			Util.Selection.collapse(sel, true); // to beg
		} catch(e) {}

		// Move the cursor where we want it
		Util.Selection.select_node(sel, selectandum); // works
		Util.Selection.collapse(sel, false); // to end
	}
};

/**
 * Removes all ranges from the given selection.
 *
 * @param	sel		the selection
 */
Util.Selection.remove_all_ranges = function clear_selection(sel)
{
	if (sel.removeAllRanges) {
		// Mozilla
		sel.removeAllRanges();
	} else if (sel.empty && !Util.is_boolean(sel.empty)) {
		sel.empty();
	} else {
		throw new Util.Unsupported_Error('clearing a selection');
	}
};

/**
 * Sets the selection to be the current range
 */
Util.Selection.select_range = function select_range(sel, rng)
{
	if (!Util.is_valid_object(sel)) {
		throw new TypeError('A selection must be provided to select_range().');
	} else if (!Util.is_valid_object(rng)) {
		throw new TypeError('A range must be provided to select_range().');
	}
	
	if (Util.is_function(sel.addRange, sel.removeAllRanges)) {
		sel.removeAllRanges();
		sel.addRange(rng);
	} else if (rng.select) {
		rng.select();
	} else {
		throw new Util.Unsupported_Error('selecting a range');
	}
};

/**
 * Selects the given node.
 */
Util.Selection.select_node = function(sel, node)
{
	// Mozilla
	try
	{
		// Select all first, to avoid the annoying Gecko
		// quasi-random highlighting bug
		try // in case document isn't editable
		{
			node.ownerDocument.execCommand('selectall', false, null);
			Util.Selection.collapse(sel, true); // to beg
		} catch(e) {}

		var rng = Util.Range.create_range(sel);
		rng.selectNode(node);
	}
	catch(e)
	{
		// IE
		try
		{
			mb('Util.Selection.select_node: in IE chunk: node', node);
			// This definitely won't work in most cases:
			/*
			if ( node.createTextRange != null )
				var rng = node.createTextRange();
			else if ( node.ownerDocument.body.createControlRange != null )
				var rng = node.ownerDocument.body.createControlRange();
			else
				throw('Util.Selection.select_node: node has neither createTextRange() nor createControlRange().');
			*/

			/*
			try
			{
				var rng = node.createTextRange();
			}
			catch(g)
			{
				var rng = node.createControlRange();
			}
			*/
			rng.select();
		}
		catch(f)
		{
			throw(new Error('Util.Selection.select_node: Neither the Gecko nor the IE way of selecting the node worked. ' +
							'When the Gecko way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
};


/**
 * Selects the contents of the given node. See 
 * Util.Range.select_node_contents for more information.
 */
Util.Selection.select_node_contents = function(sel, node)
{
	var range;
	try {
		range = Util.Range.create_range(sel);
	} catch (e) {
		if (e.name == 'Util.Unsupported_Error' && /collapsed/.test(e.message))
			range = Util.Document.create_range(node.ownerDocument);
		else
			throw e;
	}
	
	Util.Range.select_node_contents(range, node);
	Util.Selection.select_range(sel, range);
};

/**
 * Collapses the given selection.
 *
 * @param	to_start	boolean: true for start, false for end
 */
Util.Selection.collapse = function(sel, to_start)
{
	// Gecko
	try
	{
		if ( to_start )
			sel.collapseToStart();
		else
			sel.collapseToEnd();
	}
	catch(e)
	{
		// IE
		try
		{
			var rng = Util.Range.create_range(sel);
			if ( rng.collapse != null )
			{
				rng.collapse(to_start);
				rng.select();
			}
			// else it's a controlRange, for which collapsing doesn't make sense (?)
		}
		catch(f)
		{
			throw(new Error('Util.Selection.collapse: Neither the Gecko nor the IE way of collapsing the selection worked. ' +
							'When the Gecko way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}

	}
};

/**
 * Returns whether the given selection is collapsed.
 */
Util.Selection.is_collapsed = function selection_is_collapsed(sel)
{
	if (!Util.is_undefined(sel.isCollapsed))
		return sel.isCollapsed;
		
	if (sel.anchorNode && sel.focusNode) {
		return (sel.anchorNode == sel.focusNode &&
			sel.anchorOffset == sel.focusOffset);
	}
	
	var rng;
	
	try {
		rng = Util.Range.create_range(sel);
	} catch (e) {
		if (e.code == 1)
			return true;
		else
			throw e;
	}
	
	return Util.Range.is_collapsed(rng);
};

/**
 * Creates a bookmark for the current selection: a representation of the state
 * of the selection from which that state can be restored.
 *
 * The returned object should be treated as opaque except for one method:
 * restore(), which reselects whatever was selected when the bookmark was
 * created.
 *
 * @param {Window}	window	the window object
 * @param {Selection} sel	a window selection
 * @param {Range} [rng]	the selected range, if already known
 * @return {object} a bookmark object with a restore() method
 *
 * Algorithm from TinyMCE.
 */
Util.Selection.bookmark = function create_selection_bookmark(window, sel, rng)
{
	if (!rng) {
		// Create the range from the selection if one was not provided.
		// The range should be provided by Loki due to the quirk of Safari
		// explained in the function listen_for_context_changes within UI.Loki.
		
		rng = Util.Range.create_range(sel);
	}
	
	var doc = Util.Selection.get_document(sel, rng);
	var dim = Util.Document.get_dimensions(doc);
	var elem;
	var i;
	var other_range;
	
	if (doc != window.document) {
		throw new Error('The selection and window are for different ' +
			'documents.');
	}
	
	var pos = {
		x: dim.scroll.left,
		y: dim.scroll.top
	}
	
	// Try the native Windows IE text range implementation. This branch was not
	// in the original TinyMCE code.
	if (rng.getBookmark) {
		try {
			var mark_id = rng.getBookmark();
			return {
				range: rng,
				id: mark_id,
				
				restore: function restore_native_ie_bookmark()
				{
					this.range.moveToBookmark(this.id);
				}
			}
		} catch (e) {
			// Ignore the error and try the other methods.
		}
	}
	
	if (sel.addRange && doc.createRange && doc.createTreeWalker) {
		// W3C Traversal and Range, and Mozilla (et al.) selections
		
		// Returns a bookmark object that only re-scrolls to the marked position
		function position_only_bookmark(position)
		{
			return {
				window: window,
				pos: position,
				
				restore: function restore_position_only_bookmark()
				{
					if (typeof(console) == 'object') {
						var message = 'Position-only bookmark used.';
						
						if (console.warn)
							console.warn(message);
						else if (console.log)
							console.log(message);
					}
					
					this.window.scrollTo(this.pos.x, this.pos.y);
				}
			}
		}
		
		// Gets the currently selected element or the common ancestor element
		// for the selection's start and end. Taken directly from TinyMCE; I
		// don't understand all of what it's doing.
		function get_node()
		{
			var elem = rng.commonAncestorContainer;
			
			// Handle selection of an image or another control-like element
			// (e.g. an anchor).
			if (!rng.collapsed) {
				var wk = Util.Browser.WebKit;
				var same_container = (rng.startContainer == rng.endContainer ||
					(wk && rng.startContainer == rng.endContainer.parentNode));
				if (same_container) {
					if (wk || rng.startOffset - rng.endOffset < 2) {
						if (rng.startContainer.hasChildNodes()) {
							elem =
								rng.startContainer.childNodes[rng.startOffset];
						}
							
					}
				}
			}
			
			while (elem) {
				if (elem.nodeType == Util.Node.ELEMENT_NODE)
					return elem;
				elem = elem.parentNode;
			}
			
			return null;
		}
		
		// Image selection
		elem = get_node();
		if (elem && elem.nodeName == 'IMG') {
			// TinyMCE does this, though I don't know why.
			return position_only_bookmark(pos);
		}
		
		// Determines the textual position of a range relative to the body,
		// given the range's relevant start and end nodes. Only gives an answer
		// if start and end are both text nodes.
		function get_textual_position(start, end)
		{
			var bounds = {start: undefined, end: undefined};
			var walker = document.createTreeWalker(doc.body,
				NodeFilter.SHOW_TEXT, null, false);
			// Note that the walker will only retrieve text nodes.
			
			for (var p = 0, n = walker.nextNode(); n; n = walker.nextNode()) {
				if (n == start) {
					// Found the starting node in the tree under the root.
					// Store the position at which it was found.
					bounds.start = p;
				}
				
				if (n == end) { // not "else if" in case start == end.
					// Found the ending node in the tree under the root.
					// Store the position at which it was found and return the
					// boundaries.
					bounds.end = p;
					return bounds;
				}
				
				if (n.nodeValue)
					p += n.nodeValue.length;
			}
			
			return null; // Never did find the end node. Eek.
		}
		
		var bounds, start, end;
		if (Util.Selection.is_collapsed(sel)) {
			bounds = get_textual_position(sel.anchorNode, sel.focusNode);
			if (!bounds) {
				return position_only_bookmark(pos);
			}
			
			bounds.start += sel.anchorOffset;
			bounds.end += sel.focusOffset;
		} else {
			bounds = get_textual_position(rng.startContainer, rng.endContainer);
			if (!bounds) {
				return position_only_bookmark(pos);
			}
			
			bounds.start += rng.startOffset;
			bounds.end += rng.endOffset;
		}
		
		return {
			selection: sel,
			window: window,
			document: doc,
			body: doc.body,
			pos: pos,
			start: bounds.start,
			end: bounds.end,
			
			restore: function restore_w3c_bookmark()
			{
				var walker = this.document.createTreeWalker(this.body,
					NodeFilter.SHOW_TEXT, null, false);
				var bounds = {};
				var pos = 0;
				
				window.scrollTo(this.pos.x, this.pos.y);
				
				while (n = walker.nextNode()) { // assignment intentional
					if (n.nodeValue)
						pos += n.nodeValue.length;
					
					if (pos >= this.start && !bounds.startNode) {
						// This is the first time we've reached our marked
						// starting position. Record the starting node and
						// offset.
						bounds.startNode = n;
						bounds.startOffset = this.start -
							(pos - n.nodeValue.length);
					}
					
					if (pos >= this.end) { // not "else if" in case start == end
						// We've reached our ending position. Record the ending
						// node and offset and stop the search.
						bounds.endNode = n;
						bounds.endOffset = this.end -
							(pos - n.nodeValue.length);
						
						break;
					}
				}
				
				if (!bounds.endNode)
					return;
				
				var range = this.document.createRange();
				range.setStart(bounds.startNode, bounds.setOffset);
				range.setEnd(bounds.endNode, bounds.endOffset);
				
				this.selection.removeAllRanges();
				this.selection.addRange(range);
				
				if (!Util.Browser.Opera) // ???
					this.window.focus();
			}
		};
	} else if (rng.length && rng.item) {
		// Internet Explorer control range.
		
		elem = rng.item(0);
		
		// Find the index of the element in the NodeList of elements with its
		// tag name. I'm not sure why this is being done (perhaps it keeps the
		// selected Node object from being retained?), or if it works properly,
		// but I'm just porting the TinyMCE implementation.
		function get_element_index(elem)
		{
			var elements = doc.getElementsByTagName(elem.nodeName);
			for (var i = 0; i < elements.length; i++) {
				if (elements[i] == n)
					return i;
			}
		}
		
		i = get_element_index(elem);
		if (Util.is_blank(i)) {
			throw new Error('Cannot create bookmark; the selected element ' +
				'cannot be found in the editing document.');
		}
		
		return {
			window: window,
			tag: e.nodeName,
			index: i,
			pos: pos,
			
			restore: function restore_ie_control_range_bookmark()
			{
				var rng = doc.body.createControlRange();
				var elements = doc.getElementsByTagName(this.tag);
				var el = elements[this.index];
				if (!el) {
					throw new Error('Could not retrieve the bookmark target.');
				}
				
				this.window.scrollTo(this.pos.x, this.pos.y);
				rng.addElement(el);
				rng.select();
			}
		};
	} else if (!Util.is_blank(rng.length) && rng.moveToElementText) {
		// Internet Explorer text range
		
		// Figure out the position of the range. We do this in a slightly crude
		// way, by attempting to move the range backwards by a large number of
		// characters and seeing how many characters we actually moved.
		function find_relative_position(range, collapse_to_start)
		{
			range.collapse(collapse_to_start);
			// TextRange.move() returns the number of units actually moved
			return Math.abs(range.move('character', -0xFFFFFF));
		}
		
		// Establish a baseline by finding the position of the body.
		other_range = doc.body.createTextRange();
		other_range.moveToElementText(doc.body);
		var body_pos = find_relative_position(other_range, true);
		
		// Find how far the start side of the selection is from the selection's
		// base.
		other_range = rng.duplicate();
		var start_pos = find_relative_position(other_range, true);
		
		// Find the length of the range by finding how far the end side is
		// from the base and subtracting the start position from it.
		other_range = rng.duplicate();
		var length = find_relative_position(other_range, false) - start_pos;
		
		return {
			window: window,
			body: doc.body,
			start: start_pos - body_pos, // start pos. of range relative to body
			length: length,
			pos: pos,
			
			restore: function restore_ie_text_range_bookmark()
			{
				// Sanity check
				if (b.start < 0) {
					throw new Error('Invalid bookmark: starting point is ' +
						'negative.');
				}
				
				this.window.scrollTo(this.pos.x, this.pos.y);
				
				// Create a new range that we can select.
				var range = this.body.createTextRange();
				range.moveToElementText(this.body);
				range.collapse(true); // collapse to beginning of body
				
				// The move methods are relative, so we first move the range's
				// start forward to the bookmarked start position.
				range.moveStart('character', b.start);
				
				// In doing so, we also moved the end position forward by the
				// same amount (because you can't have a range's end occur
				// before its start). Now all we have to do is move the end of
				// the range forward by the bookmarked length.
				range.moveEnd('character', b.length);
				
				// Done!
				range.select();
			}
		};
	} else {
		throw new Util.Unsupported_Error('bookmarking a selection');
	}
};

/**
 * Gets the selection's owner document.
 * @param {Selection}	sel 
 * @param {Range}	rng	the selected range, if already known
 * @return {Document}
 */
Util.Selection.get_document = function get_selection_document(sel, rng)
{
	if (!rng) {
		// Create the range from the selection if one was not provided.
		// The range should be provided by Loki due to the quirk of Safari
		// explained in the function listen_for_context_changes within UI.Loki.
		
		rng = Util.Range.create_range(sel);
	}
	
	var elem = (sel.anchorNode // Mozilla (and friends) selection object
		|| rng.startContainer // W3C Range
		|| (rng.parentElement && rng.parentElement())); // IE TextRange
		
	if (!elem) {
		throw new Util.Unsupported_Error("getting a selection's owner " +
			"document");
	}
	
	return elem.ownerDocument;
}

/**
 * Returns the selected element, if any. Otherwise returns null.
 * Imitates FCK code.
 */
Util.Selection.get_selected_element = function(sel)
{
	if ( Util.Selection.get_selection_type(sel) == Util.Selection.CONTROL_TYPE )
	{
		// Gecko
		if ( sel.anchorNode != null && sel.anchorOffset != null )
		{
			return sel.anchorNode.childNodes[sel.anchorOffset];
		}
		// IE
		else
		{
			var rng = Util.Range.create_range(sel);
			if ( rng != null && rng.item != null )
				return rng.item(0);
		}
	}
};

/**
 * Gets the type of currently selection.
 * Imitates FCK code.
 */
Util.Selection.get_selection_type = function(sel)
{
	var type;

	// IE
	if ( sel.type != null )
	{
		if ( sel.type == 'Control' )
			type = Util.Selection.CONTROL_TYPE;
		else
			type = Util.Selection.TEXT_TYPE;
	}

	// Gecko
	else
	{
		type = Util.Selection.TEXT_TYPE;

		if ( sel.rangeCount == 1 )
		{
			var rng = sel.getRangeAt(0);
			if ( rng.startContainer == rng.endContainer && ( rng.endOffset - rng.startOffset ) == 1 )
			{
				type = Util.Selection.CONTROL_TYPE;
			}
		}
	}

	return type;
};

/**
 * Moves the cursor to the end (but still inside) the given
 * node. This is useful to call after performing operations 
 * on nodes.
 */
Util.Selection.move_cursor_to_end = function(sel, node)
{
	// Move cursor
	var rightmost = Util.Node.get_rightmost_descendent(node);
	if ( rightmost.nodeName == 'BR' && rightmost.previousSibling != null )
		rightmost = Util.Node.get_rightmost_descendent(rightmost.previousSibling);
	mb('rightmost', rightmost);

	// XXX This doesn't really work right in IE, although it is close
	// enough for now
	if ( rightmost.nodeType == Util.Node.TEXT_NODE )
		Util.Selection.select_node(sel, rightmost);
	else
		Util.Selection.select_node_contents(sel, rightmost);

	Util.Selection.collapse(sel, false); // to end
};

// file Util.State_Machine.js
/**
 * @constructor Creates a new state machine.
 * @class A "state machine"; an organized way of tracking discrete software states.
 * @author Eric Naeseth
 */
Util.State_Machine = function(states, starting_state, name)
{
	this.states = states || {};
	// I have no idea why this helps keep the machine in sync, but it does:
	this.state = {
		real_state: null,
		
		get: function()
		{
			return this.real_state;
		},
		
		set: function(new_state)
		{
			this.real_state = new_state;
		}
	};
	this.name = name || null;
	this.changing = false;
	this.lock = new Util.Lock(this.name);
	
	this.determine_name = function(state)
	{
		if (!state)
			return '[null]';
		
		for (var name in this.states) {
			if (this.states[name] == state)
				return name;
		}
		
		return '[unknown]';
	}
	
	this.change = function(new_state)
	{
		if (typeof(new_state) == 'string') {
			if (!this.states[new_state])
				throw new Util.State_Machine.Error('Unknown state "' + new_state + '".');
			new_state = this.states[new_state];
		}
		
		this.lock.acquire();
		try {
			var old_state = this.state.get();

			if (old_state) {
				old_state.exit(new_state);
			}

			this.state.set(new_state);
			new_state.enter(old_state);
		} finally {
			this.lock.release();
		}
	}
	
	var machine = this;
	for (var name in this.states) {
		var s = this.states[name];
		
		s.enter = (function(old_entry) {
			return function state_entry_wrapper() {
				if (arguments.length == 0)
					return machine.change(this);
				return old_entry.apply(this, arguments);
			}
		})(s.enter);
		
		s.machine = this;
	}
	
	if (starting_state)
		this.change(starting_state);
}

Util.State_Machine.Error = function(message)
{
	Util.OOP.inherits(this, Error, message);
	this.name = 'Util.State_Machine.Error';
} 
// file Util.Tabset.js
/**
 * Creates a chunk containing a tabset.
 * @constructor
 *
 * @param	params	an object with the following properties:
 *                  <ul>
 *                  <li>document - the DOM document object which will own the created DOM elements</li>
 *                  <li>id - (optional) the id of the DOM tabset element</li>
 *                  </ul>
 *
 * @class Represents a tabset.
 */
Util.Tabset = function(params)
{
	var self = this;
	this.document = params.document;
	this.id = params.id;

	var _tabs = {}; // each member of tabs should have a tab_elem and a tabpanel_elem 
	var _name_of_selected_tab;
	var _select_listeners = [];

	// Create tabset element
	this.tabset_elem = this.document.createElement('DIV');
	Util.Element.add_class(this.tabset_elem, 'tabset');
	if ( this.id != null )
		this.tabset_elem.setAttribute('id', this.id);

	// Create tabs container
	var _tabs_chunk = this.document.createElement('DIV');
	Util.Element.add_class(_tabs_chunk, 'tabs_chunk');

	// Create and append force_clear_for_ie element
	var _force_clear_for_ie_elem = this.document.createElement('DIV');
	Util.Element.add_class(_force_clear_for_ie_elem, 'force_clear_for_ie');
	_tabs_chunk.appendChild(_force_clear_for_ie_elem);

	// Create and append tabs ul
	var _tabs_ul = this.document.createElement('UL');
	_tabs_chunk.appendChild(_tabs_ul);

	// Create tabpanels container
	var _tabpanels_chunk = this.document.createElement('DIV');
	Util.Element.add_class(_tabpanels_chunk, 'tabpanels_chunk');

	// Append containers to tabset
	this.tabset_elem.appendChild(_tabs_chunk);
	this.tabset_elem.appendChild(_tabpanels_chunk);


	// Methods

	/**
	 * Adds a tab to the tabset.
	 *
	 * @param	name	the new tab's name
	 * @param	label	the new tab's label
	 */
	this.add_tab = function(name, label)
	{
		// Make entry in list of tabs
		_tabs[name] = {};
		var t = _tabs[name];

		// Create tab element ...
		t.tab_elem = this.document.createElement('LI');
		t.tab_elem.id = t.tab_id = name + '_tab';
		Util.Element.add_class(t.tab_elem, 'tab_chunk');

		// ... and its anchor ...
		var anchor_elem = this.document.createElement('A');
		anchor_elem.href = 'javascript:void(0);';
		t.tab_elem.appendChild(anchor_elem);

		// ... and its label ...
		var label_node = this.document.createTextNode(label);
		anchor_elem.appendChild(label_node);

		// ... with event listeners
		Util.Event.add_event_listener(anchor_elem, 'click', function() { self.select_tab(name); });
		Util.Event.add_event_listener(t.tab_elem, 'mouseover', function() { Util.Element.add_class(t.tab_elem, 'hover'); });
		Util.Event.add_event_listener(t.tab_elem, 'mouseout', function() { Util.Element.remove_class(t.tab_elem, 'hover'); });

		// Create tabpanel element
		t.tabpanel_elem = this.document.createElement('DIV');
		t.tabpanel_elem.id = t.tabpanel_id = name + '_tabpanel';
		Util.Element.add_class(t.tabpanel_elem, 'tabpanel_chunk');

		// Append tab and tabpanel elements
		_tabs_ul.appendChild(t.tab_elem);
		_tabpanels_chunk.appendChild(t.tabpanel_elem);

		// If this is the first tab to be added, select it
		// by default
		if ( _name_of_selected_tab == null )
		{
			this.select_tab(name);
		}
		// Otherwise, re-select the selected tab, in order
		// to refresh the the display
		else
		{
			this.select_tab(this.get_name_of_selected_tab());
		}
	};

	/**
	 * Gets the element of the tabpanel whose
	 * name is given. Then children can be 
	 * appended there.
	 *
	 * @param	name	the tabpanel's name
	 */
	this.get_tabpanel_elem = function(name)
	{
		if ( _tabs[name] == null )
			throw('Util.Tabset.get_tabpanel_elem: no such name.');

		return _tabs[name].tabpanel_elem;
	};

	/**
	 * Selects the tab whose name is given.
	 *
	 * @param	name	the tabpanel's name
	 */
	this.select_tab = function(name)
	{
		if ( _tabs[name] == null )
			throw('Util.Tabset.select_tab: no such name.');

		var old_name = _name_of_selected_tab;

		// Hide all tabs and tabpanels
		for ( var i in _tabs )
		{
			Util.Element.remove_class(_tabs[i].tab_elem, 'selected');
			Util.Element.remove_class(_tabs[i].tabpanel_elem, 'selected');
		}

		// Show selected tab and tabpanel
		Util.Element.add_class(_tabs[name].tab_elem, 'selected');
		Util.Element.add_class(_tabs[name].tabpanel_elem, 'selected');

		// Remember name
		_name_of_selected_tab = name;

		// Fire listeners
		for ( var i = 0; i < _select_listeners.length; i++ )
			_select_listeners[i](old_name, _name_of_selected_tab);
	};

	/**
	 * Gets the name of the currently selected tab. 
	 */
	this.get_name_of_selected_tab = function()
	{
		if ( _name_of_selected_tab == null )
			throw('Util.Tabset.get_name_of_selected_tab: no tab selected.');

		return _name_of_selected_tab;
	};

	/**
	 * Adds a listener to be fired whenever a different tab is selected. 
	 * Each listener will receive old_name and new_name as arguments.
	 */
	this.add_select_listener = function(listener)
	{
		_select_listeners.push(listener);
	};
};

// file Util.URI.js
/**
 * Does nothing.
 *
 * @class Container for functions relating to URIs.
 */
Util.URI = function()
{
	throw new Error("Util.URI objects may not be constructed.");
};

/**
 * Determines whether or not two URI's are equal.
 *
 * Special handling that this function performs:
 *	- Does not distinguish between http and https.
 * 	- Domain-relative links are assumed to be relative to the current domain.
 * @param {string|object}
 * @param {string|object}
 * @return {boolean}
 */
Util.URI.equal = function uri_equal(a, b)
{
	var normalize = Util.URI.normalize;
	
	a = normalize(a);
	b = normalize(b);
	
	if (!Util.Object.equal(this.parse_query(a.query), this.parse_query(b.query)))
		return false;
	
	return (a.scheme == b.scheme && a.host == b.host && a.port == b.port &&
		a.user == b.user && a.password == b.password && a.path == b.path &&
		a.fragment == b.fragment);
}

/**
 * Parses a URI into its constituent parts.
 */
Util.URI.parse = function parse_uri(uri)
{
	var match = Util.URI.uri_pattern.exec(uri);
	
	if (!match) {
		throw new Error('Invalid URI: "' + uri + '".');
	}
	
	var authority_match = (typeof(match[4]) == 'string' && match[4].length)
		? Util.URI.authority_pattern.exec(match[4])
		: [];
	
	// this wouldn't need to be so convoluted if JScript weren't so crappy!
	function get_match(source, index)
	{
		try {
			if (typeof(source[index]) == 'string' && source[index].length) {
				return source[index];
			}
		} catch (e) {
			// ignore and return null below
		}
		
		return null;
	}
	
	var port = get_match(authority_match, 7);
	var host = get_match(authority_match, 5);
	
	return {
		scheme: get_match(match, 2),
		authority: get_match(match, 4),
		user: get_match(authority_match, 2),
		password: get_match(authority_match, 4),
		host: host,
		port: (port ? Number(port) : port),
		path: get_match(match, 5) || (host ? '/' : null),
		query: get_match(match, 7),
		fragment: get_match(match, 9)
	};
}

/**
 * Checks to see if a URI is a URN (such as a mailto:) address.
 */
Util.URI.is_urn = function uri_is_urn(uri) {
	if (typeof(uri) != 'object')
		uri = Util.URI.parse(uri);
	
	return (uri.scheme && uri.path && !uri.authority);
}

/**
 * Parses a query fragment into its constituent variables.
 */
Util.URI.parse_query = function parse_query(fragment)
{
	var vars = {};
	
	if (!fragment)
		return vars;
	
	fragment.replace(/^\?/, '').split(/[;&]/).each(function (part) {
		var keyvalue = part.split('='); // we can't simply limit the number of
		                                // splits or we'll use any parts beyond
		                                // the first =
		var key = keyvalue.shift();
		var value = keyvalue.join('='); // undo any damage from the split
		
		vars[key] = value;
	});
	
	return vars;
}

/**
 * Builds a query fragment from an object.
 */
Util.URI.build_query = function build_query(variables)
{
	var parts = [];
	
	Util.Object.enumerate(variables, function(name, value) {
		parts.push(name + '=' + value);
	});
	
	return parts.join('&');
}

/**
 * Builds a URI from a parsed URI object.
 */
Util.URI.build = function build_uri_from_parsed(parsed)
{
	var uri = '';
	if (parsed.scheme)
		uri = parsed.scheme + ':'
	
	if (parsed.authority) {
		uri += '//' + parsed.authority;
	} else if (parsed.host) {
		uri += '//';
		if (parsed.user) {
			uri += parsed.user;
			if (parsed.password)
				uri += ':' + parsed.password;
			uri += '@';
		}
		
		uri += parsed.host;
		if (parsed.port)
			uri += ':' + parsed.port;
	}
	
	if (parsed.path)
		uri += parsed.path;
	if (parsed.query)
		uri += '?' + parsed.query;
	if (parsed.fragment)
		uri += '#' + parsed.fragment;
	
	return uri;
}

/**
 * Safely appends query parameters to an existing URI.
 * Previous occurrences of a query parameter are replaced.
 */
Util.URI.append_to_query = function append_params_to_query(uri, params)
{
	var parsed = Util.URI.parse(uri);
	var query_params = Util.URI.parse_query(parsed.query);
	
	Util.Object.enumerate(params, function(name, value) {
		query_params[name] = value;
	});
	
	parsed.query = Util.URI.build_query(query_params);
	return Util.URI.build(parsed);
}

/**
 * Normalizes a URI, expanding it to an absolute form and removing redundant
 * port information.
 * @param {string|object}	uri	a parsed URI object or a URI string
 * @param {string|object}	[base]	an explicit base URI to use
 * @return {object}	the parsed normalized URI
 */
Util.URI.normalize = function normalize_uri(uri, base)
{
	var path_parts, i;
	
	if (typeof(base) == 'string') {
		base = Util.URI.parse(base);
	} else {
		if (!base)
			base = Util.URI.parse((window.top || window).location);
		else if (Util.is_object(base))
			base = Util.Object.clone(base);
		else if (typeof(base) != 'object' || typeof(base.path) == 'undefined')
			throw new TypeError("Invalid base URI.");
		
		// take the path's basename and add a trailing slash:
		base.path = base.path.split('/').slice(0, -1).join('/') + '/';
	}
	
	if (typeof(uri) != 'string') {
		if (uri.scheme === undefined)
			throw new TypeError("Invalid URI object.");
		uri = Util.Object.clone(uri);
	} else {
		uri = Util.URI.parse(uri);
	}
	
	if (!uri.scheme && uri.scheme != '') {
		uri.scheme = base.scheme;
	} else if (uri.scheme = 'https') {
		if (uri.port == 443)
			uri.port = null;
	}
	
	if (!uri.host)
		uri.host = base.host;
	if (typeof(uri.host) == 'string')
		uri.host = uri.host.toLowerCase();
	
	if (uri.path.charAt(0) != '/' && uri.host == base.host) {
		uri.path = base.path + uri.path;
	}
	
	path_parts = uri.path.split('/');
	uri.path = [];
	for (i = 0; i < path_parts.length; i++) {
		if (path_parts[i] == '.') {
			continue;
		} else if (path_parts[i] == '..') {
			if (uri.path.length <= 1) { // first "/" creates an empty part
				throw new Error('Invalid relative URI: too many parent ' +
					'directory references (..).');
			}
			uri.path.pop();
		} else {
			uri.path.push(path_parts[i]);
		}
	}
	uri.path = uri.path.join('/');
		
	if (uri.scheme == 'http' && uri.port == 80)
		uri.port = null;
		
	return uri;
}

/**
 * Strips leading "https:" or "http:" from a uri, to avoid warnings about
 * mixing https and http. E.g.: https://apps.carleton.edu/asdf ->
 * //apps.carleton.edu/asdf.
 * 
 * @param	{string}	uri			the uri
 */
Util.URI.strip_https_and_http = function strip_https_and_http(uri)
{
	return (typeof(uri) == 'string')
		? uri.replace(new RegExp('^https?:', ''), '')
		: null;
};

/**
 * Extracts the domain name from the URI.
 * @param	uri	the URI
 * @return	the domain name or null if an invalid URI was provided
 */
Util.URI.extract_domain = function extract_domain_from_uri(uri)
{
	var match = Util.URI.uri_pattern.exec(uri);
	return (!match || !match[4]) ? null : match[4].toLowerCase();
};

/**
 * Makes the given URI relative to its domain
 * (i.e. strips the protocol and domain).
 */
Util.URI.make_domain_relative = function make_uri_domain_relative(uri)
{
	return uri.replace(Util.URI.protocol_host_pattern, '');
}

Util.URI.uri_pattern =
	new RegExp('^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\\?([^#]*))?(#(.*))?',
	'i');
Util.URI.authority_pattern =
	new RegExp('^(([^:@]+)(:([^@]+))?@)?([^:]+)(:(\\d+))?$');
Util.URI.protocol_host_pattern =
	new RegExp('^(([^:/?#]+):)?(//([^/?#]*))?', 'i'); 
// file Util.Unsupported_Error.js
/**
 * @class Indicates that an operation is unsupported by the browser.
 * @constructor
 * @param {string}	call
 * @author Eric Naeseth
 */
Util.Unsupported_Error = function UnsupportedError(call)
{
	var error = new Error('No known implementation of ' + call +
		' is available from this browser.');
	error.name = 'Util.Unsupported_Error';
	return error;
}

// file Util.Window.js
/**
 * Declares instance variables. <code>this.window</code>,
 * <code>this.document</code>, and <code>this.body</code> are not
 * initialized until the method <code>this.open</code> is called.
 *
 * @constructor
 *
 * @class A wrapper to <code>window</code>. Provides extra and
 * cross-browser functionality.
 */
Util.Window = function()
{
	this.window = null;
	this.document = null;
	this.body = null;
};
Util.Window.FORCE_SYNC = true;
Util.Window.DONT_FORCE_SYNC = false;

/**
 * Opens a window.
 *
 * @param	uri				(optional) the uri of the page to open in the
 *							window. Defaults to empty string, with the result
 *							that no page is initially opened in the window.
 *							But NOTE: if you leave this blank, if this is called 
 * 							from a page under https IE will complain about mixing 
 *							https and http.
 * @param	window_name		(optional) the name of the window. Defaults to
 *							'_blank'.
 * @param	window_options	(optional) a string of options as to how the window
 *                          is displayed. This is the same string as is passed
 *                          to window.open. Defaults to a fairly minimal set of
 *                          options.
 * @param	force_async		(optional) if Util.Window.FORCE_ASYNC, forces the 
 * 							function to write over the document at uri with a blank 
 * 							page and close the new document, even if uri isn't ''. This is
 *							useful if we're behind https, since setting the uri
 *							to '' from an https page causes IE to warn the user
 *							about mixing https and http.
 * @return					returns false if we couldn't open the window (e.g.,
 *							if it was blocked), or true otherwise
 */
Util.Window.prototype.open = function(uri, window_name, window_options, force_sync)
{
	// Provide defaults for optional arguments
	if ( uri == null )
		uri = '';

	if ( window_name == null )
		window_name = '_blank';

	if ( window_options == null )
		window_options = 'status=1,scrollbars=1,resizable,width=600,height=300';
	
	if ( force_sync == null )
		force_sync = Util.Window.DONT_FORCE_SYNC;

	// Open window
	this.window = window.open(uri, window_name, window_options);

	// Make sure the window opened successfully
	if ( this.window == null )
	{
		alert('I couldn\'t open a window. Please disable your popup blocker for this page. Then give me another try.');
		return false;
	}

	// Set up reference to window's document
	this.document = this.window.document;

	// By writing the document's initial HTML out ourself and then
	// closing the document (that's the important part), we
	// essentially make the "open" method synchronous rather than
	// asynchronous. And if we're just trying to open an empty window,
	// this is not dangerous. (It might be dangerous otherwise, since
	// a synchronous "open" method that involved a request to the web
	// server might cause the script to effectively hang if the web
	// server didn't respond.)
	//
	// If we are given a URI to request from the web server, we skip
	// this, so the "open" method is asynchronous, so before we do
	// anything with the window's contents, we need to make sure that
	// the content document has loaded. One way to do this is to add a
	// "load" event listener, and then do everything we want to in the
	// listener. Beware, though: this can cause extreme 
	// cross-browser pains.
	if ( uri == '' || force_sync == Util.Window.FORCE_SYNC )
	{
		this.document.write('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' +
							'<html><head><title></title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head><body>' +
							'<div id="util_window_error">You tried to reload a dialog page that exists only ephemerally. Please close the dialog and open it again.</div>' +
							// for debugging; turn off when live (make sure to get the event listener below, too):
							//'<div><a id="util_window_alert" href="#" onclick="return false;">View virtual source</a></div><hr />' + // the event which pops up the source is added below
							'<script type="text/javascript">if (window.opener) window.opener._loki_dialog_postback(window);</script>' +
							'</body></html>');
		this.document.close();

		// We can only set a reference to the body element if the
		// document has finished loading, and here we can only be sure
		// of that across browsers if we've called document.close().
		//
		// One upshot is that if we are given a URI to load in the
		// window, we have to wait until the load event is fired to
		// get a reference to the body tag, and I don't want to muck
		// around with that here. So in that case we just don't get
		// such a reference here. (Notice that the assignment below is
		// still in the if block.) You have to get the reference
		// yourself if you want it.
		this.body = this.document.getElementsByTagName('BODY').item(0);

		// We also add an onclick event to view source which uses
		// Util.Window.alert, not window.alert
		//var self = this;
		//Util.Event.add_event_listener(this.document.getElementById('util_window_alert'), 'click', function() { Util.Window.alert(self.document.getElementsByTagName('html').item(0).innerHTML); });

		// We need the error message because if people do things like
		// press refresh, they just get what's written by
		// document.write above, and that is very confusing. The
		// following line hides the message except after they've
		// pressed reload, because none of this is run on reload.
		this.document.getElementById('util_window_error').style.display = 'none';

// 		// for debugging; turn off when live:
// 		var a = this.document.createElement('DIV');
// 		a.appendChild( this.document.createTextNode('View virtual source') );
// 		a.href = '#';
// 		var self = this;
// 		var handler = function() { Util.Window.alert(self.body.innerHTML); }
// 		Util.Event.add_event_listener(a, 'click', function() { handler(); });
// 		this.body.appendChild(a);
	}

	return true; // success
};


Util.Window.prototype.add_load_listener = function(listener)
{
		mb('Util.Window.add_load_listener: this', this);
	Util.Event.add_event_listener(this.document, 'load', listener);
};


/**
 * Alerts a message. Supercedes window.alert, since allows scrolling,
 * accepts document nodes rather than just strings, etc.
 *
 * @param	alertandum	the string or document chunk (i.e., node with
 *                      all of its children) to alert
 * @static
 */ 
Util.Window.alert = function(alertandum)
{
	// Open window
	var alert_window = new Util.Window;
	alert_window.open('', '_blank', 'status=1,scrollbars=1,resizable,width=600,height=300');

	// Add the alertatandum to a document chunk
	var doc_chunk = alert_window.document.createElement('DIV'); // use a div because document frags don't work as expected on IE
	if ( typeof(alertandum) == 'string' )
	{
		var text = alertandum.toString();
		var text_arr = text.split("\n");
		for ( var i = 0; i < text_arr.length; i++ )
		{
			doc_chunk.appendChild(
				alert_window.document.createElement('DIV')
			).appendChild(
				alert_window.document.createTextNode(text_arr[i].toString())
			);
		}
	}
	else
	{
		// FIXME: leftover debugging crud
		// alert(alertandum.firstChild.firstChild.firstChild.nodeValue);
		doc_chunk.appendChild(
			Util.Document.import_node(alert_window.document, alertandum, true)
		);
		alert(doc_chunk.firstChild.nodeName);
	}

	// Append the document chunk to the window
	alert_window.body.appendChild(doc_chunk);
};

Util.Window.alert_debug = function(message)
{
	var alert_window = new Util.Window;
	alert_window.open('', '_blank', 'status=1,scrollbars=1,resizable,width=600,height=300');
	
	var text_chunk = alert_window.document.createElement('P');
	text_chunk.style.fontFamily = 'monospace';
	text_chunk.appendChild(alert_window.document.createTextNode(message));
	alert_window.body.appendChild(text_chunk);
} 
// file UI.js
/**
 * Container for objects related to user interface.
 */
function UI()
{
};

// file UI.Activity.js
/**
 * @class Displays an indicator that reassures the user that
 * work of some sort is being done in the background.
 * @author Eric Naeseth
 */
UI.Activity = function(base, document, kind, text) {
	var helper = new Util.Document(document);
	if (base.base_uri) base = base.base_uri;
	
	var kinds = {
		small: function()
		{
			var container = helper.create_element('SPAN', {
				className: 'progress_small'
			}, [helper.create_element('IMG', {src: base + 'images/loading/small.gif'})]);
			
			if (text)
				container.appendChild(document.createTextNode(' ' + text));
			
			return container;
		},
		
		arrows: function()
		{
			var container = helper.create_element('SPAN', {
				className: 'progress_arrows'
			}, [helper.create_element('IMG', {src: base + 'images/loading/arrows.gif'})]);
			
			if (text)
				container.appendChild(document.createTextNode(' ' + text));
			
			return container;
		},
		
		large: function()
		{
			var image = helper.create_element('IMG', {
				src: base + 'images/loading/large.gif'
			});
			var container = helper.create_element('DIV', {
				className: 'progress_large'
			}, [image]);
			
			if (text) {
				container.appendChild(helper.create_element('P', {}, [text]));
			}
			
			return container;
		},
		
		bar: function()
		{
			return helper.create_element('IMG', {
				src: base + 'images/loading/bar.gif'
			});
		},
		
		textual: function()
		{
			var el = helper.create_element('SPAN', {className: 'progress_text'});
			el.innerHTML = text || 'Loading&hellip;';
			return el;
		}
	}
	
	function invalid_type() {
		throw new Error('"' + kind + '" is not a valid kind of activity indicator.');
	}
	
	this.indicator = (kinds[kind] || invalid_type)();
	
	/**
	 * Convenience method for appending the indicator as a child of a parent container.
	 */
	this.insert = function(container)
	{
		container.appendChild(this.indicator);
	}
	
	/**
	 * Convenience method for replacing the indicator with actual content.
	 */
	this.replace = function(replacement)
	{
		if (!this.indicator.parentNode)
			return;
		
		this.indicator.parentNode.replaceChild(replacement, this.indicator);
	}
	
	/**
	 * Convenience method for removing the indicator.
	 */
	this.remove = function()
	{
		if (!this.indicator.parentNode)
			return;
		
		this.indicator.parentNode.removeChild(this.indicator);
	}
} 
// file UI.Align_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Changes the alignment of block-level elements.
 */
UI.Align_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.init = function(loki)
	{
		this._loki = loki;
		this._paragraph_helper = (new UI.Paragraph_Helper()).init(this._loki);
		return this;
	};
	
	function get_alignable_elements()
	{
		var elements;
		var selection;
		var range;
		var bounds;
		
		function find_blocks(scan_ancestors) {
			return Util.Range.find_nodes(bounds, Util.Node.is_block,
				scan_ancestors);
		}
		
		// Ensure that there's a paragraph; that we're not directly within the
		// document's body.
		self._paragraph_helper.possibly_paragraphify();
		
		selection = Util.Selection.get_selection(self._loki.window);
		range = Util.Range.create_range(selection);
		bounds = Util.Range.get_boundary_blocks(range, true);
		
		// First, see if there are any block-level elements within the selected
		// range.
		elements = find_blocks(false);
		if (elements.length)
			return elements;
		
		// Find any that are ancestors of the range.
		return find_blocks(true);
	};

	this.is_alignable = function selection_is_alignable()
	{
		try {
			return !!get_alignable_elements().length;
		} catch (e) {
			return false;
		}
	};
	
	this.align = function align_selection(position)
	{
		var elements = get_alignable_elements();
		
		position = position.toLowerCase();
		if (!['left', 'center', 'right', 'justify'].contains(position)) {
			throw new Error('Invalid position {' + position + '}.');
		}
		
		if (!elements.length)
			return;
		elements.each(function align_element(el) {
			var w = (self._loki.window.document == el.ownerDocument)
				? self._loki.window
				: Util.Node.get_window(el);
			
			var align = Util.Element.get_computed_style(w, el).textAlign;
			if (align.toLowerCase() == position)
				return;
			
			if (position == 'left') {
				// Try simply removing the inline style, since "left" is
				// probably the default. Check it momentarily, and if the
				// alignment isn't really left, set it explicitly.
				el.style.textAlign = '';
				if (el.style.cssText.length == 0)
					el.removeAttribute('style');
				(function verify_element_alignment() {
					var a = Util.Element.get_computed_style(w, el).textAlign;
					a = a.toLowerCase();
					// For Mozilla, the default alignment is actually "start",
					// which is equivalent to left for our purposes.
					if (a != position && !(position == 'left' && a == 'start'))
						el.style.textAlign = position;
				}).defer();
			} else {
				el.style.textAlign = position;
			}
		});
	};

	this.align_left = function align_selection_to_left()
	{
		this.align('left');
	};

	this.align_center = function align_selection_to_center()
	{
		this.align('center');
	};

	this.align_right = function align_selection_to_right()
	{
		this.align('right');
	};
};

// file UI.Align_Menugroup.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing an align menugroup. 
 */
UI.Align_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._align_helper = (new UI.Align_Helper).init(this._loki);
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var menuitems = [];

		var self = this;
		if ( this._align_helper.is_alignable() )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Align left',
				//listener : function() { self._loki.exec_command('JustifyLeft'); }
				listener : function() { self._align_helper.align_left(); }
			}) );
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Align center',
				//listener : function() { self._loki.exec_command('JustifyCenter'); }
				listener : function() { self._align_helper.align_center(); }
			}) );
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Align right',
				//listener : function() { self._loki.exec_command('JustifyRight'); }
				listener : function() { self._align_helper.align_right(); }
			}) );
		}

		return menuitems;
	};
};

// file UI.Anchor_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for inserting an anchor.
 */
UI.Anchor_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'anchor.png';
	this.title = 'Insert named anchor';
	this.click_listener = function() { self._anchor_helper.open_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._anchor_helper = (new UI.Anchor_Helper).init(this._loki);
		return this;
	};
};

// file UI.Anchor_Dialog.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class An anchor dialog window.
 */
UI.Anchor_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);

	this._dialog_window_width = 615;
	this._dialog_window_height = 200;

	this._set_title = function()
	{
		if ( !this._initially_selected_item )
			this._dialog_window.document.title = 'Insert anchor';
		else
			this._dialog_window.document.title = 'Edit anchor';
	};

	this._populate_main = function()
	{
		this._append_anchor_chunk();
		this._append_submit_and_cancel_chunk();
		this._append_remove_anchor_chunk();
		var self = this;
		setTimeout(function () { self._resize_dialog_window(false, true); }, 1000);
		//this._resize_dialog_window(false, true);
	};

	this._append_anchor_chunk = function()
	{
		this._anchor_input = this._dialog_window.document.createElement('INPUT');
		this._anchor_input.setAttribute('size', '40');
		this._anchor_input.id = 'anchor_input';

		var anchor_label = this._dialog_window.document.createElement('LABEL');
		anchor_label.innerHTML = 'Anchor name: ';
		anchor_label.htmlFor = 'anchor_input';

		var anchor_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(anchor_div, 'field');
		anchor_div.appendChild(anchor_label);
		anchor_div.appendChild(this._anchor_input);

		var long_label = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(long_label, 'label');
		long_label.appendChild( this._dialog_window.document.createTextNode('Please provide a descriptive name for this anchor. The name should begin with a letter (a-z). The rest of the name can contain letters, numbers, and these characters: hyphens (-), underscores (_), colons(:), and periods(.). Other characters can\'t be used in an anchor name.') );

		var h1 = this._dialog_window.document.createElement('H1');
		if ( !this._initially_selected_item )
			h1.innerHTML = 'Create anchor';
		else
			h1.innerHTML = 'Edit anchor';

		var fieldset = new Util.Fieldset({legend : '', document : this._dialog_window.document});
		fieldset.fieldset_elem.appendChild(anchor_div);
		fieldset.fieldset_elem.appendChild(long_label);

		this._main_chunk.appendChild(h1);
		this._main_chunk.appendChild(fieldset.chunk);
	};

	this._append_remove_anchor_chunk = function()
	{
		var button = this._dialog_window.document.createElement('BUTTON');
		button.setAttribute('type', 'button');
		button.appendChild( this._dialog_window.document.createTextNode('Remove anchor') );

		var self = this;
		var listener = function()
		{
			/* not really necessary for just an anchor
			if ( confirm('Really remove anchor? WARNING: This cannot be undone.') )
			{
			*/
				self._remove_listener();
				self._dialog_window.window.close();
			//}
		}
		Util.Event.add_event_listener(button, 'click', listener);

		// Setup their containing chunk
		var chunk = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(chunk, 'remove_chunk');
		chunk.appendChild(button);

		// Append the containing chunk
		//this._dialog_window.body.appendChild(chunk);
		this._root.appendChild(chunk);
	};

	this._apply_initially_selected_item = function()
	{
		if ( this._initially_selected_item != null )
		{
			this._anchor_input.value = this._initially_selected_item.name;
		}
	};

	this._internal_submit_listener = function()
	{
		// Get anchor name 
		var anchor_name = this._anchor_input.value;
		if ( anchor_name.replace( new RegExp('[a-zA-Z0-9_:.-]+', ''), '') != '' ||
			 !anchor_name.match( new RegExp('^[a-zA-Z]', '') ) )
		{
			this._dialog_window.window.alert('You haven\'t entered a valid name. The name should begin with a Roman letter, and be followed by any number of digits, hyphens, underscores, colons, periods, and Roman letters. The name should include no other characters.');
			return false;
		}

		this._external_submit_listener({name : anchor_name});
		this._dialog_window.window.close();
	};
};

// file UI.Anchor_Double_Click.js
UI.Anchor_Double_Click = function AnchorDoubleClick() {
	Util.OOP.inherits(this, UI.Double_Click);
	this.helper = null;
	
	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this.helper = (new UI.Anchor_Helper).init(loki);
		return this;
	};
	
	this.double_click = function() {
		if (this.helper.is_selected())
			this.helper.open_dialog();
	};
};

// file UI.Anchor_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an anchor. Contains code
 * common to both the button and the menu item.
 */
UI.Anchor_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.init = function(loki)
	{
		this._loki = loki;
		this._masseuse = (new UI.Anchor_Masseuse()).init(this._loki);
		return this;
	};

	this.is_selected = function()
	{
		return !!this.get_selected_item();
	};
	
	function _get_selected_placeholder()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var range = Util.Range.create_range(sel);
	 	var found = Util.Range.find_nodes(range, self._masseuse.is_placeholder,
			true);
			
		if (found.length == 0) {
			return null;
		} else if (found.length > 1) {
			throw new Util.Multiple_Items_Error('Multiple anchor placeholders' +
				' are selected.');
		} else {
			return found[0];
		}
	}

	this.get_selected_item = function()
	{
		var placeholder = _get_selected_placeholder();
		return (placeholder)
			? {name: self._masseuse.get_name_from_placeholder(placeholder)}
			: null;
	};

	this.open_dialog = function()
	{
		var selected_item = self.get_selected_item();
		
		if (!this._dialog)
			this._dialog = new UI.Anchor_Dialog();
	
		this._dialog.init({
			base_uri: self._loki.settings.base_uri,
			submit_listener: self.insert_anchor,
			remove_listener: self.remove_anchor,
			selected_item: selected_item
		});
		this._dialog.open();
	};

	this.insert_anchor = function(anchor_info)
	{
		var selected = _get_selected_placeholder();
		var sel;
		var anchor;
		
		if (selected) {
			self._masseuse.update_name(selected, anchor_info.name);
		} else {
			anchor = Util.Document.create_element(self._loki.document,
				'a', {name: anchor_info.name});
			
			sel = Util.Selection.get_selection(self._loki.window);
			Util.Selection.collapse(sel, true); // to beginning
			Util.Selection.paste_node(sel, anchor);
			
			self._masseuse.massage(anchor);
		}
		
		self._loki.window.focus();
	};

	this.remove_anchor = function()
	{
		var selected = _get_selected_placeholder();
		var anchor;
		
		if (!selected)
			return;
		
		anchor = self._masseuse.unmassage(selected);
		if (!anchor.hasChildNodes())
			anchor.parentNode.removeChild(anchor);
		else
			anchor.removeAttribute('name');
	};
};

// file UI.Anchor_Masseuse.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for inserting an anchor.
 */
UI.Anchor_Masseuse = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Masseuse);
	
	function needs_massaging(node) {
		return !!node.name;
	}
	needs_massaging.tag = 'A';
	
	function needs_unmassaging(node) {
		return !!node.getAttribute('loki:anchor_id');
	}
	needs_unmassaging.tag = 'IMG';

	/**
	 * Massages the given node's children, replacing any named anchors with
	 * fake images.
	 */
	this.massage_node_descendants = function(node)
	{
		var anchors = node.getElementsByTagName(needs_massaging.tag);
		var i, anchor;

		for (i = anchors.length - 1; i >= 0; i--) {
			anchor = anchors[i];
			if (needs_massaging(anchor))
				self.massage(anchor);
		}
	};

	/**
	 * Unmassages the given node's descendants, replacing any fake anchor images 
	 * with real anchor elements.
	 */
	this.unmassage_node_descendants = function(node)
	{
		var fakes = node.getElementsByTagName(needs_unmassaging.tag);
		var i, fake;
		
		// Remove anchors that have had their placeholder images deleted.
		var anchors = node.getElementsByTagName(needs_massaging.tag);
		var anchor;
		var placeholder_map = {}, id;
		
		for (i = 0; i < fakes.length; i++) {
		    id = fakes[i].getAttribute('loki:anchor_id');
		    if (id)
		        placeholder_map[id] = fakes[i];
		}
		
		for (i = anchors.length - 1; i >= 0; i--) {
			anchor = anchors[i];
			if (needs_massaging(anchor) && !placeholder_map[anchor.id])
				anchor.parentNode.removeChild(anchor);
		}

        // Unmassage the placeholders that still exist.
		for (i = fakes.length - 1; i >= 0; i--) {
			fake = fakes[i];
			if (needs_unmassaging(fake))
				self.unmassage(fake);
		}
	};
	
	this.massage = function massage_anchor(anchor)
	{
		var doc = anchor.ownerDocument;
		var placeholder;
		var anchor_id = self.assign_fake_id(anchor);
		
		placeholder = Util.Document.create_element(doc, 'img', {
			className: 'loki__named_anchor',
			title: '#' + anchor.name,
			src: self._loki.settings.base_uri + 'images/nav/anchor.gif',
			style: {width: 12, height: 12},
			'loki:fake': true,
			'loki:anchor_id': anchor_id
		});
		
		return anchor.parentNode.insertBefore(placeholder, anchor);
	};
	
	this.update_name = function update_massaged_anchor_name(placeholder, name) {
		var anchor = self.get_anchor_for_placeholder(placeholder);
		
		placeholder.title = '#' + name;
		if (anchor) {
			if (anchor.id && anchor.id == anchor.name) {
				anchor.id = name;
				placeholder.setAttribute("loki:anchor_id", name);
			}
			anchor.name = name;
		}		
	};
	
	this.unmassage = function unmassage_anchor(placeholder) {
		var anchor = self.get_anchor_for_placeholder(placeholder);
		var actual_id;
		var name;
		var expected_id;
		
		if (!anchor) {
			// The original anchor tag was somehow removed from the document.
			anchor = placeholder.ownerDocument.createElement('A');
			anchor.name = placeholder.title.substr(1); // strips leading "#"
			placeholder.parentNode.replaceChild(anchor, placeholder);
			return anchor;
		}
		
		expected_id = placeholder.getAttribute('loki:anchor_id');
		actual_id = (placeholder.nextSibling) ?
		    placeholder.nextSibling.id :
		    null;
		self.remove_fake_id(anchor);
		if (actual_id == expected_id) {
			// Relative position has not changed. Simple.
			placeholder.parentNode.removeChild(placeholder);
			return anchor;
		}
		
		// The user has moved the anchor away from its original position.
		if (!anchor.hasChildNodes()) {
			// Bare named anchor; we can just move it to the correct spot.
			placeholder.parentNode.replaceChild(anchor, placeholder);
			return anchor;
		}
		
		// Anchor has child nodes: it must be split, leaving the original anchor
		// without a name and creating a new named anchor at the placeholder's
		// position.
		name = anchor.name;
		anchor.removeAttribute('name');
		
		anchor = placeholder.ownerDocument.createElement('A');
		anchor.name = name;
		
		placeholder.parentNode.replaceChild(anchor, placeholder);
		return anchor;
	};
	
	this.is_placeholder = function is_anchor_placeholder(elem) {
		return (Util.Node.is_tag(elem, needs_unmassaging.tag)
			&& needs_unmassaging(elem));
	};
	
	this.get_name_from_placeholder = function get_anchor_name(placeholder) {
		var anchor;
		try {
			anchor = self.get_anchor_for_placeholder(placeholder);
			if (anchor && anchor.name)
				return anchor.name;
		} catch (e) { /* ignore it */ }
		
		return placeholder.title.substr(1); // strips leading "#"
	};
	
	this.get_anchor_for_placeholder = function get_real_anchor(placeholder) {
		var id = placeholder.getAttribute('loki:anchor_id');
		
		if (!id) {
			throw new Error('The placeholder has no associated anchor ID.');
		}
		
		return placeholder.ownerDocument.getElementById(id) || null;
	};
};

// file UI.Anchor_Menugroup.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing a clipboard menugroup. 
 */
UI.Anchor_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._anchor_helper = (new UI.Anchor_Helper).init(this._loki);
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var menuitems = [];

		var selected_item = this._anchor_helper.get_selected_item();
		if ( selected_item != null )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Edit anchor',
				listener : this._anchor_helper.open_dialog 
			}) );
		}

		return menuitems;
	};
};

// file UI.BR_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "Insert BR" toolbar button.
 */
UI.BR_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'break.png';
	this.title = 'Single-line break (Shift+Enter)';
	this.click_listener = function() { self._br_helper.insert_br(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._br_helper = (new UI.BR_Helper).init(this._loki);
		return this;
	};
};

// file UI.BR_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert a br. Contains code
 * common to both the button and the menu item.
 */
UI.BR_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.insert_br = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var br = self._loki.document.createElement('BR');
		if ( document.all ) // XXX bad
			Util.Selection.paste_node(sel, br);
		else
			_paste_node_for_br_in_gecko(sel, br);
/*
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		rng.setStart(br, 0);
		rng.setEnd(br, 0);
*/
//		Util.Selection.select_node(sel, br);
	//	Util.Selection.collapse(sel, false);
/*
		var rng = Util.Range.create_range(sel);
		var rng2 = Util.Range.clone_range(rng);
		Util.Selection.select_node(sel, self._loki.document.documentElement);
		Util.Selection.select_range(sel, rng2);
		//Util.Selection.collapse(sel, true);
*/
		self._loki.window.focus();
	};

	/**
	 * This function is intended to work around the problem, in Gecko,
	 * that when you click the BR button, a BR is always inserted, but 
	 * the cursor doesn't always move down a line until you start typing--
	 * which is confusing. This doesn't _totally_ fix that problem, but
	 * it's better. XXX more work needed, and get rid of this hack.
	 */
	var _paste_node_for_br_in_gecko = function(sel, to_be_inserted)
	{
		//var range = this._create_range(sel);
		var range = Util.Range.create_range(sel);
		// remove the current selection
		sel.removeAllRanges();
		range.deleteContents();
		var node = range.startContainer;
		var pos = range.startOffset;
		//range = this._create_range();
		//var range = Util.Range.create_range(sel);
		range = node.ownerDocument.createRange();
		switch (node.nodeType)
		{
		case 3: // Node.TEXT_NODE
				// we have to split it at the caret position.
			if (to_be_inserted.nodeType == 3)
			{
				// do optimized insertion
				node.insertData(pos, to_be_inserted.data);
				range.setEnd(node, pos + to_be_inserted.length);
				range.setStart(node, pos + to_be_inserted.length);
			}
			else
			{
				node = node.splitText(pos);
				node.parentNode.insertBefore(to_be_inserted, node);
				range.setStart(node, 0);
				range.setEnd(node, 0);
			}
			break;
		case 1: // Node.ELEMENT_NODE
			node = node.childNodes[pos];
			node.parentNode.insertBefore(to_be_inserted, node);
			range.setStart(node, 0);
			range.setEnd(node, 0);
			break;
		}
		sel.addRange(range);
	};
};

// file UI.Blockquote_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "blockquote" toolbar button.
 */
UI.Blockquote_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'quote.png';
	this.title = 'Blockquote';
	this.click_listener = function() { self._helper.toggle_blockquote_paragraph(); };
	this.state_querier = function() { return self._helper.query_blockquote_paragraph(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._helper = (new UI.Blockquote_Highlight_Helper).init(this._loki, 'blockquote');
		return this;
	};
};

// file UI.Blockquote_Highlight_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Helper for blockquote and highlight buttons: contains logic common to both.
 * N.B.: I use "blockquote" below as a convenient shorthand for "blockquote_or_highlight_or_etc".
 */
UI.Blockquote_Highlight_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	/**
	 * @param	kind	either "blockquote" or "highlight"
	 */
	this.init = function(loki, kind)
	{
		this.superclass.init.call(this, loki);
		this._kind = kind;
		this._paragraph_helper = (new UI.Paragraph_Helper()).init(this._loki);
		return this;
	};

	this.is_blockquoted = function()
	{
		return _get_blockquote_elem() != null;
	};

	this.toggle_blockquote_paragraph = function()
	{
		// Make sure we're not directly within BODY
		self._paragraph_helper.possibly_paragraphify();

		_remove_improper_blockquote_class_from_p();
		var blockquote = _get_blockquote_elem();

		//mb('_toggle_blockquote_paragraph: blockquote', blockquote);
		if ( blockquote == null )
		{
			//if ( self.is_blockquoteable() )
				_blockquote_paragraph();
			// else do nothing
		}
		else
		{
			/* works, but is undesired behavior:
			mb('found blockquote; replacing with children; blockquote:', blockquote);
			Util.Node.replace_with_children(blockquote);
			*/
			_unblockquote_paragraph(blockquote);
		}
	};

	/**
	 * Sometimes, despite my best efforts, in IE it seems that the callOut
	 * class gets transferred from div to p. This seems ludicrous, but 
	 * happens. So here we check for a callOut'd p and if found, remove 
	 * the callOut, since that's probably what the user will want.
	 */
	var _remove_improper_blockquote_class_from_p = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var p = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'P');

		if ( Util.Element.has_class(p, 'callOut') )
			Util.Element.remove_class(p, 'callOut');
	};

	var _is_blockquote_elem = function(node)
	{
		if ( self._kind == "blockquote" )
			return ( node.nodeType == Util.Node.ELEMENT_NODE &&
					 node.tagName == 'BLOCKQUOTE' );
		else
			return ( node.nodeType == Util.Node.ELEMENT_NODE &&
					 node.tagName == 'DIV' &&
					 Util.Element.has_class(node, 'callOut') );
	};

	var _create_blockquote_elem = function(doc)
	{
		if ( self._kind == "blockquote" )
			return doc.createElement('BLOCKQUOTE');
		else
		{
			var div = doc.createElement('DIV');
			Util.Element.add_class(div, 'callOut');
			return div;
		}
	};

	/**
	 * Gets the element contained by current selection 
	 * that is blockquoteable. If no such exists, returns null.
	 */
	this.is_blockquoteable = function()
	{
		var is_table_elem = function(node)
		{
			 return ( (new RegExp('ol', 'i')).test(node.tagName) ||
					  (new RegExp('ul', 'i')).test(node.tagName) ||
					  (new RegExp('li', 'i')).test(node.tagName) ||
				  	  (new RegExp('td', 'i')).test(node.tagName) ||
					  (new RegExp('table', 'i')).test(node.tagName) );
		};

		var is_highlightable = function(node)
		{
			return ( node.nodeType == Util.Node.ELEMENT_NODE &&
					 Util.Node.is_nestable_block_level_element(node) &&
					 !Util.Node.has_ancestor_node(node, is_table_elem) );
		};

		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		// This doesn\'t work if, e.g., we have: <body><p>aasd^ad</p><p>asdfas$asdf</p></body>,
		// because the nearest common ancestor is BODY ... :
		//var elem = Util.Range.get_nearest_ancestor_node(rng, is_highlightable);
		var start_container = Util.Range.get_start_container(rng);
		var elem = Util.Node.get_nearest_ancestor_node(start_container, is_highlightable);

		return elem != null;
	};

	var _get_blockquote_elem = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return Util.Range.get_nearest_ancestor_node(rng, _is_blockquote_elem);
	};

	/**
	 * Blockquotes the current paragraph.
	 */
	var _blockquote_paragraph = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var blocks = Util.Range.get_intersecting_blocks(rng);

		if ( blocks.length > 0 )
		{
			// Create and append the blockquote elem
			var blockquote = _create_blockquote_elem(blocks[0].ownerDocument);
			blocks[0].parentNode.insertBefore(blockquote, blocks[0]);

			// Append the blocks to the blockquote
			for ( var i = 0; i < blocks.length; i++ )
			{
				blockquote.appendChild(blocks[i]);
			}
		}

		if ( !document.all ) // XXX doesn't work in IE right now, so just make user click in iframe again:
		{
			Util.Selection.move_cursor_to_end(sel, blockquote);
			self._loki.window.focus();
		}
	};

	var _unblockquote_paragraph = function(blockquote)
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var blocks = Util.Range.get_intersecting_blocks(rng);

		var blockquote1 = blockquote.cloneNode(false); // clone blockquote elem twice
		var blockquote2 = blockquote.cloneNode(false);
		var non_blockquoted = []; // make array for non-blockquoted nodes
		var node = blockquote.firstChild
		var next;

		// loop through blockquote elem's children, adding each child to first clone
		// until first selected block (or last child) is found
		while ( node != blocks[0] && node != null )
		{
			next = node.nextSibling;
			if ( Util.Node.is_non_whitespace_text_node(node) )
				blockquote1.appendChild(node);
			node = next;
		}

		// keep looping, adding each child to array of non blockquoted
		// children, until last selected block (or last child) is found
		while ( node != blocks[blocks.length - 1] && node != null )
		{
			next = node.nextSibling;
			if ( Util.Node.is_non_whitespace_text_node(node) )
				non_blockquoted.push(node);
			node = next;
		}
		// (add the last non-blockquoted child)
		if ( node != null )
		{
			next = node.nextSibling;
			if ( Util.Node.is_non_whitespace_text_node(node) )
				non_blockquoted.push(node);
			node = next;
		}
		
		// keep looping, adding each child to second clone, 
		// until last child is found
		while ( node != null )
		{
			next = node.nextSibling;
			if ( Util.Node.is_non_whitespace_text_node(node) )
				blockquote2.appendChild(node);
			node = next;
		}


		// replace blockquote with placeholder
		var parent = blockquote.parentNode;
		var placeholder = blockquote.ownerDocument.createElement('DIV');
		parent.replaceChild(placeholder, blockquote);
		
		// insert first clone before placeholder
		if ( blockquote1.childNodes.length > 0 )
			parent.insertBefore(blockquote1, placeholder);

		// insert each element in non-blockquoted array before placeholder
		for ( var i = 0; i < non_blockquoted.length; i++ )
			parent.insertBefore(non_blockquoted[i], placeholder);

		// insert second clone before placeholder
		if ( blockquote2.childNodes.length > 0 )
			parent.insertBefore(blockquote2, placeholder);

		// remove placeholder
		parent.removeChild(placeholder);


		// move cursor
		if ( !document.all ) // XXX doesn't work in IE right now, so just make user click in iframe again:
		{
			Util.Selection.move_cursor_to_end(sel, blockquote2);
			self._loki.window.focus();
		}
	};

	/**
	 * Queries whether the current paragraph is highlightable, 
	 * or highlighted. Returns accordingly.
	 */
	this.query_blockquote_paragraph = function()
	{
		// see UI.Highlight_Button
	};
};

// file UI.Bold_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "bold" toolbar button.
 */
UI.Bold_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'strong.png';
	this.title = 'Strong (Ctrl+B)';
	this.click_listener = function() { self._loki.exec_command('Bold'); };
	this.state_querier = function() { return self._loki.query_command_state('Bold'); };
};

// file UI.Bold_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Bold_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);
	this.test = function(e) { return this.matches_keycode(e, 66) && e.ctrlKey; }; // Ctrl-B
	this.action = function() { this._loki.exec_command('Bold'); };
};

// file UI.Bold_Masseuse.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for massaging strong tags to b tags. The motivation for this is that 
 * you can't edit strong tags, but we want them in the final output.
 */
UI.Bold_Masseuse = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Masseuse);

	/**
	 * Massages the given node's children, replacing any named strongs with
	 * b elements.
	 */
	this.massage_node_descendants = function(node)
	{
		var strongs = node.getElementsByTagName('STRONG');
		for ( var i = strongs.length - 1; i >= 0; i-- )
		{
			var fake = self.get_fake_elem(strongs[i]);
			strongs[i].parentNode.replaceChild(fake, strongs[i]);
		}
	};

	/**
	 * Unmassages the given node's descendants, replacing any b elements
	 * with real strong elements.
	 */
	this.unmassage_node_descendants = function(node)
	{
		var dummies = node.getElementsByTagName('B');
		for ( var i = dummies.length - 1; i >= 0; i-- )
		{
			var real = self.get_real_elem(dummies[i]);
			dummies[i].parentNode.replaceChild(real, dummies[i])
		}
	};

	/**
	 * Returns a fake element for the given strong.
	 */
	this.get_fake_elem = function(strong)
	{
		var dummy = strong.ownerDocument.createElement('B');
		dummy.setAttribute('loki:fake', 'true');
		// maybe transfer attributes, too
		while ( strong.firstChild != null )
		{
			dummy.appendChild( strong.removeChild(strong.firstChild) );
		}
		return dummy;
	};

	/**
	 * If the given fake element is really fake, returns the appropriate 
	 * real strong. Else, returns null.
	 */
	this.get_real_elem = function(dummy)
	{
		if (dummy != null && dummy.nodeName == 'B') {
			var strong = dummy.ownerDocument.createElement('STRONG');
			// maybe transfer attributes, too
			while ( dummy.firstChild != null )
			{
				strong.appendChild( dummy.removeChild(dummy.firstChild) );
			}
			return strong;
		}
		return null;
	};
};

// file UI.Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a button. For extending only.
 */
UI.Button = function()
{
	this.image; // string to location in base_uri/img/
	this.title; // string
	this.click_listener; // function
	this.state_querier; // function (optional)
	this.show_on_source_toolbar = false; // boolean (optional)

	this.init = function(loki)
	{
		this._loki = loki;
		return this;
	};
};

// file UI.Cell_Dialog.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A table dialog window..
 */
UI.Cell_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);

	this._dialog_window_width = 615;
	this._dialog_window_width = 585;

	this._bgs = ['bgFFFFCC', 'bgFFFF99', 'bg99CCFF', 'bgCCCCCC', 'bgE8E8E8'];
	this._bg_radios = new Array();

	this._set_title = function()
	{
		this._dialog_window.document.title =  "Table cell properties";
	};

	this._append_style_sheets = function()
	{
		this.superclass._append_style_sheets.call(this);
		//Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/cssSelector.css');
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Table_Dialog.css');
	};

	this._populate_main = function()
	{
		this._append_td_properties();
		//this._append_table_color_properties();
		this.superclass._populate_main.call(this);
	};

	/**
	 * Appends a chunk containing table properties.
	 */
	this._append_td_properties = function()
	{
		var self = this;

		// Create generic label element
		var generic_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(generic_label, 'label');

		// Align
		this._align_select = this._dialog_window.document.createElement('SELECT');
		this._align_select.setAttribute('id', 'align_select');
		
		var align_label = generic_label.cloneNode(false);
		align_label.appendChild( this._dialog_window.document.createTextNode('Alignment: ') );
		align_label.setAttribute('for', 'align_select');

		Util.Select.append_options(this._align_select, [{l : 'Left', v : 'left'}, {l : 'Center', v : 'center'}, {l : 'Right', v : 'right'}]);

		var align_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(align_div, 'field');
		align_div.appendChild(align_label);
		align_div.appendChild(this._align_select);

		// Valign
		this._valign_select = this._dialog_window.document.createElement('SELECT');
		this._valign_select.setAttribute('id', 'valign_select');
		
		var valign_label = generic_label.cloneNode(false);
		valign_label.appendChild( this._dialog_window.document.createTextNode('Vertical alignment: ') );
		valign_label.setAttribute('for', 'valign_select');

		Util.Select.append_options(this._valign_select, [{l : 'Top', v : 'top'}, {l : 'Middle', v : 'middle'}, {l : 'Bottom', v : 'bottom'}]);

		var valign_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(valign_div, 'field');
		valign_div.appendChild(valign_label);
		valign_div.appendChild(this._valign_select);

		// Wrap
		this._wrap_select = this._dialog_window.document.createElement('SELECT');
		this._wrap_select.setAttribute('id', 'wrap_select');

		var wrap_label = generic_label.cloneNode(false);
		wrap_label.appendChild( this._dialog_window.document.createTextNode('Wrap: ') );
		wrap_label.setAttribute('for', 'wrap_select');

		Util.Select.append_options(this._wrap_select, [{l : 'Yes', v : 'yes'}, {l : 'No', v : 'no'}]);

		var wrap_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(wrap_div, 'field');
		wrap_div.appendChild(wrap_label);
		wrap_div.appendChild(this._wrap_select);

		// Create heading
		var h1 = this._dialog_window.document.createElement('H1');
		h1.innerHTML = 'Table cell properties';

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : '', document : this._dialog_window.document});

		// Append all the above to fieldset
		fieldset.fieldset_elem.appendChild(align_div);
		fieldset.fieldset_elem.appendChild(valign_div);
		fieldset.fieldset_elem.appendChild(wrap_div);

		// Append fieldset chunk to dialog
		this._main_chunk.appendChild(h1);
		this._main_chunk.appendChild(fieldset.chunk);
	};

	/**
	 * Appends a chunk containing table color properties.
	 */
	this._append_table_color_properties = function()
	{
		// Create generic elements
		var generic_bg_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(generic_bg_label, 'bg_label');
		//generic_bg_label.appendChild( this._dialog_window.document.createTextNode(' ') );
		generic_bg_label.innerHTML = '&nbsp;';

		var generic_bg_radio = Util.Input.create_named_input({document : this._dialog_window.document, name : 'bg_radio'});
		generic_bg_radio.setAttribute('type', 'radio');

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : 'Cell color properties:', document : this._dialog_window.document});

		// Create and append the "no bgcolor" radio and label
		this._no_bg_radio = generic_bg_radio.cloneNode(true);
		this._no_bg_radio.setAttribute('id', 'no_bg_radio');

		var no_bg_label = this._dialog_window.document.createElement('LABEL');
		no_bg_label.appendChild( this._dialog_window.document.createTextNode('Use no background color') );
		no_bg_label.setAttribute('for', 'no_bg_radio');
		Util.Element.add_class(no_bg_label, 'label');

		fieldset.fieldset.appendChild(this._no_bg_radio);
		fieldset.fieldset.appendChild(no_bg_label);

		// Create and append the bgcolor radios and labels
		var bg_labels = new Array();
		for ( var i = 0; i < this._bgs.length; i++ )
		{
			bg_labels[i] = generic_bg_label.cloneNode(true);
			bg_labels[i].setAttribute('for', 'bg_' + this._bgs[i] + '_radio');
			Util.Element.add_class(bg_labels[i], this._bgs[i]);

			this._bg_radios[i] = generic_bg_radio.cloneNode(true);
			this._bg_radios[i].setAttribute('id', 'bg_' + this._bgs[i] + '_radio');

			fieldset.fieldset_elem.appendChild(this._bg_radios[i]);
			fieldset.fieldset_elem.appendChild(bg_labels[i]);
		}

		// Append fieldset chunk to dialog
		this._main_chunk.appendChild(fieldset.chunk);
	};

	/**
	 * Sets initial values.
	 */
	this._apply_initially_selected_item = function()
	{
		messagebox('UI.Cell_Dialog.apply_initially_selelcted_item: initially_selected_item.align', this._initially_selected_item.align);
		messagebox('UI.Cell_Dialog.apply_initially_selelcted_item: initially_selected_item.valign', this._initially_selected_item.valign);

		this._align_select.value = this._initially_selected_item.align == '' ? 'left' : this._initially_selected_item.align;
		this._valign_select.value = this._initially_selected_item.valign == '' ? 'top' : this._initially_selected_item.valign;
		this._wrap_select.value = this._initially_selected_item.wrap == '' ? 'yes' : this._initially_selected_item.wrap;
		
		messagebox('UI.Cell_Dialog.apply_initially_selelcted_item: this._align_select.value', this._align_select.value);
		messagebox('UI.Cell_Dialog.apply_initially_selelcted_item: this._valign_select.value', this._valign_select.value);

		/*
		// Apply background
		this._no_bg_radio.checked = true;
		for ( var i = 0; i < this._bgs.length; i++ )
		{
			if ( this._bgs[i] == this._initially_selected_item.bg )
			{
				this._bg_radios[i].checked = true;
			}
		}
		*/
	};

	/**
	 * Called as an event listener when the user clicks the submit
	 * button. 
	 */
	this._internal_submit_listener = function()
	{
		var align = this._align_select.value;
		var valign = this._valign_select.value;
		var wrap = this._wrap_select.value;
		
		/*
		// Determine background
		var bg = '';
		for ( var i = 0; i < this._bgs.length; i++ )
		{
			if ( this._bg_radios[i].checked == true )
			{
				bg = this._bgs[i];
			}
		}
		*/

		//this._external_submit_listener({align : align, valign : valign, wrap : wrap, bg : bg});
		this._external_submit_listener({align : align, valign : valign, wrap : wrap});
		this._dialog_window.window.close();
	};
};

// file UI.Center_Align_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "center align" toolbar button.
 */
UI.Center_Align_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'align_center.png';
	this.title = 'Center align (Ctrl+E)';
	this.click_listener = function() { self._loki.exec_command('JustifyCenter'); };
	this.state_querier = function() { return self._loki.query_command_state('JustifyCenter'); };
};

// file UI.Center_Align_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Center_Align_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);
	this.test = function(e) { return this.matches_keycode(e, 69) && e.ctrlKey; }; // Ctrl-L
	//this.action = function() { this._loki.exec_command('JustifyCenter'); };
	this.action = function() { this._align_helper.align_center(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._align_helper = (new UI.Align_Helper).init(this._loki);
		return this;
	};
};

// file UI.Clean.js
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

/**
 * Cleans the children of the given root.
 *
 * @param {Element} root             reference to the node whose children should
 *                                   be cleaned
 * @param {object}	settings         Loki settings
 * @param {boolean} [live]           set to true if this clean is being run
 *                                   on content that is actively being edited
 * @param {object}  [block_settings] settings to pass along to
 *                                   Util.Block.enforce_rules
 */
UI.Clean.clean = function(root, settings, live, block_settings)
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
	 * Checks whether the given node is an element node.
	 */
	function is_element(node)
	{
		return (node.nodeType == Util.Node.ELEMENT_NODE);
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
	 * Checks whether the given node has any classes
	 * matching the given strings.
	 */
	function has_class(node, strs)
	{
		var matches = [];
		
		if (node.nodeType == Util.Node.ELEMENT_NODE) {
			for (var i = 0; i < strs.length; i++) {
				if (Util.Element.has_class(node, strs[i]))
					matches.push(strs[i]);
			}
		}
		
		return (matches.length > 0) ? matches : false;
	}

	/**
	 * Removes all attributes matching the given strings.
	 */
	function remove_class(node, strs)
	{
		for (var i = 0; i < strs.length; i++) {
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
	
	var allowable_tags;
	if (settings.allowable_tags) {
		allowable_tags = settings.allowable_tags.map(function(tag) {
			return tag.toUpperCase();
		}).toSet();
	} else {
		allowable_tags = UI.Clean.default_allowable_tags.toSet();
	}
	
	var acceptable_css;
	if (typeof(settings.allowable_inline_styles) != 'undefined') {
		if ('string' == typeof(settings.allowable_inline_styles)) {
			var macros = {
				'all': true,
				'any': true,
				'*': true,
				'none': false
			};
			acceptable_css = settings.allowable_inline_styles.toLowerCase();
			if (acceptable_css in macros) {
				acceptable_css = macros[acceptable_css];
			} else {
				acceptable_css = acceptable_css.split(/\s+/);
			}
		} else if (null === settings.allowable_inline_styles) {
			acceptable_css = UI.Clean.default_allowable_inline_styles;
		} else {
			acceptable_css = settings.allowable_inline_styles;
		}
	} else {
		acceptable_css = UI.Clean.default_allowable_inline_styles;
	}
	
	if (typeof(acceptable_css.join) == 'function') { // it's an array!	
		acceptable_css = get_css_pattern(acceptable_css);
	}
	
	function get_css_pattern(names) {
		names = names.map(Util.regexp_escape).map(function(name) {
			return name.toLowerCase();
		});
		return new RegExp('^(' + names.join('|') + ')');
	}
		
	function is_allowable_tag(node)
	{
		return (node.nodeType != Util.Node.ELEMENT_NODE ||
			node.tagName in allowable_tags);
	}
	
	function is_block(node)
	{
		var wdw = Util.Node.get_window(node);
		if (wdw) {
			try {
				return Util.Element.is_block_level(wdw, node);
			} catch (e) {
				// try using tag name below
			}
		}
		
		return Util.Node.is_block_level_element(node);
	}
	
	function is_within_container(node) {
		for (var n = node; n; n = n.parentNode) {
			if (is_element(n) && n.getAttribute('loki:container'))
				return true;
		}
		
		return false;
	}
	
	function is_on_current_page(uri) {
		if (!uri.host && (!uri.path || (/$\.\/?/.exec(uri.path))))
			return true;
		
		// Mozilla makes us go the extra mile.
		var base = Util.URI.parse(window.location);
		if (base.authority == uri.authority && base.path == uri.path)
			return true;
		
		return false;
	}
	
	function is_same_domain(uri) {
		return (uri.host == Util.URI.extract_domain(window.location));
	}

	var tests =
	[
		// description : a text description of the test and action
		// test : function that is passed node in question, and returns
		//        false if the node doesn`t match, and whatever it wants 
		//        to be passed to the action otherwise.
		// action : function that is passed node and return of action, and 

		{
			description : 'Remove all comment nodes.',
			test : function(node) {
				if (node.nodeType != Util.Node.COMMENT_NODE)
					return false;
				return !("!" in allowable_tags);
			},
			action : remove_node
		},
		{
			description : 'Remove all style nodes.',
			test : function(node) { return has_tagname(node, ['STYLE']); },
			action : remove_node
		},
		{
			description : 'Remove bad attributes. (v:shape from Ppt)',
			test : function (node) { return has_attributes(node, ['v:shape']); },
			action : remove_attributes
		},
		{
			description: 'Translate align attributes.',
			test: function(node) { return has_attributes(node, ['align']); },
			action: function translate_alignment(el) {
				// Exception: tables and images still use the align attribute.
				if (has_tagname(el, ['TD', 'TH', 'TR', 'TABLE', 'IMG']))
					return;
				
				el.style.textAlign = el.align.toLowerCase();
				el.removeAttribute('align');
			}
		},
		{
			description: 'Strip unwanted inline styles',
			test: function(node) {
				return acceptable_css !== true && has_attributes(node, ['style']); 
			},
			action: function strip_unwanted_inline_styles(el) {
				if (acceptable_css === false) {
					el.removeAttribute('style');
					return;
				}
				
				var rule = /([\w\-]+)\s*:\s*([^;]+)(?:;|$)/g;
				var raw = el.style.cssText;
				var accepted = [];
				var match;
				var name;
				
				while (match = rule.exec(raw)) {
					name = match[1].toLowerCase();
					if (acceptable_css.test(name)) {
						accepted.push(name + ": " + match[2] + ";");
					}
				}
				
				if (accepted.length > 0)
					el.style.cssText = accepted.join(' ');
				else
					el.removeAttribute('style');
			}
		},
		{
			description: 'Remove empty Word paragraphs',
			test: function is_empty_word_paragraph(node) {
				// Check node type and tag
				if (!node.tagName || node.tagName != 'P') {
					return false;
				}
				
				// Check for a Word class
				if (!(/(^|\b)Mso/.test(node.className)))
					return false;
				
				// Check for the paragraph to only contain non-breaking spaces
				// or other whitespace characters.
				var pattern = new RegExp("^[\\s\xA0]+$", "");
				for (var i = 0; i < node.childNodes.length; i++) {
					var child = node.childNodes[i];
					if (child.nodeType == Util.Node.ELEMENT_NODE) {
						if (!is_empty_word_paragraph(child)) // recurse
							return false;
					}
					
					if (child.nodeType == Util.Node.TEXT_NODE) {
						if (!pattern.test(child.data)) {
							return false;
						}
					}
				}
				
				return true;
			},
			action: remove_node
		},
		{
			description: 'Remove Microsoft Word section DIV\'s',
			test: function is_ms_word_section_div(node) {
				if (!has_tagname(node, ['DIV']))
					return false;
			
				var pattern = /^Section\d+$/;
				var classes = Util.Element.get_class_array(node);
				if (!classes.length) {
				    return false;
				}
				
				for (var i = 0; i < classes.length; i++) {
					if (!pattern.test(classes[i]))
						return false;
				}
				
				return true;
			},
			action: remove_tag
		},
		{
			description : 'Remove Microsoft Office internal classes.',
			test : is_element,
			action : function strip_ms_office_classes(node)
			{
				var office_pattern = /^(Mso|O|Section\d+$)/;
				var classes = Util.Element.get_class_array(node);
				var length = classes.length;
				
				for (var i = 0; i < length; i++) {
					if (office_pattern.test(classes[i]))
						classes.splice(i, 1); // remove the class
				}
				
				if (classes.length != length)
					Util.Element.set_class_array(node, classes);
			}
		},
		{
			description : 'Remove unnecessary span elements',
			test : function is_bad_span(node) {
				 return (has_tagname(node, ['SPAN'])
					&& !has_attributes(node, ['class', 'style'])
					&& !is_within_container(node));
			},
			action : remove_tag
		},
		{
			description : 'Remove all miscellaneous non-good tags (strip_tags).',
			test : function(node) { return !is_allowable_tag(node); },
			action : remove_tag
		},
		// STRONG -> B, EM -> I should be in a Masseuse; then exclude B and I here
		// CENTER -> P(align="center")
		// H1, H2 -> H3; H5, H6 -> H4(? or -> P)
		// Axe form elements?
		{
			description : "Remove U unless there's an appropriate option set.",
			test : function(node) { return !settings.options.underline && has_tagname(node, ['U']); },
			action : remove_tag
		},
		{
			description : 'Remove all tags that have Office namespace prefixes.',
			test : function(node) { return has_prefix(node, ['o', 'O', 'w', 'W', 'st1', 'ST1']); },
			action : remove_tag
		},
		{
			description : 'Remove width and height attrs on tables.',
			test : function(node) {
				return has_tagname(node, ['TABLE']); 
			},
			action : function(node) { 
				remove_attributes(node, ['height', 'width']); 
			}
		},
		{
			description: 'Remove width and height attributes from images if so desired.',
			test: function(node) {
				return (!!settings.disallow_image_sizes &&
					has_tagname(node, ['IMG']));
			},
			action: function(node) {
				remove_attributes(node, ['height', 'width']);
			}
		},
		{
			description: "Normalize all image URI's",
			test: Util.Node.curry_is_tag('IMG'),
			action: function normalize_image_uri(img) {
				if (Util.URI.is_urn(img)) {
					// Don't normalize URN's (like data:).
					return;
				}
				var uri = Util.URI.parse(img.src);
				var norm = Util.URI.normalize(img.src);
				if (is_same_domain(uri))
					norm.scheme = null;
				else
					norm.scheme = uri.scheme; // undo any changes
				img.src = Util.URI.build(norm);
			}
		},
		{
			description: "Normalize all link URI's",
			test: Util.Node.curry_is_tag('A'),
			action: function normalize_link_uri(link) {
				if (!link.href)
					return;
				var uri = Util.URI.parse(link.href);
				if (Util.URI.is_urn(uri)) {
					// Do nothing to URN's (like mailto: addresses).
					return;
				}
				if (is_on_current_page(uri))
					return;
				var norm = Util.URI.normalize(uri);
				if (is_same_domain(uri))
					norm.scheme = null;
				else
					norm.scheme = uri.scheme; // undo any changes
				link.href = Util.URI.build(norm);
			}
		},
		{
			description: 'Remove unnecessary BR\'s that are elements\' last ' +
				'children',
			run_on_live: false,
			test: function is_last_child_br(node) {
				function get_last_relevant_child(n)
				{
					var c; // child
					for (c = n.lastChild; c; c = c.previousSibling) {
						if (c.nodeType == Util.Node.ELEMENT_NODE) {
							return c;
						} else if (c.nodeType == Util.Node.TEXT_NODE) {
							if (/\S/.test(c.nodeValue))
								return c;
						}
					}
				}
				
				return has_tagname(node, ['BR']) && is_block(node.parentNode) &&
					get_last_relevant_child(node.parentNode) == node;
				
			},
			action: remove_node
		},
		{
			description: 'Remove improperly nested elements',
			run_on_live: false,
			test: function improperly_nested(node)
			{
				function is_nested()
				{
					var a;
					for (a = node.parentNode; a; a = a.parentNode) {
						if (a.tagName == node.tagName)
							return true;
					}
					
					return false;
				}
				
				return node.tagName in UI.Clean.self_nesting_disallowed &&
					is_nested();
			},
			action: remove_tag
		}
		// TODO: deal with this?
		// In content pasted from Word, there may be 
		// ...<thead><tr><td>1</td></tr></thead>...
		// instead of
		// ...<thead><tr><th>1</th></tr></thead>...
	];

	function _clean_recursive(root)
	{
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
			if (live && false === tests[i].run_on_live)
				continue;
			
			var result = tests[i].test(node);
			if ( result !== false )
			{
				// We do this because we don't want any errors to
				// result in lost content!
				try {
					tests[i].action(node, result);
				} catch (e) {
					if (typeof(console) == 'object') {
						if (console.warn)
							console.warn(e);
						else if (console.log)
							console.log(e);
					}
				}
			}
		}
	}

	// We do this because we don't want any errors to result in lost content!
	try
	{
		_clean_recursive(root);
		Util.Block.enforce_rules(root, block_settings);
	}
	catch(e)
	{
		if (typeof(console) == 'object') {
			if (console.warn)
				console.warn(e);
			else if (console.log)
				console.log(e);
		}
	}
};

UI.Clean.clean_URI = function clean_URI(uri)
{
	var local = Util.URI.extract_domain(uri) ==
		Util.URI.extract_domain(window.location);
		
	return (local)
		? Util.URI.strip_https_and_http(uri)
		: uri;
}

UI.Clean.clean_HTML = function clean_HTML(html, settings)
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


    for (var i in tests) {
        if (!tests[i].test || tests[i].test())
            html = html.replace(tests[i].pattern, tests[i].replacement);
	}

    return html;
};

UI.Clean.default_allowable_tags = 
	['A', 'ABBR', 'ACRONYM', 'ADDRESS', 'AREA', 'B', 'BDO', 'BIG', 'BLOCKQUOTE',
	'BR', 'BUTTON', 'CAPTION', 'CITE', 'CODE', 'COL', 'COLGROUP', 'DD', 'DEL',
	'DIV', 'DFN', 'DL', 'DT', 'EM', 'FIELDSET', 'FORM', 'H1', 'H2', 'H3', 'H4',
	'H5', 'H6', 'HR', 'I', 'IMG', 'INPUT', 'INS', 'KBD', 'LABEL', 'LI', 'MAP',
	'NOSCRIPT', 'OBJECT', 'OL', 'OPTGROUP', 'OPTION', 'P', 'PARAM', 'PRE', 'Q',
	'SAMP', 'SCRIPT', 'SELECT', 'SMALL', 'SPAN', 'STRONG', 'SUB', 'SUP', 'TABLE',
	'TBODY', 'TD', 'TEXTAREA', 'TFOOT', 'TH', 'THEAD', 'TR', 'TT', 'U', 'UL',
	'VAR'];
	
UI.Clean.default_allowable_inline_styles =
	['text-align', 'vertical-align', 'float', 'direction', 'display', 'clear',
	'list-style'];

UI.Clean.self_nesting_disallowed =
	['ABBR', 'ACRONYM', 'ADDRESS', 'AREA', 'B', 'BR', 'BUTTON', 'CAPTION',
	'CODE', 'DEL', 'DFN', 'EM', 'FORM', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6',
	'HR', 'I', 'IMG', 'INPUT', 'INS', 'KBD', 'LABEL', 'MAP', 'NOSCRIPT',
	'OPTION', 'P', 'PARAM', 'PRE', 'SCRIPT', 'SELECT', 'STRONG', 'TT', 'U',
	'VAR'].toSet();

// file UI.Clean_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents toolbar button.
 */
UI.Clean_Button = function()
{
	Util.OOP.inherits(this, UI.Button);

	this.image = 'cleanup.png';
	this.title = 'Clean up HTML';
	this.click_listener = function()
	{
		UI.Clean.clean(this._loki.body, this._loki.settings, true);
	};
};

// file UI.Clipboard_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an anchor. Contains code
 * common to both the button and the menu item.
 */
UI.Clipboard_Helper = function ClipboardHelper()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.is_selection_empty = function()
	{
		var sel = Util.Selection.get_selection(this._loki.window);
		return Util.Selection.is_collapsed(sel);
	};

	this.cut = function clipboard_cut()
	{
		if (!self.copy('Cut', 'X'))
			return;
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		Util.Range.delete_contents(rng);
		self._loki.focus();
	};

	this.copy = function clipboard_copy(command, accel)
	{
		// Get the HTML to copy
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var html = Util.Range.get_html(rng);
		//var text = rng.toString();
		
		if (Util.Selection.is_collapsed(sel)) {
			// If nothing is actually selected; do not overwrite the clipboard.
			return false;
		}

		// Unmassage and clean HTML
		var container = self._loki.document.createElement('DIV');
		container.innerHTML = html;
		self._loki.unmassage_node_descendants(container);
		
		// Clean the copied HTML. We pass an override to the block-level element
		// rule enforcer that specifies that inline content within paragraphs do
		// not have to be wrapped in (e.g.) paragraph tags. This prevents inline
		// content that is being copied from being treated as its own paragraph.
		UI.Clean.clean(container, self._loki.settings, false, {
			overrides: {DIV: Util.Block.BLOCK}
		});
		html = container.innerHTML;

		// Move HTML to clipboard
		try {
			if (UI.Clipboard_Helper._gecko) {
				_gecko_copy(html, command || 'Copy', accel || 'C');
				return false;
			} else {
				_ie_copy(html);
			}
		} finally {
			self._loki.focus();
		}
		
		return true;
	};

	this.paste = function clipboard_paste()
	{
		try {
			if (UI.Clipboard_Helper._gecko) {
				_gecko_paste();
			} else {
				_ie_paste();
			}
		} finally {
			self._loki.focus();
		}
	};

	this.delete_it = function() // delete is a reserved word
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		rng.deleteContents();
		self._loki.focus();
	};

	this.select_all = function()
	{
		self._loki.exec_command('SelectAll');
		self._loki.focus();
	};

	this.is_security_error = function(e)
	{
		return ( e.message != null && e.message.indexOf != null && e.message.indexOf('Clipboard_Helper') > -1 );
	};
	
	function _show_gecko_privileges_warning()
	{
		var message = "Your browser requires that you give explicit permission for " +
			"your clipboard to be accessed, so you may see a security warning " +
			"after dismissing this message. You are free to deny this permssion, " +
			"but if you do, you may be unable to cut, copy, or paste into this " +
			"document.";
		
		UI.Messenger.display_once_per_duration('gecko clipboard warning',
			message, 45);
	}
	
	function _gecko_clipboard_error(command, accel)
	{
		var key;
		if (!self._loki.owner_window.GeckoClipboard) {
			key = ((Util.Browser.Mac) ? '⌘' : 'Ctrl-') + accel;
			alert("In your browser, you must either choose " + command + " " +
				"from the Edit menu, or press " + key + ".");
		}
	}

	function _gecko_copy(html, command, accel)
	{
		_gecko_clipboard_error(command, accel);
	};

	function _ie_copy(html)
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);

		// transfer from iframe to editable div
		// select all of editable div
		// copy from editable div
		UI.Clipboard_Helper_Editable_Iframe.contentWindow.document.body.innerHTML = html;
		UI.Clipboard_Helper_Editable_Iframe.contentWindow.document.execCommand("SelectAll", false, null);
		UI.Clipboard_Helper_Editable_Iframe.contentWindow.document.execCommand("Copy", false, null);

		// Reposition cursor
		rng.select();
	};

	function _gecko_paste()
	{
		_gecko_clipboard_error('Paste', 'V');
	};

	function _ie_paste()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var parent = rng.parentElement();
		
		// Ensure that the selection is within the editing document.
		// if (parent && parent.ownerDocument != self._loki.document)
		// 	return;

		// Make clipboard iframe editable
		// clear editable div
		// select all of editable div
		// paste into editable div
		UI.Clipboard_Helper_Editable_Iframe.contentWindow.document.body.contentEditable = true;
		UI.Clipboard_Helper_Editable_Iframe.contentWindow.document.body.innerHTML = "";
		UI.Clipboard_Helper_Editable_Iframe.contentWindow.document.execCommand("SelectAll", false, null);
		UI.Clipboard_Helper_Editable_Iframe.contentWindow.document.execCommand("Paste", false, null);

		// Get HTML
		var html = UI.Clipboard_Helper_Editable_Iframe.contentWindow.document.body.innerHTML;

		// Massage and clean HTML
		var nodeName = 'DIV';
		if (rng.text != null && rng.text == "") {
			if (typeof(parent) == 'object' && parent.tagName)
				nodeName = parent.tagName;
		}
		
		function clean(nodeName) {
			var temp = self._loki.document.createElement(nodeName);
			temp.innerHTML = html;
			
			UI.Clean.clean(temp, self._loki.settings);
			self._loki.massage_node_descendants(temp);
			return temp.innerHTML;
		}
		
		var cleanedHTML;
		try {
			cleanedHTML = clean(nodeName);
		} catch (e) {
			if (nodeName != 'DIV')
				cleanedHTML = clean('DIV');
			else
				throw e;
		}

		// Actually paste HTML
		rng.pasteHTML(cleanedHTML);
		rng.select();
	};
};

UI.Clipboard_Helper._gecko = (typeof(Components) == 'object');

// We need to create this iframe as a place to put code that
// Gecko needs to run with special privileges, for which
// privileges Gecko requires that the code be signed.
// (But we don't want to sign _all_ of Loki, because the page
// that invokes the javascript has to be signed with the 
// javascript, and we want to be able to use Loki on dynamic
// pages; sigining dynamic pages would be too inconvenient, not
// to mention slow.)
// We create this here, on the assumption that it will have
// loaded by the time we need it.
//
// For more information about how to sign scripts, see 
// privileged/HOWTO

/** @ignore */
UI.Clipboard_Helper._setup_done = false

/** @ignore */
UI.Clipboard_Helper._setup = function setup_clipboard_helper() {
	var base_uri = (arguments[0]
	 	? Util.URI.build(Util.URI.normalize(arguments[0]))
		: null);
	var helper_src = null;
	
	if (UI.Clipboard_Helper._setup_done)
		return;
	
	function watch_onload(func)
	{
		if (typeof(Loki) == "object" && Loki.is_document_ready()) {
			func();
			return;
		}
		
		if (document.addEventListener) {
			document.addEventListener('DOMContentLoaded', func, false);
			window.addEventListener('load', func, false);
		} else if (window.attachEvent) {
			window.attachEvent('onload', func);
		} else {
			window.onload = func;
		}
	}
	
	function create_hidden_iframe(src)
	{
		var called = false;
		var frame = Util.Document.create_element(document, 'iframe',
		{
			src: src,
			style: {
				position: 'absolute',
				box: [-500, -500, 2]
			}
		});
		
		function append_helper_iframe()
		{
			if (called)
				return;
			called = true;
			
			var body = (document.getElementsByTagName('BODY')[0] ||
				document.documentElement);
			body.appendChild(frame);
		}
		
		watch_onload(append_helper_iframe);
		
		return frame;
	}
	
	function make_uri(path)
	{
		if (base_uri.charAt(base_uri.length - 1) == '/')
			return base_uri + path;
		else
			return [base_uri, path].join('/');
	}
	
	if (UI.Clipboard_Helper._gecko) {
		// Gecko
		// Our clipboard support doesn't work there anymore. Dropping it.
	} else {
		// everyone else
		if (typeof(UI__Clipboard_Helper_Editable_Iframe__src) == 'string') {
			// PHP helper is providing this for us.
			helper_src = UI__Clipboard_Helper_Editable_Iframe__src;
		} else if (base_uri) {
			helper_src = make_uri('auxil/loki_blank.html');
		} else {
			return;
		}
		UI.Clipboard_Helper_Editable_Iframe = create_hidden_iframe(helper_src);
	}
	
	UI.Clipboard_Helper._setup_done = true;
}

UI.Clipboard_Helper._setup(); 
// file UI.Clipboard_Menugroup.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing a clipboard menugroup. 
 */
UI.Clipboard_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._clipboard_helper = (new UI.Clipboard_Helper).init(this._loki);
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var menuitems = [];

		var self = this;
		menuitems.push( (new UI.Menuitem).init({ 
			label : 'Cut',
			listener : function()
			{
				self._clipboard_helper.cut();
			},
			disabled : this._clipboard_helper.is_selection_empty()
		}) );
		menuitems.push( (new UI.Menuitem).init({ 
			label : 'Copy',
			listener : function()
			{
				self._clipboard_helper.copy();
			},
			disabled : this._clipboard_helper.is_selection_empty()
		}) );
		menuitems.push( (new UI.Menuitem).init({ 
			label : 'Paste',
			listener : function()
			{
				self._clipboard_helper.paste();
			}
			//disabled : this._clipboard_helper.is_selection_empty()
		}) );
		menuitems.push( (new UI.Menuitem).init({ 
			label : 'Delete',
			listener : this._clipboard_helper.delete_it,
			disabled : this._clipboard_helper.is_selection_empty()
		}) );

		menuitems.push( (new UI.Separator_Menuitem).init() );

		menuitems.push( (new UI.Menuitem).init({ 
			label : 'Select all',
			listener : this._clipboard_helper.select_all
		}) );

		return menuitems;
	};
};

// file UI.Copy_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents toolbar button.
 */
UI.Copy_Button = function()
{
	Util.OOP.inherits(this, UI.Button);

	this.image = 'copy.png';
	this.title = 'Copy (Ctrl+C)';
	this.click_listener = function()
	{
		this._clipboard_helper.copy();
	};

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._clipboard_helper = (new UI.Clipboard_Helper).init(this._loki);
		return this;
	};
};

// file UI.Copy_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Copy_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) {
		if (Util.Browser.Gecko && Util.Browser.Windows && !this.loki.owner_window.GeckoClipboard)
			return false;
		return this.matches_keycode(e, 67) && e.ctrlKey;
	}; // Ctrl-C
	
	this.action = function() 
	{
		// try-catch so that if anything should go wrong, copy
		// still happens
		try
		{
			this._clipboard_helper.copy();
			return false;
		}
		catch(e)
		{
			return true;
		}
	};

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._clipboard_helper = (new UI.Clipboard_Helper).init(this._loki);
		return this;
	};
};

// file UI.Cut_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents toolbar button.
 */
UI.Cut_Button = function()
{
	Util.OOP.inherits(this, UI.Button);

	this.image = 'cut.png';
	this.title = 'Cut (Ctrl+X)';
	this.click_listener = function()
	{
		this._clipboard_helper.cut();
	};

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._clipboard_helper = (new UI.Clipboard_Helper).init(this._loki);
		return this;
	};
};

// file UI.Cut_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Cut_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) {
		if (Util.Browser.Gecko && Util.Browser.Windows && !this.loki.owner_window.GeckoClipboard)
			return false;
		return this.matches_keycode(e, 88) && e.ctrlKey;
	}; // Ctrl-X
	this.action = function() 
	{
		// try-catch so that if anything should go wrong, cut
		// still happens
		try
		{
			this._clipboard_helper.cut();
			return false;
		}
		catch(e)
		{
			return true;
		}
	};

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._clipboard_helper = (new UI.Clipboard_Helper).init(this._loki);
		return this;
	};
};

// file UI.Delete_Element_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Delete_Element_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return ( this.matches_keycode(e, 8) || this.matches_keycode(e, 127) ); }; // Backspace or delete

	this.action = function()
	{
		if ( this._image_helper.is_selected() )
		{
			this._image_helper.remove_image();
			return false; // cancel event's default action
		}
		else if ( this._anchor_helper.is_selected() )
		{
			this._anchor_helper.remove_anchor();
			return false;
		}
		else if ( this._hr_helper.is_selected() )
		{
			this._hr_helper.remove_hr();
			return false;
		}
		else if ( this._table_helper.is_table_selected() && 
				  !this._table_helper.is_cell_selected() && 
				  confirm('Really remove table? WARNING: This cannot be undone.') )
		{
			this._table_helper.remove_table();
			return false;
		}
		else
		{
			// Prevent the following IE bug: "When there is no apparent focus (e.g. when the page first 
			// loads and you haven't done anything yet), clicking below the last element in the Loki 
			// area) and hitting backspace zaps all of the content in the Loki area and you lose the 
			// cursor."
			if (Util.Browser.IE) // not sure this restraint is necessary, but there's 
								 // no point risking unexpected behavior in Gecko
			{
				this._loki.window.focus();
				//this._loki.exec_command('SelectAll');
				//var sel = Util.Selection.get_selection(this._loki.window);
				//Util.Selection.collapse(sel, false); // to end
			}
		}

		return true; // don't cancel event's default action
	};

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._image_helper = (new UI.Image_Helper).init(this._loki);
		this._anchor_helper = (new UI.Anchor_Helper).init(this._loki);
		this._hr_helper = (new UI.HR_Helper).init(this._loki);
		this._table_helper = (new UI.Table_Helper).init(this._loki);
		return this;
	};
};

// file UI.Dialog.js
/**
 * Declares instance variables. <code>init</code> must be called to
 * initialize instance variables.
 *
 * @constructor
 *
 * @class Base class for classes which represent dialog windows. Example usage:
 * <p>
 * <pre>
 * var dialog = new UI.Image_Dialog;   <br />
 * dialog.init({ data_source : '/fillmorn/feed.rss',   <br />
 *               submit_listener : this._insert_image,	<br />
 *               selected_item : { link : '/global_stock/images/1234.jpg' }	  <br />
 * });	 <br />
 * dialog.display();
 * </pre>
 */
UI.Dialog = function()
{
	this._external_submit_listener;
	this._data_source;
	this._base_uri;
	this._initially_selected_item;
	this._dialog_window;
	this._doc;
	this._udoc;

	this._dialog_window_width = 600;
	this._dialog_window_height = 300;

	/**
	 * Initializes the dialog.
	 *
	 * @param   params  object containing the following named parameters:
	 *                  <ul>
	 *                  <li>data_source - the RSS feed from which to read this file</li>
	 *                  <li>submit_listener - the function which will be called when
	 *                  the dialog's submit button is pressed</li>
	 *                  <li>selected_item - an object with the same properties as
	 *                  the object passed by this._internal_submit_handler (q.v.) to
	 *                  submit_handler (i.e., this._external_submit_handler). Used e.g. to
	 *                  determine which if any image is initially selected.</li>
	 *                  </ul>
	 */
	this.init = function init_dialog(params)
	{
		this._data_source = params.data_source;
		this._base_uri = params.base_uri;
		this._external_submit_listener = params.submit_listener;
		this._remove_listener = params.remove_listener;
		this._initially_selected_item = params.selected_item;

		return this;
	};

	this.open = function open_dialog()
	{
		var self = this;
		
		function populate_dialog() {
			if (self._dialog_window._dialog_populated)
				return;
			
			self._dialog_window._dialog_populated = true;
			
			self._doc = self._dialog_window.window.document;
			self._dialog_window.document = self._doc;
			self._udoc = new Util.Document(self._doc);
			
			self._root =
				self._doc.body.appendChild(self._doc.createElement('DIV'));
			
			// Work around an IE display glitch: don't render until the document
			// has been built.
			if (Util.Browser.IE)
				self._doc.body.style.display = 'none';
			try {
				self._dialog_window.body = self._doc.body;
				self._set_title();
				self._append_style_sheets();
				self._add_dialog_listeners();
				self._append_main_chunk();
				self._apply_initially_selected_item();
			} finally {
				self._doc.body.style.display = '';
			}
		}
		
		var already_open = (this._dialog_window && this._dialog_window.window
			&& !this._dialog_window.window.closed);
		
		if (already_open) {
			this._dialog_window.window.focus();
		} else {
			this._dialog_window = new Util.Window;
			var window_opened = this._dialog_window.open(
				this._base_uri + 'auxil/loki_dialog.html',
				'_blank', 'status=1,scrollbars=1,toolbars=1,resizable,width=' +
					this._dialog_window_width + ',height=' + 
					this._dialog_window_height + ',dependent=yes,dialog=yes'
			);
			
			if (!window_opened) // popup blocker
				return false;
			_loki_enqueue_dialog(this._dialog_window.window, populate_dialog);
			Util.Event.observe(this._dialog_window.window, 'load',
				populate_dialog);
		}
	};
	
	/**
	 * Creates a new activity indicator (UI.Activity) for the dialog.
	 */
	this.create_activity_indicator = function(kind, text)
	{
		if (!text)
			var text = null;
		
		return new UI.Activity(this._base_uri, this._dialog_window.document, kind, text);
	}
	
	/**
	 * Creates a new form (Util.Form) for the dialog.
	 */
	this.create_form = function(params)
	{
		if (!params)
			var params = {};
		return new Util.Form(this.dialog_window.document, params);
	}

	/**
	 * Sets the page title
	 */
	this._set_title = function() { /* do nothing by default */ };

	/**
	 * Appends all the style sheets needed for this dialog.
	 */
	this._append_style_sheets = function() { /* do nothing by default */ };

	/**
	 * Adds all the dialog event listeners for this dialog.
	 */
	this._add_dialog_listeners = function()
	{
		var self = this;
		var enter_unsafe =
			['TEXTAREA', 'BUTTON', 'SELECT', 'OPTION'].toSet();
	
		
		//Util.Event.add_event_listener(this._dialog_window.body, 'keyup', function(event) 
		this._dialog_window.document.onkeydown = function(event)
		{ 
			event = event == null ? self._dialog_window.window.event : event;
			var target = event.srcElement == null ? event.target : event.srcElement;

			// Enter key
			if (event.keyCode == 13 && target && !(target.tagName in enter_unsafe)) {
				self._internal_submit_listener();	
				return false;
			}
			
			if ( event.keyCode == 27 ) // escape
			{
				self._internal_cancel_listener();	
				return false;
			}

			// (IE) Disable refresh shortcut
			// [I should think IE and Gecko could be covered
			// together; but can't figure it out right now, tired.]
			if ( event.ctrlKey == true && event.keyCode == 82 ) // ctrl-r
			{
				return false;
			}
		};
		//});
		this._dialog_window.document.onkeypress = function(event)
		{
			event = event == null ? self._dialog_window.window.event : event;
			// (Gecko) Disable refresh shortcut
			if ( event.ctrlKey == true && event.charCode == 114 ) // ctrl-r
			{
				return false;
			}
		};

		/*
		this._dialog_window.window.onbeforeunload = 
		this._dialog_window.document.body.onbeforeunload = function(event)
		{
			event = event == null ? self._dialog_window.window.event : event;
			event.returnValue = "If you do navigate away, your changes in this dialog will be lost, and the dialog may close.";
			return event.returnValue;
		};

		this._dialog_window.window.onunload = function(event)
		{
			self._internal_cancel_listener();
		};
		*/
	};

	/**
	 * Appends the main part of the page, i.e. the children of the body element.
	 */
	this._append_main_chunk = function()
	{
		this._main_chunk = this._dialog_window.document.createElement('FORM');
		this._main_chunk.action = 'javascript:void(0);';
		//this._dialog_window.body.appendChild(this._main_chunk);
		this._root.appendChild(this._main_chunk);

		this._populate_main();
	};

	/**
	 * Stub for adding the main content of the dialog.
	 */
	this._populate_main = function()
	{
		this._append_submit_and_cancel_chunk();
	};

	/**
	 * Creates and appends a chunk containing submit and cancel
	 * buttons. Also attaches 'click' event listeners to the submit and
	 * cancel buttons: this._internal_submit_listener for submit, and
	 * this._internal_cancel_listener for cancel.
	 *
	 * @param	submit_text		(optional) the text to use on the submit button. Defaults to "OK".
	 * @param	cancel_text		(optional) the text to use on the cancel button. Defaults to "Cancel".
	 */
	this._append_submit_and_cancel_chunk = function(submit_text, cancel_text)
	{
		var self = this;
		
		function create_button(text, click_listener) {
			var b = self._udoc.create_element('BUTTON', {type: 'button'}, [text]);
			Util.Event.add_event_listener(b, 'click', click_listener.bind(self));
			return b;
		}
		
		var chunk = this._doc.createElement('DIV');
		Util.Element.add_class(chunk, 'submit_and_cancel_chunk');
		
		var submit = create_button(submit_text || 'OK', this._internal_submit_listener);
		Util.Element.add_class(submit, 'ok');
		chunk.appendChild(submit);
		chunk.appendChild(create_button(cancel_text || 'Cancel', this._internal_cancel_listener));

		this._root.appendChild(chunk);
	};

	/**
	 * Apply the initially selected item. Extending functions should do things
	 * like setting the link_input's value to the initially_selected_item's uri.
	 */
	this._apply_initially_selected_item = function()
	{
	};

	/**
	 * This resizes the window to its content. 
	 *
	 * @param	horizontal	(boolean) Sometimes we don't want to resize horizontally 
	 *						to the content, because since the content is not fixed-width, 
	 *						it will expand to take up the whole screen, which is ugly. So
	 *						false here disables horiz resize.
	 * @param	vertical	(boolean) same thing
	 *						
	 */
	this._resize_dialog_window = function(horizontal, vertical)
	{
		// Skip IE // XXX bad
		if ( document.all )
			return;

		if ( horizontal == null )
			horizontal = true;
		if ( vertical == null )
			vertical = true;

		// From NPR.org
		var win = this._dialog_window.window;
		var doc = this._dialog_window.document;

		if (win.sizeToContent)	// Gecko
		{
			var w = win.outerWidth;
			var h = win.outerHeight;

			//win.resizeBy(win.innerWidth * 2, win.innerHeight * 2);
			//win.sizeToContent();	
			//win.sizeToContent();	
			//win.resizeBy(win.innerWidth + 10, win.innerHeight + 10);
			win.resizeBy(doc.documentElement.clientWidth + 10 + (win.outerWidth - win.innerWidth) - win.outerWidth, 
						 doc.documentElement.clientHeight + 20 + (win.outerHeight - win.innerHeight) - win.outerHeight);
			//win.resizeBy(this._root.clientWidth + 10 - win.outerWidth, 
			//			 this._root.clientHeight + 10 - win.outerHeight);
			//win.resizeBy(win.innerWidth + 10 - win.outerWidth, 
			//			 win.innerHeight + 10 - win.outerHeight);
			//win.resizeBy(10,0); 

/*
		try {
			win.scrollBy(1000, 1000);
			if (win.scrollX > 0 || win.scrollY > 0) {
				win.resizeBy(win.innerWidth * 2, win.innerHeight * 2);
				win.sizeToContent();
				win.scrollTo(0, 0);
				var x = parseInt(screen.width / 2.0) - (win.outerWidth / 2.0);
				var y = parseInt(screen.height / 2.0) - (win.outerHeight / 2.0);
				win.moveTo(x, y);
			}
			mb('resized dialog');
		} catch(e) { mb('error in resize_dialog_window:' + e.message); throw(e); }
*/


			if ( !horizontal )
				win.outerWidth = w;
			if ( !vertical )
				win.outerWidth = h;
		}
		else  // IE
		{  
			//old ie method, doesn't work for dialogs:
			win.resizeTo(100,100);  
			docWidth = Math.max(this._main_chunk.offsetWidth + 70, 200);  
			docHeight = Math.max(this._main_chunk.offsetHeight + 40, doc.body.scrollHeight) + 18;
			win.resizeTo(docWidth,docHeight);
			// not tested yet ...:
/*
			docWidth = Math.max(this._main_chunk.offsetWidth + 70, 200);  
			docHeight = Math.max(this._main_chunk.offsetHeight + 40, doc.body.scrollHeight) + 18;
			if ( horizontal )
				win.dialogWidth = docWidth;
			if ( vertical )
				win.dialogHeight = docHeight;
*/
		}
	};

	/**
	 * Called as an event listener when the user clicks the submit
	 * button. Extending functions should (a) gather information needed
	 * to call the function referenced by this._submit_listener, (b) call
	 * that function, and (c) close this dialog.
	 */
	this._internal_submit_listener = function()
	{
		// Close dialog window
		this._dialog_window.window.close();
	};

	/**
	 * Called as an event listener when the user clicks the submit
	 * button. Extending functions may (a) gather information needed to
	 * call the function referenced by this._submit_listener, and (b) call
	 * that function. They should (c) close this dialog.
	 */
	this._internal_cancel_listener = function()
	{
		// Close dialog window
		this._dialog_window.window.close();
	};
};

var _loki_dialog_queue = [];
var _loki_unmatched_dialogs = [];

function _loki_enqueue_dialog(dialog_window, onload) {
	var i;
	
	for (i = 0; i < _loki_unmatched_dialogs.length; i++) {
		if (_loki_unmatched_dialogs[i] === dialog_window) {
			_loki_unmatched_dialogs.splice(i, 1);
			onload();
			return;
		}
	}
	
	_loki_dialog_queue.push({window: dialog_window, onload: onload});
}

window._loki_dialog_postback = function(dialog_window) {
	var i, callback, called = false;
	
	for (i = 0; i < _loki_dialog_queue.length; i++) {
		if (_loki_dialog_queue[i].window === dialog_window) {
			callback = _loki_dialog_queue[i].onload;
			_loki_dialog_queue.splice(i, 1);
			
			if (!called) {
				callback();
				called = true;
			}
		}
	}
	
	if (!called) {
		_loki_unmatched_dialogs.push(dialog_window);
	}
};

// file UI.Double_Click.js
/**
 * Declares instance variables.
 * @class A body double-click listener. For extending only.
 */
UI.Double_Click = function DoubleClick()
{
	this.init = function(loki)
	{
		this._loki = loki;
		return this;
	};
	
	this.double_click = function() {
		throw new Error('unimplemented');
	};
};

// file UI.Error_Display.js
/**
 * @class Provides a nicely-formatted inline error display.
 * @constructor
 * @param {HTMLElement} the element into which the message will be inserted
 */
UI.Error_Display = function(message_container)
{
	var doc = message_container.ownerDocument;
	var dh = new Util.Document(doc);
	
	var self = this;
	
	this.display = null;
	
	function create(message, options)
	{
		if ('function' == typeof(options)) {
		    options = [['Retry.', options]];
		}
		
		self.display = dh.create_element('p', {className: 'error'});
		self.display.innerHTML = message;
		
		function add_action(text, action) {
		    var link = dh.create_element('a', {
				href: '#',
				className: 'action'
			});
			link.innerHTML = text;
			
			Util.Event.add_event_listener(link, 'click', function(e) {
				if (!e)
					var e = window.event;

				try {
					action();
				} catch (e) {
					self.show('That didn\'t work: ' + (e.message || e), action);
				} finally {
					return Util.Event.prevent_default(e);
				}
			});
			
			self.display.appendChild(link);
		}
		
		if (options) {
    		options.each(function (action) {
    		   add_action(action[0], action[1]); 
    		});
    	}
		
		message_container.appendChild(self.display);
	}
	
	function remove()
	{
		if (this.display.parentNode)
			this.display.parentNode.removeChild(this.display);
		this.display = null;
	}
	
	this.show = function(message, retry, retry_text)
	{
		if (!retry)
			var retry = null;
		
		if (this.display)
			remove.call(this);
		
		create.call(this, message, retry, retry_text);
	}
	
	this.clear = function()
	{
		if (this.display)
			remove.call(this);
	}
}

// file UI.Error_State.js
/**
 * @class A canned state for a Util.State_Machine for displaying errors.
 * @see UI.Error_Display
 */
UI.Error_State = function(message_container)
{
	var display = new UI.Error_Display(message_container);
	var error = null;
	
	/**
	 * Sets the error message. Note that in order for the message to really be
	 * displayed, the machine must enter this state.
	 *
	 * @param	message	Error message to display (either a string or a
	 * 					DocumentFragment).
	 * @param	retry	If provided, the error message will include a "retry"
	 *					link that, if clicked on by the user, will call the
	 *					function provided here.
	 */
	this.set = function(message, retry)
	{
		error = {message: message, retry: (retry || null)};
	}
	
	this.enter = function()
	{
		if (!error) {
			throw new Error('Entered error state, but there is no error!');
		}

		display.show(error.message, error.retry);
	}
	
	this.exit = function()
	{
		display.clear();
		error = null;
	}
} 
// file UI.Find_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for a find-and-replace button.
 */
UI.Find_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'search_replace.png';
	this.title = 'Find and replace (Ctrl+F)';
	this.click_listener = function() { self._find_helper.open_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._find_helper = (new UI.Find_Helper).init(this._loki);
		return this;
	};
};

// file UI.Find_Dialog.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class An anchor dialog window.
 */
UI.Find_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);

	this._dialog_window_width = 615;
	this._dialog_window_height = 200;

	this.init = function(params)
	{
		this._find_listener = params.find_listener;
		this._replace_listener = params.replace_listener;
		this._replace_all_listener = params.replace_all_listener;
		this._select_beginning_listener = params.select_beginning_listener;
		this.superclass.init.call(this, params);
	};

	this._set_title = function()
	{
		this._dialog_window.document.title = "Find and replace";
	};

	this._append_style_sheets = function()
	{
		this.superclass._append_style_sheets.call(this);
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Find_Dialog.css');
	};

	this._populate_main = function()
	{
		this._append_find_chunk();
		this._append_submit_and_cancel_chunk();
		var self = this;
		setTimeout(function () { self._resize_dialog_window(true, true); }, 1000);
		//this._resize_dialog_window(false, true);
	};

	this._append_find_chunk = function()
	{
		var self = this;

		// Create Search input and label
		this._search_input = this._dialog_window.document.createElement('INPUT');
		this._search_input.setAttribute('size', '40');
		this._search_input.setAttribute('id', 'search_input');
		//this._search_input.value = 'as'; // XXX tmp

		var search_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(search_label, 'label');
		search_label.setAttribute('for', 'search_input');
		search_label.innerHTML = 'Search&nbsp;for:&nbsp;';
		//search_label.appendChild( this._dialog_window.document.createTextNode('Search for: ') );

		var search_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(search_div, 'field');
		search_div.appendChild(search_label);
		search_div.appendChild(this._search_input);
		
		// Create Replace input and label
		this._replace_input = this._dialog_window.document.createElement('INPUT');
		this._replace_input.setAttribute('size', '40');
		this._replace_input.setAttribute('id', 'replace_input');
		//this._replace_input.value = 'hmm'; // XXX tmp

		var replace_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(replace_label, 'label');
		replace_label.setAttribute('for', 'replace_input');
		replace_label.innerHTML = 'Replace&nbsp;with:&nbsp;';
		//replace_label.appendChild( this._dialog_window.document.createTextNode('Replace with: ') );

		var replace_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(replace_div, 'field');
		replace_div.appendChild(replace_label);
		replace_div.appendChild(this._replace_input);

		// Create Match Case checkbox and label
		this._matchcase_checkbox = this._dialog_window.document.createElement('INPUT');
		this._matchcase_checkbox.setAttribute('type', 'checkbox');
		this._matchcase_checkbox.setAttribute('id', 'matchcase_checkbox');

		var matchcase_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(matchcase_label, 'label');
		matchcase_label.setAttribute('for', 'matchcase_checkbox');
		matchcase_label.appendChild( this._dialog_window.document.createTextNode('Match case') );

		// Create match case div
		var matchcase_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(matchcase_div, 'field');
		matchcase_div.appendChild(this._matchcase_checkbox);
		matchcase_div.appendChild(matchcase_label);

		// Create options div
		var options_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(options_div, 'options');
		options_div.appendChild(search_div);
		options_div.appendChild(replace_div);
		options_div.appendChild(matchcase_div);


		// Create Find Next button
		this._find_button = this._dialog_window.document.createElement('BUTTON');
		Util.Element.add_class(this._find_button, 'ok');
		this._find_button.setAttribute('type', 'submit');
		this._find_button.appendChild(this._dialog_window.document.createTextNode('Find Next'));
		Util.Event.add_event_listener(this._find_button, 'click', 
			function(event)
			{
				// Since this is a submit button (in order for "enter" in the inputs
				// to cause this button to be fired), the javascript:void(0) form
				// will be submitted when this button in clicked, and in FF 1.0 
				// that causes an error about transferring data from an encrypted
				// page over an unencrypted connection.
				// So prevent the form from being submitted.
				if ( event.preventDefault )
					event.preventDefault();

				var ret = self._find_listener( self._search_input.value, 
											   self._matchcase_checkbox.checked, 
											   false, //self._findbackwards_checkbox.checked,
											   true );
				if ( ret == UI.Find_Helper.NOT_FOUND && 
					 self._dialog_window.window.confirm('Match not found. Continue from beginning?') )
				{
					self._select_beginning_listener();
					var ret = self._find_listener( self._search_input.value, 
												   self._matchcase_checkbox.checked, 
												   false, //self._findbackwards_checkbox.checked,
												   true );
					if ( ret == UI.Find_Helper.NOT_FOUND )
						self._dialog_window.window.alert('Match not found.');
				}
			}
		);

		// Create Replace button
		this._replace_button = this._dialog_window.document.createElement('BUTTON');
		this._replace_button.setAttribute('type', 'button');
		this._replace_button.appendChild(this._dialog_window.document.createTextNode('Replace'));
		Util.Event.add_event_listener(this._replace_button, 'click', 
			function()
			{
				var ret = self._replace_listener( self._search_input.value, 
												  self._replace_input.value, 
												  self._matchcase_checkbox.checked, 
												  false, //self._findbackwards_checkbox.checked,
												  true );
				if ( ret == UI.Find_Helper.NOT_FOUND && 
					 self._dialog_window.window.confirm('Match not found. Continue from beginning?') )
				{
					self._select_beginning_listener();
					var ret = self._replace_listener( self._search_input.value, 
													  self._replace_input.value, 
													  self._matchcase_checkbox.checked, 
													  false, //self._findbackwards_checkbox.checked,
													  true );
					if ( ret == UI.Find_Helper.NOT_FOUND )
						self._dialog_window.window.alert('Match not found.');
				}

				if ( ret == UI.Find_Helper.REPLACED_LAST_MATCH && 
					 self._dialog_window.window.confirm('Replaced last match. Continue from beginning?') )
				{
					self._select_beginning_listener();
					var ret = self._find_listener( self._search_input.value, 
												   self._matchcase_checkbox.checked, 
												   false, //self._findbackwards_checkbox.checked,
												   true );
					if ( ret == UI.Find_Helper.NOT_FOUND )
						self._dialog_window.window.alert('Match not found.');
				}
			}
		);

		// Create Replace All button
		this._replaceall_button = this._dialog_window.document.createElement('BUTTON');
		this._replaceall_button.setAttribute('type', 'button');
		this._replaceall_button.appendChild(this._dialog_window.document.createTextNode('Replace All'));
		Util.Event.add_event_listener(this._replaceall_button, 'click', 
			function()
			{
				var i = self._replace_all_listener( self._search_input.value, 
													self._replace_input.value, 
													self._matchcase_checkbox.checked, 
													false, //self._findbackwards_checkbox.checked,
													true );
				if ( i < 1 )
					self._dialog_window.window.alert('Not found.');
				else
					self._dialog_window.window.alert('Replaced ' + i + ' instances.');
			}
		);

		/*
		// Create Cancel button
		this._cancel_button = this._dialog_window.document.createElement('BUTTON');
		this._cancel_button.setAttribute('type', 'button');
		this._cancel_button.appendChild(this._dialog_window.document.createTextNode('Close'));
		Util.Event.add_event_listener(this._cancel_button, 'click', function() { self._internal_cancel_listener(); });
		*/

		// Create actions div
		var actions_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(actions_div, 'actions');

		var actions_ul = this._dialog_window.document.createElement('UL');
		actions_div.appendChild(actions_ul);

		var find_button_li = this._dialog_window.document.createElement('LI');
		var replace_button_li = this._dialog_window.document.createElement('LI');
		var replaceall_button_li = this._dialog_window.document.createElement('LI');
		actions_ul.appendChild(find_button_li);
		actions_ul.appendChild(replace_button_li);
		actions_ul.appendChild(replaceall_button_li);

		find_button_li.appendChild(this._find_button);
		replace_button_li.appendChild(this._replace_button);
		replaceall_button_li.appendChild(this._replaceall_button);

	/*
		// Create Find Backwards checkbox and label
		this._findbackwards_checkbox = this._dialog_window.document.createElement('INPUT');
		this._findbackwards_checkbox.setAttribute('type', 'checkbox');
		this._findbackwards_checkbox.setAttribute('id', 'findbackwards_checkbox');

		var findbackwards_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(findbackwards_label, 'label');
		findbackwards_label.setAttribute('for', 'findbackwards_checkbox');
		findbackwards_label.appendChild( this._dialog_window.document.createTextNode('Find backwards') );
	*/

		// Create heading
		var h1 = this._dialog_window.document.createElement('H1');
		h1.innerHTML = 'Find and replace';

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : '', document : this._dialog_window.document});

		// Append options and actions to fieldset
		fieldset.fieldset_elem.appendChild(options_div);
		fieldset.fieldset_elem.appendChild(actions_div);
	/*
		fieldset.fieldset_elem.appendChild(this._findbackwards_checkbox);
		fieldset.fieldset_elem.appendChild(findbackwards_label);
	*/

		// Append h1 and fieldset chunk to dialog
		this._main_chunk.appendChild(h1);
		this._main_chunk.appendChild(fieldset.chunk);
	};

	this._append_submit_and_cancel_chunk = function(submit_text, cancel_text)
	{
		// Init submit and cancel text
		submit_text = submit_text == null ? 'OK' : submit_text;
		cancel_text = cancel_text == null ? 'Close' : cancel_text;


		// Setup submit and cancel buttons

		var submit_button = this._dialog_window.document.createElement('BUTTON');
		var cancel_button = this._dialog_window.document.createElement('BUTTON');

		submit_button.setAttribute('type', 'button');
		cancel_button.setAttribute('type', 'button');

		submit_button.appendChild( this._dialog_window.document.createTextNode(submit_text) );
		cancel_button.appendChild( this._dialog_window.document.createTextNode(cancel_text) );

		var self = this;
		Util.Event.add_event_listener(submit_button, 'click', function() { self._internal_submit_listener(); });
		Util.Event.add_event_listener(cancel_button, 'click', function() { self._internal_cancel_listener(); });

		Util.Element.add_class(submit_button, 'ok');
		

		// Setup their containing chunk
		var submit_and_cancel_chunk = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(submit_and_cancel_chunk, 'submit_and_cancel_chunk');
		submit_and_cancel_chunk.appendChild(cancel_button);
		//submit_and_cancel_chunk.appendChild(submit_button);


		// Append their containing chunk
		//this._dialog_window.body.appendChild(submit_and_cancel_chunk);
		this._root.appendChild(submit_and_cancel_chunk);
	};
};

// file UI.Find_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for finding and replacing.
 */
UI.Find_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.open_dialog = function()
	{
		if ( this._dialog == null )
			this._dialog = new UI.Find_Dialog;
		this._dialog.init({ base_uri : self._loki.settings.base_uri,
							 	   find_listener : self.find,
							 	   replace_listener : self.replace,
							 	   replace_all_listener : self.replace_all,
		                           select_beginning_listener : self.select_beginning });
		this._dialog.open();
	};

	this.find = function(search_str, match_case, match_backwards, wrap)
	{
		try // Gecko
		{
			// window.find( searchString, caseSensitive, backwards, wrapAround, showDialog, wholeWord, searchInFrames ) ;
			var was_found = self._loki.window.find(search_str, match_case, match_backwards, true, false, false);
			return was_found ? UI.Find_Helper.FOUND : UI.Find_Helper.NOT_FOUND;
	//oEditor.FCK.EditorWindow.find( document.getElementById('txtFind').value, bCase, false, false, bWord, false, false ) ;
		}
		catch(e)
		{
			try // IE
			{
				var flags = 0;
				//if ( whole_words_only )
				//	flags += 2;
				if ( match_case )
					flags += 4;

				var sel = Util.Selection.get_selection(self._loki.window);
				var rng = Util.Range.create_range(sel);

				if ( rng != null )
				{
					rng.collapse(false);
					var was_found = rng.findText(search_str, 10000000, flags);
					if ( was_found )
						rng.select();
				}

				return was_found ? UI.Find_Helper.FOUND : UI.Find_Helper.NOT_FOUND;
			}
			catch(f)
			{
				throw(new Error('UI.Find_Helper.find: Neither the Gecko nor the IE way of finding text worked. When the Mozilla way was tried, an error with the following message was thrown: <<' + e.message + '>>. When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
			}
		}
	};

	this.replace = function(search_str, replace_str, match_case, match_backwards, wrap)
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		
		// If the search string isn't already selected,
		// this is presumably the first time the user is 
		// clicking the "replace" button (and hasn't already
		// clicked "find"), so we need to do that before we
		// replace anything.
		if ( Util.Range.get_text(rng).toLowerCase() != search_str.toLowerCase() )
		{
			/*
			if ( match_backwards )
				Util.Selection.collapse(sel, false); // to end
			else
				Util.Selection.collapse(sel, true); // to start

			var matched = self.find(search_str, match_case, match_backwards, wrap);
			if ( matched == UI.Find_Helper.NOT_FOUND )
				return UI.Find_Helper.NOT_FOUND;
			*/

			return self.find(search_str, match_case, match_backwards, wrap);
		}
		else
		{
			sel = Util.Selection.get_selection(self._loki.window);
			Util.Selection.paste_node(sel, self._loki.document.createTextNode(replace_str));

			var matched = self.find(search_str, match_case, match_backwards, wrap);
			if ( matched == UI.Find_Helper.NOT_FOUND )
				return UI.Find_Helper.REPLACED_LAST_MATCH;

			return UI.Find_Helper.REPLACED;
		}
	};

	this.replace_all = function(search_str, replace_str, match_case, match_backwards)
	{
		self.select_beginning();

		var matched = true;
		var i = 0;
		while ( matched != UI.Find_Helper.NOT_FOUND && i < 500 ) // to be safe
		{
			matched = self.replace(search_str, replace_str, match_case, match_backwards, false);
			if ( matched == UI.Find_Helper.REPLACED || matched == UI.Find_Helper.REPLACED_LAST_MATCH )
				i++;
		}
		return i;
	};

	this.select_beginning = function()
	{
		sel = Util.Selection.get_selection(self._loki.window);
		//Util.Selection.select_node(sel, self._loki.document.getElementsByTagName('BODY')[0]);
		Util.Selection.select_node_contents(sel, self._loki.document.getElementsByTagName('BODY')[0]);
		Util.Selection.collapse(sel, true);
	};
};

UI.Find_Helper.FOUND = 1;
UI.Find_Helper.NOT_FOUND = 2;
UI.Find_Helper.REPLACED = 3;
UI.Find_Helper.REPLACED_LAST_MATCH = 4;

// file UI.Find_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Find_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return ( this.matches_keycode(e, 70) || this.matches_keycode(e, 72) ) && e.ctrlKey; }; // Ctrl-F or Ctrl-H
	this.action = function() { this._find_helper.open_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._find_helper = (new UI.Find_Helper).init(this._loki);
		return this;
	};
};

// file UI.HR_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "Insert HR" toolbar button.
 */
UI.HR_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'hr.png';
	this.title = 'Horizontal rule';
	this.click_listener = function() { self._hr_helper.insert_hr(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._hr_helper = (new UI.HR_Helper).init(this._loki);
		return this;
	};
};

// file UI.HR_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an hr. Contains code
 * common to both the button and the menu item.
 */
UI.HR_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);
	
	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._masseuse = (new UI.HR_Masseuse).init(this._loki);
		return this;
	};

	this.is_selected = function()
	{
		return !!_get_selected_hr();
	};

	var _get_selected_hr = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'HR');
	};

	this.insert_hr = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var hr = self._loki.document.createElement('HR');
		Util.Selection.paste_node(sel, self._masseuse.wrap(hr));
		//Util.Selection.select_node(sel, hr);
		//Util.Selection.collapse(sel, false);
		window.focus();
		self._loki.window.focus();
	};

	this.remove_hr = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var hr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'HR');
		var target = self._removal_target(hr);

		// Move cursor
		Util.Selection.select_node(sel, target);
		Util.Selection.collapse(sel, false); // to end
		self._loki.window.focus();

		if ( target.parentNode != null )
			target.parentNode.removeChild(target);
	};
	
	this._removal_target = function(hr)
	{
		var p = hr.parentNode;
		return (Util.Node.is_tag(p, 'DIV') && 'hr' == p.getAttribute('loki:container'))
			? p
			: hr;
	};
};

// file UI.HR_Masseuse.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for making horizontal rule elements easier to delete.
 */
UI.HR_Masseuse = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Masseuse);
	
	this.massage_node_descendants = function(node)
	{
		Util.Array.for_each(node.getElementsByTagName('HR'),
			self.massage_node, self);
	};
	
	this.unmassage_node_descendants = function(node)
	{
		var div_elements = Util.Array.from(node.getElementsByTagName('DIV'));
		
		div_elements.each(function(div) {
			if (div.getAttribute('loki:container') == 'hr') {
				this.unmassage_node(div);
			}
		}, self);
	};
	
	this.massage_node = function(node)
	{
		var container = self._create_container(node);
		node.parentNode.replaceChild(container, node);
		container.appendChild(node);
		self._add_delete_button(container);
	};
	
	this.wrap = function(node)
	{
		var container = self._create_container(node);
		container.appendChild(node);
		self._add_delete_button(container);
		
		return container;
	};
	
	this.unmassage_node = function(node)
	{
		var r = self.get_real(node) || node.ownerDocument.createElement('HR');
		node.parentNode.replaceChild(r, node);
	};
	
	this.get_real = function(node)
	{
		return Util.Node.get_last_child_node(node,
			Util.Node.curry_is_tag('HR'));
	}
	
	this._create_container = function(node)
	{
		var div = node.ownerDocument.createElement('DIV');
		Util.Element.add_class(div, 'loki__hr_container');
		div.setAttribute('loki:fake', 'true');
		div.setAttribute('loki:container', 'hr');
		return div;
	};
	
	this._add_delete_button = function(container)
	{
		var doc = container.ownerDocument;
		var link = doc.createElement('A');
		link.title = 'Click to remove this horizontal line.'
		Util.Element.add_class(link, 'loki__delete');
		
		/*var span = doc.createElement('SPAN');
		span.appendChild(doc.createTextNode('Remove'));
		link.appendChild(span);*/
		
		Util.Event.add_event_listener(container, 'mouseover', function() {
			link.style.display = 'block';
		});
		
		Util.Event.add_event_listener(container, 'mouseout', function() {
			link.style.display = '';
		});
		
		Util.Event.add_event_listener(link, 'click', function(e) {
			if (!e) var e = window.event;
			
			container.parentNode.removeChild(container);
			
			return Util.Event.prevent_default(e);
		})
		
		container.appendChild(link);
	};
}; 
// file UI.Headline_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "headline" toolbar button.
 */
UI.Headline_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'head.png';
	this.title = 'Heading';
	this.click_listener = function() { self._loki.toggle_block('h3'); };
	this.state_querier = function() { return self._loki.query_command_state('FormatBlock') == 'h3'; };
};

// file UI.Headline_Menugroup.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing a headline menugroup. 
 */
UI.Headline_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var menuitems = [];

		var self = this;
		if ( this._is_h3() )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				//label : 'Subordinate headline',
				label : 'Change to minor heading (h4)',
				listener : function() { self._toggle_h4(); }
			}) );
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Remove headline',
				listener : function() { self._toggle_h3(); }
			}) );
		}
		else if ( this._is_h4() )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				//label : 'Superordinate headline',
				label : 'Change to major heading (h3)',
				listener : function() { self._toggle_h3(); }
			}) );
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Remove headline',
				listener : function() { self._toggle_h4(); }
			}) );
		}

		return menuitems;
	};

	this._toggle_h3 = function()
	{
		this._loki.toggle_block('h3');
	};

	this._toggle_h4 = function()
	{
		this._loki.toggle_block('h4');
	};

	this._is_h3 = function()
	{
		return this._loki.query_command_value('FormatBlock') == 'h3';
	};

	this._is_h4 = function()
	{
		return this._loki.query_command_value('FormatBlock') == 'h4';
	};
};

// file UI.Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping perform action. Contains code
 * common to both the button and the menugroup for doing whatever
 * the action is.
 */
UI.Helper = function()
{	
	this.init = function(loki)
	{
		this._loki = loki;
		return this;
	};
};

// file UI.Highlight_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "highlight" toolbar button.
 */
UI.Highlight_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'highlight.png';
	this.title = 'Highlight';
	this.click_listener = function() { self._helper.toggle_blockquote_paragraph(); };
	this.state_querier = function() { return self._helper.query_blockquote_paragraph(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._helper = (new UI.Blockquote_Highlight_Helper).init(this._loki, 'highlight');
		return this;
	};
};

// file UI.Highlight_Menugroup.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing an align menugroup. 
 */
UI.Highlight_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._helper = (new UI.Blockquote_Highlight_Helper).init(this._loki, 'highlight');
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var menuitems = [];

		var self = this;
		if ( this._helper.is_blockquoteable() )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Highlight',
				listener : function() { self._helper.toggle_blockquote_paragraph(); }
			}) );
		}

		return menuitems;
	};
};

// file UI.Image_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for inserting an image.
 */
UI.Image_Button = function()
{
	var self = this;
	Util.OOP.inherits(this, UI.Button);

	this.image = 'image.png';
	this.title = 'Insert image';
	this.click_listener = function() { self._helper.open_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._helper = (new UI.Image_Helper).init(this._loki);
		return this;
	};
};

// file UI.Image_Dialog.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class An image dialog window.
 */
UI.Image_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);

	this._dialog_window_width = 625;
	this._dialog_window_height = 600;

	this.init = function(params)
	{
		// use rss integration only if data_source is given:
		this._use_rss = !!params.data_source;
		this.superclass.init.call(this, params);
		return this;
	};

	this._set_title = function()
	{
		if ( !this._initially_selected_item )
			this._dialog_window.document.title = 'Insert image';
		else
			this._dialog_window.document.title = 'Edit image';
	};

	this._append_style_sheets = function()
	{
		this.superclass._append_style_sheets.call(this);
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Listbox.css');
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Image_Listbox.css');
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Tabset.css');
	};

	this._populate_main = function()
	{
		this._append_heading();
		this._append_tabset();
		if ( this._use_rss )
			this._append_image_listbox();
		this._append_image_custom();
		if ( this._use_rss )
			this._append_image_options_chunk('listbox');
		this._append_image_options_chunk('custom');
		this._append_remove_image_chunk();
		var self = this;
		setTimeout(function () { self._resize_dialog_window(false, true); }, 1000);
		this.superclass._populate_main.call(this);
	};

	this._append_heading = function()
	{
		var h1 = this._dialog_window.document.createElement('H1');
		if ( !this._initially_selected_item )
			h1.innerHTML = 'Insert:';
		else
			h1.innerHTML = 'Edit:';
		this._main_chunk.appendChild(h1);
	};

	this._append_tabset = function()
	{
		this._tabset = new Util.Tabset({document : this._dialog_window.document});		
		if ( this._use_rss )
			this._tabset.add_tab('listbox', 'existing image');
		this._tabset.add_tab('custom', 'image at web address');
		this._main_chunk.appendChild(this._tabset.tabset_elem);
	};

	this._append_image_listbox = function()
	{
		// Instantiate a listbox to display the images 
		this._image_listbox = new UI.Image_Listbox;
		this._image_listbox.init('image_listbox', this._dialog_window.document,
			{chunk_transfer_size: 500});

		// Append the listbox's root element. (Do
		// this here rather than later so that the listbox items are
		// displayed as they load.)
		var listbox_elem = this._image_listbox.get_listbox_elem();
		this._tabset.get_tabpanel_elem('listbox').appendChild(listbox_elem);

		// Setup test for initially selected item
		var self = this;
		function is_initially_selected(item)
		{			
			if ( !item || !item.link || !self._initially_selected_item || !self._initially_selected_item.uri )
				return false;
			else
			{
				var item_uri = Util.URI.strip_https_and_http(item.link);
				var enclosure_uri = (item.enclosure)
					? Util.URI.strip_https_and_http(item.enclosure.url)
					: null;
				var initial_uri = Util.URI.strip_https_and_http(self._initially_selected_item.uri);
				
				if (item_uri == initial_uri || enclosure_uri == initial_uri) {
					self._tabset.select_tab('listbox');
					return true;
				} else {
					return false;
				}
			}
		};
		
		function url_maker(offset, num)
		{
			return Util.URI.append_to_query(this._data_source,
				{start: offset, num: num});
		}
		
		this._image_listbox.add_event_listener('change', function() {
			var item = this._image_listbox.get_selected_item();
			
			if (item.enclosure) {
				this._listbox_size_chunk.style.display = '';
				
				if (this._initially_selected_item && this._initially_selected_item.uri) {
					var isu = Util.URI.strip_https_and_http(
						this._initially_selected_item.uri);

					if (isu == Util.URI.strip_https_and_http(item.enclosure.url)) {
						this._listbox_tn_size_radio.input_elem.checked = true;
						this._listbox_full_size_radio.input_elem.checked = false;
					} else if (isu == Util.URI.strip_https_and_http(item.link)) {
						this._listbox_tn_size_radio.input_elem.checked = false;
						this._listbox_full_size_radio.input_elem.checked = true;
					}
				}
			} else {
				this._listbox_size_chunk.style.display = 'none';
			}
		}.bind(this));

		// Append to the listbox items retrieved using an RSS feed
		var reader = new Util.RSS.Reader(url_maker.bind(this));
		this._image_listbox.load_rss_feed(reader, is_initially_selected)
	};

	this._append_image_custom = function()
	{
		// Create widgets
		var custom_uri_label = this._doc.createElement('LABEL');
		custom_uri_label.appendChild(this._doc.createTextNode('Location: '));
		custom_uri_label.htmlFor = 'custom_uri_input';

		this._custom_uri_input = this._doc.createElement('INPUT');
		this._custom_uri_input.id = 'custom_uri_input';
		this._custom_uri_input.type = 'text';
		this._custom_uri_input.setAttribute('size', '40');

		var custom_uri_div = this._doc.createElement('DIV');
		custom_uri_div.appendChild(custom_uri_label);
		custom_uri_div.appendChild(this._custom_uri_input);

		var custom_alt_label = this._doc.createElement('LABEL');
		custom_alt_label.appendChild(this._doc.createTextNode('Description: '));
		custom_alt_label.htmlFor = 'custom_alt_input';

		this._custom_alt_input = this._doc.createElement('INPUT');
		this._custom_alt_input.id = 'custom_alt_input';
		this._custom_alt_input.type = 'text';
		this._custom_alt_input.setAttribute('size', '40');

		var custom_alt_label2 = this._doc.createElement('DIV');
		custom_alt_label2.appendChild(this._doc.createTextNode('This description will be used if the image cannot be displayed or the user is visually disabled.'));

		var custom_alt_div = this._doc.createElement('DIV');
		custom_alt_div.appendChild(custom_alt_label);
		custom_alt_div.appendChild(this._custom_alt_input);

		// Create table
		var table = this._doc.createElement('TABLE');
		var tbody = table.appendChild(this._doc.createElement('TBODY'));

		var tr = tbody.appendChild(this._doc.createElement('TR'));
		var td = tr.appendChild(this._doc.createElement('TD'))
		td.appendChild(custom_uri_label);
		var td = tr.appendChild(this._doc.createElement('TD'))
		td.appendChild(this._custom_uri_input);

		var tr = tbody.appendChild(this._doc.createElement('TR'));
		var td = tr.appendChild(this._doc.createElement('TD'))
		td.appendChild(custom_alt_label);
		var td = tr.appendChild(this._doc.createElement('TD'))
		td.appendChild(this._custom_alt_input);

		// Append it all
		var custom_tabpanel = this._tabset.get_tabpanel_elem('custom');
		var fieldset = new Util.Fieldset({legend : '', document : this._dialog_window.document});
		custom_tabpanel.appendChild(fieldset.fieldset_elem);
		fieldset.fieldset_elem.appendChild(table);

		// Init
		if ( !this._initially_selected_item || !this._initially_selected_item.uri ) 
		{
			this._custom_uri_input.value = 'http://';
		}
		else
		{
			this._tabset.select_tab('custom');
			this._custom_uri_input.value = this._initially_selected_item.uri;
			this._custom_alt_input.value = this._initially_selected_item.alt;
		}
	};

	/**
	 * Appends a chunk containing image options.
	 */
	this._append_image_options_chunk = function(tabname)
	{
		// Create fieldset
		var fieldset = new Util.Fieldset({legend : 'Image options', document : this._dialog_window.document});

		// Add to fieldset
		if ( tabname == 'listbox' )
			fieldset.fieldset_elem.appendChild(this._create_size_chunk(tabname));
		fieldset.fieldset_elem.appendChild(this._create_align_chunk(tabname));
		//this._append_border_chunk();

		// We need to add a dummy div styled clear:both so the CSS works
		// right
		var clearer = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(clearer, 'clearer');
		fieldset.fieldset_elem.appendChild(clearer);

		// Append it all
		this._tabset.get_tabpanel_elem(tabname).appendChild(fieldset.fieldset_elem);
	};
	

	/**
	 * Creates a chunk containing radio inputs asking whether to use a
	 * thumbnail or full-sized image.
	 */
	this._create_size_chunk = function(tabname)
	{
		// Create radios
		this['_' + tabname + '_tn_size_radio'] = new Util.Radio({
			id : tabname + '_tn_size_radio', 
			tabname : tabname + '_size', 
			label : 'Thumbnail', 
			value : 'tn', 
			checked: true, 
			document : this._dialog_window.document
		});
		this['_' + tabname + '_full_size_radio'] = new Util.Radio({
			id : tabname + '_full_size_radio', 
			tabname : tabname + '_size', 
			label : 'Full', 
			value : 'full',  
			checked: false, 
			document : this._dialog_window.document
		});

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : 'Size', document : this._dialog_window.document});

		// Append radios and labels to fieldset
		fieldset.fieldset_elem.appendChild(this['_' + tabname + '_tn_size_radio'].chunk);
		fieldset.fieldset_elem.appendChild(this['_' + tabname + '_full_size_radio'].chunk);

		this['_' + tabname + '_size_chunk'] = fieldset.chunk;

		// Return fieldset chunk
		return fieldset.chunk;
	};

	/**
	 * Creates a chunk containing image align options.
	 */
	this._create_align_chunk = function(tabname)
	{
		// Check for initial value
		if ( this._initially_selected_item &&
			 this._initially_selected_item.align )
		{
			var align_left = this._initially_selected_item.align == 'left';
			var align_right = this._initially_selected_item.align == 'right';
		}
		var align_none = !align_left && !align_right;

		// Create radios
		this['_' + tabname + '_align_none_radio'] = new Util.Radio({
			id : tabname + '_align_none_radio', 
			name : tabname + '_align', 
			label : 'None', 
			value : 'none', 
			checked : align_none, 
			document : this._dialog_window.document
		});
		this['_' + tabname + '_align_left_radio'] = new Util.Radio({
			id : tabname + '_align_left_radio', 
			name : tabname + '_align', 
			label : 'Left', 
			value : 'left', 
			checked : align_left, 
			document : this._dialog_window.document
		});
		this['_' + tabname + '_align_right_radio'] = new Util.Radio({
			id : tabname + '_align_right_radio', 
			name : tabname + '_align', 
			label : 'Right', 
			value : 'right', 
			checked : align_right, 
			document : this._dialog_window.document
		});

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : 'Alignment', document : this._dialog_window.document});

		// Append radios and labels to fieldset
		fieldset.fieldset_elem.appendChild(this['_' + tabname + '_align_none_radio'].chunk);
		fieldset.fieldset_elem.appendChild(this['_' + tabname + '_align_left_radio'].chunk);
		fieldset.fieldset_elem.appendChild(this['_' + tabname + '_align_right_radio'].chunk);

		// Return fieldset chunk
		return fieldset.chunk;
	};

	/**
	 * Appends a chunk containing image border options.
	 */
	this._append_border_chunk = function()
	{
		// Create radios
		this._border_yes_radio = new Util.Radio({id : 'border_yes_radio', name : 'border', label : 'Yes', value : 'yes', checked: true, document : this._dialog_window.document});
		this._border_no_radio = new Util.Radio({id : 'border_no_radio', name : 'border', label : 'No', value : 'no', document : this._dialog_window.document});

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : 'Border', document : this._dialog_window.document});

		// Append radios and labels to fieldset
		fieldset.fieldset_elem.appendChild(this._border_yes_radio.chunk);
		fieldset.fieldset_elem.appendChild(this._border_no_radio.chunk);

		// Append fieldset chunk to dialog
		this._image_options_chunk.appendChild(fieldset.chunk);
	};

	/**
	 * Creates and appends a chunk containing a "remove image" button. 
	 * Also attaches 'click' event listeners to the button.
	 */
	this._append_remove_image_chunk = function()
	{
		var button = this._dialog_window.document.createElement('BUTTON');
		button.setAttribute('type', 'button');
		button.appendChild( this._dialog_window.document.createTextNode('Remove image') );

		var self = this;
		Util.Event.add_event_listener(button, 'click', function() {
			self._remove_listener();
			self._dialog_window.window.close();
		});

		// Setup their containing chunk
		var chunk = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(chunk, 'remove_chunk');
		chunk.appendChild(button);

		// Append the containing chunk
		this._dialog_window.body.appendChild(chunk);
	};

	/**
	 * Called as an event listener when the user clicks the submit
	 * button. 
	 */
	this._internal_submit_listener = function()
	{
		if ( this._tabset.get_name_of_selected_tab() == 'listbox' )
		{
			// Get selected item
			var img_item = this._image_listbox.get_selected_item();
			if ( img_item == null )
			{
				this._dialog_window.window.alert('Please select an image to insert.');
				return false;
			}

			// Determine uri
			var uri = (img_item.enclosure && this._listbox_tn_size_radio.input_elem.checked)
				? Util.URI.strip_https_and_http(img_item.enclosure.url)
				: Util.URI.strip_https_and_http(img_item.link);

			// Determine alt text
			var alt = img_item.description;
		}
		else // if ( this._tabset.get_name_of_selected_tab() == 'custom' )
		{
			var uri = this._custom_uri_input.value;
			var alt = this._custom_alt_input.value;

			if ( uri == '' )
			{
				this._dialog_window.window.alert("Please enter the image's location.");
				return false;
			}
			if ( alt == '' )
			{
				this._dialog_window.window.alert("Please enter the image's description (alt text).");
				return false;
			}
		}

		// Determine align
		var tabname = this._tabset.get_name_of_selected_tab()
		var align;
		if ( this['_' + tabname + '_align_left_radio'].input_elem.checked )
			align = 'left';
		else if ( this['_' + tabname + '_align_right_radio'].input_elem.checked )
			align = 'right';
		else //if ( this['_' + tabname + '_align_none_radio'].input_elem.checked )
			align = '';

	/*
		// Determine border
		var border;
		if ( this._border_yes_radio.input_elem.checked )
			border = 'yes';
		else //if ( this._border_no_radio.input_elem.checked )
			border = 'no';
	*/

		// TODO: Determine height and width of image

		// Call external event listener
		this._external_submit_listener({uri : uri, alt : alt, align : align});

		// Close dialog window
		this._dialog_window.window.close();
	};
};

// file UI.Image_Double_Click.js
UI.Image_Double_Click = function ImageDoubleClick() {
	Util.OOP.inherits(this, UI.Double_Click);
	this.helper = null;
	
	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this.helper = (new UI.Image_Helper).init(loki);
		return this;
	};
	
	this.double_click = function() {
		if (this.helper.is_selected())
			this.helper.open_dialog();
	};
};

// file UI.Image_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an image. Contains code
 * common to both the button and the menu item.
 */
UI.Image_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.init = function(loki)
	{
		this._loki = loki;
		this._image_masseuse = (new UI.Image_Masseuse()).init(this._loki);
		return this;
	};
	
	this.get_selected_image = function get_selected_image()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		
		var images;
		var image;
		var real_image;
		var anchor_masseuse = (new UI.Anchor_Masseuse).init(this._loki);
		
		function is_valid_image(node) {
			if (!Util.Node.is_tag(node, 'IMG'))
				return false;
			
			return !anchor_masseuse.is_placeholder(node);
		}
		
		images = Util.Range.find_nodes(rng, is_valid_image, true);
		
		if (!images || !images.length) {
			return null;
		} else if (images.length > 1) {
			throw new UI.Multiple_Items_Error('Multiple images are currently ' +
				'selected.');
		}
		
		image = images[0];
		
		return this._image_masseuse.realize_elem(image);
	};
	
	this.get_selected_item = function get_selected_image_info()
	{
		var image = this.get_selected_image();
		if (!image)
			return null;
		
		return {
			uri: image.src,
			alt: image.alt,
			align: image.align
		};
	};

	this.is_selected = function image_is_selected()
	{
		try {
			return !!this.get_selected_image();
		} catch (e) {
			if (e.name == 'UI.Multiple_Items_Error')
				return true;
			throw e;
		}
	};
	
	this.open_dialog = function open_image_dialog()
	{
		var selected_image;
		
		try {
			selected_image = this.get_selected_item();
		} catch (e) {
			if (e.name == 'UI.Multiple_Items_Error') {
				alert('Multiple images are currently selected. Please narrow ' +
					'down your selection so that it only contains one image.');
				return;
			} else {
				throw e;
			}
		}
		
		if (!this._image_dialog)
			this._image_dialog = new UI.Image_Dialog();
		
		this._image_dialog.init({
			data_source: self._loki.settings.images_feed,
			base_uri: self._loki.settings.base_uri,
			submit_listener: self.insert_image,
			remove_listener: self.remove_image,
			selected_item: selected_image
		});
		this._image_dialog.open();
	};
	
	this.insert_image = function insert_image(params)
	{
		var image, clean_src, selected_image, sel, range;
		
		image = self._loki.document.createElement('IMG');
		clean_src = UI.Clean.clean_URI(params.uri);
		
		image.src = clean_src;
		image.alt = params.alt;
		
		if (params.align)
			image.align = params.align;
		
		image = self._image_masseuse.get_fake_elem(image);
		
		self._loki.window.focus();
		selected_image = self.get_selected_image();
		if (selected_image) {
			selected_image.parentNode.replaceChild(image, selected_image);
		} else {
			sel = Util.Selection.get_selection(self._loki.window);
			rng = Util.Range.create_range(sel);
			
			Util.Range.delete_contents(rng);
			Util.Range.insert_node(rng, image);
		}
	};

	this.remove_image = function remove_image()
	{
		var image, sel;
		
		image = self.get_selected_image();
		
		if (!image)
			return false;
		
		sel = Util.Selection.get_selection(self._loki.window);

		// Move cursor
		Util.Selection.select_node(sel, image);
		Util.Selection.collapse(sel, false); // to end
		self._loki.window.focus();

		if (image.parentNode)
			image.parentNode.removeChild(image);
		return true;
	};
};

// file UI.Image_Listbox.js
/**
 * Does nothing, since all necessary instance variables are declared in Listbox's constructor.
 *
 * @constructor
 *
 * @class Represents a listbox for images. This was designed for use
 * in Loki's image-insertion dialog box, but may be useful for other
 * applications.
 */
UI.Image_Listbox = function()
{
	Util.OOP.inherits(this, UI.Listbox);
	
	/**
	 * Creates the document chunk for each item. Differs from
	 * Listbox._create_item_chunk in that it displays the image at
	 * <code>item.link</code>. Requires that each <code>item</code> contain
	 * at least <code>title</code>, <code>description</code>, and
	 * <code>link</code> properties.
	 *
	 * @param	item	the item from which to create the chunk
	 * @private
	 */
	this._create_item_chunk = function(item)
	{
		function use_enclosure_url()
		{
			if (!item.enclosure || !item.enclosure.type || !item.enclosure.url)
				return false;
			
			return item.enclosure.type.match(/^image\//);
		}
		
		//var item_chunk = this._doc_obj.createElement('DIV');
		var item_chunk = this._doc_obj.createElement('A');
		item_chunk.href = 'javascript:void(0);';
		Util.Element.add_class(item_chunk, 'item_chunk');

		// Image
		var image_elem = this._doc_obj.createElement('IMG');
		var uri = (use_enclosure_url())
			? item.enclosure.url
			: item.link;
		var src = Util.URI.strip_https_and_http(uri);
		image_elem.setAttribute('src', src);
		image_elem.setAttribute('alt', '[Image: ' + item.description + ']');
		Util.Image.set_max_size(image_elem, 125, 125); // this needs to be here for IE, and in the load handler for Gecko
		Util.Event.add_event_listener(image_elem, 'load', function() { Util.Image.set_max_size(image_elem, 125, 125); });
		item_chunk.appendChild(image_elem);

		// Title
		var title_elem = this._doc_obj.createElement('DIV');

		var title_label_elem = this._doc_obj.createElement('STRONG');
		title_elem.appendChild(title_label_elem);

		var title_value_elem = this._doc_obj.createElement('SPAN');
		title_value_elem.appendChild(
			this._doc_obj.createTextNode(item.title)
		);
		title_elem.appendChild(title_value_elem);

		item_chunk.appendChild(title_elem);

		return item_chunk;
	}
	
	/**
	 * Modify the item chunk as appropriate for its place in the set of
	 * currently displayed items. In particular, we need to add a class to
	 * every third item_chunk.
	 *
	 * @param	item_chunk	the item_chunk to modify
	 * @param	cur_i		the index of this item in relation to other items
	 *                      in the current display
	 */
	this._modify_item_chunk = function(item_chunk, cur_i)
	{
		if ( cur_i % 4 == 0 )
		{
			var doc = item_chunk.ownerDocument;
			var spacer_elem = doc.createElement('DIV');
			Util.Element.add_class(spacer_elem, 'force_clear_for_ie');
			item_chunk.parentNode.insertBefore(spacer_elem, item_chunk);
	//		Util.Element.add_class(item_chunk, 'force_clear_for_ie');
		}
	}
};

// file UI.Image_Masseuse.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for inserting an image.
 */
UI.Image_Masseuse = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Masseuse);
			
	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._unsecured = /^http:/;
		return this;
	};

	/**
	 * Massages the given node's descendants.
	 */
	this.massage_node_descendants = function(node)
	{
		self.secure_node_descendants(node);
	};
	
	this.secure_node_descendants = function(node)
	{
		Util.Array.for_each(node.getElementsByTagName('IMG'),
			self.secure_node, self);
	};
	
	this.secure_node = function(img)
	{
		var placeholder = self.get_fake_elem(img);
		if (placeholder.src !== img.src)
			img.parentNode.replaceChild(placeholder, img);
	};
	
	this.get_fake_elem = function(img)
	{
		var placeholder, src = img.getAttribute('src');
		if (src == null)
			return;
		
		var my_url = self._loki.owner_window.location;
		if (!self._unsecured.test(my_url) && self._unsecured.test(src)) {
			placeholder = img.cloneNode(false);
			
			if (Util.URI.extract_domain(src) == self._loki.editor_domain()) {
				new_src = Util.URI.strip_https_and_http(src);
			} else if (self._loki.settings.sanitize_unsecured) {
				new_src = self._loki.settings.base_uri +
					'images/insecure_image.gif';
				placeholder.setAttribute('loki:src', img.src);
				placeholder.setAttribute('loki:fake', 'true');
			} else {
				return img;
			}
			
			placeholder.src = new_src;
			
			return placeholder;
		}
		
		return img;
	};

	/**
	 * Unmassages the given node's descendants.
	 */
	this.unmassage_node_descendants = function(node)
	{
		Util.Array.for_each(node.getElementsByTagName('IMG'),
			self.unmassage_node, self);
	};
	
	this.unmassage_node = function(img)
	{
		var real = self.get_real_elem(img);
		if (real && real.src != img.src)
			img.parentNode.replaceChild(real, img);
	};
	
	this.get_real_elem = function(img)
	{
		var src, real;
		
		if (!img)
			return null;
		
		src = img.getAttribute('loki:src');
		if (!src)
			return null;
		
		real = img.ownerDocument.createElement('IMG');
		if (img.title)
			real.title = img.title;
		if (img.alt)
			real.alt = img.alt;
		real.src = src;
		
		return real;
	};
	
	/**
	 * If "img" is a fake element, returns its corresponding real element,
	 * otherwise return the element itself.
	 */
	this.realize_elem = function(img)
	{
		return this.get_real_elem(img) || img;
	}
};

// file UI.Image_Menugroup.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing a menugroup. 
 */
UI.Image_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._helper = (new UI.Image_Helper).init(this._loki);
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var self = this;
		var menuitems = [];

		var selected_item = this._helper.get_selected_item();
		if ( selected_item != null )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Edit image',
				listener : function() { self._helper.open_dialog() }
			}) );
		}

		return menuitems;
	};
};

// file UI.Indent_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "indent" toolbar button.
 */
UI.Indent_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'indent.png';
	this.title = 'Indent list item(s)';
	this.helper = null;
	
	this.click_listener = function indent_button_onclick() 
	{
		// Only indent if we're inside a UL or OL 
		// (Do this to avoid misuse of BLOCKQUOTEs.)
		
		if (!this._helper)
			this.helper = (new UI.List_Helper).init(this._loki);
		
		var list = this.helper.get_ancestor_list();
		var li = this.helper.get_list_item();
		var sib;
		
		if (list) {
			// Don't indent first element in a list, if it is not in a nested list.
			// This is because in such a situation, Gecko "indents" by surrounding
			// the UL/OL with a BLOCKQUOTE tag. I.e. <ul><li>as|df</li></ul>
			// --> <blockquote><ul><li>as|df</li></ul></blockquote>
			
			sib = Util.Node.get_nearest_non_whitespace_sibling_node(li,
			    Util.Node.PREVIOUS);
			if (sib || this.helper.get_more_distant_list(list)) {
				this.helper.indent();
			} else {
				UI.Messenger.display_once('indent_first_li',
					"The first item in a list cannot be indented.");
			}
		} else {
			this.helper.nag_about_indent_use();
		}
	};
};

// file UI.Italic_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "italic" toolbar button.
 */
UI.Italic_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'em.png';
	this.title = 'Emphasis (Ctrl+I)';
	this.click_listener = function() { self._loki.exec_command('Italic'); };
	this.state_querier = function() { return self._loki.query_command_state('Italic'); };
};

// file UI.Italic_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Italic_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);
	this.test = function(e) { return this.matches_keycode(e, 73) && e.ctrlKey; }; // Ctrl-I
	this.action = function() { this._loki.exec_command('Italic'); };
};

// file UI.Italic_Masseuse.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for massaging em tags to i tags. The motivation for this is that 
 * you can't edit em tags, but we want them in the final output.
 */
UI.Italic_Masseuse = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Masseuse);

	/**
	 * Massages the given node's children, replacing any named ems with
	 * i elements.
	 */
	this.massage_node_descendants = function(node)
	{
		var ems = node.getElementsByTagName('EM');
		for ( var i = ems.length - 1; i >= 0; i-- )
		{
			var fake = self.get_fake_elem(ems[i]);
			ems[i].parentNode.replaceChild(fake, ems[i]);
		}
	};

	/**
	 * Unmassages the given node's descendants, replacing any i elements
	 * with real em elements.
	 */
	this.unmassage_node_descendants = function(node)
	{
		var dummies = node.getElementsByTagName('I');
		for ( var i = dummies.length - 1; i >= 0; i-- )
		{
			var real = self.get_real_elem(dummies[i]);
			dummies[i].parentNode.replaceChild(real, dummies[i])
		}
	};

	/**
	 * Returns a fake element for the given em.
	 */
	this.get_fake_elem = function(em)
	{
		var dummy = em.ownerDocument.createElement('I');
		dummy.setAttribute('loki:fake', 'true');
		// maybe transfer attributes, too
		while ( em.firstChild != null )
		{
			dummy.appendChild( em.removeChild(em.firstChild) );
		}
		return dummy;
	};

	/**
	 * If the given fake element is really fake, returns the appropriate 
	 * real em. Else, returns null.
	 */
	this.get_real_elem = function(dummy)
	{
		if (dummy != null && dummy.nodeName == 'I')
		{
			var em = dummy.ownerDocument.createElement('EM');
			// maybe transfer attributes, too
			while ( dummy.firstChild != null )
			{
				em.appendChild( dummy.removeChild(dummy.firstChild) );
			}
			return em;
		}
		return null;
	};
};

// file UI.Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a keybinding. For extending only.
 */
UI.Keybinding = function()
{
	this.test; // function
	this.action; // function

	this.init = function(loki)
	{
		this._loki = loki;
		return this;
	};

	/**
	 * Returns whether the given keycode matches that 
	 * of the given event. 
	 */
	this.matches_keycode = function(e, keycode, XXX)
	{
		/*
		if ( e.keyCode == keycode ||  // keydown (IE)
			 ( e.keyCode == 0 &&      // keypress (Gecko)
			   ( e.charCode == keycode ||
			     ( ( e.charCode >= 65 || e.charCode <= 90 ) && // is uppercase alpha
			         e.charCode == keycode + 32 ) ) ) ) // keypress (Gecko)
		*/

		if ( e.type == 'keydown' && e.keyCode == keycode ) // IE
			return true;
		else if ( e.type == 'keypress' && (e.charCode == keycode || (((e.charCode >= 65 || e.charCode <= 90) && e.charCode == keycode + 32))) ) // Gecko
			return true;
		else
			return false;
	//this.test = function(e) { return ( e.charCode == 98 || e.charCode == 66 ) && e.ctrlKey; }; // Ctrl-B
	//this.test = function(e) { return ( e.keyCode == 98 || e.charCode == 66 ) && e.ctrlKey; }; // Ctrl-B
	};
};

// file UI.Left_Align_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "left align" toolbar button.
 */
UI.Left_Align_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'align_left.png';
	this.title = 'Left align (Ctrl-L)';
	this.click_listener = function() { self._loki.exec_command('JustifyLeft'); };
	this.state_querier = function() { return self._loki.query_command_state('JustifyLeft'); };
};

// file UI.Left_Align_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Left_Align_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);
	this.test = function(e) { return this.matches_keycode(e, 76) && e.ctrlKey; }; // Ctrl-L
	//this.action = function() { this._loki.exec_command('JustifyLeft'); };
	this.action = function() { this._align_helper.align_left(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._align_helper = (new UI.Align_Helper).init(this._loki);
		return this;
	};
};

// file UI.Link_Dialog.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class An email link dialog window.
 */
UI.Link_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);

	/**
	 * Populates the main chunk. You'll want to do something more
	 * here in descendents.
	 */
	this._populate_main = function()
	{
		this._append_link_information_chunk()
		this._append_submit_and_cancel_chunk();
		this._append_remove_link_chunk();
	};

	/**
	 * Appends a chunk with extra options for links.
	 */
	this._append_link_information_chunk = function()
	{
		// Link title
		this._link_title_input = this._dialog_window.document.createElement('INPUT');
		this._link_title_input.size = 40;
		this._link_title_input.id = 'link_title_input';
		this._link_title_input.value = this._initially_selected_item.title;

		var lt_label = this._dialog_window.document.createElement('LABEL');
		var strong = this._dialog_window.document.createElement('STRONG');
		strong.appendChild( this._dialog_window.document.createTextNode('Description: ') );
		lt_label.appendChild(strong);
		lt_label.htmlFor = 'link_title_input';

		lt_comment = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(lt_comment, 'comment');
		lt_comment.innerHTML = '(Will appear in some browsers when mouse is held over link.)';

		var lt_chunk = this._dialog_window.document.createElement('DIV');
		lt_chunk.appendChild(lt_label);
		lt_chunk.appendChild(this._link_title_input);
		lt_chunk.appendChild(lt_comment);

		// "Other options"
		this._other_options_chunk = this._dialog_window.document.createElement('DIV');
		this._other_options_chunk.id = 'other_options';
		if ( this._initially_selected_item.new_window == true )
			this._other_options_chunk.style.display = 'block';
		else
			this._other_options_chunk.style.display = 'none';

		var other_options_label = this._dialog_window.document.createElement('H3');
		var other_options_a = this._dialog_window.document.createElement('A');
		other_options_a.href = 'javascript:void(0);';
		other_options_a.innerHTML = 'More Options';
		var self = this;
		Util.Event.add_event_listener(other_options_a, 'click', function() {
			if ( self._other_options_chunk.style.display == 'none' )
				self._other_options_chunk.style.display = 'block';
			else
				self._other_options_chunk.style.display = 'none';
		});
		other_options_label.appendChild(other_options_a);
		
		// Checkbox
		this._new_window_checkbox = this._dialog_window.document.createElement('INPUT');
		this._new_window_checkbox.type = 'checkbox';
		this._new_window_checkbox.id = 'new_window_checkbox';
		this._new_window_checkbox.checked = this._initially_selected_item.new_window;

		var nw_label = this._dialog_window.document.createElement('LABEL');
		nw_label.appendChild( this._dialog_window.document.createTextNode('Open in new browser window') );
		nw_label.htmlFor = 'new_window_checkbox';

		var nw_chunk = this._dialog_window.document.createElement('DIV');
		nw_chunk.appendChild(this._new_window_checkbox);
		nw_chunk.appendChild(nw_label);

		this._other_options_chunk.appendChild(nw_chunk);

		// Create fieldset and its legend, and append to fieldset
		var fieldset = new Util.Fieldset({legend : 'Link information', document : this._dialog_window.document});
		fieldset.fieldset_elem.appendChild(lt_chunk);
		fieldset.fieldset_elem.appendChild(other_options_label);
		fieldset.fieldset_elem.appendChild(this._other_options_chunk);

		// Append fieldset chunk to dialog
		this._main_chunk.appendChild(fieldset.chunk);
	};

	/**
	 * Creates and appends a chunk containing a "remove link" button. 
	 * Also attaches 'click' event listeners to the button.
	 */
	this._append_remove_link_chunk = function()
	{
		var button = this._dialog_window.document.createElement('BUTTON');
		button.setAttribute('type', 'button');
		button.appendChild( this._dialog_window.document.createTextNode('Remove link') );

		var self = this;
		var listener = function()
		{
			self._external_submit_listener({uri : '', new_window : false, title : ''});
			self._dialog_window.window.close();
		}
		Util.Event.add_event_listener(button, 'click', listener);

		// Setup their containing chunk
		var chunk = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(chunk, 'remove_chunk');
		chunk.appendChild(button);

		// Append the containing chunk
		this._dialog_window.body.appendChild(chunk);
	};

	/**
	 * Called as an event listener when the user clicks the submit
	 * button. You'll want to do something more here in descendents.
	 */
	this._internal_submit_listener = function()
	{
		// Call external event listener
		this._external_submit_listener({uri : '', // in descendents change this
										new_window : this._new_window_checkbox.checked, 
										title : this._link_title_input.value});

		// Close dialog window
		this._dialog_window.window.close();
	};
};

// file UI.Link_Double_Click.js
UI.Link_Double_Click = function LinkDoubleClick() {
	Util.OOP.inherits(this, UI.Double_Click);
	this.helper = null;
	
	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this.helper = (new UI.Link_Helper).init(loki);
		return this;
	};
	
	this.double_click = function() {
		if (this.helper.is_selected())
			this.helper.open_page_link_dialog();
	};
};

// file UI.Link_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert link. Contains code
 * common to both the button and the menu item.
 */
UI.Link_Helper = function()
{
	var self = this;
	Util.OOP.inherits(this, UI.Helper);

	this.check_for_linkable_selection = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return ( !Util.Selection.is_collapsed(sel) || self.is_selected() )
	};

	/**
	 * Opens the page link dialog.
	 */
	this.open_page_link_dialog = function()
	{
		if ( !this.check_for_linkable_selection() )
		{
			alert('First select some text that you want to make into a link.');
			return;
		}

		if ( this._page_link_dialog == null )
			this._page_link_dialog = new UI.Page_Link_Dialog();
		this._page_link_dialog.init(self._loki,
									{ base_uri : this._loki.settings.base_uri,
						    		  anchor_names : this.get_anchor_names(),
						    		  submit_listener : this.insert_link,
						    		  selected_item : this.get_selected_item(),
						    		  sites_feed : this._loki.settings.sites_feed,
									  finder_feed : this._loki.settings.finder_feed,
									  default_site_regexp : 
										this._loki.settings.default_site_regexp,
									  default_type_regexp : 
										this._loki.settings.default_type_regexp });
		this._page_link_dialog.open();
	};

	/**
	 * Returns info about the selected link, if any.
	 */
	this.get_selected_item = function()
	{
		var sel = Util.Selection.get_selection(this._loki.window);
		var rng = Util.Range.create_range(sel);

		// Look around selection
		var uri = '', new_window = null, title = '';
		var ancestor = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'A');
		
		// (Maybe temporary) hack for IE, because the above doesn't work for 
		// some reason if a link is double-clicked
		// 
		// Probably the reason the above doesn't work is that get_nearest_ancestor_node
		// uses get_start_container, which, in IE, collapses a duplicate of the range
		// to front, then gets parentElement of that range. When we doubleclick on a link
		// the text of the entire link (assuming it is one word long) is selected. When a 
		// range is made from such a selection, it is considered _inside_ the A tag, which 
		// is what we want and I, at least, expect. But when the range is collapsed, it 
		// ends up (improperly, I think) _before_ the A tag.
		if ( ancestor == null && rng.parentElement && rng.parentElement().nodeName == 'A' )
		{
			ancestor = rng.parentElement();
		}

		if ( ancestor != null )
		{
			uri = ancestor.getAttribute('href');
			new_window = ( ancestor.getAttribute('target') &&
						   ancestor.getAttribute('target') != '_self' &&
						   ancestor.getAttribute('target') != '_parent' &&
						   ancestor.getAttribute('target') != '_top' );
			title = ancestor.getAttribute('title');
		}

		uri = uri.replace( new RegExp('\%7E', 'g'), '~' ); //so that users of older versions of Mozilla are not confused by this substitution
		var httpless_uri = Util.URI.strip_https_and_http(uri);

		var selected_item = { uri : uri, httpless_uri : httpless_uri, new_window : new_window, title : title };
		return selected_item;
	};

	this.is_selected = function()
	{
		return ( this.get_selected_item().uri != '' );
	};

	/**
	 * Returns an array of the names of named anchors in the current document.
	 */
	this.get_anchor_names = function()
	{
		var anchor_names = new Array();

		var anchor_masseuse = (new UI.Anchor_Masseuse).init(this._loki);
		anchor_masseuse.unmassage_body();

		var anchors = this._loki.document.getElementsByTagName('A');
		for ( var i = 0; i < anchors.length; i++ )
		{
			if ( anchors[i].getAttribute('name') ) // && anchors[i].href == false )
			{
				anchor_names.push(anchors[i].name);
			}
		}
		
		anchor_masseuse.massage_body();
		
		return anchor_names;
	};

	/**
	 * Inserts a link. Params contains uri, and optionally
	 * new_window, title, and onclick. If uri is empty string,
	 * any link is removed.
	 */
	this.insert_link = function(params)
	{
		var uri = params.uri;
		var new_window = params.new_window || false;
		var title = params.title || '';
		var onclick = params.onclick || '';
		
		var tags;

		// If the selection is inside an existing link, select that link
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var ancestor = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'A');
		if (ancestor && ancestor.getAttribute('href')) {
			tags = [ancestor];
		} else {
			self._loki.exec_command('CreateLink', false, 'hel_temp_uri');
			var links = self._loki.document.getElementsByTagName('A');
			tags = [];
			
			for (var i = 0; i < links.length; i++) {
				if (links[i].getAttribute('href') == 'hel_temp_uri') {
					tags.push(links[i]);
				}
			}
		}
		
		if (!uri || !uri.length) {
			// If no URI received, remove the links.
			tags.each(function remove_link(tag) {
				Util.Node.replace_with_children(tag);
			});
		} else {
			// Update link attributes.
			tags.each(function update_link(tag) {
				function set_attribute(name, value) {
					if (value && value.length > 0)
						tag.setAttribute(name, value);
					else
						tag.removeAttribute(name);
				}
				
				set_attribute('target', (new_window) ? '_blank' : null);
				set_attribute('title', title);
				set_attribute('loki:onclick', onclick);
				set_attribute('href', uri);
			});
			
			// Collapse selection to end so people can see the link and
			// to avoid a Gecko bug that the anchor tag is only sort of
			// selected (such that if you click the anchor toolbar button
			// again without moving the selection at all first, the new
			// link is not recognized).
			var sel = Util.Selection.get_selection(self._loki.window);
			Util.Selection.collapse(sel, false); // to end
		}
	};
};

// file UI.Link_Menugroup.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing a clipboard menugroup. 
 */
UI.Link_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._link_helper = (new UI.Link_Helper).init(this._loki);
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var menuitems = [];

		var selected_item = this._link_helper.get_selected_item();
		if ( selected_item != null && selected_item.uri != '' )
		{
			var self = this;
			menuitems.push( (new UI.Menuitem).init({
				label : 'Edit link', 
				//listener : function() { self._link_helper.open_dialog_by_context() } 
				listener : function() { self._link_helper.open_page_link_dialog() } 
			}) );
		}
		else if ( this._link_helper.check_for_linkable_selection() )
		{
			var self = this;
			menuitems.push( (new UI.Menuitem).init({
				label : 'Create link', 
				//listener : function() { self._link_helper.open_dialog_by_context() } 
				listener : function() { self._link_helper.open_page_link_dialog() } 
			}) );
		}
		return menuitems;
	};
};

// file UI.List_Helper.js
/**
 * Helps with list functionality.
 * @author Eric Naeseth
 */
UI.List_Helper = function ListHelper()
{
	Util.OOP.inherits(this, UI.Helper);
	
	this.indent = function indent_list()
	{
        this._loki.exec_command('Indent');
        this._loki.document.normalize();
	};
	
	this.outdent = function outdent_list()
	{
        this._loki.exec_command('Outdent');
	};
	
	this.get_ancestor_list = function get_ancestor_list_of_selected_range()
	{
		var sel = Util.Selection.get_selection(this._loki.window);
		var range = Util.Range.create_range(sel);
		
		return Util.Range.get_nearest_ancestor_element_by_tag_name(range, 'UL')
			|| Util.Range.get_nearest_ancestor_element_by_tag_name(range, 'OL');
	};
	
	this.get_list_item = function get_list_item_for_selected_range()
	{
		var sel = Util.Selection.get_selection(this._loki.window);
		var range = Util.Range.create_range(sel);
		
		return Util.Range.get_nearest_ancestor_element_by_tag_name(range, 'LI');
	};
	
	this.get_more_distant_list = function get_list_ancestor_of_list(list)
	{
		return Util.Node.get_nearest_ancestor_element_by_tag_name(list, 'UL')
			|| Util.Node.get_nearest_ancestor_element_by_tag_name(list, 'OL');
	};
	
	this.nag_about_indent_use = function nag_about_indent_use()
	{
		UI.Messenger.display_once('indent_use_nag',
			'The indent and unindent buttons can only be used to indent and' +
			' outdent list items; in particular, it cannot be used to indent' +
			' paragraphs.');
	};
} 
// file UI.Listbox.js
/**
 * Declares instance variables. You must call <code>init</code> to
 * initialize instance variables.
 *
 * @constructor
 *
 * @class Represents a listbox. Is intended to replace native HTML
 * elements like select boxes or checkboxes, but (a) be able to
 * display more complicated items, and (b) be more easy to navigate,
 * by having a built in filter and pager.
 *
 * @author	Nathanael Fillmore
 * @author	Eric Naeseth
 * @version 2007-10-16
 * 
 */
UI.Listbox = function()
{
	// Permanent listbox instance properties
	this._doc_obj = null; // reference to the document object for the document this listbox will be added to
	this._root_elem = null; // the root listbox element
	this._items = []; // holds the list items (their data, that is, not their document fragments)
	this._item_chunks = []; // holds the document chunk for each list item
	this._selected_index = null; // holds index in this._items of the currently selected item

	this._filtered_indices = []; // holds indices of the items which match the _filter_string
	this._cur_page_num = null;
	this._num_results_per_page = null;
	this._filter_string = null;

	this._items_chunk_elem = null;
	this._next_page_elem = null;
	this._prev_page_elem = null;
	this._page_num_elem = null;

	this._event_listeners = {};
};

/**
 * Initializes instance variables.  Also appends chunks for the
 * various parts of the listbox to the root element.
 *
 * @param	listbox_id	the desired id of the root listbox HTML element.  
 * @param	doc_obj		a reference to the document object for the document
 *                      this listbox will be added to.
 * @param	options		behavior options
 */
UI.Listbox.prototype.init = function(listbox_id, doc_obj, options)
{
	if (!options)
		var options = {};
	
	// Permanent listbox instance properties
	this._doc_obj = doc_obj;
	this._create_root_elem(listbox_id);
	this._error_display = new UI.Error_Display(this._root_elem);
	this._chunks = [];

	// Current state of listbox
	this._cur_page_num = 0; // zero-based
	this._chunk_transfer_size = options.chunk_transfer_size || 16;
	this._transfer_timeout = options.transfer_timeout || 10;
	this._num_results_per_page = options.results_per_page || 8;
	this._filter_string = options.filter_string || '';
	this._selected_index = -1;

	// Append chunks
	this._append_page_chunk();
	this._append_filter_chunk();
	this._append_items_chunk();
};

/**
 * Adds an item to the listbox. (It isn't displayed, though, until
 * refresh is called.)
 *
 * @param	item	the item to append. Item should have whatever properties
 *                  set are needed by this._create_item_chunk. For Listbox proper, 
 *                  these are title and description, but for extensions these
 *                  might be different.
 */
UI.Listbox.prototype.append_item = function(item)
{
	this._items.push(item);
};

/**
 * Inserts an item to the listbox at the specified index. See on
 * append_item() for more info
 *
 * @param	item	the item to insert
 * @param	index	the desired index of this item. The indices of all
 *                  items with indices greater than index will be
 *                  increased by 1.
 */
UI.Listbox.prototype.insert_item = function(item, index)
{
	this._items.splice(index, 0, item);
};

/**
 * Removes an item from the listbox.
 */
UI.Listbox.prototype.remove_item = function(index)
{
	// Remove item
	this._items.splice(index, 1);
	this._item_chunks.splice(index, 1);

	// Fix selected index
	if ( this._selected_index == index )
		this._selected_index = -1;
	else if ( this._selected_index > index )
		this._selected_index--;
};

/**
 * Removes all items from the listbox.
 */
UI.Listbox.prototype.remove_all_items = function()
{
	while ( this._items.length > 0 )
		this.remove_item(0);
};

/**
 * Returns the item at the given index.
 *
 * @return	the item at the given index
 */
UI.Listbox.prototype.get_item = function(index)
{
	return this._items[index];
};

/**
 * Returns the index of the given item. (Note: this is obviously a lot
 * slower than get_item, so it's better to keep track of the index
 * of the item you want than to keep track of the item itself and get
 * its index with this method.)
 *
 * @param	item	the item to get the index of 
 * @return			index of the given item
 * @throws	Error	if no item is found
 */
UI.Listbox.prototype.get_index_of_item = function(item)
{
	for ( var i = 0; i < this._items.length; i++ )
	{
		if ( this._items[i] == item )
			return i;
	}
	throw new Error("UI.Listbox.get_index_of_item: no such item was found");
};

/**
 * Sets which item is currently selected, based on the given index.
 *
 * @param	index			the index of the item to select
 */
UI.Listbox.prototype.select_item_by_index = function(index, dont_refresh, debug)
{
	var item_chunk = this._get_item_chunk(index);

	// Deselect old item, if there is one
	if ( this.get_selected_index() != -1 )
	{
		var formerly_selected_item_chunk = this._item_chunks[ this.get_selected_index() ];
		Util.Element.remove_class(formerly_selected_item_chunk, 'selected');
	}

	// Select new item
	this._selected_index = index;
	Util.Element.add_class(item_chunk, 'selected');

	// Trigger change listeners
	var self = this;
	(function() {
		self._trigger_event_listeners('change');
	}).defer();
};

/**
 * Returns the index of the currently selected item. (For
 * Multiple_Listbox, use get_selected_indices() instead.)
 *
 * @return		index of the currently selected item, or -1 if
 *              no item is currently selected
 */
UI.Listbox.prototype.get_selected_index = function()
{
	return this._selected_index;
};

/**
 * Returns the currently selected item. (For Multiple_Listbox, use
 * get_selected_items() instead.)
 *
 * @return		the currently selected item, or null if no item is
 *              currently selected
 */
UI.Listbox.prototype.get_selected_item = function()
{
	var selected_index = this.get_selected_index();

	if ( selected_index > -1 )
		return this.get_item( selected_index );
	else
		return null;
};

/**
 * Returns the number of items in the listbox.
 *
 * @return	the number of items in the listbox
 */
UI.Listbox.prototype.get_length = function()
{
	return this._items.length;
};

/**
 * Changes the current page such that the selected item is displayed.
 */
UI.Listbox.prototype.page_to_selected_item = function()
{
	var desired_page_num = Math.floor(this.get_selected_index() /
									  this._num_results_per_page);
	this._cur_page_num = desired_page_num;	
	this.refresh();
};

/**
 * Refreshes the listbox to reflect added items, changed filters,
 * current page number, and so on.
 */
UI.Listbox.prototype.refresh = function()
{
	this._refresh_items_chunk();
	this._refresh_page_chunk();
};

/**
 * Returns the root element of the listbox, which can then be added to
 * the document tree as appropriate.
 *
 * @return		the root element of the listbox
 */
UI.Listbox.prototype.get_listbox_elem = function()
{
	messagebox('UI.Listbox: this._root_elem', this._root_elem);
	return this._root_elem;
};

/**
 * Loads items from a RSS reader.
 * @param	reader	The Util.RSS.Reader object
 * @param	is_selected	(optional) Boolean-returning function that will be
 * 						called with each RSS item to determine if it should
 *						be initially selected
 */
UI.Listbox.prototype.load_rss_feed = function(reader, is_selected)
{
	var items_added = 0;
	var original_length = this._items.length;
	
	if (!is_selected) {
		var is_selected = function() { return false; };
	}
	var already_selected = this._selected_index >= 0;
	
	var load_more = (function()
	{
		reader.load(this._chunk_transfer_size, this._transfer_timeout);
	}).bind(this);
	
	var retry = (function()
	{
		for (var i = original_length; i < this._items.length; i++) {
			this.remove_item(original_length);
		}
		
		this.load_rss_feed(reader, is_selected);
	}).bind(this);
	
	function handle_error(error_msg, code)
	{
		if (code) {
			error_msg += ' (HTTP Error ' + code + ')';
		}
		this._report_error('Failed to load items: ' + error_msg, retry);
	}
	
	reader.add_event_listener('timeout', function() {
		handle_error('Failed to load items: The operation timed out.', 0);
	}.bind(this));
	
	reader.add_event_listener('load', function(reader, items) {
		var selected = null;
		
		items.each(function(item) {
			this.append_item(item);
			
			// Determine if the current item should start out selected
			// (don't bother doing this if we already have a selected item)
			if (selected === null && !already_selected && is_selected(item)) {
				selected = original_length + items_added;
			}
			
			items_added++;
		}, this);
		
		// Display the newly-added items
		this.refresh();
		
		// Select the item marked as initially selected, if any
		if (selected !== null) {
			this.select_item_by_index(selected);
			this.page_to_selected_item();
		}
		
		if (items.length > 0) {
			try {
				load_more();
			} catch (e) {
				handle_error('Failed to load the next group of items: ' +
					(e.message || e.description || e), 0);
			}
		}
	}.bind(this));
	
	reader.add_event_listener('error', handle_error.bind(this));
	
	// Load the first chunk
	try {
		load_more();
	} catch (e) {
		handle_error('Failed to load the first group of items: ' +
			(e.message || e.description || e), 0)
	}
}

/**
 * Adds a listener to be called on some event.
 */
UI.Listbox.prototype.add_event_listener = function(event_type, listener)
{
	if ( this._event_listeners[event_type] == null )
		this._event_listeners[event_type] = new Array();

	this._event_listeners[event_type].push(listener);
};

/**
 * Triggers the event listeners.
 */
UI.Listbox.prototype._trigger_event_listeners = function(event_type)
{
	if ( this._event_listeners[event_type] != null )
	{
		for ( var i = 0; i < this._event_listeners[event_type].length; i++ )
		{
			this._event_listeners[event_type][i]();
		}
	}
};

UI.Listbox.prototype._report_error = function(error, retry)
{
	if (!retry)
		var retry = null;
	
	while (this._root_elem.firstChild) {
		this._chunks.push(this._root_elem.firstChild);
		this._root_elem.removeChild(this._root_elem.firstChild);
	}
	
	this._error_display.show(error, retry);
}

UI.Listbox.prototype._clear_error = function()
{
	this._error_display.clear();
	
	for (var i = 0; i < this._chunks.length; i++) {
		this._root_elem.appendChild(this._chunks.shift());
	}
}


///////////////////////////////////
//
// ROOT SECTION
//
///////////////////////////////////

/**
 * Creates the root element.
 *
 * @param	listbox_id	the id of the root element
 */
UI.Listbox.prototype._create_root_elem = function(listbox_id)
{
	messagebox('Listbox: this._doc_obj', this._doc_obj);
	this._root_elem = this._doc_obj.createElement('DIV');
	messagebox('Listbox: created root elem', this._root_elem);
	this._root_elem.id = listbox_id;
	Util.Element.add_class(this._root_elem, 'listbox');
	messagebox('Listbox: created root elem', this._root_elem);
};

///////////////////////////////////
//
// FILTER SECTION
//
///////////////////////////////////

/**
 * Appends to the root_elem the chunk which holds the filter.
 *
 * @private
 */
UI.Listbox.prototype._append_filter_chunk = function()
{
	// create filter chunk
	var filter_chunk_elem = this._doc_obj.createElement('DIV');
	Util.Element.add_class(filter_chunk_elem, 'filter_chunk');

	// create label
	var filter_label_elem = this._doc_obj.createElement('SPAN');
	Util.Element.add_class(filter_label_elem, 'label');
	filter_label_elem.appendChild( this._doc_obj.createTextNode('Search:') );

	// create input elem ... 
	this._filter_input_elem = this._doc_obj.createElement('INPUT');
	this._filter_input_elem.setAttribute('size', '20');
	this._filter_input_elem.setAttribute('name', 'filter_input_elem');

	// .. and create event listeners to check the filter ...
	var self = this;
	var event_listener = function() { self._set_filter_string( self._filter_input_elem.value ); };

	// ... and add the listeners to the input elem
	Util.Event.add_event_listener(this._filter_input_elem, 'mouseup', event_listener);
	Util.Event.add_event_listener(this._filter_input_elem, 'change', event_listener);
	Util.Event.add_event_listener(this._filter_input_elem, 'keyup', event_listener);
	Util.Event.add_event_listener(this._filter_input_elem, 'click', event_listener);

	// ... and disable pressing enter
	var event_listener = function(event)
	{
		event = event == null ? _window.event : event;
		return ( event.keyCode != event.DOM_VK_RETURN &&
				 event.keyCode != event.DOM_VK_ENTER );
	};
	this._filter_input_elem.onkeydown = event_listener;
	this._filter_input_elem.onkeypress = event_listener;
	this._filter_input_elem.onkeyup = event_listener;

	// append label and input elem
	filter_chunk_elem.appendChild(filter_label_elem);
	filter_chunk_elem.appendChild(this._filter_input_elem);
	
	// append filter chunk
	this._root_elem.appendChild(filter_chunk_elem);
};

/**
 * Sets the filter string, resets the cur_page to the first one, and
 * tells the listbox to display appropriate items. Usually called from
 * an event listener on filter_input_elem.
 *
 * @private
 */
UI.Listbox.prototype._set_filter_string = function(filter_string)
{
	// only change things if the filter_string is different from
	// what's already there
	if ( this._filter_string != filter_string )
	{
		this._filter_string = filter_string;
		this._cur_page_num = 0;
		this.refresh();
	}
};

/**
 * Sets this._filtered_indices to contain indices of only those items
 * which match the current filter.
 *
 * @private
 */
UI.Listbox.prototype._update_filtered_indices = function()
{
	this._filtered_indices = new Array();
	
	function matches_filter(obj, filter)
	{
		var bare = {}; // see Util.Object.names() for justification
		
		for (var name in obj) {
			if (name in bare)
				continue;
			
			var value = obj[name];
			if (value == null)
				continue;
			
			var t = typeof(value);
			
			if (t == 'object' && matches_filter(value, filter))
				return true;
			if (t == 'function')
				continue;
			if (t != 'string')
				value = String(value);
			
			if (value.toLowerCase().indexOf(filter) >= 0)
				return true;
		}
	}

	if ( this._filter_string == '' )
	{
		for ( var i = 0; i < this._items.length; i++ )
			this._filtered_indices.push(i);
	}
	else
	{
		var cur_item, item_property_name, item_property_lc;
		var filter_string_lc = this._filter_string.toLowerCase();
		for ( var i = 0; i < this._items.length; i++ )
		{
			cur_item = this._items[i];
			
			if (matches_filter(cur_item, filter_string_lc))
				this._filtered_indices.push(i);
		}
	}
};

///////////////////////////////////
//
// ITEMS SECTION
//
///////////////////////////////////

/**
 * Appends to the root_elem the chunk which holds the list of items
 *
 * @private
 */
UI.Listbox.prototype._append_items_chunk = function()
{
	this._items_chunk_elem = this._doc_obj.createElement('DIV');
	Util.Element.add_class(this._items_chunk_elem, 'items_chunk');
	this._root_elem.appendChild(this._items_chunk_elem);
};

/**
 * Clears out the children of items_chunk, and replaces them with
 * chunks made from items which match the current filter/page.  (N.B.:
 * _append_items_chunk must be called before this.)
 *
 * @private
 */
UI.Listbox.prototype._refresh_items_chunk = function()
{
	// Determine starting and ending indices
	var starting_index = this._cur_page_num * this._num_results_per_page;
	var ending_index = (this._cur_page_num + 1) * this._num_results_per_page;

	// Make sure to use items which match the current filter
	this._update_filtered_indices();

	// Clear list of old displayed items 
	Util.Node.remove_child_nodes(this._items_chunk_elem);

	// Display new list of items
	var item_index, item, item_chunk;
	for ( var i = starting_index; i < ending_index && i < this._filtered_indices.length; i++ )
	{
		item_index = this._filtered_indices[i];
		item_chunk = this._get_item_chunk(item_index);
		this._items_chunk_elem.appendChild(item_chunk);
		this._modify_item_chunk(item_chunk, i);
	}

	// Display message if there are no items
	if ( this._filtered_indices.length == 0 )
	{
		var no_items_chunk = this._get_no_items_chunk();
		this._items_chunk_elem.appendChild(no_items_chunk);
	}
};

/**
 * Returns a chunk to be displayed when no items match the current
 * filter criteria, etc.
 *
 * @return		the chunk
 * @private
 */
UI.Listbox.prototype._get_no_items_chunk = function()
{
	var item_chunk = this._doc_obj.createElement('DIV');
	item_chunk.appendChild( this._doc_obj.createTextNode('No matching items.') );
	return item_chunk;
};

/**
 * If an item chunk corresponding to the given index has already been
 * created, returns that item chunk; otherwise, creates one. If you
 * want to muck with how item chunks are created, overload
 * create_item_chunk rather than this method.
 *
 * @param	item_index	the index of the item for which to get an item_chunk
 * @private
 */
UI.Listbox.prototype._get_item_chunk = function(item_index)
{
	var item = this._items[item_index];
	var item_chunk;
	
	if ( this._item_chunks[item_index] != null )
	{
		item_chunk = this._item_chunks[item_index];
	}
	else
	{
		item_chunk = this._create_item_chunk(item);
		this._add_event_listeners_to_item_chunk(item_chunk, item_index);

		this._item_chunks[item_index] = item_chunk;
	}

	return item_chunk;
};

/**
 * Modify the item chunk as appropriate for its place in the set of
 * currently displayed items. (In Image_Listbox, for example, we need
 * to add a class to every third item_chunk.)
 *
 * @param	item_chunk	the item_chunk to modify
 * @param	cur_i		the index of this item in relation to other items
 *                      in the current display
 */
UI.Listbox.prototype._modify_item_chunk = function(item_chunk, cur_i)
{
};

/**
 * Creates a document chunk for the given item.  N.B.: This is a
 * useful method to overload.
 *
 * @param	item	the item for which to create a document chunk
 * @return			the created chunk
 * @private
 */
UI.Listbox.prototype._create_item_chunk = function(item)
{
	//var item_chunk = this._doc_obj.createElement('DIV');
	var item_chunk = this._doc_obj.createElement('A');
	item_chunk.href = 'javascript:void(0);';
	Util.Element.add_class(item_chunk, 'item_chunk');
	item_chunk.appendChild(
		this._doc_obj.createTextNode('Title: ' + item.title + '; description: ' + item.description)
	);
	return item_chunk;
};

/**
 * This adds the appropriate event listeners to the given item_chunk.
 * N.B.: This is a useful method to overload.
 *
 * @param	item_chunk	the item_chunk to which the event listeners will be added
 * @param	item_index	the index of the item (in the array this._items)
 * @private
 */
UI.Listbox.prototype._add_event_listeners_to_item_chunk = function(item_chunk, item_index)
{
	// Hover
	Util.Event.add_event_listener(item_chunk, 'mouseover', function() { Util.Element.add_class(item_chunk, 'hover'); });
	Util.Event.add_event_listener(item_chunk, 'mouseout', function() { Util.Element.remove_class(item_chunk, 'hover'); });

	// Select
	var self = this;
	Util.Event.add_event_listener(item_chunk, 'click', function() { self.select_item_by_index(item_index); });
};

/**
 * Returns true if this item is selected, false otherwise.
 *
 * @param	item	the item which may be selected
 * @return			true if the given item is selected, false otherwise
 * @deprecated		use the public methods above instead
 * @private
 */
UI.Listbox.prototype._is_item_selected = function(item)
{
	for ( var i = 0; i < this._selected_items.length; i++ )
	{
		if ( item == this._selected_items[i] )
			return true;
	}
	return false;
};



///////////////////////////////////
//
// PAGE SECTION
//
///////////////////////////////////

/**
 * Appends to the root_elem the chunk which holds (a) information
 * about which page of items we're currently on, and (b) controls to
 * change pages
 *
 * @private
 */
UI.Listbox.prototype._append_page_chunk = function()
{
	var self = this;

	// create page chunk
	var page_chunk_elem = this._doc_obj.createElement('DIV');
	Util.Element.add_class(page_chunk_elem, 'page_chunk');

	// create and append prev page elem.
	this._prev_page_elem = this._doc_obj.createElement('A');
	this._prev_page_elem.href = 'javascript:void(0);';
	this._prev_page_elem.onclick = function() { self._goto_prev_page(); return false; };
	this._prev_page_elem.appendChild(this._doc_obj.createTextNode('<< Prev'));
	page_chunk_elem.appendChild(this._prev_page_elem);

	this._page_num_elem = this._doc_obj.createElement('SPAN');
	page_chunk_elem.appendChild(this._page_num_elem);

	// create and append next page elem
	this._next_page_elem = this._doc_obj.createElement('A');
	this._next_page_elem.href = 'javascript:void(0);';
	this._next_page_elem.onclick = function() { self._goto_next_page(); return false; };
	this._next_page_elem.appendChild(this._doc_obj.createTextNode('Next >>'));
	page_chunk_elem.appendChild(this._next_page_elem);

	// append page chunk
	this._root_elem.appendChild(page_chunk_elem);
};

/**
 * Refreshes the page chunk with current information. For example, if
 * a user added a filter and there are now fewer pages than there were
 * before, this causes that to be reflected.
 *
 * TEMP: you might want to just gray out the text, rather than hide
 * the element entirely
 *
 * @private
 */
UI.Listbox.prototype._refresh_page_chunk = function()
{
	var total_num_of_pages = Math.ceil( this._filtered_indices.length / this._num_results_per_page );

	// Calculate displayable cur page num
	if ( total_num_of_pages == 0 )
		displayable_cur_page_num = 0;
	else
		displayable_cur_page_num = this._cur_page_num + 1; // +1 because cur_page_num is zero-based

	// Show or hide prev page elem
	if ( displayable_cur_page_num <= 1 )
		this._prev_page_elem.style.visibility = 'hidden';
	else
		this._prev_page_elem.style.visibility = 'visible';

	// Display the current page number and the total number of pages
	if ( this._page_num_elem.hasChildNodes() )
		this._page_num_elem.removeChild(this._page_num_elem.firstChild);

	this._page_num_elem.appendChild(
		this._doc_obj.createTextNode(' ' + displayable_cur_page_num  + ' of ' + total_num_of_pages + ' ')
	);

	// Show or hide next page elem
	if ( displayable_cur_page_num >= total_num_of_pages )
		this._next_page_elem.style.visibility = 'hidden';
	else
		this._next_page_elem.style.visibility = 'visible';
};

/**
 *
 * Displays the next page of items in items_chunk. Is called onclick
 * of the prev_page_elem.
 *
 * @private
 */
UI.Listbox.prototype._goto_prev_page = function()
{
	this._cur_page_num--;
	this.refresh();
};

/**
 * Displays the previous page of items in items_chunk. Is called
 * onclick of the next_page_elem.
 *
 * @private
 */
UI.Listbox.prototype._goto_next_page = function()
{
	this._cur_page_num++;
	this.refresh();
};

// file UI.Masseuse.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a body masseuse, to replace elements 
 * inconvenient to edit with fake elements that are convenient 
 * to edit. For extending only.
 */
UI.Masseuse = function()
{
	this._loki;

	/**
	 * Massages the given node's descendants, replacing any elements inconvenient 
	 * to edit with convenient ones.
	 */
	this.massage_node_descendants = function(node)
	{
	};
	
	/**
	 * Unmassages the given node's descendants, replacing any convenient but fake
	 * elements with real ones.
	 */
	this.unmassage_node_descendants = function(node)
	{
	};

	/**
	 * For convenience.
	 */
	this.massage_body = function()
	{
		this.massage_node_descendants(this._loki.document);
	};

	/**
	 * For convenience.
	 */
	this.unmassage_body = function()
	{
		this.unmassage_node_descendants(this._loki.document);
	};
};

UI.Masseuse.prototype.init = function(loki)
{
	this._loki = loki;
	return this;
};

UI.Masseuse.prototype.assign_fake_id = function assign_fake_element_id(elem) {
	var base = 'az';
	
	function random_int(min, max) {
		return Math.floor(Math.random() * (max - min + 1)) + min;
	}
	
	function generate_id(length) {
		var i, id = '_loki_', c;
		if (!length)
			length = 6
		for (i = 0; i < length; ++i) {
			c = random_int(base.charCodeAt(0), base.charCodeAt(1));
			id += String.fromCharCode(c);
		}
		return (elem.ownerDocument.getElementById(id))
			? generate_id(length)
			: id;
	}
	
	if (!elem.id)
		elem.id = generate_id();
	return elem.id;
};

UI.Masseuse.prototype.remove_fake_id = function remove_fake_element_id(elem) {
	var pattern = /^_loki_[a-z]+$/;
	if (elem.id && pattern.test(elem.id))
		elem.removeAttribute('id');
};

// file UI.Menu.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a menu.
 */
UI.Menu = function()
{
	var self = this;
	var _loki;
	var _chunk;
	var _menuitems = new Array();

	self.init = function(loki)
	{
		_loki = loki;
		return self;
	};

	self.add_menuitem = function(menuitem)
	{
		_menuitems.push(menuitem);
	};

	self.add_menuitems = function(menuitems)
	{
		var i, length;
		if (menuitems) {
			for (i = 0, length = menuitems.length; i < length; ++i)
				self.add_menuitem(menuitems[i]);
		}
	};

	var _get_chunk = function(popup_document)
	{
		var menu_chunk = popup_document.createElement('DIV');
		Util.Event.add_event_listener(menu_chunk, 'contextmenu', 
			function(event)
			{ 
				// Stop the normal context menu from displaying
				try { event.preventDefault(); } catch(e) {} // Gecko
				return false; // IE
			});
		menu_chunk.style.zindex = 1000;
		Util.Element.add_class(menu_chunk, 'contextmenu');

		for ( var i = 0; i < _menuitems.length; i++ )
		{
			menu_chunk.appendChild(_menuitems[i].get_chunk(popup_document));
		}

		//menu_chunk.innerHTML = 'This is the context menu.'
		return menu_chunk;
	};

	/**
	 * Renders the menu.
	 * 
	 * Much of this code, especially the Gecko part, is lightly 
	 * modified from FCK; some parts are modified from TinyMCE;
	 * some parts come from Brian's Loki menu code.
	 */
	self.display = function(click_event)
	{
		if (_loki.owner_window.createPopup) {
			// Make the popup and append the menu to it
			var popup = _loki.owner_window.createPopup();
			var menu_chunk = _get_chunk(popup.document);
			var popup_body = popup.document.body;
			Util.Element.add_class(popup_body, 'loki');
			Util.Document.append_style_sheet(popup.document, _loki.settings.base_uri + 'css/Loki.css');
			popup_body.appendChild(menu_chunk);

			// Get width and height of the menu
			//
			// We use this hack (first appending a copy of the menu directly in the document,
			// and getting its width and height from there rather than from the copy of
			// the menu appended to the popup) because we append the "Loki.css" style sheet to 
			// the popup, but that may not have loaded by the time we want to find the width 
			// and height (even though it will probably be stored in the cache). Since "Loki.css"
			// has already been loaded for the main editor window, we can reliably get the dimensions
			// there.
			//
			// We surround the menu chunk here in a table so that the menu chunk div shrinks
			// in width as appropriate--since divs normally expand width-wise as much as they
			// can.
			var tmp_container = _loki.owner_document.createElement('DIV');
			tmp_container.style.position = 'absolute';
			tmp_container.innerHTML = '<table><tbody><tr><td></td></tr></tbody></table>';
			var tmp_menu_chunk = _get_chunk(_loki.owner_document);
			tmp_container.firstChild.firstChild.firstChild.firstChild.appendChild(tmp_menu_chunk);
			_loki.root.appendChild(tmp_container);
			var width = tmp_menu_chunk.offsetWidth;
			var height = tmp_menu_chunk.offsetHeight;
			_loki.root.removeChild(tmp_container);

			// This simple method of getting width and height would work, if we hadn't
			// loaded a stylesheet for the popup (see above):
			// (NB: we could also use setTimeout for the below, but that would break if 
			// the style sheet wasn't stored in the cache and thus had to be actually
			// downloaded.)
			//popup.show(x, y, 1, 1);
			//var width = menu_chunk.offsetWidth;
			//var height = menu_chunk.offsetHeight;

			Util.Event.add_event_listener(popup.document, 'click', function() { popup.hide(); });

			// Show the popup
			popup.show(click_event.screenX, click_event.screenY, width, height);
		} else {
			// Determine the coordinates at which the menu should be displayed.
			var frame_pos = Util.Element.get_position(_loki.iframe);
			var event_pos = {x: click_event.clientX, y: click_event.clientY};
			var root_offset = Util.Element.get_relative_offsets(_loki.owner_window, _loki.root);

			var x = frame_pos.x + event_pos.x - root_offset.x;
			var y = frame_pos.y + event_pos.y - root_offset.y;
			
			// Create menu, hidden
			var menu_chunk = _get_chunk(_loki.owner_document);
			_loki.root.appendChild(menu_chunk);
			menu_chunk.style.position = 'absolute';
			menu_chunk.style.visibility = 'hidden';

			// Position menu
			menu_chunk.style.left = (x - 3) + 'px';
			menu_chunk.style.top = (y - 3) + 'px';

			// Watch the "click" event for all windows to close the menu
			function close_menu() {
				var w;
				
				if (menu_chunk.parentNode) {
					menu_chunk.parentNode.removeChild(menu_chunk);
					
					var w = _loki.window;
					while (w) {
						w.document.removeEventListener('click', close_menu, false);
						w.document.removeEventListener('contextmenu', close_menu, false);
						w = (w != w.parent) ? w.parent : null;
					}
				}
			}
			
			function add_close_listeners() {
				var w = _loki.window;
				while (w) {
					w.document.addEventListener('click', close_menu, false);
					w.document.addEventListener('contextmenu', close_menu, false);
					w = (w != w.parent) ? w.parent : null;
				}
			}
			
			add_close_listeners.delay(0.2);
	
			// Show menu
			menu_chunk.style.visibility	= '';
		}
	}
} 
// file UI.Menugroup.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a menugroup. For extending only.
 */
UI.Menugroup = function()
{
	var self = this;
	this._loki;

	this.init = function(loki)
	{
		this._loki = loki;
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
	};
};

// file UI.Menuitem.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a menuitem. Can be extended or used as it is.
 */
UI.Menuitem = function()
{
	var label, listener, disabled;

	/**
	 * Inits the menuitem. Params:
	 *    label		string (should not contain HTML)
	 *    listener	function
	 *    disabled	(optional) boolean
	 */
	this.init = function(params)
	{
		if (!params || !params.label || !params.listener) {
			throw new Error('Insufficient information to construct a menu item.');
		}

		label = params.label;
		listener = params.listener;
		disabled = !!params.disabled;

		return this;
	};

	/**
	 * Returns an appendable chunk to render the menuitem.
	 * @return {HTMLElement} chunk
	 */
	this.get_chunk = function(doc)
	{
		var container;
		
		if (disabled) {
			container = doc.createElement('SPAN');
			Util.Element.add_class(container, 'disabled');
		} else {
			container = doc.createElement('A');
			container.href = 'javascript:void(0);';
			Util.Element.add_class(container, 'menuitem');
			Util.Event.add_event_listener(container, 'click', listener);
		}
		
		container.innerHTML = label.replace(' ', '&nbsp;');
		return container;
	};
	
	/**
	 * Gets the menu item's label.
	 * @return {String}
	 */
	this.get_label = function()
	{
		return label;
	}
	
	/**
	 * Gets the menu item's click listener.
	 * @return {Function}
	 */
	this.get_listener = function()
	{
		return listener;
	}
	
	/**
	 * Returns true if the menu item is disabled, false if otherwise.
	 * @return {Boolean}
	 */
	this.is_disabled = function() {
		return disabled;
	}
};

// file UI.Messenger.js
/**
 * @class Displays informative messages to the user.
 * @author Eric Naeseth
 */
UI.Messenger = {
	/**
	 * Displays a message.
	 * @param {string}  message  the message to be displayed
	 * @return {void}
	 */
	display: function display_message(message)
	{
		// It'd be nice to have a non-alert implementation of this someday. -EN
		alert(message);
	},
	
	/**
	 * Displays a message only once for the current user session.
	 * This works by setting a session cookie when the message is first
	 * displayed. If, when this function is called again, the cookie already
	 * exists, the message is not displayed.
	 * @param {string}  id       a fixed ID that can be used to identify this
	 *                           message in a cookie name
	 * @param {string}  message  the message to be displayed
	 * @return {boolean} true if the message was actually displayed, false if
	 *                   not
	 */
	display_once: function display_message_once_per_session(id, message)
	{
		return this.display_once_per_duration(id, message, null);
	},
	
	/**
	 * Displays a message only once for at least some number of days.
	 * This works by setting a cookie with an expiration date when the message
	 * is first displayed. If, when this function is called again, the cookie
	 * already exists, the message is not displayed.
	 * @param {string}  id       a fixed ID that can be used to identify this
	 *                           message in a cookie name
	 * @param {string}  message  the message to be displayed
	 * @param {number}  days     the number of days for which the message should
	 *                           not be shown
	 * @return {boolean} true if the message was actually displayed, false if
	 *                   not
	 */
	display_once_per_duration:
		function display_message_once_per_duration(id, message, days)
	{
		if (!navigator.cookieEnabled)
			return false;
		
		var cookie_name = '_loki2_pmsg_' + id.replace(/\W+/g, '_');
		
		var displayed = Boolean(Util.Cookie.get(cookie_name));
		
		if (!displayed)
			this.display(message);
		
		Util.Cookie.set(cookie_name, 'displayed', days);
		
		return !displayed;
	}
} 
// file UI.Multiple_Items_Error.js
UI.Multiple_Items_Error = function MultipleItemsError(message) {
	var err = new Error(message);
	err.name = 'UI.Multiple_Items_Error';
	return err;
};

UI.Multiple_Items_Error.prototype = new Error();

// file UI.OL_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "ol" toolbar button.
 */
UI.OL_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'ol.png';
	this.title = 'Ordered list';
	this.click_listener = function() { self._loki.toggle_list('ol'); };
};

// file UI.Options.js
UI.Options = function()
{
}


// file UI.Outdent_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "outdent" toolbar button.
 */
UI.Outdent_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'outdent.png';
	this.title = 'Unindent list item(s)';
	this.helper = null;
	
	this.click_listener = function outdent_button_onclick() 
	{
		// Only outdent if we're inside a UL or OL 
		// (Do this to avoid misuse of BLOCKQUOTEs.)
		
		if (!this._helper)
			this.helper = (new UI.List_Helper).init(this._loki);
			
		if (this.helper.get_ancestor_list()) {
			this.helper.outdent();
		} else {
			this.helper.nag_about_indent_use();
		}
	};
	
};

// file UI.Page_Link_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "link to page" toolbar button.
 */
UI.Page_Link_Button = function()
{
	var self = this;
	Util.OOP.inherits(this, UI.Button);

	this.image = 'link.png';
	this.title = 'Insert link (Ctrl+K)';
	this.click_listener = function() { self._helper.open_page_link_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._helper = (new UI.Link_Helper).init(this._loki);
		return this;
	};
};

// file UI.Page_Link_Dialog.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class An email link dialog window. 
 *
 */
UI.Page_Link_Dialog = function()
{
	//Util.OOP.inherits(this, UI.Link_Dialog);
	Util.OOP.inherits(this, UI.Dialog);

	this._dialog_window_width = 615;
	this._dialog_window_height = 410;
	this._CURRENT_PAGE_STR = '(current page)';
	this._LOADING_STR = 'Loading...';
	this._RSS_TAB_STR = 'an existing item';
	this._CUSTOM_TAB_STR = 'a web address';
	this._EMAIL_TAB_STR = 'an email address';

	/**
	 * Initializes the dialog.
	 *
	 * @param	params	object containing the following named paramaters in addition
	 *                  to those initialized in UI.Dialog.init, q.v.:
	 *                  <ul>
	 *                  </ul>
	 */
	this.init = function(loki, params)
	{
		this._loki = loki;
		
		this._anchor_names = params.anchor_names;
		this._sites_feed = params.sites_feed;
		this._finder_feed = params.finder_feed;
		this._default_site_regexp = params.default_site_regexp;
		this._default_type_regexp = params.default_type_regexp;
		// use rss integration only if sites_feed and finder_feed are given:
		this._use_rss = params.sites_feed && params.finder_feed;
		
		this._initially_selected_nameless_uri = null;
		this._initially_selected_name = null;

		// used because we want to perform certain actions only
		// when the dialog is first starting up, and others only
		// when the dialog *isn't* first starting up.
		this._links_already_loaded_once = false;
		this._anchors_already_loaded_once = false;

		this._link_information = [];

		this.superclass.init.call(this, params);
		return this;
	};

	this._set_title = function()
	{
		if ( this._initially_selected_item.uri == '' )
			this._dialog_window.document.title = "Create a Link";
		else
			this._dialog_window.document.title = "Edit a Link";
	};

	this._append_style_sheets = function()
	{
		this.superclass._append_style_sheets.call(this);
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Tabset.css');
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Link_Dialog.css');
	};

	this._populate_main = function()
	{
		this.item_selector = new UI.Page_Link_Selector(this);
		
		this._append_heading();
		this._append_tabset();
		if ( this._use_rss )
			this._append_rss_tab();
		this._append_email_tab();
		this._append_custom_tab();
		//this._append_main_links_chunk();
		this._append_link_information_chunk();
		this._append_submit_and_cancel_chunk();
		this._append_remove_link_chunk();
		
		this._sanity_error_displays = null;
		
		this._sites_error_display = (this._use_rss)
			? new UI.Error_Display(this._doc.getElementById('sites_pane'))
			: null;
	};

	this._append_heading = function()
	{
		var h1 = this._dialog_window.document.createElement('H1');
		if ( this._initially_selected_item.uri == '' )
			h1.innerHTML = 'Make a link to:';
		else
			h1.innerHTML = 'Edit link to:';
		this._main_chunk.appendChild(h1);
	};

	this._append_tabset = function()
	{
		this._tabset = new Util.Tabset({document : this._dialog_window.document});		
		if ( this._use_rss )
			this._tabset.add_tab('rss', this._RSS_TAB_STR);
		this._tabset.add_tab('custom', this._CUSTOM_TAB_STR);
		this._tabset.add_tab('email', this._EMAIL_TAB_STR);
		var self = this;
		this._tabset.add_select_listener(function(old_tab, new_tab) { self._update_link_information(old_tab, new_tab); });
		this._main_chunk.appendChild(this._tabset.tabset_elem);
	};

	this._append_rss_tab = function()
	{
		var container = this._doc.createElement('DIV');
		this._tabset.get_tabpanel_elem('rss').appendChild(container);

		// Sites pane
		var sites_pane = this._doc.createElement('DIV');
		sites_pane.id = 'sites_pane';
		container.appendChild(sites_pane);
		
		this._sites_progress = this.create_activity_indicator('textual', 'Loading sites&hellip;');
		this._sites_progress.insert(sites_pane);
		return;
	};

	this._append_custom_tab = function()
	{
		var container = this._doc.createElement('DIV');
		this._tabset.get_tabpanel_elem('custom').appendChild(container);

		var label = this._doc.createElement('LABEL');
		label.htmlFor = 'custom_input';
		label.innerHTML = 'Destination web address: ';
		container.appendChild(label);

		// adding this via innerHTML above doesn't work in Gecko for some reason
		this._custom_input = this._doc.createElement('INPUT');
		this._custom_input.id = 'custom_input';
		this._custom_input.type = 'text';
		this._custom_input.setAttribute('size', '40');
		// XXX: maybe this should go in apply_initially_selected_item
		if ( this._initially_selected_item.uri != '' && 
			 this._initially_selected_item.uri.search != null &&
			 this._initially_selected_item.uri.search( new RegExp('^mailto:') ) == -1 )
		{
			this._custom_input.value = this._initially_selected_item.uri;
		}
		else
		{
			this._custom_input.value = 'http://';
		}
		container.appendChild(this._custom_input);	
	};

	this._append_email_tab = function()
	{
		var container = this._doc.createElement('DIV');
		this._tabset.get_tabpanel_elem('email').appendChild(container);

		var label = this._doc.createElement('LABEL');
		label.innerHTML = 'Email address: ';
		label.htmlFor = 'email_input';
		container.appendChild(label);

		this._email_input = this._doc.createElement('INPUT');
		this._email_input.id = 'email_input';
		this._email_input.type = 'text';
		this._email_input.setAttribute('size', '40');
		// XXX: maybe this should go in apply_initially_selected_item
		if ( this._initially_selected_item.uri != null &&
			 this._initially_selected_item.uri.search != null &&
			 this._initially_selected_item.uri.search( new RegExp('^mailto:') ) > -1 )
		{
			this._email_input.value = this._initially_selected_item.uri.replace(new RegExp('^mailto:'), '');
		}
		container.appendChild(this._email_input);

		//var label = this._doc.createElement('DIV');
		//label.innerHTML = 'Please enter the recipient\'s whole email address, including the "@carleton.edu" or "@acs.carleton.edu"';
		//container.appendChild(label);
	};

	this._set_link_title = function(new_title)
	{
		if ( new_title == this._CURRENT_PAGE_STR || 
			 new_title == this._LOADING_STR )
			this._set_link_title_input_value('');
		else
			this._set_link_title_input_value(new_title);
	};

	this._compare_uris = function(uri_a, uri_b)
	{
		return uri_a == uri_b;

		// doesn't work right, I think:

		function split_uri(uri)
		{
			if ( uri == null || uri.split == null )
				return false;

			var u = {};

			// Discard any #name
			var arr = uri.split('#', 2);
			uri = arr[0];

			// Split pre and post ?
			arr = uri.split('?', 2);
			u.pre = arr[0];
			u.post = arr[1];

			// Split post arguments
			u.post = u.post.split('&');

			return u;
		}

		var a = split_uri(uri_a);
		var b = split_uri(uri_b);

		// Check that the splitting worked
		if ( !a || !b )
			return false;
		if ( a.pre != b.pre )
			return false;
		if ( a.post.length != b.post.length )
			return false;

		for ( var i = 0; i < a.pre.length; i++ )
		{
			var matched = false;
			for ( var j = 0; j < b.pre.length; j++ )
			{
				if ( a.pre[i] == b.pre[j] )
				{
					matched = true;
					// this messes up i
					//a.pre.splice(i, 1);
					//b.pre.splice(j, 1);
					//a.pre[i] == '';
					//b.pre[j] == '';
					continue;
				}
			}
			if ( !matched )
				return false;
		}

		return true;
	};
	
	this._sanitize_uri = function(uri)
	{
		return (Util.URI.extract_domain(uri) == this._loki.editor_domain())
			? Util.URI.make_domain_relative(uri)
			: uri;
	}

	this._load_finder = function(feed_uri)
	{
		// Split name from uri
		var a = this._initially_selected_item.httpless_uri.split('#');
		this._initially_selected_nameless_uri = a[0];
		this._initially_selected_name = a.length > 1 ? a[1] : '';
		
		if (a.length > 1 && a[0].length == 0) {
			// We have an anchor but nothing else; this means that the user
			// linked to an anchor on the current item. In this case, we should
			// simply skip going through the finder and proceed as if this
			// were a new link.
			
			this._load_sites(this._sites_feed);
			return;
		}

		// Add initially selected uri
		var self = this;
		var add_initially_selected_uri = function(uri)
		{
			var connector = ( uri.indexOf('?') > -1 ) ? '&' : '?';
			return uri + connector + 'url=' + 
				encodeURIComponent(self._initially_selected_nameless_uri);
		};

		// Load finder
		feed_uri = add_initially_selected_uri(feed_uri)
		var reader = new Util.RSS.Reader(feed_uri);
		var select = this._doc.getElementById('sites_select') || null;
		var error_display = this._sites_error_display;
		var sites_pane = this._doc.getElementById('sites_pane');
		
		error_display.clear();
		
		function report_error(message) {
			this._sites_progress.remove();
			if (select && select.parentNode)
				select.parentNode.removeChild(select);
			
			error_display.show('Failed to load finder: ' + message, function() {
				this._load_finder(feed_uri);
			}.bind(this));
		}
		
		reader.add_event_listener('load', function(feed, new_items) {
			var site_uri, type_uri;
			
			new_items.each(function(item) {
				if (item.title == 'site_feed')
					site_uri = item.link;
				else if (item.title == 'type_feed')
					type_uri = item.link;
			}, this);
		

			// ... then set them if found
			// We make sure to at least set them to null because they may
			// already be set from some previous opening of the dialog.
			this._initially_selected_site_uri = site_uri || null;
			this._initially_selected_type_uri = type_uri || null;

			// Trigger listener
			this._finder_listener();
		}.bind(this));
		reader.add_event_listener('error', report_error.bind(this));
		reader.add_event_listener('timeout', function() {
			report_error.call(this, 'Failed to check the origin of the link ' +
				'within a reasonable amount of time.');
		}.bind(this));
		
		try {
			reader.load(null, 20 /* 20 = 20 seconds until timeout */);
		} catch (e) {
			var message = e.message || e;
			report_error(message);
		}
	};

	this._load_sites = function(feed_uri)
	{
		var sites_pane = this._doc.getElementById('sites_pane');
		
		/*
		function make_uri(offset, num)
		{
			var connector = (uri.indexOf('?') > -1) ? '&' : '?';
			return feed_uri + connector + 'start=' + offset + '&num=' + num;
		}
		*/
		
		var reader = new Util.RSS.Reader(feed_uri);
		var select = this._doc.getElementById('sites_select') || null;
		var error_display = this._sites_error_display;
		
		error_display.clear();
		
		function report_error(message) {
			this._sites_progress.remove();
			if (select && select.parentNode)
				select.parentNode.removeChild(select);
			
			error_display.show('Failed to load sites: ' + message, function() {
				this._load_sites(feed_uri);
			}.bind(this));
		}
		
		reader.add_event_listener('load', function(feed, new_items)
		{
			function load_site()
			{
				if (select.selectedIndex <= 0) {
					this.item_selector.revert();
				} else {
					var o = select.options[select.selectedIndex];
					this.item_selector.load(o.text, o.value);
				}
			}
			
			if (new_items.length == 0) {
				report_error('No sites are available to choose from.');
			}
			
			if (!select) {
				sites_pane.appendChild(this._udoc.create_element('label', {
					htmlFor: 'sites_select'
				}, ['Site:']));
				select = this._udoc.create_element('select', {id: 'sites_select', size: 1});
				select.appendChild(this._udoc.create_element('option', {}, ''));
				
				Util.Event.add_event_listener(select, 'change', load_site.bind(this));
			}
			
			new_items.each(function(item) {
				var uri = this._sanitize_uri(item.link);
				var selected = (this._initially_selected_site_uri)
					? item.link == this._initially_selected_site_uri
					: this._default_site_regexp.test(item.link);
				
				var option = this._udoc.create_element('option', {value: uri,
						selected: selected});
				option.innerHTML = item.title;
				
				select.appendChild(option);
			}.bind(this));
			
			this._sites_progress.remove();
			
			if (select.parentNode != sites_pane)
				sites_pane.appendChild(select);
			
			this.item_selector.insert(sites_pane.parentNode);
			
			if (select.selectedIndex > 0) {
				// Delay this step by a trivial amount to allow the browser
				// to continue execution and render the current state of the
				// page.
				
				var self = this;
				Util.Scheduler.defer(function() {
					load_site.call(self);
				});
			}
				
		}.bind(this));
		
		reader.add_event_listener('error', report_error.bind(this));
		reader.add_event_listener('timeout', function() {
			report_error.call(this, 'Failed to load the list of sites within a reasonable amount of time.');
		}.bind(this));
		
		try {
			reader.load(null, 10 /* 10 = 10 seconds until timeout */);
		} catch (e) {
			var message = e.message || e;
			report_error(message);
		}
	};

	/**
	 * Called as an event listener when the user clicks the submit
	 * button. 
	 */
	this._internal_submit_listener = function()
	{
	    var self = this;
		var tab_name = this._tabset.get_name_of_selected_tab();
		
		if (!this._sanity_error_displays) {
		    this._sanity_error_displays = {};
		}
		
		function get_error_display() {
		    if (!self._sanity_error_displays[tab_name]) {
		        self._sanity_error_displays[tab_name] = new UI.Error_Display(
		            self._tabset.get_tabpanel_elem(tab_name));
		    }
		    
		    return self._sanity_error_displays[tab_name];
		}
		
		if (!this._initially_selected_item.uri) {
			UI.Page_Link_Dialog._default_tab = tab_name;
		}
		
		function do_submission() {
		    // Call external event listener
    		self._external_submit_listener({
    		    uri: uri,
    		    new_window: self._new_window_checkbox.checked,
    		    title: self._link_title_input.value
    		});

    		// Close dialog window
    		self._dialog_window.window.close();
		}
		
		function capitalize(s) {
		    return s.charAt(0).toUpperCase() + s.substr(1).toLowerCase();
		}

		var uri, match, display_uri, actions;
		var errdisp = get_error_display();
		var verb = (!this._initially_selected_item.uri) ? 'insert' : 'save';
		if (tab_name == 'rss') {
		    uri = this.item_selector.get_uri();
			if (!uri) {
				this._dialog_window.window.alert('Please select a page to be linked to.');
				return false;
			}
		} else if (tab_name == 'custom') {
		    uri = this._custom_input.value;
		    
		    // Check for an email address here.
		    if (!(/^mailto:/).test(uri) && (/@/).test(uri) && !(/\//).test(uri)) {
		        function fix_email() {
		            self._email_input.value = uri;
		            self._tabset.select_tab('email');
		            errdisp.clear();
		        }
		        
		        actions = [
		            ["Take me to the right place for an email address.", fix_email],
		            ["No, " + verb + " the link as-is.", do_submission]
		        ];
		        errdisp.show("If you want to link to an email address, you " +
		            "should use the \"" + this._EMAIL_TAB_STR + "\" tab " +
		            "instead.", actions);
		        return;
		    }
		    
		    // Check for a link to the local system.
		    if ((/^file:/).test(uri) || (/[A-Za-z]:\\/).test(uri)) {
		        errdisp.show("That link points to a file on your computer. " +
		            "It will not work if it is clicked on from any other " +
		            "computer. You should upload the file to the Web first. " +
		            "(If you need help doing that, contact your site " +
		            "administrator.)", [[
		                "Ignore this warning and link to the local file.",
		                    do_submission
		            ]]);
		        return;
		    }
		    
		    // Check for weird-protocol links.
		    match = /^(\w+):/.exec(uri);
		    if (match && !(/^(?:https?|mailto|ftp):/.test(uri))) {
		        actions = [[
		            "I understand; " + verb + " the link anyway.", do_submission
		        ]];
		        errdisp.show("This link uses the the <strong>" +
		            match[1].toLowerCase() + "</strong> protocol. Web " +
		            "browsers may not be able to open this link directly.",
		            actions);
		        return;
		    }
		    
		    // Check for an empty link.
		    if (uri.replace(/^\w+:(?:\/\/)?(?:www\.?)?/, '').length <= 0) {
		        errdisp.show("You haven't entered anything to link to.",
		            [["Ignore this warning and " + verb + " the link anyway.",
		                do_submission]]);
		        return;
		    }
		    
		    // Check for a cross-domain link with no protocol.
		    if (!(/^#/).test(uri) && !(/^\w+:/).test(uri) && (/^[^\/]+\.[A-Za-z]+/).test(uri)) {
		        if (uri.length > 20) {
		            display_uri = uri.substr(0, 20) + '&hellip;';
		        } else {
		            display_uri = uri;
		        }
		        
		        function add_scheme() {
		            self._custom_input.value = 'http://' + uri;
		            errdisp.clear();
		        }
		        
		        actions = [
		            ["Fix it.", add_scheme],
		            [capitalize(verb) + " the link as-is.",
		                do_submission]
		        ];
		        errdisp.show("Did you mean to link to link to the Web site "
		            + "<strong>http://</strong>" + display_uri + '? If you ' +
		            'did, the link won\'t work without the http:// at the ' +
		            'beginning.', actions);
		        return;
		    }
		} else if (tab_name == 'email') {
			uri = this._email_input.value;
			if (!(/@/).test(uri) || ((/^\w+:/).test(uri) && !(/^mailto:/).test(uri)) || (/^www\./).test(uri)) {
			    if (uri.length > 20) {
		            display_uri = uri.substr(0, 20) + '&hellip;';
		        } else {
		            display_uri = uri;
		        }
		        
		        function fix_non_email() {
		            self._custom_input.value = uri;
		            self._tabset.select_tab('custom');
		            errdisp.clear();
		        }
		        
		        actions = [
		            ["Take me to the right place for a Web page link.", fix_non_email],
		            ["No, " + verb + " the link as-is.", do_submission]
		        ];
			    errdisp.show("You've asked to link to an email address, " +
			        "but " + uri + " doesn't look like one (maybe it's a Web " +
			        "page?). Are you sure you want to continue?", actions);
			    return;
			}
			
			if (!(/^mailto:/).test(uri))
		        uri = "mailto:" + uri;
		} else {
			throw new Error('Bizarre error: unknown tab "' + tab_name + '".');
		}
		
		// We made it to the end! Let's go through with it.
		do_submission();
	};
	
	this._determine_tab = function determine_tab(use_rss)
	{
		if (arguments.length == 0)
			use_rss = this._use_rss;
		
		if (!this._initially_selected_item.uri) {
			return UI.Page_Link_Dialog._default_tab || (use_rss && 'rss') ||
				'custom';
		} else if (use_rss) {
			return 'rss';
		} else if (/^mailto:/.test(this._initially_selected_item.uri)) {
			return 'email';
		} else {
			return 'custom';
		}
	}
	
	this._select_tab = function select_tab(tab)
	{
		this._tabset.select_tab(tab);
		this._initialize_link_information(tab);
	}

	this._apply_initially_selected_item = function()
	{	
		var tab = this._determine_tab();
		
		if (tab == 'rss' && this._initially_selected_item.uri) {
			this._load_finder(this._finder_feed);
		} else {
			this._select_tab(tab);
			if (this._sites_feed && this._use_rss)
				this._load_sites(this._sites_feed);
		}
	};

	this._finder_listener = function()
	{
		if (!this._use_rss || !this._initially_selected_site_uri) {
			// Not found (or RSS not in use at all, which would be odd...)
			this._select_tab(this._determine_tab(false));
		} else {
			this._select_tab('rss');
		}
		
		this._load_sites(this._sites_feed);
	};

	/**
	 * When a tab other than the RSS one is selected,
	 * when the SELECT elements in the RSS tab switch
	 * to "Loading ..." and back to displaying elements,
	 * IE displays them on whatever tab is currently selected
	 * as well as on the hidden RSS tab.
	 * 
	 * This function avoids that by re-selecting the
	 * currently selected tab. But we don't re-select the
	 * RSS tab if it's selected, because re-selecting that
	 * tab causes the document to flicker, and we the bug
	 * doesn't surface there anyway.
	 *
	 * XXX: At some point it might make sense to hack more
	 * on Util.Select to avoid this bug altogether. I think
	 * the solution would be to never add or remove options
	 * from a displayed select--but hiding and reshowing
	 * the selects gets complicated because so much in
	 * this dialog is done asynchronously.
	 *
	 * XXX: This has been maybe neutered by my changes to this dialog. -EN
	 */
	this._workaround_ie_select_display_bug = function()
	{
		if (window.attachEvent && !window.opera) // XXX: icky IE detection
		{
			var tab_name = this._tabset.get_name_of_selected_tab();
			if ( tab_name != 'rss' )
			{
				this._tabset.select_tab(tab_name);
				this._initialize_link_information(tab_name);
			}
		}
	}

	/**
	 * Appends a chunk with extra options for links.
	 */
	this._append_link_information_chunk = function()
	{
		// Link title
		this._link_title_input = this._dialog_window.document.createElement('INPUT');
		this._link_title_input.size = 40;
		this._link_title_input.id = 'link_title_input';

		var lt_label = this._dialog_window.document.createElement('LABEL');
		var strong = this._dialog_window.document.createElement('STRONG');
		strong.appendChild( this._dialog_window.document.createTextNode('Description: ') );
		lt_label.appendChild(strong);
		lt_label.htmlFor = 'link_title_input';

		lt_comment = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(lt_comment, 'comment');
		lt_comment.innerHTML = '(Will appear in some browsers when mouse is held over link.)';

		var lt_chunk = this._dialog_window.document.createElement('DIV');
		lt_chunk.appendChild(lt_label);
		lt_chunk.appendChild(this._link_title_input);
		lt_chunk.appendChild(lt_comment);

		// "Other options"
		this._other_options_chunk = this._dialog_window.document.createElement('DIV');
		this._other_options_chunk.id = 'other_options';
		if ( this._initially_selected_item.new_window == true )
			this._other_options_chunk.style.display = 'block';
		else
			this._other_options_chunk.style.display = 'none';

		var other_options_label = this._dialog_window.document.createElement('H3');
		var other_options_a = this._udoc.create_element('A',
			{href: 'javascript:void(0)'},
			['More Options']);
			
		var self = this;
		Util.Event.add_event_listener(other_options_a, 'click', function() {
			if (self._other_options_chunk.style.display == 'none') {
				self._other_options_chunk.style.display = 'block';
				other_options_a.firstChild.nodeValue = 'Fewer Options'
			} else {
				self._other_options_chunk.style.display = 'none';
				other_options_a.firstChild.nodeValue = 'More Options'
			}
		});
		other_options_label.appendChild(other_options_a);
		
		// Checkbox
		this._new_window_checkbox = this._dialog_window.document.createElement('INPUT');
		this._new_window_checkbox.type = 'checkbox';
		this._new_window_checkbox.id = 'new_window_checkbox';

		var nw_label = this._dialog_window.document.createElement('LABEL');
		nw_label.appendChild( this._dialog_window.document.createTextNode('Open in new browser window') );
		nw_label.htmlFor = 'new_window_checkbox';

		var nw_chunk = this._dialog_window.document.createElement('DIV');
		nw_chunk.appendChild(this._new_window_checkbox);
		nw_chunk.appendChild(nw_label);

		this._other_options_chunk.appendChild(nw_chunk);

		// Create fieldset and its legend, and append to fieldset
		var fieldset = new Util.Fieldset({legend : 'Link information', document : this._dialog_window.document});
		fieldset.fieldset_elem.appendChild(lt_chunk);
		fieldset.fieldset_elem.appendChild(other_options_label);
		fieldset.fieldset_elem.appendChild(this._other_options_chunk);

		// Append fieldset chunk to dialog
		this._main_chunk.appendChild(fieldset.chunk);
	};

	/**
	 * During initialization, as the various feeds load, the selected tab may change several
	 * times. We only want whichever tab is ultimately selected to have the initially set
	 * link information--the other tabs should have default values. So this function is
	 * called every time a tab change occurs during init, and changes the newly selected
	 * tab's information to the initial information, and the other tabs' information to 
	 * defaults.
	 */
	this._initialize_link_information = function(tab_name)
	{
		// Set all tabs to default values
		['rss', 'custom', 'email'].each(function (name) {
			this._link_information[name] = {
				link_title: '',
				new_window: ''
			}
		}, this);

		// set given tab to initial values
		this._link_information[tab_name] =
		{
			link_title : this._initially_selected_item.title,
			new_window : this._initially_selected_item.new_window
		}

		this._set_link_title_input_value(this._initially_selected_item.title);
		this._new_window_checkbox.checked = this._initially_selected_item.new_window;
	}
	
	this._set_link_title_input_value = function(value)
	{
		this._link_title_input.value = value || '';
	}

	/**
	 * Updates the link information depending on which tab is selected. It's a little
	 * hack-y to have this outside of the tabset, perhaps ... but it was requested late 
	 * in the game, so I'm just doing this quick and dirty.
	 */
	this._update_link_information = function(old_name, new_name)
	{
		// save old information
		this._link_information[old_name] =
		{
			link_title : this._link_title_input.value,
			new_window : this._new_window_checkbox.checked
		};

		// set new information
		if ( this._link_information[new_name] != null )
		{
			this._set_link_title_input_value(this._link_information[new_name].link_title);
			this._new_window_checkbox.checked = this._link_information[new_name].new_window;
		}
		else
		{
			this._set_link_title_input_value('');
			this._new_window_checkbox.checked = false;
		}
	};
	
	this._update_link_title = function update_link_title(tab_name, title)
	{
		var info;
		var active = (this._tabset.get_name_of_selected_tab() == tab_name);
		if (!(info = this._link_information[tab_name])) {
			info = this._link_information[tab_name] = {
				link_title: '',
				new_window: (active && this._new_window_checkbox.checked)
			};
		}
		
		info.link_title = title;
		if (active)
			this._set_link_title(title);
	}

	/**
	 * Creates and appends a chunk containing a "remove link" button. 
	 * Also attaches 'click' event listeners to the button.
	 */
	this._append_remove_link_chunk = function()
	{
		var button = this._dialog_window.document.createElement('BUTTON');
		button.setAttribute('type', 'button');
		button.appendChild( this._dialog_window.document.createTextNode('Remove link') );

		var self = this;
		var listener = function()
		{
			self._external_submit_listener({uri : '', new_window : false, title : ''});
			self._dialog_window.window.close();
		};
		Util.Event.add_event_listener(button, 'click', listener);

		// Setup their containing chunk
		var chunk = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(chunk, 'remove_chunk');
		chunk.appendChild(button);

		// Append the containing chunk
		this._dialog_window.body.appendChild(chunk);
	};
}

UI.Page_Link_Dialog._default_tab = null;

// file UI.Page_Link_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Page_Link_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return this.matches_keycode(e, 75) && e.ctrlKey; }; // Ctrl-K
	this.action = function() { this._link_helper.open_page_link_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._link_helper = (new UI.Link_Helper).init(loki);
		return this;
	};
};

// file UI.Page_Link_Selector.js

/**
 * @class Used by UI.Page_Link_Dialog to allow selection of an item of various
 * types on a site.
 *
 * Replaces a ton of poorly-written code that used to exist directly in
 * UI.Page_Link_Dialog.
 *
 * @author Eric Naeseth
 */
UI.Page_Link_Selector = function(dialog)
{
	var doc = dialog._doc;
	var dh = dialog._udoc;
	
	var wrapper = dh.create_element('div', {id: 'pane_wrapper'});
	var message = new UI.Page_Link_Selector.Message_Display(wrapper);
	var please_choose = doc.createTextNode('Please choose a site from the above box.');
	var site = {url: null, name: null};
	
	var error = null;
	var types = [];
	
	this.dialog = dialog;
	
	this.get_uri = function()
	{
		function get_field(name)
		{
			var list = doc.getElementsByName(name);
			return (!list || list.length == 0)
				? null
				: list[0];
		}
		
		var item_select = get_field('item');
		var anchor_select = get_field('anchor');
		
		function get_anchor()
		{
			var i;
			
			if (!anchor_select)
				return '';
				
			i = anchor_select.selectedIndex || -1;
			
			if (anchor_select.value)
				return '#' + anchor_select.value;
			else if (i > 0)
				return '#' + anchor_select.options[i].value;
			
			return '';
		}
		
		if (!item_select)
			return null;
		
		var url = item_select.options[item_select.selectedIndex].value;
		var anchor = get_anchor();
		
		if (url.length == 0) {
			if (anchor.length == 0)
				return null;
		} else {
			var parsed_uri = Util.URI.parse(url);
			if (!parsed_uri.authority) {
				url = '//' + this.dialog._loki.editor_domain() + url;
			}
		}
		
		return url + anchor;
	}
	
	// Be advised: Util.State_Machine wraps the states' enter() methods;
	// when some_state.enter() is called directly, it's equivalent to
	// calling machine.change(some_state).
	Util.OOP.inherits(this, Util.State_Machine, {
		initial: {
			enter: function() {
				message.insert();
				message.setText(please_choose);
			},
			
			exit: function() {
				message.remove();
			}
		},
		
		loading_site: {
			enter: function()
			{
				types = [];
				
				message.insert();
				message.setHTML('Loading &ldquo;' + site.name +
					'&rdquo&hellip;');
				
				var reader = new Util.RSS.Reader(site.url);
				var machine = this.machine;
				
				reader.add_event_listener('load', function(feed)
				{
					if (feed.items.length == 0) {
						machine.states.error.set('No link types are available' +
							' to choose from.', function() {
								machine.change('loading_site')
							});
						machine.states.error.enter();
						return;
					}
					
					feed.items.each(function(item) {
						types.push({
							name: (item.plural_title || item.title),
							instance_name: item.title,
							url: dialog._sanitize_uri(item.link),
							is_default: (dialog._initially_selected_type_uri)
								? Util.URI.equal(item.link, dialog._initially_selected_type_uri)
								: dialog._default_type_regexp.test(item.link)
						});
					});
					
					types.sort(function(a, b) {
						return (a.name == b.name)
							? 0
							: (a.name < b.name ? -1 : 1);
					});
					
					machine.change('interactive');
				});
				
				reader.add_event_listener('error', function (error_msg, code)
				{
					machine.states.error.set('Failed to load the site: ' + error_msg,
						function() {
							machine.change('loading_site');
						}
					);
					machine.states.error.enter();
				});
				
				reader.add_event_listener('timeout', function() {
					machine.states.error.set('Failed to load the site: ' +
						'The operation timed out.',
						function() {
							machine.change('loading_site');
						}
					);
					machine.states.error.enter();
				});
				
				try {
					reader.load(null, 10 /* 10 = 10 seconds until timeout */);
				} catch (e) {
					(function report_error_shortly() {
						machine.states.error.set('Failed to load the site: ' + 
							(e.message || e),
							function() {
								machine.change('loading_site');
							}
						);
						machine.states.error.enter();
					}).defer(); // defer to prevent state machine deadlock
				}
				
			},
			
			exit: function(new_state)
			{
				if (new_state != this.machine.states.interactive)
					message.remove();
			}
		},
		
		interactive: {
			types_pane: null,
			types_list: null,
			
			links_pane: null,
			arbiter: new UI.Page_Link_Selector.Item_Selector(dialog, wrapper),
			
			enter: function(old_state)
			{
				this.types_list = dh.create_element('ul',
					{id: 'types_pane_ul'});

				var prev_selected_li = null;
				function select_type(type, li) {
					if (prev_selected_li) {
						Util.Element.remove_class(prev_selected_li,
							'selected');
					}
					
					Util.Element.add_class(li, 'selected');
					prev_selected_li = li;
					this.arbiter.load(type);
				}

				var selected_type = null;
				types.each(function(type) {
					var link = dh.create_element('a', {}, [type.name]);

					var item = dh.create_element('li', {}, [link]);
					this.types_list.appendChild(item);
					
					Util.Event.add_event_listener(link, 'click', function(e)
					{
						try {
							dialog._update_link_title('rss', '');
							select_type.call(this, type, item);
						} finally {
							Util.Event.prevent_default(e || window.event);
						}
					}.bind(this));
					
					if (type.is_default)
						selected_type = [type, item];
				}.bind(this));

				this.types_pane = dh.create_element('div', {id: 'types_pane'},
					[this.types_list]);
				
				if (old_state == this.machine.states.loading_site)
					message.remove();

				Util.Element.add_class(wrapper, 'contains_types');
				wrapper.appendChild(this.types_pane);
				this.arbiter.change('message');
				
				if (selected_type) {
					// Delay this step by a trivial amount to allow the browser
					// to continue execution and render the current state of the
					// page.
					
					(function() {
						select_type.apply(this, selected_type);
					}).bind(this).defer();
				}
					
			},
			
			exit: function()
			{
				this.arbiter.states.inactive.enter();
				
				wrapper.removeChild(this.types_pane);
				Util.Element.remove_class(wrapper, 'contains_types');
			}
		},
		
		error: new UI.Error_State(wrapper)
	}, 'initial', 'Type selector');
	
	this.insert = function(container)
	{
		container.appendChild(wrapper);
	}
	
	this.remove = function()
	{
		if (wrapper.parentNode)
			wrapper.parentNode.removeChild(wrapper);
	}

	this.revert = function()
	{
		this.states.initial.enter();
	}
	
	this.load = function(site_name, site_url)
	{
		site.name = site_name;
		site.url = site_url;
		this.states.loading_site.enter();
	}
	
	this.reload = function()
	{
		this.states.loading_site.enter();
	}
}

/**
 * @class Chooses the item.
 */
UI.Page_Link_Selector.Item_Selector = function(dialog, wrapper)
{
	var doc = wrapper.ownerDocument;
	var dh = new Util.Document(doc);
	
	var message = new UI.Page_Link_Selector.Message_Display(wrapper);
	var please_choose = doc.createTextNode(
		'Please choose the type of item to which you want to link.');
	
	var inline_p_name = null;
	var type = null;
	var error = null;
	var items = null;
	var uris_to_items = null;
	
	this.load = function(new_type)
	{
		type = new_type;
		inline_p_name = type.name.toLowerCase();
		this.change('loading');
	}
	
	Util.OOP.inherits(this, Util.State_Machine, {
		inactive: {
			enter: function() {
				
			},
			
			exit: function() {
				
			}
		},
		
		message: {
			enter: function() {
				message.insert();
				message.setText(please_choose);
			},
			
			exit: function() {
				message.remove();
			}
		},
		
		loading: {
			enter: function() {
				message.insert();
				message.setHTML('Loading ' + inline_p_name + '&hellip;');
				
				var reader = new Util.RSS.Reader(type.url);
				var machine = this.machine;
				var initial_uri = // XXX: REASON HACK
					Util.URI.strip_https_and_http(dialog._initially_selected_nameless_uri);

				reader.add_event_listener('load', function(feed)
				{
					items = [];
					uris_to_items = {};
					
					if (type.is_default) {
						// XXX: this is kinda hackish
						items.push(
							{
								value: '',
								text: '(current ' + type.instance_name.toLowerCase() + ')'
							}
						);
					} else if (feed.items.length == 0) {
						machine.states.error.set('No ' +
							type.name.toLowerCase() + ' are available to ' +
							'choose from.', function() {
								machine.change('loading')
							});
						machine.states.error.enter();
						return;
					}
					
					// We are not sorting the feed items because the server
					// might be doing fancy things (e.g. nesting).
					
					feed.items.each(function(item) {
						var uri = dialog._sanitize_uri(item.link);
						var item = {
							title: item.title,
							text: item.selector_text || item.title,
							value: uri,
							selected: (initial_uri)
								? Util.URI.equal(initial_uri, item.link)
								: false
						};
						items.push(item);
						uris_to_items[uri] = item;
					});

					machine.states.interactive.enter();
				});

				reader.add_event_listener('error', function (error_msg, code)
				{
					machine.states.error.set('Failed to load the ' + 
						inline_p_name + ': ' + error_msg,
						function() {
							machine.change('loading');
						}
					);
					machine.states.error.enter();
				});
				
				reader.add_event_listener('timeout', function() {
					machine.states.error.set('Failed to load the ' + 
						inline_p_name + ': The operation timed out.',
						function() {
							machine.change('loading');
						}
					);
					machine.states.error.enter();
				});

				try {
					reader.load(null, 10 /* 10 = 10 seconds until timeout */);
				} catch (e) {
					(function report_error_shortly() {
						machine.states.error.set('Failed to load the ' + 
							inline_p_name + ': ' + (e.message || e),
							function() {
								machine.change('loading');
							}
						);
						machine.states.error.enter();
					}).defer(); // defer to prevent state machine deadlock
				}
			},
			
			exit: function() {
				message.remove();
			}
		},
		
		interactive: {
			form: null,
			pane: null,
			
			enter: function()
			{
				this.pane = dh.create_element('form', {className: 'generated', id: 'links_pane'});
				
				this.form = new Util.Form(doc, {
					name: 'Item Selector',
					form: this.pane
				});

				var section = this.form.add_section();
				var select = section.add_select_field(type.instance_name,
					items, {name: 'item'});
					
				function item_changed()
				{
					var el = select.element;
					var option = el.options[el.selectedIndex];
					var item = uris_to_items[option.value];
					var title;
					var initial = dialog._sanitize_uri(dialog._initially_selected_item.uri);
					
					if (initial == option.value) {
						title = dialog._initially_selected_item.title;
					} else {
						// "item" may not be set if we're on the current page
						title = (item) ? item.title : '';
					}
					
					dialog._update_link_title('rss', title);
				}
					
				Util.Event.add_event_listener(select.element, 'change',
					item_changed);
				item_changed();
				
				wrapper.appendChild(this.form.form_element);
				
				// XXX: wonky in IE; neglect it for now.
				if (!Util.Browser.IE) {
					(function () {
						var select_box = select.element;
						var needed_width = select_box.offsetLeft + select_box.offsetWidth;
						var dialog_window = dialog._dialog_window.window;

						var width_diff;
						var height;
						var dd = dialog_window.document;

						if (dialog_window.outerHeight) {
							width_diff =
								(dialog_window.outerWidth - dialog_window.innerWidth);
							height = dialog_window.outerHeight;
						} else if (dd.documentElement && dd.documentElement.clientHeight) {
							width_diff = 0;
							height = dd.documentElement.clientHeight;
						} else if (dd.body.clientHeight) {
							width_diff = 0;
							height = dd.body.clientHeight;
						} else {
							return;
						}

						var ideal_width = needed_width + 55 + width_diff;
						var screen = dialog_window.screen;
						var screen_x = dialog_window.screenX - screen.left;
						
						if (screen_x + ideal_width >= screen.availWidth - 10) {
							ideal_width =
								window.screen.availWidth - screen_x - 10;
						}

						dialog_window.resizeTo(
							[dialog._dialog_window_width, ideal_width].max(),
							height);
					}).delay(.15);
				}
				
				
				function AnchorField()
				{
					Util.OOP.inherits(this, Util.Form.FormField, "Anchor");
					
					var state = 'loading';
					var container = null;
					var present = null;
					
					var activity = dialog.create_activity_indicator('bar');
					// TODO: display a text input box instead of the message
					var message = dh.create_element('p',
						{style: {margin: '0px', fontStyle: 'italic'}},
						['(No anchors were found.)']);
					var selector = null;
					var entry = null;
					
					function show_no_anchors_message()
					{
						if (state != 'none') {
							present.parentNode.removeChild(present);
							present = message;
							container.appendChild(present);
							state = 'none';
						}
					}
						
					function show_anchors(anchors)
					{
						if (anchors.length == 0) {
							show_no_anchors_message();
							return;
						}
						
						if (state == 'interactive') {
							while (selector.childNodes.length > 0)
								selector.removeChild(selector.firstChild);
						} else {
							selector = dh.create_element('select', 
								{name: 'anchor', size: 1});
							present.parentNode.removeChild(present);
							present = selector;
							container.appendChild(present);
							state = 'interactive';
						}
						
						selector.appendChild(dh.create_element('option',
							{value: ''}, ['(none)']));
						
						anchors.each(function(a) {
							selector.appendChild(dh.create_element('option',
								{
									value: a,
									selected: (dialog._initially_selected_name == a)
								}, [a]));
						});
					}
					
					function show_manual_entry()
					{
						if (!entry) {
							entry = dh.create_element('input',
								{name: 'anchor', type: 'text', size: 15});
							if (dialog._initially_selected_name)
								entry.value = dialog._initially_selected_name;
						}
						
						if (present)
							present.parentNode.removeChild(present);
						present = entry;
						container.appendChild(present);
						state = 'interactive';
					}
					
					this.load = function(url)
					{
						if (state != 'loading') {
							present.parentNode.removeChild(present);
							present = activity.indicator;
							container.appendChild(present);
							state = 'loading';
						}
						
						if (url == '') {
							// use the current document's anchors
							show_anchors(dialog._anchor_names);
						} else {
							var request = null;
							
							function nothing_found()
							{
								request.abort();
								show_manual_entry();
							}
							
							function is_html_type()
							{
								var type = request.get_header('Content-Type');
								if (!type)
									return false;
								
								var acceptable_types =
									['text/html', 'text/xml', 'application/xml',
									'application/xhtml+xml'];
								
								return acceptable_types.find(function (t) {
									return (type.indexOf(t) >= 0);
								});
							}
							
							var options = {
								method: 'get',
								timeout: 10,
								
								on_interactive: function(request)
								{
									if (!request.successful() || !is_html_type())
										nothing_found();
								},
								
								on_failure: function()
								{
									nothing_found();
								},
								
								on_success: function(request, transport)
								{
									if (!is_html_type())
										nothing_found();
									
									var parser = new Util.HTML_Parser();
									var names = [];

									parser.add_listener('open', function(tag, params) {
										if (tag.toUpperCase() == 'A') {
											if (params.name && !params.href)
												names.push(params.name);
										}
									})
									parser.parse(transport.responseText);
									
									show_anchors(names);
								}
							};
							
							try {
								request = new Util.Request(url, options);
							} catch (e) {
								show_manual_entry();
							}
							
						}
					}
					
					var really_append = this.append;
					this.append = function(form, doc, dh, target)
					{
						container = target;
						really_append.call(this, form, doc, dh, target);
					}
					
					this.create_element = function(doc, dh)
					{
						present = activity.indicator;
						return present;
					}
				}
				
				var af = new AnchorField();
				section.add_field(af);
				
				function load_anchors()
				{
					var se = select.element;
					af.load(se.options[se.selectedIndex].value);
				}
				
				Util.Event.add_event_listener(select.element, 'change', function() {
					load_anchors();
				});
				load_anchors();
			},
			
			exit: function()
			{
				if (this.form) {
					this.form = null;
				}
				
				if (this.pane)
					this.pane.parentNode.removeChild(this.pane);
			}
		},
		
		error: new UI.Error_State(wrapper)
	}, 'inactive', 'Item selector');
}

/**
 * @class Displays an instructional or loading message.
 */
UI.Page_Link_Selector.Message_Display = function(wrapper)
{
	var doc = wrapper.ownerDocument;
	var message = Util.Document.create_element(doc, 'p', {className: 'message'});

	this.insert = function() {
		if (message.parentNode != wrapper)
			wrapper.appendChild(message);
	}

	this.remove = function() {
		if (message.parentNode)
			message.parentNode.removeChild(message);
	}

	this.setText = function(text)
	{
		if (typeof(text) == 'string')
			text = doc.createTextNode(text);

		while (message.childNodes.length > 0)
			message.removeChild(message.firstChild);

		message.appendChild(text);
	}
	
	this.setHTML = function(html)
	{
		while (message.childNodes.length > 0)
			message.removeChild(message.firstChild);
		
		message.innerHTML = html;
	}
}

// file UI.Paragraph_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Paragraph helper
 */
UI.Paragraph_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.needs_paragraphifying = function(node)
	{
		return node != null && node.nodeName == 'BODY';
		//return ( Util.Node.get_nearest_bl_ancestor_element(node).nodeName == 'BODY' )
	};

	this.possibly_paragraphify = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var container = Util.Range.get_start_container(rng);

		if ( this.needs_paragraphifying(container) )
		{
			this._loki.toggle_block('p');
		}
	};
};

// file UI.Paste_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents toolbar button.
 */
UI.Paste_Button = function()
{
	Util.OOP.inherits(this, UI.Button);

	this.image = 'paste.png';
	this.title = 'Paste (Ctrl+V)';
	this.click_listener = function()
	{
		this._clipboard_helper.paste();
	};

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._clipboard_helper = (new UI.Clipboard_Helper).init(this._loki);
		return this;
	};
};

// file UI.Paste_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Paste_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) {
		if (Util.Browser.Gecko && Util.Browser.Windows && !this.loki.owner_window.GeckoClipboard)
			return false;
		return this.matches_keycode(e, 86) && e.ctrlKey;
	}; //Ctrl-V
	this.action = function() 
	{
		// try-catch so that if anything should go wrong, paste
		// still happens
		try
		{
			this._clipboard_helper.paste();
			return false;
		}
		catch(e)
		{
			return true;
		}
	};

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._clipboard_helper = (new UI.Clipboard_Helper).init(this._loki);
		return this;
	};
};

// file UI.Pre_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "pre" toolbar button.
 */
UI.Pre_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'pre.png';
	this.title = 'Preformatted';
	this.click_listener = function() { self._loki.toggle_block('pre'); };
	this.state_querier = function() { return self._loki.query_command_state('FormatBlock') == 'pre'; };
};

// file UI.Raw_Source_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "source" toolbar button.
 */
UI.Raw_Source_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'debug_source.png';
	this.title = 'Alert raw source';
	this.show_on_source_toolbar = true;
	this.click_listener = function() { Util.Window.alert_debug(self._loki.get_dirty_html()); };
};

// file UI.Right_Align_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "right align" toolbar button.
 */
UI.Right_Align_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'align_right.png';
	this.title = 'Right align (Ctrl+R)';
	this.click_listener = function() { self._loki.exec_command('JustifyRight'); };
	this.state_querier = function() { return self._loki.query_command_state('JustifyRight'); };
};

// file UI.Right_Align_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Right_Align_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);
	this.test = function(e) { return this.matches_keycode(e, 82) && e.ctrlKey; }; // Ctrl-R
	//this.action = function() { this._loki.exec_command('JustifyRight'); };
	this.action = function() { this._align_helper.align_right(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._align_helper = (new UI.Align_Helper).init(this._loki);
		return this;
	};
};

// file UI.Separator_Menuitem.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a menuitem. Can be extended or used as it is.
 */
UI.Separator_Menuitem = function()
{
	var _label;

	this.init = function()
	{
		return this;
	};

	/**
	 * Returns an appendable chunk to render the menuitem.
	 */
	this.get_chunk = function(doc)
	{
		var sep = doc.createElement('HR');
		Util.Element.add_class(sep, 'separator_menuitem');
		return sep;
	};
};

// file UI.Shift_Tab_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Shift_Tab_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return e.keyCode == 9 && e.shiftKey && this._tab_helper.is_no_default(); }; // Tab
	this.action = function() { this._tab_helper.shift_tab(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._tab_helper = (new UI.Tab_Helper).init(this._loki);
		return this;
	};
};

// file UI.Source_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "source" toolbar button.
 */
UI.Source_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'source.png';
	this.title = 'Toggle HTML source view';
	this.show_on_source_toolbar = true;
	this.click_listener = function() {
		try {
			self._loki.toggle_iframe_textarea(); 
		} catch (e) {
			var sent = self._loki.crashed(e);
			alert("An error occurred that prevented your document's HTML " +
				"from being generated." +
				(sent ? " A report of this error has been sent." : "") +
				"\n\nTechnical details:\n" +
				self._loki.describe_error(e));
		}
		
	};
};

// file UI.Spell_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for running spell check.
 */
UI.Spell_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'spellCheck.gif';
	this.title = 'Spell check (F7)';
	this.click_listener = function() { self._spell_helper.open_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._spell_helper = (new UI.Spell_Helper).init(this._loki);
		return this;
	};
};

// file UI.Spell_Dialog.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A spell dialog window.
 */
UI.Spell_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);
	var self = this;

	this._dialog_window_width = 800;
	this._dialog_window_height = 300;

	this.init = function(params)
	{
		this._spell_uri = params.spell_uri;
		this._uncorrected_html = params.uncorrected_html;
		this.superclass.init.call(this, params);
	};

	this._append_style_sheets = function()
	{
		this.superclass._append_style_sheets.call(this);
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Spell_Dialog.css');
	};

	/**
	 * Called when the iframe finishes loading the spellchecked document.
	 */
	this.finish_init_async = function(suggestion_list, words)
	{
		this._suggestion_list = suggestion_list;
		this._spell_iframe_document = Util.Iframe.get_content_document(this._spell_iframe);
		//this._words = Util.Document.get_elements_by_tag_name_ns(this._spell_iframe_document, 'http://www.carleton.edu/spell', 'WORD');
		this._words = words;
		messagebox('words', this._words);
		this._current_word_index = -1; // incremented to 0 in this._next
		this._done = false;
		this._enable_buttons();
		this._next();
	};

	this._set_title = function()
	{
		this._dialog_window.document.title = "Spell check";
	};

	this._populate_main = function()
	{
		this._append_spell_chunk();
		this._load_spell_data();
		this._append_submit_and_cancel_chunk('Apply Changes', 'Cancel Changes');
		var self = this;
		setTimeout(function () { self._resize_dialog_window(false, true); }, 1000);
	};

	this._append_spell_chunk = function()
	{
		var doc = this._dialog_window.document;
		var self = this;

		// Options

		var misspelled_label = doc.createElement('LABEL');
		misspelled_label.htmlFor = 'misspelled_input';
		misspelled_label.innerHTML = 'Misspelled word:';

		this._misspelled_input = doc.createElement('INPUT');
		this._misspelled_input.id = 'misspelled_input';
		this._misspelled_input.disabled = true;

		var replacement_label = doc.createElement('LABEL');
		replacement_label.htmlFor = 'replacement_input';
		replacement_label.innerHTML = 'Replacement:';

		this._replacement_input = doc.createElement('INPUT');
		this._replacement_input.id = 'replacement_input';

		var suggestions_label = doc.createElement('LABEL');
		suggestions_label.htmlFor = 'suggestions_select';
		suggestions_label.innerHTML = 'Suggestions:';

		this._suggestions_select = doc.createElement('SELECT');
		this._suggestions_select.id = 'suggestions_select';
		this._suggestions_select.size = 5;
		Util.Event.add_event_listener(this._suggestions_select, 'change', function() { self._replacement_input.value = self._suggestions_select.value; });

		var options_div = doc.createElement('DIV');
		Util.Element.add_class(options_div, 'options');
		options_div.appendChild(misspelled_label);
		options_div.appendChild(this._misspelled_input);
		options_div.appendChild(replacement_label);
		options_div.appendChild(this._replacement_input);
		options_div.appendChild(suggestions_label);
		options_div.appendChild(this._suggestions_select);

		// Actions

		this._replace_button = doc.createElement('BUTTON');
		this._replace_button.setAttribute('type', 'button');
		this._replace_button.appendChild( doc.createTextNode('Replace') );
		Util.Event.add_event_listener(this._replace_button, 'click', function(e) { self.replace(); });

		this._replace_all_button = doc.createElement('BUTTON');
		this._replace_all_button.setAttribute('type', 'button');
		this._replace_all_button.appendChild( doc.createTextNode('Replace all') );
		Util.Event.add_event_listener(this._replace_all_button, 'click', function() { self.replace_all(); });

		this._ignore_button = doc.createElement('BUTTON');
		this._ignore_button.setAttribute('type', 'button');
		this._ignore_button.appendChild( doc.createTextNode('Ignore') );
		Util.Event.add_event_listener(this._ignore_button, 'click', function() { self.ignore(); });

		this._ignore_all_button = doc.createElement('BUTTON');
		this._ignore_all_button.setAttribute('type', 'button');
		this._ignore_all_button.appendChild( doc.createTextNode('Ignore all') );
		Util.Event.add_event_listener(this._ignore_all_button, 'click', function() { self.ignore_all(); });

		this._disable_buttons();

		var replace_div = doc.createElement('DIV');
		Util.Element.add_class(replace_div, 'replace');
		replace_div.appendChild(this._replace_button);
		replace_div.appendChild(this._replace_all_button);

		var ignore_div = doc.createElement('DIV');
		Util.Element.add_class(ignore_div, 'ignore');
		ignore_div.appendChild(this._ignore_button);
		ignore_div.appendChild(this._ignore_all_button);

		var actions_div = doc.createElement('DIV');
		Util.Element.add_class(actions_div, 'actions');
		actions_div.appendChild(replace_div);
		actions_div.appendChild(ignore_div);

		// Document

		var spell_label = doc.createElement('DIV');
		spell_label.innerHTML = 'Document:';

		this._spell_iframe = doc.createElement('IFRAME');
		this._spell_iframe.setAttribute('style', 'width:100%; height:20ex;'); // XXX tmp
		//Util.Event.add_event_listener(this._spell_iframe, 'load', function() { self.finish_init_async() });
		this._dialog_window.window.do_onframeload = function(suggestion_list, words) { self.finish_init_async(suggestion_list, words); };
		this._spell_iframe.src = this._base_uri + 'auxil/loki_blank.html';

		/* The old way:
		this._spell_iframe = doc.createElement('IFRAME');
		this._spell_iframe.setAttribute('style', 'width:100%; height:20ex;'); // XXX tmp
		//Util.Event.add_event_listener(this._spell_iframe, 'load', function() { self.finish_init_async() });
		this._dialog_window.window.do_onframeload = function(suggestion_list, words) { self.finish_init_async(suggestion_list, words); };
		this._spell_iframe.src = this._base_uri + this._spell_uri + '?text=' + encodeURIComponent(this._uncorrected_html);
		*/

		var spell_container = doc.createElement('DIV'); // XXX tmp
		spell_container.setAttribute('style', 'width:100%; height:20ex;'); // XXX tmp
		spell_container.appendChild(this._spell_iframe); // XXX tmp

		var document_div = doc.createElement('DIV');
		Util.Element.add_class(document_div, 'document');
		document_div.appendChild(spell_label);
		//document_div.appendChild(this._spell_iframe);
		document_div.appendChild(spell_container);

		// (the div-based layout breaks in IE--the iframe wraps no matter 
		// how wide the dialog--, and I can't figure out how to fix it, 
		// so just make a table)
		var table = this._dialog_window.document.createElement('TABLE');
		table.setAttribute('cellspacing', '0px');
		table.setAttribute('cellpadding', '0px');
		table.setAttribute('border', '0px');
		table.setAttribute('width', '100%');
		var tbody = this._dialog_window.document.createElement('TBODY');
		var tr = this._dialog_window.document.createElement('TR');
		var td = this._dialog_window.document.createElement('TD');
		td.setAttribute('valign', 'top');

		var options_td = td.cloneNode(true);
		Util.Element.add_class(options_td, 'options_td');
		options_td.appendChild(options_div);

		var actions_td = td.cloneNode(true);
		Util.Element.add_class(actions_td, 'actions_td');
		actions_td.appendChild(actions_div);

		var document_td = td.cloneNode(true);
		Util.Element.add_class(document_td, 'document_td');
		document_td.appendChild(document_div);

		tr.appendChild(options_td);
		tr.appendChild(actions_td);
		tr.appendChild(document_td);
		tbody.appendChild(tr);
		table.appendChild(tbody);

		// Heading and fieldset
		var h1 = this._dialog_window.document.createElement('H1');
		h1.innerHTML = 'Spell check';
		this._main_chunk.appendChild(h1);

		var fieldset = new Util.Fieldset({legend : '', document : this._dialog_window.document});
		fieldset.fieldset_elem.appendChild(table);
		/*
		fieldset.fieldset_elem.appendChild(options_div);
		fieldset.fieldset_elem.appendChild(actions_div);
		fieldset.fieldset_elem.appendChild(document_div);
		*/
		this._main_chunk.appendChild(fieldset.chunk);
	};

	this._load_spell_data = function()
	{
		this._spell_http_reader = new Util.HTTP_Reader;
		var self = this;
		this._spell_http_reader.add_load_listener(function () { self._load_spell_data_async(); });
		this._spell_http_reader.load(this._base_uri + this._spell_uri, this._uncorrected_html);
		
	};

	this._load_spell_data_async = function()
	{
		var iframe_doc = Util.Iframe.get_content_document(this._spell_iframe);
		var iframe_html = this._spell_http_reader.request.responseText;
		iframe_doc.write(iframe_html);
		iframe_doc.close();
		var iframe_win = Util.Iframe.get_content_window(this._spell_iframe);
		if ( document.all ) // This works for IE. XXX this is sort of a hack
			setTimeout(function() { iframe_win.spell_iframe__do_onload(); }, 1000);
	};

	this._enable_buttons = function()
	{
		this._replace_button.disabled = false;
		this._replace_all_button.disabled = false;
		this._ignore_button.disabled = false;
		this._ignore_all_button.disabled = false;
	};

	this._disable_buttons = function()
	{
		this._replace_button.disabled = true;
		this._replace_all_button.disabled = true;
		this._ignore_button.disabled = true;
		this._ignore_all_button.disabled = true;
	};

	this._internal_submit_listener = function()
	{
		var html = this._spell_iframe_document.getElementsByTagName('BODY')[0].innerHTML;
		// XXX use dom?
		html = html.replace(new RegExp('<spell:word( [^>]*)>', 'gi'), '');
		html = html.replace(new RegExp('<\/spell:word>', 'gi'), '');
		html = html.replace(new RegExp('<\?xml( [^>]*)spell( [^>]*)>', 'gi'), '');
		this._external_submit_listener({corrected_html : html});
		this._dialog_window.window.close();
	};


	this.replace = function()
	{
		if ( this._done )
			return;

		var word = this._words[this._current_word_index];
		word.innerHTML = this._replacement_input.value;
		word.setAttribute('done', 'done');
		this._next();
	};

	this.replace_all = function()
	{
		if ( this._done )
			return;

		var word = this._words[this._current_word_index];
		// When we write to innerHTML below, <word> will, sadly, be
		// destroyed and recreated (although our indices for this._words 
		// will still work in the updated NodeList), so our
		// reference to it will be lost.
		// Therefore we get what we want from <word> here.
		var word_innerHTML = word.innerHTML;
		for ( var i = 0; i < this._words.length; i++ )
		{
			var cur = this._words[i];
			if ( !cur.getAttribute('done') && cur.innerHTML == word_innerHTML )
			{
				cur.innerHTML = this._replacement_input.value;
				cur.setAttribute('done', 'done');
			}
		}
		this._next();
	};

	this.ignore = function()
	{
		if ( this._done )
			return;

		this._next();
	};

	// not sure if this one is working
	this.ignore_all = function()
	{
		if ( this._done )
			return;

		var word = this._words[this._current_word_index];
		for ( var i = 0; i < this._words.length; i++ )
		{
			var cur = this._words[i];
			if ( !cur.getAttribute('done') && cur.innerHTML == word.innerHTML )
			{
				cur.setAttribute('done', 'done');
			}
		}
		this._next();
	};

	this._next = function()
	{
		// 1. Unhighlight the old word and unload suggestions for it
		if ( this._current_word_index > -1 )
		{
			Util.Element.remove_all_classes(this._words[this._current_word_index]);
		}	
		while ( this._suggestions_select.firstChild != null )
			this._suggestions_select.removeChild(this._suggestions_select.firstChild);

		// 2. Advance word_index
		do
		{
			this._current_word_index++;
			if ( this._current_word_index >= this._words.length )
			{
				if ( this._words.length == 0 )
					this._dialog_window.window.alert('No misspelled words have been found.');
				else
					this._dialog_window.window.alert('All words have been corrected or ignored.');
				this._disable_buttons();
				this._misspelled_input.value = '';
				this._replacement_input.value = '';
				this._replacement_input.disabled = true;
				this._suggestions_select.disabled = true;
				this._done = true;
				return false;
			}
		}
		while ( this._words[this._current_word_index].getAttribute('done') )

		// 3. Highlight and scroll to the new word
		var word = this._words[this._current_word_index];
		Util.Element.add_class(word, 'current');
		/*
		var text_iframe_window = window.frames[0]; // I don't know how to get at the scroll_to_word function using 
		text_iframe_window.scroll_to_word(word);   // W3 stuff like document.getElementById('text_iframe').
		*/
		// XXX try this
		var spell_iframe_window = Util.Iframe.get_content_window(this._spell_iframe);
		spell_iframe_window.scroll_to_word(word);

		// 4. Load suggestions into the suggestions listbox and the replacement textbox
		var suggestions = eval( 'this._suggestion_list.' + word.getAttribute('id') );
		if ( suggestions.length > 0 )
		{
			for (var i = 0; i < suggestions.length; i++)
			{
				var the_item = this._dialog_window.document.createElement('OPTION');
				this._suggestions_select.appendChild(the_item);
				the_item.value = suggestions[i];
				the_item.innerHTML = suggestions[i];
			}
			this._suggestions_select.selectedIndex = 0;
			this._replacement_input.value = this._suggestions_select.value;
		}
		else
		{
			this._replacement_input.value = word.innerHTML;
		}

		// 5. Update misspelled word textbox
		this._misspelled_input.value = word.innerHTML;
	};
};

// file UI.Spell_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an spell. Most
 * of this and the other spell check code is verbatim
 * from the old version (first: 14/May/2004) of Loki.
 */
UI.Spell_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.open_dialog = function()
	{
		if ( this._dialog == null )
			this._dialog = new UI.Spell_Dialog;
		this._dialog.init({ base_uri : self._loki.settings.base_uri,
							submit_listener : self.update_body,
		                    uncorrected_html : self._loki.get_html(),
							spell_uri : 'auxil/spell_iframe.php' });
		this._dialog.open();
	};

	this.update_body = function(spell_info)
	{
		self._loki.set_html(spell_info.corrected_html);

		var sel = Util.Selection.get_selection(self._loki.window);
		Util.Selection.move_cursor_to_end(sel, self._loki.body);
		self._loki.window.focus();
	};
};

// file UI.Spell_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Spell_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);
	this.test = function(e) { return e.keyCode == 118; }; // F7
	this.action = function() { this._spell_helper.open_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._spell_helper = (new UI.Spell_Helper).init(this._loki);
		return this;
	};
};

// file UI.Tab_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an anchor. Contains code
 * common to both the button and the menu item.
 */
UI.Tab_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.is_no_default = function()
	{
		// not in table
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		if ( Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE') != null )
			return false;

		// not at beg of li
		var li = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'LI');
		if ( li != null && Util.Range.is_at_beg_of_block(rng, li) )
			return false;

		// not in pre
		if ( Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'PRE') != null )
			return false;

		return true;
	};

	this.focus_next = function()
	{
		var form = this._loki.hidden.form;
		for ( var i = 0; i < form.elements.length; i++ )
		{
			if ( form.elements[i] == this._loki.hidden &&
				 i + 1 < form.elements.length )
			{
				var next_elem = form.elements[i + 1];
				next_elem.focus();
			}
		}
	};
};

// file UI.Tab_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Tab_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);

	this.test = function(e) { return e.keyCode == 9 && !e.shiftKey && 
							  !document.all &&  // XXX: bad
							  this._tab_helper.is_no_default(); }; // Tab
	this.action = function() { this._tab_helper.focus_next(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._tab_helper = (new UI.Tab_Helper).init(this._loki);
		return this;
	};
};

// file UI.Table_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for inserting an table.
 */
UI.Table_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'table.png';
	this.title = 'Insert table';
	this.click_listener = function() { self._table_helper.open_table_dialog(); };

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._table_helper = (new UI.Table_Helper).init(this._loki);
		return this;
	};
};

// file UI.Table_Dialog.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A table dialog window..
 */
UI.Table_Dialog = function()
{
	Util.OOP.inherits(this, UI.Dialog);

	this._dialog_window_width = 615;
	this._dialog_window_width = 585;

	this._bgs = ['bgFFFFCC', 'bgFFFF99', 'bg99CCFF', 'bgCCCCCC', 'bgE8E8E8'];
	this._bg_radios = new Array();
	//this._desc_blank = '(Write your summary here.)';
	this._desc_blank = '';

	this._set_title = function()
	{
		if ( this._initially_selected_item.is_new )
			this._dialog_window.document.title = 'Make a table';
		else
			this._dialog_window.document.title = 'Table properties';
	};

	this._append_style_sheets = function()
	{
		this.superclass._append_style_sheets.call(this);
		//Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/cssSelector.css');
		Util.Document.append_style_sheet(this._dialog_window.document, this._base_uri + 'css/Table_Dialog.css');
	};

	this._populate_main = function()
	{
		this._append_table_properties();
		this._append_table_color_properties();
		this._append_remove_table_button();
		this.superclass._populate_main.call(this);
	};

	/**
	 * Appends a chunk containing table properties.
	 */
	this._append_table_properties = function()
	{
		// Create function to check for digit
		var self = this;
		var is_digit = function(event) 
		{
			// Gecko uses keyCode for alphanumeric codes, charCode for special codes.
			// IE uses charCode for alphanumeric codes, and doesn`t use keyCode at all.
			event = event == null ? self._dialog_window.window.event : event;
			var char_code = event.charCode == null ? event.keyCode : event.charCode;
			// In Gecko, char_code (== event.keyCode) will be 0 if a special key has been pressed.
			return char_code == 0 || ( char_code >= 48 && char_code <=57 ); // is digit
		};

		// Create generic label element
		var generic_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(generic_label, 'label');

		// Create rows input
		this._rows_input = this._dialog_window.document.createElement('INPUT');
		this._rows_input.size = 3;
		this._rows_input.maxlength = 2;
		this._rows_input.id = 'rows_input';
		this._rows_input.onkeypress = is_digit;
		this._rows_input.value = this._initially_selected_item.rows == null ? 0 : this._initially_selected_item.rows;

		var self = this;
		if ( this._initially_selected_item.is_new == false )
		{
			Util.Event.add_event_listener(this._rows_input, 'change', function()
			{
				if ( self._rows_input.value < self._initially_selected_item.rows )
				{
					self._dialog_window.window.alert('Sorry, you cannot decrease the number of rows here--otherwise, you might accidentally delete data. \n\nIf you really want to remove a row, right click in it and select "Delete row".');
					self._rows_input.value = self._initially_selected_item.rows;
					self._rows_input.focus();
				}
			});
		}
		else 
		{
			Util.Event.add_event_listener(this._rows_input, 'change', function()
			{
				if ( self._rows_input.value < 2 )
				{
					self._dialog_window.window.alert('Sorry, at least two rows are required.');
					self._rows_input.value = 2;
					self._rows_input.focus();
				}
			});
		}

		// Create rows label
		var rows_label = generic_label.cloneNode(false);
		rows_label.appendChild( this._dialog_window.document.createTextNode('Rows: ') );
		rows_label.htmlFor = 'rows_input';

		// Create cols input
		this._cols_input = this._rows_input.cloneNode(false);
		this._cols_input.id = 'cols_input';
		this._cols_input.onkeypress = is_digit;
		this._cols_input.value = this._initially_selected_item.cols == null ? 0 : this._initially_selected_item.cols;

		var self = this;
		if ( this._initially_selected_item.is_new == false )
		{
			Util.Event.add_event_listener(this._cols_input, 'change', function()
			{
				if ( self._cols_input.value < self._initially_selected_item.cols )
				{
					self._dialog_window.window.alert('Sorry, you cannot decrease the number of columns here--otherwise, you might accidentally delete data. \n\nIf you really want to remove a column, right click in it and select "Delete column".');
					self._cols_input.value = self._initially_selected_item.cols;
					self._cols_input.focus();
				}
			});
		}
		else
		{
			Util.Event.add_event_listener(this._cols_input, 'change', function()
			{
				if ( self._cols_input.value < 2 )
				{
					self._dialog_window.window.alert('Sorry, at least two columns are required.');
					self._cols_input.value = 2;
					self._cols_input.focus();
				}
			});
		}

		// Create cols label
		var cols_label = generic_label.cloneNode(false);
		cols_label.appendChild( this._dialog_window.document.createTextNode('  Columns: ') );
		cols_label.htmlFor = 'cols_input';

		// Create rows and cols div
		var rows_and_cols_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(rows_and_cols_div, 'field');
		rows_and_cols_div.appendChild(rows_label);
		rows_and_cols_div.appendChild(self._rows_input);
		rows_and_cols_div.appendChild(cols_label);
		rows_and_cols_div.appendChild(self._cols_input);

		// Create border input
		this._border_checkbox = this._dialog_window.document.createElement('INPUT');
		this._border_checkbox.type = 'checkbox';
		this._border_checkbox.id = 'border_checkbox';
		this._border_checkbox.checked = this._initially_selected_item.border == null ? false : this._initially_selected_item.border;

		// Create border label
		var border_label = generic_label.cloneNode(false);
		border_label.appendChild( this._dialog_window.document.createTextNode('Show border:') );
		border_label.htmlFor = 'border_checkbox';

		// Create border div
		var border_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(border_div, 'field');
		border_div.appendChild(border_label);
		border_div.appendChild(self._border_checkbox);

		// Create description textarea
		this._desc_textarea = this._dialog_window.document.createElement('TEXTAREA');
		this._desc_textarea.cols = 25;
		this._desc_textarea.rows = '5';
		this._desc_textarea.id = 'desc_textarea';
		this._desc_textarea.value = this._initially_selected_item.desc == null ? self._desc_blank : this._initially_selected_item.desc;
		/* // This would toggle desc_blank onfocus/blur
		var self = this;
		Util.Event.add_event_listener(this._desc_textarea, 'focus', function()
		{
			if ( self._desc_textarea.value == self._desc_blank )
				self._desc_textarea.value = '';
		});
		Util.Event.add_event_listener(this._desc_textarea, 'blur', function()
		{
			if ( self._desc_textarea.value == '' )
				self._desc_textarea.value = self._desc_blank;
		});
		*/

		// Create description label
		var desc_label = generic_label.cloneNode(false);
		desc_label.appendChild( this._dialog_window.document.createTextNode('Summarize the contents of this table:') );
		desc_label.htmlFor = 'desc_textarea';

		// Create description div
		var desc_div = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(desc_div, 'field');
		desc_div.appendChild(desc_label);
		desc_div.appendChild(this._dialog_window.document.createElement('BR'));
		desc_div.appendChild(self._desc_textarea);

		// Create heading
		var h1 = this._dialog_window.document.createElement('H1');
		if ( this._initially_selected_item.is_new )
			h1.innerHTML = 'Make a table';
		else
			h1.innerHTML = 'Table properties';

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : '', document : this._dialog_window.document});

		// Append all the above to fieldset
		fieldset.fieldset_elem.appendChild(rows_and_cols_div);
		fieldset.fieldset_elem.appendChild(desc_div);
		fieldset.fieldset_elem.appendChild(border_div);

		// Append fieldset chunk to dialog
		this._main_chunk.appendChild(h1);
		this._main_chunk.appendChild(fieldset.chunk);
	};

	/**
	 * Appends a chunk containing table color properties.
	 */
	this._append_table_color_properties = function()
	{
		//
		// We only show the bg section if the table being
		// edited already has a bg, and the user might want
		// to get rid of it. (for legacy)
		//
		if ( this._initially_selected_item.bg )
		{
			// Create generic elements
			var generic_bg_label = this._dialog_window.document.createElement('LABEL');
			Util.Element.add_class(generic_bg_label, 'bg_label');
			//generic_bg_label.appendChild( this._dialog_window.document.createTextNode(' ') );
			generic_bg_label.innerHTML = '&nbsp;';

			var generic_bg_radio = Util.Input.create_named_input({document : this._dialog_window.document, name : 'bg_radio'});
			generic_bg_radio.type = 'radio';

			// Create fieldset and its legend
			var fieldset = new Util.Fieldset({legend : 'Table color properties:', document : this._dialog_window.document});

			// Create the "remove bgcolor" radio and label
			this._no_bg_radio = generic_bg_radio.cloneNode(true);
			this._no_bg_radio.id = 'no_bg_radio';

			var no_bg_label = this._dialog_window.document.createElement('LABEL');
			no_bg_label.appendChild( this._dialog_window.document.createTextNode('Remove background color') );
			no_bg_label.htmlFor = 'no_bg_radio';
			Util.Element.add_class(no_bg_label, 'label');

			// Create the "keep bgcolor" radio and label
			this._keep_bg_radio = generic_bg_radio.cloneNode(true);
			this._keep_bg_radio.id = 'keep_bg_radio';
			this._keep_bg_radio.checked = true; // otherwise we wouldn't be showing any of this at all

			var keep_bg_label = this._dialog_window.document.createElement('LABEL');
			keep_bg_label.appendChild( this._dialog_window.document.createTextNode('Keep background color') );
			keep_bg_label.htmlFor = 'keep_bg_radio';
			Util.Element.add_class(keep_bg_label, 'label');

			// Append them
			fieldset.fieldset_elem.appendChild(this._no_bg_radio);
			fieldset.fieldset_elem.appendChild(no_bg_label);
			fieldset.fieldset_elem.appendChild(this._keep_bg_radio);
			fieldset.fieldset_elem.appendChild(keep_bg_label);

			// Append fieldset chunk to dialog
			this._main_chunk.appendChild(fieldset.chunk);
		}
		
		/* Uncomment if bgs are reinstated
		// Create generic elements
		var generic_bg_label = this._dialog_window.document.createElement('LABEL');
		Util.Element.add_class(generic_bg_label, 'bg_label');
		//generic_bg_label.appendChild( this._dialog_window.document.createTextNode(' ') );
		generic_bg_label.innerHTML = '&nbsp;';

		var generic_bg_radio = Util.Input.create_named_input({document : this._dialog_window.document, name : 'bg_radio'});
		generic_bg_radio.type = 'radio';

		// Create fieldset and its legend
		var fieldset = new Util.Fieldset({legend : 'Table color properties:', document : this._dialog_window.document});

		// Create and append the "no bgcolor" radio and label
		this._no_bg_radio = generic_bg_radio.cloneNode(true);
		this._no_bg_radio.id = 'no_bg_radio';

		var no_bg_label = this._dialog_window.document.createElement('LABEL');
		no_bg_label.appendChild( this._dialog_window.document.createTextNode('Use no background color') );
		no_bg_label.htmlFor = 'no_bg_radio';
		Util.Element.add_class(no_bg_label, 'label');

		fieldset.fieldset_elem.appendChild(this._no_bg_radio);
		fieldset.fieldset_elem.appendChild(no_bg_label);

		// Create and append the bgcolor radios and labels
		var bg_labels = new Array();
		for ( var i = 0; i < this._bgs.length; i++ )
		{
			bg_labels[i] = generic_bg_label.cloneNode(true);
			bg_labels[i].htmlFor = 'bg_' + this._bgs[i] + '_radio';
			Util.Element.add_class(bg_labels[i], this._bgs[i]);

			this._bg_radios[i] = generic_bg_radio.cloneNode(true);
			this._bg_radios[i].id = 'bg_' + this._bgs[i] + '_radio';

			fieldset.fieldset_elem.appendChild(this._bg_radios[i]);
			fieldset.fieldset_elem.appendChild(bg_labels[i]);
		}

		// Append fieldset chunk to dialog
		this._main_chunk.appendChild(fieldset.chunk);
		*/
	};

	/**
	 * Creates and appends a chunk containing a "remove table" button. 
	 * Also attaches 'click' event listeners to the button.
	 */
	this._append_remove_table_button = function()
	{
		var button = this._dialog_window.document.createElement('BUTTON');
		button.setAttribute('type', 'button');
		button.appendChild( this._dialog_window.document.createTextNode('Remove table') );

		var self = this;
		var listener = function()
		{
			if ( confirm('Really remove table? WARNING: This cannot be undone.') )
			{
				self._remove_listener();
				self._dialog_window.window.close();
			}
		}
		Util.Event.add_event_listener(button, 'click', listener);

		// Setup their containing chunk
		var chunk = this._dialog_window.document.createElement('DIV');
		Util.Element.add_class(chunk, 'remove_chunk');
		chunk.appendChild(button);

		// Append the containing chunk
		this._dialog_window.body.appendChild(chunk);
	};

	this._apply_initially_selected_item = function()
	{
		// Apply background
		// (we have to set checked after all the radios are added;
		// otherwise, IE will uncheck what we check.)
		/* Uncomment if bgs are reinstated
		this._no_bg_radio.checked = true;
		for ( var i = 0; i < this._bgs.length; i++ )
		{
			if ( this._bgs[i] == this._initially_selected_item.bg )
			{
				this._bg_radios[i].checked = true;
			}
		}
		*/
	};

	this._internal_submit_listener = function()
	{
		// Determine rows
		if ( this._rows_input.value == '' )
		{
			this._dialog_window.window.alert('Please specify a number of rows.');
			this._rows_input.focus();
			return false;
		}
		var rows = this._rows_input.value;

		// Determine cols
		if ( this._cols_input.value == '' )
		{
			this._dialog_window.window.alert('Please specify a number of columns.');
			this._cols_input.focus();
			return false;
		}
		var cols = this._cols_input.value;
			
		// Determine border
		var border = this._border_checkbox.checked ? true : false;
		
		// Determine description
		if ( this._desc_textarea.value == this._desc_blank || this._desc_textarea.value == '' )
		{
			this._dialog_window.window.alert('Please provide a brief summary of the data in the table.');
			this._desc_textarea.focus();
			return false;
		}
		var desc = this._desc_textarea.value;
		
		// Determine whether the user wants to keep 
		// the background (for legacy)
		var bg = false;
		if ( this._keep_bg_radio != null )
			bg = this._keep_bg_radio.checked;
		/* Uncomment if bgs are reinstated
		// Determine background
		var bg = '';
		for ( var i = 0; i < this._bgs.length; i++ )
		{
			if ( this._bg_radios[i].checked == true )
			{
				bg = this._bgs[i];
			}
		}
		*/

		// Call external event listener
		this._external_submit_listener({rows : rows, cols : cols, border : border, desc : desc, bg : bg});

		// Close dialog window
		this._dialog_window.window.close();
	};
};

// file UI.Table_Helper.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an table. Contains code
 * common to both the button and the menu item. 
 * 
 * Note: keep in mind that table.createTHead() creates _or gets_ 
 * the table's THEAD elem.
 */
UI.Table_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.init = function(loki)
	{
		this._loki = loki;
		this._table_masseuse = (new UI.Table_Masseuse()).init(self._loki);
		return this;
	};

	this.is_table_selected = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE') != null;
	};

	var _cell_boolean_test = function(node)
	{
		return ( node.nodeType == Util.Node.ELEMENT_NODE &&
				 ( node.tagName == 'TD' || node.tagName == 'TH' ) );
	};

	this.is_cell_selected = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return Util.Range.get_nearest_ancestor_node(rng, _cell_boolean_test) != null;
	};

	/**
	 * use is_cell_selected unless you want TD specifically 
	 */
	this.is_td_selected = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TD') != null;
	};

	/**
	 * use is_cell_selected unless you want TH specifically 
	 */
	this.is_th_selected = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TH') != null;
	};

	this.get_selected_table_item = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);

		var selected_item;
		var selected_table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
		if ( selected_table != null )
		{
			var selected_tbody = selected_table.getElementsByTagName('TBODY')[0];

			/* Uncomment if bgs are reinstated
			var bg, classes = Util.Element.get_all_classes(selected_table).split(' ');
			for ( var i = 0; i < classes.length; i++ )
				if ( classes[i].indexOf('bg') === 0 )
					bg = classes[i];
			*/
			// Check whether any bg is present at all (for legacy)
			var classes_str = Util.Element.get_all_classes(selected_table) + ' ' +
			                  Util.Element.get_all_classes(selected_table.rows[0].cells[0]);
			var classes = classes_str.split(' ');
			var bg;
			for ( var i = 0; i < classes.length; i++ )
				if ( classes[i].indexOf('bg') === 0 )
					bg = true;

			selected_item = { rows : selected_tbody.rows.length,
			                  cols : selected_tbody.rows[0].cells.length,
			                  border : selected_table.getAttribute('border') > 0,
			                  desc : selected_table.getAttribute('summary'), 
			                  bg : bg,
			                  is_new : false };
		}
		else
		{
			selected_item = { rows : 2, 
			                  cols : 3, 
			                  border : false, 
			                  desc : null,
			                  bg : false,
			                  is_new : true };
		}

		return selected_item;
	};

	this.get_thead_rows = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var selected_table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
		return selected_table.createTHead().rows;
	};

	this.get_selected_cell_item = function(tagname)
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);

		var selected_item;
		var selected_cell = Util.Range.get_nearest_ancestor_node(rng, _cell_boolean_test);
		if ( selected_cell != null )
			selected_item = { align : selected_cell.getAttribute('align'),
			                  valign : selected_cell.getAttribute('valign'),
			                  wrap : selected_cell.getAttribute('noWrap') == null || selected_cell.getAttribute('noWrap') == '' ? 'yes' : 'no' };
			//selected_item = { colspan : selected_cell.getAttribute('colspan'),
			//                  rowspan : selected_cell.getAttribute('rowspan') };
		else
			selected_item = { align : 'left', 
			                  valign : 'top',
			                  wrap : 'yes' };

		return selected_item;
	};

	this.open_table_dialog = function()
	{
		var selected_item = self.get_selected_table_item();

		if ( this._table_dialog == null )
			this._table_dialog = new UI.Table_Dialog;
		this._table_dialog.init({ base_uri : self._loki.settings.base_uri,
							submit_listener : self.insert_table,
							remove_listener : self.remove_table,
							selected_item : selected_item });
		this._table_dialog.open();
	};

	this.open_cell_dialog = function()
	{
		var selected_item = self.get_selected_cell_item();

		if ( this._cell_dialog == null )
			this._cell_dialog = new UI.Cell_Dialog;
		this._cell_dialog.init({ base_uri : self._loki.settings.base_uri,
						 submit_listener : self.update_cell,
						 selected_item : selected_item });
		this._cell_dialog.open();
	};

	/**
	 * Adds a tr to the given tbody after the given row index.
	 * Index of -1 to insert at end.
	 * tbody doesn't actually have to be a tbody--it can be a thead (or table), too.
	 * Returns the tr.
	 */
	var _insert_tr = function(tbody, index)
	{
		return tbody.insertRow(index);
	};

	/**
	 * Adds a td to the given tr after the given cell index.
	 * Index of -1 to insert at end.
	 * Returns the td.
	 */
	var _insert_td = function(tr, index)
	{
		var td = tr.ownerDocument.createElement('TD');
		td.setAttribute('align', 'left');
		td.setAttribute('valign', 'top');
		if ( index == -1 || index >= tr.childNodes.length )
			tr.appendChild(td);
		else
			tr.insertBefore(td, tr.childNodes[index]);
		return td;
	};

	/**
	 * Adds a th to the given tr after the given cell index.
	 * Index of -1 to insert at end.
	 * Returns the td.
	 */
	var _insert_th = function(tr, index)
	{
		var td = tr.ownerDocument.createElement('TH');
		//td.setAttribute('align', 'left');
		td.setAttribute('valign', 'top');
		if ( index == -1 || index >= tr.childNodes.length )
			tr.appendChild(td);
		else
			tr.insertBefore(td, tr.childNodes[index]);
		return td;
	};

	this.insert_table = function(table_info)
	{
		if ( self.is_table_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tbody = table.getElementsByTagName('TBODY')[0];

			table.setAttribute('border', table_info.border ? '1' : '0');
			table.setAttribute('summary', table_info.desc);

			/* Uncomment if bgs are reinstated
			var classes = Util.Element.get_all_classes(table).split(' ');
			for ( var i = 0; i < classes.length; i++ )
				if ( classes[i].indexOf('bg') === 0 )
					Util.Element.remove_class(table, classes[i]);
			Util.Element.add_class(table, table_info.bg);
			*/
			// Remove bg color if asked (for legacy)
			if ( table_info.bg == false )
			{
				var classes_str = Util.Element.get_all_classes(table);
				if ( classes_str != null )
				{
					var classes = classes_str.split(' ');
					for ( var i = 0; i < classes.length; i++ )
						if ( classes[i].indexOf('bg') === 0 )
							Util.Element.remove_class(table, classes[i]);
				}
			}

			// Update rows and cols
			var old_info = self.get_selected_table_item();
			for ( var i = old_info.rows; i < table_info.rows; i++ )
				_actually_insert_row(tbody, i);
			for ( var i = old_info.cols; i < table_info.cols; i++ )
				_actually_insert_column(table, i);
		}
		else
		{
			// Create the table
			var table = self._loki.document.createElement('TABLE');
			table.setAttribute('cellpadding', '5');
			table.setAttribute('cellspacing', '0');
			table.setAttribute('border', table_info.border ? '1' : '0');
			table.setAttribute('summary', table_info.desc);
			/* Uncomment if bgs are reinstated
			Util.Element.add_class(table, table_info.bg);
			*/

			// ... and tbody and thead
			var tbody = self._loki.document.createElement('TBODY');
			table.appendChild(tbody);
			var thead = table.createTHead();

			// Populate the table ... with a row of ths ...
			var tr = _insert_tr(thead, -1);
			for ( var j = 0; j < table_info.cols; j++ )
			{
				_insert_th(tr, -1);
			}
			// ... and rows of tds
			for ( var i = 0; i < table_info.rows; i++ )
			{
				var tr = _insert_tr(tbody, -1);
				for ( var j = 0; j < table_info.cols; j++ )
				{
					_insert_td(tr, -1);
				}
			}

			// Insert the table
			var sel = Util.Selection.get_selection(self._loki.window);
			Util.Selection.paste_node(sel, table);
			self._loki.window.focus();
		}

		self._table_masseuse.massage_elem(table);
	};

	this.update_cell = function(cell_info)
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var cell = Util.Range.get_nearest_ancestor_node(rng, _cell_boolean_test);

			cell.setAttribute('align', cell_info.align);
			cell.setAttribute('valign', cell_info.valign);

			if ( cell_info.wrap == 'yes' )
			{
				//if ( cell.getAttribute('noWrap') != '' )
					cell.removeAttribute('noWrap');
			}
			else
				cell.setAttribute('noWrap', 'noWrap');

			/* NB: this was commented before bgs were gotten rid of.
			   If bgs are reinstated, keep this commented.
			var classes = (Util.Element.get_all_classes(cell) == null ? '' : Util.Element.get_all_classes(cell)).split(' ');
			for ( var i = 0; i < classes.length; i++ )
				if ( classes[i].indexOf('bg') === 0 )
					Util.Element.remove_class(cell, classes[i]);
			Util.Element.add_class(cell, cell_info.bg);
			*/
		}
		self._table_masseuse.massage_elem(table);
	};

	function _get_column_index(tr, td)
	{
		var col_index;
		for ( var i = 0; i < tr.cells.length; i++ )
		{
			if ( tr.cells[i] == td )
				col_index = i;
		}
		return col_index;
	}

	var _actually_insert_column = function(table, col_index)
	{
		var thead = table.createTHead();
		var tbody = table.getElementsByTagName('TBODY')[0];
		
		for ( var i = 0; i < thead.rows.length; i++ )
		{
			var index = thead.rows[i].cells[col_index - 1] != null ? col_index : -1;
			var new_th = _insert_th(thead.rows[i], col_index);
		}
		for ( var i = 0; i < tbody.rows.length; i++ )
		{
			var index = tbody.rows[i].cells[col_index - 1] != null ? col_index : -1;
			var new_td = _insert_td(tbody.rows[i], col_index);
		}
	};

	this.insert_column = function()
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TR');
			var cell = Util.Range.get_nearest_ancestor_node(rng, _cell_boolean_test);
			var col_index = _get_column_index(tr, cell) + 1;
	
			_actually_insert_column(table, col_index);
		}
		self._table_masseuse.massage_elem(table);
	};

	this.delete_column = function()
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TR');
			var cell = Util.Range.get_nearest_ancestor_node(rng, _cell_boolean_test);
			var col_index = _get_column_index(tr, cell);

			for ( var i = 0; i < table.rows.length; i++ )
			{
				// this needed to manage colspans across multiple columns
				var cur_row = table.rows[i];
				var cur_cell = cur_row.cells[col_index];
				if ( cur_cell.colSpan != 1 )
				{
					colspan = cur_cell.getAttribute("colspan");
					var new_cell = cur_row.insertCell(iCol+1);
					new_cell.colSpan = colspan - 1;
					new_cell.innerHTML = cur_cell.innerHTML; // XXX: should clone children instead
				}
		
				try { table.rows[i].deleteCell(col_index); } catch(e) {}		
			}
		}
		self._table_masseuse.massage_elem(table);
	};

	this.merge_columns = function()
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TR');
			var cell = Util.Range.get_nearest_ancestor_node(rng, _cell_boolean_test);
			var next_cell = cell.nextSibling;

			if ( next_cell != null )
			{
				var colspan1 = cell.getAttribute("colspan");
				var colspan2 = next_cell.getAttribute("colspan");
		
				cell.colspan = colspan1 + colspan2;
				cell.innerHTML += next_cell.innerHTML;
				table.rows[tr.rowIndex].deleteCell(next_cell.cellIndex);
			}
		}
		self._table_masseuse.massage_elem(table);
	};

	function _get_num_of_columns(tbody)
	{
		var n_colspan = 0;
		var n_cols = tbody.rows[0].cells.length;
		for (var i = 0; i < n_cols; i++ )
		{
			n_colspan += tbody.rows[0].cells[i].colSpan;
		}
		return n_colspan;
	}

	var _actually_insert_row = function(tbody, row_index)
	{
		var num_of_cols = _get_num_of_columns(tbody);
		var new_tr = _insert_tr(tbody, row_index);
		for ( var i = 0; i < num_of_cols; i++ )
		{
			_insert_td(new_tr, i);
		}
	};

	this.insert_row = function()
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tbody = table.getElementsByTagName('TBODY')[0];
			var thead = table.createTHead();
			var tr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TR');
			var row_index = tr.rowIndex - thead.rows.length + 1;

			_actually_insert_row(tbody, row_index);
		}
		self._table_masseuse.massage_elem(table);
	};

	this.delete_row = function()
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TR');
			table.deleteRow(tr.rowIndex);
		}
		self._table_masseuse.massage_elem(table);
	};

	this.convert_row_to_header = function()
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TR');
			var thead = table.createTHead();
			var thead_tr = table.ownerDocument.createElement('TR');
			thead.appendChild(thead_tr);
			for ( var i = 0; i < tr.cells.length; i++ )
			{
				var td = tr.cells[i];
				var th = table.ownerDocument.createElement('TH');
				while ( td.firstChild != null )
					th.appendChild( td.removeChild(td.firstChild ) );
				thead_tr.appendChild(th);
			}
			table.deleteRow(tr.rowIndex);
		}
		self._table_masseuse.massage_elem(table);
	};

	this.remove_table = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');

		// Move cursor
		Util.Selection.select_node(sel, table);
		Util.Selection.collapse(sel, false); // to end
		self._loki.window.focus();

		if ( table.parentNode != null )
			table.parentNode.removeChild(table);
	};
};

/*

Public methods:
--------------
insert_table
insert_row
insert_column
convert_row_to_header
delete_row
delete_column
update_table_attrs
update_td_attrs

The general approach:
--------------------
Make the real element, then masseuse.get_fake_elem, then append that.

*/

// file UI.Table_Masseuse.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for massaging a table.
 */
UI.Table_Masseuse = function TableMasseuse()
{
	Util.OOP.inherits(this, UI.Masseuse);
	
	var empty_header_text = 'Column title';
	
	/*
	 * Ensures that the given table follows the thead/tbody/tfoot structure.
	 */
	function normalize_table_structure(table, first_row_is_head)
	{		
		function get_bodies() {
			var bodies;
			var c;
			
			// the tBodies property might be broken under IE8
			if (table.tBodies)
				return table.tBodies;
			
			bodies = [];
			for (var i = 0; i < table.childNodes.length; i++) {
				c = table.childNodes[i];
				
				if (Util.Node.is_tag(c, 'TBODY'))
					bodies.push(c);
			}
			
			return bodies;
		}
		
		function get_first_row()
		{
			var source = get_bodies()[0] || table;
			
			for (var c = source.firstChild; c; c = c.nextSibling) {
				if (Util.Node.is_tag(c, 'TR'))
					return c;
			}
			
			return null;
		}
		
		function promote_row(row, where)
		{
			var method = ('createT' + where.charAt(0).toUpperCase() 
				+ where.substr(1));
			var dest = table[method]();
			
			if (!row)
				return false;
			
			dest.insertBefore(row, dest.firstChild);
			return true;
		}
		
		function is_header_row(row) {
			var maybe = false;
			
			for (var c = row.firstChild; c; c = c.nextSibling) {
				if (Util.Node.is_tag(c, 'TD'))
					return false;
				if (!maybe && Util.Node.is_tag(c, 'TH'))
					maybe = true;
			}
			
			return maybe;
		}
		
		function count_columns(row) {
			var count = 0;
			
			for (var c = row.firstChild; c; c = c.nextSibling) {
				if (Util.Node.is_tag(c, 'TD') || Util.Node.is_tag(c, 'TH'))
					count++;
			}
			
			return count;
		}
		
		function create_header_row(cells) {
			var row = table.ownerDocument.createElement('TR');
			
			for (var i = 0; i < cells; i++) {
				row.appendChild(row.ownerDocument.createElement('TH'));
			}
			
			return row;
		}
		
		function fill_in_empty_cells(row)
		{
			var empty_pat = /^(\s|&nbsp;|<br[^>]*>)+$/i;
			
			for (var c = row.firstChild; c; c = c.nextSibling) {
				if (!Util.Node.is_tag(c, 'TD') && !Util.Node.is_tag(c, 'TH'))
					continue;
				
				if (!c.hasChildNodes() || empty_pat.test(c.innerHTML))
					c.innerHTML = empty_header_text;
			}
		}
		
		if (!Util.Node.is_tag(table, 'TABLE')) {
			throw new TypeError("Cannot normalize the table structure of a " +
				"non-table.");
		}
		
		if (first_row_is_head) {
			promote_row(get_first_row(), 'head');
		}
		
		var head = table.createTHead();
		var head_valid = true;
		if (head.getElementsByTagName("TR").length == 0) {
			// See if the first row of the table is actually a header row.
			var candidate = get_first_row();
			if (candidate && is_header_row(candidate)) {
				promote_row(candidate, 'head');
			} else {
				head_valid = false; // don't worry about the lack of header
				/*
				// Create an empty header row.
				var hr = create_header_row(count_columns(candidate));
				head.appendChild(hr);
				*/
			}
		}
		
		var bodies = get_bodies();
		if (bodies.length == 0) {
			var body = table.ownerDocument.createElement('TBODY');
			head.parentNode.insertBefore(body, head.nextSibling);
			
			for (var c = table.firstChild; c; c = c.nextSibling) {
				if (Util.Node.is_tag(c, 'TR'))
					body.appendChild(c);
			}
		}
		
		if (!head_valid) {
			table.deleteTHead();
		} else {
			for (var c = head.firstChild; c; c = c.nextSibling) {
				if (Util.Node.is_tag(c, 'TR'))
					fill_in_empty_cells(c);
			}
		}
		
		return table;
	}
	
	this.massage_node_descendants =
		function massage_table_node_descendants(node)
	{
		var tables = node.getElementsByTagName('TABLE');
		if (!tables.length)
			return;
		
		for (var i = tables.length - 1; i >= 0; i--) {
			massage_table(tables[i]);
		}
	}
	
	this.unmassage_node_descendants =
		function unmassage_table_node_descendants(node)
	{
		var tables = node.getElementsByTagName('TABLE');
		if (!tables.length)
			return;
		
		for (var i = tables.length - 1; i >= 0; i--) {
			unmassage_table(tables[i]);
		}
	}
	
	function massage_table(table)
	{	
		if (!table.getAttribute('border'))
			Util.Element.add_class(table, 'loki__borderless_table');
		
		// Add trailing <br /> in Gecko, for better display and editing
		if (Util.Browser.Gecko) {
			// First, try innerHTML
			var h;
			if (table.innerHTML != null && table.innerHTML != '')
			{
				h = table.innerHTML;
				h.replace( new RegExp('(<td[ ]?[^>]*>)[ ]*(</td>)', 'gi'), '$1<br />$2' );
				h.replace( new RegExp('(<th[ ]?[^>]*>)[ ]*(</th>)', 'gi'), '$1<br />$2' );
				table.innerHTML = h;
			}
			// But sometimes (namely, when the table is first created in Gecko), 
			// innerHTML is mysteriously not available. In that case, we use the
			// slower DOM method, which on large tables can cause Gecko to display
			// the "Something is causing this script to run slowly; do you want to 
			// kill it?" alert:
			for ( var i = 0; i < table.rows.length; i++ )
			{
				var row = table.rows[i];
				for ( var j = 0; j < row.cells.length; j++ )
				{
					var cell = row.cells[j];
					if ( !( cell.lastChild != null &&
						    cell.lastChild.nodeType == Util.Node.ELEMENT_NODE &&
						    cell.lastChild.tagName == 'BR' ) )
					{
						cell.appendChild( cell.ownerDocument.createElement('BR') );
					}
				}
			}
		}
		
		normalize_table_structure(table, false);
	}
	
	function unmassage_table(table)
	{
		Util.Element.remove_class(table, 'loki__borderless_table');
		
		// Remove trailing <br /> in Gecko
		if (Util.Browser.Gecko) {
			var h = table.innerHTML;
			h.replace(/<br\s*\/?>(<\/t[dh]>)/gi, '$1');
			table.innerHTML = h;

			/*
			for ( var i = 0; i < table.rows.length; i++ )
			{
				var row = table.rows[i];
				for ( var j = 0; j < row.cells.length; j++ )
				{
					var cell = row.cells[j];
					if ( cell.lastChild != null &&
						 cell.lastChild.nodeType == Util.Node.ELEMENT_NODE &&
						 cell.lastChild.tagName == 'BR' )
					{
						cell.removeChild(cell.lastChild);
					}
				}
			}
			*/
		}
	}
	
	this.massage_elem = massage_table;
	this.unmassage_elem = unmassage_table;
};

// file UI.Table_Menugroup.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing a clipboard menugroup. 
 */
UI.Table_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._table_helper = (new UI.Table_Helper).init(this._loki);
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var menuitems = new Array();

		if ( this._table_helper.is_table_selected() )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Table properties',
				listener : this._table_helper.open_table_dialog 
			}) );
		}

		if ( this._table_helper.is_th_selected() )
		{
			var table_item = this._table_helper.get_selected_table_item();

			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Cell properties',
				listener : this._table_helper.open_cell_dialog 
			}) );

			menuitems.push( (new UI.Separator_Menuitem).init() );

			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Insert column',
				listener : this._table_helper.insert_column
			}) );

			if ( table_item.cols > 2 )
			{
				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Delete column',
					listener : this._table_helper.delete_column
				}) );

				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Merge columns',
					listener : this._table_helper.merge_columns
				}) );
			}

			menuitems.push( (new UI.Separator_Menuitem).init() );

			if ( this._table_helper.get_thead_rows().length > 1 )
			{
				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Delete row',
					listener : this._table_helper.delete_row 
				}) );
			}
		}

		if ( this._table_helper.is_td_selected() )
		{
			var table_item = this._table_helper.get_selected_table_item();

			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Cell properties',
				listener : this._table_helper.open_cell_dialog 
			}) );

			menuitems.push( (new UI.Separator_Menuitem).init() );

			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Insert column',
				listener : this._table_helper.insert_column
			}) );

			if ( table_item.cols > 2 )
			{
				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Delete column',
					listener : this._table_helper.delete_column
				}) );

				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Merge columns',
					listener : this._table_helper.merge_columns
				}) );
			}

			menuitems.push( (new UI.Separator_Menuitem).init() );

			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Insert row',
				listener : this._table_helper.insert_row 
			}) );

			if ( table_item.rows > 2 )
			{
				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Delete row',
					listener : this._table_helper.delete_row 
				}) );

				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Convert row to header',
					listener : this._table_helper.convert_row_to_header
				}) );
			}
		}

		return menuitems;
	};
};

// file UI.UL_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "ul" toolbar button.
 */
UI.UL_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'ul.png';
	this.title = 'Unordered list';
	this.click_listener = function() { self._loki.toggle_list('ul'); };
};

// file UI.UL_OL_Masseuse.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for massaging a table.
 */
UI.UL_OL_Masseuse = function()
{
	var self = this;
	var _tagnames = ['UL', 'OL'];
	var is_li = Util.Node.curry_is_tag('LI');
	Util.OOP.inherits(self, UI.Masseuse);

	this.massage_node_descendants = function(node)
	{
		_tagnames.each(function massage_list_tag_descendants(tag) {
			var lists = $A(node.getElementsByTagName(tag));
			var i, length;
			for (i = 0, length = lists.length; i < length; ++i) {
				self.massage_elem(lists[i]);
			}
		});
	};
	
	this.unmassage_node_descendants = function(node)
	{
		_tagnames.each(function unmassage_list_tag_descendants(tag) {
			var lists = $A(node.getElementsByTagName(tag));
			var i, length;
			for (i = 0, length = lists.length; i < length; ++i) {
				self.unmassage_elem(lists[i]);
			}
		});
	};

	// <ul><li>out<ul><li>in</li></ul></li><li>out again</li></ul>
	//   -->
	// <ul><li>out</li><ul><li>in</li></ul><li>out again</li></ul>
	this.massage_elem = function massage_list(list)
	{
		var parent = list.parentNode;
		var next_item;
		if (parent.nodeName == 'LI') {
			next_item = Util.Node.next_matching_sibling(parent, is_li);
			parent.parentNode.insertBefore(list, next_item);
		}
	};

	// <ul><li>out</li><ul><li>in</li></ul><li>out again</li></ul>
	//   -->
	// <ul><li>out<ul><li>in</li></ul></li><li>out again</li></ul>
	this.unmassage_elem = function unmassage_list(list)
	{
		var prev_item;
		
		if (_tagnames.contains(list.parentNode.nodeName)) {
			prev_item = Util.Node.previous_matching_sibling(list, is_li);
			
			if (!prev_item) {
				prev_item = list.ownerDocument.createElement('LI');
				list.parentNode.insertBefore(prev_item, list);
			}
			prev_item.appendChild(list);
		}
	};
};

// file UI.Underline_Button.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents "underline" toolbar button.
 */
UI.Underline_Button = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Button);

	this.image = 'underline.png';
	this.title = 'Underline (Ctrl+U)';
	this.click_listener = function() { self._loki.exec_command('Underline'); };
	this.state_querier = function() { return self._loki.query_command_state('Underline'); };
};

// file UI.Underline_Keybinding.js
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents keybinding.
 */
UI.Underline_Keybinding = function()
{
	Util.OOP.inherits(this, UI.Keybinding);
	this.test = function(e) { return this.matches_keycode(e, 85) && e.ctrlKey; }; // Ctrl-U
	this.action = function() { this._loki.exec_command('Underline'); };
};

// file UI.Loki.js
/**
 * Declares instance variables. <code>init</code> must be called to initialize them.
 * @constructor
 *
 * @class A WYSIWYG HTML editor.
 */
UI.Loki = function Loki()
{
	var _owner_window;
	var _owner_document; // that of _textarea etc.
	var _window;     //
	var _document;   // _window, _document, and _body are those of _iframe's content
	var _body;       //

	var _root;               // + root (div)
	var _toolbar;            // |--- toolbar (div)
	var _textarea_toolbar;   // |--- textarea_toolbar (div)
	var _textarea;           // |---get_nearest_bl_ancestor_element textarea
	var _statusbar;          // |--- statusbar (div)
	var _grippy_wrapper;     // |--+ grippy_wrapper (div)
	var _grippy;             //    |--- grippy (img)
	var _iframe_wrapper;     // |--+ iframe_wrapper (table)
	var _iframe;             //    |--- iframe
	var _hidden;             // |--- hidden (input)

	var _settings;
	var _options;
	var _use_p_hack;
	var _state_change_listeners = [];
	var _masseuses = [];
	var _menugroups = [];
	var _keybindings = [];
	var _editor_domain;
	var _html_generator = null;

	var self = this;


	/**
	 * Returns the (cleaned-up) HTML of the document currently being edited.
	 *
	 * @returns {String} the HTML of the document currently being edited.
	 */
	this.get_html = function()
	{
		var html;
		
		_unmassage_body();
		UI.Clean.clean(_body, _settings);
		if (_html_generator)
			html = _html_generator.generate(_body.childNodes);
		else
			html = _body.innerHTML;
		html = UI.Clean.clean_HTML(html, _settings);
		_massage_body();
		return html;
	};

	this.get_dirty_html = function()
	{
		return _body.innerHTML;
	};

	/**
	 * Sets the HTML of the document.
	 *
	 * @param	html	the HTML of the document
	 */
	this.set_html = function(html)
	{
		_body.innerHTML = html;
		UI.Clean.clean(_body, _settings);
		_massage_body();
	};
	
	this.crash_report = function editor_generate_crash_report(exc)
	{
		var s = Util.Object.clone(this.settings);
		delete s.options;
		
		return {
			version: this.version,
			report_version: "1.0",
			user_agent: navigator.userAgent,
			platform: navigator.platform,
			settings: s,
			options: Util.Object.names(this.options),
			'exception': exc,
			document: this.get_dirty_html()
		};
	};
	
	this.crashed = function loki_editor_crashed(exc)
	{
		var report_uri = _settings.crash_report_uri;
		if (!report_uri)
			return false;
		
		new Util.Request(report_uri, {
			method: "POST",
			headers: {'Content-Type': 'application/json'},
			body: Util.JSON.dump(self.crash_report(exc))
		});
		return true;
	};

	/**
	 * Copies the value of the iframe to the value of the textarea.
	 */
	this.copy_iframe_to_hidden = function()
	{
		_hidden.value = self.get_html();
	};

	/**
	 * Returns whether the textarea (vs the editable iframe)
	 * is currently active.
	 */
	var _is_textarea_active = function()
	{
		return _textarea.parentNode == _root;
	};

	/**
	 * Toggles textarea and iframe.
	 */
	this.toggle_iframe_textarea = function()
	{
		if ( _is_textarea_active() )
		{
			self.textarea_to_iframe();
		}
		else
		{
			self.iframe_to_textarea();
		}
	};
	
	/**
	 * Shows textarea instead of iframe.
	 */
	this.iframe_to_textarea = function()
	{
		_textarea.value = self.get_html(); // this runs the cleaning code
		_root.replaceChild(_textarea, _iframe_wrapper);
		_root.removeChild(_hidden);

		// recreate the toolbars before swapping in the new one,
		// in order to get rid of any lingering "hover"-class'd buttons.
		old_toolbar = _toolbar;
		_create_toolbars(); 
		_root.replaceChild(_textarea_toolbar, old_toolbar);
		_textarea.focus();
	};

	/**
	 * Shows iframe instead of textarea.
	 */
	this.textarea_to_iframe = function()
	{
		// this line crashes IE9 and i am not convinced it is needed since it gets called in _init_async()
		// self.set_html(_textarea.value);
		_root.replaceChild(_iframe_wrapper, _textarea);
		_root.appendChild(_hidden);
		_init_async();

		// recreate the toolbars before swapping in the new one,
		// in order to get rid of any lingering "hover"-class'd buttons.
		old_toolbar = _textarea_toolbar;
		_create_toolbars();
		_root.replaceChild(_toolbar, old_toolbar);
		_window.focus();
	};
	
	function enumerate_options(property) {
		var key, results = [];
		
		if (_options) {
			for (key in _options) {
				if (!property)
					results.append(_options[key]);
				else if (_options[key][property])
					results.append(_options[key][property]);
			}
		}
		
		return results;
	}
	
	/**
	 * Sets focus to the editing window.
	 * @return {void}
	 */
	this.focus = function focus_on_loki()
	{
		var doc = _owner_document;
		
		if (_is_textarea_active()) {
			if ((!doc.hasFocus || doc.hasFocus()) && _textarea == doc.activeElement)
				return;
			_textarea.focus();
		} else if (!_window) {
			throw new Error('Invalid Loki state: cannot focus; Loki window ' +
				'does not yet exist.');
		} else if (Util.Browser.IE) {
			_body.setActive();
			_window.focus();
		} else {
			_window.focus();
		}
	}


	/**
	 * Initializes instance variables.
	 *
	 * @param {HTMLTextAreaElement} textarea the textarea to replace with Loki
	 * @param {Object} settings Loki settings
	 * @returns {UI.Loki} this Loki instance
	 * @see http://code.google.com/p/loki-editor/wiki/Settings
	 */
	this.init = function init_loki(textarea, settings)
	{
		// Incompatible browser check.
		if (!(Util.Browser.IE || Util.Browser.Gecko)) {
			if(jQuery)
			{
				msg = $('<div><strong>Note:</strong> the rich text editor does not work with browsers other than Firefox and Internet Explorer. Please use one of these browsers for rich text editing.</div>');
				area = $(textarea);
				msg.insertBefore(area);
			}
			throw new Error('The Loki HTML editor does not currently support ' +
				'your browser.');
		} else if (!textarea) {
			throw new Error('Cannot initialize Loki without a textarea.');
		} else if (!textarea.form) {
			throw new Error('Cannot initialize Loki because the textarea ' +
				Util.Node.get_debug_string(textarea) + ' does not belong to ' +
				'a form.');
		}
		
		if (settings.options && Util.Browser.WebKit) {
			// WebKit doesn't implement underlining in a way that works for us,
			// and our clipboard support is currently IE only.
			settings.options += ' -underline -clipboard';
		}
		
		_settings = (settings) ? Util.Object.clone(settings) : {};
		self.options = _options = UI.Loki.Options.get(_settings.options || 'default', true);
		_settings.options = _options;
		
		['site', 'type'].each(function cleanup_default_regexp(which) {
			var setting = 'default_' + which + '_regexp';
			if (!_settings[setting])
				return;
			if (!(_settings[setting].exec && _settings[setting].test)) {
				_settings[setting] = new RegExp(_settings[setting]);
			}
		});
		
		if (!_settings.base_uri) {
			_settings.base_uri = autodetect_base_uri();
		}
		
		if (!_settings.html_generator || _settings.html_generator == 'default')
			_settings.html_generator = 'browser';
		else
			_settings.html_generator = _settings.html_generator.toLowerCase();
			
		if (_settings.html_generator == 'loki') {
			_html_generator = new Util.HTML_Generator({
				xhtml: _settings.use_xhtml || false,
				indent_text: "    "
			});
		} else if (_settings.html_generator != 'browser') {
			throw new Error('Unknown HTML generator "' +
				_settings.html_generator + '"; cannot instantiate Loki.');
		}
		
		UI.Clipboard_Helper._setup(_settings.base_uri);
		
		_textarea = textarea;
		_owner_window = window;
		_owner_document = _textarea.ownerDocument;

		_use_p_hacks = _use_p_hacks();

		// Create the various elements
		_create_root();
		_create_toolbars();
		_create_iframe();
		if ( _options.statusbar )
			_create_statusbar();
		_create_grippy();
		_create_hidden();

		// And append them to root
		_root.appendChild( _toolbar );
		_root.appendChild( _iframe_wrapper );
		if ( _options.statusbar )
			_root.appendChild( _statusbar );
		_root.appendChild( _grippy_wrapper );
		_root.appendChild( _hidden );

		// Replace the textarea with root
		_replace_textarea();

		// Append style sheets
		_append_owner_document_style_sheets();

		// Add document massagers
		_add_masseuses();

		// Init possible menugroups, for the context menu
		_init_menugroups();

		// Continue the initialization, but asynchronously
		_init_async();
		
		return self;
	};
	
	/*
	 * Attempts to automatically detect the Loki base URI.
	 */
	function autodetect_base_uri()
	{
		var scripts = document.getElementsByTagName('SCRIPT');
		var pattern = /\bloki\.js\b.*$/;
		
		for (var i = 0; i < scripts.length; i++) {
			if (pattern.test(scripts[i].src)) {
				// Found Loki!
				return scripts[i].src.replace(pattern, '');
			}
		}
		
		throw new Error("Unable to automatically determine the Loki base URI." +
			" Please set it explicitly.");
	}
	
	/**
	 * Finishes initializing instance variables, but does so
	 * asynchronously. All initing that requires _window or _document
	 * to be available should be done in this function, because this
	 * function waits until _window and _document are available to do
	 * anything.
	 */
	var _init_async = function()
	{
		try
		{
			// Try to init references to iframe content's window and
			// document ...
			try
			{
				_window = _iframe.contentWindow;
				_document = _window.document;
				if ( _window == null || _document == null )
					throw(new Error('UI.Loki._init_iframe: Couldn\'t init iframe. Will try again.'));
			}
			// ... but if the window or document aren't available yet
			// (because the 'about:blank' document hasn't finished
			// loading), try again in a few milliseconds.
			//
			// Be sure that if you change the name of the present
			// function, you also change what you call in setTimeout
			// below.
			catch(f)
			{
				setTimeout(_init_async, 10);
				return;
			}

			// Do things that require _window or _document

			// Write out a blank document
			_clear_document();

			_document.close();

			// Append style sheets for the iframe
			_append_document_style_sheets();

			// Init reference to that document's body
			_body = _document.getElementsByTagName('BODY').item(0);
			Util.Element.add_class(_body, 'contentMain'); // so front-end stylesheets work

			// Add public members // XXX the private ones should just be replaced to public ones
			self.window = _window;
			self.document = _document;
			self.body = _body;
			self.owner_window = _owner_window;
			self.owner_document = _owner_document;
			self.root = _root;
			self.iframe = _iframe;
			self.hidden = _hidden;
			self.settings = _settings;
			self.exec_command = _exec_command;
			self.query_command_state = _query_command_state;
			self.query_command_value = _query_command_value;
			
			// Set body's html to textarea's value
			self.set_html( _textarea.value );

			// Make the document editable
			_make_document_editable();

			// Add certain event listeners to the document and elsewhere
			_add_double_click_listeners();
			_add_document_listeners();
			_add_state_change_listeners();
			_add_grippy_listeners();

			// Add keybindings
			_add_keybindings();
		}
		catch(e)
		{
			// If anything goes wrong during initialization, first
			// revert to the textarea before re-throwing the error
			try {
				self.iframe_to_textarea();
			} catch (desperation) {
				// If even that doesn't work, go all the way back.
				_root.parentNode.replaceChild(_textarea, _root);
			}
			
			throw e;
		}
	};
	
	/**
	 * Returns the domain under which this editor instance exists.
	 */
	this.editor_domain = function()
	{
		if (null == self._editor_domain) {
			self._editor_domain = Util.URI.extract_domain(window.location);
		}
		
		return self._editor_domain;
	};

	/**
	 *
	 */
	var _use_p_hacks = function()
	{
		return navigator.product == 'Gecko';
	};

	/**
	 * Creates the root element for Loki.
	 */
	var _create_root = function()
	{
		_root = _owner_document.createElement('DIV');
		Util.Element.add_class(_root, 'loki');
	};

	/**
	 * Creates the toolbar, populated with the appropriate buttons.
	 */
	var _create_toolbars = function()
	{
		// Create the toolbar itself
		_toolbar = _owner_document.createElement('DIV');
		_textarea_toolbar = _owner_document.createElement('DIV');
		Util.Element.add_class(_toolbar, 'toolbar');
		Util.Element.add_class(_textarea_toolbar, 'toolbar');

		// Function to add a button to a the toolbars
		function add_button(button_class)
		{
			var b = new button_class();
			b.init(self);

			function create_button()
			{
				var button = _owner_document.createElement('A'), img, img_src;
				button.href = 'javascript:void(0);';

				Util.Event.add_event_listener(button, 'mouseover', function() { Util.Element.add_class(button, 'hover'); });
				Util.Event.add_event_listener(button, 'mouseout', function() { Util.Element.remove_class(button, 'hover'); });
				Util.Event.add_event_listener(button, 'mousedown', function() { Util.Element.add_class(button, 'active'); });
				Util.Event.add_event_listener(button, 'mouseup', function() { Util.Element.remove_class(button, 'active'); });
				Util.Event.add_event_listener(button, 'click', function() { b.click_listener(); });

				img_src = _settings.base_uri + 'images/toolbar/' + b.image;

				// Apply PNG fix.
				if (Util.Browser.IE && /MSIE 6/.test(navigator.userAgent)) {
					button.title = b.title;
					img = _owner_document.createElement('SPAN');
					img_src = Util.URI.build(Util.URI.normalize(img_src));
					img.className = 'loki_filtered_button';
					img.style.filter = "progid:" +
						"DXImageTransform.Microsoft.AlphaImageLoader(src='" +
					    img_src + "', sizingMethod='image')";
					img.setAttribute('unselectable', 'on');
				} else {
					img = _owner_document.createElement('IMG');
					img.src = img_src;
					img.title = b.title;
					img.border = 0;
					img.setAttribute('unselectable', 'on')
				}
				
				button.appendChild(img);
				return button;
			};

			_toolbar.appendChild(create_button());
			if ( b.show_on_source_toolbar == true )
				_textarea_toolbar.appendChild(create_button());
		};

		// Add each button to the toolbars
		enumerate_options('buttons').each(add_button);
	};

	/**
	 * Creates the iframe
	 */
	var _create_iframe = function()
	{
		_iframe_wrapper = _owner_document.createElement('DIV');
		Util.Element.add_class(_iframe_wrapper, 'iframe_wrapper');

		_iframe = _owner_document.createElement('IFRAME');
		_iframe.src = 'javascript:""';
		_iframe.frameBorder = '0'; // otherwise, IE puts an extra border around the iframe that css cannot erase

		_iframe_wrapper.appendChild(_iframe);

		// Take styles from textarea
		var h = _textarea.clientHeight;
		//_set_height(h);
		// We also need to try again in a second, because in some 
		// versions of FF (e.g. 1.0.6 on win, and some on mac), 
		// the above doesn't work
		setTimeout( function () { _set_height(h); }, 1000 );
		//_set_width(_textarea.clientWidth); // XXX you should check here whether it's width = 100% (or another percentage), then actually copy that; otherwise you can base the new width on clientWidth as here.
	};

	/**
	 * Creates the statusbar
	 */
	var _create_statusbar = function()
	{
		_statusbar = _owner_document.createElement('DIV');
		Util.Element.add_class(_statusbar, 'statusbar');
	};

	/**
	 * Creates the grippy
	 */
	var _create_grippy = function()
	{
		// Actually create the elem
		_grippy_wrapper = _owner_document.createElement('DIV');
		Util.Element.add_class(_grippy_wrapper, 'grippy_wrapper');
		_grippy = _owner_document.createElement('IMG');
		_grippy.src = _settings.base_uri + 'images/grippy.gif';
		Util.Element.add_class(_grippy, 'grippy');
		_grippy_wrapper.appendChild(_grippy);
		//_grippy.innerHTML = 'grippy';
	};

	/**
	 * Adds listeners to make the grippy actually resize the document.
	 */
	var _add_grippy_listeners = function()
	{
		var orig_coords;
		Util.Event.add_event_listener(_grippy, 'mousedown', start_resize);

		// The point of this resize mask is to catch the mouseups with _owner_document,
		// not the iframe's _document, because the coordinates returned when the mouseup is in
		// the iframe's _document, the returned coordinates are buggy in Gecko. If we figure out
		// how to calculate those coordinates accurately--I'm pretty sure it is possible, just
		// tricky--we could remove this resize_mask code.
		var resize_mask = _owner_document.createElement('DIV');
		resize_mask.setAttribute('style', 'position: absolute; top: 0px; left: 0px; height: 20000px; width: 20000px; background: transparent; z-index: 10000;');

		function start_resize(event)
		{
			event = event == null ? window.event : event;
			orig_coords = prev_coords = determine_coords(event);
			Util.Event.add_event_listener(_owner_document, 'mousemove', resize);
			Util.Event.add_event_listener(_owner_document, 'mouseup', stop_resize);
			Util.Event.add_event_listener(_document, 'mousemove', resize);
			Util.Event.add_event_listener(_document, 'mouseup', stop_resize);

			if ( !Util.Browser.IE ) // XXX bad
				_owner_document.documentElement.appendChild(resize_mask);

			return Util.Event.prevent_default(event);
		}
		function resize(event)
		{
			event = event == null ? window.event : event;
			return Util.Event.prevent_default(event);
		}
		function stop_resize(event)
		{
			event = event == null ? window.event : event;

			if ( !Util.Browser.IE ) // XXX bad
				_owner_document.documentElement.removeChild(resize_mask);

			var coords = determine_coords(event);
			//_iframe_wrapper.style.height = _iframe_wrapper.clientHeight + ( coords.y - orig_coords.y ) + 'px';
			_set_height(_get_height() + (coords.y - orig_coords.y));

			Util.Event.remove_event_listener(_owner_document, 'mousemove', resize);
			Util.Event.remove_event_listener(_owner_document, 'mouseup', stop_resize);
			Util.Event.remove_event_listener(_document, 'mousemove', resize);
			Util.Event.remove_event_listener(_document, 'mouseup', stop_resize);
			orig_coords = null;

			return Util.Event.prevent_default(event);
		}
		function determine_coords(event)
		{
			//// Modified from the _show_contextmenu function below.
			//// XXX: Maybe combine this code with that slightly different
			//// code into a fxn in Util.Event, if it's not too difficult.
			//
			// Determine coordinates
			// (Code modified from TinyMCE.)
			var x, y;
			if ( event.pageX != null ) // Gecko
			{
				// If the event is fired from within the iframe,
				// add iframe's position to the reported position.
				var pos;
				var target = Util.Event.get_target(event);
				if ( target.ownerDocument == _document )
					pos = Util.Element.get_position(_iframe);
				else
					pos = { x : 0, y : 0 };

				var body = _owner_document.body;
				/// works, sort of:
				//x = pos.x + (event.clientX - body.scrollLeft);
				//y = pos.y + (event.clientY - body.scrollTop);
				x = pos.x + event.pageX;
				y = pos.y + event.pageY;
			}
			else // IE
			{
				/// works, sort of:
				x = event.screenX + 2;
				y = event.screenY + 2;
				////x = event.clientX + body.scrollLeft.
				////x = event.clientY + body.scrollTop;
			}
			return { x : x, y : y };
		}
	};

	/**
	 * This sets the height of both the possibly editable areas, whether
	 * the textarea or iframe.
	 */
	 /* Commenting out to fix a tiny loki editor
	  * in the luther2010 template - SLS
	  */
	// var _set_height = function(new_height)
	// {
	// 	if ( new_height > 40 )
	// 		_iframe_wrapper.style.height = _textarea.style.height = new_height + 'px';
	// };

	/**
	 * This gets the height of the actually editable area, whether
	 * the textarea or iframe (their heights should always be the same,
	 * but whichever is not currently in the document hierarchy will have
	 * its height reported incorrectly).
	 */
	var _get_height = function()
	{
		return (_is_textarea_active() ? _textarea : _iframe_wrapper).clientHeight;
	};

	/**
	 * This sets the width of both the possibly editable areas, whether
	 * the textarea or iframe.
	 */
	var _set_width = function(new_width)
	{
		if ( new_width > 40 )
		{
			_iframe_wrapper.style.width = _textarea.style.width = new_width + 'px';
			_root.style.width = new_width + 2 + 'px'; // XXX what this number should be changes depending on style sheet..
		}
	};

	/**
	 * Creates the hidden element for Loki, and sets the hidden
	 * element's name, id, and value to those of the textarea element.
	 */
	var _create_hidden = function()
	{
		_hidden = _owner_document.createElement('INPUT');
		_hidden.setAttribute('type', 'hidden');

		if ( _textarea.getAttribute('id') )
			_hidden.setAttribute('id', _textarea.getAttribute('id'));

		if ( _textarea.getAttribute('name') )
			_hidden.setAttribute('name', _textarea.getAttribute('name'));

		if ( _textarea.getAttribute('value') )
			_hidden.setAttribute('value', _textarea.getAttribute('value'));
	};

	/**
	 * Replaces the textarea with the root.
	 */
	var _replace_textarea = function()
	{
		_textarea.parentNode.replaceChild(_root, _textarea);
	};

	/**
	 * Append style sheets to format the main Loki box (not for
	 * dialogs etc.) to owner_document's head.
	 */
	var _append_owner_document_style_sheets = function()
	{
		Util.Document.append_style_sheet(_owner_document, _settings.base_uri + 'css/Loki.css');
	};

	/**
	 * Append style sheets to format the innards of the loki iframe
	 */
	var _append_document_style_sheets = function()
	{
		var add = Util.Document.append_style_sheet.curry(_document);
		
		add((_settings.base_uri || '') + 'css/Loki_Document.css');
		
		(_settings.document_style_sheets || []).each(function (sheet) {
			add(sheet);
		});
	};
	
	/**
	 * Write out blank document. The key here is that we *close*
	 * the document. That way, we don't have to wait for any more
	 * load events, dealing with which is exceedingly annoying due
	 * to cross-browser issues. Cf note in Util.Window.open.
	 */
	var _clear_document = function()
	{
		var html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"\n'+
			'\t"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\n'+
			'<html>\n\t<head xmlns="http://www.w3.org/1999/xhtml">\n'+
			'\t<title>Loki editing document</title>\n</head>\n'+
			'<body></body>\n</html>';
			
		_document.open();
		_document.write(html);
		_document.close();
	};

	/**
	 * Make the document editable. Mozilla doesn't support
	 * contentEditable. Both IE and Mozilla support
	 * designMode. However, in IE if designMode is set on an iframe's
	 * contentDocument, the iframe's ownerDocument will be denied
	 * permission to access it (even if otherwise it *would* have
	 * permission). So for IE we use contentEditable, and for Mozilla
	 * designMode.
	 */
	var _make_document_editable = function()
	{
		if (Util.Browser.IE) {
			_body.contentEditable = true;
			try {
				// If the document isn't really editable, this will throw an
				// error. If the document is editable, this is perfectly
				// harmless.
				_query_command_state('Bold');
			} catch (e) {
				throw new Util.Unsupported_Error('rich text editing');
			}
		} else {
			_document.designMode = 'On';
			try {
				_document.execCommand('undo', false, null);
				_document.execCommand('useCSS', false, true);
			} catch (e) {
				throw new Util.Unsupported_Error('rich text editing');
			}
		}
	};

	/**
	 * Add masseuses. The purpose of a masseuse is to replace elements 
	 * inconvenient to edit with fake elements that are convenient 
	 * to edit, and vice versa. 
	 *
	 * This is the wrong place to add code designed to clean up bad 
	 * HTML; that should be done in UI.Clean.
	 */
	var _add_masseuses = function()
	{
		function add_masseuse(masseuse_class)
		{
			var masseuse = new masseuse_class();
			masseuse.init(self);
			_masseuses.push(masseuse);
		}
		
		enumerate_options('masseuses').each(add_masseuse);
	};

	/**
	 * Run the massage_node_descendants methods of the masseuses 
	 * added in _add_masseuses on _body.
	 */
	var _massage_body = function()
	{
		_massage_node_descendants(_body);
	};

	/**
	 * Run the unmassage_node_descendants methods of the masseuses 
	 * added in _add_masseuses on _body.
	 */
	var _unmassage_body = function()
	{
		_unmassage_node_descendants(_body);
	};

	/**
	 * Run the massage_node_descendants methods of the masseuses 
	 * added in _add_masseuses.
	 */
	var _massage_node_descendants = this.massage_node_descendants = function(node)
	{
		for ( var i = 0; i < _masseuses.length; i++ )
		{
			_masseuses[i].massage_node_descendants(node);
		}
	};

	/**
	 * Run the unmassage_node_descendants methods of the masseuses 
	 * added in _add_masseuses.
	 */
	var _unmassage_node_descendants = this.unmassage_node_descendants = function(node)
	{
		for ( var i = 0; i < _masseuses.length; i++ )
		{
			_masseuses[i].unmassage_node_descendants(node);
		}
	};
	
	function _add_double_click_listeners()
	{
		function add(listener_class) {
			var listener = (new listener_class()).init(self);
			Util.Event.observe(_body, 'dblclick', function(ev) {
				listener.double_click(ev);
			});
		}
		
		enumerate_options('double_click_listeners').each(add);
	}
	
	this.describe_error = function describe_error(ex) {
		// The following probably only works under Mozilla.
		function get_stack_trace(e) {
			if (typeof(e.stack) != "string")
				return null;
			
			var stack = [];
			var raw_parts = e.stack.split("\n");
			var URI = Util.URI;
			var base = URI.build(URI.normalize(self.settings.base_uri));
			
			return raw_parts.map(function parse_stack_trace_element(l) {
				var pos = l.lastIndexOf("@");
				var source = l.substr(0, pos);
				var location = l.substr(pos + 1);
				
				if (source.charAt(0) == "(")
					source = "anonymous_fn" + source;
				
				pos = location.lastIndexOf(":");
				var file = location.substr(0, pos);
				var line = parseInt(location.substr(pos + 1));
				
				if (file.indexOf(base) == 0)
					file = file.substr(base.length);
				
				return {
					source: source,
					file: file,
					line: line
				};
			});
		}
		
		var message;
		if (ex.message) {
			message = ex.message;
		} else {
			try {
				message = ex.toString();
			} catch (e) {
				// Why not just test for toString? Because IE will throw an
				// exception.
				message = '(unable to get exception message)';
			}
		}
		var stack = get_stack_trace(ex);
		
		if (stack) {
			for (var i = 0; i < 4; i++) {
				message += ("\n" + stack[i].source + "\t" +
					stack[i].file + ":" + stack[i].line);
			}
		}
		
		return message;
	}

	/**
	 * Add certain event listeners to the document, e.g. to listen to
	 * key strokes, mouse clicks, and so on.
	 */
	var _add_document_listeners = function()
	{
		// added NF 10/14 for TinyMCE
		var control = new TinyMCEControl();
		control.init(_window, _iframe, self);
		var tinyMCE = new TinyMCE();
		tinyMCE.init(_window, control);
		
		var paste_keyup = false; // a keyup event listener has been registered
		var mod_key = (Util.Browser.Mac ? 'meta' : 'ctrl') + 'Key';
		var mod_key_pressed = null;
		
		function move_past_nbsp(direction) {
			var sel = Util.Selection.get_selection(self.window);
			var range = Util.Range.create_range(sel);
			
			if (!Util.Range.is_collapsed(range))
				return false;
			
			var bounds = Util.Range.get_boundaries(range);
			var node, pos, must_move = false, value;
			
			function is_at_edge() {
				if (pos <= 1)
					return true;
				
				if (node.nodeType == Util.Node.TEXT_NODE) {
					return (pos >= node.nodeValue.length - 1);
				} else {
					return (pos >= node.childNodes.length - 1);
				}
			}
			
			if (bounds.start.container.nodeType == Util.Node.TEXT_NODE) {
				node = bounds.start.container;
				value = node.nodeValue;
				if ((direction < 0 && bounds.start.offset > 0) || (direction > 0 && bounds.end.offset < value.length)) {
					pos = bounds.start.offset;
					if (direction < 0)
						pos--;
					if (node.nodeValue.charCodeAt(pos) != 160 || !is_at_edge())
						return false;
					else
						must_move = true;
				}
			}
			
			if (!must_move) {
				if (bounds.start.container.nodeType == Util.Node.TEXT_NODE) {
					node = bounds.start.container;
					node = (direction < 0) ? node.previousSibling : node.nextSibling;
				} else {
					node = bounds.start.container.childNodes[bounds.start.offset]
				}
				if (!node)
					return false;
					
				while (true) {
					if (!node)
						return false;
					if (node.nodeType != Util.Node.TEXT_NODE)
						return false;
					value = node.nodeValue;
					if (value.length == 0) {
						// try the neighboring node
						node = (direction < 0) ?
							node.previousSibling :
							node.nextSibling;
						continue;
					}
				
					pos = (direction < 0) ? value.length - 1 : 0;
					if (value.charCodeAt(pos) != 160 || !is_at_edge())
						return false;
					break;
				}
			}
			
			if (direction > 0 && node.nodeType == Util.Node.TEXT_NODE) {
				node = Util.Node.next_element_sibling(node.parentNode);
				if (!node)
					return false;
				pos = 0;
			}
			
			range = Util.Document.create_range(self.document);
			try {
				Util.Range.set_start(range, node, pos);
				range.collapse(true /* to start */);
				Util.Selection.select_range(sel, range);
			} catch (e) {
				return false;
			}
			return true;
		}
		
		Util.Event.add_event_listener(_document, 'mouseup', function() {
			move_past_nbsp(-1);
		});
		Util.Event.add_event_listener(_document, 'keyup', function(ev) {
			if (ev.keyCode == 37)
				move_past_nbsp(-1);
		});
		Util.Event.add_event_listener(_document, 'keydown', function(ev) {
			if (ev.keyCode == 39) {
				if (move_past_nbsp(1)) {
					return Util.Event.prevent_default(ev);
				}
			}
		});

		var paragraph_helper = (new UI.Paragraph_Helper).init(self);
		Util.Event.add_event_listener(_document, 'keypress', function(event)
		{
			if (!event)
				event = window.event;
			if (!event.metaKey && !event.ctrlKey)
				paragraph_helper.possibly_paragraphify();
			if (Util.Browser.IE) {
				return Util.Fix_Keys.fix_enter_ie(event, _window, self);
			} else {
				Util.Fix_Keys.fix_delete_and_backspace(event, _window);
				tinyMCE.handleEvent(event);
			}
		});

		Util.Event.add_event_listener(_document, 'contextmenu', function(event) 
		{
			return _show_contextmenu(event || _window.event);
		});
		
		if (Util.Browser.IE) {
			function select_end(sel, range, el) {
				var c, text, length;
				for (c = el.lastChild; c; c = c.previousSibling) {
					if (c.nodeType == Util.Node.ELEMENT_NODE) {
						if (c.nodeName in Util.Element.empty) {
							Util.Range.set_start_after(range, c);
							Util.Range.set_end_after(range, c);
							Util.Selection.select_range(sel, range);
							return true;
						} else if (select_end(sel, range, c)) {
							return true;
						}
					} else if (c.nodeType == Util.Node.TEXT_NODE) {
						length = c.nodeValue.length;
						Util.Range.set_start(range, c, length);
						Util.Range.set_end(range, c, length);
						Util.Selection.select_range(sel, range);
						return true;
					}
				}
				
				text = el.ownerDocument.createTextNode('');
				el.insertBefore(text, el.lastChild);
				
				Util.Range.set_start(range, text, 0);
				Util.Range.set_end(range, text, 0);
				Util.Selection.select_range(sel, range);
				return true;
			}
			
			Util.Event.observe(_document, 'mouseup', function(event) {
				var sel, range;
				
				if (event.srcElement.tagName == 'HTML') {
					self.focus();
					
					sel = Util.Selection.get_selection(_window);
					range = Util.Document.create_range(_document);
					select_end(sel, range, _body);
					
					event.cancelBubble = true;
					event.returnValue = false;
				}
			});
		}
		
		if ( _options.statusbar )
		{
			Util.Event.add_event_listener(_document, 'keyup', function() { _update_statusbar(); });
			Util.Event.add_event_listener(_document, 'click', function() { _update_statusbar(); });
			Util.Event.add_event_listener(_toolbar, 'click', function() { _update_statusbar(); });
		}
		
		function perform_cleanup()
		{
			_unmassage_body();
			UI.Clean.clean(_body, _settings, true);
			_massage_body();
		}
		
		function handle_paste_event(ev)
		{
			if (paste_keyup && ev.type == 'paste') {
				// If the browser is capable of generating actual paste
				// events, then remove the DOMNodeInserted handler.
				
				Util.Event.remove_event_listener(_document, 'keydown',
					key_pressed);
				Util.Event.remove_event_listener(_document, 'keyup',
					key_raised);
				paste_keyup = false;
			}
			
			perform_cleanup.defer();
		}
		
		// Q: Eric, why is there all this code to accomplish the simple task
		//    of figuring out if the user pressed (Command|Ctrl)+V?
		// A: Firefox/Mac does not always give us a keydown event for when
		//    Cmd+V is pressed. We can't simply look for a Cmd+V keyup, as
		//    it's perfectly acceptable to release the command key before
		//    the V key, so the V's keyup event may have metaKey set to
		//    false. Therefore, we look for a Command keydown and store the
		//    time at which it happened. If we get a keyup for V within 2
		//    seconds of this, run a cleanup.
		
		function key_pressed(ev)
		{
			if (!paste_keyup)
				return;
			if (ev[mod_key]) {
				// We might be starting a paste.
				mod_key_pressed = (new Date()).getTime();
			}
		}
		
		function key_raised(ev)
		{
			if (!paste_keyup)
				return;
			if (mod_key_pressed && ev.keyCode == 86 /* V */) {
				if (mod_key_pressed + 2000 >= (new Date()).getTime())
					perform_cleanup();
				mod_key_pressed = null;
			}
		}
		
		Util.Event.observe(_document.body, 'paste', handle_paste_event);
		if (Util.Browser.IE || (Util.Browser.Gecko && /rv:1\.9/.test(navigator.userAgent))) {
			// We know that we have paste events.
			paste_keyup = false;
		} else {
			paste_keyup = true;
			Util.Event.add_event_listener(_document, 'keydown', key_pressed);
			Util.Event.add_event_listener(_document, 'keyup', key_raised);
		}
		
		function submit_handler(ev)
		{
			try {
				self.copy_iframe_to_hidden(); // IE9 will fail here if currently in source view
			}
			catch (e) {
				try {
					if (_is_textarea_active()) self.textarea_to_iframe();
					self.copy_iframe_to_hidden();
				}
				catch (f)
				{
					Util.Event.prevent_default(ev);
					var sent = self.crashed(e);
					alert("An error occurred that prevented your document from " + "being safely submitted." +
                    (sent ? " A report of this error has been sent." : "") +
                    "\n\nTechnical details:\n" +
                    self.describe_error(e));
                    
                    if (typeof(console) == 'object' && 'error' in console)
                    {
                    	console.error('Failed to generate HTML:', e);
                    }
                    throw e;
                }
                return false;
            }
            return true;
        }
		
		// this copies the changes made in the iframe back to the hidden form element
		Util.Event.add_event_listener(_hidden.form, 'submit',
			Util.Event.listener(submit_handler));
	};

	/**
	 * Add listeners to all events which might change the state of the
	 * window (e.g., change where the current selection is in the
	 * document tree). This is useful for updating the toolbar
	 * (updating which buttons appear depressed) and the statusbar.
	 *
	 * The listeners added are stored in _state_change_listeners. We
	 * store them there and then add them all at once at the end of
	 * initialization (when this function should be called) instead of
	 * just adding them when we need them because it is convenient to
	 * add some of the listeners before _document actually points at
	 * some non-null thing.
	 *
	 * I do not like the name "state_change", but couldn't come up
	 * with anything better.
	 */
	var _add_state_change_listeners = function()
	{
		// I commented this out because it makes Loki really slow
		/*
		for ( var i = 0; i < _state_change_listeners.length; i++ )
		{
			Util.Event.add_event_listener(_document, 'keyup', function() { _state_change_listeners[i]; });
			Util.Event.add_event_listener(_document, 'click', function() { _state_change_listeners[i]; });
			Util.Event.add_event_listener(_toolbar, 'click', function() { _state_change_listeners[i]; });
		}
		*/
	};

	/**
	 * Update the statusbar with our current place in the document tree.
	 */
	var _update_statusbar = function()
	{
		var sel = Util.Selection.get_selection(_window);
		var rng = Util.Range.create_range(sel);
		var cur_node = Util.Range.get_common_ancestor(rng);
		var status = '';
		var i = 0;
		
		do
		{
			if ( i > 0 )
				status = ' > ' + status;

			if ( cur_node.nodeType == Util.Node.TEXT_NODE )
				status = '[TEXT]' + status;
			else if ( cur_node.nodeType == Util.Node.ELEMENT_NODE )
				status = cur_node.tagName + status;

			cur_node = cur_node.parentNode;
			i++;
		}
		while ( cur_node != null &&
				( cur_node.nodeType != Util.Node.ELEMENT_NODE ||
				  cur_node.tagName != 'HTML' ) )

		_statusbar.innerHTML = status;
	};

	var _add_keybindings = function()
	{
		function add_keybinding(keybinding_class)
		{
			var keybinding = (new keybinding_class).init(self);
			_keybindings.push(keybinding);
		};

		// return value indicates whether to continue bubbling of event or not
		function fire_keybindings(event)
		{
			var i, keybinding, length = _keybindings.length;
			for (i = 0; i < length; ++i) {
				keybinding = _keybindings[i];
				if (keybinding.test(event)) {
					var should_bubble = keybinding.action();
					return (typeof(should_bubble) == "boolean")
						? should_bubble
						: false; // don't bubble
				}
			}
			
			return true; // bubble
		};

		enumerate_options('keybindings').each(add_keybinding);
		add_keybinding(UI.Delete_Element_Keybinding); // Delete image, anchor, HR, or table when selected
		add_keybinding(UI.Tab_Keybinding); // Tab

		// We need to listen for different key events for IE and Gecko,
		// because their default actions are on different events.
		var firer, event_name;
		if (Util.Browser.IE) {
			event_name = 'keydown';
			firer = function ie_fire_keybindings(event) {
				if (!fire_keybindings(event)) {
					event.cancelBubble = true;
					return Util.Event.prevent_default(event);
				}
				return true;
			};
		} else {
			event_name = 'keypress';
			firer = function gecko_fire_keybindings(event) {
				return (fire_keybindings(event))
					? true
					: Util.Event.prevent_default(event);
			};
		}
		Util.Event.observe(_document, event_name, firer);
	};

	var _init_menugroups = function()
	{
		function add_menugroup(menugroup_class)
		{
			var menugroup = (new menugroup_class).init(self);
			_menugroups.push(menugroup);
		}
		
		enumerate_options('menugroups').each(add_menugroup);
	};

	/**
	 * Shows a context menu.
	 */
	var _show_contextmenu = function(event)
	{
		var menu = (new UI.Menu).init(self);
		var i, menuitems, added = false;

		// Get appropriate menuitems
		for (i = 0; i < _menugroups.length; i++) {
			try {
				menuitems = _menugroups[i].get_contextual_menuitems();
			} catch (e) {
				if (typeof(console) == 'object' && 'warn' in console) {
					console.warn('Failed to add menugroup', i, '.', e);
				}
			}
			
			if (menuitems && menuitems.length > 0) {
				if (!added)
					added = true;
				else
					menu.add_menuitem((new UI.Separator_Menuitem).init());

				menu.add_menuitems(menuitems);
			}
		}
		
		menu.display(event);

		Util.Event.prevent_default(event);
		return false; // IE
	};

	/**
	 * Runs execCommand on _document. The motivation for this wrapper
	 * is to avoid issues when execCommand is used in event listeners.
	 * (If _document isn't yet initialized when "function() {
	 * _document.execCommand(xxx) }" is added as an event listener, an
	 * error results, because (in addition to its arguments) the
	 * listener when executed has access only to those variables which
	 * it had access to when it was defined.
	 *
	 * Also consult <a href="http://www.mozilla.org/editor/midas-spec.html">Mozilla's</a>
	 * and <a href="http://msdn.microsoft.com/workshop/author/dhtml/reference/methods/execcommand.asp">IE's</a>
	 * documentation.
	 *
	 * @param	command		the command to execute
	 * @param	iface		boolean indicating whether to use an interface. Not
	 *                      supported by Mozilla, so always provide false.
	 * @param	value		the value to pass the command
	 */
	var _exec_command = function(command, iface, value)
	{
		_window.focus();
		_document.execCommand(command, iface, value);
		_window.focus();
	};

	/**
	 * Returns the value of _document.queryCommandValue (see the
	 * links on execCommands doc for more info). But first modifies
	 * the return value so that IE's is the same as Mozilla's. (On
	 * this see <a href="http://www.mozilla.org/editor/ie2midas.html">here</a>, 
	 * bullet 8.)
	 *
	 * See also on _exec_command.
	 *
	 * @param	command		the command whose value to query (this only works for 
	 *                      some of the commands)
	 * @return				the (possibly-modified) return value of queryCommandValue
	 */
	var _query_command_value = function(command)
	{
		// Not sure if the window.focus is actually helpful here ...
		// and it makes annoying things happen like dialogs popping up
		// behind the editor's containing window.
		//_window.focus();
		var value = _document.queryCommandValue(command);
		
		if ( command == 'FormatBlock' )
		{
			var mappings = 
			{
				// IE : Mozilla
				'Normal' : 'p',
				'Formatted' : 'pre',
				'Heading 1' : 'h1',
				'Heading 2' : 'h2',
				'Heading 3' : 'h3',
				'Heading 4' : 'h4',
				'Heading 5' : 'h5',
				'Heading 6' : 'h6',
				'Preformatted' : 'pre',
				'Address' : 'address'
			};
			
			if (value === false) {
				// WebKit doesn't appear to implement querying FormatBlock,
				// so we'll do it ourselves.
				var ancestry = get_selection_ancestry();
				value = ancestry.find(function(value) {
					var key;
					for (key in mappings) {
						if (mappings[key] == value)
							 return true;
					}
				});
			} else if (value in mappings) {
				value = mappings[value];
			}
		}
		
		return value;
	}
	
	function get_selection_ancestry() {
		var sel = Util.Selection.get_selection(self.window);
		var range = Util.Range.create_range(sel);
		var ancestor = Util.Range.get_common_ancestor(range);
		
		var ancestry = [];
		var node;
		for (node = ancestor; node; node = node.parentNode) {
			if (node.nodeType == Util.Node.ELEMENT_NODE)
				ancestry.push(node.nodeName.toLowerCase());
		}
		
		return ancestry;
	}

	/**
	 * See on _exec_command.
	 */
	var _query_command_state = function(command)
	{
		// Not sure if the window.focus is actually helpful here ...
		// and it makes annoying things happen like dialogs popping up
		// behind the editor's containing window.
		//_window.focus();
		return _document.queryCommandState(command);
	}

	/**
	 * Formats a block as specified if it's not so, and if it is so,
	 * formats it as a normal paragraph.
	 *
	 * @param   tag     the tag name corresponding to how you want
     *                  the block to be formatted. See <code>mappings</code>
     *                  variable inside the function.
     *
	 */
	this.toggle_block = function(tag)
	{
		var tag_string = (_query_command_value('FormatBlock') != tag)
			? '<' + tag + '>'
			: '<p>';
		
		_exec_command('FormatBlock', false, tag_string);
		_window.focus();
	};

	/**
	 * Formats a block as a list of the given type if it's not so, and
	 * if it is so, formats it as a normal paragraph. This is
	 * necessary because in Mozilla, if a block is already formatted
	 * as a list, the Insert[Un]orderedList commands simply remove the
	 * block's block-level formatting, rather than changing it to a
	 * paragraph.
	 *
     * @param   tag     the tag name corresponding to how you want
     *                  the block to be formatted. See mappings variable 
     *                  inside the function
     */
	this.toggle_list = function(tag)
	{
		var command = tag == 'ol' ? 'InsertOrderedList' : 'InsertUnorderedList';

		if ( _query_command_state(command) )
		{
			_exec_command(command); // turn off the list
			this.toggle_block('p');
		}
		else
		{
			_exec_command(command); // turn on the list
		}
	};
};
UI.Loki.prototype.version = "2.0.4-reason";

UI.Loki.Options = new Util.Chooser();
UI.Loki.Options._add_bundled = function add_bundled_loki_options() {
	this.add('bold', {
		buttons: [UI.Bold_Button],
		masseuses: [UI.Bold_Masseuse],
		keybindings: [UI.Bold_Keybinding]
	});
	this.add('italic', {
		buttons: [UI.Italic_Button],
		masseuses: [UI.Italic_Masseuse],
		keybindings: [UI.Italic_Keybinding]
	});
	this.add('underline', {
		buttons: [UI.Underline_Button],
		keybindings: [UI.Underline_Keybinding]
	});
	this.add('headings', {
		buttons: [UI.Headline_Button],
		menugroups: [UI.Headline_Menugroup],
		keybindings: []
	});
	this.add('pre', {
		buttons: [UI.Pre_Button]
	});
	this.add('br', {
		buttons: [UI.BR_Button]
	});
	this.add('hr', {
		buttons: [UI.HR_Button],
		masseuses: [UI.HR_Masseuse]
	});
	this.add('clipboard', {
		buttons: [UI.Cut_Button, UI.Copy_Button, UI.Paste_Button],
		menugroups: [UI.Clipboard_Menugroup],
		keybindings: [UI.Cut_Keybinding, UI.Copy_Keybinding, UI.Paste_Keybinding]
	});
	this.add('highlight', {
		buttons: [UI.Highlight_Button]
	});
	this.add('align', {
		// buttons: [UI.Left_Align_Button, UI.Center_Align_Button, UI.Right_Align_Button],
		menugroups: [UI.Align_Menugroup],
		keybindings: [UI.Left_Align_Keybinding, UI.Center_Align_Keybinding, UI.Right_Align_Keybinding]
	});
	this.add('blockquotes', {
		buttons: [UI.Blockquote_Button]
	});
	this.add('lists', {
		// masseuses: [UI.UL_OL_Masseuse],
		buttons: [UI.OL_Button, UI.UL_Button, UI.Indent_Button, UI.Outdent_Button]
	});
	this.add('find', {
		buttons: [UI.Find_Button],
		keybindings: [UI.Find_Keybinding]
	});
	this.add('tables', {
		buttons: [UI.Table_Button],
		masseuses: [UI.Table_Masseuse],
		menugroups: [UI.Table_Menugroup]
	});
	this.add('images', {
		buttons: [UI.Image_Button],
		masseuses: [UI.Image_Masseuse],
		double_click_listeners: [UI.Image_Double_Click]
	});
	this.add('links', {
		buttons: [UI.Page_Link_Button],
		menugroups: [UI.Link_Menugroup],
		keybindings: [UI.Page_Link_Keybinding],
		double_click_listeners: [UI.Link_Double_Click]
	});
	this.add('anchors', {
		buttons: [UI.Anchor_Button],
		masseuses: [UI.Anchor_Masseuse],
		menugroups: [UI.Anchor_Menugroup],
		double_click_listeners: [UI.Anchor_Double_Click]
	});
	this.add('cleanup', {
		buttons: [UI.Clean_Button]
	});
	this.add('source', {
		buttons: [UI.Source_Button]
	});
	this.add('debug', {
		buttons: [UI.Raw_Source_Button]
	});
	//this.add('statusbar', true);
	
	// Some of these aliases are for installer sanity, while others are for
	// Loki 1 compatibility.
	this.alias('bold', 'strong');
	this.alias('italic', 'em');
	this.alias('tables', 'table');
	this.alias('images', 'image');
	this.alias('links', 'link');
	this.alias('lists', 'list');
	this.alias('blockquotes', 'blockquote');
	this.alias('anchors', 'anchor');
	this.alias('headings', 'heading');
	this.alias('headings', 'headlines');
	this.alias('headings', 'headline');
	this.alias('br', 'linebreaks');
	this.alias('br', 'linebreak');
	this.alias('find', 'findtext');
	
	this.put_set('default', ['strong', 'em', 'headline', 'br', 'hr',
		'highlight', 'align', 'blockquotes', 'lists', 'find', 'images',
		'links', 'cleanup']);
	this.put_set('power', ['strong', 'em', 'headline', 'br', 'hr', 'pre',
		'clipboard', 'highlight', 'align', 'blockquotes', 'lists',
		'find', 'tables', 'images', 'links', 'anchors', 'cleanup', 'source']);
	this.put_set('developer', ['power', 'debug']);
};

var Loki = {
	/**
	 * Converts the given textarea to an instance of the Loki WYSIWYG editor.
	 * @param {HTMLTextAreaElement} area a TEXTAREA element or the ID of one
	 * @param {object} [settings] Loki settings
	 * @param {function} [callback] a function that will be called when the
	 *        conversion is finished
	 * @see UI.Loki#init
	 * @see http://code.google.com/p/loki-editor/wiki/Settings
	 * @returns {void}
	 */
	convert_textarea: function loki_convert_textarea(area, settings,
		callback)
	{
		Loki.convert_textareas([area], settings || {}, callback || null);
	},
	
	/**
	 * Converts the given textareas to instances of the Loki WYSIWYG editor.
	 * @param {HTMLTextAreaElement[]} areas an array of TEXTAREA elements to
	 * convert, or the ID's of the elements
	 * @param {object} [settings] Loki settings
	 * @param {function} [callback] a function that will be called as the
	 *        conversions are finished
	 * @see UI.Loki#init
	 * @see http://code.google.com/p/loki-editor/wiki/Settings
	 * @returns {void}
	 */
	convert_textareas: function loki_convert_textareas(areas, settings,
		callback)
	{	
		var area;
		var instance;
		
		for (var i = 0; i < areas.length; i++) {
			if (typeof(areas[i]) == 'string') {
				area = document.getElementById(areas[i]);
				if (!area) {
					if (Loki._loaded) {
						throw new Error('No element with the ID of "' +
							areas[i] + '" exists in the document.');
					}
					Loki._pend(areas[i], settings || {}, callback || null);
					continue;
				}
			} else {
				area = areas[i];
			}
			
			if (!Util.Node.is_tag(area, "TEXTAREA")) {
				throw new TypeError("Unable to convert a non-textarea to a " +
					"Loki instance.");
			}
			
			instance = (new UI.Loki).init(area, settings || {});
			
			if (callback) {
				callback(instance, area);
			}
		}
	},
	
	/**
	 * Converts all of the textareas in the document which have the specified
	 * class(es).
	 * @param {string} classes	one or more class names
	 * @param {object} [settings] Loki settings
	 * @param {function} [callback] a function that will be called as the
	 *        conversions are finished
	 * @returns {void}
	 */
	convert_textareas_by_class: function loki_convert_classed_textareas(classes,
		settings, callback)
	{
		function get_textareas()
		{
			return Util.Element.find_by_class(document, classes);
		}
		
		if (this._loaded) {
			Loki.convert_textareas(get_textareas(), settings, callback);
		} else {
			Loki._pend(get_textareas, settings || {}, callback || null);
		}
	},
	
	/**
	 * Converts all of the textareas on the document into Loki instances.
	 * @param {object} [settings] Loki settings
	 * @param {function} [callback] a function that will be called as the
	 *        conversions are finished
	 * @see UI.Loki#init
	 * @see http://code.google.com/p/loki-editor/wiki/Settings
	 * @returns {void}
	 */
	convert_all_textareas: function loki_convert_all_textareas(settings,
		callback)
	{
		if (this._loaded) {
			Loki.convert_textareas(document.getElementsByTagName("TEXTAREA"),
				settings || {}, callback);
		} else {
			Loki._pend(null, settings || {}, callback || null);
		}
		
	},
	
	/**
	 * Returns true if the DOM is ready.
	 * @returns {boolean}
	 */
	is_document_ready: function is_document_ready()
	{
		return this._loaded;
	},
	
	/**
	 * The Loki version.
	 * @type string
	 */
	version: "2.0.4-reason",
	
	/** @private */
	_pending: [],
	/** @private */
	_loaded: false,
	
	/** @private */
	_pend: function loki_pend_textarea(area, settings, callback) {
		this._pending.push([area, settings, callback]);
	},
	
	/** @private */
	_finish_conversions: function loki_finish_conversions() {
		var a;
		
		if (this._loaded)
			return false;
		this._loaded = true;
		
		while (a = this._pending.pop()) {
			if (a[0] == null) {
				Loki.convert_all_textareas(a[1], a[2]);
				return true;
			} else if (typeof(a[0]) == 'function') {
				Loki.convert_textareas(a[0](), a[1], a[2]);
			} else {
				Loki.convert_textarea(a[0], a[1], a[2]);
			}
		}
		
		return true;
	}
};

(function loki_wait_for_load() {
	var done = Loki._finish_conversions.bind(Loki);
	Util.Event.observe(document, 'DOMContentLoaded', done);
	Util.Event.observe(window, 'load', done);
})();
