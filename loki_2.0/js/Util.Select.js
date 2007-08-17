/**
 * @constructor Nothing
 *
 * @class Represents an HTML select element. Example usage:
 *
 *  var s = new Util.Select({ document : document, loading_str : 'Loading now ...', id : 's_id' });
 *  parent_elem.appendChild(s);
 *  s.start_loading();
 *  s.add_option({ key : 'One', value : 'Two', selected : false });
 *  s.add_option({ key : 'Three', value : 'Four', selected : false });
 *  s.add_option({ key : 'Five', value : 'Six', selected : true });
 *  s.end_loading();
 *
 */
Util.Select = function(params)
{
	this.document = params.document;
	this._loading_str = params.loading_str != null ? params.loading_str : 'Loading ...';
	this.id = params.id;

	this._options = [];

	// Create select element
	function default_factory() { return this.document.createElement('SELECT'); }
	
	this.select_elem = (params.factory || default_factory)();
	if ( this.id != null )
		this.select_elem.setAttribute('id', this.id);
		
	function create_loading_option()
	{
		var option = this.document.createElement('OPTION');
		option.value = '';
		option.appendChild(this.document.createTextNode(this._loading_str));
		
		return option;
	}

	// Methods

	/**
	 * Start loading. This removes all options, hides the actual select
	 * element, and shows a fake "loading" one.
	 */
	this.start_loading = function()
	{
		// Remove all options
		while ( this.select_elem.firstChild != null )
			this.select_elem.removeChild(this.select_elem.firstChild);
		this._options = [];

		// Add loading option
		this.select_elem.appendChild(create_loading_option());

/*
		// Create loading element
		this._loading_elem = this.select_elem.cloneNode(true);
		var o = this.document.createElement('OPTION');
		o.appendChild(this.document.createTextNode(this._loading_str));
		this._loading_elem.appendChild(o);

		// Hide actual select element
		if ( this.select_elem.parentNode != null )
			this.select_elem.parentNode.replaceChild(this._loading_elem, this.select_elem);
*/
	};

	/**
	 * Adds an option. Does not actually append an option element to the select
	 * element. (That happens all at once in end_loading.)
	 */
	this.add_option = function(value, key, selected)
	{
		this._options.push({k : key, v : value, s : selected});
	};

	/**
	 * Ends loading. This actually creates option elements from the added option
	 * key-value pairs, hides the fake "loading" select element, and shows the
	 * actual select element.
	 */
	this.end_loading = function()
	{
		// Create loading element
		this._loading_elem = this.select_elem.cloneNode(true);
		/*var o = this.document.createElement('OPTION');
		o.appendChild(this.document.createTextNode(this._loading_str));
		this._loading_elem.appendChild(o);*/

		// Hide actual select element
		if ( this.select_elem.parentNode != null )
			this.select_elem.parentNode.replaceChild(this._loading_elem, this.select_elem);


		// Remove all options
		while ( this.select_elem.firstChild != null )
			this.select_elem.removeChild(this.select_elem.firstChild);

		// Add options
		for ( var i = 0; i < this._options.length; i++ )
		{
			var o = this.document.createElement('OPTION');
			o.appendChild(this.document.createTextNode(this._options[i].v));
			o.value = this._options[i].k;
			this.select_elem.appendChild(o);
			o.selected = this._options[i].s;
		}
		/* // Doesn't work in IE:
		var html = '';
		for ( var i = 0; i < this._options.length; i++ )
		{
			var sel = this._options[i].s ? ' selected="selected"' : '';
			html += '<option value="' + this._options[i].k + '"' + sel + '>' + this._options[i].v + '</option>';
		}
		this.select_elem.innerHTML = html;
		*/
		this._options = [];


		// Show actual select element
		if ( this._loading_elem.parentNode != null )
			this._loading_elem.parentNode.replaceChild(this.select_elem, this._loading_elem);
	};
};
