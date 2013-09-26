<?php
/**
 *  @package reason
 *  @subpackage minisite_modules
 */

/**
 * Include the parent class
 */
reason_include_once( 'minisite_templates/modules/publication/persistent_markup/default.php' );
	
/**
*  Generates the markup to display a the filtering for a publication 
*  Helper class to the publication minisite module.  
*/
class PublicationsPersistentListsOnlyMarkupGenerator extends PublicationsPersistentMarkupGenerator
{

	function PublicationsPersistentListsOnlyMarkupGenerator()
	{
		$this->variables_needed[] = 'item';
	}

	function run()
	{
		if(!empty($this->passed_vars['item']))
			return;
		parent::run();
	}
}
?>
