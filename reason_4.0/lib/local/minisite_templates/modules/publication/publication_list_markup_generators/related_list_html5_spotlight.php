<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include parent class
 */
reason_include_once( 'minisite_templates/modules/publication/publication_list_markup_generators/related_list_html5.php' );
	
/**
*
* This extends our HTML5 related list markup generator. 
* All it does is add the class "spotlight" to articles.
*
*/

class RelatedListHTML5SpotlightMarkupGenerator extends RelatedListHTML5MarkupGenerator
{


	function get_list_markup_for_these_items ($item_ids)
	{
		$markup_string = '';
		if(!empty($this->passed_vars['list_item_markup_strings']) && !empty($item_ids))
		{
			$markup_string .= '<div class="posts">'."\n";
			foreach($item_ids as $item_id)
			{
				if(!empty($this->passed_vars['list_item_markup_strings'][$item_id]) && !array_key_exists($item_id, $this->passed_vars['featured_item_markup_strings']))
					$markup_string .= '<article class="post spotlight">'.$this->passed_vars['list_item_markup_strings'][$item_id].'</article>'."\n";
			}
			$markup_string .= '</div>'."\n";
		}
		return $markup_string;
	}
	
//////
// Featured item methods
//////
	
	function get_featured_items_markup()
	{
		$markup_string = '';
		$featured_items = $this->passed_vars['featured_item_markup_strings'];

		if(!empty($featured_items))
		{
			$feature_header_string = '';
			
			if(count($featured_items) > 1)
			{
				if (!empty($feature_header_string)) $feature_header_string .= 's';
			}
			
			$markup_string = '<div id="featuredItems" class="posts">'."\n";
			if (!empty($feature_header_string)) $markup_string .= '<h3> '.$feature_header_string.' </h3>'."\n";
			
			//$markup_string .= '<ul class="posts">'."\n";
			foreach($this->passed_vars['featured_item_markup_strings'] as $list_item_string)
			{
				$markup_string .= '<article class="post spotlight">'.$list_item_string.'</article>'."\n";
			}
			//$markup_string .= '</ul>'."\n";
			$markup_string .= '</div>'."\n";
		}
		return $markup_string;
	}
	
}
?>
