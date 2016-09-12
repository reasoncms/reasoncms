<?php
	reason_include_once("classes/media/factory.php");
	include_once(CARL_UTIL_INC.'basic/html_funcs.php');

	class SimpleEmbedHandler extends DefaultEmbedHandler {
		function handleImageSubstitution($position, $img, $et) {
			static $counter = 0;
			$counter++;
			$classes = array(
				'embed',
				'embeddedImage',
				'imgNum'.$counter,
				($counter%2 ? 'oddImage' : 'evenImage'),
			);
			if($floatClass = $this->get_float_class($et))
				$classes[] = $floatClass;
			if($et->hasParam("width"))
			{
				$param_width = (integer) $et->getParam("width");
				if($param_width)
				{
					$rsi = new reasonSizedImage();
					$rsi->set_id($img->id());
					$rsi->set_width($param_width);
					$img_url = $rsi->get_url();
					$width = $rsi->get_image_width();
					$height = $rsi->get_image_height();
				}
			}
			if(empty($img_url))
			{
				$img_url = reason_get_image_url($img, 'standard');
				$width = $img->get_value('width');
				$height = $img->get_value('height');
			}
			$caption = $et->hasParam("caption") ? $et->getParam("caption") : $img->get_value('description');
			
			$ret = '';
			$ret .= '<span class="'.implode(' ',$classes).'" style="width:'.$width.'px;">';
			$ret .= '<img src="'.$img_url.'" width="'.$width.'" height="'.$height.'" alt="'.reason_htmlspecialchars(strip_tags($caption)).'" />';
			
			$ret .= '<span class="embedCaption">'.strip_non_phrasing_tags($caption).'</span>';
			if($img->get_value('author'))
				$ret .= '<span class="embedAuthor">Photo: '.strip_non_phrasing_tags($img->get_value('author')).'</span>';
			$ret .= '</span>';
			return $ret;
		}

		function handleMediaSubstitution($position, $media, $et) {
			static $counter = 0;
			$counter++;
			$classes = array(
				'embed',
				'embeddedMedia',
				'mediaNum'.$counter,
				($counter%2 ? 'oddMedia' : 'evenMedia'),
			);
			if($floatClass = $this->get_float_class($et))
				$classes[] = $floatClass;
			
			$caption = $et->hasParam("caption") ? $et->getParam("caption") : $media->get_value('name');
			
			$width = 500;
			if($et->hasParam("width"))
			{
				$param_width = (integer) $et->getParam("width");
				if($param_width)
					$width = $param_width;
			}
			$rv = '';
			
			$displayer_chrome = MediaWorkFactory::displayer_chrome($media, "default");
			if ($displayer_chrome) {
				$rv .= '<span class="'.implode(' ',$classes).'" style="width:'.$width.'px;">';
				$displayer_chrome->set_media_work($media);
				$displayer_chrome->set_media_width($width);
				$rv .= $displayer_chrome->get_html_markup();
				$rv .= '<span class="embedCaption">'.strip_non_phrasing_tags($caption).'</span>';
				if($media->get_value('author'))
					$rv .= '<span class="embedAuthor">By: '.strip_non_phrasing_tags($media->get_value('author')).'</span>';
				$rv .= '</span>';
			}
			else
				$rv = 'nochrome';

			// $rv = "(" . $position . ")/(" . $media->id() . ") " . $rv;
			return $rv;
		}
		
		function get_float_class($et)
		{
			if($et->hasParam("float"))
			{
				switch($et->getParam("float"))
				{
					case 'left':
						return 'floatLeft';
					case 'right':
						return 'floatRight';
				}
			}
			return '';
		}
	}
?>
