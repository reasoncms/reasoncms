function add_biu(command, button_class, el, native_el, adjective) {
	var name = command.toLowerCase();
	if (!adjective)
		adjective = name;
	
	function wrap(el, text) {
		return '<' + el + '>' + text + '</' + el + '>';
	}
	
	function id(s) {
		return name + '.' + s;
	}
	
	fixture.add(id('check'), 'Seeing if text is ' + adjective, function() {
		var text = 'Some ' + name + ' text.';
		this.setHTML(wrap(el, text)).show();
		this.selectInElement(el, 0, text.length);
		this.assert(this.editor.query_command_state(command),
			'The ' + command + ' command should be in the true state');
	});

	if (native_el) {
		fixture.add(id('massage'), 'Massaging ' + native_el + ' tags', function() {
			var text = 'Some nice text.', editor = this.editor;
			this.setHTML(wrap(native_el, text)).show();

			function el_count(tag) {
				return match_elements(editor.document, tag).length;
			}

			this.assertEqual(0, el_count(native_el), 'No ' + native_el +
				' elements');
			this.assertEqual(1, el_count(el), 'One ' + el.toUpperCase() +
				' element');
		});
	}

	fixture.add(id('enable'), 'Making text ' + adjective, function() {
		var text = 'Some nice filler text.', button;
		this.setHTML('<p>' + text + '</p>').show();
		this.selectInElement('p', 10, 16);

		button = (new button_class).init(this.editor);
		button.click_listener();

		this.assert(this.editor.query_command_state(command),
			'Selection should now be ' + adjective);

		this.selectInElement('p', 0, 10);
		this.assertFalse(this.editor.query_command_state(command),
			'Text before ' + adjective + ' region should be normal');

		this.selectInElement('p', 11, 16);
		this.assertFalse(this.editor.query_command_state(command),
			'Text after ' + adjective + ' region should be normal');
	});

	fixture.add(id('disable'), 'Making text not ' + adjective, function() {
		var button;
		this.setHTML(wrap('p', 'Text, some of which ' +
			wrap(el, 'is ' + adjective) + '.'));
		this.show();

		this.selectElementContents(el);

		button = (new button_class).init(this.editor);
		button.click_listener();

		this.assertFalse(this.editor.query_command_state(command),
			'Selection should no longer be ' + adjective);
		this.assertEqual(0, match_elements(this.editor.document, el).length,
			'No ' + name +' elements');
	});

	fixture.add(id('enable_hetero'), 'Making heterogeneous text ' + adjective, function() {
		var button;
		this.setHTML(wrap('p', 'Text, some of which ' +
			wrap(el, 'is ' + name) + '.'));
		this.show();

		this.selectElementContents('p');

		button = (new button_class).init(this.editor);
		button.click_listener();

		this.assert(this.editor.query_command_state(command),
			'Entire paragraph should now be ' + adjective);
	});
}

add_biu('Bold', UI.Bold_Button, 'b', 'strong');
add_biu('Italic', UI.Italic_Button, 'i', 'em');
add_biu('Underline', UI.Underline_Button, 'u', null, 'underlined');
