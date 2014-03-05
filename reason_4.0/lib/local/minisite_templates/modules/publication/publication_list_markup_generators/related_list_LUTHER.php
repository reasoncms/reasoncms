<?php
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );
	
/**
*  Generates the markup to display a list of related items from other publications
*
*  @author Nathan White
*/
class RelatedListMarkupGenerator extends PublicationMarkupGenerator
{
	//yep, we're overloading a private variable from the abstract class  
	//Any children of this should extend get_variables_needed instead of overloading this array.  
	var $variables_needed = array(  'list_item_markup_strings',		//array item_id => markup for list item
									'items_by_section', 
									'no_section_key', 
									'date_format',
									'featured_item_markup_strings',
									'links_to_current_publications',
									'cur_page',
									); 	

	function RelatedListMarkupGenerator ()
	{
	}

	function run()
	{
		$this->markup_string .= $this->get_featured_items_markup();
		$this->markup_string .= $this->get_list_markup();
		$this->markup_string .= $this->get_post_list_markup();
	}
	
	function get_post_list_markup()
	{
		$markup_string = '';
		$url = end($this->passed_vars['links_to_current_publications']);
		reset($this->passed_vars['links_to_current_publications']);
		if(count($this->passed_vars['links_to_current_publications']) == 1
			&& preg_match('/spotlight/', $url) === 0)
		{
			$markup_string .= '<nav class="button view-all">'."\n";
			$markup_string .= '<ul>'."\n";
			if (preg_match('/headlines/', $url) === 1)
			{
				$markup_string .= '<li><a href="'.$url.'">View all news &gt;</a></li>'."\n";
			}
			else
			{
				$markup_string .= '<li><a href="'.$url.'">View all &gt;</a></li>'."\n";
			}
			$markup_string .= '</ul>'."\n";
			$markup_string .= '</nav>'."\n";
		}
		/*// Create "View all news" link for related publications that are minisite headlines
		$is_headline = false;
		if(!empty($this->passed_vars['links_to_current_publications']))
		{
			foreach($this->passed_vars['links_to_current_publications'] as $id => $url)
			{
				if (preg_match('/headline/', $url))
				{
					$is_headline = true;
					break;
				}
			}
		}
		if ($is_headline)
		{
			$markup_string .= '<nav class="button view-all">'."\n";
			$markup_string .= '<ul>'."\n";
			$markup_string .= '<li><a href="'.$url.'">View all news &gt;</a></li>'."\n";
			$markup_string .= '</ul>'."\n";
			$markup_string .= '</nav>'."\n";
		}*/
		
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
		//$markup_string .= $this->get_markup_for_pubs_links();
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
			$markup_string .= '<ul class="posts">'."\n";
			foreach($item_ids as $item_id)
			{
				if(!empty($this->passed_vars['list_item_markup_strings'][$item_id]) && !array_key_exists($item_id, $this->passed_vars['featured_item_markup_strings']))
					$markup_string .= '<li class="post">'.$this->passed_vars['list_item_markup_strings'][$item_id].'</li>'."\n";
			}
			$markup_string .= '</ul>'."\n";
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
			
			$markup_string = '<div id="featuredItems">'."\n";
			if (!empty($feature_header_string)) $markup_string .= '<h3> '.$feature_header_string.' </h3>'."\n";
			
			$markup_string .= '<ul class="posts">'."\n";
			foreach($this->passed_vars['featured_item_markup_strings'] as $list_item_string)
			{
				$markup_string .= '<li class="post">'.$list_item_string.'</li>'."\n";
			}
			$markup_string .= '</ul>'."\n";
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
