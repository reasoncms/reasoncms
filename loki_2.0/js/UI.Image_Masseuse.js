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
		this._unsecured = new RegExp('^http:', '');
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
		var src = img.getAttribute('src');
		if (src == null)
			return;
		
		if (self._unsecured.test(src)) {
			if (Util.URI.extract_domain(src) == self._loki.editor_domain())
				new_src = Util.URI.strip_https_and_http(src);
			else if (self._loki.settings.sanitize_unsecured)
				new_src = self._loki.settings.base_uri +
					'images/insecure_image.gif';
			else
				return img;
			
			var placeholder = img.ownerDocument.createElement('IMG');
			placeholder.title = img.title;
			placeholder.alt = img.alt;
			placeholder.setAttribute('loki:src', img.src);
			placeholder.setAttribute('loki:fake', 'true');
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
		if (img == null || img.getAttribute('loki:fake') != 'true') {
			return null;
		}
		
		var src = img.getAttribute('loki:src');
		if (src == null)
			return img;
		
		var real = img.ownerDocument.createElement('IMG');
		real.title = img.title;
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
