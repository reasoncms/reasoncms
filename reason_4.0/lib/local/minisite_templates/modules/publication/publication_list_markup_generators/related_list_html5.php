<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include parent class
 */
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );
	
/**
*  Generates the markup to display a list of related items from other publications.
*  Updates markup to HTML5.
*
*  @author Nathan White
*  @author Nathan Dirks
*/
class RelatedListHTML5MarkupGenerator extends PublicationMarkupGenerator
{
	//yep, we're overloading a private variable from the abstract class  
	//Any children of this should extend get_variables_needed instead of overloading this array.  
	var $variables_needed = array(  'list_item_markup_strings',		//array item_id => markup for list item
									'items_by_section', 
									'no_section_key', 
									'date_format',
									'featured_item_markup_strings',
									'links_to_current_publications',
									); 	

	function RelatedListMarkupGenerator ()
	{
	}

	function run()
	{	
		$this->markup_string .= $this->get_featured_items_markup();
		$this->markup_string .= $this->get_pre_list_markup();
		$this->markup_string .= $this->get_list_markup();
		$this->markup_string .= $this->get_post_list_markup();
	}
	
	function get_pre_list_markup()
	{
		$markup_string = '';
		return $markup_string;
	}
	
	function get_post_list_markup()
	{
		$markup_string = '';
		return $markup_string;
	}

/////
//  List item methods
/////
	
	function get_list_markup() // related list does not use sections
	{
		$markup_string = '';
		$list = $this->passed_vars['items_by_section'][$this->passed_vars['no_section_key']];
		$markup_string .= $this->get_list_markup_for_these_items(array_keys($list))."\n";
		$markup_string .= $this->get_markup_for_pubs_links();
		return $markup_string;
	}
	
	
	/**
	* Returns markup for $item_ids in unordered link format - exclude anything passed in the featured_item_markup_strings array
	* Helper function to {@link get_list_markup}.
	* @param array $item_ids Array of ids of news item entities.
	* @return string Markup for the given items.
	*/	
	function get_list_markup_for_these_items ($item_ids)
	{
		$markup_string = '';
		if(!empty($this->passed_vars['list_item_markup_strings']) && !empty($item_ids))
		{
			$markup_string .= '<div class="posts">'."\n";
			foreach($item_ids as $item_id)
			{
				if(!empty($this->passed_vars['list_item_markup_strings'][$item_id]) && !array_key_exists($item_id, $this->passed_vars['featured_item_markup_strings']))
					$markup_string .= '<article class="post">'.$this->passed_vars['list_item_markup_strings'][$item_id].'</article>'."\n";
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
				$markup_string .= '<article class="post">'.$list_item_string.'</article>'."\n";
			}
			//$markup_string .= '</ul>'."\n";
			$markup_string .= '</div>'."\n";
		}
		return $markup_string;
	}
	
	function get_markup_for_pubs_links()
	{
		$markup_string = '';
		if(!empty($this->passed_vars['links_to_current_publications']))
		{
			$markup_string .= '<ul class="pubLinks">'."\n";
			foreach($this->passed_vars['links_to_current_publications'] as $id => $url)
			{
				$markup_string .= '<li><a href="'.$url.'">'.$this->get_pub_link_text($id).'</a></li>'."\n";
			}
			$markup_string .= '</ul>'."\n";
		}
		return $markup_string;
	}
	function get_pub_link_text($id)
	{
		$pub = new entity($id);
		return 'More '.$pub->get_value('name');
	}
}
?>
