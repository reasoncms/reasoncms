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
	 * Opens the mail link dialog.
	 */
	this.open_mail_link_dialog = function()
	{
		if ( !this._check_for_selected_text() )
			return;

		if ( this._mail_link_dialog == null )
			this._mail_link_dialog = new UI.Mail_Link_Dialog();
		this._mail_link_dialog.init({ base_uri : this._loki.settings.base_uri,
						    		  submit_listener : this.insert_link,
						    		  selected_item : this.get_selected_item() });
		this._mail_link_dialog.open();
	};

	/**
	 * Opens the appropriate link dialog depending on 
	 * the context of the current selection.
	 */
	this.open_dialog_by_context = function()
	{
		var selected_item = this.get_selected_item();
		if ( selected_item != null &&
			 selected_item.uri != null &&
			 selected_item.uri.match(new RegExp('mailto\:', 'i')) != null )
		{
			this.open_mail_link_dialog();
		}
		else
		{
			this.open_page_link_dialog();
		}
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
		var new_window = params.new_window == null ? false : params.new_window;
		var title = params.title == null ? '' : params.title;
		var onclick = params.onclick == null ? '' : params.onclick;
		var a_tag;

		mb('uri', uri);

		// If the selection is inside an existing link, select that link
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var ancestor = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'A');
		if ( ancestor != null && ancestor.getAttribute('href') != null )
		{
			//Util.Selection.select_node(sel, ancestor);
			a_tag = ancestor;
		}
		else
		{
			// If the selection is collapsed, insert either the title (if
			// given) or the uri, and select that, so that it is used as the
			// link's text--otherwise, in Gecko no link will be created,
			// and in IE, the uri will be used as link text even if a title
			// is available.
			// - Now we check this above, before the dialog is opened, in 
			//   check_for_selected_text()
			/*
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			if ( Util.Selection.is_collapsed(sel) )
			{
				var text = title == '' ? uri : title;
				var text_node = self._loki.document.createTextNode(text);
				Util.Range.insert_node(rng, text_node);
				Util.Selection.select_node(sel, text_node);
			}
			*/

			self._loki.exec_command('CreateLink', false, 'hel_temp_uri');
			var links = self._loki.document.getElementsByTagName('a');
			for (var i = 0; i < links.length; i++)
			{
				if ( links.item(i).getAttribute('href') == 'hel_temp_uri')
				{
					a_tag = links.item(i);
				}
			}
		}

		// if URI is not given, remove the link entirely
		if ( uri == '' )
		{
			Util.Node.replace_with_children(a_tag);
		}
		// otherwise, actually add/update link attributes
		else
		{
			a_tag.setAttribute('href', uri);

			if ( new_window == true )
				a_tag.setAttribute('target', '_blank');
			else
				a_tag.removeAttribute('target');

			if ( title != '' )
				a_tag.setAttribute('title', title);
			else
				a_tag.removeAttribute('title');

			if ( onclick != '' )
				a_tag.setAttribute('loki:onclick', onclick);
			else
				a_tag.removeAttribute('loki:onclick');

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
