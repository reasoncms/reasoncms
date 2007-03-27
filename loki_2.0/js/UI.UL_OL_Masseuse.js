/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for massaging a table.
 */
UI.UL_OL_Masseuse = function()
{
	var self = this;
	var _tagnames = ['UL', 'OL'];
	Util.OOP.inherits(self, UI.Masseuse);

	this.massage_node_descendants = function(node)
	{
		for ( var j in _tagnames )
		{
			var uls = node.getElementsByTagName(_tagnames[j]);
			for ( var i = 0; i < uls.length; i++ )
			{
				self.massage_elem(uls[i]);
			}
		}
	};
	
	this.unmassage_node_descendants = function(node)
	{
		for ( var j in _tagnames )
		{
			var uls = node.getElementsByTagName(_tagnames[j]);
			for ( var i = 0; i < uls.length; i++ )
			{
				self.unmassage_elem(uls[i]);
			}
		}
	};

	this.massage_elem = function(ul)
	{
		// <ul><li>out<ul><li>in</li></ul></li><li>out again</li></ul>
		//   -->
		// <ul><li>out</li><ul><li>in</li></ul><li>out again</li></ul>
		if ( ul.parentNode.nodeName == 'LI' )
		{
			var old_li = ul.parentNode;
			if ( old_li.nextSibling == null )
				old_li.parentNode.appendChild(ul);
			else
				old_li.parentNode.insertBefore(ul, old_li.nextSibling);
		}
	};

	this.unmassage_elem = function(ul)
	{
		// <ul><li>out</li><ul><li>in</li></ul><li>out again</li></ul>
		//   -->
		// <ul><li>out<ul><li>in</li></ul></li><li>out again</li></ul>
		if ( ul.parentNode.nodeName == 'UL' || ul.parentNode.nodeName == 'OL' )
		{
			var prev_li = ul.previousSibling;
			if ( prev_li != null )
			{
				prev_li.appendChild(ul);
			}
			else
			{
				var new_li = ul.ownerDocument.createElement('LI');
				ul.parentNode.insertBefore(new_li, ul);
				new_li.appendChild(ul);
			}
		}
	};
};
