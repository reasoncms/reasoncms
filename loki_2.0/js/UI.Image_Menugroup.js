/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing a menugroup. 
 */
UI.Image_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._helper = (new UI.Image_Helper).init(this._loki);
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var self = this;
		var menuitems = [];

		var selected_item = this._helper.get_selected_item();
		if ( selected_item != null )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Edit image',
				listener : function() { self._helper.open_dialog() }
			}) );

			menuitems.push( (new UI.Separator_Menuitem).init() );
		}

		return menuitems;
	};
};
