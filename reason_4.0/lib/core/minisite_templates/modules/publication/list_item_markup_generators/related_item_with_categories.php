<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include base class
 */
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );
reason_include_once( 'classes/sized_image.php' );

/**
 * Generates markup for list items in a related news list, and shows the categories for each list item.  
 *
 * @author Nathan White
 *
 */

class RelatedListItemWithCategoriesMarkupGenerator extends PublicationMarkupGenerator
{
	//variables needed to be passed from the publication module
	var $variables_needed = array( 	'use_dates_in_list', 
									'date_format', 
									'item',
									'link_to_full_item',
									'teaser_image',
									'item_categories',
									'item_publication',
									'links_to_current_publications'
									);

	function RelatedListItemMarkupGenerator ()
	{
	}
	
	function run ()
	{	
		$this->markup_string .= $this->get_teaser_image_markup();
		$this->markup_string .= $this->get_title_markup();
		$this->markup_string .= $this->get_date_markup();
		$this->markup_string .= $this->get_item_category_markup();
		$this->markup_string .= $this->get_description_markup();
	}
	
/////
// show_list_item methods
/////
	
	function get_item_category_markup()
	{
		$pub_links = $this->passed_vars['links_to_current_publications'];
		$owner_pub_id = $this->passed_vars['item_publication']->id();
		$correct_pub_link = $pub_links[$owner_pub_id];
		$link_markup = array();
		foreach ($this->passed_vars['item_categories'] as $cat)
		{
			$link_markup[] = '<li class="post-cat"><a href="' . $correct_pub_link . $cat->get_value('category_url') . '">' . $cat->get_value('name') . '</a></li>';
		}
		$markup_string = '';
		if(!empty($link_markup))
		{
			$markup_string .= '<ul class="post-cats">';
			$markup_string .= implode("\n ", $link_markup);
			$markup_string .= '</ul>';
		}
		return $markup_string;
	}
	
	function get_date_markup()
	{
		$item = $this->passed_vars['item'];
		if($item->get_value( 'datetime') && $this->passed_vars['use_dates_in_list'] )
		{
			$datetime = prettify_mysql_datetime( $item->get_value( 'datetime' ), $this->passed_vars['date_format'] );
			return  '<div class="date"><span>'.$datetime.'</span> in </div>'."\n";
		}
	}
	
	function get_title_markup()
	{
		$markup_string = '';
		$item = $this->passed_vars['item'];
		$link_to_full_item = $this->passed_vars['link_to_full_item'];
				
		$markup_string .=  '<h4>';
		if(isset($link_to_full_item) &&  !empty($link_to_full_item))
			$markup_string .=  '<a href="' .$link_to_full_item. '">'.$item->get_value('release_title').'</a>';
		else
			$markup_string .= $item->get_value('release_title');
		$markup_string .=  '</h4>'."\n";
		return $markup_string;
	}

	function get_description_markup()
	{
		$item = $this->passed_vars['item'];
		if($item->get_value('description'))
			return '<div class="desc">'.$item->get_value('description').'</div>'."\n";
	}
	
	function get_teaser_image_markup() // {{{
	{
		$markup_string = '';
		$image = $this->passed_vars['teaser_image'];
		if (!empty($image))
		{
			$markup_string .= '<div class="teaserImage">';
			ob_start();	
			show_image( reset($image), false, false, false, '' , '', true,'' );
			$markup_string .= ob_get_contents();
			ob_end_clean();
			$markup_string .= '</div>';
		} 
		return $markup_string;
	}

}
?>
