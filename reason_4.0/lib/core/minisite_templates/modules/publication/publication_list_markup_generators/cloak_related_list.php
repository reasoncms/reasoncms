<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include parent class
 */
reason_include_once( 'minisite_templates/modules/publication/publication_list_markup_generators/related_list.php' );
	
/**
*  Generates the markup to display a list of related items from other publications
*
*  @author Nathan White
*/
class CloakRelatedListMarkupGenerator extends RelatedListMarkupGenerator
{
	
	function get_list_markup_for_these_items ($item_ids)
	{
		$markup_string = '';
		if(!empty($this->passed_vars['list_item_markup_strings']) && !empty($item_ids))
		{
			$markup_string .= '<div id="normalItems" data-equalizer >'."\n";
			$markup_string .= '<ul class="posts">'."\n";
			foreach($item_ids as $item_id)
			{
				if(!empty($this->passed_vars['list_item_markup_strings'][$item_id]) && !array_key_exists($item_id, $this->passed_vars['featured_item_markup_strings']))
					$markup_string .= '<li class="post">'.$this->passed_vars['list_item_markup_strings'][$item_id].'</li>'."\n";
			}
			$markup_string .= '</ul>'."\n";
			$markup_string .= '</div>'."\n";
		}
		return $markup_string;
	}
}
?>
