/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for massaging em tags to i tags. The motivation for this is that 
 * you can't edit em tags, but we want them in the final output.
 */
UI.Italic_Masseuse = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Masseuse);

	/**
	 * Massages the given node's children, replacing any named ems with
	 * i elements.
	 */
	this.massage_node_descendants = function(node)
	{
		var ems = node.getElementsByTagName('EM');
		for ( var i = ems.length - 1; i >= 0; i-- )
		{
			var fake = self.get_fake_elem(ems[i]);
			ems[i].parentNode.replaceChild(fake, ems[i]);
		}
	};

	/**
	 * Unmassages the given node's descendants, replacing any i elements
	 * with real em elements.
	 */
	this.unmassage_node_descendants = function(node)
	{
		var dummies = node.getElementsByTagName('I');
		for ( var i = dummies.length - 1; i >= 0; i-- )
		{
			var real = self.get_real_elem(dummies[i]);
			dummies[i].parentNode.replaceChild(real, dummies[i])
		}
	};

	/**
	 * Returns a fake element for the given em.
	 */
	this.get_fake_elem = function(em)
	{
		var dummy = em.ownerDocument.createElement('I');
		dummy.setAttribute('loki:fake', 'true');
		// maybe transfer attributes, too
		while ( em.firstChild != null )
		{
			dummy.appendChild( em.removeChild(em.firstChild) );
		}
		return dummy;
	};

	/**
	 * If the given fake element is really fake, returns the appropriate 
	 * real em. Else, returns null.
	 */
	this.get_real_elem = function(dummy)
	{
		if (dummy != null && dummy.nodeName == 'I')
		{
			var em = dummy.ownerDocument.createElement('EM');
			// maybe transfer attributes, too
			while ( dummy.firstChild != null )
			{
				em.appendChild( dummy.removeChild(dummy.firstChild) );
			}
			return em;
		}
		return null;
	};
};
