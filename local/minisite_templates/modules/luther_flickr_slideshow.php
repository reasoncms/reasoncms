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
			"lc.anthrolab" => array("6725551efcba6082283094d49a0c807f", "3dff123f2a053c3a"),
			"luthertkd" => array("2e80ed6a3ce811d45607da933862c0e5", "140daaf7539dd293"),
			"lutherbookshop" => array("72d5d704e16ae8cefcfab73e046d9129", "30fd8bc6d7fb815c"),
			"lcnorsesports" => array("2bb970a9274d898fd71b221853b6329a", "f872cd98ea82f335"),
			"lutherfinearts" => array("e0b84ec8f5c4cdee13dbe0c6eab3c516", "5aba74ac9fb733c1"),
			"Luther_College_Alumni_Office" => array("d97a9bcb0bef39692eb8b0ac97c8887f", "b0d82d76b43bc6d6"),
			"luthercollegechemistry" => array("4fe2ce650080400958007a505b980522", "cef37676c4424ae1"),
                        "luthersustainability" => array("32deae8d61a176ea73d561995d903426", "d5805fcf8d221869"));

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
			if ($this->cur_page->get_value( 'custom_page' ) != 'luther2010_music'
					&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_alumni')
			{
				echo "<hr>\n";
			}
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
				// 30 day cache expiration
				$f->enableCache("fs", "/var/www/phpFlickrCache", 2580000); 
				$photos = $f->photosets_getPhotos($post->get_value('flickr_photoset_id'));
				//foreach ((array)$photos['photoset']['photo'] as $photo)
				//{
    				//	echo $photo['id'] . ": " . $photo['title'] . " (" . $photo['isprimary'] . ")<br />";
				//	$pinfo = $f->photos_getInfo($photo['id']);
				//	echo "farm: " . $pinfo['farm'] . "<br />";
				//	//print_r($pinfo);
				//}
				if ($number_slideshows == 1 && $this->cur_page->get_value( 'custom_page' ) != 'luther2010_music'
					&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_alumni')
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
					// free accounts don't fill in $pinfo['originalformat']
					if ($pinfo['originalformat'] == null)
					{
						$pinfo['originalformat'] = 'jpg';
					}
					if (preg_match("/[A-Za-z0-9]+/", $pinfo['description']))
					{
						$description = "<b>" .$photo['title'] . "</b><br/> " . preg_replace("|\"|", "&quot;", $pinfo['description']);
					}
					else
					{
						$description = $photo['title'];
					}
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
					echo "<img src=\"http://farm" . $pinfo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s." . $pinfo['originalformat']  . "\" title=\"Click to open gallery\" alt=\"" . $description . "\" />\n";
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
		
		function has_content()
		{
			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'flickr_slideshow_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_flickr_slideshow'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_flickr_slideshow'));
			$es->set_order('rel_sort_order'); 
			$posts = $es->run_one();
			if (count($posts) > 0)
			{
				return true;
			}
			return false;
		}
		
	}
?>
