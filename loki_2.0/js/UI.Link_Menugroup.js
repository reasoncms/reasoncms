/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing a clipboard menugroup. 
 */
UI.Link_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._link_helper = (new UI.Link_Helper).init(this._loki);
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var menuitems = [];

		var selected_item = this._link_helper.get_selected_item();
		if ( selected_item != null && selected_item.uri != '' )
		{
			var self = this;
			menuitems.push( (new UI.Menuitem).init({
				label : 'Edit link', 
				//listener : function() { self._link_helper.open_dialog_by_context() } 
				listener : function() { self._link_helper.open_page_link_dialog() } 
			}) );

			menuitems.push( (new UI.Separator_Menuitem).init() );
		}
		else if ( this._link_helper.check_for_linkable_selection() )
		{
			var self = this;
			menuitems.push( (new UI.Menuitem).init({
				label : 'Create link', 
				//listener : function() { self._link_helper.open_dialog_by_context() } 
				listener : function() { self._link_helper.open_page_link_dialog() } 
			}) );

			menuitems.push( (new UI.Separator_Menuitem).init() );
		}
		return menuitems;
	};
};
