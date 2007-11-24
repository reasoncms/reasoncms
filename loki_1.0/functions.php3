<?php
/**
 * Wraps up the object-oriented method of intantiating Loki in a function
 * @package loki_1
 */

/**
 * Wraps up the object-oriented method of intantiating Loki in a function
 */
function loki($fieldName,$fieldValue="<p></p>",$bitwise='default',$site_id=-1) {

	$loki = new Loki($fieldName, $fieldValue, $bitwise, $site_id);
	$loki->print_form_children();
}

?>