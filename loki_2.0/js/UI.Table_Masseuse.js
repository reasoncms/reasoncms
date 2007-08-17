/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for massaging a table.
 */
UI.Table_Masseuse = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Masseuse);

	var _empty_th_text = 'Column title';

	this.massage_node_descendants = function(node)
	{
		var tables = node.getElementsByTagName('TABLE');
		for ( var i = 0; i < tables.length; i++ )
		{
			self.massage_elem(tables[i]);
		}
	};
	
	this.unmassage_node_descendants = function(node)
	{
		var tables = node.getElementsByTagName('TABLE');
		for ( var i = 0; i < tables.length; i++ )
		{
			self.unmassage_elem(tables[i]);
		}
	};

	this.massage_elem = function(table)
	{
		if ( table.getAttribute('border') == null ||
		     table.getAttribute('border') == 0 )
		{
			Util.Element.add_class(table, 'loki__borderless_table');
		}

		// Add trailing <br /> in Gecko, for better display and editing
		if ( !document.all ) // XXX bad
		{
			// First, try innerHTML
			var h = table.innerHTML;
			// XXX: should this really be (h != null && h != '')? -EN
			if ( h == '' || h == null )
			{
				h.replace( new RegExp('(<td[ ]?[^>]*>)[ ]*(</td>)', 'g'), '$1<br />$2' );
				h.replace( new RegExp('(<th[ ]?[^>]*>)[ ]*(</th>)', 'g'), '$1<br />$2' );
				table.innerHTML = h;
			}
			// But sometimes (namely, when the table is first created in Gecko), 
			// innerHTML is mysteriously not available. In that case, we use the
			// slower DOM method, which on large tables can cause Gecko to display
			// the "Something is causing this script to run slowly; do you want to 
			// kill it?" alert:
			for ( var i = 0; i < table.rows.length; i++ )
			{
				var row = table.rows[i];
				for ( var j = 0; j < row.cells.length; j++ )
				{
					var cell = row.cells[j];
					if ( !( cell.lastChild != null &&
						    cell.lastChild.nodeType == Util.Node.ELEMENT_NODE &&
						    cell.lastChild.tagName == 'BR' ) )
					{
						cell.appendChild( cell.ownerDocument.createElement('BR') );
					}
				}
			}
		}

		// Add header to the table if there is none
		var thead = table.createTHead();
		var tbody = table.getElementsByTagName('TBODY')[0];
		if ( thead.rows.length == 0 )
		{
			thead.insertRow(-1);
			for ( var i = 0; i < tbody.rows[0].cells.length; i++ )
			{
				var th = thead.ownerDocument.createElement('TH');
				thead.rows[0].appendChild(th);
			}
		}

		// Add header text templates to empty THs
		var ths = table.getElementsByTagName('TH');
		var empty_regexp = new RegExp('^([ ]|&nbsp;|<br>|<br[^>]*>)+$', '');
		for ( var i = 0; i < ths.length; i++ )
		{
			// the empty regexp test alone won't catch the newly 
			// created THs from above, b/c their innerHTML isn't yet
			// available in Gecko (see note above)
			if ( ths[i].firstChild == null || empty_regexp.test(ths[i].innerHTML) )
			{
				ths[i].innerHTML = _empty_th_text;
				/*
				ths[i].onmouseover = function() {
				//Util.Event.add_event_listener(ths[i], 'mouseover', function() {
					alert('asdf');
					if ( ths[i].innerHTML == 'Column title' )
						ths[i].innerHTML = '';
				};
				ths[i].onmouseout = function() {
				//Util.Event.add_event_listener(ths[i], 'mouseout', function() {
					if ( empty_regexp.test(ths[i].innerHTML) )
						ths[i].innerHTML = '';
				};
				*/
			}
		}
	};

	this.unmassage_elem = function(table)
	{
		Util.Element.remove_class(table, 'loki__borderless_table');

		// Remove trailing <br /> in Gecko
		if ( !document.all ) // XXX bad bad bad
		{
			var h = table.innerHTML;
			h.replace( new RegExp('<br />(</td>)', 'g'), '<br />$1' );
			h.replace( new RegExp('<br />(</th>)', 'g'), '<br />$1' );
			table.innerHTML = h;

			/*
			for ( var i = 0; i < table.rows.length; i++ )
			{
				var row = table.rows[i];
				for ( var j = 0; j < row.cells.length; j++ )
				{
					var cell = row.cells[j];
					if ( cell.lastChild != null &&
						 cell.lastChild.nodeType == Util.Node.ELEMENT_NODE &&
						 cell.lastChild.tagName == 'BR' )
					{
						cell.removeChild(cell.lastChild);
					}
				}
			}
			*/
		}
	};
};
