/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for inserting an anchor.
 */
UI.Anchor_Masseuse = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Masseuse);

	/**
	 * Massages the given node's children, replacing any named anchors with
	 * fake images.
	 */
	this.massage_node_descendants = function(node)
	{
		var anchors = node.getElementsByTagName('A');
		for ( var i = anchors.length - 1; i >= 0; i-- )
		{
			if ( anchors[i].getAttribute('name') ) //&& anchors[i].href == null )
			{
				var fake = self.get_fake_elem(anchors[i]);
				anchors[i].parentNode.replaceChild(fake, anchors[i]);
			}
		}
	};

	/**
	 * Unmassages the given node's descendants, replacing any fake anchor images 
	 * with real anchor elements.
	 */
	this.unmassage_node_descendants = function(node)
	{
		var tagnames = ['A', 'IMG'];
		for ( var j in tagnames )
		{
			var dummies = node.getElementsByTagName(tagnames[j]);
			for ( var i = dummies.length - 1; i >= 0; i-- )
			{
				if ( dummies[i].getAttribute('loki:anchor_name') )
				{
					var real = self.get_real_elem(dummies[i]);
					dummies[i].parentNode.replaceChild(real, dummies[i])
				}
			}
		}
	};

	/**
	 * Returns a fake element for the given anchor.
	 */
	this.get_fake_elem = function(anchor)
	{
		if (anchor == null)
			return null;
		
		var dummy = anchor.ownerDocument.createElement('IMG');
		Util.Element.add_class(dummy, 'loki__named_anchor');
		dummy.title = anchor.name;
		dummy.setAttribute('loki:fake', 'true');
		dummy.setAttribute('loki:anchor_name', anchor.name);
		dummy.src = self._loki.settings.base_uri + 'images/nav/anchor.gif';
		dummy.width = 12;
		dummy.height = 12;
		return dummy;
	};

	/**
	 * If the given fake element is really fake, returns the appropriate 
	 * real anchor. Else, returns null.
	 */
	this.get_real_elem = function(dummy)
	{
		if (dummy == null || dummy.getAttribute('loki:fake') != 'true') {
			return null;
		}
		
		var anchor_name = dummy.getAttribute('loki:anchor_name');
		if (!anchor_name)
			return null;
		
		return Util.Anchor.create_named_anchor(
			{document: dummy.ownerDocument, name: anchor_name}
		);
	};
};
