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
 * Generates markup for list items in a related news list, without the descriptions. Also adds a little HTML5 markup.  
 *
 * @author Nathan White
 * @author Nathan Dirks
 *
 */

class RelatedListItemLutherMarkupGenerator extends RelatedListItemMarkupGenerator
{
	//variables needed to be passed from the publication module
	var $variables_needed = array( 	'use_dates_in_list', 
									'date_format', 
									'item',
									'link_to_full_item',
									'teaser_image',
									);

	function RelatedListItemMarkupGenerator ()
	{
	}
	
	function run ()
	{	
		$this->markup_string .= $this->get_teaser_image_markup();
		$this->markup_string .= $this->get_date_markup();
		$this->markup_string .= $this->get_title_markup();
		$this->markup_string .= $this->get_description_markup();
	}
	
/////
// show_list_item methods
/////
	
	function get_date_markup()
	{
		$item = $this->passed_vars['item'];
		if($item->get_value( 'datetime') && $this->passed_vars['use_dates_in_list'] )
		{
			$datetime = prettify_mysql_datetime( $item->get_value( 'datetime' ), $this->passed_vars['date_format'] );
			return  '<time class="date" itemprop="dateCreated">'.$datetime.'</time>'."\n";
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
	
	function get_teaser_image_markup() // {{{
	{
		$markup_string = '';
		$image = $this->passed_vars['teaser_image'];
		if (!empty($image))
		{
			$markup_string .= '<figure class="teaserImage">';
			ob_start();	
			show_image( reset($image), false, false, false, '' , '', true,'' );
			$markup_string .= ob_get_contents();
			ob_end_clean();
			$markup_string .= '</figure>';
		}
		return $markup_string;
	}

	function get_description_markup()
	{
		$item = $this->passed_vars['item'];
		if($item->get_value('description'))
			return '<div class="description">'.$item->get_value('description').'</div>'."\n";
	}

}
?>
