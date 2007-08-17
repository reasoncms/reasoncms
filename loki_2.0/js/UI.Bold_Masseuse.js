/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for massaging strong tags to b tags. The motivation for this is that 
 * you can't edit strong tags, but we want them in the final output.
 */
UI.Bold_Masseuse = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Masseuse);

	/**
	 * Massages the given node's children, replacing any named strongs with
	 * b elements.
	 */
	this.massage_node_descendants = function(node)
	{
		var strongs = node.getElementsByTagName('STRONG');
		for ( var i = strongs.length - 1; i >= 0; i-- )
		{
			var fake = self.get_fake_elem(strongs[i]);
			strongs[i].parentNode.replaceChild(fake, strongs[i]);
		}
	};

	/**
	 * Unmassages the given node's descendants, replacing any b elements
	 * with real strong elements.
	 */
	this.unmassage_node_descendants = function(node)
	{
		var dummies = node.getElementsByTagName('B');
		for ( var i = dummies.length - 1; i >= 0; i-- )
		{
			var real = self.get_real_elem(dummies[i]);
			dummies[i].parentNode.replaceChild(real, dummies[i])
		}
	};

	/**
	 * Returns a fake element for the given strong.
	 */
	this.get_fake_elem = function(strong)
	{
		var dummy = strong.ownerDocument.createElement('B');
		dummy.setAttribute('loki:fake', 'true');
		// maybe transfer attributes, too
		while ( strong.firstChild != null )
		{
			dummy.appendChild( strong.removeChild(strong.firstChild) );
		}
		return dummy;
	};

	/**
	 * If the given fake element is really fake, returns the appropriate 
	 * real strong. Else, returns null.
	 */
	this.get_real_elem = function(dummy)
	{
		if (dummy != null && dummy.nodeName == 'B') {
			var strong = dummy.ownerDocument.createElement('STRONG');
			// maybe transfer attributes, too
			while ( dummy.firstChild != null )
			{
				strong.appendChild( dummy.removeChild(dummy.firstChild) );
			}
			return strong;
		}
		return null;
	};
};
