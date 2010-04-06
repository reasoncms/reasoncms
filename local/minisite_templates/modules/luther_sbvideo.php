<?php
	reason_include_once( 'minisite_templates/modules/image_sidebar.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherSbvideoModule';
	
	class LutherSbvideoModule extends ImageSidebarModule
	// includes videos and maps in left sidebar
	{
		var $es;
		var $images;

		var $acceptable_params = array(
		'num_to_display' => '',
		'caption_flag' => true,
		'rand_flag' => false,
		'order_by' => '' );

		function init( $args = array() )
		{
			parent::init( $args );
			$head_items =& $this->parent->head_items;
		}
		
		function run()
		{
			$die = isset( $this->die_without_thumbmail ) ? $this->die_without_thumbnail : false;
			$popup = isset( $this->show_popup_link ) ? $this->show_popup_link : true;
			$desc = isset( $this->description ) ? $this->description : true;
			$text = isset( $this->additional_text ) ? $this->additional_text : "";
			
			if ( !empty($this->parent->textonly) )
				echo '<p>Sidebar Video</p>'."\n";
			
			echo '<div id="bannerleft">'."\n";
			foreach( $this->images AS $id => $image )
			{
				// video in sidebar
				if (preg_match("/video/", $image->get_value('keywords')))
				{
					$url = WEB_PHOTOSTOCK . $id . '.' . $image->get_value('image_type');
					echo $image->get_value("content");
					//break;
				}
				// map in sidebar
				if (preg_match("/map/", $image->get_value('keywords')))
				{
					if (preg_match("/hide_caption/", $image->get_value('keywords')))
					{
						$caption = "";
					}
					else
					{
						$caption = $image->get_value('description');
					}
					$url = WEB_PHOTOSTOCK . $id . '.' . $image->get_value('image_type');
					echo '<div id="imageside">'."\n";
					echo '<div class="imagesideframe">'."\n";
                                        echo '<a href="'. $url . '" class="highslide" onclick="return hs.expand(this, imageOptions)">';
                                        echo '<img src="' . $url . '" style="border:0" alt="' . $caption . '" title="Click to enlarge" />';
                                        echo '</a>';
					echo '</div class="imagesideframe">'."\n";
					echo '</div id="imageside">'."\n";

				}
			}
			echo "</div>\n";
		}
	}
?>
