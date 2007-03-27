/**
 * Creates a chunk containing a radio button.
 * @constructor
 *
 * @param	params	an object with the following properties:
 *                  <ul>
 *                  <li>document - the DOM document object which will own the created DOM elements
 *                  <li>id - the desired id of the radio's DOM input element</li>
 *                  <li>name - the desired name of the radio's DOM input element</li>
 *                  <li>value - the desired value of the radio's DOM input element</li>
 *                  <li>label - the desired label of the radio</li>
 *                  <li>checked - boolean indicating whether the radio is checked</li>
 *                  </ul>
 *
 * @class Represents a radio button. Once instantiated, a Radio object
 * has the following properties:
 * <ul>
 * <li>all of the properties given to the constructor in <code>params</code></li>
 * <li>id - the id of the DOM input element</li>
 * <li>label_elem - the DOM label element</li>
 * <li>input_elem - the DOM input element</li>
 * <li>chunk - the containing DOM span element. Use this to append the whole radio chunk.</li>
 * </ul>
 */
Util.Radio = function(params)
{
	this.document = params.document;
	this.id = params.id;
	this.name = params.name;
	this.value = params.value;
	this.label = params.label;
	this.checked = params.checked;

	// Create input element
	this.input_elem = Util.Input.create_named_input({document : this.document, name : this.name, checked : this.checked });
	this.input_elem.setAttribute('type', 'radio');
	this.input_elem.setAttribute('id', this.id);
	this.input_elem.setAttribute('value', this.value);

	// Create label elem
	this.label_elem = this.document.createElement('LABEL');
	this.label_elem.appendChild( this.document.createTextNode( this.label ) );
	this.label_elem.setAttribute('for', this.id);

	// Create chunk, and append to it the input and label elems
	this.chunk = this.document.createElement('SPAN');
	this.chunk.appendChild(this.input_elem);
	this.chunk.appendChild(this.label_elem);
};
