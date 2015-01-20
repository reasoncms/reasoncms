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
}