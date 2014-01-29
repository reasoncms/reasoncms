<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Register the module with Reason and include the parent class
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'FAQModule';
	reason_include_once( 'minisite_templates/modules/generic3.php' );

	/**
	 * A minisite module that lists FAQs
	 *
	 * By default, shows all FAQs on the current site
	 */
	class FAQModule extends Generic3Module
	{
		var $type_unique_name = 'faq_type';
		var $style_string = 'faq';
		var $other_items = 'Other FAQs';
		var $query_string_frag = 'faq';
		var $use_filters = true;
		var $filter_types = array(	'category'=>array(	'type'=>'category_type',
														'relationship'=>'faq_to_category',
													),
								);
		var $search_fields = array('name','description','keywords','content','author');
		var $acceptable_params = array(
			'audiences'=>array(),
			'limit_to_current_site'=>true,
			'order_field' => 'last_modified',
			'order_direction' => 'DESC',
		);
		var $has_feed = true;
		var $feed_link_title = 'Subscribe to this feed for updates to this FAQ';
		var $make_current_page_link_in_nav_when_on_item = true;
		var $jump_to_item_if_only_one_result = false;
		
		function show_item_name( $item ) // {{{
		{
			echo '<h3 class="faqName">' . $item->get_value( 'description' ) . '</h3>'."\n";
		} // }}}
		function alter_es() // {{{
		{
			if (!$this->params['limit_to_current_site'])
			{
				$alias = $this->es->add_right_relationship_field('owns', 'entity', 'name', 'site_name');
				$order_string = $alias['site_name']['table'] . '.' . $alias['site_name']['field'] . ' ASC';
				if(!empty($this->params['order_field']) && !empty($this->params['order_direction']))
				{
					$table = get_table_from_field($this->params['order_field'], id_of('faq_type'));
					if($table)
					{
						if(strtoupper($this->params['order_direction']) == 'DESC')
							$order_direction = 'DESC';
						else
							$order_direction = 'ASC';
						$order_string .= ', `'.$table.'`.`'.$this->params['order_field'].'` '.$order_direction;
					}
					else
					{
						trigger_error('Table not found for order field '.$this->params['order_field']);
					}
				}
				$this->es->set_order( $order_string );
			}
			else
			{
				if(!empty($this->params['order_field']) && !empty($this->params['order_direction']))
				{
					$table = get_table_from_field($this->params['order_field'], id_of('faq_type'));
					if($table)
					{
						if(strtoupper($this->params['order_direction']) == 'DESC')
							$order_direction = 'DESC';
						else
							$order_direction = 'ASC';
						$order_string = '`'.$table.'`.`'.$this->params['order_field'].'` '.$order_direction;
						$this->es->set_order( $order_string );
					}
					else
					{
						trigger_error('Table not found for order field '.$this->params['order_field']);
					}
				}
			}
			if(!empty($this->params['audiences']))
			{
				$aud_ids = array();
				foreach($this->params['audiences'] as $audience)
				{
					$aud_id = id_of($audience);
					if($aud_id)
					{
						$aud_ids[] = $aud_id;
					}
					else
					{
						trigger_error($audience.' is not a unique name; skipping this audience');
					}
				}
				if(!empty($aud_ids))
				{
					$this->es->add_left_relationship($aud_ids, relationship_id_of('faq_to_audience'));
				}
			}
		} // }}}
	
		//
		function do_list()
		{
			if (!$this->params['limit_to_current_site'])
			{
				$site_indexed_items =& $this->get_items_by_site();
				echo '<h3 id="site_index">Site Index<h3>';
				echo '<ul>';
				foreach( $site_indexed_items as $site => $items )
				{
					$anchor[$site] = strtolower(htmlspecialchars(str_replace(" ", "_", $site)));
					echo '<li><a href="#'.$anchor[$site].'">'.$site.'</a></li>';
				}
				echo '</ul>';
				foreach( $site_indexed_items as $site => $items )
				{
					echo '<h4 id="'.$anchor[$site].'">'.$site.' (<a href="#site_index">back to index</a>)</h4> ';
					echo '<ul>'."\n";
					foreach( $items AS $item )
					{
						$this->show_list_item( $item );
					}
					echo '</ul>'."\n";
				}
			}
			else parent::do_list();
		}
		
		function &get_items_by_site()
		{
			foreach ($this->items as $item)
			{
				$item_id = $item->id();
				$site_name = $item->get_value('site_name');
				$site_indexed_items[$site_name][$item_id] =& $this->items[$item_id];
			}
			return $site_indexed_items;
		}

		function show_list_item_name( $item )
		{
			echo $item->get_value( 'description' );
		}
		
		function show_list_item_desc( $item )
		{
			if($item->get_value('content'))
			{
				$desc_array = explode(' ',strip_tags($item->get_value('content')));
				echo '<div>'.implode(' ', array_slice( $desc_array, 0, 15)).'...</div>';
			}
		}
		function show_item_content( $item ) // {{{
		{
			echo '<div class="answer">';
			echo $item->get_value( 'content' );
			$datetime = false;
			if($item->get_value( 'datetime' ) && $item->get_value( 'datetime' ) != '0000-00-00 00:00:00')
				$datetime = $item->get_value( 'datetime' );

			$owner = $item->get_owner();
			if($item->get_value('author') || $datetime || $item->get_value( 'keywords' ) || $owner->id() != $this->site_id)
			{
				echo '<ul class="meta">';
				if($item->get_value('author') || $datetime)
				{
					echo '<li>';
					if($item->get_value('author'))
					{
						echo $item->get_value('author');
						if($datetime)
							echo ', ';
					}
					if($datetime)
						echo prettify_mysql_datetime( $datetime, "j F Y" );
					echo '</li>'."\n";
				}
				if($owner->id() != $this->site_id)
				{
					$url = $owner->get_value('base_url');
					if($this->textonly)
						$url .= '?textonly=1';
					echo '<li>FAQ courtesy of <a href="'.$url.'">'.$owner->get_value('name').'</a></li>'."\n";
				}
				if($item->get_value( 'keywords' ))
					echo '<li class="hide">Keywords: '.strip_tags($item->get_value( 'keywords' )).'</li>'."\n";
				echo '</ul>'."\n";
			}
			echo '</div>'."\n";
		} // }}}
	}
?>
