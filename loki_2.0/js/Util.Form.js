/**
 * @constructor
 *
 * @class Form generation without fuss and with validation.
 * @author Eric Naeseth
 */
Util.Form = function(document, params)
{
	var dh = new Util.Document(document); // document helper
	
	this.document = document;
	this._dh = dh;
	this.name = params.name || '(untitled form)';
	this.form_element = params.form || dh.create_element('form',
		{method: params.method || 'POST',
		action: params.action || 'about:blank',
		className: 'generated'});
	this.section_heading_level = params.section_heading_level || 'H3';
	this.live_validation = true;
	
	this.sections = [];
	this.active_section = null;
	
	this.toString = function()
	{
		return '[object Util.Form name=' + this.name +
			', form_element=' + this.form_element + ']';
	}
	
	/**
	 * Constructs and returns a new form section.
	 * Form elements cannot be added directly to the form, but must be added
	 * to sections. The name parameter is optional, so to simulate a form with
	 * no sectional organization, create one single nameless section and add
	 * the fields to it.
	 */
	this.add_section = function(name)
	{
		if (arguments.length == 0)
			var name = null;
		
		var s = new Util.Form.FormSection(this, name);
		this.sections.push(s);
		this.active_section = s;
		s.append(document, dh);
		
		return s;
	}
}

/**
 * @constructor
 * @class Base class for form sections and compound form fields.
 */
Util.Form.FormElementContainer = function(form)
{
	this.new_container = Util.Function.unimplemented;
	this.fields = [];
	
	this.add_field = function(field)
	{
		var container = this.new_container(form, form.document, form._dh);
		field.append(form, form.document, form._dh, container);
		this.fields.push(field);
		return field;
	}
	
	// convenience methods
	
	this.add_text_field = function(name, params)
	{
		if (!params) var params = {};
		
		return this.add_field(new Util.Form.TextField(name,
			params.exposition || null, params));
	}
	
	this.add_blurb_field = function(name, params)
	{
		if (!params) var params = {};
		
		return this.add_field(new Util.Form.BlurbField(name,
			params.exposition || null, params));
	}
	
	this.add_select_field = function(name, values, params)
	{
		if (!params) var params = {};
		
		return this.add_field(new Util.Form.SelectField(name,
			params.exposition || null, params, values));
	}
	
	this.add_instructions = function(text)
	{
		if (!params) var params = {};
	
		return this.add_field(new Util.Form.Instructions(text));
	}
}

/**
 * @constructor
 * @class A section of a form.
 */
Util.Form.FormSection = function(form, name)
{
	Util.OOP.inherits(this, Util.Form.FormElementContainer, form);
	
	this.name = (arguments.length < 2)
		? null
		: name;
	var list = null;
	
	this.append = function(doc, dh)
	{
		var fe = form.form_element;
		
		if (this.name) {
			fe.appendChild(dh.create_element(form.section_heading_level,
				{className: 'section_heading'}, [this.name]));
		}
		
		list = dh.create_element('ul', {className: 'form_section'});
		fe.appendChild(list);
	}
	
	this.new_container = function(form, doc, dh)
	{
		var litem = dh.create_element('li');
		list.appendChild(litem);
		return litem;
	}
	
	this.add_compound_field = function()
	{
		return this.add_field(new Util.Form.CompoundField(form));
	}
}

/**
 * @constructor
 * @class A field on a form.
 */
Util.Form.FormField = function(name, exposition, validator)
{
	this.name = name || null;
	this.exposition = exposition || null;
	this.validate = validator || Util.Function.empty;
	this.element = null;
	
	this.append = function(form, doc, dh, target)
	{
		if (this.name) {
			target.appendChild(dh.create_element('label',
				{className: 'description'}, [this.name]));
		}
		
		if (this.exposition) {
			target.appendChild(dh.create_element('p',
				{className: 'exposition'}, [this.exposition]));
		}
		
		this.element = this.create_element(doc, dh);
		target.appendChild(this.element);
	}
	
	this.get_field_name = function() {
		if (arguments.length > 0) {
			var name = arguments[0];
			if (typeof(name) == 'object' && typeof(name.name) == 'string')
				return name.name;
		}
		
		if (typeof(this.name) != 'string') {
			throw new Error('No pretty name for this field is defined.');
		}
		
		return this.name.replace(/\W+/, '_').toLowerCase();
	}
	
	this._apply_validation = function(element) {
		var field = this;
		Util.Event.add_event_listener(element, 'change', function(e) {
			field.validate.call(this, e || window.event);
		})
		return element;
	}
	
	this.create_element = Util.Function.unimplemented;
}

Util.Form.TextField = function(name, exposition, params)
{
	Util.OOP.inherits(this, Util.Form.FormField, name, exposition, params.validator);
	
	this.create_element = function(doc, dh)
	{
		return this._apply_validation(dh.create_element('input', {
			type: 'text',
			name: this.get_field_name(params || {}),
			value: params.value || '',
			size: params.size || 20
		}));
	}
}

Util.Form.BlurbField = function(name, exposition, params)
{
	Util.OOP.inherits(this, Util.Form.FormField, name, exposition, params.validator);
	
	this.create_element = function(doc, dh)
	{
		return this._apply_validation(dh.create_element('textarea', {
			name: this.get_field_name(params || {}),
			cols: params.cols || 60,
			rows: params.rows || 5},
			[params.value || '']
		));
	}
}

Util.Form.SelectField = function(name, exposition, params, values)
{
	Util.OOP.inherits(this, Util.Form.FormField, name, exposition, params.validator);
	
	this.create_element = function(doc, dh)
	{
		var options = [];
		for (var i = 0; i < values.length; i++) {
			var v = values[i];
			var option = dh.create_element('option',
				{value: v.value, selected: (v.selected || false)});
			option.innerHTML = v.text;
			options.push(option);
		}
		
		return this._apply_validation(dh.create_element('select', 
			{name: this.get_field_name(params || {}),
			size: params.size || 1},
			options
		));
	}
}

Util.Form.CompoundField = function(form)
{
	Util.OOP.inherits(this, Util.Form.FormElementContainer, form);
	
	var container = null;
	var line_break = null;
	
	this.append = function(form, doc, dh, target)
	{
		container = target;
		line_break = dh.create_element('br', {className: 'compound_end'});
		container.appendChild(line_break);
	}
	
	this.new_container = function(form, doc, dh)
	{
		var item = dh.create_element('span');
		container.insertBefore(item, line_break);
		return item;
	}
	
	this.validate = function()
	{
		for (var i = 0; i < this.fields.length; i++) {
			this.fields[i].validate();
		}
	}
}

Util.Form.Instructions = function(text)
{
	Util.OOP.inherits(this, Util.Form.FormField);
	
	this.create_element = function(doc, dh)
	{
		return dh.create_element('p', {className: 'instructions'},
			[text]);
	}
}