<?php
/**
 * @package reason
 * @subpackage minisite_templates
 */
	
	/**
	 * Include parent class; register module with Reason
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MinutesModule';
	reason_include_once( 'minisite_templates/modules/generic3.php' );

	/**
	 * A minisite module that displays minute entities on the current site
	 *
	 * Note: this module currently assumes that the dividing line between sets of minutes should be
	 * Sept. 1 (a common northern hemisphere academic year start date)
	 *
	 * @todo generalize so that the dividing line can be placed anywhere on the calendar (like Jan. 1, for example)
	 */
	class MinutesModule extends Generic3Module
	{
		var $prev_month;
		var $prev_year;
		var $type_unique_name = 'minutes_type';
		var $make_current_page_link_in_nav_when_on_item = true;
		var $back_link_text = 'Back to list of minutes';
		var $show_list_with_details = false;
		var $jump_to_item_if_only_one_result = false;
		
		function alter_es() // {{{
		{
			$type_id = id_of($this->type_unique_name);
			$this->es->set_order( table_of('datetime',$type_id).' DESC' );
			$this->es->add_relation( table_of('minutes_status',$type_id).' = "published"' );
		} // }}}
		function list_items() // {{{
		{
			echo '<ul class="minutesList">'."\n";
			foreach( $this->items AS $item )
			{
				$this->show_list_item( $item );
			}
			echo '</ul>'."\n";
		} // }}}
		function show_list_item( $item ) // {{{
		{
			$class_text = '';
			$month = prettify_mysql_datetime( $item->get_value( 'datetime' ), 'n' );
			$year = prettify_mysql_datetime( $item->get_value( 'datetime' ), 'Y' );
			settype($month, "integer");
			settype($year, "integer");
			$last_year_start = ($this->prev_month < 9 ) ? (($this->prev_year - 1 ) * 12) + 9 : ($this->prev_year * 12) + 9;
			if ((($year * 12) + $month) < $last_year_start)
				$class_text = ' class="yearEnd"';
			$this->prev_month = $month;
			$this->prev_year = $year;
			
			$link = '?item_id=' . $item->id();
			if (!empty($this->parent->textonly))
				$link .= '&amp;textonly=1';
			
			echo "\t".'<li'.$class_text.'><a href="' . $link . '">' . prettify_mysql_datetime( $item->get_value( 'datetime' ), 'F jS, Y' ) . '</a></li>'."\n";
		} // }}}
		function show_item_name( $item ) // {{{
		{
			echo '<h3 class="minutesHead">'.prettify_mysql_datetime( $item->get_value( 'datetime' ), 'F jS, Y' ).'</h3>'."\n";
		} // }}}
		function show_item_content( $item ) // {{{
		{
			$plural = '';
			$time = '';
			$time = prettify_mysql_datetime( $item->get_value( 'datetime' ), 'g:i a' );
			if ($item->get_value( 'location' ) || $time != '12:00 am' || $item->get_value( 'present_members' ) || $item->get_value( 'absent_members' ) || $item->get_value( 'guests' ) || $item->get_value( 'bigger_author' ) )
			{
				echo "\n".'<ul class="minutesInfo">'."\n";
				if ($item->get_value( 'location' ))
					echo '<li class="location"><em>Location:</em> '.$item->get_value( 'location' ).'</li>'."\n";
				if ($time != '12:00 am')
				{
					echo '<li class="time"><em>Time:</em> '.$time.'</li>'."\n";
				}
				if ($item->get_value( 'present_members' ))
					echo '<li class="present"><em>Present:</em> '.$item->get_value( 'present_members' ).'</li>'."\n";
				if ($item->get_value( 'absent_members' ))
					echo '<li class="absent"><em>Absent:</em> '.$item->get_value( 'absent_members' ).'</li>'."\n";
				if ($item->get_value( 'guests' ))
					echo '<li class="guests"><em>Guests:</em> '.$item->get_value( 'guests' ).'</li>'."\n";
					if ($item->get_value( 'bigger_author' ))
					echo '<li class="secretary"><em>Secretary:</em> '.$item->get_value( 'bigger_author' ).'</li>'."\n";
				if ($item->get_value( 'keywords' ))
					echo '<li class="keywords"><em>Keywords:</em> '.$item->get_value( 'keywords' ).'</li>'."\n";
				echo "\n".'</ul>'."\n";
			}
			if ($item->get_value( 'bigger_content' ))
			{
				$search = array('<h3>','</h3>');
				$replace = array('<h4>','</h4>');
				echo '<div class="minutesContent">'.str_replace($search, $replace, $item->get_value( 'bigger_content' )).'</div>'."\n";
			}
		} // }}}
		function show_back() // {{{
		{
			$link = '?';
			if (!empty($this->parent->textonly))
				$link .= 'textonly=1';
			echo '<p class="minutesBack"><a href="'.$link.'">View All Minutes for '.$this->parent->pages->site_info->get_value('name').'</a></p>';
		}
	}
?>
