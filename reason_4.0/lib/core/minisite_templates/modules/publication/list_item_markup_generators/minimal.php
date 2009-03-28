<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include base class
 */
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );

/**
 * Generates markup for list items in a related news list.  
 *
 * @author Nathan White
 *
 */

class MinimalListItemMarkupGenerator extends PublicationMarkupGenerator
{
	//variables needed to be passed from the publication module
	var $variables_needed = array( 	'use_dates_in_list', 
									'date_format', 
									'item',
									'link_to_full_item',
									'teaser_image',
									);

	function MinimalListItemMarkupGenerator ()
	{
	}
	
	function run ()
	{	
		$this->markup_string .= $this->get_date_markup();
		$this->markup_string .= $this->get_title_markup();
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
			return  '<div class="date">'.$datetime.'</div>'."\n";
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

}
?>
