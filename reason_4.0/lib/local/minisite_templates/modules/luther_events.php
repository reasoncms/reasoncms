<?php 
reason_include_once( 'minisite_templates/modules/events.php' );
reason_include_once( 'classes/calendar.php' );
reason_include_once( 'classes/calendar_grid.php' );
reason_include_once( 'classes/icalendar.php' );
reason_include_once( 'classes/google_mapper.php' );
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherEventsModule';

class LutherEventsModule extends EventsModule
{
	var $list_date_format = 'l, F j';
	var $show_months = false;
	var $show_icalendar_links = true;
	
	//////////////////////////////////////
	// For The Events Listing
	//////////////////////////////////////
	function show_event_details()
	{
		$sponsorContactUrl = false;
		$e =& $this->event;
		if (preg_match("/^[Rr]edirect:?\s?(.*?)$/", $e->get_value( 'url' ), $matches))
		// Redirect to another site to display event information or registration
		{
			echo '<script>
			window.location.replace("'.$matches[1].'");
			</script>'."\n";
		}
		if ($this->is_sports_event($e->get_value('sponsor')))
		{
			$postToResults = (preg_match("/post_to_results/", $e->get_value( 'contact_organization' ))) ? true : false;
			echo '<div class="eventDetails">'."\n";
			$this->show_back_link();
			//$this->show_images($e);
			echo '<h1>'.ucfirst(preg_replace("|(^.*?)\s\((w?o?m?en)\)$|", "\\2's \\1", $e->get_value('sponsor')))." - ".$e->get_value( 'name' ).'</h1>'."\n";
			if (!$postToResults)
				$this->show_repetition_info($e);
			//$this->show_ownership_info($e);			
			$st = substr($e->get_value('datetime'), 0, 10);
			$lo = substr($e->get_value('last_occurence'), 0, 10);
			echo '<table>'."\n";
			if (!empty($this->request['date']) && strstr($e->get_value('dates'), $this->request['date']))
			{
				if ($postToResults)
				{		
					if ($lo != $st)
					{
						echo '<tr><td width="15%">Date:</td><td width="85%">'.prettify_mysql_datetime($st, "F j, Y" ).' - '.prettify_mysql_datetime($lo, "F j, Y").'</td></tr>'."\n";
					}
					else
					{
						echo '<tr><td width="15%">Date:</td><td width="85%">'.prettify_mysql_datetime( $this->request['date'], "F j, Y" ).'</td></tr>'."\n";
					}
				}
				else 
				{
					echo '<tr><td width="15%">Date:</td><td width="85%">'.prettify_mysql_datetime( $this->request['date'], "l, F j, Y" ).'</td></tr>'."\n";
				}
			}		
			$dateTime = $postToResults ? "Results:" : "Time:";
			if (preg_match("/https?:\/\/[A-Za-z0-9_\-\.\/]+/", $e->get_value( 'description' ), $matches))
			{
				echo '<tr><td width="15%">' . $dateTime . '</td><td width="85%"><a title="Live stats" href="'. $matches[0] . '">Live stats</a></td></tr>'."\n";
			}
			else if ($e->get_value( 'description' ) != '')
			{
				echo '<tr><td width="15%">' . $dateTime . '</td><td width="85%">' . $e->get_value( 'description' ) . '</td></tr>'."\n";
			}
			else if (substr($e->get_value('datetime'), 11) != '00:00:00')
			{
				echo '<tr><td width="15%">' . $dateTime . '</td><td width="85%">' . prettify_mysql_datetime($e->get_value('datetime'), "g:i a" ) . '</td></tr>'."\n";
			}						
			if ($e->get_value('location'))
				echo '<tr><td width="15%">Location:</td><td width="85%">'.$e->get_value('location') . $this->video_audio_streaming($e->get_value('id')) . '</td></tr>'."\n";
			echo '</table>'."\n";
		}
		else
		{		
			echo '<div class="eventDetails">'."\n";
			$this->show_back_link();
			//$this->show_images($e);
			echo '<h1>'.$e->get_value('name').'</h1>'."\n";
			//$this->show_ownership_info($e);
			$this->show_repetition_info($e);
			echo '<table>'."\n";
			if (!empty($this->request['date']) && strstr($e->get_value('dates'), $this->request['date']))
				echo '<tr><td width="15%">Date:</td><td width="85%">'.prettify_mysql_datetime( $this->request['date'], "l, F j, Y" ).'</td></tr>'."\n";
			if(substr($e->get_value( 'datetime' ), 11) != '00:00:00')
				echo '<tr><td width="15%">Time:</td><td width="85%">'.prettify_mysql_datetime( $e->get_value( 'datetime' ), "g:i a" ).'</td></tr>'."\n";
			$this->show_duration($e);
			if ($e->get_value('location'))
				echo '<tr><td width="15%">Location:</td><td width="85%">'.$e->get_value('location').'</td></tr>'."\n";
			echo '</table>'."\n";
			if ($e->get_value('description'))
			{
				echo '<p class="description">'.$e->get_value( 'description' ).'</p>'."\n";
			}
		}
		if ($e->get_value('content'))
		{
			echo $e->get_value( 'content' )."\n";
		}
		if ($e->get_value('sponsor'))
		{
			echo '<p class="sponsor">Sponsor: '.$e->get_value('sponsor').'</p>'."\n";
			$sponsorContactUrl = true;
		}		
		$this->show_contact_info($e);
		if(!empty($contact))
		{
			$sponsorContactUrl = true;
		}
		if ($e->get_value('url'))
		{
			echo '<p class="eventUrl">For more information, visit: <a href="'.$e->get_value( 'url' ).'">'.$e->get_value( 'url' ).'</a>.</p>'."\n";
		}
		if ($sponsorContactUrl)
		{
			echo '<p class="eventUrl">&nbsp;</p>'."\n";
		}
		if($this->show_icalendar_links)
		{
			$this->show_item_export_link($e);
		}
		$this->show_google_map($e);
		echo '</div>'."\n";

	}
	
	function show_back_link()
	{
		echo '<p class="back"><a title="Back to event listings" href="'.$this->construct_link().'">&#x25c4;</a></p>'."\n";
	}
	
	function show_repetition_info(&$e)
	{
		$rpt = $e->get_value('recurrence');
		$freq = '';
		$words = array();
		$dates_text = '';
		$occurence_days = array();
		if (!($rpt == 'none' || empty($rpt)))
		{
			$words = array('daily'=>array('singular'=>'day','plural'=>'days'),
							'weekly'=>array('singular'=>'week','plural'=>'weeks'),
							'monthly'=>array('singular'=>'month','plural'=>'months'),
							'yearly'=>array('singular'=>'year','plural'=>'years'),
					);
			if ($e->get_value('frequency') <= 1)
				$sp = 'singular';
			else
			{
				$sp = 'plural';
				$freq = $e->get_value('frequency').' ';
			}
			if ($rpt == 'weekly')
			{
				$days_of_week = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
				foreach($days_of_week as $day)
				{
					if($e->get_value($day))
						$occurence_days[] = $day;
				}
				$last_day = array_pop($occurence_days);
				$dates_text = ' on ';
				if (!empty( $occurence_days ) )
				{
					$comma = '';
					if(count($occurence_days) > 2)
						$comma = ',';
					$dates_text .= ucwords(implode(', ', $occurence_days)).$comma.' and ';
				}
				$dates_text .= prettify_string($last_day);
			}
			elseif ($rpt == 'monthly')
			{
				$suffix = array(1=>'st',2=>'nd',3=>'rd',4=>'th',5=>'th');
				if ($e->get_value('week_of_month'))
				{
					$dates_text = ' on the '.$e->get_value('week_of_month');
					$dates_text .= $suffix[$e->get_value('week_of_month')];
					$dates_text .= ' '.$e->get_value('month_day_of_week');
				}
				else
					$dates_text = ' on the '.prettify_mysql_datetime($e->get_value('datetime'), 'j').' day of the month';
			}
			elseif ($rpt == 'yearly')
			{
				$dates_text = ' on '.prettify_mysql_datetime($e->get_value('datetime'), 'F j');
			}
			echo '<p class="repetition">This event takes place each ';
			echo $freq;
			echo $words[$rpt][$sp];
			echo $dates_text;
			echo ' from '.prettify_mysql_datetime($e->get_value('datetime'), 'F j, Y').' to '.prettify_mysql_datetime($e->get_value('last_occurence'), 'F j, Y').'.';
			
			echo '</p>'."\n";
		}		
	}
	
	function show_contact_info(&$e)
	{
		$contact = $e->get_value('contact_username');
		if(!empty($contact) )
		{
			$dir = new directory_service();
			$dir->search_by_attribute('ds_username', array(trim($contact)), array('ds_email','ds_fullname','ds_phone',));
			
			$email = $dir->get_first_value('ds_email');
			$fullname = $dir->get_first_value('ds_fullname');
			if (($dir->get_first_value('edupersonaffiliation') != 'Student') || ($dir->get_first_value('edupersonaffiliation') != 'Emeritus') ){
				$phone = $dir->get_first_value('ds_phone');
			}
				
			echo '<p class="contact"><strong>Contact:</strong> ';
			if(!empty($email))
				echo '<a href="mailto:'.$email.'">';
			if(!empty($fullname))
				echo $fullname;
			else
				echo $contact;
			if(!empty($email))
				echo '</a>';
			if (!empty($phone))
				echo ', '.$phone;
			echo '</p>'."\n";
		}
	}
	
	function show_dates(&$e)
	{
		$dates = explode(', ', $e->get_value('dates'));
		if(count($dates) > 1 || empty($this->request['date']) || !strstr($e->get_value('dates'), $this->request['date']))
		{
			echo '<div class="dates"><h4>This event occurs on:</h4>'."\n";
			echo '<ul>'."\n";
			foreach($dates as $date)
			{
				echo '<li>'.prettify_mysql_datetime( $date, "l, F j, Y" ).'</li>'."\n";
			}
			echo '</ul>'."\n";
			echo '</div>'."\n";
		}
	}
	
	function show_event_list_item( $event_id, $day, $ongoing_type = '' )
	{
		$inline_edit =& get_reason_inline_editing($this->page_id);
		$editable = $inline_edit->available_for_module($this);
		if ($editable && $this->user_can_inline_edit_event($event_id))
		{
			$active = $inline_edit->active_for_module($this);
			$class = ($active) ? 'editable editing' : 'editable';
			echo '<div class="'.$class.'">'."\n";
			if (!$active) echo '<div class="editRegion">';
		}
	
		if($this->params['list_type'] == 'verbose')
			$this->show_event_list_item_verbose( $event_id, $day, $ongoing_type );
		else if($this->params['list_type'] == 'schedule')
			$this->show_event_list_item_schedule( $event_id, $day, $ongoing_type );
		else
			$this->show_event_list_item_standard( $event_id, $day, $ongoing_type );
	
		// We're currently only showing edit options if you're on a calendar page
		// (signified by the absence of $events_page_url). Inline editing events in
		// sidebars, etc., is a complicated issue.
		if (!$this->events_page_url)
		{
			if ($editable && $this->user_can_inline_edit_event($event_id))
			{
				if ($active)
				{
					echo 'EDITING';
				}
				else
				{
					$activation_params = $inline_edit->get_activation_params($this);
					$activation_params['edit_id'] = $event_id;
					$activation_params['event_id'] = $event_id;
					$url = carl_make_link($activation_params);
					echo ' <a href="'.$url.'" class="editThis">Edit Event</a></div>'."\n";
				}
				echo '</div>';
			}
		}
	}
	
	function show_event_list_item_standard( $event_id, $day, $ongoing_type = '' )
	{
		$link = $this->events_page_url.$this->construct_link(array('event_id'=>$this->events[$event_id]->id(),'date'=>$day));
		echo '<table><tr><td width="15%">';
		if($this->show_times && substr($this->events[$event_id]->get_value( 'datetime' ), 11) != '00:00:00')
		{
			echo prettify_mysql_datetime( $this->events[$event_id]->get_value( 'datetime' ), $this->list_time_format );
		}
		else
		{
			echo 'All day';
		}
		if ($this->is_sports_event($this->events[$event_id]->get_value('sponsor')))
		{
			$event_name = ucfirst(preg_replace("|(^.*?)\s\((w?o?m?en)\)$|", "\\2's \\1", $this->events[$event_id]->get_value('sponsor')))." - ".$this->events[$event_id]->get_value( 'name' );
		}
		else 
		{
			$event_name = $this->events[$event_id]->get_value( 'name' );
		}
		echo '</td><td width="85%"><a href="'.$link.'">';
		echo $event_name;
		echo '</a>';
		switch($ongoing_type)
		{
			case 'starts':
				echo ' <span class="begins">begins</span>';
			case 'through':
				echo ' <em class="through">(through '.$this->_get_formatted_end_date($this->events[$event_id]).')</em> ';
				break;
			case 'ends':
				echo ' <span class="ends">ends</span>';
				break;
		}
		echo '</td></tr></table>'."\n";
	}
	
	function show_event_list_item_verbose( $event_id, $day, $ongoing_type = '' )
	{
		$this->show_event_list_item_standard( $event_id, $day, $ongoing_type);
	}
	
	function show_item_export_link($e)
	{
		$imgCal = "/images/calendar_40.png";
		echo '<div class="export">'."\n";
		if($e->get_value('recurrence') == 'none' || empty($this->request['date']))
		{
			echo '<a href="'.$this->construct_link(array('event_id'=>$e->id(),'format'=>'ical')).'"><img class="ical" src="' . $imgCal .'" alt="calendar_image" title="Add to calendar..."></a>';
		}
		else
		{
			echo '<a href="'.$this->construct_link(array('event_id'=>$e->id(),'format'=>'ical','date'=>'')).'"><img class="ical" src="' . $imgCal .'" alt="calendar_image" title="Add to calendar..."></a>';
		}
		echo '</div>'."\n";
	}
	
	function get_all_categories() // {{{
	{
		$ret = '';
		$cs = new entity_selector();
		$cs = new entity_selector($this->parent->site_id);
		$cs->set_site($this->parent->site_id);
		$cs->description = 'Selecting all categories on the site';
		$cs->add_type(id_of('category_type'));
		$cs->set_order('entity.name ASC');
		$cs->set_cache_lifespan($this->get_cache_lifespan_meta());
		$cats = $cs->run_one();
		$cats = $this->check_categories($cats);
		if(empty($cats))
			return '';
		$ret .= '<div class="categories';
		if ($this->calendar->get_view() == "all")
			$ret .= ' divider';
		$ret .= '">'."\n";
		$ret .= '<h4>Event Categories</h4>'."\n";
		$ret .= '<ul>'."\n";
		$ret .= '<li>';
		$used_cats = $this->calendar->get_categories();
		if (empty( $used_cats ))
			$ret .= '<strong>All</strong>';
		else
			$ret .= '<a href="'.$this->construct_link(array('category'=>'','view'=>'')).'" title="Events in all categories">All</a>';
		$ret .= '</li>';
		foreach($cats as $cat)
		{	
			// don't show categories borrowed from the "events" minisite on the individual minisites
			if (get_owner_site_id($cat->id()) != id_of('events'))
			{
				$ret .= '<li>';
				if (array_key_exists($cat->id(), $this->calendar->get_categories()))
					$ret .= '<strong>'.$cat->get_value('name').'</strong>';
				else
					$ret .= '<a href="'.$this->construct_link(array('category'=>$cat->id(),'view'=>'','no_search'=>'1')).'" title="'.reason_htmlspecialchars(strip_tags($cat->get_value('name'))).' events">'.$cat->get_value('name').'</a>';
				$ret .= '</li>';
			}
		}
		$ret .= '</ul>'."\n";
		$ret .= '</div>'."\n";
		return $ret;
	}
	
	function no_events_error()
	{
		echo '<div class="newEventsError">'."\n";
		$start_date = $this->calendar->get_start_date();
		$audiences = $this->calendar->get_audiences();
		$categories = $this->calendar->get_categories();
		$min_date = $this->calendar->get_min_date();
		if($this->calendar->get_view() == 'all' && empty($categories) && empty( $audiences ) && empty($this->request['search']) )
		{
			//trigger_error('get_max_date called');
			$max_date = $this->calendar->get_max_date();
			if(empty($max_date))
			{
				echo '<p>This calendar does not have any events.</p>'."\n";
			}
			else
			{
				echo '<p>There are no future events in this calendar.</p>'."\n";
				echo '<ul>'."\n";
				echo '<li><a href="'.$this->construct_link(array('start_date'=>$max_date, 'view'=>'all','category'=>'','audience'=>'','search'=>'')).'">View most recent event</a></li>'."\n";
				if($start_date > '1970-01-01')
				{
					echo '<li><a href="'.$this->construct_link(array('start_date'=>$min_date, 'view'=>'all','category'=>'','audience'=>'','search'=>'')).'">View entire event archive</a></li>'."\n";
				}
				echo '</ul>'."\n";
			}
		}
		else
		{
			if(empty($categories) && empty($audiences) && empty($this->request['search']))
			{
				$desc = $this->get_scope_description();
				if(!empty($desc))
				{
					echo '<p>There are no events '.$this->get_scope_description().'.</p>'."\n";
					if($start_date > '1970-01-01')
					{
						echo '<ul><li><a href="'.$this->construct_link(array('start_date'=>'1970-01-01', 'view'=>'all')).'">View entire event archive</a></li></ul>'."\n";
					}
				}
				else
				{
					echo '<p>There are no events available.</p>'."\n";
				}
			}
			else
			{
				echo '<p>There are no events available';
				$clears = '<ul>'."\n";
				if(!empty($audiences))
				{
					$audience = current($audiences);
					echo ' for '.strtolower($audience->get_value('name'));
					$clears .= '<li><a href="'.$this->construct_link(array('audience'=>'')).'">Clear group/audience</a></li>'."\n";
				}
				if(!empty($categories))
				{
					$cat = current($categories);
					echo ' in the '.$cat->get_value('name').' category';
					$clears .= '<li><a href="'.$this->construct_link(array('category'=>'')).'">Clear category</a></li>'."\n";
				}
				if(!empty($this->request['search']))
				{
					echo ' that match your search for "'.htmlspecialchars($this->request['search']).'"';
					$clears .= '<li><a href="'.$this->construct_link(array('search'=>'')).'">Clear search</a></li>'."\n";
				}
				$clears .= '</ul>'."\n";
				echo $clears;
			
				if($this->calendar->get_start_date() > $this->today)
				{
					echo '<p><a href="'.$this->construct_link(array('start_date'=>'', 'view'=>'','category'=>'','audience'=>'', 'end_date'=>'','search'=>'')).'">Reset calendar to today</a></p>';
				}
				if($start_date > '1970-01-01')
				{
					echo '<p><a href="'.$this->construct_link(array('start_date'=>'1970-01-01', 'view'=>'all')).'">View entire event archive</a></p>'."\n";
				}
			}
		}
		echo '</div>'."\n";
	}
	
	function find_events_page()
	// used to find the url of the page that contains the list of events
	{
		reason_include_once( 'minisite_templates/nav_classes/default.php' );
		$ps = new entity_selector($this->parent->site_id);
		$ps->add_type( id_of('minisite_page') );
		$rels = array();
		foreach($this->events_page_types as $page_type)
		{
			$rels[] = 'page_node.custom_page = "'.$page_type.'"';
		}
		$ps->add_relation('( '.implode(' OR ', $rels).' )');
		$page_array = $ps->run_one();
		reset($page_array);
		$this->events_page = current($page_array);
		if (!empty($this->events_page))
		{
			$ret = $this->parent->pages->get_full_url($this->events_page->id());
		}
		if(!empty($ret))
			$this->events_page_url = $ret;
	}
	
	function show_google_map(&$e)
	{
		$site_id = $this->site_id;
		$es = new entity_selector( $site_id );
		$es->add_type( id_of( 'google_map_type' ) );
		$es->add_right_relationship($e->id(), relationship_id_of('event_to_google_map'));
		$es->add_rel_sort_field($e->id(), relationship_id_of('event_to_google_map'));
		$es->set_order('rel_sort_order');
		$gmaps = $es->run_one();
		
		draw_google_map($gmaps);
		
	}
	
	function video_audio_streaming($event_id, $imgVideo = "/images/luther2010/video_camera_gray_128.png", $imgAudio = "/images/luther2010/headphones_gray_256.png")
	// check if video/audio streaming categories are present for an event
	{
		$es = new entity_selector();
		$es->description = 'Selecting categories for event';
		$es->add_type( id_of('category_type'));
		$es->add_right_relationship( $event_id, relationship_id_of('event_to_event_category') );
		$cats = $es->run_one();
		$vstream = '';
		$astream = '';
		foreach( $cats AS $cat )
		{
			if ($cat->get_value('name') == 'Video Streaming')
			{
				$vstream = '<a title="Video Streaming" href="http://client.stretchinternet.com/client/luther.portal"><img class="video_streaming" src="' . $imgVideo .'" alt="Video Streaming"></a>';
			}
			if ($cat->get_value('name') == 'Audio Streaming')
			{
				$astream = '<a title="Audio Streaming" href="http://www.luther.edu/kwlc/"><img class="audio_streaming" src="' . $imgAudio .'" alt="Audio Streaming" title="Audio Streaming"></a>';
			}
		}
		return $astream . $vstream;
	}
	
	function is_sports_event($sponsor)
	{
		$url = get_current_url();
		if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/sports\/?/", $url)
			|| preg_match("/([Bb]aseball|[Bb]asketball|[Cc]ross [Cc]ountry|[Ff]ootball|[Gg]olf|[Ss]occer|[Ss]oftball|[Ss]wimming|[Tt]ennis|[Tt]rack|[Vv]olleyball|[Ww]restling)/", $sponsor))
		{
			return true;
		}
		return false;
	}
	

}
?>
