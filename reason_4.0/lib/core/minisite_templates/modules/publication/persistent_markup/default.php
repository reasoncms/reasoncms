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
*  Generates the markup to display a the filtering for a publication 
*  Helper class to the publication minisite module.  
*/
class PublicationsPersistentMarkupGenerator extends PublicationMarkupGenerator
{
	//yep, we're overloading a private variable from the abstract class  
	//Any children of this should extemd get_variables_needed instead of overloading this array.  
	var $variables_needed = array(  'add_item_link',
									'login_logout_link',
									'use_filters',
									'filtering_markup',
								);

	function PublicationsPersistentMarkupGenerator()
	{
	}

	function run()
	{
		$add_item_link = isset($this->passed_vars['add_item_link']) ? trim($this->passed_vars['add_item_link']) : '';
		$login_logout_link = isset($this->passed_vars['login_logout_link']) ? trim($this->passed_vars['login_logout_link']) : '';
			
		if(!empty($this->passed_vars['use_filters']) || !empty($add_item_link) || !empty($login_logout_link))
		{
			$this->markup_string .= '<div class="persistent">'."\n";
			$this->markup_string .= $add_item_link."\n";
			
			if(!empty($this->passed_vars['use_filters']))
			{
				$this->markup_string .= '<div id="filtering">'."\n";
				$this->markup_string .= $this->passed_vars['filtering_markup'];
				$this->markup_string .= '</div>'."\n";
			}
			
			$this->markup_string .= $login_logout_link."\n";
			
			$this->markup_string .= '</div>'."\n"; // close the persistent items
		}
	}
}
?>
