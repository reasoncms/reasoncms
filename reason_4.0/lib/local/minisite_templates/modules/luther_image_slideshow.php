<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class and register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/image_sidebar.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherImageSlideshowModule';
	
	/**
	 * A minisite module that displays a js-based slideshow of images attached to the page
	 *
	 * @todo  improve significantly and/or merge with gallery2
	 */
	class LutherImageSlideshowModule extends ImageSidebarModule
	{
		function init( $args = array() )
		{
			parent::init( $args );
                        //if($hi =& $this->get_head_items())
                        //{
			//}
		}
		function run() // {{{
		{
			if(!empty($this->textonly))
			{
				$this->run_text_only();
			}
			else
			{
				$this->run_full_graphics();
			}
		}
		function run_text_only()
		{
			echo '<ul>';
			foreach( $this->images AS $id => $image )
			{
				echo '<li>';
				echo '<a href="'.WEB_PHOTOSTOCK.$id.'.'.$image->get_value('image_type').'">Image: '.$image->get_value('description').'</a>';
				echo '</li>'."\n";
			}
			echo '</ul>'."\n";
		}
		function run_full_graphics()
		{
			$die = isset( $this->die_without_thumbmail ) ? $this->die_without_thumbnail : false;
			$popup = isset( $this->show_popup_link ) ? $this->show_popup_link : true;
			$desc = isset( $this->description ) ? $this->description : true;
			$text = isset( $this->additional_text ) ? $this->additional_text : "";
			
			$max_dimensions = $this->get_max_dimensions();
			
			//echo '<div class="imageSlideshow">'."\n";
			//echo '<div class="timedSlideshow" id="mySlideshow" style="height:'.$max_dimensions['height'].'px;width:'.$max_dimensions['width'].'px;"></div>'."\n";
			//echo '<script type="text/javascript">'."\n";
			//echo 'countArticle = 0;'."\n";
			//echo 'var mySlideData = new Array();'."\n";
			//echo '<div class="hidden-container">'."\n";
			$i = 0;
			echo "<div id=\"gallery\">\n";
                        echo "<div class=\"gallery-info\">\n";

			echo "<div id=\"gallerycontainer\">\n";
                        echo "<ul id=\"galleryimages\">\n";
			foreach( $this->images AS $id => $image )
			{
				$show_text = $text;
				echo "<li><div class=\"file_iframe_image\">\n";

				//if ($i == 0)
				//{
			//		echo '<a id="thumb1" class="highslide" href="'; 
			//	}
			//	else
			//	{
					echo '<a class="highslide" href="'; 
			//	}


				echo WEB_PHOTOSTOCK . $id . '.' . $image->get_value('image_type');
				//echo '" onclick="return hs.expand(this, inPageOptions)"><img src="';
				echo '" onclick="return hs.expand(this, {dimmingOpacity: 0.8, slideshowGroup: 1})"><img src="';
				echo WEB_PHOTOSTOCK . $id . '_tn.' .$image->get_value('image_type');
				echo '" alt="';
				echo htmlspecialchars( strip_tags($image->get_value('description')),ENT_QUOTES,'UTF-8' );
				echo '"/></a>'."\n";
				echo "</div class=\"file_iframe_image\"></li>\n";
				$i++;
			}
                        echo "</ul id=\"galleryimages\">\n";
			echo "</div id=\"gallerycontainer\">\n";
                        echo "</div class=\"gallery-info\">\n";
			echo "</div id=\"gallery\">\n";
			//echo '</div>'."\n";
			//echo '<div id="gallery-area" ></div>'."\n";
		}
		
		function get_max_dimensions()
		{
			$width = 0;
			$height = 0;
			foreach( $this->images AS $image )
			{
				if($image->get_value('height') > $height)
					$height = $image->get_value('height');
				if($image->get_value('width') > $width)
					$width = $image->get_value('width');
			}
			if($width == 0)
				$width = 500;
			if($height == 0)
				$height = 500;
			return array('height'=>$height,'width'=>$width);
		}
	}
?>
