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
*  Generates the markup to display a list of catgory links at the bottom of all posts.
*
*  @author Nathan White
*  @author Andrew Bacon
*
*/
class RelatedListWithCategoriesMarkupGenerator extends PublicationMarkupGenerator
{
	//yep, we're overloading a private variable from the abstract class  
	//Any children of this should extend get_variables_needed instead of overloading this array.  
	var $variables_needed = array(  'list_item_markup_strings',		//array item_id => markup for list item
									'items_by_section', 
									'no_section_key', 
									'date_format',
									'featured_item_markup_strings',
									'links_to_current_publications',
									'site'
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
		$site_id = $this->passed_vars['site']->id();
		$links = $this->passed_vars['links_to_current_publications'];
		$pub_ids = array_keys($links);
		$pub_ents = array();
		/* $category_list = array(
		 *     'some_pub_id' => array(
		 *         'some_cat',
		 *         'another_cat'
 		 *     ),
 		 *     ...
		 * );
		*/
		// This is about to become a monstrous, horrendous hack.
		// Probably the least efficient, most annoying code I've 
		// ever written, no joke. It is gross.
		$category_list = array();
		$flat_cat_list = array();
		$duplicate_cat_list = array();
		// For each publication scraped by this list
		foreach ($pub_ids as $pub) {
			// Get the pub entities and throw them in $pub_ents for their names later
			$pub_ents[$pub] = new entity($pub);
			// Return all news posts that belong to $pub, along with their categories
			$category_list[$pub] = array();
			$es = new entity_selector();
			$es->add_type(id_of('news'));
			$es->add_left_relationship($pub, relationship_id_of('news_to_publication'));
			$es->enable_multivalue_results();
			$es->add_left_relationship_field('news_to_category', 'entity', 'id', 'category_ids');
//			$es->add_left_relationship_field('news_to_publication', 'entity', 'id', 'publication_ids');
			// For each news post returned
			foreach ($es->run_one() as $result) {
				// If it has multiple categories attached
				if (is_array($result->get_value('category_ids')))
				{
					// For each one
					foreach($result->get_value('category_ids') as $cat_id) {
						// If this category hasn't been seen in this publication yet
						if (!in_array($cat_id, array_keys($category_list[$pub]))) {
							// Get its entity, and put it in an array that organizes it by pub.
							$cat_ent = new entity($cat_id);
							if ($cat_ent->get_value('state') == 'Live') {
								$category_list[$pub][$cat_id] = $cat_ent;
								// If we've seen this category name before at all
								if (in_array($cat_ent->get_value('name'), $flat_cat_list))
									// Add it to the duplicate cat list. 
									$duplicate_cat_list[$cat_id] = $cat_ent->get_value('name');
								else 
									// Just go ahead and add it to the flat_cat_list.
									$flat_cat_list[$cat_id] = $cat_ent->get_value('name');
							}
						}
					}
				// If there's only one category attached to the post, and we haven't seen it before in this publication
				} 
				elseif (!in_array($result->get_value('category_ids'), array_keys($category_list[$pub]))) {
					// Add it to the list!
					$cat_ent = new entity($result->get_value('category_ids'));
					if ($cat_ent->get_value('state') == 'Live') {
						$category_list[$pub][$result->get_value('category_ids')] = $cat_ent;
						// If we've seen this category name before at all
						if (in_array($cat_ent->get_value('name'), $flat_cat_list))
							// Add it to the duplicate cat list. 
							$duplicate_cat_list[$cat_end->id()] = $cat_ent->get_value('name');
						else 
							// Just go ahead and add it to the flat_cat_list.
							$flat_cat_list[$cat_ent->id()] = $cat_ent->get_value('name');
					}
				}
			}
		}
		$markup_string .= '<h4 class="categoryHeading">More articles about...</h4>';
		$markup_string .= '<ul class="all_cats_links">';
		foreach ($pub_ids as $publication_id) {
			$publication_url = $links[$publication_id];
			foreach ($category_list[$publication_id] as $category) {
					$category_url = '?filters[1][type]=category&filters[1][id]='.$category->id();
					$markup_string .= '<li class="' . $publication_id . '_cat"><a href="' . $publication_url . $category_url . '">' . $category->get_value('name');
					if (in_array($category->get_value('name'), $duplicate_cat_list)) 
						$markup_string .= " (" . $pub_ents[$publication_id]->get_value("name") . ")";
					$markup_string .= '</a></li>';
				}
		}
		$markup_string .= '</ul>';
		
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
