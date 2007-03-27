<?php

	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'NewsViaCategoriesModule';
	
	class NewsViaCategoriesModule extends DefaultMinisiteModule
	{
		var $es;
		var $categories = array();
		var $news = array();
		var $news_page_title;
		var $news_page_link;
		var $news_cat_relevance = array();
		var $news_date_relevance = array();
		var $date_weight_factor = 28; // Number of days for a news item to be +1 in relevance
		var $relevance = array();
		var $limit = 3;

		function init( $args = array() ) // {{{
		{
			parent::init( $args );

			$this->es = new entity_selector( $this->parent->site_id );
			$this->es->description = 'Selecting categories for this page';
			$this->es->add_type( id_of('category_type') );
			$this->es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_category') );
			$this->es->set_env('site',$this->parent->site_id);
			$this->categories = $this->es->run_one();
			
			$now = time();
			$mysql_now = date('Y-m-d H:i:s');
			$date_weight_seconds = $this->date_weight_factor * 86400;
			
			if(!empty($this->categories))
			{
				foreach($this->categories as $cat)
				{
					$es = new entity_selector( $this->parent->site_id );
					$es->description = 'Selecting news items for '.$cat->get_value('name');
					$es->add_type( id_of('news') );
					$es->add_relation( 'dated.datetime <= "'.$mysql_now.'"' );
					$es->set_order('dated.datetime DESC');
					$es->add_left_relationship( $cat->id(), relationship_id_of('news_to_category') );
					$es->set_env('site',$this->parent->site_id);
					$es->set_num( $this->limit );
					$news_items = $es->run_one();
					foreach($news_items as $news_id=>$news_item)
					{
						if(empty($this->news[$news_id]))
							$this->news[$news_id] = $news_item;
						
						if(empty($this->news_cat_relevance[$news_id]))
							$this->news_cat_relevance[$news_id] = 1;
						else
							$this->news_cat_relevance[$news_id]++;
						
						if(empty($this->news_date_diffs[$news_id]))
						{
							$seconds = $now - prettify_mysql_datetime($news_item->get_value('datetime'), 'U');
							$days = $seconds / 86400;
							$this->news_date_relevance[$news_id] = 1 / (sqrt(.1*$days));
						}
					}
				}
				
				foreach($this->news_cat_relevance as $news_id=>$cat_rel)
				{
					$this->relevance[$news_id] = $cat_rel + $this->news_date_relevance[$news_id];
				}
				
				// divide each element in news_cat_relevance by the max value in the array.
				arsort($this->news_cat_relevance);
				reset($this->news_cat_relevance);
				$max_cat = current($this->news_cat_relevance);
				array_walk($this->news_cat_relevance, create_function('&$val, $key, $max_cat', '$val = $val / $max_cat;'), $max_cat);
				
				// make date relevance ceiling = 1
				array_walk($this->news_date_relevance, create_function('&$val, $key', 'if($val > 1) $val = 1;') );
				
				foreach($this->news_cat_relevance as $key=>$value)
				{
					$this->relevance[$key] = .5*($value + $this->news_date_relevance[$key]);
				}
				arsort($this->relevance);
				for($i = 0; $var = each($this->relevance); $i++)
				//while($var = each($this->relevance))
				{
					if ($i > $this->limit-1)
						unset($this->relevance[$var['key']]);
				}
			}
			
			
			
		} // }}}
		function get_news_page_link() // {{{
		{
			if(empty($this->news_page_link))
			{
				$es = new entity_selector($this->parent->pages->site_info->get_value('id'));
				$es->add_type( id_of( 'minisite_page' ) );
				$es->add_relation( '(page_node.custom_page = "news" or page_node.custom_page = "news_doc")' );
				$es->set_num( 1 );
				$newsPages = $es->run_one();
				if (!empty($newsPages))
				{
					$item = current($newsPages);
					$this->news_page_link = $this->parent->pages->get_full_url($item->id());
					$this->news_page_title = $item->get_value('name');
				}
				else
				{
					$this->get_parent_news_page_link();
				}
				
				if(empty($this->news_page_link))
					trigger_error('Neither this site nor its parent have a news page. Links will not work.');
			}
			return $this->news_page_link;
		} // }}}
		function get_parent_news_page_link()
		{
			$es = new entity_selector();
			$es->add_type( id_of( 'site' ) );
			$es->add_right_relationship( $this->parent->site_id, relationship_id_of( 'parent_site' ) );
			$tmp = $es->run_one();
			$parent_site = current( $tmp );
			
			$es = new entity_selector( $parent_site->id() );
			$es->add_type( id_of( 'minisite_page' ) );
			$es->add_relation( '(page_node.custom_page = "news" or page_node.custom_page = "news_doc")' );
			$es->set_num( 1 );
			$newsPages = $es->run_one();
			
			if(!empty($newsPages))
			{
				$pages = new $this->parent->nav_class;
				$pages->site_info = $parent_site;
				$pages->init( $parent_site->id(), id_of('minisite_page') );
			
				$item = current($newsPages);
				$this->news_page_link = $pages->get_full_url($item->id());
				$this->news_page_title = $item->get_value('name');
			}
			else
				trigger_error('Neither this site nor its parent have a news page. Links will not work.');
		}
		function has_content() // {{{
		{
			if( !empty($this->news) )
				return true;
			else
				return false;
		} // }}}
		function run() // {{{
		{
			echo '<div id="newsViaCats">'."\n";
			echo '<h3>Related Stories</h3>'."\n";
			echo '<ul>'."\n";
			foreach($this->relevance as $news_id=>$value)
			{
				$item = $this->news[$news_id];
				$es = new entity_selector();
				$es->add_type( id_of('image') );
				$es->add_right_relationship( $item->id(), relationship_id_of('news_to_image') );
				$es->set_env('site',$this->parent->site_id);
				$es->set_order('dated.datetime DESC');
				$es->set_num( 1 );
				$images = $es->run_one();
				$image = current($images);
				
				echo '<li>';
				if(!empty($image))
					show_image( $image, false, false, false, '', $this->parent->textonly );
				echo '<h4>'.$item->get_value('release_title').'</h4>';
				echo '<div>';
				$desc = strip_tags($item->get_value('description'), '<strong><em><a><span><ul><ol><li><abbr><acronym>');
				if(!empty($desc))
					echo $desc.' ';
				echo '<a href="'.$this->get_news_page_link().'?story_id='.$item->id().'">Read More</a>';
				echo '</div>';
				echo '<div class="clear"></div>';
				//echo '<div>Cat Rel: '.$this->news_cat_relevance[$item->id()].'</div>';
				//echo '<div>Date Rel: '.$this->news_date_relevance[$item->id()].'</div>';
				//echo '<div>Comb Rel: '.$value.'</div>';
				echo '</li>'."\n";
			}
			echo '</ul>'."\n";
			echo '</div>'."\n";
		} // }}}
	}
?>
