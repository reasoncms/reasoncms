Util.Fix_Keys = function()
{
};

tinyMCE = {};
tinyMCE.getParentBlockElement = function()
{

};

tinyMCE.isBlockElementNode = function()
{

};

Util.Fix_Keys.TinyMCE_insertPara = function(e, win)
{
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
	var doc = win.document;
	var sel = Util.Selection.get_selection(win);
	var rng = sel.getRangeAt(0);
	var body = doc.body;
	var rootElm = doc.documentElement;
	var blockName = "P";
	/*
	//var doc = this.getDoc();
	//var sel = this.getSel();
	//var win = this.contentWindow;
	//var rng = sel.getRangeAt(0);
	//var body = doc.body;
	var rootElm = doc.documentElement;
	var self = this;
	var blockName = "P";
	*/

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

	// Use current block name
	if (startBlock != null) {
		blockName = startBlock.nodeName;

		// Use P instead
		if (blockName == "TD" || blockName == "TABLE" || (blockName == "DIV" && new RegExp('left|right', 'gi').test(startBlock.style.cssFloat)))
			blockName = "P";
	}

	// Within a list item (use normal behavior)
	if ((startBlock != null && startBlock.nodeName == "LI") || (endBlock != null && endBlock.nodeName == "LI"))
		return false;

	// Within a table create new paragraphs
	if ((startBlock != null && startBlock.nodeName == "TABLE") || (endBlock != null && endBlock.nodeName == "TABLE"))
		startBlock = endBlock = null;

	// Setup new paragraphs
	var paraBefore = (startBlock != null && startBlock.nodeName.toUpperCase() == blockName) ? startBlock.cloneNode(false) : doc.createElement(blockName);
	var paraAfter = (endBlock != null && endBlock.nodeName.toUpperCase() == blockName) ? endBlock.cloneNode(false) : doc.createElement(blockName);

	// Setup chop nodes
	var startChop = startNode;
	var endChop = endNode;

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

	/*
	// added by NF:
	if ( blockquote_helper.is_blockquoted() || highlight_helper.is_blockquoted() )
	{
		if ( isEmpty(startBlock) )
		{
			
		}
	}
	*/

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
			if (endChop.parentNode.nodeName == blockName)
				endChop = endChop.parentNode;

			rng.setEndAfter(endChop);
			if (endChop.nodeName != "#text" && endChop.nodeName != "BODY")
				rngBefore.setEndAfter(endChop);

			var contents = rng.cloneContents();
			if (contents.firstChild && (contents.firstChild.nodeName == blockName || contents.firstChild.nodeName == "BODY")) {
				var nodes = contents.firstChild.childNodes;
				for (var i=0; i<nodes.length; i++) {
					if (nodes[i].nodeName != "BODY")
						paraAfter.appendChild(nodes[i]);
				}
			} else
				paraAfter.appendChild(contents);

			// Check if it's a empty paragraph
			if (isEmpty(paraBefore))
				paraBefore.innerHTML = "&nbsp;";

			// Check if it's a empty paragraph
			if (isEmpty(paraAfter))
				paraAfter.innerHTML = "&nbsp;";

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
			body.innerHTML = "<" + blockName + ">&nbsp;</" + blockName + "><" + blockName + ">&nbsp;</" + blockName + ">";
			paraAfter = body.childNodes[1];
		}

		this.selectNode(paraAfter, true, true);

		return true;
	}

	// Place first part within new paragraph
	if (startChop.nodeName == blockName)
		rngBefore.setStart(startChop, 0);
	else
		rngBefore.setStartBefore(startChop);
	rngBefore.setEnd(startNode, startOffset);
	paraBefore.appendChild(rngBefore.cloneContents());

	// Place secound part within new paragraph
	rngAfter.setEndAfter(endChop);
	rngAfter.setStart(endNode, endOffset);
	var contents = rngAfter.cloneContents();
	if (contents.firstChild && contents.firstChild.nodeName == blockName) {
		var nodes = contents.firstChild.childNodes;
		for (var i=0; i<nodes.length; i++) {
			if (nodes[i].nodeName.toLowerCase() != "body")
				paraAfter.appendChild(nodes[i]);
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

	if (!startChop.previousSibling && startChop.parentNode.nodeName.toUpperCase() == blockName) {
		rng.setStartBefore(startChop.parentNode);
	} else {
		if (rngBefore.startContainer.nodeName.toUpperCase() == blockName && rngBefore.startOffset == 0)
			rng.setStartBefore(rngBefore.startContainer);
		else
			rng.setStart(rngBefore.startContainer, rngBefore.startOffset);
	}

	if (!endChop.nextSibling && endChop.parentNode.nodeName.toUpperCase() == blockName)
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

Util.Fix_Keys.TinyMCE_cancelEvent = function(e) {
	if (tinyMCE.isMSIE) {
		e.returnValue = false;
		e.cancelBubble = true;
	} else
		e.preventDefault();
};


Util.Fix_Keys.tinymce_fix_keyupdown = function(e, win)
{
	var selectedInstance = win; // ?

	tinyMCE = {};
	tinyMCEinst = {};
	tinyMCEinst.insertPara = function(e, win) { return Util.Fix_Keys.TinyMCE_insertPara(e, win); };
	tinyMCE.cancelEvent = function(e) { Util.Fix_Keys.TinyMCE_cancelEvent; };
	tinyMCE.isGecko = true;
	tinyMCEinst.handleBackspace = function(e_type) { return true; };

	switch (e.type) {
		case "keypress":
			// Insert P element
			if (tinyMCE.isGecko && e.keyCode == 13 && !e.shiftKey) {
				// Insert P element instead of BR
				//if (tinyMCE.selectedInstance._insertPara(e)) {
				if (tinyMCEinst.insertPara(e, win))
				{
					tinyMCE.cancelEvent(e);
					return false;
				}
			}

			/*

			// Handle backspace
			if (tinyMCE.isGecko && tinyMCE.settings['force_p_newlines'] && (e.keyCode == 8 || e.keyCode == 46) && !e.shiftKey) {
				// Insert P element instead of BR
				if (tinyMCEinst.handleBackSpace(e.type)) {
					e.preventDefault();
					return false;
				}
			}

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
};

/**
 * Event handler which fixes the behavior of enter. Works in
 * conjunction with fix_enter_keyup.
 *
 * @param	event	the event object
 * @param	win		the editor window
 */
Util.Fix_Keys.fix_enter_keydown = function(event, win)
{
	// does nothing at present
};

/**
 * Event handler which fixes the behavior of enter. Works in
 * conjunction with fix_enter_keydown.
 *
 * @param	event	the event object
 * @param	win		the editor window
 */
Util.Fix_Keys.fix_enter_keyup = function(event, win)
{
	var sel = Util.Selection.get_selection(win);
	var rng = Util.Range.create_range(sel);
	var ancestor_element = Util.Range.get_nearest_bl_ancestor_element(rng);
	var doc = ancestor_element.ownerDocument;

	//
	// The condition matches only keyCodes for enter or characters,
	// not things like arrow keys, because things like arrow keys
	// allow movement too quickly among blocks for the range to change
	// or this script to keep up or something, with the result that
	// some blocks are incorrectly formatted as if they were other
	// blocks.
	//
	// For more information about keyCodes and the DOM_VK constants, see
	// <http://www.mozilla.org/docs/dom/domref/examples.html>.
	//
	if ( ( event.keyCode == event.DOM_VK_RETURN || event.keyCode == event.DOM_VK_ENTER ||
		   (event.keyCode > event.DOM_VK_0 && event.keyCode < event.DOM_VK_DIVIDE) ||
		   (event.keyCode > event.DOM_VK_COMMA && event.keyCode < event.DOM_VK_QUOTE) )
		 &&
		 !event.ctrlKey
		 &&
		 ancestor_element.tagName != 'PRE' )
	{
		if ( ancestor_element != null )
		{
			// Can contain p
			if ( Util.BLE_Rules.ccp.regexp.test(ancestor_element.tagName) )
			{
				doc.execCommand('FormatBlock', false, 'p');
			}
			// Cannot contain p
			else if ( Util.BLE_Rules.cncp.regexp.test(ancestor_element.tagName) )
			{
				doc.execCommand('FormatBlock', false, ancestor_element.tagName);

//				// Copy all attributes of the ancestor_element to the new parent element
//				var sel = this._get_selection();
//				var range = this._create_range(sel);
//				var new_element = this._get_nearest_ancestor_element(range, this._is_block_level_element); // this works because it's just after we've formatted the block
//				for ( var i = 0; i < ancestor_element.attributes.length; i++ )
//				{
//					var attr = ancestor_element.attributes.item(i);
//					alert(attr.name + ': ' + attr.value);
//					new_element.setAttributeNode(attr);
//				}
			}
			// Can contain only more than one p
			else if ( Util.BLE_Rules.ccomtop.regexp.test(ancestor_element.tagName) )
			{
				// This can remain a stub right now, since it only deals with tables, which we don't actually handle anyway
			}
			// Can contain double brs but not p
			else if ( Util.BLE_Rules.ccdbbnp.regexp.test(ancestor_element.tagName) )
			{
			}
		}
	}
};

Util.Fix_Keys.fix_enter = function(e, win)
{
	var sel = Util.Selection.get_selection(win);
	var rng = Util.Range.create_range(sel);

	if (!( !e.shiftKey && e.keyCode == 13 ))
		return;

	// Delete contents of sel
	rng.deleteContents();

	// If block is proper
	var block = Util.Range.get_nearest_bl_ancestor_element(rng);
	if ( Util.BLE_Rules.cncp.regexp.test(block.tagName) )
	{
		// Clone block shallowly
		var clone = block.cloneNode(false);

		// Insert clone after block
		Util.Node.insert_after(clone, block);
		//block.parentNode.insertBefore(clone, block);

		// Create rng from sel start to end of block
		var rng = Util.Range.create_range(sel);
		//rng.selectNodeContents(block);
		rng.setEndAfter(block.lastChild);
		//rng.setStart(sel.anchorNode, sel.anchorOffset);

		// rng.extractContents into frag
		var frag = rng.extractContents();

		// append frag to clone
		clone.appendChild(frag);

		if ( clone.lastChild == null ||
			 !( clone.lastChild.nodeType == Util.Node.ELEMENT_NODE && clone.lastChild.tagName == 'BR' ) )
			clone.appendChild( clone.ownerDocument.createElement('BR') );

		if ( block.lastChild == null ||
			 !( block.lastChild.nodeType == Util.Node.ELEMENT_NODE && block.lastChild.tagName == 'BR' ) )
			block.appendChild( block.ownerDocument.createElement('BR') );

		clone.normalize();
		block.normalize();

		rng.selectNodeContents(clone);
		Util.Selection.select_range(sel, rng);
		sel.collapseToStart();

		e.preventDefault();
	}
	else if ( Util.BLE_Rules.ccp.regexp.test(block.tagName) )
	{
		// Create paragraphs
		var p1 = block.ownerDocument.createElement('P');
		var p2 = block.ownerDocument.createElement('P');

		// Create rng from start of block to sel start (for p1)
		var rng = Util.Range.create_range(sel);
		//rng.selectNodeContents(block);
		rng.setStartBefore(block.firstChild);
		//rng.setEnd(sel.anchorNode, sel.anchorOffset);

		// rng.extractContents into frag
		var f1 = rng.extractContents();
		// append frag to p_one
		p1.appendChild(f1);

		// Create rng from sel start to end of block (for p2)
		var rng = Util.Range.create_range(sel);
		//rng.selectNodeContents(block);
		//rng.setStart(sel.anchorNode, sel.anchorOffset);
		rng.setEndAfter(block.lastChild);
		// rng.extractContents into frag
		var f2 = rng.extractContents();
		// append frag to p_two
		p2.appendChild(f2);

		// append p_one and p_two to block
		block.appendChild(p1);
		block.appendChild(p2);

		e.preventDefault();
	}
};

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
	};

	function is_not_at_beg_of_body(rng)
	{
		var start_container = rng.startContainer;
		var start_offset = rng.startOffset;
		var rng2 = Util.Range.create_range(sel);
		rng2.selectNodeContents(start_container.ownerDocument.getElementsByTagName('BODY')[0]);
		rng2.setEnd(start_container, start_offset);
		var ret = rng2.toString().length > 0;// != '';
		return ret;
	};

	function move_selection_to_end(node, sel)
	{
		var rightmost = Util.Node.get_rightmost_descendent(node);
		Util.Selection.select_node(sel, rightmost);
		Util.Selection.collapse(sel, false); // to end
	};

	function remove_trailing_br(node)
	{
		if ( node.lastChild != null && 
			 node.lastChild.nodeType == Util.Node.ELEMENT_NODE && 
			 node.lastChild.tagName == 'BR' )
		{
			node.removeChild(node.lastChild);
		}
	};

	function merge_blocks(one, two)
	{
		while ( two.firstChild != null )
			one.appendChild(two.firstChild);
		two.parentNode.removeChild(two);

		//one.normalize(); // this messes up cursor position
		return;
	};
	
	function is_container(node)
	{
		return (node && node.getAttribute('loki:container'));
	}

	function do_merge(one, two, sel)
	{
		/*
		 * If the node is a special Loki container (e.g. for a horizontal rule),
		 * we shouldn't merge with it. Instead, delete the container (and the)
		 * page element it contains.
		 */
		function handle_containers(node)
		{
			if (is_container(node)) {
				node.parentNode.removeChild(node);
				return true;
			}
			
			return false;
		}
		
		var tags_regexp = new RegExp('BODY|HEAD|TABLE|TBODY|THEAD|TR|TH|TD', '');
		if ( one == null || one.nodeName.match(tags_regexp) ||
			 two == null || two.nodeName.match(tags_regexp) )
		{
			return;
		}
		else if (handle_containers(one) || handle_containers(two))
		{
			return;
		}
		else
		{
			remove_trailing_br(one);
			move_selection_to_end(one, sel);
			merge_blocks(one, two);
			e.preventDefault();
		}
	};
	
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
	var cur_block = Util.Range.get_nearest_bl_ancestor_element(rng);
	
	function get_neighbor_element(direction)
	{
		if (rng.startContainer != rng.endContainer || rng.startOffset != rng.endOffset)
			return null;
		
		if (direction == Util.Node.NEXT && rng.endContainer.childNodes[rng.endOffset])
			return rng.endContainer.childNodes[rng.endOffset];
		else if (direction == Util.Node.PREVIOUS && rng.startContainer.childNodes[rng.startOffset - 1])
			return rng.startContainer.childNodes[rng.startOffset - 1];
		else
			return null;
	}

	if ( rng.collapsed == true && !e.shiftKey )
	{
		var neighbor = null;
		
		if (e.keyCode == e.DOM_VK_DELETE) {
			if (Util.Range.is_at_end_of_block(rng, cur_block)) {
				do_merge(cur_block, Util.Node.next_element_sibling(cur_block), sel);
			} else if (Util.Range.is_at_end_of_text(rng) && is_container(rng.endContainer.nextSibling)) {
				remove_container(rng.endContainer.nextSibling);
			} else if (neighbor = get_neighbor_element(Util.Node.NEXT)) {
				remove_if_container(neighbor);
			}
		} else if (e.keyCode == e.DOM_VK_BACK_SPACE) {
			// both the following two are necessary to avoid
			// merge on B's here: <p>s<b>|a</b>h</p>
			if (Util.Range.is_at_beg_of_block(rng, cur_block) && rng.isPointInRange(rng.startContainer, 0)) {
				do_merge(Util.Node.previous_element_sibling(cur_block), cur_block, sel);
			} else if (Util.Range.is_at_beg_of_text(rng) && is_container(rng.startContainer.previousSibling)) {
				remove_container(rng.endContainer.nextSibling);
			} else if (neighbor = get_neighbor_element(Util.Node.PREVIOUS)) {
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
