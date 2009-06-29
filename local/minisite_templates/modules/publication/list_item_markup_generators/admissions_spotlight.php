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
	var $variables_needed = array( 	'use_dates_in_list', 
		'date_format', 
		'item',
		'link_to_full_item',
		'permalink',
		'item_images',
		'teaser_image',
		'cur_page',
		);

	function MinimalListItemMarkupGenerator ()
	{
	}
	
	function run ()
	{	
                $this->markup_string .= '<div class="supplemental block block-1">'."\n";
                $this->markup_string .= '<h2>Luther Spotlight</h2>'."\n";
                $this->markup_string .= '<div id="spotlight">'."\n";
                $this->markup_string .= '<p>'."\n";

		//$this->markup_string .= $this->get_date_markup();
		$this->markup_string .= $this->get_image_markup();
		//$this->markup_string .= $this->get_pre_markup();
 		//$this->markup_string .= $this->get_title_markup();
 		$this->markup_string .= $this->get_description_markup();
                $this->markup_string .= '</div id="spotlight">'."\n";
                $this->markup_string .= '</p>'."\n";

                $this->markup_string .= '<p class="links">'."\n";
//			$markup_string .= '<a href ="'.$this->passed_vars['link_to_full_item'].'">read spotlight &gt;</a>'."\n";
		$full_link = $this->passed_vars['link_to_full_item'];
		$full_link = preg_replace("|http(s)?:\/\/\w+\.\w+\.\w+|", "", $full_link);
                $this->markup_string .= '<a href="'.$full_link.'" class="more">Read more</a>'."\n";
                //$this->markup_string .= '<a href="'.$this->passed_vars['link_to_full_item'].'" class="more">Read more</a>'."\n";
                $this->markup_string .= '<a href="/spotlightarchives/" class="all">See all spotlights</a>'."\n";
                $this->markup_string .= '</p>'."\n";
                $this->markup_string .= '</div>'."\n";

	}
	
/////
// show_list_item methods
/////
	function get_pre_markup()
	{
		return $this->get_teaser_image_markup();
	}

	function get_image_markup()
	{
		$markup_string = '';
		$image = $this->passed_vars['item_images'];
		if (!empty($image))
		{
		$id = reset($image)->get_value('id');
		$imgtype = reset($image)->get_value('image_type');
		$full_image_name = WEB_PHOTOSTOCK.$id.'_tn.'.$imgtype;	
			//$markup_string .= '<div class="image">';
			ob_start();	
			//reason_get_image_url(reset($image), 'standard');
			//print_r(array_values( $image));
                        echo '<img src="'.$full_image_name.'"/>';
			//show_image( reset($image), true,true,false );
			$markup_string .= ob_get_contents();
			ob_end_clean();
			//$markup_string .= '</div>';
		} 
		return $markup_string;
	}
	
	function get_teaser_image_markup() // {{{
	{
		$markup_string = '';
		$image = $this->passed_vars['teaser_image'];
		if (!empty($image))
		{
			$markup_string .= '<div class="teaserImage">';
			ob_start();	
			show_image( reset($image), true,false,false );
			$markup_string .= ob_get_contents();
			ob_end_clean();
			$markup_string .= '</div>';
		} 
		return $markup_string;
	}
	
	function get_description_markup()
	{
		$markup_string = '';
		$item = $this->passed_vars['item'];
		if($item->get_value('description'))
			//return '<div class="desc">'.$item->get_value('description').'</div>'."\n";
			$markup_string .= '<div class="desc">'."\n";
			$markup_string .= $item->get_value('description')."\n";
//			$markup_string .= '<a href ="'.$this->passed_vars['link_to_full_item'].'">read spotlight &gt;</a>'."\n";
			$markup_string .= '</div class="desc">'."\n";
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
