fixture.add('pre.create', 'Turning a paragraph into a preformatted block', function() {
	this.setHTML('<p>Code</p>').show().selectInElement('p');
	this.pushButton('Pre');
	this.assertEqual(0, this.elementCount('p'), 'Should be no paragraphs');
	this.assertEqual(1, this.elementCount('pre'), 'Should be one PRE');
	this.assertEqual('Code', this.elementText('pre'));
});

fixture.add('pre.remove', 'Turning a preformatted block back into a paragraph', function() {
	this.setHTML('<pre>A future paragraph.</pre>').show().selectInElement('pre');
	this.pushButton('Pre');
	this.assertEqual(1, this.elementCount('p'), 'Should be one paragraph');
	this.assertEqual(0, this.elementCount('pre'), 'Should be no PRE\'s');
	this.assertEqual('A future paragraph.', this.elementText('p'));
});

fixture.add('pre.create_multiple', 'Converting multiple paragraphs to PRE blocks', function() {
	var range;
	
	this.setHTML('<p class="one">Some code</p>\n' +
		'<p class="two">Some more code</p>').show();
		
	range = this.createRange();
	Util.Range.set_start(range, this.findElement('p.one').firstChild, 2);
	Util.Range.set_end(range, this.findElement('p.two').firstChild, 7);
	this.selectRange(range);
	
	this.pushButton('Pre');
	this.assertEqual(0, this.elementCount('p'), 'Should be no paragraphs');
	this.assertEqual(2, this.elementCount('pre'), 'Should be two PRE\'s');
	this.assertEqual(1, this.elementCount('pre.one'), 'p.one should retain class');
	this.assertEqual(1, this.elementCount('pre.two'), 'p.two should retain class');
	this.assertEqual('Some code', this.elementText('pre.one'));
	this.assertEqual('Some more code', this.elementText('pre.two'));
});
