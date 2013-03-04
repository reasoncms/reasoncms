<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * Include base class & other dependencies
  */
	reason_include_once( 'minisite_templates/modules/av.php' );
	
	/**
	 * Register the class so the template can instantiate it
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherAvModule';
	
	/**
	 * A minisite module to display media works & media files
	 */
	class LutherAvModule extends AvModule
	{
		var $acceptable_params = array(
			'limit_to_current_site'=>true,
			'limit_to_current_page'=>true,
			'sort_direction'=>'DESC', // Normally this page shows items in reverse chronological order, but you can change this to ASC for formward chronological order
			'sort_field'=>'dated.datetime',
			'relationship_sort'=>true, // Says whether the module should pay attention to the 'sortable' nature of the minisite_page_to_av allowable relationship
			'full_size'=>false,   // audio_video_full_size page type sets this to true
		);
		
		function run()
		{				
			Generic3Module::run();	
		}

		function do_list()
		{
			echo '<ul>'."\n";
			foreach( $this->items AS $item )
			{
				$this->show_list_item( $item );
			}
			echo '</ul>'."\n";
		}
		
		function show_list_item( $item ) 
		{
			echo '<li>';
			if ($this->params['full_size'])
			{
				echo '<div class="video fullsize">'."\n";
				$this->show_list_item_pre( $item );
				echo '</div>'."\n";
			}
			else if ($this->cur_page->get_value('custom_page') != 'audio_video'
				&& $this->cur_page->get_value('custom_page') != 'audio_video_reverse_chronological')
			{
				echo '<div class="figure" style="width: 125px">'."\n";
				$this->show_list_item_pre( $item );
				$this->show_list_item_name( $item );
				echo '</div>'."\n";      	
			}
			else
			{
				$this->show_list_item_pre( $item );
				echo '<h4>';
				$this->show_list_item_name( $item );
				echo '</h4>';
				$this->show_list_item_desc( $item );
			}
			echo '</li>'."\n";
			if ($this->cur_page->get_value('custom_page') == 'audio_video'
				|| $this->cur_page->get_value('custom_page') == 'audio_video_reverse_chronological'
				|| $this->cur_page->get_value('custom_page') == 'audio_video_full_size')
			{
				echo '<hr>'."\n";
			}
			$this->item_counter++;
		}

		//Called on by show_list_item()
		function show_list_item_pre( $item )
		{
			$pi = $this->get_primary_image( $item );
			//echo $pi;
			//echo preg_replace("|(\<img src=\".*?\").*?\/\>|", "\\1 />", $pi);
			$avfilelist = $this->get_av_files($item);
			//echo "media works id = ".$item->id()."   ";
			// Block any media files other than video
			foreach( $avfilelist as $av_file )
			{
				//echo $av_file->get_value( 'name' )."\n";
				//echo $av_file->get_value( 'media_format' )."\n";
				//echo $av_file->get_value( 'av_type' )."\n";
				if ($av_file->get_value( 'av_type' ) != 'Video')
				{
					return;
				}
				//print_r($av_file);
			}
			reset($avfilelist);
			$vn = $_SERVER['REQUEST_URI'] . "video_" . strtolower(preg_replace('|[\'\"]|', '', preg_replace('| |', '_', current($avfilelist)->get_value('name')))); 
			//print($vn);
			//print(current($avfilelist)->get_value('name'));
			//print(current($avfilelist)->get_value('url'));
			//print(current($avfilelist)->get_value('height'));
			//print(current($avfilelist)->get_value('width'));
			//$vurl = preg_replace("|(.*?)&.*?$|", "\\1", current($avfilelist)->get_value('url'));
			if (current($avfilelist)->get_value('media_format') == 'Flash')
			{
			//	if (preg_match("/(^http:\/\/www\.youtube\.com\/)(\w+)(\/(.*?)$)/", $vurl, $m ))
				$url = current($avfilelist)->get_value('url');
				if (preg_match("/(^http:\/\/youtu\.be\/)((.*?)$)/", $url, $m))
				{
					$url = "http://www.youtube.com/watch?v=" . $m[2];
				}
				if (preg_match("/(^http:\/\/www\.youtube\.com\/)(watch\?v\=)((.*?)$)/", $url, $m ))
				{
					if (!$this->params['full_size'])
					{
						//echo "<a href=\"" . $m[1] . "v/" . $m[3] . "&amp;hl=en&amp;rel=0&amp;fs=0&amp;autoplay=1\" onclick=\"javascript:pageTracker._trackPageview('" . $vn ."');return hs.htmlExpand(this, { objectType: 'swf', width: " . current($avfilelist)->get_value('width') . ", objectWidth: " . current($avfilelist)->get_value('width') . ", objectHeight: " . current($avfilelist)->get_value('height') . ", preserveContent: false, outlineType: 'rounded-white', wrapperClassName: 'draggable-header no-footer', maincontentText: 'You need to upgrade your Flash player', swfOptions: { version: '7' } } )\" class=\"highslide\"><img src=\"http://img.youtube.com/vi/" . $m[3] . "/default.jpg\" /><img class=\"av-play\" title=\"Play Video: " . preg_replace('|\"|', '&quot;', $item->get_value( 'name' )) . "\" src=\"/images/play_44.png\" /></a>";
						//echo "<a href=\"" . $m[1] . "v/" . $m[3] . "&amp;hl=en&amp;rel=0&amp;fs=0&amp;autoplay=1\" onclick=\"return hs.htmlExpand(this, { slideshowGroup: -1, objectType: 'swf', width: " . current($avfilelist)->get_value('width') . ", objectWidth: " . current($avfilelist)->get_value('width') . ", objectHeight: " . current($avfilelist)->get_value('height') . ", preserveContent: false, outlineType: 'rounded-white', wrapperClassName: 'draggable-header no-footer', maincontentText: 'You need to upgrade your Flash player', swfOptions: { version: '7' } } )\" class=\"highslide\" name=\"" . $vn . "\"><img src=\"http://img.youtube.com/vi/" . $m[3] . "/default.jpg\" /><img class=\"av-play\" title=\"Play Video: " . preg_replace('|\"|', '&quot;', $item->get_value( 'name' )) . "\" src=\"/images/play_44.png\" /></a>";
						if (preg_match('/android 2\.1/i',$_SERVER['HTTP_USER_AGENT']))
						{
							echo "<a href=\"" . $m[1] . "v/" . $m[3] . "&amp;hl=en&amp;rel=0&amp;fs=0&amp;autoplay=1&amp;showinfo=0\" onclick=\"return hs.htmlExpand(this, { slideshowGroup: -1, objectType: 'swf', width: " . current($avfilelist)->get_value('width') . ", objectWidth: " . current($avfilelist)->get_value('width') . ", objectHeight: " . current($avfilelist)->get_value('height') . ", preserveContent: false, outlineType: 'rounded-white', wrapperClassName: 'draggable-header no-footer', maincontentText: 'You need to upgrade your Flash player', swfOptions: { version: '7' } } )\" class=\"highslide\" name=\"" . $vn . "\"><img src=\"http://img.youtube.com/vi/" . $m[3] . "/default.jpg\" /><img class=\"av-play\" title=\"Play Video: " . preg_replace('|\"|', '&quot;', $item->get_value( 'name' )) . "\" src=\"/images/play_44.png\" /></a>";
						}
						else 
						{
							echo "<a href=\"" . $m[1] . "embed/" . $m[3] . "?autoplay=1&amp;rel=0&amp;fs=1&amp;showinfo=0\" onclick=\"return hs.htmlExpand(this, { slideshowGroup: -1, objectType: 'iframe', width: '640', height: '418', objectLoadTime: 'after', outlineType: 'rounded-white', wrapperClassName: 'draggable-header no-footer'} )\" class=\"highslide\" name=\"" . $vn . "\"><img src=\"http://img.youtube.com/vi/" . $m[3] . "/default.jpg\" /><img class=\"av-play\" title=\"Play Video: " . preg_replace('|\"|', '&quot;', $item->get_value( 'name' )) . "\" src=\"/images/play_44.png\" /></a>";
						}
					}
					else
					{
						if (preg_match('/android 2\.1/i',$_SERVER['HTTP_USER_AGENT']))
						{
							echo "<object width=\"444\" height=\"356\" classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0\" onclick=\"javascript:pageTracker._trackPageview('" . $vn ."');\"><param name=\"movie\" value=\"" . $m[1] . "v/" . $m[3] . "?fs=1&amp;hl=en_US&amp;rel=0\"></param><param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param><embed src=\"" .$m[1] . "v/" . $m[3] . "?fs=1&amp;hl=en_US&amp;rel=0\" type=\"application/x-shockwave-flash\" allowscriptaccess=\"always\" allowfullscreen=\"true\" width=\"444\" height=\"356\"></embed></object>"."\n";
						}
						else if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home' || $this->cur_page->get_value( 'custom_page' ) == 'luther2010_home_feature')
						{
							echo "<iframe width=\"420\" height=\"265\" src=\"" .$m[1] . "embed/" . $m[3] ."?autoplay=0&amp;rel=0&amp;fs=1&amp;showinfo=0\" frameborder=\"0\" allowfullscreen></iframe>"."\n";
						}
						else 
						{
							echo "<iframe width=\"444\" height=\"280\" src=\"" .$m[1] . "embed/" . $m[3] ."?autoplay=0&amp;rel=0&amp;fs=1&amp;showinfo=0\" frameborder=\"0\" allowfullscreen></iframe>"."\n";
						}
					}
				//echo "<a href=\"" . $vurl . "&amp;hl=en&amp;rel=0&amp;fs=0&amp;autoplay=1\" onclick=\"return hs.htmlExpand(this, { objectType: 'swf', width: " . current($avfilelist)->get_value('width') . ", objectWidth: " . current($avfilelist)->get_value('width') . ", objectHeight: " . current($avfilelist)->get_value('height') . ", preserveContent: false, outlineType: 'rounded-white', wrapperClassName: 'draggable-header no-footer', maincontentText: 'You need to upgrade your Flash player', swfOptions: { version: '7' } } )\" class=\"highslide\"><img src=\"http://img.youtube.com/vi" . $m[3] . "/default.jpg\" /></a>";
				//print("m[0] = $m[0]<br />\n");
				//print("m[1] = $m[1]<br />\n");
				//print("m[2] = $m[2]<br />\n");
				//print("m[3] = $m[3]<br />\n");
				}
				else
				{
					//echo "<a href=\"" . current($avfilelist)->get_value('url') . "\" onclick=\"javascript:pageTracker._trackPageview('" . $vn ."');return hs.htmlExpand(this, { objectType: 'swf', width: " . current($avfilelist)->get_value('width') . ", objectWidth: " . current($avfilelist)->get_value('width') . ", objectHeight: " . current($avfilelist)->get_value('height') . ", preserveContent: false, outlineType: 'rounded-white', wrapperClassName: 'draggable-header no-footer', maincontentText: 'You need to upgrade your Flash player', swfOptions: { version: '7' } } )\" class=\"highslide\">" . preg_replace("|(\<img src=\".*?\").*?\/\>|", "\\1 />", $pi) . "<img class=\"av-play\" title=\"Play Video: " . preg_replace('|\"|', '&quot;', $item->get_value( 'name' )) . "\" src=\"/images/play_44.png\" /></a>";
					echo "<a href=\"" . current($avfilelist)->get_value('url') . "\" onclick=\"return hs.htmlExpand(this, { slideshowGroup: -1, objectType: 'swf', width: " . current($avfilelist)->get_value('width') . ", objectWidth: " . current($avfilelist)->get_value('width') . ", objectHeight: " . current($avfilelist)->get_value('height') . ", preserveContent: false, outlineType: 'rounded-white', wrapperClassName: 'draggable-header no-footer', maincontentText: 'You need to upgrade your Flash player', swfOptions: { version: '7' } } )\" class=\"highslide\" name=\"" . $vn . "\">" . preg_replace("|(\<img src=\".*?\").*?\/\>|", "\\1 />", $pi) . "<img class=\"av-play\" title=\"Play Video: " . preg_replace('|\"|', '&quot;', $item->get_value( 'name' )) . "\" src=\"/images/play_44.png\" /></a>";
				}
				//print(current($avfilelist)->get_value('media_format'));
			}
			else
			{
				echo "<a href=\"" . current($avfilelist)->get_value('url') . "\" onclick=\"javascript:pageTracker._trackPageview('" . $vn ."')\">" . preg_replace("|(\<img src=\".*?\").*?\/\>|", "\\1 />", $pi) . "<img class=\"av-play\" title=\"Play Video: " . preg_replace('|\"|', '&quot;', $item->get_value( 'name' )) . "\" src=\"/images/play_44.png\" /></a>";
			}
			//print_r($avfilelist);
		}
		
		function get_primary_image( $item )
		{
			if(empty($this->parent->textonly))
			{
				$item->set_env('site_id',$this->parent->site_id);
				$images = $item->get_left_relationship( relationship_id_of('av_to_primary_image') );
				if(!empty($images))
				{
					$image = current($images);
					$die_without_thumbnail = true;
					$show_popup_link = false;
					$show_description = false;
					$additional_text = '';
					$link = '';
					return get_show_image_html( $image, $die_without_thumbnail, $show_popup_link, $show_description, $additional_text, $this->parent->textonly, false, $link );
				}
			}
		}

		function has_content()
		{
			if(empty($this->items))
			{
				return false;
			}
			else
			{
				return true;
			}
		}

		function list_items()
		{
			Generic3Module::list_items();
			$media_file_type = new entity(id_of('av_file'));
			$feed_link = $this->parent->site_info->get_value('base_url').MINISITE_FEED_DIRECTORY_NAME.'/'.$media_file_type->get_value('feed_url_string');
		}
	}
?>
