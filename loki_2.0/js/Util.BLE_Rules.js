/**
 * Does nothing.
 *
 * @constructor
 *
 * @class <p>Contains rules used used to determine how to clean
 * block-level elements and their children.</p>
 *
 * <p>JSDoc doesn't work well with this file. See the code and
 * comments for more details about how it works.</p>
 */
Util.BLE_Rules = function()
{
};

/**
 * <p>Includes elements which can contain paragraphs. Any
 * children of one of these elements should be paragraphs; text should
 * be in child nodes of those paragraphs, rather than in child nodes
 * of the element itself. When the user requests vertical whitespace
 * (i.e., presses enter) inside one of these elements, there should
 * come to exist two child paragraphs, which should be siblings.</p>
 *
 * <p>ccp was formerly can_contain_p.</p>
 */
Util.BLE_Rules.ccp = new Object;
Util.BLE_Rules.ccp.rule = 'object|ins|del|dd|blockquote|body|dd|form|fieldset|button|map|noscript';
Util.BLE_Rules.ccp.regexp = new RegExp( '(' + Util.BLE_Rules.ccp.rule + ')', 'i' );

/**
 * <p>Includes elements which cannot contain paragraphs. When
 * the user requests vertical whitespace inside one of these elements,
 * there should come to exist two such elements, and they should be
 * siblings.</p>
 *
 * <p>cncp was formerly cannot_contain_p</p>
 */
Util.BLE_Rules.cncp = new Object;
Util.BLE_Rules.cncp.rule = 'p|h1|h2|h3|h4|h5|h6|address';
Util.BLE_Rules.cncp.regexp = new RegExp( '(' + Util.BLE_Rules.cncp.rule + ')', 'i' );

/**
 * <p>Includes elements which can contain paragraphs. Unless
 * the user requests vertical whitespace in one of these elements,
 * text should be in child nodes of the element itself, rather than in
 * child nodes of child paragraphs. But if the user requests vertical
 * whitespace, the original text *should* come to be in a child node
 * of a child paragraph, and the new text should be in a child node of
 * a paragraph which is a sibling of the first paragraph.</p>
 *
 * <p>ccomtop was formerly can_contain_only_more_than_one_p</p>
 */
Util.BLE_Rules.ccomtop = new Object;
Util.BLE_Rules.ccomtop.rule = 'div|th|td';
Util.BLE_Rules.ccomtop.regexp = new RegExp( '(' + Util.BLE_Rules.ccomtop.rule + ')', 'i' );

/**
 * <p>Includes elements which cannot contain paragraphs, but
 * which, if the user requests vertical whitespace, should contain the
 * following as child nodes: text node, br elem, br elem, text
 * node.</p>
 *
 * <p>ccdbbnp was formerly can_contain_double_brs_but_not_p</p>
 */
Util.BLE_Rules.ccdbbnp = new Object;
Util.BLE_Rules.ccdbbnp.rule = 'li|pre';
Util.BLE_Rules.ccdbbnp.regexp = new RegExp( '(' + Util.BLE_Rules.ccdbbnp.rule + ')', 'i' );

/**
 * <p>Includes elements in all the other categories. Note that
 * <code>all_ble</code> is set to the *value* of the function, not to
 * the function itself.</p>
 *
 * <p>all_ble was formerly all_block_level_elements</p>
 */
Util.BLE_Rules.all_ble = function()
{
	var all_ble = new Object;

	// concatenate all the other rules
	all_ble.rule = '';
	for ( var i in Util.BLE_Rules )
	{
		all_ble.rule += Util.BLE_Rules[i].rule + '|';
	}
	all_ble.rule = all_ble.rule.slice(0, -1);

	// make regex for this concatenated rule
	all_ble.regexp = new RegExp( '(' + all_ble.rule + ')', 'i' );

	return all_ble;
}();
