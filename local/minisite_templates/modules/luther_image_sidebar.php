<?php
	reason_include_once( 'minisite_templates/modules/image_sidebar.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherImageSidebarModule';
	
	class LutherImageSidebarModule extends ImageSidebarModule
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
                        $site_id = $this->site_id;
			
			if ( !empty($this->parent->textonly) )
				echo '<h3>Images</h3>'."\n";
			
			foreach( $this->images AS $id => $image )
			{

                            if ($site_id == '276256'){
                                if (!preg_match("/imagetop|bannerad|video|map/", $image->get_value('keywords')))
				{
					$url = WEB_PHOTOSTOCK . $id . '.' . $image->get_value('image_type');
					$thumb = WEB_PHOTOSTOCK . $id . '_tn.' . $image->get_value('image_type');
					$d = max($image->get_value('width'), $image->get_value('height')) / 125.0;
					if (preg_match("/hide_caption/", $image->get_value('keywords')))
					{
						$caption = "";
					}
					elseif (preg_match("/[A-Za-z0-9]+/", $image->get_value('content')))
					{
						$caption = $image->get_value('content');
					}
					else
					{
						$caption = $image->get_value('description');
					}
					//echo "<div class=\"imageChunk\">";
					//echo '<div class="imageChunk" style="width:' . $image->get_value('width')/4 .'px;">';
					//echo '<div class="figure" style="width:' . intval($image->get_value('width')/$d) .'px;">';
                                        echo '<div class="figure" style="width:100%;">';
					// show href to full size image with class and onclick for highslide
                                        //showing site id: echo 'site id is this: '. $site_id . ' end';
					echo '<a href="'. $url . '" class="highslide" onclick="return hs.expand(this, imageOptions)">';
					echo '<img src="' . $thumb . '" border="0" alt="" title="Click to enlarge" />';
					echo '</a>';

					// show caption if flag is true
					if ($this->params['caption_flag'] && $caption != "") echo $image->get_value('description') ;
					echo "</div>\n";
				}
                            }
                            else {
				if (!preg_match("/imagetop|bannerad|video|map/", $image->get_value('keywords')))
				{
					$url = WEB_PHOTOSTOCK . $id . '.' . $image->get_value('image_type');
					$thumb = WEB_PHOTOSTOCK . $id . '_tn.' . $image->get_value('image_type');
					$d = max($image->get_value('width'), $image->get_value('height')) / 125.0;
					if (preg_match("/hide_caption/", $image->get_value('keywords')))
					{
						$caption = "";
					}
					elseif (preg_match("/[A-Za-z0-9]+/", $image->get_value('content')))
					{
						$caption = $image->get_value('content');
					}
					else
					{
						$caption = $image->get_value('description');
					}
					//echo "<div class=\"imageChunk\">";
					//echo '<div class="imageChunk" style="width:' . $image->get_value('width')/4 .'px;">';
					echo '<div class="figure" style="width:' . intval($image->get_value('width')/$d) .'px;">';
					// show href to full size image with class and onclick for highslide
                                        //showing site id: echo 'site id is this: '. $site_id . ' end';
					echo '<a href="'. $url . '" class="highslide" onclick="return hs.expand(this, imageOptions)">';
					echo '<img src="' . $thumb . '" border="0" alt="' . $caption . '" title="Click to enlarge" />';
					echo '</a>';
				
					// show caption if flag is true
					if ($this->params['caption_flag'] && $caption != "") echo $image->get_value('description') ;
					echo "</div>\n";
				}
                            }
			}
		}
	}
?>
