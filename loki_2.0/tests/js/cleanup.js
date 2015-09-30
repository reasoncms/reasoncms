var clean = Crucible.addFixture('loki.cleanup', EnvTools);

clean.add('links.strip_protocol.same_domain', "Should strip protocol from same-domain links", function() {
	var domain = Util.URI.extract_domain(window.location);
	if (!domain) {
		throw new Error('Untestable: current location has no domain.');
	}
	var uri = 'http://' + domain + '/test';
	
	this.setHTML('<p><a href="' + uri + '">Test</a></p>').show();
	var a = this.findElement('a');
	this.assertEqual('//' + domain + '/test', a.getAttribute("href"));
});

clean.add('links.strip_protocol.off_domain', "Should preserve protocol in off-domain links", function() {
	this.setHTML('<p><a href="http://www.example.com/test">Test</a></p>"');
	this.show();
	var a = this.findElement('a');
	this.assertEqual('http://www.example.com/test', a.getAttribute("href"));
});

clean.add('images.strip_protocol.same_domain', "Should strip protocol from same-domain images", function() {
	var domain = Util.URI.extract_domain(window.location);
	if (!domain) {
		throw new Error('Untestable: current location has no domain.');
	}
	var uri = 'http://' + domain + '/example.png';
	
	var gp = document.createElement('DIV');
	gp.innerHTML = '<p><img src="' + uri + '" /></p>';
	
	var img = gp.getElementsByTagName("IMG")[0];
	if (img.src != uri) {
		throw new Error("Image src is automatically rewritten.");
	}
	
	this.setHTML('<p><img src="' + uri + '" /></p>').show();
	var img = this.findElement('img');
	var src;
	if (img.outerHTML) {
		// IE fills in the protocol for us when we access the element's "src".
		// Read the outerHTML instead.
		
		var parser = new Util.HTML_Parser();
		parser.add_listener('open', function(n, attributes) {
			src = attributes.src;
			parser.halt();
		});
		parser.parse(img.outerHTML);
	} else {
		src = img.getAttribute("src");
	}
	
	this.assertEqual('//' + domain + '/example.png', src);
});

clean.add('images.strip_protocol.off_domain', "Should preserve protocol in off-domain images", function() {
	this.setHTML('<p><img src="http://www.example.com/example.png" /></p>').show();
	var img = this.findElement('img');
	this.assertEqual('http://www.example.com/example.png', img.getAttribute("src"));
});

clean.add('strip_tags', "Should strip non-allowed tags", function() {
	this.setHTML('<p><a href="http://www.google.com/">Foo <acronym>Bar</acronym></a></p>');
	this.show({allowable_tags: ['P', 'ACRONYM']});
	this.log(this.getHTML());
	this.assertEqual(1, this.elementCount("p"), 'Should be one paragraph');
	this.assertEqual(1, this.elementCount("acronym"), 'Should be 1 acronym');
	this.assertEqual(0, this.elementCount("a"), 'Should be no links');
	this.assertEqual('Bar', this.elementText("acronym"));
	this.assertEqual('Foo Bar', this.elementText("p"));
	this.assertSame(this.findElement("p"), this.findElement("acronym").parentNode,
		"The acronym should be a child of the paragraph");
});

clean.add('strip_tags.lower_case_allowable', "Should accept allowable tags in lower case", function() {
	this.setHTML('<p><a href="http://www.google.com/">Foo <acronym>Bar</acronym></a></p>');
	this.show({allowable_tags: ['p', 'acronym']});
	this.assertEqual(1, this.elementCount("p"), 'Should be one paragraph');
	this.assertEqual(1, this.elementCount("acronym"), 'Should be 1 acronym');
	this.assertEqual(0, this.elementCount("a"), 'Should be no links');
	this.assertEqual('Bar', this.elementText("acronym"));
	this.assertEqual('Foo Bar', this.elementText("p"));
	this.assertSame(this.findElement("p"), this.findElement("acronym").parentNode,
		"The acronym should be a child of the paragraph");
});

clean.add('strip_comments', "Should remove comments by default", function() {
	this.setHTML('<p>Foo<!-- bar --><span>baz</span></p><!-- quux -->').show();
	this.assertEqual(0, this.findNodes(Util.Node.COMMENT_NODE).length,
		'Should be no comment nodes in the document');
})

clean.add('strip_comments.disable', "Shouldn't remove comments if '!' is an allowed tag", function() {
	var settings = {
		allowable_tags: UI.Clean.default_allowable_tags.concat(['!'])
	};
	
	this.setHTML('<p>Foo<!-- bar --><span>baz</span></p><!-- quux -->');
	this.show(settings);
	this.log(this.getHTML());
	this.assertEqual(2, this.findNodes(Util.Node.COMMENT_NODE).length,
		'Should be two comment nodes in the document');
});

clean.add('strip_styles.default', 'Style tags should be removed by default', function() {
	this.setHTML('<style type="text/css">body { background: black; }</style>' +
		'<p>Foo <span>Bar</span></p>');
	this.show();
	this.assertEqual(0, this.elementCount("style"),
		"Should be no style elements");
});

clean.add('strip_styles.always', 'Style tags should be removed even if STYLE is an allowed tag', 
function() {
	var settings = {
		allowable_tags: UI.Clean.default_allowable_tags.concat(['STYLE'])
	};
	
	this.setHTML('<style type="text/css">body { background: black; }</style>' +
		'<p>Foo <span>Bar</span></p>');
	this.show(settings);
	this.assertEqual(0, this.elementCount("style"),
		"Should be no style elements");
});

var office = Crucible.addFixture('loki.cleanup.office', EnvTools);
office.add('v_shape', '"v:shape" attributes should be stripped', function() {
	this.setHTML('<p v:shape="foo">Some text <a v:shape="bar" href="baz">' +
		'and a link.</a></p>').show();
	this.assertNull(this.findElement('p').getAttribute("v:shape"));
	this.assertNull(this.findElement('a').getAttribute("v:shape"));
});

clean.add('align.to_css', 'Align attributes should be translated to CSS', function() {
	this.setHTML('<div class="pretty" align="right"><p align="center">Foo Bar</p></div>');
	this.show();
	this.log(this.editor.get_dirty_html());
	
	this.assertFalse(this.findElement('div').getAttribute('align'),
		'The DIV should not have an align attribute');
	this.assertFalse(this.findElement('p').getAttribute('align'),
		'The paragraph should not have an align attribute');
	this.assertEqual('right', this.findElement('div').style.textAlign);
	this.assertEqual('center', this.findElement('p').style.textAlign);
});

clean.add('align.tables_images', 'Align attributes should be preserved on table elements and images',
function() {
	this.setHTML('<table align="left"><tr align="center">' +
		'<th align="right">Header</th><td align="left">Cell</td></tr></table>');
	this.show();
	
	var self = this;
	function align_of(selector) {
		return self.findElement(selector).getAttribute('align');
	}
	
	this.assertEqual('left', align_of('table'), "Table should be left-aligned");
	this.assertEqual('center', align_of('tr'), "TR should be center-aligned");
	this.assertEqual('right', align_of('th'), "TH should be right-aligned");
	this.assertEqual('left', align_of('tD'), "TD should be center-aligned");
});

clean.add('strip_styles.disallowed', 'Only disallowed inline styles should be stripped', function() {
	var settings = {
		allowable_inline_styles: ['background-image', 'text-align', 'font-size']
	};
	
	this.setHTML('<p style="background-image: url(&quot;/bg.png&quot);' +
		'letter-spacing: 0.5em; text-align: center; font-size: 140%;' +
		'border-width: 2px">Foo!</p>');
	this.show(settings);
	this.log(this.getHTML());
	
	var p = this.findElement('p');
	this.assert(/^url\((["']|)\/bg.png\1\)$/.exec(p.style.backgroundImage),
		"Paragraph should have /bg.png as a background image");
	this.assertFalse(p.style.letterSpacing,
		"Paragraph should not have letter-spacing set.");
	this.assertEqual('center', p.style.textAlign,
		"Paragraph should be center-aligned");
	this.assertEqual('140%', p.style.fontSize,
		"Paragraph should have a font size of 140%");
	this.assertFalse(p.style.borderWidth,
		"Paragraph should not have border-width set.");
});

clean.add('strip_styles.prefix_matching', 'The list of allowed inline styles should prefix-match', function() {
	var settings = {
		allowable_inline_styles: ['background', 'text']
	};
	
	this.setHTML('<p style="background-attachment: fixed; color: blue; ' +
		'text-decoration: underline; background-repeat: repeat-x;">' +
		'Foo, bar, baz.</p>');
	this.show(settings);
	
	var p = this.findElement('p');
	this.assertEqual('fixed', p.style.backgroundAttachment);
	this.assertEqual('underline', p.style.textDecoration);
	this.assertEqual('repeat-x', p.style.backgroundRepeat);
	this.assertFalse(p.style.color, 'Paragraph should not have a background color');
});

clean.add('strip_styles.defaults', 'Certain styles should be permitted by default', function() {
	this.setHTML('<p style="text-align: center; float: right; direction: ltr;' +
		' display: block; clear: both;">' +
		'<img style="vertical-align: middle;" src="/fake.png" /></p>' +
		'<ul style="list-style: none;"><li>Foo</li></ul>');
	this.show();
	this.log(this.getHTML());
	
	var p = this.findElement('p');
	this.assertEqual('center', p.style.textAlign);
	this.assertEqual('right', p.style.cssFloat || p.style.styleFloat);
	this.assertEqual('ltr', p.style.direction);
	this.assertEqual('block', p.style.display);
	this.assertEqual('both', p.style.clear);
	this.assertEqual('middle', this.findElement('img').style.verticalAlign);
	this.assertEqual('none', this.findElement('ul').style.listStyleType);
});

clean.add('strip_styles.allowable_as_string', 'Allowable inline styles should be specifiable as a string',
function() {
	this.setHTML('<p style="color: blue; background-color: black;">omg</p>');
	this.show({allowable_inline_styles: 'color background'});
	
	var p = this.findElement('p');
	this.assertEqual('blue', p.style.color);
	this.assertEqual('black', p.style.backgroundColor);
});

function inline_style_all_macro(macro) {
	var repr = Crucible.Tools.inspect(macro);
	var name = 'Setting acceptable_inline_styles to ' + repr + ' should permit all';
	var id = (macro == '*') ? 'star' : (macro === true ? 'true' : macro);
	id = 'strip_styles.macros.' + id;
	clean.add(id, name, function() {
		this.setHTML('<p style="color: blue; background-color: black;">omg</p>');
		this.show({allowable_inline_styles: macro});

		var p = this.findElement('p');
		this.assertEqual('blue', p.style.color);
		this.assertEqual('black', p.style.backgroundColor);
	});
}
inline_style_all_macro('all');
inline_style_all_macro('any');
inline_style_all_macro('*');
inline_style_all_macro(true);

function inline_style_none_macro(macro) {
	var repr = Crucible.Tools.inspect(macro);
	var name = 'Setting acceptable_inline_styles to ' + repr + ' should disallow all';
	var id = (macro === true) ? 'true' : macro;
	clean.add('strip_styles.macros.' + id, name, function() {
		this.setHTML('<p style="text-align: left; direction: ltr">omg</p>');
		this.show({allowable_inline_styles: macro});

		var p = this.findElement('p');
		this.assertFalse(p.style.textAlign, 'Paragraph should not have text-align');
		this.assertFalse(p.style.direction, 'Paragraph should not have direction');
		this.assertFalse(p.style.cssText, 'Paragraph should not have an inline style');
	});
}
inline_style_none_macro('none');
inline_style_none_macro(false);

office.add('word.empty_paras', 'Empty Word-produced paragraphs should be removed', function() {
	this.setHTML('<p id="foo">Foo</p><p class="MsoNormal">\n&nbsp;\n</p>' +
		'<p class="whee">&nbsp;</p><p class="MsoFrizzle">&nbsp;</p>' +
		'<p class="bar MsoNormal">&nbsp;</p><p id="baz">Baz</p>');
	this.show();
	
	this.assertEqual(3, this.elementCount('p'), 'Three paragraphs should survive');
	this.assertEqual(1, this.elementCount('p#foo'), '"Foo" paragraph should survive');
	this.assertEqual('Foo', this.elementText('p#foo'));
	this.assertEqual(1, this.elementCount('p#baz'), '"Baz" paragraph should survive');
	this.assertEqual('Baz', this.elementText('p#baz'));
	this.assertEqual(1, this.elementCount('p.whee'), 'Empty non-Word paragraph should survive');
	this.assertEqual(0, this.elementCount('p.MsoNormal'), 'No .MsoNormal paragraphs should survive');
	this.assertEqual(0, this.elementCount('p.MsoFrizzle'), 'The .MsoFrizzle paragraph should not survive');
	this.assertEqual(0, this.elementCount('p.bar'), 'No paragraphs with class "bar" should survive');
});

office.add('strip_classes', 'Should remove Microsoft Office classes', function() {
	this.setHTML('<p class="MsoNormal foo Office bar">Meh.</p>').show();
	
	this.assertEqual(1, this.elementCount('p'), 'Should be one paragraph');
	this.assertEqual('Meh.', this.elementText('p'));
	this.assertEqual('foo bar', this.findElement('p').className);
});

office.add('strip_sections', 'Should remove Microsoft Word section DIV\'s', function() {
	this.setHTML('<div class="Section1"><p id="foo">Foo</p></div>' +
		'<div class="hmm Section2"><p id="bar">Bar</p></div>').show();
	
	this.assertEqual(0, this.elementCount('div.Section1'),
		"div.Section1 should be gone");
	this.assertEqual(1, this.elementCount('p#foo'),
		"p#foo should be present");
	this.assertEqual('Foo', this.elementText('p#foo'),
		'p#foo\'s text should be unaffected');
	
	this.assertEqual(1, this.elementCount('div.hmm'),
		'div.hmm should be present');	
	this.assertEqual(0, this.elementCount('div.Section2'),
		"No div should have Section2 as a class.");
	this.assertEqual(1, this.elementCount('p#bar'),
		"p#bar should be present");
	this.assertEqual('Bar', this.elementText('p#bar'),
		'p#bar\'s text should be unaffected');
	this.assertSame(this.findElement('div.hmm'),
		this.findElement('p#bar').parentNode,
		'div.hmm should still be p#bar\'s parent');
	this.assertSame(this.findElement('div.hmm'), this.findElement('p#foo').nextSibling,
		'p#foo should come right before div.hmm');
});

clean.add('permit_bare_divs', "DIV elements with no classes should be permitted (#174)", function() {
    this.setHTML('<div><div class="foo"><p id="foo">Foo!</p></div>' +
        '<div><p id="bar">Bar!</p></div>').show();
    
    this.assertEqual(3, this.elementCount('div'), "There shold be 3 DIV's");
    this.assertEqual('DIV', this.findElement('div.foo').parentNode.tagName);
});

clean.add('pointless_spans', 'Should strip pointless SPAN elements', function() {
	this.setHTML('<p><span>Foo</span> <span class="bar">Bar</span>' +
		' <span style="direction: ltr;">Baz</span></p>');
	this.show();
	this.log(this.getHTML());
	
	this.assertEqual(2, this.elementCount('span'), 'Should be two spans');
	this.assertEqual('Foo Bar Baz', this.elementText('p'));
	this.assertEqual(1, this.elementCount('span.bar'), 'span.bar should be ok');
	this.assertEqual('Bar', this.elementText('span.bar'));
	this.assertEqual(1, this.elementCount('span[style]'),
		'Should be one span with a style attribute');
	this.assertEqual('Baz', this.elementText('span[style]'));
	this.assertEqual('ltr', this.findElement('span[style]').style.direction);
});

clean.add('pointless_spans.in_containers', 'Should not strip SPAN elements that are in containers', function() {
	this.setHTML('<div loki:container="true" class="hr"><span>Foo</span></div>');
	this.show();
	
	this.assertEqual(1, this.elementCount('span'));
	this.assertEqual('Foo', this.elementText('span'));
});

clean.add('underline.when_disabled', 'Should strip U elements if the underline option is disabled', function() {
	this.setHTML('<p>Foo <u>bar</u> baz.</p>');
	this.show({options: 'all - underline'});
	
	this.assertEqual(0, this.elementCount('u'));
	this.assertEqual(1, this.elementCount('p'));
	this.assertEqual('Foo bar baz.', this.elementText('p'));
});

clean.add('underline.when_enabled', 'Shouldn\'t strip U elements if the underline option is enabled', function() {
	this.setHTML('<p>Foo <u>bar</u> baz.</p>');
	this.show({options: 'all + underline'});
	
	this.assertEqual(1, this.elementCount('u'));
	this.assertEqual('bar', this.elementText('u'));
	this.assertEqual(1, this.elementCount('p'));
	this.assertEqual('Foo bar baz.', this.elementText('p'));
});

office.add('tags_with_prefixes', 'Should strip tags with Office prefixes', function() {
	var settings = {
		allowable_tags: UI.Clean.default_allowable_tags.concat(['foo'])
	};
	
	this.setHTML('<p><acronym>meh</acronym><o:acronym>meh</o:acronym>' +
		'<O:acronym>meh</O:acronym><w:acronym>meh</w:acronym>' +
		'<W:acronym>meh</W:acronym><st1:acronym>meh</st1:acronym>' +
		'<ST1:acronym>meh</ST1:acronym> ohmigod!</p>');
	this.show(settings);
	this.log(this.getHTML());
	
	var doc = this.editor.document;
	var self = this;
	function count(tag) {
		return doc.getElementsByTagName(tag).length;
	}
	function none_of(tag) {
		var i, tag;
		for (i = 0; i < arguments.length; i++) {
			tag = arguments[i];
			self.assertEqual(0, count(tag), 'Should be no "' + tag + '" elements');
		}
	}
	this.assertEqual(1, this.elementCount('p'), 'Should be 1 paragraph');
	this.assertEqual(1, this.elementCount('acronym'), 'Should be 1 "acronym" element');
	none_of('o:acronym', 'O:acronym', 'w:acronym', 'W:acronym', 'st1:acronym',
		'ST1:acronym');
});

clean.add('tables.dimensions', 'Should strip width and height attributes from tables', function() {
	this.setHTML('<table summary="Foo" height="100%" width="100%">' +
		'<tr><td>x</td></tr></table>');
	this.show();
	
	this.assertEqual(1, this.elementCount('table'), 'Should be one table');
	
	var table = this.findElement('table');
	this.assertEqual("Foo", table.summary);
	this.assertFalse(table.height, 'Table should have no height');
	this.assertFalse(table.width, 'Table should have no width');
});

clean.add('images.dimensions.enabled', 'Should strip width and height attributes from images if desired', function() {
	this.setHTML('<p><img src="/fake.png" height="24" width="16" /></p>');
	this.show({disallow_image_sizes: true});
	this.log(this.getHTML());
	
	this.assertEqual(1, this.elementCount('img'), 'Should be one image');
	var img = this.findElement('img');
	this.log("height = ", img.getAttribute('height'), "width = ", img.getAttribute('width'));
	this.assertFalse(img.getAttribute('height'), 'Image should have no height');
	this.assertFalse(img.getAttribute('width'), 'Image should have no width');
});

clean.add('images.dimensions.disabled', 'Shouldn\'t strip width and height attributes from images unless desired', function() {
	this.setHTML('<p><img src="/fake.png" height="24" width="16" /></p>');
	this.show({disallow_image_sizes: false});
	this.log(this.getHTML());
	
	this.assertEqual(1, this.elementCount('img'), 'Should be one image');
	var img = this.findElement('img');
	this.assertEqual(24, img.getAttribute('height'));
	this.assertEqual(16, img.getAttribute('width'));
});

clean.add('brs.end_of_blocks', "Should remove BR elements that are blocks' last children", function() {
	this.setHTML('<p>Foo<br id="a" />Bar\n<br id="b" /><br id="c" /> \n</p>' +
		'<p>Bar<br id="d" /></p><p><span class="quux">Baz<br id="e" /></span></p>' +
		'<ul><li>List Item</li><li>Other list item<br id="f" /></li></ul>');
	this.show();
	
	this.assertEqual(1, this.elementCount('br#a'), "#a should survive");
	this.assertEqual(0, this.elementCount('br#b'), "#b should not survive");
	this.assertEqual(0, this.elementCount('br#c'), "#c should not survive");
	this.assertEqual(0, this.elementCount('br#d'), "#d should not survive");
	this.assertEqual(1, this.elementCount('br#e'), "#e should survive");
	this.assertEqual(0, this.elementCount('br#f'), "#f should not survive");
	this.assertEqual(2, this.elementCount('br'), "There should be 2 surviving BR's");
});
