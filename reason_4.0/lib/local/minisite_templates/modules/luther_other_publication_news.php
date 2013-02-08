<?php
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherOtherPublicationNewsModule';
reason_include_once( 'minisite_templates/modules/other_publication_news.php' );

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
 * @package reason_local
 * @subpackage minisite_modules
 * 
 * @author Nathan White
 */

class LutherOtherPublicationNewsModule extends OtherPublicationNewsModule
{	
	/**
	 * Returns a reference to an array with data about ordered news items
	 */
	
	function &set_order_and_limits(&$news_items)
	{
		$index = 0;
		$sorted_and_limited_news_items = array();
		
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home')
		// special treatment on home page. Need to put featured posts at top
		// then shuffle remaining entries
		{
			$ids = array_keys($news_items);
			$source_name = 'news';
			$featured_ids = $this->get_featured_ids();
			shuffle($ids);
			$ids = array_unique(array_merge($featured_ids, $ids));
			foreach ($ids as $k)
			{
				$index++;
				//$source_name = $news_items[$k]->get_value('source_name');
				$sorted_and_limited_news_items[$source_name][$k] =& $news_items[$k];
				if ($index == $this->params['max_num_to_show']) break;	
			}
		}
		else
		{
			$featured_ids = $this->_get_featured_news_item_ids($this->get_publication_id());
			foreach( $featured_ids as $featured_id)
			{
				if(isset($news_items[$featured_id]))
				{
					$source_name = $news_items[$featured_id]->get_value('source_name');
					$sorted_and_limited_news_items[$source_name][$featured_id] =& $news_items[$featured_id];
					$index++;
				}
			}
			
			$ids = array_keys($news_items);
			foreach ($ids as $k)
			{
				if ($this->params['max_num_to_show'] != 0 && $index > $this->params['max_num_to_show']) break;
				if(in_array($k,$featured_ids))
					continue;
				$index++;
				$source_name = $news_items[$k]->get_value('source_name');
				$sorted_and_limited_news_items[$source_name][$k] =& $news_items[$k];
			}
		}
		
		return $sorted_and_limited_news_items;
	}

        // custom method
        function get_featured_ids()
        {
          //$es = new entity_selector($this->site_id);  // removed $this->site_id: featured news items were not showing on borrowed publications
          $es = new entity_selector();
          $es->add_type(id_of('news'));		
          $es->add_right_relationship($this->get_publication_id(), relationship_id_of('publication_to_featured_post'));
          //$es->add_rel_sort_field($this);
          $es->add_rel_sort_field($this->get_publication_id(), relationship_id_of('publication_to_featured_post'));
		  $es->set_order('relationship.rel_sort_order ASC');
          $result = $es->run_one();
          //pray($result);
          return ($result) ? array_keys($result) : array();
        }   
	
	function run()
	{
		echo '<div class="newsItems">';
		$this->show_module_title();
		$this->show_news_listing();
		echo '</div>';
	}
	
	
	function show_news_listing()
	{
		if (get_theme($this->site_id)->get_value('name') == 'luther2010')
		{
			echo '<ul class="hfeed">'."\n";	
			foreach ($this->news_items as $source_name => $news_items)
			{
				$this->show_news_items($news_items);	
			}
			echo '</ul>'."\n";
			
			echo '<nav class="button view-all">'."\n";
			echo '<ul>'."\n";
			echo '<li><a href="/headlines">View all news &gt;</a></li>'."\n";
			echo '</ul>'."\n";
			echo '</nav>'."\n";
		}
		else
		{
			echo '<div id="headline-list">'."\n";
			foreach ($this->news_items as $source_name => $news_items)
			{
				//$this->show_news_item_source($source_name, $news_items);
				$this->show_news_items($news_items);
			}
			echo '</div> <!-- id="headline-list" -->'."\n";
		}
	}
	
	function show_news_item_source($source_name, &$news_items)
	{
		$item = current($news_items); // each set has the same source_base_url for now
		//$source_url = '//' . REASON_HOST . $item->get_value('source_base_url');
		$source_url =  $item->get_value('source_base_url');
		if ($this->textonly) $source_url .= '?textonly=1';
		echo '<h4><a href="' . $source_url . '">'.$source_name.'</a></h4>';
	}
	
	function show_news_items(&$news_items)
	{
		if (get_theme($this->site_id)->get_value('name') == 'luther2010')
		{
			foreach ($news_items as $news_item)
			{
				echo '<li>';
				echo '<article role="article">'."\n";
				$this->show_news_item($news_item);
				echo '</article>'."\n";	
				echo '</li>';
			}
		}
		else
		{

			foreach ($news_items as $news_item)
			{
				echo '<p>';
				$this->show_news_item($news_item);
				echo '</p>';
			}
		}
	}
	
	function show_news_item(&$news_item)
	{
		$title = $news_item->get_value('release_title');
		$parameters = $news_item->get_value('parameters');

		//$link = '//' . REASON_HOST . $news_item->get_value('page_url');
		$link =  $news_item->get_value('page_url');

		$link = $news_item->get_value('page_url');
		if (!empty($parameters))
		{
			if ($this->textonly) $parameters['textonly'] = 1;
			foreach ($parameters as $k=>$v)
			{
				$param[$k] = $v;
			}
			$link .= '?' . implode_with_keys('&amp;',$param);
		}
		$link = preg_replace("|http(s)?:\/\/\w+\.\w+\.\w+|", "", $link);
		
		if (get_theme($this->site_id)->get_value('name') == 'luther2010')
		{
			echo '<a href="'. $link . '" title="Read more...">'."\n";
			echo '<div><header><h1 class="entry-title">'. $title. '</h1></header></div></a>'."\n";
		}
		else
		{
			echo '<a href="'. $link . '">'.$title.'</a>';
		}
	}
	
}
?>
