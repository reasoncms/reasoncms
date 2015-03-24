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
 * Generates markup for list items in a related news list with teaser image.  
 *
 * @author Nathan White
 *
 */

class CloakMinimalWithImageListItemMarkupGenerator extends PublicationMarkupGenerator
{
	//variables needed to be passed from the publication module
	var $variables_needed = array( 	'use_dates_in_list', 
									'date_format', 
									'item',
									'link_to_full_item',
									'teaser_image',
									);

	function MinimalWithImageListItemMarkupGenerator ()
	{
	}
	
	function run ()
	{	
		$this->markup_string .= $this->get_content_wrapper_markup();
	}
	
/////
// show_list_item methods
/////

	function get_content_wrapper_markup()
	{
		$link_to_full_item = $this->passed_vars['link_to_full_item'];

		$markup_string = '';
		if(isset($link_to_full_item) &&  !empty($link_to_full_item))
			$markup_string .=  '<a href="' .$link_to_full_item. '">';

		$markup_string .= '<div class="postInner" data-equalizer-watch>';
			$markup_string .= $this->get_teaser_image_markup();
			$markup_string .= $this->get_date_title_markup();
		$markup_string .=  '</div>';

		if(isset($link_to_full_item) &&  !empty($link_to_full_item))
			$markup_string .=  '</a>';
		return $markup_string;
	}

	function get_teaser_image_markup()
	{
		$markup_string = '';
		$image = $this->passed_vars['teaser_image'];
		if (!empty($image))
		{
			$markup_string .= '<div class="teaserImage">';

			if(is_array($image))
				$image = reset($image);

			$rsi = new reasonSizedImage();
			$rsi->set_id($image->id());
			$rsi->set_width(300);
			$rsi->set_height(200);
			$rsi->set_crop_style('fill');
			ob_start();	
			show_image( $rsi, true, false, false, '');
			$markup_string .= ob_get_contents();
			ob_end_clean();

			$markup_string .= '</div>';
		} 
		return $markup_string;
	}

	function get_date_title_markup()
	{
		$link_to_full_item = $this->passed_vars['link_to_full_item'];

		$markup_string = '';
		$markup_string .=  '<div class="postContent">';
			$markup_string .= $this->get_date_markup();
			$markup_string .= $this->get_title_markup();
		$markup_string .=  '</div>';
		return $markup_string;
	}
	
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
				
		$markup_string .=  '<h4>';
		$markup_string .= $item->get_value('release_title');
		$markup_string .=  '</h4>'."\n";
		return $markup_string;
	}

}
?>
