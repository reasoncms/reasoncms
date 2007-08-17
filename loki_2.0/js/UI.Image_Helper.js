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

	this.get_selected_item = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);

		var selected_item;
		var selected_image = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'IMG');
		if ( selected_image != null )
		{
			// Because we use an image as a stand-in for named anchors ...
			var anchor_helper = (new UI.Anchor_Helper).init(this._loki);
			if ( anchor_helper.get_selected_item() == null )
			{
				var real_image =
					self._image_masseuse.realize_elem(selected_image);
				selected_item =
				{
					uri : real_image.getAttribute('src'),
					alt : real_image.getAttribute('alt'),
					align : real_image.getAttribute('align')
				}; 
			}
		}
		return selected_item;
	};

	this.is_selected = function()
	{
		return ( this.get_selected_item() != null );
	};

	this.open_dialog = function()
	{
		if ( this._image_dialog == null )
			this._image_dialog = new UI.Image_Dialog;
		this._image_dialog.init({ data_source : self._loki.settings.images_feed,
							base_uri : self._loki.settings.base_uri,
							submit_listener : self.insert_image,
							remove_listener : self.remove_image,
							selected_item : this.get_selected_item() });
		this._image_dialog.open();
	};

	this.insert_image = function(image_info)
	{
		// Create the image
		var image = self._loki.document.createElement('IMG');
		var clean_src = UI.Clean.cleanURI(image_info.uri);
		image.setAttribute('src', clean_src);
		if (clean_src != image_info.uri)
			image.setAttribute('loki:src', image_info.uri);
		image.setAttribute('alt', image_info.alt);

		if ( image_info.align != '' )
			image.setAttribute('align', image_info.align);
		else
			image.removeAttribute('align');

		/*
			if ( image_info.border == 'yes' )
				Util.Element.add_class(image, 'bordered');
			else
				Util.Element.remove_class(image, 'bordered');
		*/

		// disallow resizing (only works in IE)
		/*
		//image.onclick = function(event) 
		image.onresize = function(event) 
		{
			event = event == null ? window.event : event;
			event.returnValue = false;
			return false;
		};
		*/

		// Massage the image
		image = self._image_masseuse.get_fake_elem(image);

		// Insert the image
		self._loki.window.focus();	
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		// We check for an image ancestor and replace it if found
		// because in Gecko, if an image in the document isn't found on the server
		// the ALT text will be displayed, and will be editable; in such
		// a case, the cursor can be inside an image--with the result 
		// that if one pastes the new image, it is nested in the original 
		// image rather than replacing it.
		var selected_image = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'IMG');
		if ( selected_image != null )
			selected_image.parentNode.replaceChild(image, selected_image);
		else
			Util.Selection.paste_node(sel, image);

		self._loki.window.focus();	
	};

	this.remove_image = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var image = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'IMG');

		// Move cursor
		Util.Selection.select_node(sel, image);
		Util.Selection.collapse(sel, false); // to end
		self._loki.window.focus();

		if ( image.parentNode != null )
			image.parentNode.removeChild(image);
	};
};
