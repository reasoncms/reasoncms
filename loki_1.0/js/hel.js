//	this javascript file must be parsed as php
<?
	include ( 'paths.php' );
?>

function hel(editor_id, site_id, server_name, p_rules)
{
	this._editor_id = editor_id;
	this._editor_obj = editor_id + '_obj'; // This is the var name of the current instantiation, for passing to modal windows via php
	this._site_id = site_id;
	this._editor_window = document.getElementById(this._editor_id).contentWindow;
	this._editor = this._editor_window.document;
	this.temp_modal_args = Object(); //container for temporary arguments to modal windows
	this._unsupported = false;
 	this._asset_location = '<? echo LOKI_HTTP_PATH; ?>';
// 	this._asset_location = '/fillmorn/hel-loki/';
	this._server_name = server_name;
	this._p_rules = p_rules;

	this._copy_from_source_into_hel();
	this._start();
}

hel.prototype._start = function()
{
	var unsupported_container = document.getElementById(this._editor_id + '_unsupported_container');
	var supported_container = document.getElementById(this._editor_id + '_supported_container');

	try {
		supported_container.style.display = 'block'; // otherwise, setting designMode may throw an exception
		this._editor.designMode = 'on';

		try {
			// The 'useCSS' command may throw exceptions even when designMode is supported, 
			// e.g. on Gecko/20030312 Mozilla 1.3 OS X	
			//this._editor.execCommand('useCSS', false, null);
			this._editor.execCommand('useCSS', false, true); // 10/14/2003 BK - I think final arguement needs to be true
		} catch (e) {}
		
		try { // so as to avoid exceptions if we call this function more than once
			unsupported_container.parentNode.removeChild(unsupported_container);
		} catch (e) {}
	}
	catch (e) {
		this._unsupported = true;
		//alert("This editor is not supported on your level of Mozilla.");

		supported_container.parentNode.removeChild(supported_container);
	}
};

hel.prototype._is_block_level_element = function(node, ref_to_this)
{
// 	var all_block_level_elements = ref_to_this._p_rules.can_contain_p + '|' + ref_to_this._p_rules.cannot_contain_p + '|' + ref_to_this._p_rules.can_contain_only_more_than_one_p + '|' + ref_to_this._p_rules.can_contain_double_brs_but_not_p;
	var able_regexp = new RegExp('(' + ref_to_this._p_rules.all_block_level_elements + ')', 'gi');
	return able_regexp.test(node.tagName);
};

hel.prototype.do_on_keydown = function(event)
{
	var sel = this._get_selection();
	var range = this._create_range(sel);

// 	window.status = 'onkeydown, event.keyCode == ' + event.keyCode;

	//
	// The condition matches only keyCodes for enter or characters,
	// not things like arrow keys, because things like arrow keys
	// allow movement too quickly among blocks for the range to change
	// or this script to keep up or something, with the result that
	// blocks are formatted incorrectly similarly to other blocks.
	//
	// For more information about keyCodes and the DOM_VK constants, see
	// <http://www.mozilla.org/docs/dom/domref/examples.html>.
	//
	var ancestor_element = this._get_nearest_ancestor_element(range, this._is_block_level_element);
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
// 			window.status = 'onkeydown, ancestor_element != null, and ancestor_element.tagName == ' + ancestor_element.tagName;

			var ccp_regexp = new RegExp('(' + this._p_rules.can_contain_p + ')', 'gi');
			var cncp_regexp = new RegExp('(' + this._p_rules.cannot_contain_p + ')', 'gi');
			var ccomtop_regexp = new RegExp('(' + this._p_rules.can_contain_only_more_than_one_p + ')', 'gi');
			var ccdbbnp_regexp = new RegExp('(' + this._p_rules.can_contain_double_brs_but_not_p + ')', 'gi');

			// Make sure that were never merely inside the body--that
			// we're always at least inside a paragraph tag
			// ... perhaps this should be extended to div etc (and all
			// other can_contain_p's)? ---answer: yes
// 			if ( ancestor_element.tagName == 'BODY' )
			if ( ccp_regexp.test(ancestor_element.tagName) )
			{
				this._editor.execCommand('FormatBlock', false, 'p');
			}
		}
	}
};

hel.prototype.do_on_keyup = function(event)
{
	var sel = this._get_selection();
	var range = this._create_range(sel);

	var ancestor_element = this._get_nearest_ancestor_element(range, this._is_block_level_element);
	if ( ( event.keyCode == event.DOM_VK_RETURN || event.keyCode == event.DOM_VK_ENTER ) && !event.ctrlKey
		 && ancestor_element.tagName != 'PRE' )
	{
		if ( ancestor_element != null )
		{
			var ccp_regexp = new RegExp('(' + this._p_rules.can_contain_p + ')', 'gi');
			var cncp_regexp = new RegExp('(' + this._p_rules.cannot_contain_p + ')', 'gi');
			var ccomtop_regexp = new RegExp('(' + this._p_rules.can_contain_only_more_than_one_p + ')', 'gi');
			var ccdbbnp_regexp = new RegExp('(' + this._p_rules.can_contain_double_brs_but_not_p + ')', 'gi');

			if ( ccp_regexp.test(ancestor_element.tagName) )
			{
				this._editor.execCommand('FormatBlock', false, 'p');
// 				window.status = 'can contain p: ' + ancestor_element.tagName;
			}
			else if ( cncp_regexp.test(ancestor_element.tagName) )
			{
				this._editor.execCommand('FormatBlock', false, ancestor_element.tagName);

// 				// Copy all attributes of the ancestor_element to the new parent element
// 				var sel = this._get_selection();
// 				var range = this._create_range(sel);
// 				var new_element = this._get_nearest_ancestor_element(range, this._is_block_level_element); // this works because it's just after we've formatted the block
// 				for ( var i = 0; i < ancestor_element.attributes.length; i++ )
// 				{
// 					var attr = ancestor_element.attributes.item(i);
// 					alert(attr.name + ': ' + attr.value);
// 					new_element.setAttributeNode(attr);
// 				}

// 				window.status = 'cannot contain p: ' + ancestor_element.tagName;
			}
			else if ( ccomtop_regexp.test(ancestor_element.tagName) )
			{
				// This can remain a stub right now, since it only deals with tables, which we don't actually handle anyway

				//this._editor.execCommand('FormatBlock', false, ancestor_element.tagName);
// 				window.status = 'can only contain more than one p: ' + ancestor_element.tagName;
			}
			else if ( ccdbbnp_regexp.test(ancestor_element.tagName) )
			{
// 				window.status = 'can contain double brs but not p: ' + ancestor_element.tagName;
			}
			else
			{
// 				window.status = 'do nothing: ' + ancestor_element.tagName;
			}

			return true;
		}
		else
		{
// 			window.status = 'ancestor element == false';
		}
	}
	else
	{
// 		window.status = 'event.keyCode != 13';
	}

	return true;
};

hel.prototype._insert_html = function(the_html)
{
//   	var sel = this._get_selection();
//   	var range = this._create_range(sel);

	// construct a new document fragment with the given HTML
	var fragment = this._editor.createDocumentFragment();
	var div = this._editor.createElement('div');
	div.innerHTML = the_html;
	while (div.firstChild) {
		// the following call also removes the node from div
		fragment.appendChild(div.firstChild);
	}
	// this also removes the current selection
 	var node = this._insert_node_at_selection(fragment);
};

// Returns a node after which we can insert other nodes, in the current
// selection.  The selection is removed.  It splits a text node, if needed.
hel.prototype._insert_node_at_selection = function(to_be_inserted)
{
	var sel = this._get_selection();
	var range = this._create_range(sel);
	// remove the current selection
	sel.removeAllRanges();
	range.deleteContents();
	var node = range.startContainer;
	var pos = range.startOffset;
	range = this._create_range();
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

hel.prototype._get_selection = function()
{
	return this._editor_window.getSelection();
};

// returns a range for the current selection
hel.prototype._create_range = function(sel)
{
	if(sel != null && sel.rangeCount > 0)
		return sel.getRangeAt(0);
	else // I'm not sure if this else statement should be here
		return this._editor.createRange();

// 		this.focusEditor();
// 		if (sel) {
// 			return sel.getRangeAt(0);
// 		} else {
// 			return this._doc.createRange();
// 		}
};

// Used by the toolbar buttons
hel.prototype.exec = function(command_name)
{
	try {
		this._editor.execCommand(command_name, false, null);
		this._editor_window.focus();
	} catch(e) {}
};

hel.prototype.headline = function()
{
	try {
		if (this._editor.queryCommandValue('FormatBlock') != 'h3') {
			this._editor.execCommand('FormatBlock', false, 'h3');
		} else {
			this._editor.execCommand('FormatBlock', false, 'p');
		}
		this._editor_window.focus();
	} catch(e) {}
};

hel.prototype.preformat = function()
{
	if (this._editor.queryCommandValue('FormatBlock') != 'pre') {
			this._editor.execCommand('FormatBlock', false, 'pre');
	} else {
		this._editor.execCommand('FormatBlock', false, 'p');
	}

	this._editor_window.focus();
};

// hel.prototype.teletype = function()
// {
// 	var sel = this._get_selection();
// 	var range = this._create_range(sel);

// 	var pre_elem = this._editor.createElement('TT');
// 	// 	The following two lines mostly do what range.surroundContents(pre_elem) should do (see http://www.mozilla.org/docs/dom/domref/dom_range_ref22.html#1004930)
// 	pre_elem.appendChild(range.extractContents());
// 	range.insertNode(pre_elem);

// 	this._editor_window.focus();
// };

// Changed back from inserting a block-styled spacer.gif by NF 2004-06-30.
// See fillmorn/reason_backups/wdp_loki_2004-06-29/js/hel.js for the image
// solution.
hel.prototype.insert_br = function()
{
	
	this._insert_html('<br>');
	this._editor_window.focus();	
};

// Changed back from inserting a block-styled spacer.gif by NF 2004-06-30.
// See fillmorn/reason_backups/wdp_loki_2004-06-29/js/hel.js for the image
// solution.
hel.prototype.insert_hr = function()
{
	var sel = this._get_selection();
	var rng = this._create_range(sel);
	var hr = this._editor.createElement('HR');

	//alert('rng.startContainer: ' + rng.startContainer);
// 	alert('this needs to be fixed (but wait for better oop)');

	this._insert_html('<hr>');
	this._editor_window.focus();
};

// Opens a modal window with the specified location.
// The modal window will have access to this object via opener.<?php echo (isset($_REQUEST['editor_id'])) ? $_REQUEST['editor_id'] : 'editor_id'; ?>,
// and it should call the appropriate method to pass back variables and do other interesting things.
hel.prototype.open_modal_window = function(modal_url, width, height)
{
// 	try {
		var the_window = window.open(modal_url, 'hel_modal_window', 'chrome,modal,dialog,resizable=yes,width='+width+',height='+height);
// 	} catch (e) {}
};


// If the specified range is a child of an element that meets the
// conditions specified in the function boolean_test, returns
// true. Otherwise, returns false.
//
// Example usage 1: var is_child = this._is_child_of_element(rng, function(node) { return node.tagName == 'A' });
// Example usage 2: var is_child = this._is_child_of_element(rng, function(node, ref_to_this) { return node.tagName == ref_to_this.something });
//
hel.prototype._is_child_of_element = function(rng, boolean_test)
{
	return this._get_nearest_ancestor_element(rng, boolean_test) != null;
};

// Recurses through the ancestor elements of the specified range,
// until either (a) an element is found which meets the conditions
// specified in the function boolean_test, or (b) the root of the
// document tree is reached. If (a) obtains, the found element is
// returned; if (b) obtains, null is returned.
//
// Example usage 1: var nearest_ancestor = this._get_nearest_ancestor_element(rng, function(node) { return node.tagName == 'A' });
// Example usage 2: var nearest_ancestor = this._get_nearest_ancestor_element(rng, function(node, ref_to_this) { return node.tagName == ref_to_this.something });
//
hel.prototype._get_nearest_ancestor_element = function(rng, boolean_test)
{
	var cur_node = rng.commonAncestorContainer;
	while ( true )
	{
		if ( cur_node.nodeType == Node.ELEMENT_NODE )
		{
			//
			// N.B.: Although we're passing as the second argument of
			// "boolean_test" a reference to "this", it's not
			// necessary for "boolean_test" to explicitly specify a
			// variable name in which to receive that argument. That's
			// why both example usages 1 and 2 above are possible.
			// For more information cf. <http://academ.hvcc.edu/~kantopet/javascript/index.php?page=adv+js+functions&parent=js+functions>.
			//
			if ( boolean_test(cur_node, this) )
				return cur_node;
			else
				cur_node = cur_node.parentNode;
		}
		else if ( cur_node.nodeType == Node.DOCUMENT_NODE
				  || cur_node.nodeType == Node.DOCUMENT_FRAGMENT_NODE )
		{
			return null;
		}
		else
		{
			cur_node = cur_node.parentNode;
		}
	}
};

//
// This inserts a link, and will typically be called from inside the
// modal_link window itself
//
hel.prototype.insert_image = function(src, height, width, alt)
{
// 	try {

	var image_elem = this._editor.createElement('img', src);
	image_elem.setAttribute('src', src);
	image_elem.setAttribute('height', height);
	image_elem.setAttribute('width', width);
	image_elem.setAttribute('alt', alt);
	image_elem.setAttribute('border', '0');
	image_elem.setAttribute('hspace', '10');
	image_elem.setAttribute('vspace', '10');
	this._insert_node_at_selection(image_elem);

// 	} catch (e) {}
};

//
// This opens the window for inserting a link. It relies on
// insert_link for actually inserting the link.
//
// type should be either 'asset' or ''
//
hel.prototype.open_modal_link = function(type)
{
	var sel = this._get_selection();
	var rng = this._create_range(sel);

	// First, look for anchor inside the range
	var doc_frag = rng.cloneContents();
	var clipboard = document.getElementById(this._editor_id+'_clipboard').contentWindow.document;
	clipboard.body.innerHTML = '';
	clipboard.body.appendChild(doc_frag);

	var links = clipboard.getElementsByTagName('a');
	var url = '', new_window = false, title = '';
	if ( links.length > 0 ) {
		url = links.item(0).getAttribute('href');
		new_window = ( links.item(0).hasAttribute('target')
					   && links.item(0).getAttribute('target') != '_self'
					   && links.item(0).getAttribute('target') != '_parent'
					   && links.item(0).getAttribute('target') != '_top' );
		title = links.item(0).getAttribute('title');
	}

	// Second, look for anchor surrounding the range
	if ( url == '' )
	{
		var parent_node = this._get_nearest_ancestor_element(rng, function(node) { return node.tagName == 'A' });
		if ( parent_node != null )
		{
			url = parent_node.getAttribute('href');
			new_window = ( parent_node.hasAttribute('target')
						   && parent_node.getAttribute('target') != '_self'
						   && parent_node.getAttribute('target') != '_parent'
						   && parent_node.getAttribute('target') != '_top' );
			title = parent_node.getAttribute('title');

			// So that execCommand sees the parent_node (i.e., sees the entire anchor node):
			rng.setStartBefore(parent_node);
			rng.setEndAfter(parent_node);
			//sel.addRange(rng);
		}
	}

	// Find named anchors in the editor, to pass to the window
	var named_anchors = new Array();
	var all_anchors = this._editor.getElementsByTagName('img');
	var j = 0;
	for (i = 0; i < all_anchors.length; i++)
	{
		if ( all_anchors[i].getAttribute("loki:is_really_an_anchor_whose_name") != null)
		{
			named_anchors[j] = document.createElement('a');
			named_anchors[j].name = all_anchors[i].getAttribute("loki:is_really_an_anchor_whose_name");
			j++;
		}
	}

	this.temp_modal_args.named_anchors = named_anchors;
	this.temp_modal_args.link_url = url;
	this.temp_modal_args.link_new_window = new_window;
	this.temp_modal_args.link_title = title;

	if ( type == 'asset' )
		this.open_modal_window(this._asset_location+'dialogs/hel_link_to_asset.php?site_id='+this._site_id+'&editor_obj='+this._editor_obj, 500, 300);
	else if ( type == 'email' )
		this.open_modal_window(this._asset_location+'dialogs/hel_link_to_email.php?site_id='+this._site_id+'&editor_obj='+this._editor_obj, 500, 375);
	else if ( type == 'image' )
		this.open_modal_window(this._asset_location+'dialogs/hel_image.php?site_id='+this._site_id+'&editor_obj='+this._editor_obj,600,450);
	else
		this.open_modal_window(this._asset_location+'dialogs/hel_link.php?site_id='+this._site_id+'&editor_obj='+this._editor_obj,450,375);
};

hel.prototype.preload_modal_link = function(type, iframe)
{
	if ( type == 'asset' )
		document.getElementById(iframe).src = this._asset_location+'dialogs/hel_link_to_asset.php?site_id='+this._site_id+'&editor_obj='+this._editor_obj;
	else if ( type == 'email' )
		document.getElementById(iframe).src = this._asset_location+'dialogs/hel_link_to_email.php?site_id='+this._site_id+'&editor_obj='+this._editor_obj;
	else if ( type == 'image' )
		document.getElementById(iframe).src = this._asset_location+'dialogs/hel_image.php?site_id='+this._site_id+'&editor_obj='+this._editor_obj;
	else
		document.getElementById(iframe).src = this._asset_location+'dialogs/hel_link.php?site_id='+this._site_id+'&editor_obj='+this._editor_obj;
};

//
// This inserts a link, and will typically be called from inside the
// modal_link window itself
//
hel.prototype.insert_link = function(url, new_window, title, onclick)
{
// 	try {

    url = url.replace( /\%7E/g, '~' ); //so that users of older versions of Mozilla aren't confused by this substitution

	if ( url != '' ) this._editor.execCommand('CreateLink', false, 'hel_temp_url');
	else             this._editor.execCommand('unlink', false, null);

	var links = this._editor.getElementsByTagName('a');
	for (var i = 0; i < links.length; i++) {
		if ( links.item(i).getAttribute('href') == 'hel_temp_url') {

			links.item(i).setAttribute('href', url);

			if ( new_window == true )  links.item(i).setAttribute('target', '_blank');
			else                       links.item(i).removeAttribute('target');

			if ( title != '' ) links.item(i).setAttribute('title', title);
			else               links.item(i).removeAttribute('title');

			if ( onclick != '' ) links.item(i).setAttribute('loki:onclick', onclick);
			else                 links.item(i).removeAttribute('loki:onclick');
		}
	}

// 	} catch (e) {}
};

//
// This opens the window for inserting a link. It relies on
// insert_named_anchor for actually inserting the link.
//
hel.prototype.open_modal_named_anchor = function()
{
	var sel = this._get_selection();
	var rng = this._create_range(sel);

	// First, look for anchor inside the range
	var doc_frag = rng.cloneContents();
	var clipboard = document.getElementById(this._editor_id+'_clipboard').contentWindow.document;
	clipboard.body.innerHTML = '';
	clipboard.body.appendChild(doc_frag);

	var named_anchors = clipboard.getElementsByTagName('img');
	var anchor_name = '';
	if ( named_anchors.length > 0 ) {
		anchor_name = named_anchors.item(0).getAttribute('loki:is_really_an_anchor_whose_name');
	}

	// Second, open the window
	this.temp_modal_args.anchor_name = anchor_name;
	this.open_modal_window(this._asset_location+'dialogs/hel_named_anchor.php?site_id='+this._site_id+'&editor_obj='+this._editor_obj, 350, 225);
};

//
// This inserts a named anchor, and will typically be called from
// inside the modal_named_anchor window itself
//
hel.prototype.insert_named_anchor = function(anchor_name)
{
// 	try {

	var sel = this._get_selection();
	var rng = this._create_range(sel);

	if ( anchor_name != '' ) {
		this._delete_named_anchors_in_range(rng);

		// neither of these seems to do any good
// 		rng.collapse(true);
// 		sel.collapseToStart();

		var image_elem = this._editor.createElement('img', anchor_name);
		image_elem.setAttribute('loki:is_really_an_anchor_whose_name', anchor_name);
		image_elem.setAttribute('src', 'https://'+this._server_name+this._asset_location+'images/nav/anchor.gif');
		this._insert_node_at_selection(image_elem);
	}
	else { // "Remove Anchor" was selected
		this._delete_named_anchors_in_range(rng);
	}

// 	} catch (e) {}
};

//
// This function deletes any named anchors in the specified range
//
hel.prototype._delete_named_anchors_in_range = function(rng)
{
	// Copy from the range into the clipboard iframe
	var doc_frag = rng.cloneContents();
	var clipboard = document.getElementById(this._editor_id+'_clipboard').contentWindow.document;
	clipboard.body.innerHTML = '';
	clipboard.body.appendChild(doc_frag);

	// Delete the images that are standing in for named anchors
	var imgs = clipboard.getElementsByTagName('img')
	for (var i = 0; i < imgs.length; i++)
	{
		if ( imgs.item(i).getAttribute('loki:is_really_an_anchor_whose_name') != null )
		{
			imgs.item(i).parentNode.removeChild(imgs.item(i));
		}
	}

	// Copy from the clipboard iframe into a document fragment
	var clipboard_rng = clipboard.createRange();
	clipboard_rng.selectNodeContents(clipboard.body);
	var clipboard_doc_frag = clipboard_rng.extractContents();

	// Replace the current contents of the range with the contents of the document fragment
	rng.deleteContents();
	this._insert_node_at_selection(clipboard_doc_frag);
};

//
// This opens the window for inserting a link. It relies on
// insert_named_anchor for actually inserting the link.
//
hel.prototype.open_modal_spell = function()
{
	var sel = this._get_selection();
	var rng = this._create_range(sel);

	// First, get the contents of the editor
	var text = this._editor.body.innerHTML;

	// Second, open the window
	this.temp_modal_args.text = text;
	//this.open_modal_window(this._asset_location+'dialogs/hel_spell.php?site_id='+this._site_id+'&editor_obj='+this._editor_obj); //+'&text='+escape(text));
	window.open(this._asset_location+'dialogs/hel_spell.php?site_id='+this._site_id+'&editor_obj='+this._editor_obj,
				'hel_modal_window', 'scrollbars=1,resizable,width=600,height=300');
};

//
// This copies the corrected text back into hel. (It's to be called
// from inside the spell check modal window.)
//
hel.prototype.copy_from_spell_into_hel = function(text)
{
	this._editor.body.innerHTML = text;
};

//
// A nice-looking replacement for alert
//
hel.prototype.alert = function(text)
{
	this.temp_modal_args.text = text;
	var modal_url = this._asset_location + 'dialogs/hel_alert.php?editor_obj=' + this._editor_obj;
	var window_name = "window_" + (new Date()).getTime(); // Generate a unique name
	window.open(modal_url, window_name, 'scrollbars=1,resizable,width=600,height=300');
};

hel.prototype._copy_from_source_into_hel = function()
{
	var html_elem = document.getElementById(this._editor_id);
	var source_elem = document.getElementById(this._editor_id+'_source');
	
// 	this._editor.getElementsByTagName('body').item(0).appendChild(
// 		this._editor.importNode(source_elem, true)
// 	);

// 	alert(this._editor.getElementsByTagName('body').item(0).innerHTML);


// 	this._editor.getElementsByTagName('body').item(0).appendChild(source_elem.cloneNode(true));

// 	alert(source_elem.value);

	this._editor.body.innerHTML = source_elem.value;

// 	alert(source_elem.value);
};

hel.prototype._copy_from_hel_into_source = function()
{
	var html_elem = document.getElementById(this._editor_id);
	var source_elem = document.getElementById(this._editor_id+'_source');

	source_elem.value = this._editor.body.innerHTML;
};

//
// This code is based on the mozilla.org's demo at <http://www.mozilla.org/editor/midasdemo/>
//
hel.prototype.toggle_source = function()
{
// 	try {

	var html_elem = document.getElementById(this._editor_id);
	var source_elem = document.getElementById(this._editor_id+'_source');

	if ( html_elem.style.display != 'none' )
	{
		source_elem.style.display = 'block';
		html_elem.style.display = 'none';

		source_elem.style.width = html_elem.width;
		source_elem.style.height = html_elem.height;

		this._copy_from_hel_into_source();
	}
	else
	{
		source_elem.style.display = 'none';
		html_elem.style.display = 'block';

		this._copy_from_source_into_hel();
		this._start();
	}
};

//
// A nice-looking replacement for alert
//
hel.prototype.alert_clean_html = function()
{
	this.temp_modal_args.text = this._editor.body.innerHTML;
	var modal_url = this._asset_location + 'dialogs/hel_alert_clean.php?editor_obj=' + this._editor_obj;
	var window_name = "window_" + (new Date()).getTime(); // Generate a unique name
	window.open(modal_url, window_name, 'scrollbars=1,resizable,width=600,height=300');
};

// //
// // Alerts a cleaned version of the contents of the editor
// //
// hel.prototype.alert_clean_html = function()
// {
// 	this.alert(this.export_clean_html());
// };

// //
// // Returns a cleaned version of the contents of the editor
// //
// hel.prototype.export_clean_html = function()
// {
// 	var html = this.export_html();

// 	var ps = html.getElementsByTagName('p');
// 	alert(ps);


// 	this._editor.body.innerHTML = dirty_html; // are you sure you want to do it this way? (perhaps call a function instead)

// 	return html;
// };


//
// Returns the current contents of the editor, formatted as html
//
hel.prototype.export_html = function()
{
	var html_elem = document.getElementById(this._editor_id);
	var source_elem = document.getElementById(this._editor_id+'_source');

	if ( html_elem.style.display != 'none' )
		return this._editor.body.innerHTML;
	else
		return source_elem.value;
};

///
function find_form(el) {
	if (el !=null && el.tagName=="FORM") {
		return el;
	} else {
		return find_form(el.parentNode);
	}
}
