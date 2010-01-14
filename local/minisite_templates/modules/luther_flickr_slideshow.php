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
			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'flickr_slideshow_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_flickr_slideshow'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_flickr_slideshow'));
			$es->set_order('rel_sort_order'); 
			$posts = $es->run_one();
			foreach( $posts AS $post )
			{
    				echo $post->get_value( 'flickr_username' ).'<br />';
    				echo $post->get_value( 'flickr_photoset_id' ).'<br />';
    				echo $post->get_value( 'description' ).'<br />';
    				echo $post->get_value( 'keywords' ).'<br />';
				$f = new phpFlickr("5b298e650817ac77f14054abfc722b01", "f8a94f21e063f110");
				$photos = $f->photosets_getPhotos($post->get_value('flickr_photoset_id'));
				foreach ((array)$photos['photoset']['photo'] as $photo)
				{
    					echo $photo['id'] . ": " . $photo['title'] . " (" . $photo['isprimary'] . ")<br />";
					$pinfo = $f->photos_getInfo($photo['id']);
					echo "farm: " . $pinfo['farm'] . "<br />";
					//print_r($pinfo);
				}
				foreach ((array)$photos['photoset']['photo'] as $photo)
				{
					$pinfo = $f->photos_getInfo($photo['id']);
					echo "<img src=\"http://farm" . $pinfo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s." . $pinfo['originalformat']  . "\"/>"; 
				}
			}


		}
	}
?>
