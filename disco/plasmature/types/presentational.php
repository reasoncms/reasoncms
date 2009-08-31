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
	var $colspan = 2;
	var $type_valid_args = array( 'text' );
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
	var $colspan = null;
	var $type = 'commentWithLabel';
}

/**
 * Displays an <hr /> tag.
 * @package disco
 * @subpackage plasmature
 */
class hrType extends defaultType
{
	var $type = 'hr';
	function get_display()
	{
		return '<hr />';
	}
}
