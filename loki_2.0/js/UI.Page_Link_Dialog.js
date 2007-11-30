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
		
		this._sites_progress = this.create_activity_indicator('textual', 'Loading sites…');
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
		// Get URI

		var uri;
		if ( this._tabset.get_name_of_selected_tab() == 'rss' )
		{
			uri = this.item_selector.get_uri();
			if (!uri) {
				this._dialog_window.window.alert('Please select a page to be linked to.');
				return false;
			}
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
		if (!value) {
			this._link_title_input.value = '';
			return;
		}
		
		/* Strip any number of hyphens and spaces from beginning of title */
		while(value.indexOf('-') == 0 || value.indexOf(' ') == 0)
		{
			value = value.substring(1);
		}
		this._link_title_input.value = value;
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
