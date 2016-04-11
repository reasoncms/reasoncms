<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * Include base class & other dependencies
  */
	reason_include_once( 'minisite_templates/modules/generic3.php' );
	reason_include_once( 'classes/av_display.php' );
	reason_include_once( 'function_libraries/url_utils.php' );
	reason_include_once('classes/media_work_helper.php');
	reason_include_once('classes/media/factory.php');
	
	/**
	 * Register the class so the template can instantiate it
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AvModule';
	
	/**
	 * A minisite module to display media works & media files
	 */
	class AvModule extends Generic3Module
	{
		var $type_unique_name = 'av';
		var $style_string = 'av';
		var $jump_to_item_if_only_one_result = false;
		var $use_dates_in_list = true;
		var $num_per_page = 15;
		var $use_pagination = true;
		var $item_counter = 1;
		var $acceptable_params = array(
			'limit_to_current_site'=>true,
			'limit_to_current_page'=>true, // if set to true we ignore limit_to_current_site
			'sort_direction'=>'DESC', // Normally this page shows items in reverse chronological order, but you can change this to ASC for formward chronological order
			'sort_field'=>'dated.datetime',
			'relationship_sort'=>true, // Says whether the module should pay attention to the 'sortable' nature of the minisite_page_to_av allowable relationship
			'thumbnail_width'=>0,
			'thumbnail_height'=>0,
			'thumbnail_crop'=>'',
			'default_video_height' => 360,
			'num_per_page' => 15,
			'show_primary_image_with_audio' => false,
			'offer_original_download_link' => false, // if true, present the user with a download link for the original file, along with the compressed versions
			'show_media_first' => false,
			'fallback_image' => '', // A unique name for a fallback thumbnail image
			'show_list_with_details' => true,
			//'show_next_prev' => false,
		);
		var $make_current_page_link_in_nav_when_on_item = true;
		var $no_items_text = 'There is no audio or video attached to this page yet.';
		var $media_format_overrides = array('Flash Video'=>'Flash');
		var $size_selector;
		var $primary_images = array();
		var $fallback_image;
		var $displayer_chrome; // used for displaying the av items

		var $noncanonical_request_keys = array(
										'show_transcript',
										'displayer_height');

		
		function init($args = array())
		{	
			if(isset($this->params['num_per_page']))
				$this->num_per_page = $this->params['num_per_page'];
			if(isset($this->params['show_list_with_details']))
				$this->show_list_with_details = $this->params['show_list_with_details'];
			
			// only load javascript and css for integrated items
			if ( !empty($this->request[ $this->query_string_frag.'_id' ]) )
			{
				$media_work = new entity($this->request[ $this->query_string_frag.'_id' ]);
				if ($media_work->get_values() && $media_work->get_value('type') == id_of('av'))
				{
					$head_items = $this->get_head_items();
					$head_items->add_javascript(JQUERY_URL, true);
					$this->displayer_chrome = MediaWorkFactory::displayer_chrome($media_work, 'av');
					if ($this->displayer_chrome)
					{
						$this->displayer_chrome->set_module($this);
						$this->displayer_chrome->set_head_items($head_items);
					}
				}
			}
	
			parent::init($args);
			$this->_init_social_media_integration();
		}
		
		function alter_es() // {{{
		{
			if($this->params['limit_to_current_page'])
			{
				$this->es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_av') );
				$this->es->set_site( NULL );
			}
			if ( !$this->params['relationship_sort'] || !$this->params['limit_to_current_page'])
			{
				$this->es->set_order( $this->params['sort_field'].' '.$this->params['sort_direction'] );
			}
			else
			{
				$this->es->add_rel_sort_field($this->parent->cur_page->id(), relationship_id_of('minisite_page_to_av'));
				$this->es->set_order('rel_sort_order ASC');
			}
			$this->es->add_relation( 'show_hide.show_hide = "show"' );
			$this->es->add_relation( '(media_work.transcoding_status = "ready" OR ISNULL(media_work.transcoding_status) OR media_work.transcoding_status = "")' );
			
		}
		
		function show_item_content( $item )
		{
			if($this->params['show_media_first'])
			{
				$this->display_media_work($item);
			}
			if($this->params['show_primary_image_with_audio'] && $item->get_value('av_type') == 'Audio' && ($image = $this->get_primary_image($item)))
			{
				$alt = strip_tags($image->get_value('description'));
				echo '<img src="'.reason_get_image_url($image).'" alt="'.reason_htmlspecialchars($alt).'" class="primaryImage" />';
			}
			if($item->get_value('datetime') || $item->get_value('media_publication_datetime') )
			{
				echo '<p class="date">';
				echo $this->get_date_information($item);
				echo '</p>'."\n";
			}
			if($item->get_value('author'))
			{
				echo '<p class="author">By '.$item->get_value('author').'</p>'."\n";
			}
			if($item->get_value('description'))
			{
				echo '<div class="desc">'.$item->get_value('description').'</div>'."\n";
			}
			if(!$this->params['show_media_first'])
			{
				$this->display_media_work($item);
			}
			if ($item->get_value('integration_library'))
				$this->display_transcript($item, 'item_id');
			else
				$this->display_transcript($item, 'av_file_id');
			$this->display_rights_statement($item);
			//if(!empty($this->params['show_next_prev']))
			//	$this->show_next_prev($item);
		}
		
		function get_date_information($item)
		{
			$ret = '';
			if($item->get_value('datetime') && $item->get_value('datetime') != '0000-00-00 00:00:00')
			{
				$ret .= '<span class="created">';
				if($item->get_value('media_publication_datetime')) $ret .= 'Created ';
				$ret .= prettify_mysql_datetime($item->get_value('datetime'),$this->date_format).'</span>';
				if($item->get_value('media_publication_datetime')) $ret .= '; ';
			}
			if($item->get_value('media_publication_datetime'))
			{
				$ret .= '<span class="published">Published '.prettify_mysql_datetime($item->get_value('media_publication_datetime'),$this->date_format).'</span>';
			}
			return $ret;
		}
		//Called on by show_list_item()
		function show_list_item_pre( $item )
		{
			$this->show_primary_image( $item );
		}
		
		//Called on by show_list_item
		function show_list_item_desc( $item )
		{
			if($item->get_value('description'))
			{
				echo '<div class="desc">'.$item->get_value('description').'</div>'."\n";
			}
		}
		
		function show_list_item_date( $item )
		{
			if($this->use_dates_in_list && ( $item->get_value( 'datetime' )|| $item->get_value('media_publication_datetime') ) )
				echo '<div class="smallText date">'.$this->get_date_information($item).'</div>'."\n";
		}
		
		function show_primary_image( $item )
		{
			if(empty($this->parent->textonly))
			{
				$image = $this->get_primary_image($item);
				if(!empty($image))
				{
					if($this->params['thumbnail_width'] != 0 or $this->params['thumbnail_height'] != 0)
					{
						$rsi = new reasonSizedImage();
						if(!empty($rsi))
						{
							$rsi->set_id($image->id());
							if($this->params['thumbnail_width'] != 0)
							{
								$rsi->set_width($this->params['thumbnail_width']);
							}
							if($this->params['thumbnail_height'] != 0)
							{
								$rsi->set_height($this->params['thumbnail_height']);
							}
							if($this->params['thumbnail_crop'] != '')
							{
								$rsi->set_crop_style($this->params['thumbnail_crop']);
							}
							$image = $rsi;
						}
					}
					
					$die_without_thumbnail = true;
					$show_popup_link = false;
					$show_description = false;
					$additional_text = '';
					if(empty($this->request[ $this->query_string_frag.'_id' ]) || $this->request[ $this->query_string_frag.'_id' ] != $item->id() )
					{
						$link = $this->construct_link($item);
					}
					else
					{
						$link = '';
					}
					
					show_image( $image, $die_without_thumbnail, $show_popup_link, $show_description, $additional_text, $this->parent->textonly, false, $link );
				}
			}
		}
	
		function get_primary_image($item)
		{
			if(!isset($this->primary_images[$item->id()]))
			{
				$item->set_env('site_id',$this->parent->site_id);
				$images = $item->get_left_relationship( relationship_id_of('av_to_primary_image') );
				if(!empty($images))
				{
					$this->primary_images[$item->id()] = current($images);	
				}
				elseif($fallback_image = $this->get_fallback_image())
				{
					$this->primary_images[$item->id()] = $fallback_image;	
				}
				else
				{
					$this->primary_images[$item->id()] = false;
				}
			}
			return $this->primary_images[$item->id()];
			
		} 
		
		function get_fallback_image()
		{
			if(!isset($this->fallback_image))
			{
				$this->fallback_image = false;
				if(!empty($this->params['fallback_image']))
				{
					if(reason_unique_name_exists($this->params['fallback_image']))
					{
						$image = new entity(id_of($this->params['fallback_image']));
						if($image->get_values() && id_of('image') == $image->get_value('type'))
						{
							$this->fallback_image = $image;
						}
					}
					if(empty($this->fallback_image))
						trigger_error('Fallback image with unique name '.$this->params['fallback_image'].' not found in Reason');
				}
			}
			return $this->fallback_image;
		}
		
		function get_cleanup_rules()
		{
			$this->cleanup_rules[$this->query_string_frag . '_id'] = array('function' => 'turn_into_int');
			$this->cleanup_rules['av_file_id'] = array('function'=>'turn_into_int');
			$this->cleanup_rules['show_transcript'] = array('function'=>'check_against_array','extra_args'=>array('true','false'));
			$this->cleanup_rules['displayer_height'] = array('function'=>'turn_into_int');
			return $this->cleanup_rules;
		}
		
		function get_av_files( $item )
		{
			$avf = new entity_selector();
			$avf->add_type( id_of('av_file' ) );
			$avf->add_right_relationship( $item->id(), relationship_id_of('av_to_av_file') );
			$avf->set_order('av.media_format ASC, av.av_part_number ASC');
			return $avf->run_one();
		}
		
		function display_media_work($item)
		{
			if ($this->displayer_chrome)
			{
				$this->displayer_chrome->set_media_work($item);
				echo $this->displayer_chrome->get_html_markup();
			}
			else
			{
				echo '<p>Sorry, this media is unavailable.</p>';
			}
		}
		
		function display_transcript( $item, $request_field )
		{
			
			if($item->get_value('transcript_status') == 'published')
			{
				$add_link_items = array();
				if(!empty($this->request[$request_field]))
				{
					$add_link_items[$request_field] = $this->request[$request_field];
				}
				if(!empty($this->request['show_transcript']) && $this->request['show_transcript'] == 'true')
				{
					$link = $this->construct_link($item,$add_link_items);
					echo '<div class="transcript"><h4>Transcript</h4>'."\n";
					echo '<div class="transcriptToggle"><a href="'.$link.'">Hide Transcript</a></div>'."\n";
					echo $item->get_value('content');
					echo '<div class="transcriptToggle"><a href="'.$link.'">Hide Transcript</a></div>'."\n";
					echo '</div>'."\n";
				}
				else
				{
					$add_link_items['show_transcript'] = 'true';
					$link = $this->construct_link($item,$add_link_items);
					echo '<div class="transcriptToggle"><a href="'.$link.'">View Transcript</a></div>'."\n";
				}
			}
		}
		
		function display_rights_statement( $item )
		{
			if($item->get_value('rights_statement'))
			{
				echo '<div class="rights">'."\n";
				echo $item->get_value('rights_statement');
				echo '</div>'."\n";
			}
		}
		
		function further_checks_on_entity( $entity )
		{
			if($this->params['limit_to_current_page'])
			{
				$es = new entity_selector();
				$es->add_type(id_of('av'));
				$es->add_relation('`entity`.`id` = "'.reason_sql_string_escape($entity->id()).'"');
				$es->add_right_relationship( $this->page_id, relationship_id_of('minisite_page_to_av') );
				$es->set_num(1);
				$es->limit_tables();
				$es->limit_fields();
				$results = $es->run_one();
				return (!empty($results));
			}
			return true;
		}
		
		/**
		 * A nice handler for missing av things. Attempts to point the user to another
		 * place to access the item, even if that place is on another site.
		 * 
		 * Takes the ID of a missing item and looks for it on pages that use a page 
		 * type which uses a module defined in the module set 'av_module_derivatives'.
		 * Requires reason_page_types and reason_module_sets.
		 * 
		 * @param $id int The entity ID of the missing item
		 * @return null;
		 * 
		 */
		function handle_missing_item($id)
		{
			// Get the list of modules
			reason_include_once('classes/module_sets.php');
			$ms =& reason_get_module_sets();
			$av_module_derivatives = $ms->get("av_module_derivatives");
			
			// Get the page types that use these modules.
			$rpts =& get_reason_page_types();
			$allowed_page_types = array();
			foreach ($av_module_derivatives as $mod){
				$allowed_page_types = array_merge($allowed_page_types, array_diff($rpts->get_page_type_names_that_use_module($mod), $allowed_page_types));
			}
			
			// Turn this list into a string.
			$serialized = "'" . implode("','", $allowed_page_types) . "'";
			
			// Build the ES
			$es = new entity_selector();
			$es->add_type(id_of('minisite_page'));
			$es->add_left_relationship($id, relationship_id_of('minisite_page_to_av'));
			$es->add_right_relationship_field('owns', 'entity', 'name', 'site_name');
			$es->add_relation("page_node.custom_page IN ($serialized)");
			$result = $es->run_one();

			echo '<div class="notice itemNotAvailable"><h3>Sorry -- this item is not available</h3>';
			// If there are suitable replacements found, display them...
			if (!empty($result))
			{
				$url = parse_url(get_current_url());
				
				if (count($result) == 1)
				{
					$new_page_link = reason_get_page_url(current($result)->id()) . '?' . $url['query'];
					header( 'Location: ' . $new_page_link, true, 301 );
					exit;
				}
				echo "<p>However, you might be able to find it at the following location" . ((count($result)-1) ? "s" : "" ) . ":</p>\n<ul>\n";
				foreach ($result as $key => $entity)
				{
					// Don't forget to pass a nice query string that includes the item of the av as well as the av_file_id if it's in the request.
					echo '<li><a href="' . reason_get_page_url($key) . "?" . $url['query'] . "\">{$entity->get_value("site_name")}: {$entity->get_value("name")}</a></li>";
				}
				echo "</ul>";
			} else {
			// Else just echo the normal 404. 
				echo '<p>This might be because...</p><ul><li>the page you are coming from has a bad link</li><li>there is a typo in the web address</li><li>the item you are requesting has been removed</li></ul>';
			}
			echo "</div>";
		}
		
		function list_items()
		{
			parent::list_items();
			
			$media_file_type = new entity(id_of('av_file'));
			$feed_link = $this->parent->site_info->get_value('base_url').MINISITE_FEED_DIRECTORY_NAME.'/'.$media_file_type->get_value('feed_url_string');
			$params = array();
			if($this->params['limit_to_current_page'])
			{
				$params[] = 'page_id='.$this->parent->cur_page->id();
			}
			if($this->params['relationship_sort'])
			{
				$params[] = 'rel_sort=1';
			}
			if(!empty($params))
				$feed_link .= '?'.implode('&amp;',$params);
			echo '<p class="podcast">';
			echo '<a href="'.$feed_link.'" class="feedLink">Podcast Feed</a>';
			echo ' <a href="itpc://'.HTTP_HOST_NAME.$feed_link.'" class="subscribe">Subscribe in iTunes</a>';
			echo ' <a href="feed://'.HTTP_HOST_NAME.$feed_link.'" class="subscribe">Subscribe on iOS</a>';
			echo '</p>'."\n";
			if(defined('REASON_URL_FOR_PODCAST_HELP'))
			{
				echo '<p class="smallText podcastHelp"><a href="'.REASON_URL_FOR_PODCAST_HELP.'">What\'s a podcast, and how does this work?</a></p>';
			}
		}


		/**
		 * Init and add needed head items for social media integration.
		 *
		 * This is triggered in post_es_additional_init_actions.
		 *
		 * @todo add parameters for further integration that this method pays attention to.
		 */
		function _init_social_media_integration() 
		{	
			// for now, lets just add sensible open graph tags for the item if we have a current item
			if ($this->current_item_id)
			{
				$this->_add_open_graph_tags_for_item();
			}
		}
	
		/**
		 * Add basic metadata using the open graph protocol (http://ogp.me/).
		 *
		 * This should improve how shared items appear on facebook and possibly other social networks.
		 *
		 * @todo add integration with propietary tags for specific social networks.
		 */
		function _add_open_graph_tags_for_item()
		{
			if(empty($this->current_item_id))
				return;
			$item = new entity($this->current_item_id);
			$itemValues = $item->get_values();
			if (reason_is_entity($item) && !empty($itemValues))
			{
				$title = htmlspecialchars(trim(strip_tags($item->get_value('name'))),ENT_QUOTES,'UTF-8');
				$description = htmlspecialchars(trim(str_replace('&nbsp;', '', strip_tags($item->get_value('description')))),ENT_QUOTES,'UTF-8');
				$url = carl_construct_link(array(''), array('item_id') );
				$image = $this->get_primary_image($item);
				if ($teaser = $image)
				{
					$teaser = reset($teaser);
					$image_urls[] = reason_get_image_url($teaser);
				}
				elseif ($image = $this->get_primary_image($item))
				{
					foreach ($images as $image)	
					{
						$image_urls[] = reason_get_image_url($image);
					}
				}
				$site = new entity($this->site_id);
				if ($site) $site_name = reason_htmlspecialchars(trim(strip_tags($site->get_value('name'))));
				if(defined('FULL_ORGANIZATION_NAME') && strtolower(FULL_ORGANIZATION_NAME) != strtolower($site_name))
					$site_name .= ', '.FULL_ORGANIZATION_NAME;
				$head_items =& $this->get_head_items();
				$head_items->add_head_item('meta',array( 'property' => 'og:title', 'content' => $title));
				$head_items->add_head_item('meta',array( 'property' => 'og:url', 'content' => $url));
				if (!empty($description)) $head_items->add_head_item('meta',array( 'property' => 'og:description', 'content' => $description));
				if (!empty($image_urls))
				{
					foreach ($image_urls as $image_url)
					{
						$head_items->add_head_item('meta',array( 'property' => 'og:image', 'content' => 'http://'.$_SERVER['HTTP_HOST'].$image_url));
						if (HTTPS_AVAILABLE) $head_items->add_head_item('meta',array( 'property' => 'og:image:secure_url', 'content' => 'https://'.$_SERVER['HTTP_HOST'].$image_url));
					}	
				}
				if (!empty($site_name)) $head_items->add_head_item('meta',array( 'property' => 'og:site_name', 'content' => $site_name));
			}
		}	
		/* function show_next_prev($item)
		{
			$next = $this->get_next_item($item->id());
			$prev = $this->get_previous_item($item->id());
			if(!empty($next) || !empty($prev))
			{
				echo '<div class="nextPrev">';
				if(!empty($next))
					echo 'next';
				if(!empty($prev))
					echo 'prev';
				echo '</div>';
			}
		} */
	}
?>	
