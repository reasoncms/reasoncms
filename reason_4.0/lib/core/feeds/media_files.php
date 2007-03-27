<?php

include_once( 'reason_header.php' );
reason_include_once( 'feeds/default.php' );
reason_include_once( 'function_libraries/url_utils.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'mediaFileFeed';

class mediaFileFeed extends defaultFeed
{
	var $feed_class = 'mediaFileRSS';
	var $default_num_works = 15;
	//var $page_types = array('av');
	function alter_feed()
	{
		$this->feed->set_item_field_map('title','id');
		$this->feed->set_item_field_map('pubDate','work_publication_datetime');
		$this->feed->set_item_field_map('enclosure','id');
		$this->feed->set_item_field_map('description','work_description');
		$this->feed->set_item_field_handler( 'description', 'strip_tags', false );
		$this->feed->set_item_field_handler( 'title', 'make_title', true );
		
		// We're going to have to either improve the reason_rss class's handling of elements to do enclosures, or we're going to have to migrate to a general-purpose xml tool for it.
		// Enclosure example:
		// <enclosure url="http://www.scripting.com/mp3s/weatherReportSuite.mp3" length="12216320" type="audio/mpeg" />
		$this->feed->set_item_field_validator( 'enclosure', 'validate_media_format_for_rss_enclosure' );
		$this->feed->es->add_relation('url.url != ""');
		$this->feed->es->add_relation('av.media_is_progressively_downloadable != "false"');
		$this->feed->es->set_order('av.av_part_number ASC');
		$this->feed->es->set_num(-1);
		if(!empty($this->request['num_works']))
		{
			$this->feed->set_num_works($this->request['num_works']);
		}
		else
		{
			$this->feed->set_num_works($this->default_num_works);
		}
	}
}

class mediaFileRSS extends ReasonRSS
{
	var $num_works = 15;
	function set_num_works( $num )
	{
		$this->num_works = $num;
	}
	function _build_rss() // {{{
	{
		$works_es = new entity_selector($this->site_id);
		$works_es->add_type( id_of('av') );
		$works_es->set_num( $this->num_works );
		$works_es->add_relation('show_hide.show_hide = "show"');
		$works_es->set_order('media_work.media_publication_datetime DESC');
		$media_works = $works_es->run_one();
		
		foreach($media_works as $work)
		{
			$es = $this->es;
			$es->add_right_relationship($work->id(),relationship_id_of('av_to_av_file'));
			
			$media_files = $es->run_one();
			foreach($media_files as $media_file)
			{
				$media_file->set_value('work_publication_datetime',$work->get_value('media_publication_datetime'));
				$media_file->set_value('work_name',$work->get_value('name'));
				$media_file->set_value('work_description',$work->get_value('description'));
				$media_file->set_value('author',$work->get_value('author'));
				$this->items[$media_file->id()] = $media_file;
			}
		}
		//pray($this->items);
		//echo $this->es->get_one_query();

		$this->_out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<rss version="2.0">'."\n".'<channel>'."\n\n";
		foreach( $this->_channel_attr_values AS $attr => $value )
			$this->_out .= '<'.$attr.'>'.$this->_clean_value( $value ).'</'.$attr.'>'."\n";
		$this->_out .= "\n";
		
		if( !empty( $this->items ) )
		{
			foreach( $this->items AS $item )
			{
				$this->generate_item( $item );
			}
		}

		$this->_out .= '</channel>'."\n".'</rss>';
		
	} // }}}
	function make_enclosure($item, $attr, $value)
	{
		if($item->get_value('url'))
		{
			//$size = get_remote_filesize($item->get_value('url'));
			if($item->get_value('media_size_in_bytes'))
			{
				$size = $item->get_value('media_size_in_bytes');
			}
			else // guess wildly -- 5 megs?
			{
				$size = 5242880;
			}
			$additional_attrs = $this->get_additional_enclosure_arributes( $item );
			return '<'.$attr.' url="'.$item->get_value('url').'" length="'.$size.'" '.implode(' ',$additional_attrs).' />'."\n";
		}
		else
		{
			return '';
		}
	}
	// This is meant to be overloaded so that the rss feed can get the appropriate attributes
	function get_additional_enclosure_arributes( $item )
	{
		$return = array();
		$valid_formats = array('Quicktime'=>'video/quicktime','MP3'=>'audio/mpeg');
		if(array_key_exists($item->get_value('media_format'), $valid_formats))
		{
			$return[] = 'type="'.$valid_formats[$item->get_value('media_format')].'"';
		}
		return $return;
	}
	function make_title( $id )
	{
		$item = $this->items[$id];
		$title = $item->get_value('work_name');
		
		if($item->get_value('av_part_number') || $item->get_value('description'))
		{
			$title .= ' (';
			if($item->get_value('av_part_number'))
			{
				$title .= 'Part '.$item->get_value('av_part_number');
				if($item->get_value('av_part_total'))
				{
					$title .= ' of '.$item->get_value('av_part_total');
				}
			}
			if($item->get_value('description'))
			{
				if($item->get_value('av_part_number'))
				{
					$title .= ' – ';
				}
				$title .= $item->get_value('description');
			}
			$title .= ')';
		}
		return $title;
		
	}
}

function validate_media_format_for_rss_enclosure( $id )
{
	$valid_formats = array('Quicktime'=>'video/quicktime','MP3'=>'audio/mpeg');
	$entity = new entity( $id );
	if(array_key_exists($entity->get_value('media_format'), $valid_formats))
	{
		return true;
	}
	else
	{
		return false;
	}
}

?>
