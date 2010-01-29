<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
       	require_once("phpFlickr/phpFlickr.php");

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherFlickrSlideshow';
	
	class LutherFlickrSlideshow extends DefaultMinisiteModule
	{
		function init( $args = array() )
		{
		}
		
		function run()
		{
		// flickr username, api key, and secret must be included in array below:
		$flickr_account = array("luthercollegemedia" => array("5b298e650817ac77f14054abfc722b01", "f8a94f21e063f110"));

			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'flickr_slideshow_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_flickr_slideshow'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_flickr_slideshow'));
			$es->set_order('rel_sort_order'); 
			$posts = $es->run_one();
			echo "<div id=\"gallery\">\n";
			echo "<div class=\"gallery-info\">\n";
			$slideshowGroup = 0;
			foreach( $posts AS $post )
			{
    				//echo $post->get_value( 'flickr_username' ).'<br />';
    				//echo $post->get_value( 'flickr_photoset_id' ).'<br />';
    				//echo $post->get_value( 'description' ).'<br />';
    				//echo $post->get_value( 'keywords' ).'<br />';
				if ($flickr_account[$post->get_value( 'flickr_username' )] != null)
				{
				$f = new phpFlickr("5b298e650817ac77f14054abfc722b01", "f8a94f21e063f110");
				$f->enableCache("fs", "/var/www/phpFlickrCache"); 
				$photos = $f->photosets_getPhotos($post->get_value('flickr_photoset_id'));
				//foreach ((array)$photos['photoset']['photo'] as $photo)
				//{
    				//	echo $photo['id'] . ": " . $photo['title'] . " (" . $photo['isprimary'] . ")<br />";
				//	$pinfo = $f->photos_getInfo($photo['id']);
				//	echo "farm: " . $pinfo['farm'] . "<br />";
				//	//print_r($pinfo);
				//}
				echo "<h3>" . $post->get_value('name') . "</h3>" . "\n";
				echo "<div id=\"gallerycontainer\">\n";
				echo "<ul id=\"galleryimages\">\n";
				foreach ((array)$photos['photoset']['photo'] as $photo)
				{
					//echo "<li><div class=\"file_iframe_image\">\n";
					echo "<li>\n";
					$pinfo = $f->photos_getInfo($photo['id']);
//					echo "<img src=\"http://farm" . $pinfo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s." . $pinfo['originalformat']  . "\"/>"; 
					// see /javascripts/highslide/highslide-overrides.js for gallery declaration
					echo "<a class=\"highslide\" href=\"http://farm" . $pinfo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "." . $pinfo['originalformat']  . "\" onclick=\"return hs.expand(this, galleryOptions[" . $slideshowGroup . "])\"> <img src=\"http://farm" . $pinfo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s." . $pinfo['originalformat']  . "\" title=\"Click to open gallery\" alt=\"" . $photo['title'] . "\" /></a>"; 

					//echo "</div class=\"file_iframe_image\"></li>\n";
					echo "</li>\n";
				}
				echo "</ul id=\"galleryimages\">\n";
				echo "</div id=\"gallerycontainer\">\n";
				$slideshowGroup++;
				// max gallery size is declared in /javascript/highslide/highslide-overrides.js
				if ($slideshowGroup > 49)
				{
					break;
				}
				}
			}
			echo "</div class=\"gallery-info\">\n";
			echo "</div id=\"gallery\">\n";

		}
	}
?>
