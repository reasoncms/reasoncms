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
			"lcphotobureau" => array("0538a1c1ff66f69bb09e9ba0e863fb90", "f1c35e7ef5c4ac85"),
			"lc.anthrolab" => array("6725551efcba6082283094d49a0c807f", "3dff123f2a053c3a"),
			"luthertkd" => array("2e80ed6a3ce811d45607da933862c0e5", "140daaf7539dd293"),
			"lutherbookshop" => array("72d5d704e16ae8cefcfab73e046d9129", "30fd8bc6d7fb815c"),
			"luthercollegemusic" => array("0aefd0bebcb26cce81f0bd459787a244", "2de87b03bcbd7249"),
			"lcnorsesports" => array("ff52469fa499b0af7e9e05dbb0c94c36", "844acf6234151a1c"),
			"lutherfinearts" => array("e0b84ec8f5c4cdee13dbe0c6eab3c516", "5aba74ac9fb733c1"),
			"lutherfac" => array("fc556345d663f358322fa1ded6d72542", "468b278ffdddbbb7"),
			"Luther_College_Alumni_Office" => array("d97a9bcb0bef39692eb8b0ac97c8887f", "b0d82d76b43bc6d6"),
			"luthercollegechemistry" => array("4fe2ce650080400958007a505b980522", "cef37676c4424ae1"),
			// environmental outreach
			"nealem01" => array("d76f4769f09cc708389538ffe0d82733", "27e40ea372ece10c"),
			"LutherMinistry" => array("524ba8e86f00754ce5216cf02345125e", "48a2c6cb8df8ca1f"),
			"luthersustainability" => array("32deae8d61a176ea73d561995d903426", "d5805fcf8d221869"),
			"lcenglish" => array("46cf018e33c5a48b6a58d4957c015434", "0a4cf78f67c7c6b3"),
			"luther_cgl" => array("5b0b63d4c8d0a45c2b944a44baf98f56", "8e2244e3e8f07d71"));

			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'flickr_slideshow_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_flickr_slideshow'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_flickr_slideshow'));
			$es->set_order('rel_sort_order'); 
			$posts = $es->run_one();

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
					if (HTTP_HOST_NAME != 'localhost')
					{
						// 365 day cache expiration
						$f->enableCache("fs", "/var/reason/phpFlickrCache", 31536000); 
					}
					$photos = $f->photosets_getPhotos($post->get_value('flickr_photoset_id'));
					//foreach ((array)$photos['photoset']['photo'] as $photo)
					//{
	    			//		echo $photo['id'] . ": " . $photo['title'] . " (" . $photo['isprimary'] . ")<br />";
					//	$pinfo = $f->photos_getInfo($photo['id']);
					//	echo "farm: " . $pinfo['farm'] . "<br />";
					//	print_r($pinfo);
					//}
	
					if ($number_slideshows == 1)
					{
						echo "<h3>" . $post->get_value('name') . "</h3>" . "\n";
						echo "<ul id=\"galleryimages\">\n";
					}
					elseif ($number_slideshows > 1)
					{
						echo "<div class=\"flickr-set-container\">\n";
					}
					$photo_count = 1;
					foreach ((array)$photos['photoset']['photo'] as $photo)
					{
						// see /javascripts/highslide/highslide-overrides.js for gallery declaration
						$getInfo = $f->photos_getInfo($photo['id']);
						$pinfo = $getInfo['photo'];
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
						//elseif ($number_slideshows > 1 && $photo['isprimary']) 
						elseif ($number_slideshows > 1 && $photo_count == 1)
						{
							echo "<div class=\"flickr-set\">\n";
						}
						else
						{
							echo "<div class=\"hidden-container\">\n";
						}
						echo "<a class=\"highslide\" href=\"http://farm" . $pinfo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "." . $pinfo['originalformat']  . "\" onclick=\"return hs.expand(this, galleryOptions[" . $slideshowGroup . "])\">\n";
						echo "<img src=\"http://farm" . $pinfo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s." . $pinfo['originalformat']  . "\" title=\"Click to open gallery\"  />\n";
						echo "</a>\n";
						echo '<div class="highslide-caption" >'."\n";
						echo $description ."\n";
						// link to original image
						echo "<a href=\"http://farm" . $pinfo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $pinfo['originalsecret'] . "_o." . $pinfo['originalformat'] . "\" title=\"High res\">&prop;</a>\n"; 
						echo "</div>   <!--- class=\"highslide-caption\" -->\n"; 
	
						if ($number_slideshows == 1)
						{
							echo "</li>\n";
						}
						//elseif ($number_slideshows > 1 && $photo['isprimary'])
						elseif ($number_slideshows > 1 && $photo_count == 1)
						{
							echo "</div>   <!-- class=\"flickr-set\"-->\n";
						}
						else
						{
							echo "</div>   <!-- class=\"hidden-container\"-->\n";
						}
						$photo_count++;
					}
					if ($number_slideshows == 1)
					{
						echo "</ul>   <!-- id=\"galleryimages\"-->\n";
					}
					elseif ($number_slideshows > 1)
					{
						echo "<h4>" . $post->get_value('name') . "</h4>" . "\n";
						echo "</div>   <!-- class=\"flickr-set-container\"-->\n";
					}
					$slideshowGroup++;
					if ($slideshowGroup % 3 == 0)
					{
						echo "<hr>\n";
					}
					// max gallery size is declared in /javascript/highslide/highslide-overrides.js
					if ($slideshowGroup > 49)
					{
						break;
					}
				}
			}
			
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
