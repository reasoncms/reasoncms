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
 * with page_id we keep the API for the class simple. It is best to always just use get_url, which makes a
 * "best guess" about protocol.
 *
 * Basic Usage:
 *
 * <code>
 * $reason_page_url = new reasonPageURL();
 * $reason_page_url->set_id(3347);
 * $url = $reason_page_url->get_url();
 * </code>
 *
 * Advanced Usage:
 * 
 * There are some optimizations that can be applied for advanced users. Most notably, if you have a minisite page 
 * or a set of minisite pages already, you can provide them to the reasonPageURL class. If those entities have values 
 * for parent_id and/or owner_id preset, those values will be used for further optimization. See methods:
 *
 * - provide_page_entity
 * - provide_page_entities
 *
 * One instance of this class can and should be used for repeated operations - just change the page_id.
 *
 * One bit of oddness is how to handle pages that aren't really pages, but just containers for URLs. What we do 
 * is to just return the value in the url field if it is populated regardless of what get_url method is called.
 *
 * @todo modify page tree stuff to use this class
 * @todo make sure ports are properly handled
 *
 * @author Nathan White
 */
class reasonPageURL extends reasonURL
{
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
		$page =& $this->get_page($page_id);
		if ($page && $page->get_value('url')) return $page->get_value('url');
		return ($page) ? 'https://' . $page->get_value('domain') . $page->get_value('relative_url') : NULL;
	}
	
	/**
	 * @return string absolute url with the http protocol
	 */
	function get_url_http()
	{
		$page_id = $this->get_id();
		$page =& $this->get_page($page_id);
		if ($page && $page->get_value('url')) return $page->get_value('url');
		return ($page) ? 'http://' . $page->get_value('domain') . $page->get_value('relative_url') : NULL;
	}
	
	/**
	 * @return string absolute url with the most secure protocol supported by the domain
	 */
	function get_url_most_secure()
	{
		$page_id = $this->get_id();
		$page =& $this->get_page($page_id);
		if ($page)
		{
			if ($page->get_value('url')) return $page->get_value('url');
			if (isset($GLOBALS['_reason_domain_settings'][$page->get_value('domain')]['HTTPS_AVAILABLE'])) $secure = $GLOBALS['_reason_domain_settings'][$page->get_value('domain')]['HTTPS_AVAILABLE'];
			elseif (isset($GLOBALS['_default_domain_settings']['HTTPS_AVAILABLE'])) $secure = $GLOBALS['_default_domain_settings']['HTTPS_AVAILABLE'];
			else $secure = ($page->get_value('domain') == REASON_HOST) ? HTTPS_AVAILABLE : false;
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
		$page =& $this->get_page($page_id);
		if ($page && $page->get_value('url')) return $page->get_value('url');
		return ($page) ? $page->get_value('relative_url') : NULL;
	}
	
	/**
	 * You can provide entities to the class in order to speed things up.
	 *
	 * @param object minisite_page entity
	 * @return void
	 */
	function provide_page_entity(&$page)
	{
		$page_entities =& $this->_get_page_entities();
		if (!isset($page_entities[$page->id()])) $page_entities[$page->id()] = $page;
	}
	
	/**
	 * You can provide entities to the class in order to speed things up - this should rarely be necessary but might help in cases 
	 * where large batches of pages are selected with a single entity selector, and you want to get urls for those entities.
	 *
	 * @param array of minisite_page entity objects
	 * @return void
	 */
	function provide_page_entities(&$pages)
	{
		$page_entities =& $this->_get_page_entities();
		$page_entities = $pages;
	}

	/**
	 * Grab the page entity with URL information (builds it if necessary)
	 *
	 * @param int page_id
	 * @return object page entity with URL information
	 */
	function &get_page($page_id)
	{
		static $pages;
		if (!isset($pages[$page_id]) || !$this->use_static_cache())
		{
			$pages[$page_id] = $this->_build_page($page_id);
		}
		$page =& $pages[$page_id];
		return $page;
	}
	
	/**
	 * We use a DBSelector to construct a super basic query to quickly get the parent.
	 *
	 * This method probably ought to be a method directly on minisite page entities ... but alas we aren't there yet.
	 * For efficiency sake, we could eliminate this if the parent was stored on the page entity and not across a relationship
	 *
	 * @access private
	 * @param object minisite_page entity
	 * @return int parent entity id for a minisite page
	 */
	function _get_parent_id(&$page)
	{
		if (!$page->has_value('parent_id'))
		{
			$d = new DBselector();
			$d->add_table( 'r', 'relationship' );
			$d->add_field( 'r', 'entity_b', 'parent_id' );
			$d->add_relation( 'r.type = ' . relationship_id_of('minisite_page_parent'));
			$d->add_relation( 'r.entity_a = ' . $page->id() );
			$d->set_num(1);
			$result = db_query( $d->get_query() , 'Error getting parent ID.' );
			if( $row = mysql_fetch_assoc($result))
			{
				$page->set_value('parent_id', $row['parent_id']);
			}
			else
			{
				$page->set_value('parent_id', false);
			}
		}
		return $page->get_value('parent_id');
	}
	
	/**
	 * We use a DBSelector to construct a super basic query to quickly get the owner id.
	 *
	 * This method probably ought to be a method directly on minisite page entities ... but alas we aren't there yet.
	 * For efficiency sake, we could eliminate this if the owner was stored on the page entity and not across a relationship
	 *
	 * @access private
	 * @param object minisite_page entity
	 * @return int owner id for a minisite page
	 */
	function _get_owner_id(&$page)
	{
		if (!$page->has_value('owner_id'))
		{
			$d = new DBselector();
			$d->add_table( 'r', 'relationship' );
			$d->add_field( 'r', 'entity_a', 'owner_id' );
			$d->add_relation( 'r.type = ' . get_owns_relationship_id(id_of('minisite_page')));
			$d->add_relation( 'r.entity_b = ' . $page->id() );
			$d->set_num(1);
			$result = db_query( $d->get_query() , 'Error getting owner ID.' );
			if( $row = mysql_fetch_assoc($result))
			{
				$page->set_value('owner_id', $row['owner_id']);
			}
			else
			{
				$page->set_value('owner_id', false);
			}
		}
		return $page->get_value('owner_id');
	}
	
	/**
	 * Build a page entity with some custom values
	 *
	 * - relative_url
	 * - domain
	 * - parent_id
	 * - owner_id
	 *
	 * @access private
	 * @return object augmented page entity
	 */
	function _build_page($page_id)
	{
		$page =& $this->_get_page_entity($page_id);
		if ($page && !$page->get_value('url')) // lets populate our custom values
		{
			if ($parent_id = $this->_get_parent_id($page)) // this populates parent_id on the entity
			{
				if ($parent_id == $page_id) // if i'm my own parent i am a root and my relative_url is the site_url
				{
					if ($site_id = $this->_get_owner_id($page)) // this populates owner_id on the entity
					{
						$site =& $this->_get_site_entity($site_id);
						if ($site)
						{
							$domain = ($site->has_value('domain') && $site->get_value('domain')) ? $site->get_value('domain') : REASON_HOST;
							$page->set_value('relative_url', str_replace("//", "/", $site->get_value('base_url')));
							$page->set_value('domain', $domain);
						}
						else
						{
							trigger_error('a live owner site (should have id ' . $site_id . ') could not be found for page_id ' . $page_id);
							return false;
						}
					}
					else
					{
						trigger_error('an owner site could not be found for page_id ' . $page_id);
						return false;
					}
				}
				else
				{
					$parent =& $this->get_page($parent_id);
					if ($parent)
					{
						$parent_path = $parent->get_value('relative_url');
						$page_path = $parent_path . $page->get_value('url_fragment') . '/';
						$page->set_value('relative_url', str_replace("//", "/", $page_path));
						$page->set_value('domain', $parent->get_value('domain'));
					}
					else
					{
						trigger_error('a live parent entity (should have id ' . $parent_id . ') could not be found for page_id ' . $page_id);
						return false;
					}
				}
			}
			else
			{
				trigger_error('a parent page could not be found for page id ' . $page_id);
				return false;
			}
		}
		elseif ($page && $page->get_value('url'))
		{
			$page->set_value('relative_url', '');
			$page->set_value('domain', '');
		}
		else
		{
			trigger_error('a live page with id ' . $page_id . ' could not be found in the reason database.');
			return false;
		}
		return $page;
	}
	
	/**
	 * @access private
	 */
	function &_get_page_entity($page_id)
	{
		$page_entities =& $this->_get_page_entities();
		if (!isset($page_entities[$page_id]) || !$this->use_static_cache())
		{
			$es = new entity_selector();
			$es->limit_tables(array('page_node', 'url'));
			$es->add_type(id_of('minisite_page'));
			$es->add_relation("entity.id = " . $page_id);
			$es->set_num(1);
			$result = $es->run_one();
			$page_entities[$page_id] = ($result) ? reset($result) : false;
		}
		return $page_entities[$page_id];
	}

	/**
	 * @access private
	 */
	function &_get_page_entities()
	{
		static $entities;
		if (!isset($entities)) $entities = array();
		return $entities;
	}
	
	/**
	 * @access private
	 */
	function &_get_site_entity($site_id)
	{
		$site_entities =& $this->_get_site_entities();
		if (!isset($site_entities[$site_id]) || !$this->use_static_cache())
		{
			$es = new entity_selector();
			$es->limit_tables('site');
			$es->add_type(id_of('site'));
			$es->add_relation("entity.id = " . $site_id);
			$es->set_num(1);
			$result = $es->run_one();
			$site_entities[$site_id] = ($result) ? reset($result) : false;
		}
		return $site_entities[$site_id];
	}
	
	/**
	 * @access private
	 */
	function &_get_site_entities()
	{
		static $entities;
		if (!isset($entities)) $entities = array();
		return $entities;
	}
}