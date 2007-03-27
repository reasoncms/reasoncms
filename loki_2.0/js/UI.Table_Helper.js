/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class for helping insert an table. Contains code
 * common to both the button and the menu item. 
 * 
 * Note: keep in mind that table.createTHead() creates _or gets_ 
 * the table's THEAD elem.
 */
UI.Table_Helper = function()
{
	var self = this;
	Util.OOP.inherits(self, UI.Helper);

	this.init = function(loki)
	{
		this._loki = loki;
		this._table_masseuse = (new UI.Table_Masseuse()).init(self._loki);
		return this;
	};

	this.is_table_selected = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE') != null;
	};

	var _cell_boolean_test = function(node)
	{
		return ( node.nodeType == Util.Node.ELEMENT_NODE &&
				 ( node.tagName == 'TD' || node.tagName == 'TH' ) );
	};

	this.is_cell_selected = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return Util.Range.get_nearest_ancestor_node(rng, _cell_boolean_test) != null;
	};

	/**
	 * use is_cell_selected unless you want TD specifically 
	 */
	this.is_td_selected = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TD') != null;
	};

	/**
	 * use is_cell_selected unless you want TH specifically 
	 */
	this.is_th_selected = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		return Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TH') != null;
	};

	this.get_selected_table_item = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);

		var selected_item;
		var selected_table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
		if ( selected_table != null )
		{
			var selected_tbody = selected_table.getElementsByTagName('TBODY')[0];

			/* Uncomment if bgs are reinstated
			var bg, classes = Util.Element.get_all_classes(selected_table).split(' ');
			for ( var i = 0; i < classes.length; i++ )
				if ( classes[i].indexOf('bg') === 0 )
					bg = classes[i];
			*/
			// Check whether any bg is present at all (for legacy)
			var classes_str = Util.Element.get_all_classes(selected_table) + ' ' +
			                  Util.Element.get_all_classes(selected_table.rows[0].cells[0]);
			var classes = classes_str.split(' ');
			var bg;
			for ( var i = 0; i < classes.length; i++ )
				if ( classes[i].indexOf('bg') === 0 )
					bg = true;

			selected_item = { rows : selected_tbody.rows.length,
			                  cols : selected_tbody.rows[0].cells.length,
			                  border : selected_table.getAttribute('border') > 0,
			                  desc : selected_table.getAttribute('summary'), 
			                  bg : bg,
			                  is_new : false };
		}
		else
		{
			selected_item = { rows : 2, 
			                  cols : 3, 
			                  border : false, 
			                  desc : null,
			                  bg : false,
			                  is_new : true };
		}

		return selected_item;
	};

	this.get_thead_rows = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var selected_table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
		return selected_table.createTHead().rows;
	};

	this.get_selected_cell_item = function(tagname)
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);

		var selected_item;
		var selected_cell = Util.Range.get_nearest_ancestor_node(rng, _cell_boolean_test);
		if ( selected_cell != null )
			selected_item = { align : selected_cell.getAttribute('align'),
			                  valign : selected_cell.getAttribute('valign'),
			                  wrap : selected_cell.getAttribute('noWrap') == null || selected_cell.getAttribute('noWrap') == '' ? 'yes' : 'no' };
			//selected_item = { colspan : selected_cell.getAttribute('colspan'),
			//                  rowspan : selected_cell.getAttribute('rowspan') };
		else
			selected_item = { align : 'left', 
			                  valign : 'top',
			                  wrap : 'yes' };

		return selected_item;
	};

	this.open_table_dialog = function()
	{
		var selected_item = self.get_selected_table_item();

		if ( this._table_dialog == null )
			this._table_dialog = new UI.Table_Dialog;
		this._table_dialog.init({ base_uri : self._loki.settings.base_uri,
							submit_listener : self.insert_table,
							remove_listener : self.remove_table,
							selected_item : selected_item });
		this._table_dialog.open();
	};

	this.open_cell_dialog = function()
	{
		var selected_item = self.get_selected_cell_item();

		if ( this._cell_dialog == null )
			this._cell_dialog = new UI.Cell_Dialog;
		this._cell_dialog.init({ base_uri : self._loki.settings.base_uri,
						 submit_listener : self.update_cell,
						 selected_item : selected_item });
		this._cell_dialog.open();
	};

	/**
	 * Adds a tr to the given tbody after the given row index.
	 * Index of -1 to insert at end.
	 * tbody doesn't actually have to be a tbody--it can be a thead (or table), too.
	 * Returns the tr.
	 */
	var _insert_tr = function(tbody, index)
	{
		return tbody.insertRow(index);
	};

	/**
	 * Adds a td to the given tr after the given cell index.
	 * Index of -1 to insert at end.
	 * Returns the td.
	 */
	var _insert_td = function(tr, index)
	{
		var td = tr.ownerDocument.createElement('TD');
		td.setAttribute('align', 'left');
		td.setAttribute('valign', 'top');
		if ( index == -1 || index >= tr.childNodes.length )
			tr.appendChild(td);
		else
			tr.insertBefore(td, tr.childNodes[index]);
		return td;
	};

	/**
	 * Adds a th to the given tr after the given cell index.
	 * Index of -1 to insert at end.
	 * Returns the td.
	 */
	var _insert_th = function(tr, index)
	{
		var td = tr.ownerDocument.createElement('TH');
		//td.setAttribute('align', 'left');
		td.setAttribute('valign', 'top');
		if ( index == -1 || index >= tr.childNodes.length )
			tr.appendChild(td);
		else
			tr.insertBefore(td, tr.childNodes[index]);
		return td;
	};

	this.insert_table = function(table_info)
	{
		if ( self.is_table_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tbody = table.getElementsByTagName('TBODY')[0];

			table.setAttribute('border', table_info.border ? '1' : '0');
			table.setAttribute('summary', table_info.desc);

			/* Uncomment if bgs are reinstated
			var classes = Util.Element.get_all_classes(table).split(' ');
			for ( var i = 0; i < classes.length; i++ )
				if ( classes[i].indexOf('bg') === 0 )
					Util.Element.remove_class(table, classes[i]);
			Util.Element.add_class(table, table_info.bg);
			*/
			// Remove bg color if asked (for legacy)
			if ( table_info.bg == false )
			{
				var classes_str = Util.Element.get_all_classes(table);
				if ( classes_str != null )
				{
					var classes = classes_str.split(' ');
					for ( var i = 0; i < classes.length; i++ )
						if ( classes[i].indexOf('bg') === 0 )
							Util.Element.remove_class(table, classes[i]);
				}
			}

			// Update rows and cols
			var old_info = self.get_selected_table_item();
			for ( var i = old_info.rows; i < table_info.rows; i++ )
				_actually_insert_row(tbody, i);
			for ( var i = old_info.cols; i < table_info.cols; i++ )
				_actually_insert_column(table, i);
		}
		else
		{
			// Create the table
			var table = self._loki.document.createElement('TABLE');
			table.setAttribute('cellpadding', '5');
			table.setAttribute('cellspacing', '0');
			table.setAttribute('border', table_info.border ? '1' : '0');
			table.setAttribute('summary', table_info.desc);
			/* Uncomment if bgs are reinstated
			Util.Element.add_class(table, table_info.bg);
			*/

			// ... and tbody and thead
			var tbody = self._loki.document.createElement('TBODY');
			table.appendChild(tbody);
			var thead = table.createTHead();

			// Populate the table ... with a row of ths ...
			var tr = _insert_tr(thead, -1);
			for ( var j = 0; j < table_info.cols; j++ )
			{
				_insert_th(tr, -1);
			}
			// ... and rows of tds
			for ( var i = 0; i < table_info.rows; i++ )
			{
				var tr = _insert_tr(tbody, -1);
				for ( var j = 0; j < table_info.cols; j++ )
				{
					_insert_td(tr, -1);
				}
			}

			// Insert the table
			var sel = Util.Selection.get_selection(self._loki.window);
			Util.Selection.paste_node(sel, table);
			self._loki.window.focus();
		}

		self._table_masseuse.massage_elem(table);
	};

	this.update_cell = function(cell_info)
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var cell = Util.Range.get_nearest_ancestor_node(rng, _cell_boolean_test);

			cell.setAttribute('align', cell_info.align);
			cell.setAttribute('valign', cell_info.valign);

			if ( cell_info.wrap == 'yes' )
			{
				//if ( cell.getAttribute('noWrap') != '' )
					cell.removeAttribute('noWrap');
			}
			else
				cell.setAttribute('noWrap', 'noWrap');

			/* NB: this was commented before bgs were gotten rid of.
			   If bgs are reinstated, keep this commented.
			var classes = (Util.Element.get_all_classes(cell) == null ? '' : Util.Element.get_all_classes(cell)).split(' ');
			for ( var i = 0; i < classes.length; i++ )
				if ( classes[i].indexOf('bg') === 0 )
					Util.Element.remove_class(cell, classes[i]);
			Util.Element.add_class(cell, cell_info.bg);
			*/
		}
		self._table_masseuse.massage_elem(table);
	};

	function _get_column_index(tr, td)
	{
		var col_index;
		for ( var i = 0; i < tr.cells.length; i++ )
		{
			if ( tr.cells[i] == td )
				col_index = i;
		}
		return col_index;
	}

	var _actually_insert_column = function(table, col_index)
	{
		var thead = table.createTHead();
		var tbody = table.getElementsByTagName('TBODY')[0];
		
		for ( var i = 0; i < thead.rows.length; i++ )
		{
			var index = thead.rows[i].cells[col_index - 1] != null ? col_index : -1;
			var new_th = _insert_th(thead.rows[i], col_index);
		}
		for ( var i = 0; i < tbody.rows.length; i++ )
		{
			var index = tbody.rows[i].cells[col_index - 1] != null ? col_index : -1;
			var new_td = _insert_td(tbody.rows[i], col_index);
		}
	};

	this.insert_column = function()
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TR');
			var cell = Util.Range.get_nearest_ancestor_node(rng, _cell_boolean_test);
			var col_index = _get_column_index(tr, cell) + 1;
	
			_actually_insert_column(table, col_index);
		}
		self._table_masseuse.massage_elem(table);
	};

	this.delete_column = function()
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TR');
			var cell = Util.Range.get_nearest_ancestor_node(rng, _cell_boolean_test);
			var col_index = _get_column_index(tr, cell);

			for ( var i = 0; i < table.rows.length; i++ )
			{
				// this needed to manage colspans across multiple columns
				var cur_row = table.rows[i];
				var cur_cell = cur_row.cells[col_index];
				if ( cur_cell.colSpan != 1 )
				{
					colspan = cur_cell.getAttribute("colspan");
					var new_cell = cur_row.insertCell(iCol+1);
					new_cell.colSpan = colspan - 1;
					new_cell.innerHTML = cur_cell.innerHTML; // XXX: should clone children instead
				}
		
				try { table.rows[i].deleteCell(col_index); } catch(e) {}		
			}
		}
		self._table_masseuse.massage_elem(table);
	};

	this.merge_columns = function()
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TR');
			var cell = Util.Range.get_nearest_ancestor_node(rng, _cell_boolean_test);
			var next_cell = cell.nextSibling;

			if ( next_cell != null )
			{
				var colspan1 = cell.getAttribute("colspan");
				var colspan2 = next_cell.getAttribute("colspan");
		
				cell.colspan = colspan1 + colspan2;
				cell.innerHTML += next_cell.innerHTML;
				table.rows[tr.rowIndex].deleteCell(next_cell.cellIndex);
			}
		}
		self._table_masseuse.massage_elem(table);
	};

	function _get_num_of_columns(tbody)
	{
		var n_colspan = 0;
		var n_cols = tbody.rows[0].cells.length;
		for (var i = 0; i < n_cols; i++ )
		{
			n_colspan += tbody.rows[0].cells[i].colSpan;
		}
		return n_colspan;
	}

	var _actually_insert_row = function(tbody, row_index)
	{
		var num_of_cols = _get_num_of_columns(tbody);
		var new_tr = _insert_tr(tbody, row_index);
		for ( var i = 0; i < num_of_cols; i++ )
		{
			_insert_td(new_tr, i);
		}
	};

	this.insert_row = function()
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tbody = table.getElementsByTagName('TBODY')[0];
			var thead = table.createTHead();
			var tr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TR');
			var row_index = tr.rowIndex - thead.rows.length + 1;

			_actually_insert_row(tbody, row_index);
		}
		self._table_masseuse.massage_elem(table);
	};

	this.delete_row = function()
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TR');
			table.deleteRow(tr.rowIndex);
		}
		self._table_masseuse.massage_elem(table);
	};

	this.convert_row_to_header = function()
	{
		if ( self.is_cell_selected() )
		{
			var sel = Util.Selection.get_selection(self._loki.window);
			var rng = Util.Range.create_range(sel);
			var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');
			var tr = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TR');
			var thead = table.createTHead();
			var thead_tr = table.ownerDocument.createElement('TR');
			thead.appendChild(thead_tr);
			for ( var i = 0; i < tr.cells.length; i++ )
			{
				var td = tr.cells[i];
				var th = table.ownerDocument.createElement('TH');
				while ( td.firstChild != null )
					th.appendChild( td.removeChild(td.firstChild ) );
				thead_tr.appendChild(th);
			}
			table.deleteRow(tr.rowIndex);
		}
		self._table_masseuse.massage_elem(table);
	};

	this.remove_table = function()
	{
		var sel = Util.Selection.get_selection(self._loki.window);
		var rng = Util.Range.create_range(sel);
		var table = Util.Range.get_nearest_ancestor_element_by_tag_name(rng, 'TABLE');

		// Move cursor
		Util.Selection.select_node(sel, table);
		Util.Selection.collapse(sel, false); // to end
		self._loki.window.focus();

		if ( table.parentNode != null )
			table.parentNode.removeChild(table);
	};
};

/*

Public methods:
--------------
insert_table
insert_row
insert_column
convert_row_to_header
delete_row
delete_column
update_table_attrs
update_td_attrs

The general approach:
--------------------
Make the real element, then masseuse.get_fake_elem, then append that.

*/
