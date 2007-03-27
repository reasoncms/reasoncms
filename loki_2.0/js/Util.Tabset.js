/**
 * Creates a chunk containing a tabset.
 * @constructor
 *
 * @param	params	an object with the following properties:
 *                  <ul>
 *                  <li>document - the DOM document object which will own the created DOM elements</li>
 *                  <li>id - (optional) the id of the DOM tabset element</li>
 *                  </ul>
 *
 * @class Represents a tabset.
 */
Util.Tabset = function(params)
{
	var self = this;
	this.document = params.document;
	this.id = params.id;

	var _tabs = {}; // each member of tabs should have a tab_elem and a tabpanel_elem 
	var _name_of_selected_tab;
	var _select_listeners = [];

	// Create tabset element
	this.tabset_elem = this.document.createElement('DIV');
	Util.Element.add_class(this.tabset_elem, 'tabset');
	if ( this.id != null )
		this.tabset_elem.setAttribute('id', this.id);

	// Create tabs container
	var _tabs_chunk = this.document.createElement('DIV');
	Util.Element.add_class(_tabs_chunk, 'tabs_chunk');

	// Create and append force_clear_for_ie element
	var _force_clear_for_ie_elem = this.document.createElement('DIV');
	Util.Element.add_class(_force_clear_for_ie_elem, 'force_clear_for_ie');
	_tabs_chunk.appendChild(_force_clear_for_ie_elem);

	// Create and append tabs ul
	var _tabs_ul = this.document.createElement('UL');
	_tabs_chunk.appendChild(_tabs_ul);

	// Create tabpanels container
	var _tabpanels_chunk = this.document.createElement('DIV');
	Util.Element.add_class(_tabpanels_chunk, 'tabpanels_chunk');

	// Append containers to tabset
	this.tabset_elem.appendChild(_tabs_chunk);
	this.tabset_elem.appendChild(_tabpanels_chunk);


	// Methods

	/**
	 * Adds a tab to the tabset.
	 *
	 * @param	name	the new tab's name
	 * @param	label	the new tab's label
	 */
	this.add_tab = function(name, label)
	{
		// Make entry in list of tabs
		_tabs[name] = {};
		var t = _tabs[name];

		// Create tab element ...
		t.tab_elem = this.document.createElement('LI');
		t.tab_elem.id = t.tab_id = name + '_tab';
		Util.Element.add_class(t.tab_elem, 'tab_chunk');

		// ... and its anchor ...
		var anchor_elem = this.document.createElement('A');
		anchor_elem.href = 'javascript:void(0);';
		t.tab_elem.appendChild(anchor_elem);

		// ... and its label ...
		var label_node = this.document.createTextNode(label);
		anchor_elem.appendChild(label_node);

		// ... with event listeners
		Util.Event.add_event_listener(anchor_elem, 'click', function() { self.select_tab(name); });
		Util.Event.add_event_listener(t.tab_elem, 'mouseover', function() { Util.Element.add_class(t.tab_elem, 'hover'); });
		Util.Event.add_event_listener(t.tab_elem, 'mouseout', function() { Util.Element.remove_class(t.tab_elem, 'hover'); });

		// Create tabpanel element
		t.tabpanel_elem = this.document.createElement('DIV');
		t.tabpanel_elem.id = t.tabpanel_id = name + '_tabpanel';
		Util.Element.add_class(t.tabpanel_elem, 'tabpanel_chunk');

		// Append tab and tabpanel elements
		_tabs_ul.appendChild(t.tab_elem);
		_tabpanels_chunk.appendChild(t.tabpanel_elem);

		// If this is the first tab to be added, select it
		// by default
		if ( _name_of_selected_tab == null )
		{
			this.select_tab(name);
		}
		// Otherwise, re-select the selected tab, in order
		// to refresh the the display
		else
		{
			this.select_tab(this.get_name_of_selected_tab());
		}
	};

	/**
	 * Gets the element of the tabpanel whose
	 * name is given. Then children can be 
	 * appended there.
	 *
	 * @param	name	the tabpanel's name
	 */
	this.get_tabpanel_elem = function(name)
	{
		if ( _tabs[name] == null )
			throw('Util.Tabset.get_tabpanel_elem: no such name.');

		return _tabs[name].tabpanel_elem;
	};

	/**
	 * Selects the tab whose name is given.
	 *
	 * @param	name	the tabpanel's name
	 */
	this.select_tab = function(name)
	{
		if ( _tabs[name] == null )
			throw('Util.Tabset.select_tab: no such name.');

		var old_name = _name_of_selected_tab;

		// Hide all tabs and tabpanels
		for ( var i in _tabs )
		{
			Util.Element.remove_class(_tabs[i].tab_elem, 'selected');
			Util.Element.remove_class(_tabs[i].tabpanel_elem, 'selected');
		}

		// Show selected tab and tabpanel
		Util.Element.add_class(_tabs[name].tab_elem, 'selected');
		Util.Element.add_class(_tabs[name].tabpanel_elem, 'selected');

		// Remember name
		_name_of_selected_tab = name;

		// Fire listeners
		for ( var i = 0; i < _select_listeners.length; i++ )
			_select_listeners[i](old_name, _name_of_selected_tab);

		//mcTabs.display_tab(_tabs[name].tab_id, _tabs[name].tabpanel_id);
	};

	/**
	 * Gets the name of the currently selected tab. 
	 */
	this.get_name_of_selected_tab = function()
	{
		if ( _name_of_selected_tab == null )
			throw('Util.Tabset.get_name_of_selected_tab: no tab selected.');

		return _name_of_selected_tab;
	};

	/**
	 * Adds a listener to be fired whenever a different tab is selected. 
	 * Each listener will receive old_name and new_name as arguments.
	 */
	this.add_select_listener = function(listener)
	{
		_select_listeners.push(listener);
	};
};
