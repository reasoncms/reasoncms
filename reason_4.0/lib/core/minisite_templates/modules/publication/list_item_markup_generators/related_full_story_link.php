<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include base class
 */
reason_include_once( 'minisite_templates/modules/publication/list_item_markup_generators/related_item.php' );

/**
 * Extends the basic related list item markup generator to add a "full story" link after the description. 
 *
 * @author Nathan White
 *
 */

class RelatedFullStoryLinkListItemMarkupGenerator extends RelatedListItemMarkupGenerator
{

	function run ()
	{	
		$this->markup_string .= $this->get_teaser_image_markup();
		$this->markup_string .= $this->get_date_markup();
		$this->markup_string .= $this->get_title_markup();
		$this->markup_string .= $this->get_description_markup();
		$this->markup_string .= $this->get_links_markup();
	}
	
	function get_links_markup()
	{
		$markup_string =  '<ul class="links">'."\n";
		if(!empty($this->passed_vars['link_to_full_item']))
			$markup_string .= $this->get_more_link_markup();
		if(!empty($this->passed_vars['permalink']))
			$markup_string .= $this->get_permalink_markup();
		if(!empty($this->passed_vars['item_comment_count']))
			$markup_string .= $this->get_comment_link_markup();
		$markup_string .= '</ul>'."\n";
		return $markup_string;
	}

	function get_more_link_markup()
	{
		$item = $this->passed_vars['item'];

		$markup_string = '';
		if($item->get_value('content') && isset($this->passed_vars['link_to_full_item']) &&  !empty($this->passed_vars['link_to_full_item']))
		{
			$markup_string .=  '<li class="more">';
			$markup_string .=  '<a href="' . $this->passed_vars['link_to_full_item'] .'" title="'.reason_htmlspecialchars( $item->get_value('release_title'), ENT_QUOTES, 'UTF-8' ).'">';
			$markup_string .=  'Full Story';
			$markup_string .=  '</a>';
			$markup_string .=  '</li>'."\n";
		}
		return $markup_string;
	}
}
?>
