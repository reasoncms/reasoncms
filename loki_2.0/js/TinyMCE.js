/**
 * $RCSfile: tiny_mce_src.js,v $
 * $Revision: 1.233 $
 * $Date: 2005/08/26 15:20:32 $
 *
 * @author Moxiecode. Extracts made by NF starting 2005/10/14.
 * @copyright Copyright © 2004, Moxiecode Systems AB, All rights reserved.
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
	var pos = new Object();

	pos.absLeft = pos.absTop = 0;

	var parentNode = node;
	while (parentNode) {
		pos.absLeft += parentNode.offsetLeft;
		pos.absTop += parentNode.offsetTop;

		parentNode = parentNode.offsetParent;
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

		this.selectNode(paraAfter, true, true);

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

	this.selectNode(paraAfter, true, true);

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
				this.selectNode(lastTextNode, true, false, false);

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

TinyMCEControl.prototype.selectNode = function(node, collapse, select_text_node, to_start) {
	if (!node)
		return;

	if (typeof(collapse) == "undefined")
		collapse = true;

	if (typeof(select_text_node) == "undefined")
		select_text_node = false;

	if (typeof(to_start) == "undefined")
		to_start = true;

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
	if (!tinyMCE.settings['auto_resize'] && !(node.absTop > scrollY && node.absTop < (scrollY - 25 + height)))
		this.contentWindow.scrollTo(pos.absLeft, pos.absTop - height + 25);
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


