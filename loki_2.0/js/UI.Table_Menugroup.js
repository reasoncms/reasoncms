/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing a clipboard menugroup. 
 */
UI.Table_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._table_helper = (new UI.Table_Helper).init(this._loki);
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var menuitems = new Array();

		if ( this._table_helper.is_table_selected() )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Table properties',
				listener : this._table_helper.open_table_dialog 
			}) );
		}

		if ( this._table_helper.is_th_selected() )
		{
			var table_item = this._table_helper.get_selected_table_item();

			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Cell properties',
				listener : this._table_helper.open_cell_dialog 
			}) );

			menuitems.push( (new UI.Separator_Menuitem).init() );

			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Insert column',
				listener : this._table_helper.insert_column
			}) );

			if ( table_item.cols > 2 )
			{
				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Delete column',
					listener : this._table_helper.delete_column
				}) );

				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Merge columns',
					listener : this._table_helper.merge_columns
				}) );
			}

			menuitems.push( (new UI.Separator_Menuitem).init() );

			if ( this._table_helper.get_thead_rows().length > 1 )
			{
				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Delete row',
					listener : this._table_helper.delete_row 
				}) );
			}
		}

		if ( this._table_helper.is_td_selected() )
		{
			var table_item = this._table_helper.get_selected_table_item();

			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Cell properties',
				listener : this._table_helper.open_cell_dialog 
			}) );

			menuitems.push( (new UI.Separator_Menuitem).init() );

			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Insert column',
				listener : this._table_helper.insert_column
			}) );

			if ( table_item.cols > 2 )
			{
				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Delete column',
					listener : this._table_helper.delete_column
				}) );

				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Merge columns',
					listener : this._table_helper.merge_columns
				}) );
			}

			menuitems.push( (new UI.Separator_Menuitem).init() );

			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Insert row',
				listener : this._table_helper.insert_row 
			}) );

			if ( table_item.rows > 2 )
			{
				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Delete row',
					listener : this._table_helper.delete_row 
				}) );

				menuitems.push( (new UI.Menuitem).init({ 
					label : 'Convert row to header',
					listener : this._table_helper.convert_row_to_header
				}) );
			}
		}

		if ( this._table_helper.is_table_selected() )
		{
			menuitems.push( (new UI.Separator_Menuitem).init() );
		}

		return menuitems;
	};
};
