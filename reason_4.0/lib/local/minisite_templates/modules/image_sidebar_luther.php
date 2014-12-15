<?php
	reason_include_once( 'minisite_templates/modules/image_sidebar.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ImageSidebarLutherModule';

	class ImageSidebarLutherModule extends ImageSidebarModule
	{
		function run()
		{
			$die = isset( $this->die_without_thumbnail ) ? $this->die_without_thumbnail : false;
			$popup = isset( $this->show_popup_link ) ? $this->show_popup_link : true;
			$desc = isset( $this->description ) ? $this->description : true;
			$text = isset( $this->additional_text ) ? $this->additional_text : "";
		
			echo '<div class="imageSidebarModule">'."\n";
		
			if ( !empty($this->textonly) )
				echo '<h3>Images</h3>'."\n";
		
			$even_odd = 'odd';
		
			foreach( $this->images AS $id => $image )
			{
				$keywords = $image->get_value('keywords');
				$show_text = $text;
				if( !empty( $this->show_size ) )
					$show_text .= '<br />('.$image->get_value( 'size' ).' kb)';
				echo '<div class="imageChunk '.$this->get_api_class_string().' '.$even_odd.'">';
					
				if($this->params['thumbnail_width'] != 0 or $this->params['thumbnail_height'] != 0)
				{
					$rsi = new reasonSizedImage();
					if(!empty($rsi))
					{
						$rsi->set_id($image->id());
						if($this->params['thumbnail_width'] != 0)
						{
							$rsi->set_width($this->params['thumbnail_width']);
						}
						if($this->params['thumbnail_height'] != 0)
						{
							$rsi->set_height($this->params['thumbnail_height']);
						}
						if($this->params['thumbnail_crop'] != '')
						{
							$rsi->set_crop_style($this->params['thumbnail_crop']);
						}
						$image = $rsi;
					}
				}
					
				if ($this->params['caption_flag'] == false || preg_match("/hide_caption/", $keywords))
					show_image( $image, $die, $popup, false, $show_text, $this->textonly, false, $this->get_image_url($image) );
				else
					show_image( $image, $die, $popup, $desc, $show_text, $this->textonly, false, $this->get_image_url($image) );
		
				echo "</div>\n";
					
				$even_odd = ($even_odd == 'even') ? 'odd' : 'even';
			}
		
			echo '</div>'."\n";
		}
	}
?>