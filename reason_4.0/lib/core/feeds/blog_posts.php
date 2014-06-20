<?php
/**
 * This is the feed generator for posts on a publication
 *
 * @package reason
 * @subpackage feeds
 */

/**
 * Include dependencies & register feed with Reason
 */
include_once( 'reason_header.php' );
reason_include_once( 'feeds/page_tree.php' );
reason_include_once( 'classes/page_types.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'blogPostsFeed';


/** Functions that act as handlers for the rss feed code, only used by the blog_post feed. **/	

function get_blog_description_from_id($id)
{
	$post = new entity($id);
	return $post->get_value('description');
}

function get_blog_content_from_id($id)
{
	$post = new entity($id);
	if($post->get_value('content'))
	{
		return $post->get_value('content');
	}
	else
	{
		return get_blog_description_from_id($id);
	}
}

/**
 * Generates feed for a particular publication
 */
class blogPostsFeed extends pageTreeFeed
{
	var $query_string = 'story_id';
	var $blog; // entity
	var $module_sets = array('publication_item_display');
	
	function grab_blog()
	{
		if(empty($this->blog))
		{
			if($this->request['blog_id'])
			{
				$this->blog = new entity($this->request['blog_id']);
			}
			else
			{
				trigger_error('No publication id set on publication feed');
			}
		}
	}
	function get_feed_description()
	{
		$this->grab_blog();
		
		if($this->blog->get_value('description'))
		{
			$this->feed_description = $this->blog->get_value('description');
		}
		else
		{
			$this->feed_description = 'The latest posts from '.strip_tags($this->blog->get_value('name'));
		}
	}
	function get_feed_title()
	{
		$this->grab_blog();
		
		$this->feed_title = strip_tags($this->blog->get_value('name')).' :: '.$this->institution;
	}
	function get_site_link()
	{
		$this->grab_blog();
		
		if($this->site_specific)
		{
			$this->create_page_tree();
			$this->site_link = get_blog_page_link($this->site, $this->page_tree, $this->page_types, $this->blog);
			if(empty($this->site_link))
			{
				trigger_error('Unable to find a page for the blog "'.$this->blog->get_value('name').'" in the site "'.$this->site->get_value('name').'"');
			}
		}
		else
			$this->site_link = $this->home_url;
	}
	function alter_feed()
	{
		$this->grab_blog();
		
		$this->feed->set_item_field_map('title','release_title');
		$this->feed->set_item_field_map('author','author');
		// Set description to the blog post id so it can be passed to a handler
		$this->feed->set_item_field_map('description','id');
		$this->feed->set_item_field_map('pubDate','datetime');
		
		// Check for include content toggle
		if($this->blog->get_value('blog_feed_include_content')=='yes')
		{
			$this->feed->set_item_field_handler('description','get_blog_content_from_id',false);
		}
		else
		{
			$this->feed->set_item_field_handler('description','get_blog_description_from_id',false);
		}
		// Don't run this handler because it overwrites the content/description grab handlers
		//$this->feed->set_item_field_handler( 'description', 'expand_all_links_in_html', false );
		$this->feed->set_item_field_handler( 'title', 'strip_tags', false );
		
		$this->feed->es->add_relation( 'show_hide = "show"' );
		$this->feed->es->set_order( 'datetime DESC' );
		$this->feed->es->add_relation( 'status != "pending"' );
		
		// In order to be able to take advantage of query caching so we round up using 5 minute intervals when looking at the datetime.
		$this->feed->es->add_relation( 'datetime <= "' . get_mysql_datetime(ceil(time()/300)*300) . '"' );
		
		// lets add some sensible limits to avoid joining across all the tables (particularly chunk)
		$this->feed->es->limit_tables(array('entity', 'dated', 'status', 'show_hide'));
		$this->feed->es->limit_fields();
		
		if(!empty($this->request['shared_only']))
			$this->feed->es->add_relation( 'no_share = "0"' );
		
		if($this->blog->get_value('has_issues') == 'yes')
		{
			if($issue_id = $this->_get_latest_published_issue_id($this->blog->id()))
			{
				$this->feed->es->add_left_relationship( $issue_id , relationship_id_of( 'news_to_issue' ) );
				$this->feed->es->set_num(999); // show all posts in issue up to a reasonable number
			}
			else
			{
				$this->feed->es->add_relation('1 = 2'); // don't show any posts if there are no shown issues
			}
		}
		else
		{
			if($this->blog->get_value('posts_per_page'))
			{
				$this->feed->es->set_num($this->blog->get_value('posts_per_page'));
			}
		}
		
		$this->feed->es->add_left_relationship( $this->blog->id() , relationship_id_of( 'news_to_publication' ) );
	}
	function _get_latest_published_issue_id($blog_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('issue_type'));
		$es->add_left_relationship($blog_id, relationship_id_of('issue_to_publication'));
		$es->limit_tables(array('dated','show_hide'));
		$es->set_num(1);
		$es->set_order( 'datetime DESC' );
		$es->add_relation('show_hide.show_hide = "show"');
		$issues = $es->run_one();
		if(!empty($issues))
		{
			$issue = current($issues);
			return $issue->id();
		}
		return false;
	}
}

function get_blog_page_link( $site, $tree, $page_types, $blog ) // {{{
{
	$relations = array();
	$es = new entity_selector($site->id());
	$es->add_type( id_of( 'minisite_page' ) );
	
	$rpts =& get_reason_page_types();
	$ms =& reason_get_module_sets();
	$publication_modules = $ms->get('publication_item_display');
		
	foreach ($page_types as $page_type_name)
	{
		$pt = $rpts->get_page_type($page_type_name);
		$pt_props = $pt->get_properties();
		foreach ($pt_props as $region => $region_info)
		{
			if ( (in_array($region_info['module_name'], $publication_modules) && !(isset($region_info['module_params']['related_mode']) && ( ($region_info['module_params']['related_mode'] == "true") || ($region_info['module_params']['related_mode'] == true)))))
			{
				$valid_page_types[] = $page_type_name;
			}
		}
	}
	
	foreach($valid_page_types as $page_type)
	{
		$relations[] = 'page_node.custom_page = "'.$page_type.'"';
	}
	$es->add_relation( '('.implode(' or ', $relations).')' );
	$es->add_left_relationship( $blog->id(), relationship_id_of('page_to_publication') );
	$es->set_num( 1 );
	$pages = $es->run_one();
	
	if (!empty($pages))
	{
		$page = current($pages);
		return $tree->get_full_url($page->id(), true);
	}
	else
	{
		return false;
	}
}

?>
