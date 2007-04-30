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
	this._LOADING_STR = 'Loading ...';
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
	this.init = function(params)
	{
		this._anchor_names = params.anchor_names;
		this._sites_feed = params.sites_feed;
		this._finder_feed = params.finder_feed;
		this._default_site_regexp = params.default_site_regexp;
		this._default_type_regexp = params.default_type_regexp;
		// use rss integration only if sites_feed and finder_feed are given:
		this._use_rss = params.sites_feed && params.finder_feed;

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
			this._dialog_window.document.title = "Make a Link";
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

		//this._load_sites(this._sites_feed);
		//this._load_finder(this._finder_feed);
		// this is already called in UI.Dialog.open;
		//this._apply_initially_selected_item();
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

		var sites_label = this._doc.createElement('LABEL');
		sites_label.innerHTML = 'Sites: ';
		sites_label.htmlFor = 'sites_select';
		sites_pane.appendChild(sites_label);

		this._sites_select = new Util.Select({document : this._doc, loading_str : this._LOADING_STR, id : 'sites_select'});
		sites_pane.appendChild(this._sites_select.select_elem);
		//this._sites_select.start_loading();

		// Types/Links wrapper
		var pane_wrapper = this._doc.createElement('DIV');
		pane_wrapper.id = 'pane_wrapper';
		container.appendChild(pane_wrapper);

		// Types pane
		this._types_pane = this._doc.createElement('DIV');
		this._types_pane.id = 'types_pane';
		pane_wrapper.appendChild(this._types_pane);

		this._types_pane_ul = this._doc.createElement('UL');
		this._types_pane_ul.id = 'types_pane_ul';
		this._types_pane.appendChild(this._types_pane_ul);

		var types_pane_loading_li = this._doc.createElement('LI');
		types_pane_loading_li.innerHTML = this._LOADING_STR;
		this._types_pane_ul.appendChild(types_pane_loading_li);

		// Links pane
		this._links_pane = this._doc.createElement('DIV');
		this._links_pane.id = 'links_pane';
		pane_wrapper.appendChild(this._links_pane);

		// Links select
		this._links_label = this._doc.createElement('LABEL');
		this._links_label.id = 'links_label';
		this._links_label.htmlFor = 'links_select';
		this._links_pane.appendChild(this._links_label);

		this._links_select = new Util.Select({ document : this._doc, loading_str : this._LOADING_STR, id : 'links_select' });
		this._links_pane.appendChild(this._links_select.select_elem);
		//this._links_select.start_loading();

		// Anchors select
		var anchors_label = this._doc.createElement('LABEL');
		anchors_label.id = 'anchors_label';
		anchors_label.htmlFor = 'anchors_select';
		anchors_label.innerHTML = 'Anchors (optional):';
		this._links_pane.appendChild(anchors_label);

		this._anchors_select = new Util.Select({ document : this._doc, loading_str : this._LOADING_STR, id : 'anchors_select' });
		this._links_pane.appendChild(this._anchors_select.select_elem);
		//this._anchors_select.start_loading();

/*
		container.innerHTML =
			'<div id="sites_pane">' +
			'  <label for="sites_select">Site:</label>' +
			'  <select id="sites_select"><option>' + this._LOADING_STR + '</option></select>' +
			'</div>' +
			'<div id="pane_wrapper">' +
			'  <div id="types_pane"><ul id="types_pane_ul"><li>' + this._LOADING_STR + '</li></ul></div>' +
			'  <div id="links_pane">' +
		    '    <label id="links_label" for="links_select"></label>' +
		    '    <select id="links_select"><option>' + this._LOADING_STR + '</option></select>' +
		    '    <label for="anchors_select">Anchors you can link to (optional):</label>' +
		    '    <select id="anchors_select"><option>' + this._LOADING_STR + '</option></select>' +
			'  </div>' +
			'</div>';

		this._sites_select = this._doc.getElementById('sites_select');
		this._types_pane = this._doc.getElementById('types_pane');
		this._types_pane_ul = this._doc.getElementById('types_pane_ul');
		this._links_pane = this._doc.getElementById('links_pane');
		this._links_label = this._doc.getElementById('links_label');
		this._links_select = this._doc.getElementById('links_select');
		this._anchors_select = this._doc.getElementById('anchors_select');
*/

		var self = this;
		Util.Event.add_event_listener(this._sites_select.select_elem, 'change', function()
		{ 
			var option = self._sites_select.select_elem.options[self._sites_select.select_elem.selectedIndex];
			self._load_types(option.text, option.value);
		});

		Util.Event.add_event_listener(this._links_select.select_elem, 'change', function()
		{ 
			var option = self._links_select.select_elem.options[self._links_select.select_elem.selectedIndex];
			self._set_link_title(option.text);
			self._load_anchors(option.value);
		});
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
		// We don't want to overwrite the initially given
		// title onload, before the user has taken any
		// action or even seen the original title.
		if ( this._links_already_loaded_once == false )
			return;

		if ( new_title == this._CURRENT_PAGE_STR || 
			 new_title == this._LOADING_STR )
			this._link_title_input.value = '';
		else
			this._link_title_input.value = new_title;
	};

	this._add_type = function(name, feed_uri, is_selected)
	{
		var li = this._doc.createElement('LI');

		var a = this._doc.createElement('A');
		a.href = 'javascript:void(0);';
		a.appendChild(this._doc.createTextNode(name));

		var self = this;
		Util.Event.add_event_listener(a, 'click', function() { 
			self._select_type(li, name, feed_uri);
		});

		li.appendChild(a);
		this._types_pane_ul.appendChild(li);

		if ( is_selected )
			this._select_type(li, name, feed_uri);
	};

	this._select_type = function(li, name, feed_uri)
	{
		// Deselect all, then select new
		var lis = this._types_pane_ul.getElementsByTagName('LI');
		for ( var i = 0; i < lis.length; i++ )
			Util.Element.remove_class(lis[i], 'selected');
		Util.Element.add_class(li, 'selected');

		//this._tabset.select_tab('rss');
		this._load_links(name, feed_uri); 
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

	this._load_finder = function(feed_uri)
	{
		// Split name from uri
		var a = this._initially_selected_item.httpless_uri.split('#');
		this._initially_selected_nameless_uri = a[0];
		this._initially_selected_name = a.length > 1 ? a[1] : '';

		// Add initially selected uri
		var self = this;
		var add_initially_selected_uri = function(uri)
		{
			var connector = ( uri.indexOf('?') > -1 ) ? '&' : '?';
			// XXX: shouldn't need to add "http:" here--fix RSS feed
			return uri + connector + 'url=' + 
				encodeURIComponent('http:' + self._initially_selected_nameless_uri);
		};

		// Load finder
		feed_uri = add_initially_selected_uri(feed_uri)
		var reader = (new Util.RSS_Reader).init(feed_uri, 10);
		var self = this;
		reader.add_load_listener(function()
		{
			// Set initially selected site uri
			// Set initially selected type uri
			// Set initially selected link uri
			// Set initially selected anchor uri

			// Find site and type uris in feed ...
			var initially_selected_site_uri, initially_selected_type_uri;
			var items = reader.get_cur_items();
			for ( var i = 0; i < items.length; i++ )
			{
				var item_uri = Util.URI.strip_https_and_http(items[i].link);
				if ( items[i].title == 'site_feed' )
					initially_selected_site_uri = item_uri;
				if ( items[i].title == 'type_feed' )
					initially_selected_type_uri = item_uri;
			}

			// ... then set them if found
			if ( initially_selected_site_uri )
				self._initially_selected_site_uri = initially_selected_site_uri;
			else
				self._initially_selected_site_uri = null; // (might already be set from previous opening of dialog)

			if ( initially_selected_type_uri )
				self._initially_selected_type_uri = initially_selected_type_uri;
			else
				self._initially_selected_type_uri = null; // (might already be set from previous opening of dialog)
				
			// Trigger load_sites
			//self._load_sites(self._sites_feed);

			// Trigger listener
			self._finder_listener();
		});
		setTimeout(function() { reader.load_next_items(); }, 100); // gives time to render
	};

	this._load_sites = function(feed_uri)
	{
		// Start loading
		this._sites_select.start_loading();
		this._workaround_ie_select_display_bug();

		// Load new sites
		var reader = (new Util.RSS_Reader).init(feed_uri);
		var self = this;
		reader.add_load_listener(function()
		{
			// Add new sites
			var items = reader.get_cur_items();
			for ( var i = 0; i < items.length; i++ )
			{
				//var is_selected = ( items[i].isSelected == 'true' );
				//var is_selected = ( items[i].link == self._initially_selected_site_uri );
				//var is_selected = self._compare_uris(items[i].link, self._initially_selected_site_uri);
				var item_uri = Util.URI.strip_https_and_http(items[i].link);
				var is_selected = ( item_uri == self._initially_selected_site_uri ||
									( !self._initially_selected_site_uri &&
									  self._default_site_regexp.test(item_uri) ) );
				self._sites_select.add_option(items[i].title, item_uri, is_selected);
			}

			/*
			// Select initially selected site
			// XXX: should be current site, not just first
			self._sites_select.selectedIndex = 0;
			*/

			self._sites_select.end_loading();

			// Now trigger load types
			if ( self._sites_select.select_elem.options.length > 0 )
			{
				var i = self._sites_select.select_elem.selectedIndex;
				var option = self._sites_select.select_elem.options[i];
				self._load_types(option.text, option.value);
			}
		});
		setTimeout(function() { reader.load_next_items(); }, 100); // gives time to render
	};

	this._load_types = function(name, feed_uri)
	{
		// Remove all old types and set loading message 
		this._types_pane_ul.innerHTML = '<li>' + this._LOADING_STR + '</li>';

		// Load new types
		var reader = (new Util.RSS_Reader).init(feed_uri);
		var self = this;
		reader.add_load_listener(function()
		{
			// Remove loading message
			self._types_pane_ul.innerHTML = '';

			// Add new types
			var items = reader.get_cur_items();
			for ( var i = 0; i < items.length; i++ )
			{
				//var is_selected = items[i].title == 'Pages';
				//var is_selected = ( items[i].isSelected == 'true' );
				//var is_selected = self._compare_uris(items[i].link, self._initially_selected_type_uri);
				var item_uri = Util.URI.strip_https_and_http(items[i].link);
				var is_selected = ( item_uri == self._initially_selected_type_uri ||
									( /* !self._initially_selected_type_uri */
									  self._default_type_regexp.test(item_uri) ) );
				self._add_type(items[i].title, item_uri, is_selected);
			}

			/*
			// Add "custom" pseudo-type
			//XXX deprecated:...
			//self._add_custom(some_type_is_selected == false);
			if ( !some_type_is_selected )
				self._select_custom_or_email_tab();
			*/

			/*
			// Select initially selected item
			// XXX: should be "pages" type, not just first
			var li = self._types_pane_ul.getElementsByTagName('LI').item(0);
			var name = items[0].title;
			var feed_uri = items[0].link;
			self._select_type(li, name, feed_uri);
			*/
		});
		setTimeout(function() { reader.load_next_items(); }, 100); // gives time to render
	};

/*
	this._select_custom_or_email_tab = function()
	{
		var uri = this._initially_selected_item.uri;
		if ( uri.match(new RegExp('^mailto\:', 'i')) != null )
		{
			this._tabset.select_tab('email');
		}
		else
		{
			this._tabset.select_tab('custom');
		}
	};
*/

	this._load_links = function(name, feed_uri)
	{
		// Set labela and start loading
		this._links_label.innerHTML = name + ':';
		this._links_select.start_loading();
		this._workaround_ie_select_display_bug();

		// Load new links
		var reader = (new Util.RSS_Reader).init(feed_uri);
		var self = this;
		reader.add_load_listener(function()
		{
			// If "pages" type, add "current page" option
			var i = self._sites_select.select_elem.selectedIndex;
			var selected_site_option = self._sites_select.select_elem.options[i];
			var selected_site_uri = selected_site_option.value;
			if ( name == 'Pages' && self._default_site_regexp.test(selected_site_uri) ) // XXX here
				self._links_select.add_option(self._CURRENT_PAGE_STR, '', true);

			// Add new links
			var items = reader.get_cur_items();
			for ( var i = 0; i < items.length; i++ )
			{
				if(items[i].link)
				{
					//var is_selected = ( items[i].isSelected == 'true' );
					//var is_selected = self._compare_uris(items[i].link, self._initially_selected_nameless_uri);
					var item_uri = Util.URI.strip_https_and_http(items[i].link);
					var is_selected = ( item_uri == self._initially_selected_nameless_uri );
					self._links_select.add_option(items[i].title, item_uri, is_selected);
				}
			}

			self._links_select.end_loading();

			// Now trigger load anchors
			if ( self._links_select.select_elem.options.length > 0 )
			{
				var i = self._links_select.select_elem.selectedIndex;
				var option = self._links_select.select_elem.options[i];
				self._set_link_title(option.text);
				self._load_anchors(option.value);
			}

			self._links_already_loaded_once = true;
		});
		setTimeout(function() { reader.load_next_items(); }, 100); // gives time to render
	};

	this._load_anchors = function(uri)
	{
		// Start loading
		this._anchors_select.start_loading();
		this._workaround_ie_select_display_bug();

		// Load new anchors
		if ( uri == '' )
		{
			this._add_anchors(this._anchor_names);
		}
		else
		{
			var feed_uri = this._base_uri + 'anchors_feed.php?uri=' + encodeURI(uri);

			// Load new links
			var reader = (new Util.RSS_Reader).init(feed_uri);
			var self = this;
			reader.add_load_listener(function()
			{
				// Get anchors` names
				var names = [];
				var items = reader.get_cur_items();
				for ( var i = 0; i < items.length; i++ )
					names.push(items[i].title);

				// Add anchors
				self._add_anchors(names);
			});
			setTimeout(function() { reader.load_next_items(); }, 100); // gives time to render
		}
	};

	this._add_anchors = function(names)
	{
		// Split name from uri
		/*
		var a = this._initially_selected_item.uri.split('#');
		var selected_nameless_uri = a[0];
		var selected_name = a.length > 1 ? a[1] : '';
		*/

		// Add "none selected" option, and select it by default
		this._anchors_select.add_option('(none selected)', '');

		// Add new anchors
		for ( var i = 0; i < names.length; i++ )
		{
			// We only want to automatically select the item matching
			// the originally selected anchor the first time around, i.e.
			// when the dialog is first loading.
			var is_selected = ( this._anchors_already_loaded_once == false && 
								names[i] == this._initially_selected_name );
			this._anchors_select.add_option(names[i], names[i], is_selected);
			if ( is_selected && this._use_rss )
			{
				this._tabset.select_tab('rss');
				this._initialize_link_information('rss');
			}
		}

		this._anchors_select.end_loading();

		// XXX this is only temporarily commented out, until I figure out why
		// things are loading twice on start (which I've done, but now it's
		// not necessary)
		//this._anchors_already_loaded_once = true;
	};

	/**
	 * Returns an array of names from the given collection of A elements.
	 */
	this._get_anchor_names = function(as)
	{
		// Get a list of the named anchors in the document
		var names = new Array();
		for ( var i = 0; i < as.length; i++ )
		{
			if ( !as[i].getAttribute('href') &&
				 as[i].getAttribute('name') )
			{
				names.push(as[i].getAttribute('name'));
			}
		}
		return names;
	};

	/**
	 * Called as an event listener when the user clicks the submit
	 * button. 
	 */
	this._internal_submit_listener = function()
	{
		// Get URI

		var uri;
		if ( this._tabset.get_name_of_selected_tab() == 'rss' )
		{
			uri = this._links_select.select_elem.value;
			var anchor = this._anchors_select.select_elem.value;

			if ( uri == '' && anchor == '')
			{
				this._dialog_window.window.alert('Please select a page to be linked to.');
				return false;
			}

			if ( anchor != '' )
				uri += '#' + anchor;
		}
		else if ( this._tabset.get_name_of_selected_tab() == 'custom' )
		{
			var uri = this._custom_input.value;
			if ( uri.search( new RegExp('\@', '') ) > -1 && 
				 uri.search( new RegExp('\/', '') ) == -1 && // e.g. http://c.edu/fmail?to=me@c.edu would work
				 // We might as well let them create links to 
				 // email addresses from here if they know how:
				 uri.search( new RegExp('^mailto:') ) == -1 )
			{
				var answer = confirm("You've asked to create a link to a custom page, but <<" + uri + ">> looks like an email address. If you want to create a link to an email address, you should press \"Cancel\" here and use the \"" + this._EMAIL_TAB_STR + "\" tab instead. \n\nAre you sure you want to continue anyway?");
				if ( !answer )
					return;
			}
			else if ( uri.search( new RegExp('^file:') ) > -1 ||
			          uri.search( new RegExp('^[A-Za-z]:') ) > -1 )
			{
				var answer = confirm("It appears that you've tried to create a link to a page on your hard drive. This will not work anywhere but your own computer--probably not what you want. \n\nAre you sure you want to continue anyway?");
				if ( !answer )
					return;
			}
			else if ( uri.search( new RegExp('^https?:') ) == -1 && 
					  uri.search( new RegExp('^#') ) == -1 )
			{
				if ( uri.search( new RegExp('^www') ) > -1 ||
				     uri.search( new RegExp('^apps') ) > -1 )
				{
					uri = 'http://' + uri;
				}
				// Since links to pages on the same server/protocol are okay
				// without specifying protocol:
				else if ( uri.search( new RegExp('^[./]') ) == -1 ) 
				{
					if ( uri.search( new RegExp('^[A-Za-z]+://') ) == -1 )
					{
						var answer = confirm("It appears that you're trying to link to a page without specifying a protocol like HTTP. You probably want to press \"Cancel\" here, and add an \"http://\" to the beginning of your link. \n\nAre you sure you want to continue anyway?");
						if ( !answer )
							return;
					}
					else
					{
						var answer = confirm("It appears that you're trying to link to a page using a protocol other than HTTP. You shouldn't try this unless you know what you're doing. \n\nAre you sure you want to continue anyway?");
						if ( !answer )
							return;
					}
				}
			}
		}
		else
		{
			var uri = this._email_input.value;
			if ( uri.search( new RegExp('\@', '') ) == -1 ||
			     uri.search( new RegExp('^https?:') ) > -1 ||
			     uri.search( new RegExp('^www[.]') ) > -1 )
			{
				var answer = confirm("You've asked to create a link to an email address, but <<" + uri + ">> doesn't look like an email address. Are you sure you want to continue?");
				if ( answer == false )
					return;
			}

			if ( uri.search( new RegExp('^mailto:', 'i') ) == -1 )
				uri = 'mailto:' + uri;
		}

		// Call external event listener
		this._external_submit_listener({uri : uri, 
										new_window : this._new_window_checkbox.checked, 
										title : this._link_title_input.value});

		// Close dialog window
		this._dialog_window.window.close();
	};

	this._apply_initially_selected_item = function()
	{
		if ( this._use_rss )
		{	
			if ( !this._initially_selected_item.uri )
			{
				this._tabset.select_tab('rss');
				this._initialize_link_information('rss');
				this._load_sites(this._sites_feed);
			}
			else
			{
				this._load_finder(this._finder_feed);
			}	
		}
		else
		{
			this._select_custom_or_email_tab();
		}
	};

	this._finder_listener = function()
	{
		var not_found = !this._initially_selected_site_uri;

		// Note: if an anchor on the current page is selected (i.e., uri == "#anchor"),
		// the RSS tab will not be selected here, but rather once the anchors are loaded.

		if ( not_found || !this._use_rss )
		{
			this._select_custom_or_email_tab();
		}
		else
		{
			this._tabset.select_tab('rss');
			this._initialize_link_information('rss');
		}

		this._load_sites(this._sites_feed);
	};

	this._select_custom_or_email_tab = function()
	{
		var is_email_address = 
			( this._initially_selected_item.uri.match != null &&
			  this._initially_selected_item.uri.match( new RegExp('mailto:', 'i') ) );

		if ( is_email_address )
		{
			this._tabset.select_tab('email');
			this._initialize_link_information('email');
		}
		else
		{
			this._tabset.select_tab('custom');
			this._initialize_link_information('custom');
		}
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
	 * XXX At some point it might make sense to hack more
	 * on Util.Select to avoid this bug altogether. I think
	 * the solution would be to never add or remove options
	 * from a displayed select--but hiding and reshowing
	 * the selects gets complicated because so much in
	 * this dialog is done asynchronously.
	 */
	this._workaround_ie_select_display_bug = function()
	{
		if ( document.all ) // XXX
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
		var tab_names = ['rss', 'custom', 'email'];
		for ( var i in tab_names )
		{
			this._link_information[i] = 
			{
				link_title : '',
				new_window : ''
			}
		}

		// set given tab to initial values
		this._link_information[tab_name] =
		{
			link_title : this._initially_selected_item.title,
			new_window : this._initially_selected_item.new_window
		}

		this._link_title_input.value = this._initially_selected_item.title;
		this._new_window_checkbox.checked = this._initially_selected_item.new_window;
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
			this._link_title_input.value = this._link_information[new_name].link_title;
			this._new_window_checkbox.checked = this._link_information[new_name].new_window;
		}
		else
		{
			this._link_title_input.value = '';
			this._new_window_checkbox.checked = false;
		}
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


/*

#
# do this in apply_initially_selected_item
#
On first time ...
Is uri empty?
{
	Show rss tab
	Call "load other rss feeds" below.
}
Is uri not empty?
{
	Load finder.
	#
	# do this in a listener
	#
	Is not found
	{
		Is email address
		{
			Show email tab
		}
		Is not email address
		{
			Show custom tab
		}
	}
	Is found?
	{
		Show rss tab
	}
	Call "load other rss feeds" below.
}

Load other rss feeds.
If initially selected uri not found
{
	Select defaults
}
Else
{
	Select as indicated by finder
}

*/
