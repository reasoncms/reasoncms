
/**
 * @class Used by UI.Page_Link_Dialog to allow selection of an item of various
 * types on a site.
 *
 * Replaces a ton of poorly-written code that used to exist directly in
 * UI.Page_Link_Dialog.
 *
 * @author Eric Naeseth
 */
UI.Page_Link_Selector = function(dialog)
{
	var doc = dialog._doc;
	var dh = dialog._udoc;
	
	var wrapper = dh.create_element('div', {id: 'pane_wrapper'});
	var message = new UI.Page_Link_Selector.Message_Display(wrapper);
	var please_choose = doc.createTextNode('Please choose a site from the above box.');
	var site = {url: null, name: null};
	
	var error = null;
	var types = [];
	
	this.dialog = dialog;
	
	this.get_uri = function()
	{
		function get_field(name)
		{
			var list = doc.getElementsByName(name);
			return (!list || list.length == 0)
				? null
				: list[0];
		}
		
		var item_select = get_field('item');
		var anchor_select = get_field('anchor');
		
		if (!item_select)
			return null;
		
		var url = item_select.options[item_select.selectedIndex].value;
		var anchor = (anchor_select && anchor_select.selectedIndex > 0)
			? '#' + anchor_select.options[anchor_select.selectedIndex].value
			: '';
		
		if (url.length == 0 && anchor.length == 0)
			return null;
			
		var parsed_uri = Util.URI.parse(url);
		if (!parsed_uri.authority) {
			url = '//' + this.dialog._loki.editor_domain() + url;
		}
		
		return url + anchor;
	}
	
	// Be advised: Util.State_Machine wraps the states' enter() methods;
	// when some_state.enter() is called directly, it's equivalent to
	// calling machine.change(some_state).
	Util.OOP.inherits(this, Util.State_Machine, {
		initial: {
			enter: function() {
				message.insert();
				message.setText(please_choose);
			},
			
			exit: function() {
				message.remove();
			}
		},
		
		loading_site: {
			enter: function()
			{
				types = [];
				
				message.insert();
				message.innerHTML = 'Loading &ldquo;' + site.name + '&rdquo&hellip;';
				
				var reader = new Util.RSS.Reader(site.url);
				var machine = this.machine;
				
				reader.add_event_listener('load', function(feed)
				{
					if (feed.items.length == 0) {
						machine.states.error.set('No link types are available' +
							' to choose from.', function() {
								machine.change('loading_site')
							});
						machine.states.error.enter();
						return;
					}
					
					feed.items.each(function(item) {
						types.push({
							name: item.plural_title,
							instance_name: item.title,
							url: dialog._sanitize_uri(item.link),
							is_default: (dialog._initially_selected_type_uri)
								? item.link == dialog._initially_selected_type_uri
								: dialog._default_type_regexp.test(item.link)
						});
					});
					
					types.sort(function(a, b) {
						return (a.name == b.name)
							? 0
							: (a.name < b.name ? -1 : 1);
					});
					
					machine.change('interactive');
				});
				
				reader.add_event_listener('error', function (error_msg, code)
				{
					machine.states.error.set('Failed to load the site: ' + error_msg,
						function() {
							machine.change('loading_site');
						}
					);
					machine.states.error.enter();
				});
				
				reader.add_event_listener('timeout', function() {
					machine.states.error.set('Failed to load the site: ' +
						'The operation timed out.',
						function() {
							machine.change('loading_site');
						}
					);
					machine.states.error.enter();
				});
				
				try {
					reader.load(null, 10 /* 10 = 10 seconds until timeout */);
				} catch (e) {
					machine.states.error.set('Failed to load the site: ' + 
						(e.message || e),
						function() {
							machine.change('loading_site');
						}
					);
					machine.states.error.enter();
				}
				
			},
			
			exit: function(new_state)
			{
				if (new_state != this.machine.states.interactive)
					message.remove();
			}
		},
		
		interactive: {
			types_pane: null,
			types_list: null,
			
			links_pane: null,
			arbiter: null,
			
			enter: function(old_state)
			{
				this.types_list = dh.create_element('ul',
					{id: 'types_pane_ul'});

				var prev_selected_li = null;
				function select_type(type, li) {
					if (prev_selected_li) {
						Util.Element.remove_class(prev_selected_li,
							'selected');
					}
					
					Util.Element.add_class(li, 'selected');
					prev_selected_li = li;
					this.arbiter.load(type);
				}

				var selected_type = null;
				types.each(function(type) {
					var link = dh.create_element('a', {}, [type.name]);

					var item = dh.create_element('li', {}, [link]);
					this.types_list.appendChild(item);
					
					Util.Event.add_event_listener(link, 'click', function(e)
					{
						try {
							dialog._set_link_title('');
							select_type.call(this, type, item);
						} finally {
							Util.Event.prevent_default(e || window.event);
						}
					}.bind(this));
					
					if (type.is_default)
						selected_type = [type, item];
				}.bind(this));

				this.types_pane = dh.create_element('div', {id: 'types_pane'},
					[this.types_list]);
				
				if (old_state == this.machine.states.loading_site)
					message.remove();

				Util.Element.add_class(wrapper, 'contains_types');
				wrapper.appendChild(this.types_pane);
				
				this.arbiter =
					new UI.Page_Link_Selector.Item_Selector(this.machine, wrapper);
				
				if (selected_type) {
					// Delay this step by a trivial amount to allow the browser
					// to continue execution and render the current state of the
					// page.
					
					(function() {
						select_type.apply(this, selected_type);
					}).bind(this).defer();
				}
					
			},
			
			exit: function()
			{
				if (this.arbiter && this.arbiter.state) {
					this.arbiter.state.exit();
					this.arbiter = null;
				}
				
				wrapper.removeChild(this.types_pane);
				Util.Element.remove_class(wrapper, 'contains_types');
			}
		},
		
		error: new UI.Error_State(wrapper)
	}, 'initial');
	
	this.insert = function(container)
	{
		container.appendChild(wrapper);
	}
	
	this.remove = function()
	{
		if (wrapper.parentNode)
			wrapper.parentNode.removeChild(wrapper);
	}

	this.revert = function()
	{
		this.states.initial.enter();
	}
	
	this.load = function(site_name, site_url)
	{
		site.name = site_name;
		site.url = site_url;
		this.states.loading_site.enter();
	}
	
	this.reload = function()
	{
		this.states.loading_site.enter();
	}
}

/**
 * @class Chooses the item.
 */
UI.Page_Link_Selector.Item_Selector = function(parent, wrapper)
{
	var dialog = parent.dialog;
	var doc = wrapper.ownerDocument;
	var dh = new Util.Document(doc);
	
	var message = new UI.Page_Link_Selector.Message_Display(wrapper);
	var please_choose = doc.createTextNode(
		'Please choose the type of item to which you want to link.');
	
	var inline_p_name = null;
	var type = null;
	var error = null;
	var items = null;
	
	this.load = function(new_type)
	{
		type = new_type;
		inline_p_name = type.name.toLowerCase();
		this.states.loading.enter();
	}
	
	Util.OOP.inherits(this, Util.State_Machine, {
		initial: {
			enter: function() {
				message.insert();
				message.setText(please_choose);
			},
			
			exit: function() {
				message.remove();
			}
		},
		
		loading: {
			enter: function() {
				message.insert();
				message.setText('Loading ' + inline_p_name + '…');
				
				var reader = new Util.RSS.Reader(type.url);
				var machine = this.machine;
				var initial_uri = // XXX: REASON HACK
					Util.URI.strip_https_and_http(dialog._initially_selected_nameless_uri);

				reader.add_event_listener('load', function(feed)
				{
					items = [];
					
					if (type.is_default) {
						// XXX: this is kinda hackish
						items.push(
							{
								value: '',
								text: '(current ' + type.instance_name.toLowerCase() + ')'
							}
						);
					} else if (feed.items.length == 0) {
						machine.states.error.set('No ' +
							type.name.toLowerCase() + ' are available to ' +
							'choose from.', function() {
								machine.change('loading_site')
							});
						machine.states.error.enter();
						return;
					}
					
					// We are not sorting the feed items because the server
					// might be doing fancy things (e.g. nesting).
					
					feed.items.each(function(item) {
						items.push({
							text: item.title,
							value: dialog._sanitize_uri(item.link),
							selected: (initial_uri)
								? (initial_uri == Util.URI.strip_https_and_http(item.link))
								: false
						});
					});

					machine.change('interactive');
				});

				reader.add_event_listener('error', function (error_msg, code)
				{
					machine.states.error.set('Failed to load the ' + 
						inline_p_name + ': ' + error_msg,
						function() {
							machine.change('loading');
						}
					);
					machine.states.error.enter();
				});
				
				reader.add_event_listener('timeout', function() {
					machine.states.error.set('Failed to load the ' + 
						inline_p_name + ': The operation timed out.',
						function() {
							machine.change('loading');
						}
					);
					machine.states.error.enter();
				});

				try {
					reader.load(null, 10 /* 10 = 10 seconds until timeout */);
				} catch (e) {
					machine.states.error.set('Failed to load the ' + 
						inline_p_name + ': ' + (e.message || e),
						function() {
							machine.change('loading');
						}
					);
					machine.states.error.enter();
				}
			},
			
			exit: function() {
				message.remove();
			}
		},
		
		interactive: {
			form: null,
			pane: null,
			
			enter: function()
			{
				this.pane = dh.create_element('form', {className: 'generated', id: 'links_pane'});
				
				this.form = new Util.Form(doc, {
					name: 'Item Selector',
					form: this.pane
				});

				var section = this.form.add_section();
				var select = section.add_select_field(type.instance_name,
					items, {name: 'item'});
					
				function item_changed()
				{
					var el = select.element;
					dialog._set_link_title(el.options[el.selectedIndex].text);
				}
					
				Util.Event.add_event_listener(select.element, 'change',
					item_changed);
				item_changed();
				
				wrapper.appendChild(this.form.form_element);
				
				// XXX: wonky in IE; neglect it for now.
				if (!Util.Browser.IE) {
					(function () {
						var select_box = select.element;
						var needed_width = select_box.offsetLeft + select_box.offsetWidth;
						var dialog_window = dialog._dialog_window.window;

						var width_diff;
						var height;
						var dd = dialog_window.document;

						if (dialog_window.outerHeight) {
							width_diff =
								(dialog_window.outerWidth - dialog_window.innerWidth);
							height = dialog_window.outerHeight;
						} else if (dd.documentElement && dd.documentElement.clientHeight) {
							width_diff = 0;
							height = dd.documentElement.clientHeight;
						} else if (dd.body.clientHeight) {
							width_diff = 0;
							height = dd.body.clientHeight;
						} else {
							return;
						}

						var ideal_width = needed_width + 55 + width_diff;
						if (window.screenX + ideal_width >= window.screen.availWidth - 10)
							ideal_width = window.screen.availWidth - window.screenX - 10;

						dialog_window.resizeTo(
							[dialog._dialog_window_width, ideal_width].max(),
							height);
					}).delay(.05);
				}
				
				
				function AnchorField()
				{
					Util.OOP.inherits(this, Util.Form.FormField, "Anchor");
					
					var state = 'loading';
					var container = null;
					var present = null;
					
					var activity = dialog.create_activity_indicator('bar');
					var message = dh.create_element('p',
						{style: {margin: '0px', fontStyle: 'italic'}},
						['(No anchors were found.)']);
					var selector = null;
					
					function show_no_anchors_message()
					{
						if (state != 'none') {
							present.parentNode.removeChild(present);
							present = message;
							container.appendChild(present);
							state = 'none';
						}
					}
						
					function show_anchors(anchors)
					{
						if (anchors.length == 0) {
							show_no_anchors_message();
							return;
						}
						
						if (state == 'interactive') {
							while (selector.childNodes.length > 0)
								selector.removeChild(selector.firstChild);
						} else {
							selector = dh.create_element('select', 
								{name: 'anchor', size: 1});
							present.parentNode.removeChild(present);
							present = selector;
							container.appendChild(present);
							state = 'interactive';
						}
						
						selector.appendChild(dh.create_element('option',
							{value: ''}, ['(none)']));
						
						anchors.sort();
						anchors.each(function(a) {
							selector.appendChild(dh.create_element('option',
								{
									value: a,
									selected: (dialog._initially_selected_name == a)
								}, [a]));
						});
					}
					
					this.load = function(url)
					{
						if (state != 'loading') {
							present.parentNode.removeChild(present);
							present = activity.indicator;
							container.appendChild(present);
							state = 'loading';
						}
						
						if (url == '') {
							// use the current document's anchors
							show_anchors(dialog._anchor_names);
						} else {
							var request = null;
							
							function nothing_found()
							{
								request.abort();
								show_no_anchors_message();
							}
							
							function is_html_type()
							{
								var type = request.get_header('Content-Type');
								if (!type)
									return false;
								
								var acceptable_types =
									['text/html', 'text/xml', 'application/xml',
									'application/xhtml+xml'];
								
								return acceptable_types.find(function (t) {
									return (type.indexOf(t) >= 0);
								});
							}
							
							request = new Util.Request(url, {
								method: 'get',
								timeout: 10,
								
								on_interactive: function(request)
								{
									if (!request.successful() || !is_html_type())
										nothing_found();
								},
								
								on_failure: function()
								{
									nothing_found();
								},
								
								on_success: function(request, transport)
								{
									if (!is_html_type())
										nothing_found();
									
									var parser = new Util.HTML_Parser();
									var names = [];

									parser.add_listener('open', function(tag, params) {
										if (tag.toUpperCase() == 'A') {
											if (params.name && !params.href)
												names.push(params.name);
										}
									})
									parser.parse(transport.responseText);
									
									show_anchors(names);
								}
							});
						}
					}
					
					var really_append = this.append;
					this.append = function(form, doc, dh, target)
					{
						container = target;
						really_append.call(this, form, doc, dh, target);
					}
					
					this.create_element = function(doc, dh)
					{
						present = activity.indicator;
						return present;
					}
				}
				
				var af = new AnchorField();
				section.add_field(af);
				
				function load_anchors()
				{
					var se = select.element;
					af.load(se.options[se.selectedIndex].value);
				}
				
				Util.Event.add_event_listener(select.element, 'change', function() {
					load_anchors();
				});
				load_anchors();
			},
			
			exit: function()
			{
				if (this.form) {
					this.form = null;
				}
				
				if (this.pane)
					this.pane.parentNode.removeChild(this.pane);
			}
		},
		
		error: new UI.Error_State(wrapper)
	}, 'initial');
	
	// links_pane = dh.create_element('div', {id: 'links_pane'});
}

/**
 * @class Displays an instructional or loading message.
 */
UI.Page_Link_Selector.Message_Display = function(wrapper)
{
	var doc = wrapper.ownerDocument;
	var message = Util.Document.create_element(doc, 'p', {className: 'message'});

	this.insert = function() {
		if (message.parentNode != wrapper)
			wrapper.appendChild(message);
	}

	this.remove = function() {
		if (message.parentNode)
			message.parentNode.removeChild(message);
	}

	this.setText = function(text)
	{
		if (typeof(text) == 'string')
			text = doc.createTextNode(text);

		while (message.childNodes.length > 0)
			message.removeChild(message.firstChild);

		message.appendChild(text);
	}
}