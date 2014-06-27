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
			$head_items = $this->get_head_items();
			$head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'FancyBox/source/jquery.fancybox.js');
			$head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH.'FancyBox/source/jquery.fancybox.css');			
			$head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'FancyBox/source/helpers/jquery.fancybox-thumbs.js');
			$head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH.'FancyBox/source/helpers/jquery.fancybox-thumbs.css');
			$head_items->add_javascript('/reason/local/luther_2014/javascripts/luther-image-galleries.js');
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
			
			$i = 0;
			
            echo "<div id=\"galleryimages\">\n";
			foreach( $this->images AS $id => $image )
			{
				$show_text = $text;
				echo "<div class=\"image reason-image\">\n";
	
				echo '<a class="fancybox-thumb" href="' . WEB_PHOTOSTOCK . $id . '.' . $image->get_value('image_type');
				echo '" rel="gallery_image_slideshow"';
				echo ' title="' .htmlspecialchars( strip_tags($image->get_value('description')),ENT_QUOTES,'UTF-8' ). '">';
				echo '<img src="' . WEB_PHOTOSTOCK . $id . '_tn.' .$image->get_value('image_type') . '" title="Click to open gallery"/></a>'."\n";
				echo "</div>  <!-- class=\"flickr-image\" -->\n";
				$i++;
			}
            echo "</div>  <!-- id=\"galleryimages\" -->\n";			
			
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
