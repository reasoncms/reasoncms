<?php

/**
 * Presentational types: types that do not actually take values.
 *
 * @package disco
 * @subpackage plasmature
 */

require_once PLASMATURE_TYPES_INC."default.php";

/**
 * Displays a comment that extends across both columns.
 * @package disco
 * @subpackage plasmature
 */
class commentType extends defaultType
{
	var $type = 'comment';
	var $text = 'Comment';
	var $type_valid_args = array( 'text' );
	var $_labeled = false;
	function grab()
	{
	}
	function get_display()
	{
		return $this->text;
	}
}

/**
 * Displays a comment that sits next to a regular field label.
 * @package disco
 * @subpackage plasmature
 */
class commentWithLabelType extends commentType {
	var $type = 'commentWithLabel';
	var $_labeled = true;
}

/**
 * Displays an <hr /> tag.
 * @package disco
 * @subpackage plasmature
 */
class hrType extends defaultType
{
	var $type = 'hr';
	var $_labeled = false;
	function get_display()
	{
		return '<hr />';
	}
}
