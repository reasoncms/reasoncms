<?php

/**
 * Default with Tables
 * @package reason
 * @subpackage minisite_templates
 */

// include the MinisiteTemplate class
reason_include_once( 'minisite_templates/default.php' );
// this variable must be the same as the class name
$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'tablesTemplate';

/**
 * Default with Tables
 *
 * Extends the default template to turn tables on. Includes no branding.
 * @author Ben Cochran
 */
class tablesTemplate extends MinisiteTemplate
{
	var $use_tables = true;
}