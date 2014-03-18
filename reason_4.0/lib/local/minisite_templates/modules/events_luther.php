<?php

reason_include_once( 'minisite_templates/modules/events.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsLutherModule';

/**
 * Luther extension of Reason Events module
 *
 * By default, this module shows upcoming events on the current site,
 * and proves an interface to see past events
 */

class EventsLutherModule extends EventsModule
{
	
	public function is_sports_event($sponsor)
	// Is the event from one of the Luther sports minisites?
	{
		$url = get_current_url();
		if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/sports\/?/", $url)
				|| preg_match("/([Bb]aseball|[Bb]asketball|[Cc]ross [Cc]ountry|[Ff]ootball|[Gg]olf|[Ss]occer|[Ss]oftball|[Ss]wimming|[Tt]ennis|[Tt]rack|[Vv]olleyball|[Ww]restling)/", $sponsor))
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Populate the $next_and_previous_links class variable with appropriate markup
	 *
	 * @return void
	 * @todo move into markup class
	 */
	function generate_next_and_previous_links()
	{
		$start_array = explode('-',$this->calendar->get_start_date() );
		if ($this->calendar->get_view() != 'all')
		{
			$show_links = true;
			$prev_u = 0;
			//$end_array = explode('-',$this->calendar->get_end_date() );
			if( $this->calendar->get_view() == 'daily' )
			{
				$prev_u = get_unix_timestamp($this->calendar->get_start_date()) - 60*60*24;
				$next_u = get_unix_timestamp($this->calendar->get_start_date()) + 60*60*24;
				$word = '';
			}
			elseif($this->calendar->get_view() == 'weekly')
			{
				$prev_u = get_unix_timestamp($this->calendar->get_start_date()) - 60*60*24*7;
				$next_u = get_unix_timestamp($start_array[0].'-'.$start_array[1].'-'.str_pad($start_array[2]+7, 2, "0", STR_PAD_LEFT));
				$word = 'Week';
			}
			elseif($this->calendar->get_view() == 'monthly')
			{
				$prev_u = get_unix_timestamp($start_array[0].'-'.str_pad($start_array[1]-1, 2, "0", STR_PAD_LEFT).'-'.$start_array[2]);
				$next_u = get_unix_timestamp($start_array[0].'-'.str_pad($start_array[1]+1, 2, "0", STR_PAD_LEFT).'-'.$start_array[2]);
				$word = 'Month';
			}
			elseif($this->calendar->get_view() == 'yearly')
			{
				$prev_u = get_unix_timestamp($start_array[0]-1 .'-'.$start_array[1].'-'.$start_array[2]);
				$next_u = get_unix_timestamp($start_array[0]+1 .'-'.$start_array[1].'-'.$start_array[2]);
				$word = 'Year';
			}
			else
			{
				$show_links = false;
			}
			if($show_links)
			{
				$prev_start = date('Y-m-d', $prev_u);
				$next_start = date('Y-m-d', $next_u);
	
				$starting = '';
				if($this->calendar->get_view() != 'daily')
					$starting = ' Starting';
					
				$format_prev_year = '';
				if (date('Y', $prev_u) != date('Y'))
				{
					$format_prev_year = ', Y';
				}
	
				$format_next_year = '';
				if (date('Y', $next_u) != date('Y'))
					$format_next_year = ', Y';
				if($this->calendar->contains_any_events_before($this->calendar->get_start_date()) )
				{
					$this->next_and_previous_links = '<a class="previous" href="';
					$link_params = array('start_date'=>$prev_start,'view'=>$this->calendar->get_view());
					if(in_array($this->calendar->get_view(),$this->views_no_index))
						$link_params['no_search'] = 1;
					$this->next_and_previous_links .= $this->construct_link($link_params);
					if(date('M', $prev_u) == 'May') // All months but may need a period after them
						$punctuation = '';
					else
						$punctuation = '.';
					$this->next_and_previous_links .= '" title="View '.$word.$starting.' '.date('M'.$punctuation.' j'.$format_prev_year, $prev_u).'">';
					$this->next_and_previous_links .= '<i class="fa fa-chevron-circle-left"></i></a> &nbsp; ';
				}
			}
			$this->next_and_previous_links .= '<strong>'.$this->get_scope('&#8212;').'</strong>';
			if($show_links && $this->calendar->contains_any_events_after($next_start) )
			{
				$this->next_and_previous_links .= ' &nbsp; <a class="next" href="';
				$link_params = array('start_date'=>$next_start,'view'=>$this->calendar->get_view());
				if(in_array($this->calendar->get_view(),$this->views_no_index))
					$link_params['no_search'] = 1;
				$this->next_and_previous_links .= $this->construct_link($link_params);
				if(date('M', $next_u) == 'May') // All months but may need a period after them
					$punctuation = '';
				else
					$punctuation = '.';
				$this->next_and_previous_links .= '" title="View '.$word.$starting.' '.date('M'.$punctuation.' j'.$format_next_year, $next_u).'">';
				$this->next_and_previous_links .= '<i class="fa fa-chevron-circle-right"></i></a>'."\n";
			}
		}
		else // "all" view should have a 1-month-back link
		{
			$this->next_and_previous_links = '';
				
			if($this->calendar->contains_any_events_before($this->calendar->get_start_date()) )
			{
					
				$prev_u = get_unix_timestamp($start_array[0].'-'.str_pad($start_array[1]-1, 2, "0", 	STR_PAD_LEFT).'-'.$start_array[2]);
	
				$prev_start = date('Y-m-d', $prev_u);
	
				$format_prev_year = '';
				if (date('Y', $prev_u) != date('Y'))
				{
					$format_prev_year = ', Y';
				}
	
				$this->next_and_previous_links = '<a class="previous" href="';
				$link_params = array('start_date'=>$prev_start,'view'=>'monthly');
				if(in_array($this->calendar->get_view(),$this->views_no_index))
					$link_params['no_search'] = 1;
				$this->next_and_previous_links .= $this->construct_link($link_params);
				if(date('M', $prev_u) == 'May') // All months but may need a period after them
					$punctuation = '';
				else
					$punctuation = '.';
				$this->next_and_previous_links .= '" title="View Month Starting '.date('M'.$punctuation.' j'.$format_prev_year, $prev_u).'">';
				$this->next_and_previous_links .= '<i class="fa fa-chevron-circle-left"></i></a> &nbsp; ';
			}
				
			$this->next_and_previous_links .= '<strong>Starting '.prettify_mysql_datetime($this->calendar->get_start_date(),$this->list_date_format.', Y');
			switch($this->calendar->get_start_date())
			{
				case $this->today:
					$this->next_and_previous_links .= ' (today)';
					break;
				case $this->tomorrow:
					$this->next_and_previous_links .= ' (tomorrow)';
					break;
				case $this->yesterday:
					$this->next_and_previous_links .= ' (yesterday)';
					break;
			}
			$this->next_and_previous_links .= '</strong>';
		}
	}
	
	/**
	 * Display the ical link section
	 *
	 * @return void
	 * @todo Move to a markup class
	 */
	function show_list_export_links()
	{
		echo '<div class="iCalExport">'."\n";
	
		/* If they are looking at the current view or a future view, start date in link should be pinned to current date.
		 If they are looking at an archive view, start date should be pinned to the start date they are currently viewing */
	
		$start_date = $this->today;
		if(!empty($this->request['start_date']) && $this->_get_start_date() < $this->today)
		{
			$start_date = $this->request['start_date'];
		}
	
		$query_string = $this->construct_link(array('start_date'=>$start_date,'view'=>'','end_date'=>'','format'=>'ical'));
		if(!empty($this->request['category']) || !empty($this->request['audience']) || !empty($this->request['search']))
		{
			$subscribe_text = 'Subscribe to this view in desktop calendar';
			$download_text = 'Download these events (.ics)';
		}
		else
		{
			$subscribe_text = 'Subscribe to this calendar';
			$download_text = 'Download events (.ics)';
		}
		//echo '<a href="webcal://'.REASON_HOST.$this->parent->pages->get_full_url( $this->page_id ).$query_string.'">'.$subscribe_text.'</a>';
		if(!empty($this->events))
			echo '<a href="'.$query_string.'" title="'.$download_text.'"><i class="fa fa-calendar"></i></a>';
		echo '</div>'."\n";
	}
	
}
