<?php

/**
 * Contains the 3.0 version of the generic module
 */

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'Generic3Module';
	reason_include_once('minisite_templates/modules/default.php' );
	reason_include_once('function_libraries/url_utils.php');
	
	/**
	 * A class that handles many common module tasks like pagination, searching, filtering, feeds, and more.
	 *
	 * This class does not do anything on its own -- it requires a type to be set
	 * via the $type_unique_name or by overloading the set_type() method.
	 *
	 * It is backwards-compatible with the generic and generic2 modules,
	 * so modules based on those can be upgraded to generic3 with no modification
	 *
	 * @author Matt Ryan
	 */
	class Generic3Module extends DefaultMinisiteModule
	{
		// General settings
		
		/**
		 * The unique name of the primary type that is being displayed with the module
		 * @var string
		 */	
		var $type_unique_name = '';
		
		/**
		 * The string used as the id of the module
		 * This might be better termed 'module_id'
		 * @var string
		 */	
		var $style_string = 'generic';
		
		/**
		 * The header used above the list of items when they are displayed below an item detail
		 * @var string
		 */	
		var $other_items = 'Other Items';
		
		/**
		 * The plural word used throughout the module to refer to more than one item
		 * @var string
		 */	
		var $plural_type_name = 'items';
		
		/**
		 * The entity selector that is used to grab the items
		 * @var object an entity_selector
		 */	
		var $es; //entity selector
		
		/**
		 * The header used above entire module
		 * Module does not have a title if this string is empty
		 * @var string
		 */	
		var $module_title = '';
		
		/**
		 * Title level used for the module title if it is used
		 * @var integer
		 */	
		var $module_title_level = 3;
		
		/**
		 * The array of items that the entity selector grabs
		 * This array is iterated over to generate the listing on the page
		 * @var array
		 */	
		var $items = array();
		
		/**
		 * The string used to denote the item in the query string
		 * '_id' added to this string to build actual query key
		 * @var string
		 */	
		var $query_string_frag = 'item';
		
		/**
		 * Rules to help validate inputs
		 * @var array
		 */	
		var $cleanup_rules = array(
			'filters' => array('function' => 'turn_into_array'),
			'search' => array('function' => 'turn_into_string'),
			'page' => array('function' => 'turn_into_int'),
			'textonly' => array('function' => 'turn_into_string'),
			'add_item' => array('function' => 'turn_into_string'),
		);
		// Empty result settings
		var $no_items_text = 'There are no items available on this site.';
		// Filter settings
		var $use_filters = false;
		var $filter_types = array();
		var $filters = array();
		var $filter_entities = array();
		var $search_fields = array('entity.name');
		var $default_links = array();
		var $search_field_size = 20;
		// Pagination settings
		var $use_pagination = false;
		var $num_per_page = 10;
		var $total_count = 0;
		var $total_pages = 0;
		var $pagination_output_string = '';
		var $pagination_prev_next_texts = array('previous'=>'Previous','next'=>'Next');
		// Listing Settings
		var $use_dates_in_list = false;
		var $date_format = 'j F Y';
		var $show_list_with_details = true; // Toggles whether to show the list after the details of the item
		var $back_link_text = 'Back to list';
		var $base_params = array(
			'limit_to_current_site'=>true,
			'filter_displayer'=>'default.php',
			'pagination_displayer'=>'window.php',
		);
		var $jump_to_item_if_only_one_result = true;
		var $has_feed = false;
		var $feed_link_title = 'RSS 2.0 feed';
		var $make_current_page_link_in_nav_when_on_item = false;
		var $feed_url = ''; // this is where get_feed_url stores it
		var $item_counter = 1;
		
		// class variables for linking to a separate page
		
		
		var $page_types_available_for_linking = array();
		var $link_to_a_different_page = false;
		var $path_to_link_target_page = '';
		var $attempted_to_find_target_page = false;
		var $use_other_page_name_as_module_title = false;
		var $link_target_page;
		
		// stealth mode hides the module if it is empty
		var $stealth_mode = false;
		var $report_last_modified_date = true;
		var $ok_ids = array();
		var $not_ok_ids = array();
		
		//calls on parent::init, set_type(), alter_es(), do_filtering(), do_pagination() add_crumb(), 
		function init( $args ) // {{{
		{
			$error = 'Your class needs to have a type id.  Please overload the set_type() function and '.
					 'include a line such as $this->type = id_of( "something" ) to run this module.';
			parent::init( $args );
			$this->set_type();
			if( empty( $this->type ) )
				trigger_error( $error , E_USER_ERROR );
				
			$this->additional_init_actions();
			$this->pre_es_additional_init_actions();
					
			if(empty($this->request['add_item']));
			{
				if($this->params['limit_to_current_site'])
				{
					$this->es = new entity_selector( $this->parent->site_id );
				}
				else
				{
					$this->es = new entity_selector();
				}
				$this->es->add_type( $this->type );
				$this->es->set_env('site_id',$this->parent->site_id);
				$this->alter_es();
				if($this->use_filters)
					$this->do_filtering();
				if($this->use_pagination)
					$this->do_pagination(); // This needs to go last before calling "run_one."
				
				$this->items = $this->es->run_one();
				$this->alter_items($this->items);
				
				if( count( $this->items ) > 1 )
				{
					if( !empty( $this->request[ $this->query_string_frag.'_id' ] ) ) $this->add_crumb();
				}
				if(
					$this->make_current_page_link_in_nav_when_on_item
					&&
					!empty($this->request[$this->query_string_frag.'_id'])
				)
				{
					$this->parent->pages->make_current_page_a_link();
				}
				if($this->has_feed)
				{
					$this->add_feed_to_head();
				}
				if($this->use_other_page_name_as_module_title)
				{
					$this->get_path_to_link_target_page();
					if(!empty($this->link_target_page))
					{
						$this->module_title = $this->link_target_page->get_value('name');
					}
				}
				$this->post_es_additional_init_actions();
			}
		} // }}}

		function alter_items(&$items)
		{
			return $items;
		}
		
		function add_crumb()
		{
			foreach( $this->items AS $item )
			{
				if( $item->id() == $this->request[ $this->query_string_frag.'_id' ] ) 
				{
					$this->parent->add_crumb( $item->get_value( 'name' ) );
				}
			}
		}

		function get_cleanup_rules()
		{
			$this->cleanup_rules[$this->query_string_frag . '_id'] = array('function' => 'turn_into_int');
			return $this->cleanup_rules;
		}
		function has_content()
		{
			if($this->stealth_mode && empty($this->items))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		
		//calls upon show_filtering(), _show_item(), list_items(), show_back_link(), construct_link()
		function run() // {{{
		{
			echo '<div id="'.$this->style_string.'">'."\n";
			if(!empty($this->module_title))
				echo '<h'.$this->module_title_level.'>'.$this->module_title.'</h'.$this->module_title_level.'>'."\n";

			if(!empty($this->request['add_item']))
			{
				$this->add_item();
			}
			else
			{
				echo '<div class="persistent">'."\n";
				echo $this->get_add_item_link();
				
				if($this->use_filters)
				{
					echo '<div id="filtering">'."\n";
					$this->show_filtering();
					echo '</div>'."\n";
				}
				
				echo $this->get_login_logout_link();
				
				echo '</div>'."\n"; // close the persistent items
				
				if(!empty( $this->request[ $this->query_string_frag.'_id' ] ) )
				{
					$this->_show_item( $this->request[ $this->query_string_frag.'_id' ] );
					if($this->show_list_with_details && count($this->items) > 1)
						$this->list_items();
					else
						$this->show_back_link();
				}
				
				else
				{
					if($this->jump_to_item_if_only_one_result && $this->total_count == 1)
					{
						$item = current($this->items);
						$url_parts = parse_url(get_current_url());
						$location = $url_parts['scheme'].'://'.$url_parts['host'].$url_parts['path'].$this->construct_link($item);
						$location = html_entity_decode( $location );
						header('Location: '.$location);
						die();
					}
					$this->list_items();
				}
			}
			echo '</div>'."\n";
		} // }}}

		//called on by run
		function add_item()// {{{
		{	
		} // }}}
		
		//called on by run
		function get_add_item_link()// {{{
		{
		}// }}}


		//Called upon by run().
		//Calls on check_id(), show_item_name(), show_item_content() 
		function _show_item( $id ) // {{{
		{
			if(!empty($this->items[$id]))
			{
				 // Checks aren't needed because the entity already exists in the query result
				$entity =  $this->items[$id];
			}
			else
			{
				// Checks are needed because the id may be of an entity that is not OK to show
				// check_id returns the entity if it is OK and false if it is not OK
				$entity = $this->check_id( $id );
			}
			if(!empty($entity))
			{
				echo '<div class="item">'."\n";
				$this->show_item_name( $entity );
				$this->show_item_content( $entity );
				echo '</div>'."\n";
			}
			else
			{
				echo '<div class="notice itemNotAvailable"><h3>Sorry -- this item is not available</h3><p>This might be because...</p><ul><li>the page you are coming from has a bad link</li><li>there is a typo in the web address</li><li>the item you are requesting has been removed</li></ul>';
				if($this->show_list_with_details && !$this->stealth_mode)
				{
					echo '<p>If you think you might have made a mistake typing the web address, look at the items below to see if what you want is listed.</p>';
				}
				echo '<p>If you wish to report a bad link, please contact the site maintainer listed at the bottom of this page.</p></div>'."\n";
			}
		} // }}}
		
		//Called upon by _show_item()
		//Calls on further_checks_on_entity()
		function check_id( $id )
		{
			$e = new entity ( $id );
			if(in_array($id,$this->ok_ids))
			{
				return $e;
			}
			elseif(in_array($id, $this->not_ok_ids))
			{
				return false;
			}
			elseif( $e->get_values()
				&& $e->get_value('type')
				&& $e->get_value('type') == $this->type
				&& $e->get_value('state') == 'Live'
				&& ( !$this->params['limit_to_current_site'] || $e->owned_or_borrowed_by( $this->parent->site_id ) )
				&& $this->further_checks_on_entity( $e )
			 )
			 {
			 	$this->ok_ids[] = $id;
				return $e;
			}
			else
			{
				$this->not_ok_ids[] = $id;
				header('HTTP/1.0 404 Not Found');
				if(!empty($_SERVER['HTTP_REFERER']))
				{
					trigger_error('ID given does not correspond to an appropriate entity. Referer: '.$_SERVER['HTTP_REFERER']);
				}
				return false;
			}
		}
		
		//Called on by further_checks_on_entity
		function further_checks_on_entity( $entity )
		{
			// This exists to allow modules to do checks that are specific to the type or the appplication.  To use this function, overload it. It should return true if the entity looks OK to be shown and false if it does not.
			return true;
		}

		//Called on by run
		//Calls on show_pagination(), show_list_item(), 
		function list_items() // {{{
		{
			echo '<div class="moduleNav">'."\n";
			if(!empty( $this->request[ $this->query_string_frag.'_id' ] ) && !empty($this->other_items))
			{
				echo '<h3>';
				if($this->use_filters && (!empty($this->filters) || !empty($this->request['search'])))
				{
					if(!empty($this->request['search']))
						$phrase[] = 'search term';
					if(!empty($this->filters))
						$phrase[] = 'focus';
					echo $this->other_items.' which match the current '.implode(' and ', $phrase);
				}
				else
				{
					echo $this->other_items;
				}
				echo '</h3>'."\n";
			}
			
			$this->show_pagination('above');
			if($this->list_should_be_displayed())
			{
				$this->do_list();
				if($this->use_filters && (!empty($this->filters) || !empty($this->request['search'])))
				{
					$link = '?';
					if (!empty($this->parent->textonly))
						$link .= '&amp;textonly=1';
					echo '<p><a href="'.$link.'">Show all '.$this->plural_type_name.'</a></p>'."\n";
				}
			}
			else
			{
				echo '<p>';
				if($this->use_filters)
				{
					if(!empty($this->request['search']))
						$phrase[] = 'search term';
					if(!empty($this->filters))
						$phrase[] = 'focus';
					if(!empty($phrase))
						echo 'There are no items that match the current '.implode(' and ', $phrase).'.';
					else
						echo $this->no_items_text;
				}
				else
					echo $this->no_items_text;
				echo '</p>'."\n";
			}
			$this->show_pagination('below');
			$this->post_list_items();
			if($this->has_feed)
			{
				$this->show_feed_link();
			}
			echo '</div>'."\n";
		} // }}}
		
		/**
		 * Hook for anything that should be done after items are listed, before the feed link is shown
		 */
		function post_list_items()
		{
		}
		
		function list_should_be_displayed()
		{
			if(!empty($this->items))
				return true;
			else
				return false;
		}
		
		function do_list()
		{
			echo '<ul>'."\n";
			foreach( $this->items AS $item )
			{
				$this->show_list_item( $item );
			}
			echo '</ul>'."\n";
		}

		//called on by init()
		function set_type() // {{{
		{
			if(!empty($this->type_unique_name))
				$this->type = id_of( $this->type_unique_name );
		} // }}}
		
		//called on by init()
		function alter_es() // {{{
		{
		} // }}}
		
		//Called on by list_items()
		//Calls on show_list_item_name(), show_list_item_desc(), show_list_item_pre()
		function show_list_item( $item ) // {{{
		{
			echo '<li class="item number'.$this->item_counter.'">';
			$this->show_list_item_pre( $item );
			$this->show_list_item_date( $item );
			echo '<strong>';
			if(empty($this->request[ $this->query_string_frag.'_id' ]) || $this->request[ $this->query_string_frag.'_id' ] != $item->id() )
			{
				echo '<a href="' . $this->construct_link($item) . '">';
				$this->show_list_item_name( $item );
				echo '</a>';
			}
			else
				$this->show_list_item_name( $item );
			echo '</strong>';
			//if(empty($this->request[ $this->query_string_frag.'_id' ]))
			$this->show_list_item_desc( $item );
			echo '</li>'."\n";
			$this->item_counter++;
		} // }}}
		function show_list_item_date( $item )
		{
			if($this->use_dates_in_list && $item->get_value( 'datetime' ) )
				echo '<div class="smallText date">'.prettify_mysql_datetime( $item->get_value( 'datetime' ), $this->date_format ).'</div>'."\n";
		}
		
		//Called on by show_list_item()
		//Intended to be overloaded
		function show_list_item_pre( $item ) // {{{
		{
		}
		
		//Called upon by run()
		//calls on construct_link()
		function show_back_link()
		{
			echo '<div class="back"><a href="'.$this->construct_link(NULL).'">'.$this->back_link_text.'</a></div>'."\n";
		}
		
		//Called upon by run(), show_list_item(), show_back_link()
		function construct_link($item, $other_args = array() )
		{
			$link_frags = array();
			if(!empty($item))
				$link_frags[ $this->query_string_frag.'_id' ] = $item->id();
			if (!empty($this->parent->textonly))
				$link_frags[ 'textonly' ] = 1;
			if($this->use_filters)
			{
				foreach($this->filters as $key=>$vals)
				{
					$link_frags[ 'filters['.$key.'][type]' ] = $vals['type'];
					$link_frags[ 'filters['.$key.'][id]' ] = $vals['id'];
				}
				if(!empty($this->request['search']))
					$link_frags[ 'search' ] = urlencode($this->request['search']);
			}
			if (!empty($this->request['page']))
				$link_frags[ 'page' ] = $this->request['page'];
			
			foreach($other_args as $key=>$value)
			{
				$link_frags[$key] = $value;
			}
			$query_frags = array();
			foreach($link_frags as $key=>$value)
			{
				$query_frags[] = $key.'='.$value;
			}
			$link = $this->get_path_to_link_target_page().'?'.implode('&amp;',$query_frags);
			return $link;
		}
		
		//Called on by show_list_item
		function show_list_item_name( $item )
		{
			echo $item->get_value( 'name' );
		}
		
		//Called on by show_list_item
		function show_list_item_desc( $item )
		{
			if($item->get_value('description'))
				echo '<div class="desc">'.$item->get_value('description').'</div>'."\n";
		}
		
		//Called on by show_item
		function show_item_name( $item ) // {{{
		{
			echo '<h3>' . $item->get_value( 'name' ) . '</h3>'."\n";
		} // }}}
		
		//Called on by show_item()
		function show_item_content( $item ) // {{{
		{
			echo '<div>' . $item->get_value( 'content' ) . '</div>'."\n";
		} // }}}
		
# 		is this ever called upon?
		function show_back() // {{{
		{
			$link = '?';
			if (!empty($this->parent->textonly))
				$link .= 'textonly=1';
			echo '<a href="'.$link.'">Back to List</a>';
		} // }}}
		
		//Called on by init()
		function do_filtering()
		{
			if(!empty($this->request['filters']))
			{
				$this->filters = $this->request['filters'];
				foreach($this->filters as $key=>$filter)
				{
					settype($filter['id'], 'integer'); // force an integer to thwart SQL insertion through query string
					$this->es->add_left_relationship( $filter['id'] /*, $this->filter_types[$filter['type']]['relationship'] */);
				}
			}
			if(!empty($this->request['search']))
			{
				$search_array = array();
				foreach($this->search_fields as $field)
				{
					$search_array[] = $field.' LIKE "%'.addslashes($this->request['search']).'%"';  // add slashes to thwart SQL insertion through query string
				}
				//echo '('.implode(' OR ', $search_array).')';
				$this->es->add_relation('('.implode(' OR ', $search_array).')');
			}
		}
		
		//Called on by init()
		function do_pagination()
		{
			$this->total_count = $this->es->get_one_count();
			if(empty($this->request['page']))
				$this->request['page'] = 1;
			$this->es->set_start( $this->num_per_page * ( $this->request['page'] - 1 ) );
			$this->es->set_num( $this->num_per_page );
		}
		
		//Called on by list_items
		function show_pagination($class = '')
		{
			if($this->use_pagination && ( $this->show_list_with_details || empty( $this->request[ $this->query_string_frag.'_id' ] ) ) )
			{
				if(empty($this->total_pages))
					$this->total_pages = ceil( $this->total_count / $this->num_per_page );
				if($this->total_pages > 1)
				{
					if(empty($this->pagination_output_string))
					{
						$this->pagination_output_string = $this->_get_pagination_markup();
					}
					if(!empty($class))
					{
						$class = ' '.$class;
					}
					echo '<div class="pagination'.$class.'">'.$this->pagination_output_string.'</div>'."\n";
				}
			}
		}
		function _get_pagination_markup()
		{
			reason_include_once('minisite_templates/modules/pagination_displayers/'.$this->params['pagination_displayer']);
			if(!empty($GLOBALS['_reason_pagination_displayers'][$this->params['pagination_displayer']]))
			{
				$class = $GLOBALS['_reason_pagination_displayers'][$this->params['pagination_displayer']];
				$pd = new $class();
				if (!empty($this->request['page']))
					$pd->set_current_page($this->request['page']);
				$pd->set_previous_item_text($this->pagination_prev_next_texts['previous']);
				$pd->set_next_item_text($this->pagination_prev_next_texts['next']);
				$pd->set_pages($this->get_pages_for_pagination_markup());
				return $pd->get_markup();
			}
			else
			{
				trigger_error('No pagination class specified in '.$this->params['pagination_displayer']);
			}
		}
		function get_pages_for_pagination_markup()
		{
			$pages = array();
			for($i = 1; $i <= $this->total_pages; $i++)
			{
				$pages[$i] = array('url'=>$this->construct_link(NULL, array('page'=>$i) ) );
			}
			return $pages;
		}
		
		//Called on by run()
		//Calls on build_default_links(), show_filter_set()
		function show_filtering()
		{
			$markup = $this->get_filter_markup();
			if(!empty($markup['search']))
			{
				echo $markup['search'];
			}
			if(!empty($markup['filter']))
			{
				echo $markup['filter'];
			}
		}
		function get_filter_markup()
		{
			foreach($this->filter_types as $filter_name=>$filter_type)
			{
				$es = new entity_selector($this->parent->site_id);
				$es->add_type(id_of($filter_type['type']));
				$es->set_order('entity.name ASC');
				$filter_entities = $es->run_one();
				if(!empty($filter_entities))
				{
					// check to make sure the relationship filtering makes sense for each item
					if($this->params['limit_to_current_site'])
					{
						$setup_es = new entity_selector($this->parent->site_id);
					}
					else
					{
						$setup_es = new entity_selector();
					}
					$setup_es->add_type( $this->type );
					$setup_es->set_env('site_id',$this->parent->site_id);
					$setup_es = $this->alter_relationship_checker_es($setup_es);
					$setup_es->set_num(1);
					foreach($filter_entities as $key=>$filter)
					{
						$es = $setup_es;
						$es->add_left_relationship( $filter->id() );
						$results = $es->run_one();
						if(empty($results))
						{
							unset($filter_entities[$key]);
						}
						$results = array();
					}
					if(!empty($filter_entities))
					{
						$this->filter_entities[$filter_name] = $filter_entities;
					}
				}
			}
			ksort($this->filters);
			foreach($this->filters as $key=>$values)
			{
				$this->build_default_links($key);
			}
			
			reason_include_once('minisite_templates/modules/filter_displayers/'.$this->params['filter_displayer']);
			if(!empty($GLOBALS['_reason_filter_displayers'][$this->params['filter_displayer']]))
			{
				$class = $GLOBALS['_reason_filter_displayers'][$this->params['filter_displayer']];
				$fd = new $class();
				$fd->set_type($this->type);
				$fd->set_filter_types($this->filter_types);
				$fd->set_filters($this->filters);
				$fd->set_textonly($this->parent->textonly);
				$fd->set_search_field_size($this->search_field_size);
				if(!empty($this->request['search']))
				{
					$fd->set_search_value($this->request['search']);
				}
				$fd->set_default_links($this->default_links);
				$fd->set_filter_entities($this->filter_entities);
				$ret = array();
				$ret['search'] = $fd->get_search_interface();
				$ret['filter'] = $fd->get_filter_interface();
				return $ret;
			}
			else
			{
				trigger_error('No filter class specified in '.$this->params['filter_displayer']);
			}
		}
		function alter_relationship_checker_es($es)
		{
			return $es;
		}

		//called on by show_filtering()		
		function build_default_links($key)
		{
			$this->default_links[$key] = 'filters['.$key.'][type]='.$this->filters[$key]['type'].'&amp;filters['.$key.'][id]='.$this->filters[$key]['id'];
		}
		
		function show_feed_link()
		{
			if( $this->get_feed_url() )
			{
				reason_include_once('function_libraries/feed_utils.php');
				echo make_feed_link( $this->get_feed_url(), $this->feed_link_title);
			}
		}
		
		function add_feed_to_head()
		{
			if($this->get_feed_url())
			{
				$this->parent->add_head_item('link',array('rel'=>'alternate','type'=>'application/rss+xml','href'=>$this->get_feed_url(),'title'=>$this->feed_link_title,));
			}
		}
		
		function get_feed_url()
		{
			if(empty($this->feed_url))
			{
				$type = new entity($this->type);
				if($type->get_value('feed_url_string'))
				{
					$this->feed_url = $this->parent->site_info->get_value('base_url').MINISITE_FEED_DIRECTORY_NAME.'/'.$type->get_value('feed_url_string');
				}
			}
			if(!empty($this->feed_url))
			{
				return $this->feed_url;
			}
		}
		
		function get_login_logout_link()
		{
			return '';
		}
		
		function get_path_to_link_target_page()
		{
			if($this->link_to_a_different_page)
			{
				if(!$this->attempted_to_find_target_page)
				{
					$relation = '(page_node.custom_page = "'.implode('" OR page_node.custom_page = "',$this->page_types_available_for_linking).'")';
					
					$es = new entity_selector($this->site_id);
					$es->add_type( id_of( 'minisite_page' ) );
					$es->add_relation( $relation );
					$es->set_num( 1 );
					$pages = $es->run_one();
					if(!empty($pages))
					{
						$this->link_target_page = current($pages);
						$link = $this->parent->pages->get_full_url($this->link_target_page->id());
						$this->path_to_link_target_page = $link;
					}
					$this->attempted_to_find_target_page = true;
				}
				return $this->path_to_link_target_page;
			}
			return '';
		}
		
		/**
		*  Hook to allow children to add things to the init function.
		*  This currently happens after the type is set and before the entity selector is run.
		*/
		function additional_init_actions()
		{
		
		}
		/**
		* Template calls this function to figure out the most recently last modified item on page
		* This function uses the most recently modified item in list if not looking at an individual item
		* If looking at details, it returns last modified info for that item
		* @return mixed last modified value or false
		*/
		function last_modified() // {{{
		{
			//not all child classes have to have items to have content, so we need to check both.
			if( $this->report_last_modified_date && $this->has_content() && !empty($this->items) )
			{
				if(empty( $this->request[ $this->query_string_frag.'_id' ] ) )
				{
					/*$temp = $this->es->get_max( 'last_modified' );
					return $temp->get_value( 'last_modified' );*/
					$max_last_mod = 0;
					foreach(array_keys($this->items) as $key)
					{
						if($this->items[$key]->get_value('last_modified') > $max_last_mod)
							$max_last_mod = $this->items[$key]->get_value('last_modified');
					}
					if(!empty($max_last_mod))
					{
						return $max_last_mod;
					}
				}
				else
				{
					$id = $this->request[ $this->query_string_frag.'_id' ];
					if(!empty($this->items[$id]))
					{
						$entity =  $this->items[$id];
					}
					else
					{
						$entity = $this->check_id( $id );
					}
					if(!empty($entity))
					{
						return $entity->get_value( 'last_modified' );
					}
					else
					{
						return false;
					}
				}
			}
			else
			{
				return false;
			}
		} // }}}
		
		/**
		*  Hook to allow children to add things to the init function.
		*  This happens after the type is set and before the entity selector is run.
		*/
		function pre_es_additional_init_actions()
		{
		
		}
		
		/**
		*  Hook to allow children to add things to the init function.
		*  This is the last thing to run in the init function 
		*/
		function post_es_additional_init_actions()
		{
		
		}
	}
?>
