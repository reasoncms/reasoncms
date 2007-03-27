/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class A class representing an align menugroup. 
 */
UI.Align_Menugroup = function()
{
	Util.OOP.inherits(this, UI.Menugroup);

	this.init = function(loki)
	{
		this.superclass.init.call(this, loki);
		this._align_helper = (new UI.Align_Helper).init(this._loki);
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
		if ( this._align_helper.is_alignable() )
		{
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Align left',
				//listener : function() { self._loki.exec_command('JustifyLeft'); }
				listener : function() { self._align_helper.align_left(); }
			}) );
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Align center',
				//listener : function() { self._loki.exec_command('JustifyCenter'); }
				listener : function() { self._align_helper.align_center(); }
			}) );
			menuitems.push( (new UI.Menuitem).init({ 
				label : 'Align right',
				//listener : function() { self._loki.exec_command('JustifyRight'); }
				listener : function() { self._align_helper.align_right(); }
			}) );

			menuitems.push( (new UI.Separator_Menuitem).init() );
		}

		return menuitems;
	};
};
