<?php

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'Gallery2Module';
reason_include_once( 'minisite_templates/modules/generic3.php' );

/**
 * undocumented class
 *
 * @package Reason_Core
 * @subpackage Minisite_Module
 *
 * @author Ben Cochran
 **/
class Gallery2Module extends Generic3Module
{
	var $type_unique_name = 'image';
	var $use_pagination = true;
	var $num_per_page = 12;
	var $show_list_with_details = false;

	/**
	 * The string used as the id of the module
	 * This might be better termed 'module_id'
	 * @var string
	 */	
	var $style_string = 'imageGallery';
	
	/**
	 * The string used to denote the item in the query string
	 * '_id' added to this string to build actual query key
	 * @var string
	 */	
	var $query_string_frag = 'image';
	
	/**
	 * The header used above the list of items when they are displayed below an item detail
	 * @var string
	 */	
	var $other_items = 'Other Images';
	
	/**
	 * The plural word used throughout the module to refer to more than one item
	 * @var string
	 */	
	var $plural_type_name = 'images';
	
	// Filter stuff
	var $use_filters = true;	
	var $search_fields = array('chunk.content','meta.description','meta.keywords','chunk.author');
	var $allowable_psearch_fields = array(
								'date' => array(
									'field' => 'dated.datetime',
									'cleanup_rule' => array('function' => 'turn_into_string'),
								)
							);
//	var $jump_to_item_if_only_one_result = false;
	
	var $next_arrow_url;
	var $prev_arrow_url;
	
	// Used to hide the "Back to list" link.
	var $only_one_item = false;
	
	var $base_params = array(
		'limit_to_current_site'=>true,
		'filter_displayer'=>'gallery_specific.php',
		'pagination_displayer'=>'window.php',
		'use_pagination'=>true,
		'entire_site'=>false,
		'use_relationship_sort' => false,
		'sort_order' => 'dated.datetime ASC, meta.description ASC, entity.id ASC',
		'show_dates_in_list' => false,
		'show_descriptions_in_list' => true,
		'date_format' => 'j F Y',
		'min_num_to_show_search' => 20,
	);
	
	var $sorting_by_rel_sort_order = false;
	
	var $sort_order_array = array();
	
	var $image_array = array();
	
	var $rel_sort_values = array();
	var $rel_sort_values_queried = array();
	
	var $use_desc_in_list = true;
	
	function additional_init_actions()
	{
		$this->prev_arrow_url = REASON_HTTP_BASE_PATH.'css/gallery2/image_gallery_arrow_prev.gif';
		$this->next_arrow_url = REASON_HTTP_BASE_PATH.'css/gallery2/image_gallery_arrow_next.gif';
		$this->parent->add_stylesheet( REASON_HTTP_BASE_PATH.'css/gallery2/gallery2.css', '', true );
		$this->parent->add_head_item('script',array( 'language' => 'JavaScript', 'type' => 'text/javaScript',  'src' => REASON_HTTP_BASE_PATH.'js/gallery2/next_page_link.js'));
		$this->use_pagination = ($this->params['use_pagination']) ? true : false;
		//$this->use_pagination = $this->params['use_pagination'];
		$this->use_dates_in_list = ($this->params['show_dates_in_list']) ? true : false;
		$this->use_desc_in_list = ($this->params['show_descriptions_in_list']) ? true : false;
		$this->date_format = $this->params['date_format'];
		$this->min_num_to_show_search = $this->params['min_num_to_show_search'];
		
		// Since rel_sort doesn't make sense on a site-wide gallery, we need to keep this from happening
		if (empty($this->params['sort_order']) || ($this->params['sort_order'] == 'rel' && $this->params['entire_site']))
		{
			$sort_order_string = 'dated.datetime ASC, entity.id ASC';
		}
		elseif ($this->params['sort_order'] == 'rel')
		{
			$this->sorting_by_rel_sort_order = true;
		}
		else
		{
			$sort_order_string = $this->params['sort_order'];
		}

		if (!$this->sorting_by_rel_sort_order)
		{
			// If we use regular expressions to split the sort order string,
			// we'll be more likely to get real sort orders
			$pattern = '/\b[A-Za-z]+\.[A-Za-z]+\b \b(?:ASC|DESC)\b/';//(?:,(?: )?)?/';
			//echo $pattern .'<hr />';
			//echo $sort_order_string;
			//preg_match($pattern, substr($subject,3), $matches, PREG_OFFSET_CAPTURE);
			while (empty($matches))
			{
				preg_match_all($pattern, $sort_order_string, $matches);
				$sort_order_string = 'dated.datetime ASC, entity.id ASC';
			}

//		pray(reset($matches));
//		if (empty($matches))
			
//		if ($sort_order_string != 'rel')

			//$sort_orders = split(',',$this->params['sort_order']);
//			foreach ($sort_orders as $order)
			foreach (reset($matches) as $order)
			{
				$order = trim($order);
				$table_field_and_direction = explode(' ',$order);
				
				$table_and_field = $table_field_and_direction[0];
				
				$this->sort_order_array[$order]['table_and_field'] = $table_and_field;
				
				
				$table_and_field_parts = explode('.',$table_and_field);
				$direction = strtoupper($table_field_and_direction[1]);
				$this->sort_order_array[$order]['table'] = $table_and_field_parts[0];
				$this->sort_order_array[$order]['field'] = $table_and_field_parts[1];
				
				if ($direction == 'ASC')
				{
					$this->sort_order_array[$order]['direction'] = 'ASC';
					$this->sort_order_array[$order]['chevron'] = '>';
					$this->sort_order_array[$order]['opposite_direction'] = 'DESC';
					$this->sort_order_array[$order]['opposite_chevron'] = '<';
				}
				else
				{
					
					$this->sort_order_array[$order]['direction'] = 'DESC';
					$this->sort_order_array[$order]['chevron'] = '<';
					$this->sort_order_array[$order]['opposite_direction'] = 'ASC';
					$this->sort_order_array[$order]['opposite_chevron'] = '>';					
				}
			}
		}
	}
	
	function post_es_additional_init_actions()
	{
		foreach ($this->items as $image)
		{
			$this->image_array[$image->id()] = $this->get_image_data($image);
		}
		
		
		$largest_width = 0;
		$largest_height = 0;
		foreach ($this->image_array as $image)
		{
			if (!empty($image['thumb']))
				if ($image['thumb']['width'] > $largest_width)
					$largest_width = $image['thumb']['width'];

		}
		
		if ($largest_width == 0) $largest_width = 125;

		$largest_width_with_padding = $largest_width + 20;
		$largest_height = 0;
		$largest_height_with_text = 0;
		foreach ($this->image_array as $image)
		{
			if (empty($image['thumb']))
				$temp_height = 0;
			else
				$temp_height = $image['thumb']['height'];
			
			if ($temp_height > $largest_height)
				$largest_height = $temp_height;
			
			if ($this->use_desc_in_list)
			{
				$desc_length = strlen($image['description'])*13/($largest_width_with_padding/7) + 6;

				if ($desc_length <= 45)
					$temp_height += $desc_length;
				else
					$temp_height += 45;
			}
			
			// Add in an extra line of text for the date if it's shown
			if ($this->use_dates_in_list)
				$temp_height += 13;
				
			if ($temp_height > $largest_height)
				$largest_height_with_text = $temp_height;
			
		}
		if ($largest_height_with_text == 0) $largest_height_with_text = $largest_height;
		$largest_height_with_text = round($largest_height_with_text) + 15;
		
		
		if ($largest_height == 0)
		{
			$largest_height = 90;
			$largest_height_with_text = $largest_height + $largest_height_with_text;
		}
		
		$css =  "\n\t".'#imageGalleryItemList li.item, li#imageGalleryNextPageItem {';
		$css .= "\n\t\t".'width: '.$largest_width_with_padding.'px;';
		$css .= "\n\t\t".'height: '.$largest_height_with_text.'px;';
		$css .= "\n\t".'}';
//		$css .= "\n\t".'#imageGalleryItemList li.item div.noImagePlaceholder {';
//		$css .= "\n\t\t".'width: '.$largest_width . 'px;';
//		$css .= "\n\t\t".'height: '.$largest_height .'px;';
//		$css .= "\n\t".'};."\n"';
		$this->parent->add_head_item('style', array('type' => 'text/css','media' => 'screen'),$css);

	}
	
	function do_list()
	{
		echo '<ul id="imageGalleryItemList">'."\n";
		foreach( $this->items AS $item )
		{
			$this->show_list_item( $item );
		}
		
		if(
			$this->use_pagination && 
			( $this->show_list_with_details || empty( $this->current_item_id ) ) && 
			$this->total_pages > 1 && $this->total_pages != $this->request['page'] 
		)
		{
			$pages = $this->get_pages_for_pagination_markup();
			if (array_key_exists($this->request['page'] + 1,$pages))
				echo '<li id="imageGalleryNextPageItem"><a href="'.$pages[$this->request['page'] + 1]['url'].'" title="Page '.($this->request['page'] + 1).'">Next Page</a></li>'."\n";
			//pray($pages);
		}
		echo '</ul>'."\n";
	}
	
	//Called on by list_items()
	//Calls on show_list_item_name(), show_list_item_desc(), show_list_item_pre()
	function show_list_item( $item ) // {{{
	{
		echo '<li class="item number'.$this->item_counter.'">' . "\n";
		$this->show_list_item_pre( $item );
		echo '<a href="' . $this->construct_link($item) . '" class="imageLink">';
		$this->show_list_item_name( $item );
		echo '</a>'."\n";
		$this->show_list_item_date( $item );
		$this->show_list_item_desc( $item );
		echo '</li>'."\n";
		$this->item_counter++;
	} // }}}
	
	//Called on by show_list_item
	function show_list_item_name( $item )
	{
		if ($image_markup = $this->show_image($item))
			echo $image_markup;
		else
			echo '<span class="noImagePlaceholder">No Thumbnail</span>';
	}
	
	//Called on by show_list_item
	function show_list_item_date( $item )
	{
		if($this->use_dates_in_list && $item->get_value( 'datetime' ) )
		{
			echo '<p class="dateText">';
			echo '<a href="' . $this->construct_link($item) . '">';
			echo prettify_mysql_datetime( $item->get_value( 'datetime' ), $this->date_format );
			echo '</a></p>'."\n";
		}
	}
	
	//Called on by show_list_item
	function show_list_item_desc( $item )
	{
		if($this->use_desc_in_list && $item->get_value('description'))
		{
			echo '<p class="descText">';
			echo '<a href="' . $this->construct_link($item) . '">';
			echo $item->get_value('description');
			echo '</a></p>'."\n";
		}
	}
	
	//Called on by show_item
	function show_item_name( $item ) // {{{
	{
		echo '<h3 class="imageTitle">' . $item->get_value( 'description' ) . '</h3>'."\n";
	} // }}}
	
	//Called on by show_item()
	function show_item_content( $item ) // {{{
	{
		$this->show_sequence_number($item);
		$next_prev_array = $this->get_next_and_prev_images($item);
		if (!empty($next_prev_array['prev']))
		{
			echo '<div class="prevWrapper"><div class="thumbnail">';
			if ($prev_image_markup = $this->show_image($next_prev_array['prev']))
				echo $prev_image_markup;
			echo '</div><a href="'.$this->construct_link($next_prev_array['prev']).'" title="Previous: ' . $next_prev_array['prev']->get_value('description') . '"><img class="nav_arrow" src="'.$this->prev_arrow_url.'" width="80" height="80" alt="Previous image"></a></div>'."\n";
		}
		
		if (!empty($next_prev_array['next']))
		{
			echo '<div class="nextWrapper"><div class="thumbnail">';
			if ($next_image_markup = $this->show_image($next_prev_array['next']))
				echo $next_image_markup;
			echo '</div><a href="'.$this->construct_link($next_prev_array['next']).'" title="Next: ' . $next_prev_array['next']->get_value('description') . '"><img class="nav_arrow" src="'.$this->next_arrow_url.'" width="80" height="80" alt="Next image"></a></div>'."\n";
		}
		echo '<div class="imageWrapper">';
		echo $this->show_image( $item, false );
		echo '</div>'."\n";
		echo '<div class="imageCaptionWrapper">'."\n";
		if ($item->get_value( 'content' ))
			echo '<div class="fullDescription">' .  $item->get_value( 'content' ) . '</div>'."\n";
		if ($item->get_value( 'author' ))
			echo '<div class="author"><h4>Photo:</h4> ' .  $item->get_value( 'author' ) . '</div>'."\n";
		if ($item->get_value( 'keywords' ))
			echo '<div class="keywords"><h4>Keywords:</h4> ' .  $item->get_value( 'keywords' ) . '</div>'."\n";
		if ($item->get_value( 'datetime' ))
			echo '<div class="dateTime">' .  prettify_mysql_datetime($item->get_value( 'datetime' ), $this->date_format) . '</div>'."\n";
		$owner_site_id = get_owner_site_id($item->id());
		if ($owner_site_id != $this->site_id)
		{
			$owner_site = new entity($owner_site_id);
			if ($owner_site->get_values())
			{
				echo '<div class="owner">From site: <a href="'.$owner_site->get_value( 'base_url' ).'">' . $owner_site->get_value( 'name' ) . '</a></div>'."\n"; 
			}
		}
		if ($cat_array = $this->get_categories_for_image($item->id()))
			echo '<div class="categories"><h4>Categories:</h4> ' . implode(', ',$cat_array) . '</h4></div>';
		echo '</div>'."\n";
	} // }}}
	
	function get_categories_for_image($image_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('category_type'));
		$es->add_right_relationship($image_id, relationship_id_of('image_to_category'));
		$categories = $es->run_one();
		foreach ($categories as $category)
		{
			$category_array[$category->id()] = $category->get_value('name');
		}
		if (!empty($category_array))
			return $category_array;
		else
			return false;
	}
	
	function get_next_and_prev_images($item)
	{
		$return = array();
		
		if(!empty($this->items[$item->id()]))
		{
			reset($this->items);
			while ($item->id() != key($this->items))
			{
            	next($this->items);
            }
			if($next_image = next($this->items))
			{
				$return['next'] = $next_image;
			}
			prev($this->items);
			if($prev_image = prev($this->items))
			{
				$return['prev'] = $prev_image;
			}
		}
		
		if(empty($return['next']))
		{
			$next = $this->get_next_image($item);
			if(!empty($next))
				$return['next'] = $next;
		}
		if(empty($return['prev']))
		{
			$prev = $this->get_previous_image($item);
			if(!empty($prev))
				$return['prev'] = $prev;
		}
		if(!empty($return))
		{
			return $return;
		}
		else
		{
			$this->only_one_item = true;
			return false;
		}
	}
	function get_next_image($item)
	{
		if($this->sorting_by_rel_sort_order)
			return $this->get_next_image_using_rel_sort($item);
		else
			return $this->get_next_image_using_sort_criteria($item);
	}
	function get_previous_image($item)
	{
		if($this->sorting_by_rel_sort_order)
			return $this->get_previous_image_using_rel_sort($item);
		else
			return $this->get_previous_image_using_sort_criteria($item);
	}
	function get_next_image_using_rel_sort($item)
	{
		$cur_sort = $this->get_relationship_sort_value($item);
		if($cur_sort)
		{
			$next = carl_clone($this->es);
			$next->add_relation('relationship.rel_sort_order > '.$cur_sort);
			$next->set_num(1);
			$next->set_start(0);
			$items = $next->run_one();
			if (!empty($items))
				return current($items);
		}
		return false;
	}
	function get_next_image_using_sort_criteria($item)
	{
		$next = carl_clone($this->es);
		//$next->add_relation('( ( dated.datetime > "'.$item->get_value('datetime').'" ) || ( dated.datetime = "'.$item->get_value('datetime').'" && entity.id > "'.$item->id().'" ) )');
		$position = 0;
		foreach ($this->sort_order_array as $sort_criteria)
		{
			$relation_string_part = '( ';
			// We want to get the array up to this key, so we use array_slice with a length of $position
			foreach (array_slice($this->sort_order_array,0,$position) as $old_sort_criteria)
			{
				$relation_string_part .= $old_sort_criteria['table_and_field'] . ' = "' . mysql_escape_string($item->get_value($old_sort_criteria['field'])) . '" && ';
			}
			$relation_string_part .= $sort_criteria['table_and_field'] . ' ' . $sort_criteria['chevron'] . ' "' . mysql_escape_string($item->get_value($sort_criteria['field'])) . '" ) '; 
			$relation_string_array[] = $relation_string_part;
			$position++;
		}
		// Combine all the parts with an or in between and put parenthesis around the whole thing.
		$relation_string = '( ' . implode(' || ',$relation_string_array) . ' )';
		
		$next->add_relation($relation_string);
		
		$next->set_num(1);
		$next->set_start(0);
		$items = $next->run_one();
		if (!empty($items))
			return current($items);
		return false;
	}
	function get_previous_image_using_rel_sort($item)
	{
		$cur_sort = $this->get_relationship_sort_value($item);
		if($cur_sort)
		{
			$prev = carl_clone($this->es);
				
			$prev->add_relation('relationship.rel_sort_order < "'.$cur_sort.'"');
			$prev->set_order('relationship.rel_sort_order DESC, dated.datetime DESC, entity.id DESC');
			$prev->set_num(1);
			$prev->set_start(0);
			$items = $prev->run_one();
			if (!empty($items))
				return current($items);
		}
		return false;
	}
	function get_previous_image_using_sort_criteria($item)
	{
		$prev = carl_clone($this->es);
		
		$position = 0;
		foreach ($this->sort_order_array as $sort_criteria)
		{
			$relation_string_part = '( ';
			// We want to get the array up to this key, so we use array_slice with a length of $position
			foreach (array_slice($this->sort_order_array,0,$position) as $old_sort_criteria)
			{
				$relation_string_part .= $old_sort_criteria['table_and_field'] . ' = "' . mysql_escape_string($item->get_value($old_sort_criteria['field'])) . '" && ';
			}
			$relation_string_part .= $sort_criteria['table_and_field'] . ' ' . $sort_criteria['opposite_chevron'] . ' "' . mysql_escape_string($item->get_value($sort_criteria['field'])) . '" ) '; 
			$relation_string_array[] = $relation_string_part;
			$position++;
			
			// We need to also create a string for $entity_selector->set_order() that is the opposite order as the main es
			$order_string_array[] = $sort_criteria['table_and_field'] . ' ' . $sort_criteria['opposite_direction'];
		}
		// Combine all the parts with an or in between and put parenthesis around the whole thing.
		$relation_string = '( ' . implode(' || ',$relation_string_array) . ' )';
		
		$prev->add_relation($relation_string);
		
		$prev->set_order(implode(', ',$order_string_array));
		
		$prev->set_num(1);
		$prev->set_start(0);
		$items = $prev->run_one();
		if (!empty($items))
			return current($items);
		return false;
	}
	
	function get_relationship_sort_value($item)
	{
		if(!empty($this->rel_sort_values_queried[$item->id()]))
			return $this->rel_sort_values[$item->id()];
		$vals = array();
		$this->rel_sort_values_queried[$item->id()] = true;
		if(!empty($item))
		{
			$vals = $item->get_values();
		}
		if(!empty($vals['rel_sort_order']))
		{
			$this->rel_sort_values[$item->id()] = $vals['rel_sort_order'];
			return $this->rel_sort_values[$item->id()];
		}
		else
		{
			$es = new entity_selector();
			$es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_image') );
			$es->add_rel_sort_field( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_image'), 'rel_sort_order');
			$es->add_type($this->type);
			$es->add_relation('entity.id = '.$item->id());
			$es->set_num(1);
			$new_items = $es->run_one();
			if(!empty($new_items))
			{
				$new_item = current($new_items);
				$this->rel_sort_values[$item->id()] = $new_item->get_value('rel_sort_order');
				return $this->rel_sort_values[$item->id()];
			}
		}
		return NULL;
	}
	
	//Called upon by run()
	//calls on construct_link()
	function show_back_link()
	{
	  if (!$this->only_one_item)
		  echo '<div class="back"><a href="'.$this->construct_link(NULL).'">'.$this->back_link_text.'</a></div>'."\n";
	}
	
	//called on by init()
	function alter_es() // {{{
	{
		if (!$this->sorting_by_rel_sort_order)
		{
			if ($this->params['entire_site'])
			{
				$this->es->set_site( $this->parent->site_id );
			}
			else
			{
				$this->es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_image') );
			}
			//pray($this->sort_order_array);
			foreach ($this->sort_order_array as $sort_criteria)
			{
				// We need to create a string for $es->set_order()
				$order_string_array[] = $sort_criteria['table_and_field'] . ' ' . $sort_criteria['direction'];
			}
			
			$this->es->set_order(implode(', ',$order_string_array));
			//$this->es->set_order( 'dated.datetime ASC, entity.id ASC' );
		}
		else
		{
			$this->es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_image') );
			$this->es->add_rel_sort_field( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_image'), 'rel_sort_order');
			// order first by rel_sort_order if that is not defined second criteria is dated.datetime ASC and in case of same datetimes, lastly by entity.id- this keeps pages that change to gallery pages reasonably predictable
			$this->es->set_order( 'rel_sort_order ASC, dated.datetime ASC, entity.id ASC' );
		}
	} // }}}
	
	function get_image_data($image)
	{
		$values = $image->get_values();
		$id = $image->id();
		$image = $values;
		
		$tn_name = $id.'_tn'.'.'.$image['image_type'];
		$full_name = $id.'.'.$image['image_type'];
		
		if( file_exists( PHOTOSTOCK . $tn_name ) )
		{
			$return_array['thumb']['url'] = WEB_PHOTOSTOCK . $tn_name;
			list($return_array['thumb']['width'],$return_array['thumb']['height']) = getimagesize( PHOTOSTOCK . $tn_name );
		}
		if( file_exists( PHOTOSTOCK . $full_name ) )
		{
			$return_array['full']['url'] = WEB_PHOTOSTOCK . $full_name;
			list($return_array['full']['width'],$return_array['full']['height']) = getimagesize( PHOTOSTOCK . $full_name );
		}
		
		$return_array['description'] = $image['description'];
		return $return_array;
	}
	
	
	function show_image($image, $thumbnail = true)
	{
		$values = $image->get_values();
		$id = $image->id();
		$image = $values;
		
		$tn_name = PHOTOSTOCK.$id.'_tn'.'.'.$image['image_type'];
		$full_image_name = PHOTOSTOCK.$id.'.'.$image['image_type'];
		
		if ($thumbnail && file_exists( $tn_name ))
		{
			if( file_exists( $tn_name ) )
			{
				$tn = true;
				$image_name = $id.'_tn.'.$image['image_type'];
			}
		}
		else
		{
			$tn = false;
			$image_name = $id.'.'.$image['image_type'];
		}
		
		
		if( file_exists( PHOTOSTOCK.$image_name ) )
		{
			list($width,$height) = getimagesize( PHOTOSTOCK.$image_name );

			$full_image_exists = file_exists( $full_image_name );

			if( !$image['description'] )
				if( $image['keywords'] )
					$image['description'] = $image['keywords'];
				else
					$image['description'] = $image['name'];

			$mod_time = filemtime( PHOTOSTOCK.$image_name );

			$window_width = $image['width'] < 340 ? 340 : 40 + $image['width'];
			$window_height = 170 + $image['height']; // formerly 130 // 96 works on Mac IE 5
			
			
			if ($thumbnail) $class = "thumbnail";
			else $class = "mainImage";
			return '<img src="'.WEB_PHOTOSTOCK.$image_name.'?cb='.$mod_time.'" width="'.$width.'" height="'.$height.'" alt="'.htmlentities( $image['description'] ).'" class="'.$class.'" border="0" />';
		}
		else
		{
			return false;
		}
	}
	function further_checks_on_entity( $entity )
	{
		$es = carl_clone($this->es);
		$es->add_relation('entity.id = '.$entity->id());
		$es->set_num(1);
		$es->set_start(0);
		$check = $es->run_one();
		if(empty($check))
			return false;
		return true;
	}
	
	function get_distinct_date_array()
	{
		
		$dates_es = carl_clone($this->pre_user_input_es);
		$dates_es->set_order('dated.datetime ASC');
		$dates_es->limit_tables(array('dated'));
		$dates_es->limit_fields(array('entity.id','dated.datetime'));
		$dates_es->set_start(0);
		$dates_es->set_num(false);
		$items = $dates_es->run_one();
		
		foreach ($items as $item)
		{
			$ts = get_unix_timestamp( $item->get_value( 'datetime' ) );
			$dt = date('Y-m-d',$ts);
			$ts = get_unix_timestamp( $dt );
			if( $ts > 0 )
				$return_array[ $dt . '*'] = date('D, M j, Y',$ts);
		}
		if (!empty($return_array))
			return $return_array;
		else
			return false;
	}
	
	function show_sequence_number($item)
	{
		if (($current = $this->get_current_item_position_after_user_input($item)) && ($total = $this->get_total_num_images_after_user_input()))
		{
			echo '<div class="sequenceNum">'."\n";
			echo '#<em>' . $current . '</em> of <em>' . $total . '</em>'."\n";
			echo '</div>'."\n";
		}
	}
	
	function get_current_item_position_after_user_input($item)
	{
		$current_es = carl_clone($this->es);
		$current_es->set_start(0);
		$current_es->set_num(false);
		$current_es->limit_fields(array('entity.id'));
		$items = $current_es->run_one();

		$i = 1;
		if(!empty($items[$item->id()]))
		{
			reset($items);
			while ($item->id() != key($items))
			{
				$i++;
            	next($items);
            }
			return $i;
		}
		else
		{
			return false;
		}
	}
	
	function get_total_num_images_after_user_input()
	{
		$total_es = carl_clone($this->es);
		$total_es->set_start(0);
		$total_es->set_num(false);
		return $total_es->get_one_count();
	}
	
	function get_total_num_images_before_user_input()
	{
		$total_es = carl_clone($this->pre_user_input_es);
		$total_es->set_start(0);
		$total_es->set_num(false);
		return $total_es->get_one_count();
	}
	
	function show_search()
	{
		if (($this->get_total_num_images_before_user_input() < $this->min_num_to_show_search ) && (empty($this->request['search'])) )
			return false;
		else
			return true;
	}
	function get_crumb_text(&$item)
	{
		return strip_tags( $item->get_value('description') );
	}
}

?>