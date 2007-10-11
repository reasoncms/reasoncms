/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for making horizontal rule elements easier to delete.
 */
UI.HR_Masseuse = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Masseuse);
	
	this.massage_node_descendants = function(node)
	{
		Util.Array.for_each(node.getElementsByTagName('HR'),
			self.massage_node, self);
	};
	
	this.unmassage_node_descendants = function(node)
	{
		var div_elements = Util.Array.from(node.getElementsByTagName('DIV'));
		
		div_elements.each(function(div) {
			if (div.getAttribute('loki:container') == 'hr') {
				this.unmassage_node(div);
			}
		}, self);
	};
	
	this.massage_node = function(node)
	{
		var container = self._create_container(node);
		node.parentNode.replaceChild(container, node);
		container.appendChild(node);
		self._add_delete_button(container);
	};
	
	this.wrap = function(node)
	{
		var container = self._create_container(node);
		container.appendChild(node);
		self._add_delete_button(container);
		
		return container;
	};
	
	this.unmassage_node = function(node)
	{
		var r = self.get_real(node) || node.ownerDocument.createElement('HR');
		node.parentNode.replaceChild(r, node);
	};
	
	this.get_real = function(node)
	{
		return Util.Node.get_last_child_node(node,
			Util.Node.curry_is_tag('HR'));
	}
	
	this._create_container = function(node)
	{
		var div = node.ownerDocument.createElement('DIV');
		Util.Element.add_class(div, 'loki__hr_container');
		div.setAttribute('loki:fake', 'true');
		div.setAttribute('loki:container', 'hr');
		return div;
	};
	
	this._add_delete_button = function(container)
	{
		var doc = container.ownerDocument;
		var link = doc.createElement('A');
		link.href = '#';
		link.title = 'Click to remove this horizontal line.'
		Util.Element.add_class(link, 'loki__delete');
		
		var span = doc.createElement('SPAN');
		span.appendChild(doc.createTextNode('Remove'));
		link.appendChild(span);
		
		Util.Event.add_event_listener(container, 'mouseover', function() {
			link.style.display = 'block';
		});
		
		Util.Event.add_event_listener(container, 'mouseout', function() {
			link.style.display = '';
		});
		
		Util.Event.add_event_listener(link, 'click', function(e) {
			if (!e) var e = window.event;
			
			container.parentNode.removeChild(container);
			
			return Util.Event.prevent_default(e);
		})
		
		container.appendChild(link);
	};
};