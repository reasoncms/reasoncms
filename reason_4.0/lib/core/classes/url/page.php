<?php
/**
 * Reason Page URL class
 *
 * @package reason
 * @subpackage classes
 * @author Nathan White
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/url/abstract.php');
include_once(CARL_UTIL_INC . 'basic/url_funcs.php');

/**
 * Reason Page URL
 * 
 * A class to simplify getting correct URLs for reason pages, and help deprecate all the crazy URL functions.
 *
 * This class only cares about page id. Even if the site id is available we don't care. This is done because
 * ultimately ownership is going to be directly on the entity and will not require a lookup. By only dealing
 * with page_id we keep the API for the class simple.
 *
 * Usage:
 *
 * <code>
 * $reason_page_url = new reasonPageURL();
 * $reason_page_url->set_id(3347);
 * $url = $reason_page_url->get_url();
 * </code>
 *
 * One instance can be used for repeated operations - just change the page_id
 *
 * @todo modify page tree stuff to use this class
 * @todo make sure ports are properly handled
 *
 * @author Nathan White
 */
class reasonPageURL extends reasonURL
{
	/**
	 * Improves speed when lots of urls are being grabbed on a single site, slight hit otherwise
	 */
	var $find_children_of_parents_dynamically = false;
	
	/**
	 * Returns an absolute url with our best guess at the appropriate protocol
	 * 
	 * @return string absolute url with a "smart" protocol choice
	 */
	function get_url()
	{
		return (on_secure_page()) ? $this->get_url_most_secure() : $this->get_url_http();
	}
	
	/**
	 * @return string absolute url with the https protocol
	 */
	function get_url_https()
	{
		$page_id = $this->get_id();
		$page =& $this->_get_page($page_id);
		return ($page) ? 'https://' . $page->get_value('domain') . $page->get_value('relative_url') : NULL;
	}
	
	/**
	 * @return string absolute url with the http protocol
	 */
	function get_url_http()
	{
		$page_id = $this->get_id();
		$page =& $this->_get_page($page_id);
		return ($page) ? 'http://' . $page->get_value('domain') . $page->get_value('relative_url') : NULL;
	}
	
	/**
	 * Does the page we are linking to live on a domain where HTTPS_AVAILABLE is true?
	 */
	function get_url_most_secure()
	{
		$page_id = $this->get_id();
		$page =& $this->_get_page($page_id);
		if ($page)
		{
			if (isset($GLOBALS['_reason_domain_settings'][$page->get_value('domain')]['HTTPS_AVAILABLE'])) $secure = $GLOBALS['_reason_domain_settings'][$page->get_value('domain')]['HTTPS_AVAILABLE'];
			elseif (isset($GLOBALS['_default_domain_settings']['HTTPS_AVAILABLE'])) $secure = $GLOBALS['_default_domain_settings']['HTTPS_AVAILABLE'];
			else $secure = ($domain == REASON_HOST) ? HTTPS_AVAILABLE : false;
			return ($secure) ? $this->get_url_https() : $this->get_url_http();
		}
		else return NULL;
	}
	
	/**
	 * @return string relative url from the document root
	 */
	function get_relative_url()
	{
		$page_id = $this->get_id();
		$page =& $this->_get_page($page_id);
		$url = $page->get_value('relative_url');
		return ($page) ? $page->get_value('relative_url') : NULL;
	}
	
	/**
	 * Build a page entity with some custom values
	 *
	 * - relative_url
	 * - domain
	 *
	 */
	function _build_page($page_id)
	{
		$es = new entity_selector();
		$es->limit_tables('page_node');
		$es->add_type(id_of('minisite_page'));
		$es->add_relation("entity.id = " . $page_id);
		$page_result = $es->run_one();
		$page = ($page_result) ? reset($page_result) : false;
		if ($page) // lets populate our custom values
		{
			if ($parent_id = $this->get_parent_id($page_id))
			{
				if ($parent_id == $page_id) // if i'm my own parent i am a root and my relative_url is the site_url
				{
					$es = new entity_selector();
					$es->limit_tables('site');
					$es->add_type(id_of('site'));
					$es->add_left_relationship($page_id, $this->_get_site_owns_minisite_page_relationship_id());
					$site_result = $es->run_one();
					$site = ($site_result) ? reset($site_result) : false;
					if ($site)
					{
						$domain = ($site->has_value('domain') && $site->get_value('domain')) ? $site->get_value('domain') : REASON_HOST;
						$page->set_value('relative_url', str_replace("//", "/", $site->get_value('base_url')));
						$page->set_value('domain', $domain);
					}
					else
					{
						trigger_error('a site id could not be found for page_id ' . $page_id);
						return false;
					}
				}
				else
				{
					$parent =& $this->_get_page($parent_id);
					if ($parent)
					{
						$parent_path = $parent->get_value('relative_url');
						$page_path = $page->get_value('url_fragment');
						$page->set_value('relative_url', $parent_path . $page_path . '/');
						$page->set_value('domain', $parent->get_value('domain'));
					}
					else
					{
						trigger_error('the parent entity (should have id ' . $parent_id . ') could not be found for page_id ' . $page_id);
						return false;
					}
				}
			}
			else
			{
				trigger_error('a parent id could not be found for page id ' . $page_id);
				return false;
			}
		}
		else
		{
			trigger_error('a page with id ' . $page_id . ' could not be found in the reason database.');
			return false;
		}
		return $page;
	}
	
	/**
	 * Until Reason 4 Beta 9 when we have actual relationship unique names - we have to use relationship finder functions to get
	 * the correct owns relationship between the site and minisite_page.
	 *
	 * @todo zap me when relationship_id_of('site_to_minisite_page') works as it ought to
	 */
	function _get_site_owns_minisite_page_relationship_id()
	{
		static $rel_id;
		if (!isset($rel_id))
		{
			if (!reason_relationship_name_exists('site_owns_minisite_page'))
			{
				reason_include_once('function_libraries/relationship_finder.php');
				$rel_id = relationship_finder( 'site', 'minisite_page');
			}
			else $rel_id = relationship_id_of('site_owns_minisite_page');
		}
		return $rel_id;
	}
	
	/**
	 * We use a DBSelector to construct a super basic query to quickly get the parent.
	 *
	 * For efficiency sake, we could eliminate this if the parent was stored on the page entity and not across a relationship
	 */
	function get_parent_id($page_id)
	{
		static $page_to_parent;
		if (!isset($page_to_parent[$page_id]) || !$this->use_static_cache())
		{
			$d = new DBselector();
			$d->add_table( 'r', 'relationship' );
			$d->add_field( 'r', 'entity_b', 'parent_id' );
			$d->add_relation( 'r.type = ' . relationship_id_of('minisite_page_parent'));
			$d->add_relation( 'r.entity_a = ' . $page_id );
			$result = db_query( $d->get_query() , 'Error getting parent ID.' );
			if( $row = mysql_fetch_assoc($result))
			{
				$parent_id = $row['parent_id'];
				if ($this->find_children_of_parents_dynamically) // this makes the most sense when grabbing lots of urls on a single site
				{
					// lets find the children of the parent
					$d2 = new DBselector();
					$d2->add_table( 'r', 'relationship' );
					$d2->add_field( 'r', 'entity_a', 'page_id' );
					$d2->add_relation( 'r.type = ' . relationship_id_of('minisite_page_parent'));
					$d2->add_relation( 'r.entity_b = ' . $row['parent_id'] );
					$result2 = db_query( $d2->get_query() , 'Error getting child page ids of page '.$row['parent_id']);
					while ($row2 = mysql_fetch_assoc($result2))
					{
						$page_to_parent[$row2['page_id']] = $parent_id;
					}
				}
				else $page_to_parent[$page_id] = $parent_id;
			}
			else
			{
				$page_to_parent[$page_id] = false;
			}
		}
		return $page_to_parent[$page_id];
	}
	
	function &_get_page($page_id)
	{
		static $pages;
		if (!isset($pages[$page_id]) || !$this->use_static_cache())
		{
			$pages[$page_id] = $this->_build_page($page_id);
		}
		$page =& $pages[$page_id];
		return $page;
	}
}