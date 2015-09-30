<?php
/**
 *  @package reason
 *  @subpackage minisite_modules
 */

/**
 * Include the parent class
 */
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );
	
/**
*  A markup generator that generates no markup
*  Helper class to the publication minisite module.  
*/
class EmptyMarkupGenerator extends PublicationMarkupGenerator
{
	var $variables_needed = array();

	function PublicationsPersistentMarkupGenerator()
	{
	}

	function run()
	{
	}
}
?>