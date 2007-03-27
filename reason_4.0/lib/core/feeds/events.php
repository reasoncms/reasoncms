<?php

include_once( 'reason_header.php' );
reason_include_once( 'feeds/page_tree.php' );
reason_include_once( 'classes/calendar.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'eventsFeed';

class eventsFeed extends pageTreeFeed
{
	var $home_url = REASON_PRIMARY_EVENTS_PAGE_URI;
	var $page_types = array('events','events_verbose','events_nonav','events_academic_calendar','event_registration','events_verbose_nonav','event_slot_registration',);
	var $query_string = 'event_id';
	var $feed_class = 'eventsRSS';
	
	function alter_feed()
	{
		$this->feed->set_item_field_map('title','id');
		$this->feed->set_item_field_map('description','id');
		$this->feed->set_item_field_map('pubDate','');
		
		if(!empty($this->request['category_id']))
		{
			$this->feed->set_category_id($this->request['category_id']);
		}
		
		$this->feed->set_item_field_handler( 'description', 'make_description', true );
		$this->feed->set_item_field_handler( 'title', 'make_title', true );
	}
}
class eventsRSS extends pageTreeRSS
{
	var $calendar;
	var $categories;
	function eventsRSS( $site_id, $type_id = '', $category_id = '' )
	{
		$this->init( $site_id, $type_id );
	}
	function set_category_id($cat_id)
	{
		$this->categories[$cat_id] = new entity($cat_id);
	}
	function _build_rss() // {{{
		{
			$cal_init = array();
			if(!empty($this->site_id))
			{
				$this->site = new entity($this->site_id);
				$cal_init['site'] = $this->site;
			}
			if(!empty($this->categories))
			{
				$cal_init['categories'] = $this->categories;
			}
			$this->calendar = new reasonCalendar($cal_init);
		
			$this->calendar->run();
			$this->items = $this->calendar->get_all_events();
			$this->events_by_date = $this->calendar->get_all_days();

			$this->_out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<rss version="2.0">'."\n".'<channel>'."\n\n";
			foreach( $this->_channel_attr_values AS $attr => $value )
				$this->_out .= '<'.$attr.'>'.$this->_clean_value( $value ).'</'.$attr.'>'."\n";
			$this->_out .= "\n";
			
			if( !empty( $this->events_by_date ) )
			{
				foreach( $this->events_by_date AS $date=>$item_ids )
				{
					foreach( $item_ids as $item_id )
					{
						
						$this->generate_item( $item_id, $date );
					}
				}
			}

			$this->_out .= '</channel>'."\n".'</rss>';
			
		} // }}}
		function generate_item( $item_id, $date )
		{
			$this->_out .= ('<item>'."\n");
			foreach( $this->_item_field_map AS $attr => $field )
			{						// grab a handler if one is set
				if( !empty( $this->_item_field_handlers[ $attr ] ) )
					$handler = $this->_item_field_handlers[ $attr ];
				else
					$handler = '';

				$value = (!empty($field)) ? $this->items[$item_id]->get_value( $field ) : '';
				if(!empty($value))
				{

					// run the handler if it is set
					if( !empty( $handler ) )
					{
						if(!empty($this->_item_field_handler_rules[ $attr ]['class_variable']))
							$value = $this->$handler( $value, $date );
						else
							$value = $handler( $value, $date );
					}

					// load field validator if one was set
					if( !empty( $this->_item_field_validator[ $attr ] ) )
						$validate_func = $this->_item_field_validator[ $attr ];
					else
						$validate_func = '';

					// run field validator if set, otherwise, set valid to true
					if( !empty( $validate_func ) )
						$valid = $validate_func( $value );
					else
						$valid = true;

					// show the field if a value is set
					if( !empty( $value ) )
					{
						// make sure value is also valid
						if( $valid )
						{
							$this->_out .= '<'.$attr.'>';
							// call a field handler if one is set up
							$this->_out .= $this->_clean_value( $value );
							$this->_out .= '</'.$attr.'>'."\n";
						}
						/* else
							trigger_error('RSS Field "'.$attr.'" (Reason: "'.$field.'") was invalid by function "'.$validate_func.'".  Value was "'.$value.'"', WARNING );	*/
					}
					// This was annoying. I'm turning it off for debuging purposes. --MR
					/* else
						trigger_error( 'RSS Field "'.$attr.'" (Reason: "'.$field.'") was empty', WARNING ); */
				}
			}
			$this->_out .= ('</item>'."\n\n");
		}
		function make_description( $id, $date )
		{
			$ret = '';
			if($this->items[$id]->get_value('location'))
				$ret .= $this->items[$id]->get_value('location');
			if(substr($this->items[$id]->get_value( 'datetime' ), 11) != '00:00:00')
			{
				if(!empty($ret))
					$ret .= ', ';
				$ret .= prettify_mysql_datetime( $this->items[$id]->get_value( 'datetime' ), 'g:i a' );
			}
			return $ret;
		}
		function make_title( $id, $date )
		{
			$ret = prettify_mysql_datetime( $date, 'F j' ).' - ';
			$ret .= $this->items[$id]->get_value( 'name' );
			return $ret;
		}
		function site_specific_item_link( $item_id, $date )
		{
			return $this->get_channel_attr( 'link' ).'?'.$this->query_string.'='.$item_id.'&date='.$date;
		}

		function non_site_specific_item_link( $item_id )
		{
			$this->page_type_id = id_of('minisite_page');
 	              	$owner = $this->items[ $item_id ]->get_owner();
                	if(empty( $this->trees[ $owner->id() ] ) )
                	{
                        	$this->trees[ $owner->id() ] = new minisiteNavigation();
                        	$this->trees[ $owner->id() ]->site_info = $owner;
                        	$this->trees[ $owner->id() ]->init( $owner->id(), $this->page_type_id );
                	}

                	if(empty($this->pages[ $owner->id() ]))
                	{
                        	$this->pages[ $owner->id() ] = get_page_link( $owner, $this->trees[ $owner->id() ], $this->page_types, true );
                	}

	                return $this->pages[ $owner->id() ].'?'.$this->query_string.'='.$item_id;
        	}
}

?>
