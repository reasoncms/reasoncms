<?php

reason_include_once('minisite_templates/modules/publication/item_markup_generators/responsive.php');

// Increase thumbanil size of related images on the full article page.

class CloakItemMarkupGenerator extends ResponsiveItemMarkupGenerator
{

	function get_images_section()
	{
		$str = '';
		$str .= '<ul>';
		foreach($this->passed_vars['item_images'] as $image)
		{
			$str .= '<li class="imageChunk">';
			$rsi = new reasonSizedImage();
			$rsi->set_id($image->id());
			$rsi->set_width(400);
			// Uncomment if you want to force a height or crop
			//$rsi->set_height(300);
			//$rsi->set_crop_style('fill');
			ob_start();
			show_image( $rsi, false, true, true, '');
			$str .= ob_get_contents();
			ob_end_clean();
			$str .= '</li>';
		}
		$str .= '</ul>';
		return $str;
	}

	function get_social_sharing_section()
	{
		$ret = '<p><strong>Share post:</strong>';
		foreach($this->passed_vars['item_social_sharing'] as $social_sharing)
		{
			// Change Social Media name into a css-class-friendly string
			$name = $social_sharing['text'];
			//Lower case everything
			$name = strtolower($name);
			//Make alphanumeric (removes all other characters)
			$name = preg_replace("/[^a-z0-9_\s-]/", "", $name);
			//Clean up multiple dashes or whitespaces
			$name = preg_replace("/[\s-]+/", " ", $name);
			//Convert whitespaces and underscore to dash
			$name = preg_replace("/[\s_]/", "-", $name);
			
			$ret .= ' <a href="'.$social_sharing['href'].'" class="' . $name . '">';
			//$ret .= '<img src="'. $social_sharing['icon'] . '" alt="'. $social_sharing['text'] . '" />';
			$ret .= '<span>' . $social_sharing['text'] . '</span>'; 
			$ret .= '</a>';
		}
		$ret .= '</p>';
		return $ret;
	}
}