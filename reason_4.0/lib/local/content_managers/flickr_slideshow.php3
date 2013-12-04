<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'LutherFlickrSlideshow';

	/**
	 * A content manager for text blurbs
	 */
	class LutherFlickrSlideshow extends ContentManager
	{
		function alter_data()
		{
		//$this->set_display_name('flickr_username', 'Flickr account username');	
		$this->set_comments('flickr_username', form_comment('Enter username used to access flickr account.'));
		$this->set_comments('flickr_photoset_id', form_comment('All images from flickr set will be used in this slideshow.'));
		$this->add_required('flickr_username');
		$this->add_required('flickr_photoset_id');
		$this->add_element( 'clear_cache', 'checkbox', array('description' => 'If changes have been made to the Flickr Photoset, then the cache must be cleared for the changes to take effect on the Luther site.'));


		}
		function process()
		{
			if ($this->get_value("clear_cache"))
			{
				//$f2 = fopen('/var/www/phpFlickrCache/out.txt', 'w');
				//fprintf($f2, "%s\n", $this->get_value('flickr_photoset_id'));

				$flickrset = exec("grep -l '" . $this->get_value('flickr_photoset_id') . "' /var/reason/reason_package/reason_4.0/www/local/phpFlickrCache/*.cache");
				//fprintf($f2, "%s\n", $flickrset);

				if ($f = fopen($flickrset, "r"))
				{
					$s = fgets($f, 24000);
					fclose($f);
					unlink($flickrset);
					//fprintf($f2, "%s\n", $s);
					preg_match_all("/\"id\";s:1[01]:\"(\d+)\";/", $s, $images);
					//ob_start();
					//print_r($images);
					//$output = ob_get_clean();
					//fprintf($f2, "%s", $output);

					foreach ($images[1] as $img)
					// $images[1] contains matched images from (\d+) 
					{
						$i = exec("grep -l '" . $img . "' /var/reason/reason_package/reason_4.0/www/local/phpFlickrCache/*.cache");
						//fprintf($f2, "%s\n", $i);
						unlink($i);
					}
				}
				//fclose($f2);
			}

			parent::process();
		}
	}
?>
