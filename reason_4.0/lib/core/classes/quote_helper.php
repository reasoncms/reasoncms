<?
include_once('reason_header.php');
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'classes/object_cache.php' );

/**
 * Quote helper
 *
 * Retrives unused quotes from the cache and database according to site_id, page_id, category_ids.
 *
 * Can be setup to exclude unavailable_quotes from the set of quotes returned.
 *
 * Utilized by the quote module and the quote_retrieval script.
 *
 * Sample usage example:
 *
 *  <code>
 *  	$qh = new QuoteHelper();
 *		$qh->set_site_id($site_id);
 *		$qh->set_page_id($page_id);
 *		$qh->init();
 *		$quote =& $qh->get_random_quote();	
 *  </code>
 *
 * @package reason
 * @subpackage classes
 *
 * @author Nathan white
 */

 class QuoteHelper
 {
	var $site_id;
	var $page_id;
	var $unavailable_quote_ids = array();
	var $ignore_categories = false;
	
	var $quote;
	var $quote_pool;
	
 	function QuoteHelper($site_id = NULL, $page_id = NULL, $unavailable_quote_ids = NULL)
 	{
 		if (isset($site_id)) $this->set_site_id($site_id);
 		if (isset($page_id)) $this->set_page_id($page_id);
 		if (isset($unavailable_quote_ids)) $this->set_unavailable_quote_ids($unavailable_quote_ids);
 	}
 	
 	function init()
 	{
 		$this->init_from_cache();
		if (empty($this->quote_pool) && ($this->quote_pool === false)) $this->init_from_database(); // there is no cache
 	}
 	
 	function init_from_cache()
	{
		$cache = new ReasonObjectCache($this->get_cache_id(), 86400);
		$this->quote_pool =& $cache->fetch();
	}
	
	function init_from_database()
	{
		if (!empty($this->site_id) && !empty($this->page_id))
 		{
 			$es = new entity_selector($this->site_id);
 			$es->add_type( id_of('quote_type') );
 			$es->limit_tables(array('meta'));
 			$es->limit_fields('meta.description');
 			$es->add_right_relationship( $this->page_id, relationship_id_of('page_to_quote') );
 			if (!$this->ignore_categories) $this->_limit_by_category($es);
 			$this->quote_pool = $es->run_one();
 			$this->set_cache();
 		}
 		else
 		{
 			trigger_error('The page_id and site_id must be available to determine category ids');
 		}
 	}
 	
 	function &get_quotes()
 	{
 		if (isset($this->quote_pool))
		{
			return $this->quote_pool;
		}
 		else
 		{
 			trigger_error('You must initialize the helper using the init() method before accessing quotes.', FATAL);
 		}
 	}
 	
 	function get_unavailable_quotes()
 	{
 		$quotes =& $this->get_quotes();
 		foreach ($this->unavailable_quote_ids as $id)
 		{
 			$unavailable[$id] =& $quotes[$id];
 		}
 		return (!empty($unavailable)) ? $unavailable : false;
 	}
 	
 	function get_available_quotes()
 	{
 		$quotes =& $this->get_quotes();
 		$available_ids = array_diff(array_keys($quotes), $this->unavailable_quote_ids);
 		foreach ($available_ids as $id)
 		{
 			$available[$id] =& $quotes[$id];
 		}
 		return (!empty($available)) ? $available : false;
 	}
 	
 	function &get_random_quote()
 	{
 		$quotes =& $this->get_quotes();
 		if (!empty($quotes))
 		{
 			$available_quotes = $this->get_available_quotes();
 			if ($available_quotes)
 			{
 				$id = array_rand($available_quotes);
 			}
 			else
 			{
 				$unavailable_quotes = $this->get_unavailable_quotes();
 				if (count($unavailable_quotes) > 1)
 				{
 					array_pop($unavailable_quotes);
 					$id = array_rand($unavailable_quotes);
 				}
 				else $id = array_rand($quotes);
 			}
 			$quote =& $quotes[$id];
 		}
 		else $quote = false;
 		return $quote;
 	}
  	
 	function set_site_id($site_id)
 	{
 		$this->site_id = $site_id;
 	}
 	
 	function set_page_id($page_id)
 	{
 		$this->page_id = $page_id;
 	}
 		
 	function set_categories_ids($cat_ids)
 	{
 		$this->category_ids = $cat_ids;
 	}
 	
 	function set_ignore_categories($boolean)
 	{
 		$this->ignore_categories = $boolean;
 	}

 	function set_unavailable_quote_ids($unavailable_quote_ids)
 	{
 		$this->unavailable_quote_ids = $unavailable_quote_ids;
 	}
		
	function set_cache()
	{
		$cache = new ReasonObjectCache($this->get_cache_id());
		$cache->set($this->quote_pool);
	}
		
	function get_cache_id()
	{
		return md5('quote_cache_site_' . $this->site_id . '_page_' . $this->page_id);
	}
	
	function _limit_by_category(&$es)
	{
		if (!isset($this->category_ids))
		{
			$cat_es = new entity_selector($this->site_id);
			$cat_es->add_type( id_of('category_type') );
			$cat_es->limit_tables();
			$cat_es->limit_fields();
			$cat_es->add_right_relationship ($this->page_id, relationship_id_of( 'page_to_category' ) );
			$cat_result = $cat_es->run_one();
			if (!empty($cat_result))
			{
				$this->set_category_ids = array_keys($cat_result);
			}
		}
		if (!empty($this->category_ids))
		{
			$es->add_left_relationship_field( 'quote_to_category', 'entity', 'id', 'cat_id', $this->category_ids);
		}
	}
 }
 
?>