/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing a headline menugroup. 
 */
UI.Headline_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	/**
	 * Returns an array of menuitems, depending on the current context.
	 * May return an empty array.
	 */
	this.get_contextual_menuitems = function()
	{
		var menuitems = [];

		var self = this;
		if ( this._is_h3() )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				//label : 'Subordinate headline',
				label : 'Change to minor heading (h4)',
				listener : function() { self._toggle_h4(); }
			}) );
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Remove headline',
				listener : function() { self._toggle_h3(); }
			}) );

			menuitems.push( (new UI.Separator_Menuitem).init() );
		}
		else if ( this._is_h4() )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				//label : 'Superordinate headline',
				label : 'Change to major heading (h3)',
				listener : function() { self._toggle_h3(); }
			}) );
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Remove headline',
				listener : function() { self._toggle_h4(); }
			}) );

			menuitems.push( (new UI.Separator_Menuitem).init() );
		}

		return menuitems;
	};

	this._toggle_h3 = function()
	{
		this._loki.toggle_block('h3');
	};

	this._toggle_h4 = function()
	{
		this._loki.toggle_block('h4');
	};

	this._is_h3 = function()
	{
		return this._loki.query_command_value('FormatBlock') == 'h3';
	};

	this._is_h4 = function()
	{
		return this._loki.query_command_value('FormatBlock') == 'h4';
	};
};
