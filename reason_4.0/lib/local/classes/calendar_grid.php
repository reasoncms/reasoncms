<?php
/**
* A class to generate the HTML necessary to make a calendar with linked dates.
* @package reason
* @subpackage classes
* @author Meg Gibbs
* 21-7-2005
*/

	/**
	 * Include dependencies
	 */
	include_once( 'reason_header.php' );
	reason_include_once( 'function_libraries/calendar_utilities.php' );
	
	/**
	 * A class to generate the HTML necessary to make a calendar with linked dates.
	 *
	 * Notes: I have dumbed the calendar grid down a lot.  
	 * It now just gets links for next and previous months and does not try to 
	 * determine that stuff itself.
	 */
	class calendar_grid
	{
		var $wrapper_class = 'calendarGrid';
		var $vew_year;		//string - year being viewed.  Defaults to current year. Format = 'Y'
		var $view_month; 	//string - month being viewed.  Defaults to current month. Format ='m'
		var $view_day;		//string - date being viewed.  Not required to be set. Format = 'j'
		
		var $linked_dates = array(); //array - all dates which should have links. Format: day_of_month=>link.
										//LINKS WILL NOT APPEAR PROPERLY IF YOU DO NOT USE THE FORMAT  'j'
		var $next_month_query_string;	//string - query for the link to the next or previous month.
		var $previous_month_query_string;	//string - query for the link to the next or previous month
		var $table_summary = 'Calendar grid';			//string - the summary for the HTML table that makes up the calendar.

		var $weekdays = array(	//Days of the week as they will appear on the calendar.
			'Sunday' => 'S',
			'Monday' => 'M',
			'Tuesday' => 'T',
			'Wednesday' => 'W',
			'Thursday' => 'Th',
			'Friday' => 'F',
			'Saturday' => 'S',
		);
		var $heading_tag = 'h4';
		var $date_classes = array();
		
		/**
		* Constructor.  
		* @access public
		*/
		function calendar_grid()
		{
			$this->set_default_vals();
		}
		
		/**
		* Sets default values for variables that are required to be set.
		* @access private
		*/
		function set_default_vals()
		{
			if(!isset($this->year))
				$this->view_year = date('Y');
			if(!isset($this->month))
				$this->view_month = date('m');
		}
		function get_previous_month_name()
		{
			return 'previous month';
		}
		function get_next_month_name()
		{
			return 'next month';
		}
		
		/**
		* Generates the HTML markup for the calendar grid
		* @access public
		* @return string The HTML markup of the calendar
		*/
		function get_calendar_markup()
		{	
			$calendar_data = get_calendar_data_for_month( $this->view_year, $this->view_month );
			$now_year = date('Y');
			$now_month = date('m');
			$now_day = date('j');
			
			if($this->view_year == $now_year && $this->view_month == $now_month)
			{
				$this->date_classes[$now_day][] = 'today';
			}
			if(empty($this->date_classes[$this->view_day]) || !in_array('currentlyViewing', $this->date_classes[$this->view_day]) )
			{
				$this->date_classes[$this->view_day][] = 'currentlyViewing';
			}
			
			$calendar_markup = '<div class="'.$this->wrapper_class.'">'."\n";
		
			// previous month link
			if(!empty($this->previous_month_query_string))
			{
				$calendar_markup .= '<a href="'.$this->previous_month_query_string.'" title="'.$this->get_previous_month_name().'" class="previous"><i class="fa fa-chevron-circle-left"></i></a>';
			}
			
			// month and year we are currently viewing
			$calendar_markup .='<'.$this->heading_tag.'>'.date('F Y', mktime(0,0,0,$this->view_month,1,$this->view_year)).'</'.$this->heading_tag.'>';
			
			// next month link
			if(!empty($this->next_month_query_string))
			{
				$calendar_markup .= '<a href="'.$this->next_month_query_string.'" title="'.$this->get_next_month_name().'" class="next"><i class="fa fa-chevron-circle-right"></i></a>';
			}
						
			//set up table (without data)
			$calendar_markup .= '<table summary="'.htmlspecialchars($this->table_summary).'">'."\n";
			$calendar_markup .= '<tr>'."\n";	
			foreach($this->weekdays as $weekday=>$abbreviation)
			{
				$calendar_markup .= '<th>'.$abbreviation.'</th>'."\n";
			}
			$calendar_markup .= '</tr>'."\n";
			
			//add data
			foreach($calendar_data as $week_num =>$week_values)
			{
				$calendar_markup .= '<tr>'."\n";
				foreach($this->weekdays as $weekday=>$abbreviation)
				{
					if(!empty($week_values[$weekday]))
					{
						$day = $week_values[$weekday];
						if(!empty($this->date_classes[$day]))
						{
							$calendar_markup .= '<td class="'.implode(' ',$this->date_classes[$day]).'">';
						}
						else
						{
							$calendar_markup .= '<td>';
						}
						if(!empty($this->linked_dates[$day]))
						{
							$link = $this->linked_dates[$day];
							$title = 'View '.date('j F Y', mktime(0,0,0,$this->view_month,$day,$this->view_year));
							$calendar_markup .= '<a href="'.$link.'" title="'.$title.'">'.$day.'</a>';
						}
						else
						{
							$calendar_markup .= $day;
						}
						$calendar_markup .= '</td>'."\n";
					}
					else 
					{
						$calendar_markup .= '<td></td>'."\n";
					}
				}
				$calendar_markup .= '</tr>'."\n";
			}
			$calendar_markup .= '</table>'."\n";
			$calendar_markup .= '</div>'."\n";
			
			return $calendar_markup;
		}
		
		function set_cal_month($cal_month)
		{
			if(!empty($cal_month))
			{
				$pieces = explode('-',$cal_month);
				$this->view_year = $pieces[0];
				$this->view_month = $pieces[1];
			}
		}
		
		function set_year($year)
		{
			$this->view_year = $year;
		}
		
		function set_month($month)
		{
			$this->view_month = $month;
		}
		
		function set_day($day)
		{
			$this->view_day = intval($day);
		}
		function set_view_date($date)
		{
			if(!empty($date))
			{
				$pieces = explode('-',$date);
				$this->set_year($pieces[0]);
				$this->set_month($pieces[1]);
				$this->set_day($pieces[2]);
			}
		}
		
		function set_linked_dates($linked_dates)
		{
			$this->linked_dates = array();
			foreach($linked_dates as $date=>$link)
			{
				$this->linked_dates[intval($date)] = $link;
			}
		}	
		
		function set_next_month_query_string($query_string)
		{
			$this->next_month_query_string = $query_string;
		}
		function set_previous_month_query_string($query_string)
		{
			$this->previous_month_query_string = $query_string;
		}
		
		function set_table_summary($table_summary)
		{
			$this->table_summary = $table_summary;
		}
		function set_heading_tag($tag_name)
		{
			$this->heading_tag = $tag_name;
		}
		function set_weekdays($weekdays)
		{
			$this->weekdays = $weekdays;
		}
		function set_wrapper_class($wrapper_class)
		{
			$this->wrapper_class = $wrapper_class;
		}
		function add_class_to_dates($class_name, $days)
		{
			foreach($days as $day)
			{
				if(empty($this->date_classes[$day]) || !in_array($class_name, $this->date_classes[$day]))
				{
					$this->date_classes[$day][] = $class_name;
				}
			}
		}
	}
?>
