<?php

/**
 * Group type library.
 * @package disco
 * @subpackage plasmature
 */

require_once PLASMATURE_TYPES_INC."default.php";

/**
 * A group encapsulates a list of items and their values.
 * Essentially, this class represents a group of {@link defaultType}s.
 * @package disco
 * @subpackage plasmature
 */
class group extends defaultType
{
	var $type = 'group';
	var $elements = array();
	
	function grab()
	{
		$HTTP_VARS = $this->get_request();
		foreach( $this->elements as $key => $el )
			if ( isset( $HTTP_VARS[ $el->name ] ) )
			{
				$el->set( $HTTP_VARS[ $el->name ] );
				$this->elements[ $key ] = $el;
			}
	}
	
	function get_display()
	{
		$str = '';
		foreach( $this->elements as $key => $el )
			$str .= $el->get_display();
		return $str;
	}
}
