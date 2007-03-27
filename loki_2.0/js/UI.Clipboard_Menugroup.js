/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing a clipboard menugroup. 
 */
UI.Clipboard_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._clipboard_helper = (new UI.Clipboard_Helper).init(this._loki);
		return this;
	};

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var menuitems = [];

		var self = this;
		menuitems.push( (new UI.Menuitem).init({ 
			label : 'Cut',
			listener : function()
			{
				try
				{
					self._clipboard_helper.cut();
				}
				catch(e)
				{
					self._clipboard_helper.alert_helpful_message();
					throw e;
				}
			},
			disabled : this._clipboard_helper.is_selection_empty()
		}) );
		menuitems.push( (new UI.Menuitem).init({ 
			label : 'Copy',
			listener : function()
			{
				try
				{
					self._clipboard_helper.copy();
				}
				catch(e)
				{
					self._clipboard_helper.alert_helpful_message();
					throw e;
				}
			},
			disabled : this._clipboard_helper.is_selection_empty()
		}) );
		menuitems.push( (new UI.Menuitem).init({ 
			label : 'Paste',
			listener : function()
			{
				try
				{
					self._clipboard_helper.paste();
				}
				catch(e)
				{
					self._clipboard_helper.alert_helpful_message();
					throw e;
				}
			}
			//disabled : this._clipboard_helper.is_selection_empty()
		}) );
		menuitems.push( (new UI.Menuitem).init({ 
			label : 'Delete',
			listener : this._clipboard_helper.delete_it,
			disabled : this._clipboard_helper.is_selection_empty()
		}) );

		menuitems.push( (new UI.Separator_Menuitem).init() );

		menuitems.push( (new UI.Menuitem).init({ 
			label : 'Select all',
			listener : this._clipboard_helper.select_all
		}) );

		return menuitems;
	};
};
