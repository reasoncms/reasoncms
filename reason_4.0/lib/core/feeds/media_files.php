<?php
/**
 * @package reason
 * @subpackage feeds
 */

/**
 * Include dependencies & register feed with Reason
 */
include_once( 'reason_header.php' );
reason_include_once( 'feeds/default.php' );
reason_include_once( 'function_libraries/url_utils.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'mediaFileFeed';

class mediaFileFeed extends defaultFeed
{
	var $feed_class = 'mediaFileRSS';
	var $default_num_works = 15;
	var $_page;
	var $_page_check_state = 'unchecked';
	//var $page_types = array('av');
	function _get_page()
	{
		// note -- limiting to page only works for site-specific feeds to reduce spelunking
		if($this->site_specific && $this->_page_check_state === 'unchecked' && !empty($this->request['page_id']))
		{
			// using entity selector as easy way to enforce ownership rules & ensure acceptable state
			$es = new entity_selector($this->site->id());
			$es->add_type(id_of('minisite_page'));
			$es->add_relation('entity.id = "'.$this->request['page_id'].'"');
			$es->set_num(1);
			$pages = $es->run_one();
			if(!empty($pages))
			{
				$this->_page = current($pages);
				$this->_page_check_state = 'ok';
			}
			else
			{
				$this->_page_check_state = 'fail';
			}
		}
		return $this->_page;
	}
	function get_feed_title()
	{
		if($p = $this->_get_page())
		{
			$this->feed_title = $p->get_value('name');
			if($this->site_specific)
			{
				$this->feed_title .= ' :: ';
				$this->feed_title .= $this->site->get_value( 'name' ).' ';
			}
			$this->feed_title .= ' :: ';
			$this->feed_title .= $this->institution;
		}
		else
		{
			parent::get_feed_title();
		}
	}
	function get_feed_description()
	{
		if($p = $this->_get_page())
		{
			if($p->get_value('description'))
				$this->feed_description = $p->get_value('description');
		}
		if(empty($this->feed_description))
		{
			parent::get_feed_description();
		}
	}
	function get_site_link()
	{
		if($p = $this->_get_page())
		{
			$this->site_link = reason_get_page_url($p);
		}
		else
		{
			parent::get_site_link();
		}
	}
	function alter_feed()
	{
		if($p = $this->_get_page())
		{
			$this->feed->set_page_id($p->id());
			if($p->get_value('author') && valid_rss_author( $p->get_value('author') ))
			{
				$this->feed->set_channel_attribute( 'author', $p->get_value('author') );
			}
		}
		
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
		
		$ok_formats = array_keys(reason_get_valid_formats_for_podcasting());
		$ok_formats = array_map('addslashes',$ok_formats);
		// this is a hack because .mp4s should be included even if the media_format field is "invalid".
		$this->feed->es->add_relation('((av.media_format IN ("'.implode('","',$ok_formats).'")) OR (url.url LIKE "%.mp4"))');
		$this->feed->es->set_site(NULL);
		$this->feed->es->set_num(-1);

		if(!empty($this->request['num_works']))
		{
			$this->feed->set_num_works($this->request['num_works']);
		}
		else
		{
			$this->feed->set_num_works($this->default_num_works);
		}
		if($this->_page_check_state == 'fail')
		{
			$this->feed->nullify_items();
		}
	}
}

class mediaFileRSS extends ReasonRSS
{
	var $num_works = 15;
	var $_page_id;
	var $_nullify_items = false;
	function set_num_works( $num )
	{
		$this->num_works = $num;
	}
	function set_page_id( $id )
	{
		$this->_page_id = $id;
	}
	function nullify_items()
	{
		$this->_nullify_items = true;
	}
	function _build_rss() // {{{
	{
		if(!$this->_nullify_items)
		{
			$this->_get_av_items();
		}
		
		//pray($this->items);
		//echo $this->es->get_one_query();

		$this->_out = '<?xml version="1.0" encoding="UTF-8"?'.'>'."\n".'<rss version="2.0">'."\n".'<channel>'."\n\n";
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
	function _get_av_items()
	{
		$works_es = new entity_selector($this->site_id);
		$works_es->add_type( id_of('av') );
		$works_es->set_num( $this->num_works );
		$works_es->add_relation('show_hide.show_hide = "show"');
		$works_es->set_order('media_work.media_publication_datetime DESC');
		if(!empty($this->_page_id))
		{
			$works_es->add_right_relationship($this->_page_id, relationship_id_of('minisite_page_to_av'));
		}
		
		$media_works = $works_es->run_one();
		
		foreach($media_works as $work)
		{
			$es = carl_clone($this->es);
			$es->add_right_relationship($work->id(),relationship_id_of('av_to_av_file'));
			
			$media_files = $es->run_one();
			/* echo 'Files found: '.count($media_files)."\n";
			echo 'Query: '.$es->get_one_query()."\n"; */
			foreach($media_files as $media_file)
			{
				$media_file->set_value('work_publication_datetime',$work->get_value('media_publication_datetime'));
				$media_file->set_value('work_name',$work->get_value('name'));
				$media_file->set_value('work_description',$work->get_value('description'));
				$media_file->set_value('author',$work->get_value('author'));
				$this->items[$media_file->id()] = $media_file;
			}
		}
	}
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
		$valid_formats = reason_get_valid_formats_for_podcasting();
		if(array_key_exists($item->get_value('media_format'), $valid_formats))
		{
			$return[] = 'type="'.$valid_formats[$item->get_value('media_format')].'"';
		}
		else // this is a hack because .mp4s should be included even if the media_format field is "invalid".
		{
			$url = $item->get_value('url');
			if (!empty($url))
			{
				$ext = strtolower(substr($url, -4));
				if ($ext == '.mp4') $return[] = 'type="'.$valid_formats['Quicktime'].'"';
			}
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
	$valid_formats = reason_get_valid_formats_for_podcasting();
	$entity = new entity( $id );
	if(array_key_exists($entity->get_value('media_format'), $valid_formats))
	{
		return true;
	}
	else
	{
		// this is a hack because .mp4s should be included even if the media_format field is "invalid".
		$url = $entity->get_value('url');
		if (!empty($url))
		{
			$ext = strtolower(substr($url, -4));
			if ($ext == '.mp4') return true;
		}
	}
	return false;
}
/**
 * Get an array of formats considered acceptable to place in a podcast
 *
 * Keys are values in the Reason field media_format
 * Values are MIME types
 */
function reason_get_valid_formats_for_podcasting()
{
	return array('Quicktime'=>'video/quicktime','MP3'=>'audio/mpeg');
}
?>
