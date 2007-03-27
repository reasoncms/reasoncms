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
				if ( dummies[i].getAttribute('loki:is_really_a_named_anchor_whose_name_is') )
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
		//if ( document.all ) // XXX bad
		if ( true )
		{
			var dummy = anchor.ownerDocument.createElement('IMG');
			Util.Element.add_class(dummy, 'loki__named_anchor'); // changed while in if (false)
			dummy.title = anchor.name;
			dummy.setAttribute('loki:is_really_a_named_anchor_whose_name_is', anchor.name);
			dummy.src = self._loki.settings.base_uri + 'images/nav/anchor.gif';
			dummy.width = 12;
			dummy.height = 12;
			return dummy;
		}
		else
		{
			var dummy = anchor.ownerDocument.createElement('A');
			Util.Element.add_class(dummy, 'loki__named_anchor');
			//dummy.className = 'loki__named_anchor';
			dummy.title = anchor.name;
			dummy.setAttribute('loki:is_really_a_named_anchor_whose_name_is', anchor.name);
			return dummy;
		}
	};

	/**
	 * If the given fake element is really fake, returns the appropriate 
	 * real anchor. Else, returns null.
	 */
	this.get_real_elem = function(dummy)
	{
		if ( dummy != null )
		{
			var anchor_name = dummy.getAttribute('loki:is_really_a_named_anchor_whose_name_is');
			if ( anchor_name )
			{
				var anchor = Util.Anchor.create_named_anchor({document : dummy.ownerDocument, name : anchor_name});
				//	dummy.ownerDocument.createElement('A');
				//anchor.setAttribute('name', anchor_name); // find out why this doesn't work 
				return anchor;
			}
		}
		return null;
	};
};
