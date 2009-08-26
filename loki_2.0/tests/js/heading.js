fixture.add('heading.create', 'Turning a paragraph into a heading', function() {
	this.setHTML('<p>A Future Heading</p>').show().selectInElement('p');
	this.pushButton('Headline');
	this.assertEqual(0, this.elementCount('p'), 'Should be no paragraphs');
	this.assertEqual(1, this.elementCount('h3'), 'Should be one H3');
	this.assertEqual('A Future Heading', this.elementText('h3'));
});

fixture.add('heading.remove', 'Turning a heading back into a paragraph', function() {
	this.setHTML('<h3>A future paragraph.</h3>').show().selectInElement('h3');
	this.pushButton('Headline');
	this.assertEqual(1, this.elementCount('p'), 'Should be one paragraph');
	this.assertEqual(0, this.elementCount('h3'), 'Should be no H3\'s');
	this.assertEqual('A future paragraph.', this.elementText('p'));
});

fixture.add('heading.deflate', 'Converting a headline from major to minor', function() {
	var text = 'A Heading';
	this.setHTML('<h3>' + text + '</h3>').show().selectInElement('h3');
	this.assertFalse(this.hasMenuItem('Headline', /major heading/));
	this.runMenuItem('Headline', /minor heading/);
	this.assertEqual(0, this.elementCount('h3'), 'Should be no H3\'s');
	this.assertEqual(1, this.elementCount('h4'), 'Should be no H4\'s');
	this.assertEqual(text, this.elementText('h4'));
});

fixture.add('heading.inflate', 'Converting a headline from minor to major', function() {
	var text = 'A Heading';
	this.setHTML('<h4>' + text + '</h4>').show().selectInElement('h4');
	this.assertFalse(this.hasMenuItem('Headline', /minor heading/));
	this.runMenuItem('Headline', /major heading/);
	this.assertEqual(0, this.elementCount('h4'), 'Should be no H4\'s');
	this.assertEqual(1, this.elementCount('h3'), 'Should be no H3\'s');
	this.assertEqual(text, this.elementText('h3'));
});

fixture.add('heading.menu.remove_major', 'Removing a major heading using the menu item', function() {
	var text = 'A Heading';
	this.setHTML('<h3>' + text + '</h3>').show().selectInElement('h3');
	this.runMenuItem('Headline', 'Remove headline');
	this.assertEqual(1, this.elementCount('p'), 'Should be one paragraph');
	this.assertEqual(0, this.elementCount('h3'), 'Should be no H3\'s');
	this.assertEqual(0, this.elementCount('h4'), 'Should be no H4\'s');
	this.assertEqual(text, this.elementText('p'));
});

fixture.add('heading.menu.remove_minor', 'Removing a minor heading using the menu item', function() {
	var text = 'A Heading';
	this.setHTML('<h4>' + text + '</h4>').show().selectInElement('h4');
	this.runMenuItem('Headline', 'Remove headline');
	this.assertEqual(1, this.elementCount('p'), 'Should be one paragraph');
	this.assertEqual(0, this.elementCount('h3'), 'Should be no H3\'s');
	this.assertEqual(0, this.elementCount('h4'), 'Should be no H4\'s');
	this.assertEqual(text, this.elementText('p'));
});

fixture.add('heading.create_multiple', 'Converting multiple paragraphs to headings', function() {
	var range;
	
	this.setHTML('<p class="one">A Future Heading</p>\n' +
		'<p class="two">Another Future Heading</p>').show();
		
	range = this.createRange();
	Util.Range.set_start(range, this.findElement('p.one').firstChild, 2);
	Util.Range.set_end(range, this.findElement('p.two').firstChild, 7);
	this.selectRange(range);
	
	this.pushButton('Headline');
	this.assertEqual(0, this.elementCount('p'), 'Should be no paragraphs');
	this.assertEqual(2, this.elementCount('h3'), 'Should be two H3\'s');
	this.assertEqual(1, this.elementCount('h3.one'), 'p.one should retain class');
	this.assertEqual(1, this.elementCount('h3.two'), 'p.two should retain class');
	this.assertEqual('A Future Heading', this.elementText('h3.one'));
	this.assertEqual('Another Future Heading', this.elementText('h3.two'));
});
