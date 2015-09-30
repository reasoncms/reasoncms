var TestTools = {
	setHTML: function set_loki_textarea_html(html) {
		while (this.textarea.firstChild)
			this.textarea.firstChild = null;
		this.textarea.appendChild(this.textarea.ownerDocument.createTextNode(html));
		return this;
	},
	
	show: function show_loki(settings) {
		settings = Crucible.augment({base_uri: '../', options: 'all'},
			settings || {});
		this.editor.init(this.textarea, settings);
		return this;
	},
	
	getHTML: function get_loki_html() {
		return this.editor.get_html();
	},
	
	findElement: function find_loki_element(selector) {
		var elements = match_elements(this.editor.document, selector);
		if (elements.length == 0) {
			throw new Error('No elements matched selector "' + selector + '".');
		} else if (elements.length > 1) {
			throw new Error(elements.length + ' elements matched selector "' +
				selector + '", but only one may.');
		}
		
		return elements[0];
	},
	
	findNodes: function find_nodes(test, context) {
		var matches = [];
		var start = this.editor.document.body;
		var n;
		var type;
		
		if ("number" == typeof(test)) {
			type = test;
			test = function is_of_type(node) {
				return node.nodeType == type;
			}
		}
		
		function get_next(n) {
			return n.firstChild ||
				n.nextSibling ||
				(n.parentNode ? n.parentNode.nextSibling : null) ||
				null;
		}
		
		for (n = start; n; n = get_next(n)) {
			if (test.call(context || null, n))
				matches.push(n);
		}
		
		return matches;
	},
	
	elementContent: function get_loki_element_content(selector) {
		return this.findElement(selector).innerHTML;
	},
	
	elementText: function get_loki_element_content(selector) {
		var el = this.findElement(selector);
		return el.innerText || el.textContent;
	},
	
	elementCount: function count_elements_matching_selector(selector) {
		return match_elements(this.editor.document, selector).length;
	},
	
	createRange: function create_range(range) {
		return Util.Document.create_range(this.editor.document);
	},
	
	selectRange: function select_range(range) {
		var selection = Util.Selection.get_selection(this.editor.window);
		Util.Selection.select_range(selection, range);
		return this;
	},
	
	selectElement: function select_element(selector) {
		var element = this.findElement(selector);
		var range = Util.Document.create_range(this.editor.document);
		
		Util.Range.select_node(range, element);
		return this.selectRange(range);
	},
	
	selectElementContents: function select_element_contents(selector) {
		var element = this.findElement(selector);
		var range = Util.Document.create_range(this.editor.document);
		
		Util.Range.select_node_contents(range, element);
		return this.selectRange(range);
	},
	
	selectInElement: function(selector, start, end) {
		var element = this.findElement(selector);
		var range = Util.Document.create_range(this.editor.document);
		var i, pos, child, tl;
		
		if (!start)
			start = 0;
		
		for (pos = 0, i = 0; i < element.childNodes.length; i++) {
			child = element.childNodes[i];
			if (child.nodeType != Util.Node.TEXT_NODE)
				continue;
			tl = child.nodeValue.length;
			
			if (pos <= start && (pos + tl) > start) {
				Util.Range.set_start(range, child, start - pos);
				if (!end)
					break;
			}
			if (end && pos <= end && (pos + tl) > end) {
				Util.Range.set_end(range, child, end - pos);
				break;
			}
			
			pos += tl;
		}
		
		return this.selectRange(range);
	},
	
	selectionBounds: function() {
		var selection = Util.Selection.get_selection(this.editor.window);
		var range = Util.Range.create_range(selection);
		
		return Util.Range.get_boundaries(range);
	},
	
	pushButton: function(button) {
		var bo;
		if (typeof(button) == 'string')
			button = UI[button + '_Button'];
		if (!button)
			throw new Error('No button provided to push.');
		
		bo = (new button()).init(this.editor);
		bo.click_listener();
		
		return this;
	},
	
	getMenuItems: function(menugroup) {
		var mgo;
		if (typeof(menugroup) == 'string')
			menugroup = UI[menugroup + '_Menugroup'];
		if (!menugroup)
			throw new Error('No menugroup provided.');
		
		mgo = (new menugroup()).init(this.editor);
		return mgo.get_contextual_menuitems();
	},
	
	getMenuItem: function(menugroup, label) {
		var items = this.getMenuItems(menugroup);
		var test = (typeof(label) == 'string')
			? function(l) { return l == label; }
			: function(l) { return label.test(l); }
		var i;
		
		for (i = 0; i < items.length; i++) {
			if (test(items[i].get_label()))
				return items[i];
		}
		
		return null;
	},
	
	hasMenuItem: function(menugroup, label) {
		return !!this.getMenuItem(menugroup, label);
	},
	
	runMenuItem: function(menugroup, label) {
		var item = this.getMenuItem(menugroup, label);
		
		if (!item)
			throw new Error('No item found with matching label.');
			
		item.get_listener().call(item);
	}
};
