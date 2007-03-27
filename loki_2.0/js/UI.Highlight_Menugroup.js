/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing an align menugroup. 
 */
UI.Highlight_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._helper = (new UI.Blockquote_Highlight_Helper).init(this._loki, 'highlight');
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
		if ( this._helper.is_blockquoteable() )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Highlight',
				listener : function() { self._helper.toggle_blockquote_paragraph(); }
			}) );

			menuitems.push( (new UI.Separator_Menuitem).init() );
		}

		return menuitems;
	};
};
