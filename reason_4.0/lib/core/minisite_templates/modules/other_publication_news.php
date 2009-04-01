<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Register module with Reason and include dependencies
 */
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'OtherPublicationNewsModule';
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/object_cache.php' );
reason_include_once( 'function_libraries/util.php' );

/**
 * Displays the news items in a publication with links to the news items in another publication.
 *
 * For the sake of efficiency, this module is lightweight and does not use the publication framework.
 *
 * Supported parameters
 *
 * - cache_lifespan: controls how many seconds the cache lasts (set to 0 for no caching)
 * - publication_unique_name: sets the publication to use as a source for the news items
 * - max_num_to_show: if greater than 0, sets a maximum number of items to show
 * - title: if set, shows a custom module title
 * 
 * @author Nathan White
 */

class OtherPublicationNewsModule extends DefaultMinisiteModule
{
	var $news_items;
	var $publication_modules = array ('publication');	
	var $acceptable_params = array('cache_lifespan' => 0,
								   'publication_unique_name' => '',
								   'max_num_to_show' => 0,
								   'title'=>'',
								  );
	
	function init( $args = array() )
	{
		if ($this->params['cache_lifespan'] > 0)
		{
			$news_item_cache = new ReasonObjectCache($this->get_cache_id(), $this->params['cache_lifespan']);
			$this->news_items =& $news_item_cache->fetch();
			if (!$this->news_items) // nothing was fetched
			{
				$this->news_items =& $this->build_news_items($this->acceptable_params['publication_unique_name']);
				$news_item_cache->set($this->news_items);
			}
		}
		else
		{
			$this->news_items =& $this->build_news_items($this->acceptable_params['publication_unique_name']);
		}
	}
	
	/**
	 * Returns a reference to an array with data about ordered news items
	 */
	function &build_news_items($publication_unique_name = NULL)
	{
		$pub_id = $this->get_publication_id();
		if ($pub_id)
		{
			// The idea of this entity selector is to select all news items that are related to the publication
			// along with the news item owner and id and publication id(s) other than the named publication ...
			// The result should be news items along with a site entity id and publication id(s) - we'll use that
			// data to help build the link.
			
			$es = new entity_selector(); // try without site_id for now ... allows this to be used anywhere with a unique publication name
			$es->add_type(id_of('news'));
			$es->enable_multivalue_results();
			$es->limit_tables(array('press_release', 'dated'));
			$es->limit_fields(array('press_release.release_title', 'dated.datetime'));
			$es->add_left_relationship($pub_id, relationship_id_of('news_to_publication'));
			$alias = $es->add_left_relationship_field('news_to_publication', 'entity', 'id', 'pub_id');
			$es->add_relation($alias['pub_id']['table'] . '.' . $alias['pub_id']['field'] . " != " . $pub_id);
			$es->add_right_relationship_field('owns', 'entity', 'id', 'site_id');
			$es->set_order('dated.datetime DESC');
			$result = $es->run_one();
			
			if (!empty($result))
			{
				$result_keys = array_keys($result);
				foreach ($this->publication_modules as $module)
				{
					$valid_page_types = (isset($valid_page_types))
										? array_unique(array_merge($valid_page_types, page_types_that_use_module($module)))
										: page_types_that_use_module($module);
				}
				foreach (array_keys($valid_page_types) as $k) quote_walk($valid_page_types[$k], NULL);
				foreach ($result_keys as $key)
				{
					$success = $this->augment_entity($result[$key], $valid_page_types);
					if ($success)
					{
						$result[$key]->unset_value('pub_id');
						$result[$key]->unset_value('site_id');
					}
					else unset($result[$key]);
				}
			}
			$news_items =& $this->set_order_and_limits($result);
		}
		else
		{
			trigger_error('The module needs a publication unique name or a page associated with a publication to select borrowed news items');
			$news_items = array();
		}
		return $news_items;
	}
	
	function &set_order_and_limits(&$news_items)
	{
		$sorted_and_limited_news_items = array();
		$index = 0;
		$ids = array_keys($news_items);
		foreach ($ids as $k)
		{
			$index++;
			$source_name = $news_items[$k]->get_value('source_name');
			$sorted_and_limited_news_items[$source_name][$k] =& $news_items[$k];
			if ($index == $this->params['max_num_to_show']) break;
		}
		return $sorted_and_limited_news_items;
	}
	
	/**
	 * Accepts a news item by reference and adds the url and site name
	 *
	 */
	function augment_entity(&$news_item_entity, &$valid_page_types)
	{
		$site_id = $news_item_entity->get_value('site_id');
		$site = new entity($site_id);
		$site_unique_name = $site->get_value('unique_name');
		if (isset($this->augment_entity_handlers[$site_unique_name]))
		{
			$method_name = $this->augment_entity_handlers[$site_unique_name];
			return $this->$method_name($news_item_entity, $valid_page_types);
		}
		
		$pub_id = $news_item_entity->get_value('pub_id');
		$pub_id_array = (!is_array($pub_id)) ? array($pub_id) : $pub_id;
		
		$es = new entity_selector($site_id);
		$es->add_type(id_of('minisite_page'));
		$es->limit_tables(array('page_node'));
		$es->limit_fields(array('page_node.url_fragment'));
		$es->add_left_relationship($pub_id_array, relationship_id_of('page_to_publication'));
		$es->add_relation('page_node.custom_page IN ('.implode(",", $valid_page_types).')');
		$result = $es->run_one();
		
		if ($result)
		{
			$my_url = '';
			foreach ($result as $k=>$item)
			{
				$page_url = build_URL_from_entity($item);
				if (strlen($page_url) > strlen($my_url)) $my_url = $page_url;
			}
			$parameters['story_id'] = $news_item_entity->id();
			$news_item_entity->set_value('source_name', $site->get_value('name'));
			$news_item_entity->set_value('source_base_url', $site->get_value('base_url'));
			$news_item_entity->set_value('page_url', $my_url);
			$news_item_entity->set_value('parameters', $parameters);
			return true;
		}
		return false;
	}
	
	/**
	 * Returns publication id for the page in the most efficient way possible.
	 */
	function get_publication_id()
	{
		if (!isset($this->pub_id))
		{
			if ($this->params['publication_unique_name'])
			{
				$this->pub_id = id_of($this->params['publication_unique_name']);
			}
			else
			{
				$es = new entity_selector($this->site_id);
				$es->add_type(id_of('publication_type'));
				$es->limit_tables();
				$es->limit_fields();
				$es->add_right_relationship($this->page_id, relationship_id_of('page_to_publication'));
				$result = $es->run_one();
				if ($result)
				{
					$pub = current($result);
					$this->pub_id = $pub->id();
				}
				else $this->pub_id = '';
			}
		}
		return $this->pub_id;
	}
		
	function has_content()
	{
		return (!empty($this->news_items));
	}
	
	function run()
	{
		echo '<div class="newsItems">';
		$this->show_module_title();
		$this->show_news_listing();
		echo '</div>';
	}
	
	function show_module_title()
	{
		if(!empty($this->params['title']))
		{
			echo '<h3>'.$this->params['title'].'</h3>'."\n";	
		}
	}
	
	function show_news_listing()
	{
		echo '<div class="list">'."\n";
		echo '<ul>';
		foreach ($this->news_items as $source_name => $news_items)
		{
			echo '<li>';
			$this->show_news_item_source($source_name, $news_items);
			$this->show_news_items($news_items);
			echo '</li>';
		}
		echo '</ul>';
		echo '</div>'."\n";
	}
	
	function show_news_item_source($source_name, &$news_items)
	{
		$item = current($news_items); // each set has the same source_base_url for now
		$source_url = '//' . REASON_HOST . $item->get_value('source_base_url');
		if ($this->textonly) $source_url .= '?textonly=1';
		echo '<h4><a href="' . $source_url . '">'.$source_name.'</a></h4>';
	}
	
	function show_news_items(&$news_items)
	{
		echo '<ul>';
		foreach ($news_items as $news_item)
		{
			echo '<li>';
			$this->show_news_item($news_item);
			echo '</li>';
		}
		echo '</ul>';
	}
	
	function show_news_item(&$news_item)
	{
		$title = $news_item->get_value('release_title');
		$parameters = $news_item->get_value('parameters');
		$link = '//' . REASON_HOST . $news_item->get_value('page_url');
		
		if (!empty($parameters))
		{
			if ($this->textonly) $parameters['textonly'] = 1;
			foreach ($parameters as $k=>$v)
			{
				$param[$k] = $v;
			}
			$link .= '?' . implode_with_keys('&amp;',$param);
		}
		echo '<a href="'. $link . '">'.$title.'</a>';
	}
	
	function get_cache_id($site_id = '', $page_id = '')
	{
		$site_id = ($site_id) ? $site_id : $this->site_id;
		$page_id = ($page_id) ? $page_id : $this->page_id;
		return md5('other_publication_news_item_cache_' . $site_id . '_page_' . $page_id);
	}
	
	/**
	 * This method will clear the news item cache generated by this module for a site and page
	 * @todo implement something to call this
	 */
	function clear_cache($site_id = '', $page_id = '')
	{
		$site_id = ($site_id) ? $site_id : $this->site_id;
		$page_id = ($page_id) ? $page_id : $this->page_id;
		if ($site_id && $page_id)
		{
			$cache = new ReasonObjectCache($this->get_cache_id($site_id, $page_id));
			$cache->clear();
		}
		else trigger_error('clear_cache needs a site_id and page_id');	
	}	
}
?>
