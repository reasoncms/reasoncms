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
*  A basic post listing page that displays the posts' categories after the publication date and images at custom sizes.
*
*/

class MagazinePublicationListItemMarkupGenerator extends PublicationMarkupGenerator
{
	//variables needed to be passed from the publication module
	var $variables_needed = array( 	'use_dates_in_list', 
									'date_format', 
									'item',
									'item_comment_count', 
									'link_to_full_item', 
									'permalink',
									'teaser_image',
									'item_categories',
									'publication',
									'current_issue',
								  	'commenting_status',
									);
	
	var $show_section_name = false;

	function PublicationListItemMarkupGenerator ()
	{
	}
	
	function run ()
	{	
		//$this->markup_string .= $this->get_item_wrapper_markup();
		$this->markup_string .= $this->get_teaser_image_markup();
		//$this->markup_string .= $this->get_item_category_markup();
		//$this->markup_string .= $this->get_title_markup();
		//$this->markup_string .= $this->get_description_markup();
		$this->markup_string .= $this->get_content_block_markup();
		
		//$this->markup_string .= $this->this_is_a_filtered_mofo();
		
		//$this->markup_string .= $this->get_section_markup();
		//$this->markup_string .= $this->get_links_markup();
	}

	// function get_item_wrapper_markup()
	// {

	// 	foreach ($this->passed_vars['item_categories'] as $cat)
	// 	{
	// 		$name = $cat->get_value('name');

	// 		// $section_entity = $this->passed_vars['sections'][$section_id];
	// 		// $name = $section_entity->get_value('name');
			
	// 		// //Lower case everything
	// 		$name = strtolower($name);
	// 		// //Make alphanumeric (removes all other characters)
	// 		$name = preg_replace("/[^a-z0-9_\s-]/", "", $name);
	// 		// //Clean up multiple dashes or whitespaces
	// 		$name = preg_replace("/[\s-]+/", " ", $name);
	// 		// //Convert whitespaces and underscore to dash
	// 		$name = preg_replace("/[\s_]/", "-", $name);
	// 	}
	// 	$categories_as_classes = implode("\n ", $name);
		

	// 	// // Change section name into a css-class-friendly string
	// 	// $section_entity = $this->passed_vars['sections'][$section_id];
	// 	// $name = $section_entity->get_value('name');
	// 	// //Lower case everything
	// 	// $name = strtolower($name);
	// 	// //Make alphanumeric (removes all other characters)
	// 	// $name = preg_replace("/[^a-z0-9_\s-]/", "", $name);
	// 	// //Clean up multiple dashes or whitespaces
	// 	// $name = preg_replace("/[\s-]+/", " ", $name);
	// 	// //Convert whitespaces and underscore to dash
	// 	// $name = preg_replace("/[\s_]/", "-", $name);

	// 	//$markup_string .= '<article class="post '. $name .'"><div class="inner">'."\n";
	// 	$markup_string .= '<article class="post"><div class="inner">'."\n";
	// 	//$markup_string .= $name;
	// 	$markup_string .= $this->get_teaser_image_markup();
	// 	$markup_string .= $this->get_content_block_markup();
	// 	$markup_string .= '</div></article>'."\n";
	// 	return $markup_string;
	// }
	
	function get_pre_markup()
	{
		return $this->get_teaser_image_markup();
	}

	// function this_is_a_filtered_mofo()
	// {	
	// 	$msg = '';
	// 	$msg .= 'HI!';
	// 	if(!empty($this->passed_vars['current_filters']))
	// 	{
	// 		$msg = '<h1>This is filtered, yo!</h1>';
	// 	}
	// 	return $msg;
	// }
	
	function get_item_category_markup()
	{
		$link_markup = array();
		$markup_string = '<ul class="post-cats">';
		foreach ($this->passed_vars['item_categories'] as $cat)
		{
			$class_name = $cat->get_value('name');
			// //Lower case everything
			$class_name = strtolower($class_name);
			// //Make alphanumeric (removes all other characters)
			$class_name = preg_replace("/[^a-z0-9_\s-]/", "", $class_name);
			// //Clean up multiple dashes or whitespaces
			$class_name = preg_replace("/[\s-]+/", " ", $class_name);
			// //Convert whitespaces and underscore to dash
			$class_name = preg_replace("/[\s_]/", "-", $class_name);

			$link_markup[] = '<li class="post-cat ' . $class_name . '"><a href="' . carl_make_link( array(), '', '', false, false) . $cat->get_value('category_url') . '">' . $cat->get_value('name') . '</a></li>';
		}
		$markup_string .= implode("\n ", $link_markup);
		$markup_string .= '</ul>';
		return $markup_string;
	}

	// FULL SIZE TEASER IMG MARKUP
	function get_teaser_image_markup() // {{{
	{
		$markup_string = '';
		$image = current($this->passed_vars['teaser_image']);
		$link_to_full_item = isset($this->passed_vars['link_to_full_item']) ? $this->passed_vars['link_to_full_item'] : '';
		if (!empty($image))
		{
			$markup_string .= '<figure class="primaryImage">';
			if(isset($link_to_full_item) &&  !empty($link_to_full_item))
				$markup_string .=  '<a href="' .$link_to_full_item. '">';
				
			$markup_string .= '<img src="'.WEB_PHOTOSTOCK.reason_get_image_filename( $image->id() ).'" alt="'.str_replace('"', "'", $image->get_value( 'description' )).'"/>';

			if(isset($link_to_full_item) &&  !empty($link_to_full_item))
				$markup_string .=  '</a>';
			
			$markup_string .= '</figure>';
		} 
		else {
			$markup_string .= '<figure class="primaryImage">';
			if(isset($link_to_full_item) &&  !empty($link_to_full_item))
				$markup_string .=  '<a href="' .$link_to_full_item. '">';
			//$markup_string .= '<img src="'.WEB_PHOTOSTOCK.reason_get_image_filename( $image->id() ).'" width="'.$image->get_value( 'width' ).'" height="'.$image->get_value( 'height' ).'" alt="'.str_replace('"', "'", $image->get_value( 'description' )).'"/>';
			
			//$markup_string .= '<img src="http://dirks.luther.edu/reason/images/542544.jpg" />';
				$markup_string .= '<img src="/reason/images/562005.jpg" />'; 

			if(isset($link_to_full_item) &&  !empty($link_to_full_item))
			$markup_string .=  '</a>';
			$markup_string .= '</figure>';
		}
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

	// CUSTOM TEASER IMG SIZE MARKUP
	// function get_teaser_image_markup()
	// {
	// 	$markup_string = '';
	// 	$image = $this->passed_vars['teaser_image'];
	// 	if (!empty($image))
	// 	{
	// 		$markup_string .= '<figure class="teaserImage">';

	// 		if(is_array($image))
	// 			$image = reset($image);
			
	// 		$rsi = new reasonSizedImage();
	// 		$rsi->set_id($image->id());
	// 		$rsi->set_width(400);
	// 		$rsi->set_height(250);
	// 		$rsi->set_crop_style('fill');

	// 		ob_start();	
	// 		show_image( $rsi,true,false,false );
	// 		$markup_string .= ob_get_contents();
	// 		ob_end_clean();
	// 		//$markup_string .= '<img src="/reason/sized_images/540620/b25879bda30d8a9542e03ab9670e730e.gif?cb=1397165736">';
	// 		$markup_string .= '</figure>';
	// 	}
	// 	return $markup_string;
	// }

	// ORIGINAL TEASER IMG MARKUP	
	// function get_teaser_image_markup() // {{{
	// {
	// 	$markup_string = '';
	// 	$image = $this->passed_vars['teaser_image'];
	// 	if (!empty($image))
	// 	{
	// 		$markup_string .= '<div class="teaserImage">';
	// 		ob_start();	
	// 		show_image( reset($image), true,false,false );
	// 		$markup_string .= ob_get_contents();
	// 		ob_end_clean();
	// 		$markup_string .= '</div>';
	// 	} 
	// 	return $markup_string;
	// }

	function get_content_block_markup() {
		$link_to_full_item = isset($this->passed_vars['link_to_full_item']) ? $this->passed_vars['link_to_full_item'] : '';
		
		$markup_string .=  '<div class="content-block">';
		$markup_string .= $this->get_date_markup();
		$markup_string .= $this->get_item_category_markup();

		if(isset($link_to_full_item) &&  !empty($link_to_full_item))
			$markup_string .=  '<a href="' .$link_to_full_item. '">';
		
		$markup_string .= $this->get_title_markup();
		$markup_string .= $this->get_description_markup();
		
		if(isset($link_to_full_item) &&  !empty($link_to_full_item))
			$markup_string .=  '</a>';
		$markup_string .=  '</div>';
		return $markup_string;
	}
	
	function get_title_markup()
	{
		$markup_string = '';
		$item = $this->passed_vars['item'];
		$markup_string .=  '<h4 class="title">';
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
	
	// function get_title_markup()
	// {
	// 	$markup_string = '';
	// 	$item = $this->passed_vars['item'];
	// 	$markup_string .=  '<h4 class="title">';
	// 	$markup_string .= $item->get_value('release_title');
	// 	$markup_string .=  '</h4>'."\n";
	// 	return $markup_string;
	// }
	
	// function get_description_markup()
	// {
	// 	$item = $this->passed_vars['item'];
	// 	if($item->get_value('description'))
	// 		return '<div class="desc">'.$item->get_value('description').'</div>'."\n";
	// }

}
?>