<?php
//reason_include_once( 'minisite_templates/modules/publication/list_item_markup_generators/default.php' );
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );

/**
 * Generates markup for list items in a related news list.  
 *
 * @author Nathan White
 *
 */

class SpotlightListItemMarkupGenerator extends PublicationMarkupGenerator
{
	//variables needed to be passed from the publication module
	var $variables_needed = array( 	
		'use_dates_in_list', 
		'date_format', 
		'item',
		'link_to_full_item',
		'item_images',
		'teaser_image',
		'cur_page',
		'site_id',
		);

	function MinimalListItemMarkupGenerator ()
	{
	}


	function run ()
	{			
		$this->markup_string .= $this->get_pre_markup();
		$this->markup_string .= $this->get_title_markup();
		$this->markup_string .= $this->get_description_markup();
		$this->markup_string .= $this->get_link_to_full_item_markup();
	}
	
/////
// show_list_item methods
/////
	function get_pre_markup()
	{
		return $this->get_teaser_image_markup();
	}

	function get_teaser_image_markup()
	{
		$markup_string = '';
		$image = $this->passed_vars['teaser_image'];
		if (!empty($image))
		{
			$markup_string .= '<figure class="teaserImage">';

			if(is_array($image))
				$image = reset($image);
			
			$rsi = new reasonSizedImage();
			$rsi->set_id($image->id());
			$rsi->set_width(100);
			//$rsi->set_height(275);
			//$rsi->set_crop_style('fill');

			ob_start();	
			show_image( $rsi,true,false,false );
			$markup_string .= ob_get_contents();
			ob_end_clean();
			$markup_string .= '</figure>';
		}
		return $markup_string;
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
			return '<div class="description">'.$item->get_value('description').'</div>'."\n";
	}
	
	
	// Returns the "Continue reading..." link
	function get_link_to_full_item_markup() {
		$markup_string = '';
		if(!empty($this->passed_vars['link_to_full_item']))
			$markup_string .= $this->get_more_link_markup();
		return $markup_string;
	}

	function get_more_link_markup()
	{
		$item = $this->passed_vars['item'];

		// Here we change the link to full article text
		$markup_string = '';
		if(!carl_empty_html($item->get_value('content')) && isset($this->passed_vars['link_to_full_item']) &&  !empty($this->passed_vars['link_to_full_item']))
		{
			$markup_string .=  '<p class="more">';
			$markup_string .=  '<a href="' . $this->passed_vars['link_to_full_item'] .'">';
			$markup_string .=  'Continue reading';
			$markup_string .=  '</a>';
			$markup_string .=  '</p>'."\n";
		}
		return $markup_string;
	}

	function get_permalink_markup()
	{
	}
}
?>
