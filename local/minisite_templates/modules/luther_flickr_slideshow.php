<?php
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherFlickrSlideshow';
	
	class LutherFlickrSlideshow extends DefaultMinisiteModule
	{
		function init( $args = array() )
		{
		}
		
		function run()
		{
       			require("phpFlickr/phpFlickr.php");

			// flickr username, api key, and secret must be included in array below:
			$flickr_account = array("luthercollegemedia" => array("5b298e650817ac77f14054abfc722b01", "f8a94f21e063f110"),
			"lc.anthrolab" => array("6725551efcba6082283094d49a0c807f", "3dff123f2a053c3a"));

			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'flickr_slideshow_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_flickr_slideshow'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_flickr_slideshow'));
			$es->set_order('rel_sort_order'); 
			$posts = $es->run_one();
			echo "<div id=\"gallery\">\n";
			echo "<div class=\"gallery-info\">\n";
			echo "<div id=\"gallerycontainer\">\n";
			echo "<hr>\n";
			$slideshowGroup = 0;
			$number_slideshows = count($posts);
			foreach( $posts AS $post )
			{
    				//echo $post->get_value( 'flickr_username' ).'<br />';
    				//echo $post->get_value( 'flickr_photoset_id' ).'<br />';
    				//echo $post->get_value( 'description' ).'<br />';
    				//echo $post->get_value( 'keywords' ).'<br />';
				if (array_key_exists($post->get_value('flickr_username'), $flickr_account))
				{
				$f = new phpFlickr($flickr_account[$post->get_value('flickr_username')][0], $flickr_account[$post->get_value('flickr_username')][1]);
				$f->enableCache("fs", "/var/www/phpFlickrCache"); 
				$photos = $f->photosets_getPhotos($post->get_value('flickr_photoset_id'));
				//foreach ((array)$photos['photoset']['photo'] as $photo)
				//{
    				//	echo $photo['id'] . ": " . $photo['title'] . " (" . $photo['isprimary'] . ")<br />";
				//	$pinfo = $f->photos_getInfo($photo['id']);
				//	echo "farm: " . $pinfo['farm'] . "<br />";
				//	//print_r($pinfo);
				//}
				if ($number_slideshows == 1)
				{
					echo "<h3>" . $post->get_value('name') . "</h3>" . "\n";
				}
				
				if ($number_slideshows == 1)
				{
					echo "<ul id=\"galleryimages\">\n";
				}
				elseif ($number_slideshows > 1)
				{
					echo "<div class=\"flickr-set-container\">\n";
				}
				foreach ((array)$photos['photoset']['photo'] as $photo)
				{
					// see /javascripts/highslide/highslide-overrides.js for gallery declaration
					$pinfo = $f->photos_getInfo($photo['id']);
					if ($number_slideshows == 1)
					{
						echo "<li>\n";
					}
					elseif ($number_slideshows > 1 && $photo['isprimary']) 
					{
						echo "<div class=\"flickr-set\">\n";
					}
					else
					{
						echo "<div class=\"hidden-container\">\n";
					}
					echo "<a class=\"highslide\" href=\"http://farm" . $pinfo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "." . $pinfo['originalformat']  . "\" onclick=\"return hs.expand(this, galleryOptions[" . $slideshowGroup . "])\">\n";
					echo "<img src=\"http://farm" . $pinfo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s." . $pinfo['originalformat']  . "\" title=\"Click to open gallery\" alt=\"" . $photo['title'] . "\" />\n";
					echo "</a>\n";

					if ($number_slideshows == 1)
					{
						echo "</li>\n";
					}
					elseif ($number_slideshows > 1 && $photo['isprimary']) 
					{
						echo "</div class=\"flickr-set\">\n";
					}
					else
					{
						echo "</div class=\"hidden-container\">\n";
					}
				}
				if ($number_slideshows == 1)
				{
					echo "</ul id=\"galleryimages\">\n";
				}
				elseif ($number_slideshows > 1)
				{
					echo "<h4>" . $post->get_value('name') . "</h4>" . "\n";
					echo "</div class=\"flickr-set-container\">\n";
				}
				$slideshowGroup++;
				// max gallery size is declared in /javascript/highslide/highslide-overrides.js
				if ($slideshowGroup > 49)
				{
					break;
				}
				}
			}
			echo "</div id=\"gallerycontainer\">\n";
			echo "</div class=\"gallery-info\">\n";
			echo "</div id=\"gallery\">\n";

		}
	}
?>
