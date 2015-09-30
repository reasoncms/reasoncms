fixture.add('br.insert', 'Inserting a line break in text', function() {
	var text;
	
	this.setHTML('<p>textjuice</p>').show();
	this.selectInElement('p', 4);
	this.pushButton('BR');
	this.log(this.getHTML());
	
	this.assertEqual(1, this.elementCount('p'), 'Should be one paragraph');
	this.assertEqual(1, this.elementCount('p > br'),
		'Paragraph should have one BR child');
	
	text = Util.Node.find_children(this.findElement('p'), Util.Node.TEXT_NODE);
	this.assertEqual(2, text.length, 'Should be two text nodes');
	this.assertEqual('text', text[0].nodeValue);
	this.assertEqual('juice', text[1].nodeValue);
	
	this.assertSame(this.findElement('p > br'), text[0].nextSibling,
		'First text node should precede the BR');
	this.assertSame(this.findElement('p > br'), text[1].previousSibling,
		'First text node should follow the BR');
});
