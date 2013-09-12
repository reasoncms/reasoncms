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
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'content_listers/tree.php3' );
reason_include_once( 'minisite_templates/nav_classes/default.php' );
reason_include_once( 'classes/page_access.php' );
reason_include_once( 'classes/group_helper.php' );
reason_include_once('classes/media/factory.php');	


$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'mediaFileFeed';

class mediaFileFeed extends defaultFeed
{
	var $feed_class = 'mediaFileRSS';
	var $default_num_works = 15;
	var $page_limited_default_num_works = -1;
	var $_page;
	var $_page_check_state = 'unchecked';
	//var $page_types = array('av');
	
	
	function run($send_header = true)
	{
		$this->get_site_id();
		if($page = $this->_get_page())
		{ 
			if($groups = $this->get_page_restriction_groups($page,$this->site))
			{
				foreach($groups as $group)
				{
					$gh = new group_helper();
					$gh->set_group_by_entity($group);
					if($gh->requires_login())
					{
						$username = reason_require_http_authentication();
						if(!$gh->is_username_member_of_group($username))
						{
							$this->_send_unauthorized_output($send_header);
							die();
						}
					}
				}
			}
		}
		else
		{
			$pages = $this->get_sitewide_media_pages($this->site);
			if(!empty($pages))
			{
				$restricted_pages = array();
				$page_group_helpers = array();
				foreach($pages as $page)
				{
					if($groups = $this->get_page_restriction_groups($page,$this->site))
					{
						foreach($groups as $group)
						{
							$gh = new group_helper();
							$gh->set_group_by_entity($group);
							if($gh->requires_login())
							{
								$restricted_pages[$page->id()] = $page;
								if(!isset($page_group_helpers[$page->id()]))
									$page_group_helpers[$page->id()] = array();
								$page_group_helpers[$page->id()][] = $gh;
							}
						}
					}
				}
				if(count($restricted_pages) >= count($pages))
				{
					$username = reason_require_http_authentication();
					$access_ok = false;
					foreach($restricted_pages as $page)
					{
						$is_member = true;
						foreach($page_group_helpers[$page->id()] as $gh)
						{
							if(!$gh->is_username_member_of_group($username))
							{
								$is_member = false;
								break;
							}
						}
						if($is_member)
						{
							$access_ok = true;
							break;
						}
					}
					if(!$access_ok)
					{
						$this->_send_unauthorized_output($send_header);
						die();
					}
				}
			}
			else
			{
				$this->_send_unauthorized_output($send_header);
				die();
			}
		}
		parent::run($send_header);
	}
	function _send_unauthorized_output($send_header)
	{
		if($send_header)
			header('Content-type: text/xml');
		echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		echo '<rss version="2.0">'."\n";
		echo '<channel>'."\n";
		echo '<title>Unauthorized</title>'."\n";
		echo '</channel>'."\n";
		echo '</rss>'."\n";
		die();
	}
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
	function get_page_restriction_groups($page,$site)
	{
		$rpa = new reasonPageAccess();
		$page_tree = $this->get_page_tree($site);
		$rpa->set_page_tree($page_tree);
		return $rpa->get_groups($page->id());
	}
	function get_page_tree($site)
	{
		$pages = new MinisiteNavigation();
		$pages->site_info = $site;
		$pages->init( $this->site_id, id_of('minisite_page') );
		return $pages;
	}
	function get_sitewide_media_pages($site)
	{
		$pts = page_types_that_use_module(array('av','av_with_filters'));
		$ptq = array();
		foreach($pts as $pt)
		{
			if(isset($GLOBALS['_reason_page_types'][$pt]))
			{
				foreach($GLOBALS['_reason_page_types'][$pt] as $loc=>$mod)
				{
					if(is_array($mod) && isset($mod['limit_to_current_page']) && false == $mod['limit_to_current_page'])
						$ptq[] = addslashes($pt);
				}
			}
		}
		if(!empty($ptq))
		{
			$es = new entity_selector($this->site->id());
			$es->add_type(id_of('minisite_page'));
			$es->add_relation('custom_page IN("'.implode('","',$ptq).'")');
			return $es->run_one();
		}
		return NULL;
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
		if(empty($this->request['rel_sort']))
			$this->feed->set_item_field_map('pubDate','work_publication_datetime');
		else
			$this->feed->set_item_field_map('pubDate','');
		$this->feed->set_item_field_map('enclosure','id');
		$this->feed->set_item_field_map('description','work_description');
		$this->feed->set_item_field_handler( 'description', 'strip_tags', false );
		$this->feed->set_item_field_handler( 'title', 'make_title', true );
		
		// We're going to have to either improve the reason_rss class's handling of elements to do enclosures, or we're going to have to migrate to a general-purpose xml tool for it.
		// Enclosure example:
		// <enclosure url="http://www.scripting.com/mp3s/weatherReportSuite.mp3" length="12216320" type="audio/mpeg" />
		//$this->feed->set_item_field_validator( 'enclosure', 'validate_media_format_for_rss_enclosure' );
		$this->feed->es->add_relation('url.url != ""');
		$this->feed->es->add_relation('av.media_is_progressively_downloadable != "false"');
		$this->feed->es->set_order('av.av_part_number ASC');
		
		$ok_formats = array_keys(reason_get_valid_formats_for_podcasting());
		$ok_formats = array_map('addslashes',$ok_formats);
		// this is a hack because .mp4s should be included even if the media_format field is "invalid".
		$this->feed->es->add_relation('((av.media_format IN ("'.implode('","',$ok_formats).'")) OR (url.url LIKE "%.mp4") OR (mime_type IN("video/mp4","audio/mpeg")) )');
		$this->feed->es->set_site(NULL);
		$this->feed->es->set_num(-1);

		if(!empty($this->request['num_works']))
		{
			$this->feed->set_num_works($this->request['num_works']);
		}
		elseif(!empty($p))
		{
			$this->feed->set_num_works($this->page_limited_default_num_works);
		}
		else
		{
			$this->feed->set_num_works($this->default_num_works);
		}
		if(!empty($this->request['rel_sort']))
		{
			$this->feed->set_rel_sort(true);
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
	var $rel_sort = false;
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
	function set_rel_sort($val)
	{
		$this->rel_sort = $val;
	}
	function nullify_items()
	{
		$this->_nullify_items = true;
	}
	function _build_rss()
	{
		if(!$this->_nullify_items)
		{
			$this->_get_av_items();
		}

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
		
	}
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
			if($this->rel_sort)
			{
				$works_es->add_rel_sort_field($this->_page_id, relationship_id_of('minisite_page_to_av'));
				$works_es->set_order('rel_sort_order ASC');
			}
		}
		
		$media_works = $works_es->run_one();
		foreach($media_works as $work)
		{
			$es = new entity_selector();
			$es->add_type(id_of('av_file'));
			$es->add_right_relationship($work->id(),relationship_id_of('av_to_av_file'));
			if($work->get_value('integration_library'))
			{
				$es->set_num(1);
				if ($work->get_value('av_type') == 'Video')
				{
					$es->set_order('av.height DESC');
					$es->add_relation('av.mime_type = "video/mp4"');
				}
				elseif ($work->get_value('av_type') == 'Audio')
				{
					$es->add_relation('av.mime_type = "audio/mpeg"');
				}
			}
			$media_files = $es->run_one();
			if (!empty($media_files))
			{
				foreach($media_files as $media_file)
				{
					$media_file->set_value('work_publication_datetime',$work->get_value('media_publication_datetime'));
					$media_file->set_value('work_name',$work->get_value('name'));
					$media_file->set_value('work_description',$work->get_value('description'));
					$media_file->set_value('author',$work->get_value('author'));
					$media_file->set_value('integration_library',$work->get_value('integration_library'));
					$media_file->set_value('work_id',$work->id());
					$this->items[$media_file->id()] = $media_file;
				}
			}
		}
	}
	function make_enclosure($item, $attr, $value)
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
		$url = $this->get_media_file_url( $item );
		if ($url)
		{
			return '<'.$attr.' url="'.$url.'" length="'.$size.'" '.implode(' ',$additional_attrs).' />'."\n";
		}
		return '';
	}
	function get_media_file_url( $item )
	{
		$media_work = new entity($item->get_value('work_id'));
		$shim = MediaWorkFactory::shim($media_work->get_value('integration_library'));
		if ($shim)
		{
			return $shim->get_media_file_url($item, $media_work);
		}
		return false;
	}
	
	// This is meant to be overloaded so that the rss feed can get the appropriate attributes
	function get_additional_enclosure_arributes( $item )
	{
		$return = array();
		$valid_formats = reason_get_valid_formats_for_podcasting();
		if($item->get_value('mime_type'))
		{
			$return[] = 'type="'.$item->get_value('mime_type').'"';
		}
		elseif(array_key_exists($item->get_value('media_format'), $valid_formats))
		{
			$return[] = 'type="'.$valid_formats[$item->get_value('media_format')].'"';
		}
		else // this is a hack because .mp4s should be included even if the media_format field is "invalid".
		{
			$url = $item->get_value('url');
			if (!empty($url))
			{
				$ext = strtolower(substr($url, -4));
				if ($ext == '.mp4') $return[] = 'type="'.$valid_formats['MP4'].'"';
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
	return array('Quicktime'=>'video/quicktime','MP3'=>'audio/mpeg','MP4'=>'video/mp4');
}
?>
