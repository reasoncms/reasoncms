<?php
	/* Top features by category. mr, 9/23/2004 */

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'givingFrontPageModule';
	reason_include_once( 'minisite_templates/modules/default.php' );

	class givingFrontPageModule extends DefaultMinisiteModule
	{
		var $cat_news = array();
		var $category_unique_names = array('planned_giving_category','scholarships_category','buildings_category');
		
		function init ( $args = array() )	 // {{{
		{
			parent::init( $args );
			
			foreach($this->category_unique_names as $cat_name)
			{
				$es = new entity_selector( $this->parent->site_id );
				$es->description = 'Selecting news for '.$cat_name;
				$es->add_type( id_of('news') );
				$es->set_env('site', $this->parent->site_id);
				$es->add_left_relationship(id_of($cat_name), relationship_id_of('news_to_category'));
				if(!empty($this->cat_news))
				{
					foreach($this->cat_news as $news_item)
					{
						$es->add_relation('entity.id != "'.$news_item->id().'"');
					}
				}
				$items = $es->run_one();
				if(!empty($items))
				{
					$key = array_rand($items);
					$this->cat_news[$cat_name] = $items[$key];
				}
			}
		} // }}}
		function has_content() // {{{
		{
			if( empty($this->cat_news) )
			{
				return false;
			}
			else
				return true;
		} // }}}
		function run()
		{
			echo '<div id="givingFrontPageFeatures">'."\n";
			echo '<ul>';
			$link_base = $this->get_news_page_link();
			if(!empty($this->parent->textonly))
				$link_tail = '&amp;textonly=1';
			else
				$link_tail = '';
			$counter = 1;
			foreach($this->cat_news as $cat_name=>$news_item)
			{
				$link = $link_base.'?story_id='.$news_item->id().$link_tail;
				$category = new entity(id_of($cat_name));
				$image_ids = $news_item->get_left_relationship( relationship_id_of('news_to_image') );
				$link_title = 'Read more of &quot;'.str_replace('"',"'",$news_item->get_value('release_title')).'&quot;';
				echo '<li class="item'.$counter++.'">';
				if(!empty($image_ids))
				{
					echo '<div class="image">';
					echo '<a href="'.$link.'" title="'.$link_title.'">';
					show_image(current($image_ids), false, false, false, '', $this->parent->textonly);
					echo '</a>';
					echo '</div>';
				}
				echo strip_tags($news_item->get_value('description'));
				echo ' <a href="'.$link.'" title="'.$link_title.'">Full story</a>';
				echo '<div class="clear"></div>';
				echo '</li>';
			}
			echo '</ul>'."\n";
			echo '<div class="clear"></div>'."\n";
			echo '</div>'."\n";
		}
		function get_news_page_link() // {{{
		{
			$es = new entity_selector($this->parent->pages->site_info->get_value('id'));
			$es->add_type( id_of( 'minisite_page' ) );
			$es->add_relation( 'page_node.custom_page = "news"' );
			$es->set_num( 1 );
			$newsPage = $es->run_one();
			if ($newsPage) $item = current($newsPage);
			else $item = false;
			if ($item)
			{
				$link = $this->parent->pages->get_full_url($item->id());
				$this->news_page_title = $item->get_value('name');
			}
			else $link = false;
			return $link;
		}
	}
?>
