




var Crucible = {
	



	version: "0.2a2",
	
	



	base: null,
	
	defaultRunner: null,
	tests: [],
	_doneAdding: false,
	_windowReady: false,
	
	settings: {
		runner_class: 'TableRunner',
		autorun: true,
		filters: null
	},
	
	add: function crucible_add(id, name, body) {
		var key, tid, tests;
		
		if (typeof(id) == 'object') {
			if (id instanceof Crucible.Test) {
				this.tests.push(id);
				return;
			}
			
			tests = id;
			for (key in tests) {
				tid = Crucible.Test.parseID(key);
				Crucible.add(tid[0], tid[1], tests[key]);
			}
			return;
		} else if (typeof(name) == 'object' && name !== null) {
			tests = name;
			for (key in tests) {
				tid = Crucible.Test.parseID([id, key].join('.'));
				Crucible.add(tid[0], tid[1], tests[key]);
			}
			return;
		} else if (typeof(name) == 'function') {
			body = name;
			name = null;
		} else if (typeof(id) != 'string') {
			throw new Error('Must identify the test being added.');
		}
		
		var test = new Crucible.Test(id, name, body);
		this.tests.push(test);
		return test;
	},
	
	addFixture: function crucible_add_fixture(id, name, spec) {
		if (typeof(name) == 'object') {
			spec = name;
			name = null;
		} else if (typeof(id) != 'string') {
			throw new Error('Must identify the fixture being added.');
		}
		
		return new Crucible.Fixture(id, name, spec);
	},
	
	doneAdding: function crucible_done_adding() {
		Crucible._doneAdding = true;
		if (Crucible._windowReady) {
			Crucible._createRunner();
		}
	},
	
	_readQuerySettings: function crucible_read_settings_from_query_params() {
	    var query = window.location.search;
	    if (!query)
	        return;
	    
	    query = query.replace(/^\?/, '').split('&');
	    
	    Crucible.forEach(query, function process_setting(setting) {
	        var name, value, word_value, int_value;
	        var equals = setting.indexOf('=');
	        
	        if (!(1 + equals))  {
	            name = setting;
	            value = true;
	        } else {
	            name = setting.substr(0, equals);
	            value = setting.substr(equals + 1);
	            word_value = value.toLowerCase();
	            
	            if (name == "filters") {
	                value = value.split(/\s*,\s*/);
	            } else if (word_value == "true" || word_value == "yes") {
	                value = true;
	            } else if (word_value == "false" || word_value == "no") {
	                value = false;
	            } else if (!isNaN(int_value = parseInt(value))) {
	                value = int_value;
	            }
	        }
	        
	        this.settings[name] = value;
	    }, Crucible);
	},
	
	_createRunner: function crucible_create_runner() {
		var Runner = Crucible[Crucible.settings.runner_class];
		Crucible.defaultRunner = new Runner(Crucible.product || null,
			Crucible.tests);
		
		if (Crucible.settings.autorun)
			Crucible.run();
		
		return Crucible.defaultRunner;
	},
	
	run: function crucible_run() {
		Crucible.defaultRunner.run(Crucible.settings.filters);
	},
	
	







	augment: function augment_object(destination, source, overwrite) {
		if (typeof(overwrite) == 'undefined' || overwrite === null)
			overwrite = true;
		for (var name in source) {
			if (overwrite || !(name in destination))
				destination[name] = source[name];
		}
		return destination;
	},
	
	



	equal: function objects_equal(a, b) {
		var seen;
		if (typeof(a) != 'object') {
			return (typeof(b) == 'object')
				? false
				: (a == b);
		} else if (typeof(b) != 'object') {
			return false;
		}

		seen = {};

		for (var name in a) {
			if (name in Object.prototype)
				continue;
			if (!(name in b && Crucible.equal(a[name], b[name])))
				return false;
			seen[name] = true;
		}

		for (var name in b) {
			if (name in Object.prototype)
				continue;
			if (!(name in seen))
				return false;
		}

		return true;
	},
	
	arrayFrom: function array_from_iterable(iterable) {
		if (!iterable) return [];

		var length = iterable.length || 0
		var results = new Array(length);
		for (var i = 0; i < length; i++)
			results[i] = iterable[i];
		return results;
	},
	
	observeEvent: function observe_event(target, name, handler) {
		if (target.addEventListener) {
			target.addEventListener(name, handler, false);
		} else if (target.attachEvent) {
			function ie_event_wrapper(ev) {
				if (!ev)
					ev = window.event;
				if (!ev.target && ev.srcElement)
					ev.target = ev.srcElement;
				if (!ev.relatedTarget) {
					if (ev.type == 'mouseover' && ev.fromElement)
						ev.relatedTarget = ev.fromElement;
					else if (ev.type == 'mouseout' && ev.toElement)
						ev.relatedTarget = ev.toElement;
				}
				if (!ev.stopPropagation) {
					ev.stopPropagation = function() {
						this.cancelBubble = true;
					}
				}
				if (!ev.preventDefault) {
					ev.preventDefault = function() {
						this.returnValue = false;
					}
				}
				
				handler.call(this, ev);
			}
			target.attachEvent('on' + name, ie_event_wrapper);
		} else {
			throw new Error('No modern event API available.');
		}
	},
	
	bind: function bind_function(function_, thisp) {
		if (!thisp)
			return function_; // no wrapping needed
		return function binder() {
			return function_.apply(thisp, arguments);
		};
	},
	
	delay: function delay_function(function_, timeout, thisp) {
		var args = Crucible.arrayFrom(arguments).slice(3);
		return window.setTimeout(function delayer() {
			return function_.apply(thisp || null, args);
		}, timeout * 1000);
	},
	
	defer: function defer_function(function_, thisp) {
		var args = [function_, 0.01, thisp || null], i;
		for (i = 2; i < arguments.length; i++)
			args.push(arguments[i]);
		return Crucible.delay.apply(Crucible, args);
	},
	
	determineBase: function determine_base_uri() {
		if (Crucible.base)
			return Crucible.base;
		
		var scripts = document.getElementsByTagName('SCRIPT');
		var pattern = /\bcrucible\.js(\?[^#]*)?(#\S+)?$/;
		
		for (var i = 0; i < scripts.length; i++) {
			if (pattern.test(scripts[i].src)) {
				// Found Crucible!
				Crucible.base = scripts[i].src.replace(pattern, '');
				if (/build\/?$/.test(Crucible.base)) {
					Crucible.base = Crucible.base.replace(/build\/?$/, '');
				}
				if (Crucible.base.charAt(Crucible.base.length - 1) == '/') {
					Crucible.base = Crucible.base.substr(0,
						Crucible.base.length - 1);
				}
				return Crucible.base;
			}
		}
		
		throw new Error('Unable to automatically determine the Crucible base ' +
			'URI. Please explicitly set the Crucible.base property.');
	},
	
	




	objectKeys: function object_keys(obj) {
		var keys = [];
		for (var name in obj)
			keys.push(name);
		return keys;
	},
	
	forEach: function for_each(iterable, fn, context) {
		if (!context)
			context = null;
		
		var i, length = iterable.length;
		for (i = 0; i < length; i++) {
			if (i in iterable)
				fn.call(context, iterable[i], i, iterable);
		}
	},
	
	



	emptyFunction: function() {
		// do nothing
	},
	
	




	constantFunction: function(value) {
		return value;
	},
	
	escapeRegexp: function(text) {
	    return String(text).replace(/([.*+?^=!:${}()|[\]\/\\])/g, '\\$1');
	}
};

Crucible.observeEvent(window, 'load', function _crucible_window_loaded() {
	Crucible._windowReady = true;
	Crucible._readQuerySettings();
	if (Crucible._doneAdding) {
		Crucible._createRunner();
	}
});


Crucible.Class = {
	// Function: create
	// Creates a new class.
	//
	// Usage:
	//     The following example creates a trivial class named Foo, and an
	//     equally-trivial subclass of Foo named Bar.
	//     > var Foo = Crucible.Class.create({
	//     >     initialize: function Foo() { this.x = true; }
	//     > });
	//     > var Bar = Crucible.Class.create(Foo, {
	//     >     initialize: function Bar() { this.y = false; }
	//     > });
	//
	// Parameters:
	//     (Function) [superclass] - a class from which this class will inherit
	//     (Object) prototype - the class's prototype; a constructor function,
	//                          if any, should be named "initialize" in the
	//                          prototype
	//
	// Returns:
	//     (Function) the newly-created class
	//
	// Throws:
	//     TypeError - if _prototype_ is not an object or if _superclass_ (if
	//                 given) is not a function
	create: function create_class(superclass, prototype) {
		if (arguments.length === 0) {
			superclass = undefined;
			prototype = {};
		} else if (arguments.length == 1) {
			prototype = superclass;
			superclass = undefined;
		}
		
		if (typeof(superclass) != 'undefined' && typeof(superclass) != 'function') {
			throw new TypeError("If given, the superclass must be a function.");
		} else if (typeof(prototype) != 'object' || prototype === null) {
			throw new TypeError("The class's prototype must be an object.");
		}
		
		var cl = prototype.initialize || function T() { };
		
		if (superclass) {
			var my_proto = prototype;
			var Subclass = function() { };
			Subclass.prototype = superclass.prototype;
			prototype = new Subclass();
			Crucible.augment(prototype, my_proto);
			cl.superclass = superclass;
		}
		
		cl.prototype = prototype;
		cl.prototype.constructor = cl;
		return cl;
	},
	
	// Function: mixin
	// Adds an object to a class's prototype. See <Crucible.augment> if you
	// need to mix an object into any general object.
	//
	// Parameters:
	//     (Function) class - The class to which the object will be mixed in
	//     (Object) object - The object that will be mixed in
	mixin: function add_object_to_class(class_, object) {
		Crucible.augment(class_.prototype, object);
	}
};

Crucible.Failure = function Failure(test, message) {
	var err = new Error('Failure in test "' + test.name + '": ' + message);
	
	function filter_html(text) {
		return text.replace(Crucible.Failure.HTML, '');
	}
	
	err.name = "Crucible.Failure";
	err.description = message || null;
	err.plainDescription = (message) ? filter_html(message) : null;
	err.test = test || null;
	
	err._crucible_failure = true;
	
	return err;
};

Crucible.Failure.HTML = /<\/?(\w+:)?\w:[^>]*>/g;

Crucible.ExpectationFailure =
	function ExpectationFailure(test, expected, actual, message) 
{
	var err;
	var expected_r = Crucible.Tools.inspect(expected);
	var actual_r = Crucible.Tools.inspect(actual);
	
	if (message) {
	    message = message.replace(/[?!.:;,]$/, '') + '; expected ';
	} else {
	    message += 'Expected ';
	}
	message += '<code>' + expected_r + '</code> but actually' +
		' got <code>' + actual_r + '</code>.';
	
	err = new Crucible.Failure(test, message);
	err.name = "Crucible.ExpectationFailure";
	
	return err;
};

Crucible.AsyncCompletion = function AsyncCompletion() {
	Error.call(this, 'Test will be completed asynchronusly. (Not an error.)');
	this.name = "Crucible.AsyncCompletion";
};

Crucible.AsyncCompletion.prototype = new Error(null);

Crucible.UnexpectedError = function UnexpectedError(test, error) {
	Error.call(this, 'Unexpected ' + error.name + ' thrown from test "' +
		test.name + '": ' + error.message);
	this.name = "Crucible.UnexpectedError";
	this.error = error;
};

Crucible.UnexpectedError.prototype = new Error(null);

Crucible.Tools = {
	
	inspect: function inspect_object(obj) {
		return Crucible.Tools.inspect.handlers[typeof(obj)](obj);
	},
	
	
	gsub: function gsub(source, pattern, replacement) {
		var result = '', match, after;
		
		while (source.length > 0) {
			match = source.match(pattern)
			if (match) {
				result += source.slice(0, match.index);
				after = (typeof(replacement) == 'function')
					? replacement(match)
					: replacement;

				if (after)
					result += after;
				source = source.slice(match.index + match[0].length);
			} else {
				result += source;
				source = '';
			}
		}
		
		return result;
	},
	
	trim: function trim_string(str) {
		str = str.replace(/^\s+/, '');
		for (var i = str.length - 1; i >= 0; i--) {
			if (/\S/.test(str.charAt(i))) {
				str = str.substring(0, i + 1);
				break;
			}
		}
		return str;
	},
	
	
	getAttributes: function get_element_attributes(elem)
	{
		var attrs = {};
		
		if (typeof(elem) != 'object' || !elem) {
			throw new TypeError('Cannot get the attributes of a non-object.');
		}
		
		if (elem.nodeType != 1 || !elem.hasAttributes())
			return attrs;
		
		for (var i = 0; i < elem.attributes.length; i++) {
			var a = elem.attributes[i];
			if (!a.specified || a.nodeName in attrs)
				continue;
			
			var v;
			try {
				v = a.nodeValue.toString();
			} catch (e) {
				v = a.nodeValue;
			}
			
			switch (a.nodeName) {
				case 'class':
					attrs.className = v;
					break;
				case 'for':
					attrs.htmlFor = v;
					break;
				default:
					attrs[a.nodeName] = v;
			}
		}
		
		return attrs;
	},
	
	element: function create_element(name, attrs, children)
	{
		var e = document.createElement(name.toUpperCase());

		function collapse(i, dom_text)
		{
			switch (typeof(i)) {
				case 'function':
					return collapse(i(), dom_text);
				case 'string':
					return (dom_text) ? document.createTextNode(i) : i;
				default:
					return i;
			}
		}

		function dim(dimension)
		{
			return (typeof(dimension) == 'number') ?
				dimension + 'px' :
				dimension;
		}

		var style = {};

		for (var name in attrs || {}) {
			var dest_name = name;

			switch (name) {
				case 'className':
				case 'class':
					var klass = attrs[name];

					// Allow an array of classes to be passed in.
					if (typeof(klass) != 'string' && klass.join)
						klass = klass.join(' ');

					e.className = klass;
					continue; // note that this continues the for loop!
				case 'htmlFor':
					dest_name = 'for';
					break;
				case 'style':
					if (typeof(style) == 'object') {
						style = attrs.style;
						continue; // note that this continues the for loop!
					}
			}

			var a = attrs[name];
			if (typeof(a) == 'boolean') {
				if (a)
					e.setAttribute(dest_name, dest_name);
				else
					continue;
			} else {
				e.setAttribute(dest_name, collapse(a, false));
			}
		}

		for (var name in style) {
			// Special cases
			switch (name) {
				case 'box':
					var box = style[name];
					e.style.left = dim(box[0]);
					e.style.top = dim(box[1]);
					e.style.width = dim(box[2]);
					e.style.height = dim(box[3] || box[2]);
					break;
				case 'left':
				case 'top':
				case 'right':
				case 'bottom':
				case 'width':
				case 'height':
					e.style[name] = dim(style[name]);
					break;
				default:
					e.style[name] = style[name];
			}
		}
		
		if (typeof(children) == 'string')
			children = [children];
		Crucible.forEach(children || [], function(c) {
			e.appendChild(collapse(c, true));
		});

		return e;
	},
	
	addStyleSheet: function add_style_sheet(path) {
		var heads = document.getElementsByTagName('HEAD');
		var head;
		
		if (!heads.length)
			throw new Error('Document has no HEAD.');
		head = heads[0];
		
		return head.appendChild(Crucible.Tools.element('link',
			{rel: 'stylesheet', type: 'text/css', href: path}));
	}
};

Crucible.Tools.inspect.handlers = {
	string: function(s) {
		for (var sp_char in this.string.chars) {
			s = Crucible.Tools.gsub(s, sp_char, this.string.chars[sp_char]);
		}
		return '"' + s + '"';
	},
	
	number: function(n) {
		return String(n);
	},
	
	boolean: function(b) {
		return String(b);
	},
	
	'function': function(f) {
		return 'function' + (f.name ? ' ' + f.name : '') + '()';
	},
	
	'object': function(o) {
		var reprs = [];
		
		if (o === null)
			return 'null';
		
		if (o.nodeType) {
			if (o.nodeType == 3)
				return this.string(o.nodeValue);
			else if (o.nodeType == 1)
				return this.element(o);
			else if (o.nodeType == 8)
				return this.comment(o);
			else if (o.nodeType == 9)
				return this.document(o);
			else
				return '[Node]';
		}
		
		if (typeof(o.length) == 'number' && o.length >= 0)
			return this.array(o);
		
		for (var name in o) {
			if (name in Object.prototype)
				continue;
			reprs.push(name + ': ' + Crucible.Tools.inspect(o[name]));
		}
		
		return '{' + reprs.join(', ') + '}';
	},
	
	'array': function(a) {
		var reprs = [];
		
		for (var i = 0; i < a.length; i++) {
			reprs.push(Crucible.Tools.inspect(a[i]));
		}
		
		return '[' + reprs.join(', ') + ']';
	},
	
	'undefined': function() {
		return 'undefined';
	},
	
	element: function(el) {
		var attrs, name, tag;
		
		tag = '<' + el.tagName.toLowerCase();
		
		attrs = Crucible.Tools.get_attributes(el);
		for (var name in attrs) {
			tag += ' ' + name + '="' + attrs[name] + '"';
		}
		
		return tag + '>';
	},
	
	comment: function(node) {
		return '<!-- ' + node.nodeValue + ' -->';
	},
	
	document: function(document) {
		return '[Document]';
	}
};

Crucible.Tools.inspect.handlers.string.chars = {
	"\b": '\\b',
	"\t": '\\t',
	"\n": '\\n',
	"\v": '\\v',
	"\f": '\\f',
	"\r": '\\r',
	'"': '\\"'
};

Crucible.Delegator = function Delegator(name) {
	if (name)
		this.name = name;
	this.listeners = [];
};

Crucible.augment(Crucible.Delegator.prototype,
	
{
	
	name: null,
	
	
	listeners: null,
	
	
	call: function call_delegates() {
		var i, len, l;
		for (i = 0, len = this.listeners.length; i < len; i++) {
			l = this.listeners[i];
			if (typeof(l.listener) == 'function')
				l.listener.apply(l.context, arguments);
			else
				l.listener[l.context].apply(l.listener, arguments);
		}
	},
	
	
	add: function add_listener_to_delegator(listener, context) {
		if (typeof(listener) == 'function') {
			this.listeners.push({
				listener: listener,
				context: context || null
			});
		} else if (typeof(listener) == 'object') {
			this.listeners.push({
				listener: listener,
				context: context || 'handleEvent'
			});
		} else {
			throw new TypeError('Cannot add a "' + typeof(listener) + '" ' +
				'as a delegation listener.');
		}
	},
	
	
	remove: function remove_listener_from_delegator(listener, context) {
		var i, l;
		
		if (typeof(listener) == 'function') {
			if (!context)
				context = null;
		} else if (typeof(listener) == 'object') {
			if (!context)
				context = 'handleEvent';
		} else {
			return false;
		}
		
		for (i = 0; i < this.listeners.length; i++) {
			l = this.listeners[i];
			if (l.listener == listener && l.context == context) {
				this.listeners.splice(i, 1);
				return true;
			}
		}
		
		return false;
	}
});


 

Crucible.Assertions = {
	assertEqual: function assert_equal(expected, actual, message) {
		if (!Crucible.equal(expected, actual)) {
			throw new Crucible.ExpectationFailure(this._test || null, expected,
				actual, message || null);
		}
	},
	
	assertSame: function assert_same(expected, actual, message) {
		if (expected !== actual) {
			throw new Crucible.ExpectationFailure(this._test || null, expected,
				actual, message || null);
		}
	},
	
	assertType: function assert_type(expected_type, object, message) {
		if (typeof(object) != expected_type) {
			throw new Crucible.Failure(this._test || null,
				message || 'Object should be of type "' + expected_type + '".');
		}
	},
	
	assertDefined: function assert_defined(object, message) {
		if (typeof(object) == 'undefined') {
			throw new Crucible.Failure(this._test || null,
				message || 'Object should not be undefined.');
		}
	},
	
	assertNull: function assert_null(object, message) {
		if (object !== null) {
			throw new Crucible.Failure(this._test || null,
				message || 'Object should be null.');
		}
	},
	
	assertNotNull: function assert_not_null(object, message) {
		if (object === null || typeof(object) == 'undefined') {
			throw new Crucible.Failure(this._test || null,
				message || 'Object should not be null.');
		}
	},
	
	assert: function assert(condition, message) {
		if (!condition) {
			throw new Crucible.Failure(this._test || null,
				message || '(unspecified reason)');
		}
	},
	
	assertFalse: function assert_false(condition, message) {
		if (condition) {
			throw new Crucible.Failure(this._test || null,
				message || '(unspecified reason)');
		}
	},
	
	fail: function fail(message) {
		throw new Crucible.Failure(this._test, message ||
			'(unspecified reason)');
	}
};

Crucible.Preferences = {
	_values: {},
	
	get: function get_preference(name) {
		var self = Crucible.Preferences;
		var process, value;
		
		if (self._values[name])
			return self._values[name];
			
		process = self._get_processor(name, 'get');
		value = self._get_cookie('crucible_' + name);
		
		return (value) ? process(value) : self._prefs[name].value;
	},
	
	set: function set_preference(name, value) {
		var self = Crucible.Preferences;
		var process;
		
		process = self._get_processor(name, 'set');
		
		self._values[name] = value;
		self._set_cookie('crucible_' + name, process(value), 730);
	},
	
	_get_cookie: function _get_cookie(name) {
		var cookies = document.cookie.split(';');
		var cookie_pattern = /(\S+)=(.+)$/;
		var i, match;
		
		for (i = 0; i < cookies.length; i++) {
			match = cookie_pattern.exec(cookies[i]);
			if (!match || !match[1] || !match[2])	
				continue;
			
			if (name && match[1] == name)
				return match[2];
		}
		
		return null;
	},
	
	_set_cookie: function _set_cookie(name, value, days) {
		var expires = '';
		
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			
			expires = '; expires=' + date.toGMTString();
		}
		
		document.cookie = name + '=' + value + expires + '; path=/';
	},
	
	_get_processor: function _get_preference_processor(pref_name, which) {
		var pref = Crucible.Preferences._prefs[pref_name];
		var proc;
		
		if (!pref) {
			throw new Error('Unknown preference "' + pref_name + '".');
		}
		
		proc = pref.processor || 'string';
		
		if (which != 'get' && which != 'set')
			throw new Error('Invalid preference direction "' + which + '".');
		
		if (typeof(proc) == 'string') {
			if (!Crucible.Preferences._default_processors[proc]) {
				throw new Error('Unknown default preference processor "' +
					proc + '".');
			}
			proc = Crucible.Preferences._default_processors[proc];
		}
		
		if (!proc[which]) {
			throw new Error('Invalid preference processor for "' + pref_name +
				'".');
		}
		
		return proc[which];
	},
	
	_prefs: {
		pr_status: {
			value: 'closed'
		}
	},
	
	_default_processors: {
		'string': {
			get: Crucible.constantFunction,
			set: Crucible.constantFunction
		}
	}
};

// Class: Crucible.Test
// Represents a unit test within Crucible.
Crucible.Test = Crucible.Class.create({
	// var: id
	// The test's short-name identifier.
	id: null,
	
	// var: name
	// The test's human-readable name.
	name: null,
	
	// var: context
	// The "this" context in which the test code will run. By default, this
	// object will include the contents of <Crucible.Assertions>. Test segments
	// will add an expect method and a reference to the current test at "test".
	context: null,
	
	// var: root
	// The test's root segment. (see <Crucible.Test.Segment>)
	root: null,
	
	events: null,
		
	// Constructor: Test
	// Creates a new test.
	//
	// Parameters:
	//     (String) id - the test's identifying string
	//     (String) name - the test's human-readable name
	//     (Function) body - the test code
	initialize: function Test(id, name, body) {
		this.id = id;
		this.name = name || id || null;
		this.context = new Crucible.Test.Context(this);
		this.root = new Crucible.Test.Segment(this, body);
		
		this.events = {
			run: new Crucible.Delegator("run test"),
			result: new Crucible.Delegator("test result available")
		};
	},
	
	run: function run_test(callbacks, callback_context) {
		this.callbacks = callbacks || null;
		this.callback_context = callback_context || this.callbacks;
		this.events.run.call(this);
		this.root.run(this.context);
	},
	
	log: function test_log() {
		if (!this.callbacks || !this.callbacks.log)
			return;
		
		this.callbacks.log.apply(this.callback_context, arguments);
	},
	
	reportResult: function report_test_result(status, result) {
		this.events.result.call(this, status, result);
	}
});

// Function: parseID
// Parses a test ID.
Crucible.Test.parseID = function parse_test_id(id) {
	if (typeof(id) != 'string')
		throw new TypeError("Test ID must be a string.");
	var pos = id.indexOf(':');
	
	var name;
	if (pos > -1) {
		name = Crucible.Tools.trim(id.substr(pos + 1));
		id = Crucible.Tools.trim(id.substr(0, pos));
	} else {
		name = null;
	}
	
	var ret = [id, name];
	ret.id = id;
	ret.name = name;
	return ret;
};

// Class: Crucible.Test.Segment
// One synchronusly-executing part of a test.
Crucible.Test.Segment = Crucible.Class.create({
	// var: test
	// The test of which this segment is a part.
	test: null,
	
	// var: body
	// The segment's test function.
	body: null,
	
	// var: parent
	// The segment's parent, if any.
	parent: null,
	
	// var: expected
	// The exception that should be thrown from the segment body, if any.
	expected: null,
	
	// Constructor: Segment
	// Creates a new segment.
	//
	// Parameters:
	//     (Crucible.Test) test - the test of which this segment is a part
	//     (Function) body - the segment's body
	//     (Crucible.Test.Segment) [parent] - the segment's parent, if any
	initialize: function Segment(test, body, parent) {
		this.test = test;
		this.body = body;
		this.parent = parent || null;
		this.expected = null;
	},
	
	// Method: run
	// Runs the test segment.
	run: function run_segment(context) {
		this.context = context || null;
		
		try {
			this.context.expect = Crucible.bind(this.expect, this);
			this.body.call(this.context);
		} catch (e) {
			if (e.name == "Crucible.AsyncCompletion") {
				this.callback = this.context = null;
				return false;
			} else if (e._crucible_failure) {
				return this.report('fail', e);
			} else if (this.wasExpected(e)) {
				return this.report('pass', e);
			} else {
				return this.report('exception', e);
			}
		}
		
		var which, article, exc;
		if (this.expected) {
			if (typeof(this.expected) == 'string') {
				article = (/^[aeiou]/i.test(expected)) ? 'an' : 'a';
				which = article + ' <code>' + this.expected + '</code>';
			} else {
				which = 'an exception';
			}
			
			exc = new Crucible.Failure(this.test, "Expected " + which +
				" to be thrown, but none was.");
			return this.report('fail', exc);
		} else {
			return this.report('pass');
		}
	},
	
	expect: function segment_expect_exception(name) {
		if (!name)
			name = true;
		this.expected = name;
	},
	
	wasExpected: function segment_exception_was_expected(exc) {
		if (!this.expected)
			return false;
		if (typeof(this.expected) == 'string')
			return (exc.name == this.expected);
		return true;
	},
	
	report: function report_segment_result(status, result) {
		this.callback = this.context = null;
		this.test.reportResult(status, result || null);
		return true;
	}
});

Crucible.Test.Context = Crucible.Class.create({
	initialize: function Context(test) {
		this._test = test;
	},
	
	log: function test_context_log() {
		this._test.log.apply(this._test, arguments);
	}
});
Crucible.Class.mixin(Crucible.Test.Context, Crucible.Assertions);

// Class: Crucible.Fixture
// A collection of tests with an associated context and set-up and tear-down
// routines.
Crucible.Fixture = Crucible.Class.create({
	// var: id
	id: null,
	
	// var: name
	name: null,
	
	// Constructor: Fixture
	// Creates a new fixture.
	//
	// Parameters:
	//     (String) id - the base of the identifying string for the fixture's
	//                   tests
	//     (String) name - the fixture's human-readable name
	//     (Object) spec - the fixture specification
	initialize: function Fixture(id, name, spec) {
		this.id = id;
		this.name = name || id || null;
		
		if (typeof(spec) != 'object') {
			throw new Error('Fixture spec must be an object.');
		}
		
		var name, tid;
		for (name in spec) {
			if (name == 'setUp' || name == 'set_up') {
				this.setUp = spec[name];
			} else if (name == 'tearDown' || name == 'tear_down') {
				this.tearDown = spec[name];
			} else {
				tid = Crucible.Test.parseID(name);
				this.add(tid.id, tid.name, spec[name]);
			}
		}
	},
	
	add: function add_to_fixture(id, name, body) {
		var key, tests, tid, test;
		if (typeof(id) == 'object') {
			tests = id;
			for (key in tests) {
				tid = Crucible.Test.parseID(key);
				this.add(tid[0], tid[1], tests[key]);
			}
			return;
		} else if (typeof(name) == 'object') {
			tests = name;
			for (key in tests) {
				tid = Crucible.Test.parseID([id, key].join('.'));
				this.add(tid[0], tid[1], tests[key]);
			}
			return;
		} else if (typeof(name) == 'function') {
			body = name;
			name = null;
		} else if (typeof(id) != 'string') {
			throw new Error('Must identify the test being added.');
		}
		
		tid = Crucible.Test.parseID([this.id, id].join('.'));
		if (tid.name)
			name = tid.name;
		test = Crucible.add(tid.id, name, body);
		test.events.run.add(this, '_beforeTest');
		test.events.result.add(this, '_afterTest');
		return test;
	},
	
	_beforeTest: function _fixture_do_set_up(test) {
		this.setUp(test);
	},
	
	_afterTest: function _fixture_do_tear_down(test) {
		this.tearDown(test);
	},
	
	setUp: Crucible.emptyFunction,
	tearDown: Crucible.emptyFunction
});
// Class: Crucible.Runner
// Runs Crucible tests.
Crucible.Runner = Crucible.Class.create({
	product: null,
	tests: null,
	
	events: null,
	
	// Constructor: Runner
	// Creates a new test runner.
	//
	// Parameters:
	//     (Crucible.Test[]) tests - the tests to be run
	initialize: function Runner(product, tests) {
		this.product = product || '(unknown product)';
		this.tests = [];
		if (tests) {
			Crucible.forEach(tests, this.add, this);
		}
		
		this.events = {
			start: new Crucible.Delegator("started testing"),
			run: new Crucible.Delegator("test started"),
			log: new Crucible.Delegator("message logged"),
			pass: new Crucible.Delegator("test passed"),
			fail: new Crucible.Delegator("test failed"),
			exception: new Crucible.Delegator("test threw an exception"),
			result: new Crucible.Delegator("test finished"),
			finish: new Crucible.Delegator("finished testing")
		};
	},
	
	// Method: add
	// Adds a test to the runner.
	//
	// Parameters:
	//     (Crucible.Test) test - the test to add
	add: function add_to_runner(test) {
		test.events.result.add(this, '_processResult');
		this.tests.push(test);
	},
	
	// Method: run
	// Runs the tests.
	run: function run_tests(filters) {
		if (this.running) {
			throw new Error('Already running!');
		}
		
		this.queue = this._filter(this.tests, filters);
		this.queue.reverse();
		this.running = true;
		
		this.events.start.call();
		this._runTest();
	},
	
	log: function runner_log() {
		this.events.log.call(arguments);
	},
	
	_filter: function _runner_apply_filters(tests, filters) {
	    var filtered, filter_regexp_parts, filter;
	    
	    function glob_to_regexp(glob) {
	        // Temporarily convert the glob "*" character to something that's
	        // not a special regular expression character.
	        glob = glob.replace('*', '__WILDCARD__');
	        glob = Crucible.escapeRegexp(glob);
	        return '(?:' + glob.replace('__WILDCARD__', '.*') + ')';
	    }
	    
	    if (!filters) {
	        return tests.slice(0); // slice makes a shallow clone of the list
	    } else {
	        filter_regexp_parts = [];
	        Crucible.forEach(filters, function compile_filter(filter) {
	            filter_regexp_parts.push(glob_to_regexp(filter));
	        });
	        filter = new RegExp('^' + filter_regexp_parts.join('|') + '$', 'i');
	        
	        filtered = [];
	        Crucible.forEach(tests, function filter_test(test) {
	            if (filter.test(test.id))
	                filtered.push(test);
	        }, this);
	        return filtered;
	    }
	},
	
	_runTest: function _runner_run_test() {
		if (!this.queue) {
			return;
		} else if (this.queue.length == 0) {
			this.events.finish.call();
			this.running = false;
			delete this.queue;
			return;
		}
		
		var test = this.queue.pop();
		this.events.run.call(test);
		Crucible.defer(function run_test_later() {
			test.run(this);
		}, this);
	},
	
	_processResult: function _process_test_result(test, status, result) {
		this.events[status].call(test, result || null);
		this.events.result.call(test, status, result || null);
		Crucible.defer(function run_next_test_later() {
			this._runTest();
		}, this);
	}
});

// Class: Crucible.TableRunner
// A runner that shows its results in a nice table.
Crucible.TableRunner = Crucible.Class.create(Crucible.Runner, {
	icons: {
		waiting: {
			src: 'assets/icons/gear_disable.png',
			alt: 'Waiting to start.'
		},
		passing: {
			src: 'assets/icons/tick_circle_frame.png',
			alt: 'All tests passing.'
		},
		failing: {
			src: 'assets/icons/exclamation_frame.png',
			alt: 'One or more tests failed.'
		},
		errors: {
			src: 'assets/icons/cross_circle_frame.png',
			alt: 'One or more tests encountered errors.'
		}
	},
	
	status: null,
	tallies: null,
	
	// Constructor: TableRunner
	// Creates a new table runner.
	initialize: function TableRunner(product, tests) {
		var build = Crucible.Tools.element;
		var runner = this;
		Crucible.TableRunner.superclass.call(this, product, tests);
		
		Crucible.determineBase();
		Crucible.Tools.addStyleSheet(Crucible.base +
			'/assets/css/table_runner.css');
		
		this.root = build('div', {id: 'crucible_results'});
		this.root.appendChild(build('h1', {}, this.product));
		
		this.statusIndicator = build('div', {id: 'crucible_status'},
			[this._statusIcon('waiting')]);
		this.status = 'waiting';
		this.root.appendChild(this.statusIndicator);
		
		this.startButton = build('div', {id: 'crucible_start'},
			'Start Testing');
		Crucible.observeEvent(this.startButton, 'click', function() {
			Crucible.run();
		});
		this.root.appendChild(this.startButton);
		
		this.tallies = {
			pass: 0,
			fail: 0,
			exception: 0
		};
		
		this._listenForEvents();
		document.body.insertBefore(this.root, document.body.firstChild);
	},
	
	_listenForEvents: function _table_runner_listen_for_events() {
		var events = {
			run: 'Started',
			pass: 'Passed',
			fail: 'Failed',
			result: 'Finished',
			exception: 'ThrewException'
		};
		var name;
		for (name in events) {
			this.events[name].add(this, '_test' + events[name]);
		}
		this.events.start.add(this, '_startedTesting');
		this.events.finish.add(this, '_finishedTesting');
		this.events.log.add(this, '_logMessage');
	},
	
	_startedTesting: function _started_testing() {
		var build = Crucible.Tools.element;
		this.table = build('table', {'class': 'tests'});
		this.table_body = build('tbody');
		this.table.appendChild(this.table_body);
		this.startButton.parentNode.replaceChild(this.table, this.startButton);
	},
	
	_finishedTesting: function _finished_testing() {
		function round(number) {
			// yes, it's ghetto... stfu
			try {
				return String(number).match(/\d+(\.\d)?/)[0];
			} catch (e) {
				return '0';
			}
		}
		var build = Crucible.Tools.element;
		
		var tallies = this.tallies;
		var total_tests = tallies.pass + tallies.fail + tallies.exception;
		
		function make_row(which, title) {
			var percent = (total_tests > 0) ?
				'(' + round(100 * (tallies[which] / total_tests)) + '%)' :
				'';
			
			var row = build('tr', {'class': which});
			row.appendChild(build('th', {}, title));
			row.appendChild(build('td', {}, String(tallies[which])));
			row.appendChild(build('td', {}, percent));
			return row;
		}
		
		var table = build('table', {id: 'crucible_tally'});
		var tbody = build('tbody');
		table.appendChild(tbody);
		
		var last;
		tbody.appendChild(make_row('pass', 'Passed'));
		tbody.appendChild(make_row('fail', 'Failed'));
		tbody.appendChild(last = make_row('exception', 'Errors'));
		
		this.root.appendChild(build('h2', {}, 'Test Results'));
		this.root.appendChild(table);
		last.scrollIntoView();
	},
	
	_logMessage: function _log_message(parts) {
		var message = [], i, length, arg, part;

		for (i = 0, length = parts.length; i < length; ++i) {
			arg = parts[i];
			part = (typeof(arg) == 'string') ?
				arg :
				Crucible.Tools.inspect(arg);

			part = Crucible.Tools.gsub(part, '<', '&lt;');
			part = Crucible.Tools.gsub(part, '>', '&gt;');

			if (typeof(arg) != 'string')
				part = '<code>' + part + '</code>';
			message.push(part);
		}

		this._createRow('log', message.join(' '));
	},
	
	_createRow: function _create_row(type, message) {
		var build = Crucible.Tools.element;
		var row = build('tr', {'class': type});
		var cell = build('td', {'class': 'message'});
		cell.innerHTML = message;
		row.appendChild(cell);
		this.table_body.appendChild(row);
		row.scrollIntoView();
		return row;
	},
	
	_testStarted: function _test_started(test) {
		this.currentRow = this._createRow('busy',
			'Running &ldquo;' + test.name + '&rdquo;&hellip;');
	},
	
	_updateStatus: function _update_test_status(status, message) {
		this.currentRow.className = status;
		this.currentRow.firstChild.innerHTML = message;
	},
	
	_testPassed: function _test_passed(test) {
		if (this.status == 'waiting')
			this._changeGlobalStatus('passing');
		this._updateStatus('pass', test.name);
	},
	
	_testFailed: function _test_failed(test, info) {
		if (this.status != 'errors')
			this._changeGlobalStatus('failing');
		this._updateStatus('fail', test.name + ': ' + info.description);
	},
	
	_testFinished: function _test_finished(test, status) {
		this.tallies[status]++;
	},
	
	_testThrewException: function _test_threw_exception(test, ex) {
		if (this.status != 'errors')
			this._changeGlobalStatus('errors');
		this._updateStatus('exception', ex.name + ' in test &ldquo;' +
			test.name + '&rdquo;: ' + ex.message);
	},
	
	_statusIcon: function _make_status_icon(which) {
		var info = this.icons[which];
		return Crucible.Tools.element('img', {
			src: Crucible.base + '/' + info.src,
			alt: info.alt,
			title: info.alt
		});
	},
	
	_changeGlobalStatus: function _change_global_status(status) {
		this.status = status;
		var n = this._statusIcon(status);
		var i = this.statusIndicator;
		i.replaceChild(n, i.firstChild);
	}
});
