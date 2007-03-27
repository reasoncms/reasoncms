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

	/**
	 * Massages the given node's descendants.
	 */
	this.massage_node_descendants = function(node)
	{
		var images = node.getElementsByTagName('IMG');
		for ( var i = images.length - 1; i >= 0; i-- )
		{
			if ( images[i].getAttribute('src') != null ) //&& images[i].href == null )
			{
				var fake = self.get_fake_elem(images[i]);
				images[i].parentNode.replaceChild(fake, images[i]);
			}
		}
	};

	/**
	 * Unmassages the given node's descendants.
	 */
	this.unmassage_node_descendants = function(node)
	{
		var dummies = node.getElementsByTagName('SPAN');
		for ( var i = dummies.length - 1; i >= 0; i-- )
		{
			if ( dummies[i].getAttribute('loki:is_really_an_image_whose_src_is') != null )
			{
				var real = self.get_real_elem(dummies[i]);
				dummies[i].parentNode.replaceChild(real, dummies[i])
			}
		}
	};

	/**
	 * Returns a fake element for the given image.
	 */
	this.get_fake_elem = function(image)
	{
		if ( document.all ) // XXX bad
		{
			/*
			var dummy = image.ownerDocument.createElement('IMG');
			Util.Element.add_class(dummy, 'loki__named_image'); // changed while in if (false)
			dummy.title = image.name;
			dummy.setAttribute('loki:is_really_an_image_whose_src_is', image.name);
			dummy.src = self._loki.settings.base_uri + 'images/nav/image.gif';
			dummy.width = 12;
			dummy.height = 12;
			return dummy;
			*/
			return image;
		}
		else
		{
			// get width and height
			image.style.position = 'absolute';
			image.style.left = '-10000px';
			image.ownerDocument.documentElement.appendChild(image);
			var width = image.clientWidth;
			var height = image.clientHeight;
			image.ownerDocument.documentElement.removeChild(image);

			var dummy = image.ownerDocument.createElement('A');
			Util.Element.add_class(dummy, 'loki__image');
			//dummy.className = 'loki__image';
			dummy.title = image.title;
			dummy.alt = image.alt;
			dummy.setAttribute('loki:is_really_an_image_whose_src_is', image.src);
			dummy.setAttribute('style', 'display: inline-block; width: ' + width + 'px; height: ' + height + 'px; line-height: ' + height + 'px; overflow: hidden; padding-left: ' + width + 'px; background-image: url("' + image.src + '"); background-position: bottom; background-repeat: no-repeat;');
			//dummy.style.width = width + 'px';
			//dummy.style.lineHeight = height + 'px';
			//dummy.innerHTML = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			//dummy.style.background = 'url("' + image.src + '");';
			return dummy;
		}
	};

	/**
	 * If the given fake element is really fake, returns the appropriate 
	 * real image. Else, returns null.
	 */
	this.get_real_elem = function(dummy)
	{
		if ( dummy != null )
		{
			var image_src = dummy.getAttribute('loki:is_really_an_image_whose_src_is');
			if ( image_src != null )
			{
				var image = dummy.ownerDocument.createElement('IMG');
				image.title = dummy.title;
				image.src = image_src;
				//image.width = 12;
				//image.height = 12;
				return image;
			}
		}
		return null;
	};
};
