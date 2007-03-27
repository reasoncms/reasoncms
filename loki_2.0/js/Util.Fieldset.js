/**
 * Creates a chunk containing a fieldset.
 * @constructor
 *
 * @param	params	an object with the following properties:
 *                  <ul>
 *                  <li>document - the DOM document object which will own the created DOM elements</li>
 *                  <li>legend - the desired legend text of the radio</li>
 *                  <li>id - (optional) the id of the DOM fieldset element</li>
 *                  </ul>
 *
 * @class Represents a radio button. Once instantiated, a Radio object
 * has the following properties:
 * <ul>
 * <li>all of the properties given to the constructor in <code>params</code></li>
 * <li>fieldset_elem - the DOM fieldset element. Use this when you want to get at the fieldset element qua fieldset element.</li>
 * <li>legend_elem - the DOM legend element</li>
 * <li>chunk - another reference to the DOM fieldset element. Use this when you want to get at the fieldset element qua chunk, e.g. to append the whole fieldset chunk.</li>
 * </ul>
 */
Util.Fieldset = function(params)
{
	this.document = params.document;
	this.legend = params.legend;
	this.id = params.id;

	// Create fieldset element
	this.fieldset_elem = this.document.createElement('DIV');
	Util.Element.add_class(this.fieldset_elem, 'fieldset');
	if ( this.id != null )
		this.fieldset_elem.setAttribute('id', this.id);

	// Create legend elem
	this.legend_elem = this.document.createElement('DIV');
	Util.Element.add_class(this.legend_elem, 'legend');
	this.legend_elem.appendChild( this.document.createTextNode( this.legend ) );

	// Append legend to fieldset
	this.fieldset_elem.appendChild(this.legend_elem);

	// Create "chunk"
	this.chunk = this.fieldset_elem;


	// Methods

	/**
	 * Sets this fieldset's legend.
	 *
	 * @param	value	the new value
	 */
	this.set_legend = function(value)
	{
		Util.Node.remove_child_nodes( this.legend_elem );
		this.legend_elem.appendChild( this.document.createTextNode(value) );
	};
};
