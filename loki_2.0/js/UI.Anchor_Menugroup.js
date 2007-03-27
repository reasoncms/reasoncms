/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing a clipboard menugroup. 
 */
UI.Anchor_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._anchor_helper = (new UI.Anchor_Helper).init(this._loki);
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var menuitems = [];

		var selected_item = this._anchor_helper.get_selected_item();
		if ( selected_item != null )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Edit anchor',
				listener : this._anchor_helper.open_dialog 
			}) );

			menuitems.push( (new UI.Separator_Menuitem).init() );
		}

		return menuitems;
	};
};
