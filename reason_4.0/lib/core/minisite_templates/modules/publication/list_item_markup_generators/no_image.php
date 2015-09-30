<?php
/**
 *  @package reason
 *  @subpackage minisite_modules
 */
 
 /**
  * Include the base class
  */
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );


/**
*  Generates markup for list items in a related news list, without thumbnail images. 
*
*
*  @author Meg Gibbs
*  @author Nathan Dirks
*
*/
class ListItemNoImageMarkupGenerator extends PublicationMarkupGenerator
{
	//variables needed to be passed from the publication module
	var $variables_needed = array( 	'use_dates_in_list', 
									'date_format', 
									'item',
									'link_to_full_item', 
									);
	
	function ListItemNoImageMarkupGenerator ()
	{
	}
	
	function run ()
	{
		$this->markup_string .= $this->get_title_markup();
		$this->markup_string .= $this->get_date_markup();
		$this->markup_string .= $this->get_description_markup();
	}
	
/////
// show_list_item methods
/////
	
	function get_pre_markup()
	{
	}
	
	function get_teaser_image_markup() // {{{
	{
	}
	
	function get_title_markup()
	{
		$markup_string = '';
		$item = $this->passed_vars['item'];
		$link_to_full_item = isset($this->passed_vars['link_to_full_item']) ? $this->passed_vars['link_to_full_item'] : '';
				
		$markup_string .=  '<h4 class="title">';
		if(isset($link_to_full_item) &&  !empty($link_to_full_item))
			$markup_string .=  '<a href="' .$link_to_full_item. '">'.$item->get_value('release_title').'</a>';
		else
			$markup_string .= $item->get_value('release_title');
		$markup_string .=  '</h4>'."\n";
		return $markup_string;
	}
	
	function get_date_markup()
	{
		$item = $this->passed_vars['item'];
		if($item->get_value( 'datetime') && empty($this->passed_vars['current_issue']) && $this->passed_vars['use_dates_in_list'])
		{
			$datetime = prettify_mysql_datetime( $item->get_value( 'datetime' ), $this->passed_vars['date_format'] );
			return  '<div class="date">'.$datetime.'</div>'."\n";
		}
	}

	function get_description_markup()
	{
		$item = $this->passed_vars['item'];
		if($item->get_value('description'))
			return '<div class="desc">'.$item->get_value('description').'</div>'."\n";
	}

}
?>