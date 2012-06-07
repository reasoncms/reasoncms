<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
	/**
	 * Include parent class & register module with Reason
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
	 * @todo Work up a next-generation MVC-style generic module that can truly deserve the title
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
		 * A copy of the entity selector before user
		 * input such as filters are applied
		 * @var object an entity_selector
		 */
		var $pre_user_input_es;  //entity selector
		
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
		*  The array of all items that would appear in a full
		*  list of the module
		*/
		var $ids = array();
		
		/**
		*  An array of all items, keyed on positions (1,2,3,4,etc.)
		*  to item ids
		*/
		var $position_to_ids = array();
		
		/**
		*  An array of all items, keyed on ids to
		*  poistions (1,2,3,4,etc.)
		*/
		var $id_to_positions = array();
		
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
			'filter1' => array('function' => 'turn_into_string'),
			'filter2' => array('function' => 'turn_into_string'),
			'filter3' => array('function' => 'turn_into_string')
		);
		// Empty result settings
		var $no_items_text = 'There are no items available.';
		// Filter settings
		var $use_filters = false;
		var $filter_types = array();
		var $filters = array();
		var $filter_entities;
		var $search_fields = array('entity.name');
		var $default_links = array();
		var $search_field_size = 20;
		
		/**
		 * Sets up power search arguments.
		 * Array of 'url_fragment' => array (
		 *									'field'=>field in reason to search, 
		 *									'cleanup_rule' => cleanup rule as defined in generic3,
		 *								),
		 * @var array
		 */
		var $allowable_psearch_fields = array();
		
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
			'wrapper_id_string'=>'',
			'straight_join_filter_threshold' => 4,
			'max_filters' => 3
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
		
		var $current_item_id;
		
		//calls on parent::init, set_type(), alter_es(), apply_user_input_to_es(), do_filtering(), do_pagination() add_crumb(), refine_ids_and_positions_arrays()
		function init($args = array()) // {{{
		{
			$error = 'Your class needs to have a type id.  Please overload the set_type() function and '.
					 'include a line such as $this->type = id_of( "something" ) to run this module.';
			parent::init( $args );
			$this->set_type();
			if( empty( $this->type ) )
				trigger_error( $error , E_USER_ERROR );
				
			if(!empty($this->request[ $this->query_string_frag.'_id' ]))
				$this->current_item_id = $this->request[ $this->query_string_frag.'_id' ];
				
			if(!empty($this->params['wrapper_id_string']))
			{
				$this->style_string = $this->params['wrapper_id_string'];
			}
			
			$this->additional_init_actions();
			$this->pre_es_additional_init_actions();
					
			if(empty($this->request['add_item']));
			{
				$this->es = $this->_create_primary_entity_selector();
				$this->alter_es();
				
				// We want to "archive" a version of the entity selector
				// just before the filters are applied
				$this->pre_user_input_es = carl_clone($this->es);				
				$this->apply_user_input_to_es();
				if($this->use_filters)
					$this->do_filtering();
				
				
				// So the idea here is to grab an array of ids of all of the elements
				// that would appear on a full list of the module. From this we can
				// easily find an item's position relative to the list and next and
				// previous items.
				
				// If we're viewing a single item and have no list below the item,
				// we simply want to get the id list and put the current item into
				// the items array
				if (!$this->show_list_with_details && !empty($this->current_item_id))
				{
					$this->es->limit_fields('entity.id');
					$this->es->exclude_tables_dynamically();
					$this->ids = $this->es->run_one();
					
					// make sure the currently selected item has all fields needed (e.g. relationship fields, etc.)
					$item_es = carl_clone($this->es);
					$item_es->add_relation('entity.id = "'.$this->current_item_id.'"');
					$item_es->set_num(1);
					$item_array = $item_es->run_one();
					
					if(!empty($item_array))
					{
						$item = current($item_array);
						$this->items[$item->id()] = $item;
					}
					
					$this->refine_ids_and_positions_arrays();
				}
				else
				{
					// when we are using a bunch of relationship filters AND searching across a big table like the chunk table, mysql can take a really
					// long time figuring out how to optimize the query. Forcing a straight join appears to address this issue. Note that this will only
					// ever be run if you configure max_filters is equal to or greater than the straight_join_filter_threshold.
					if ($this->use_filters && !empty($this->request['filters']) && !empty($this->request['search']))
					{
						if (isset($this->params['straight_join_filter_threshold']) && is_numeric($this->params['straight_join_filter_threshold']))
						{
							if (count($this->request['filters']) >= $this->params['straight_join_filter_threshold'])
							{
								$this->es->optimize('STRAIGHT_JOIN');
							}
						}
					}									  
					
					// If we have a list, either below an item or by itself, and also
					// have pagination, we want to basically grab the current page's
					// "chunk" of items as before and put it into $this->items
					if ($this->use_pagination)
					{
						$all_ids_es = carl_clone($this->es);
						$all_ids_es->limit_fields(array('entity.id'));
						$all_ids_es->exclude_tables_dynamically();
						$this->ids = $all_ids_es->run_one(); 
						
						$this->refine_ids_and_positions_arrays();
						
						// We want to define what page the current item should
						// be on so we know how to do pagination
						if (!empty($this->current_item_id) && ($page_num = $this->get_page_number_from_id($this->current_item_id)))
						{
							$this->request['page'] = $page_num;
						}
						$this->do_pagination(); // This needs to go last before calling "run_one."
						$this->items = $this->es->run_one();
						$this->alter_items($this->items);
					}
					// If we have a list with no pagination, we grab all information
					// about all items for the array of ids and the items array.
					else
					{
						$this->es->exclude_tables_dynamically();
						$this->items = $this->ids = $this->es->run_one();
						$this->refine_ids_and_positions_arrays();
						$this->alter_items($this->items);
					}
				}
				
				if( !empty( $this->items ) && !empty( $this->current_item_id ) && !empty($this->items[$this->current_item_id]) )
				{
					$this->add_crumb();
				}
				if(
					$this->make_current_page_link_in_nav_when_on_item
					&&
					!empty($this->current_item_id)
					&&
					$pages_object =& $this->get_page_nav()
				)
				{
					$pages_object->make_current_page_a_link();
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
				if(!empty( $this->current_item_id ))
				{
					$this->check_id( $this->current_item_id );
				}
				$this->post_es_additional_init_actions();
			}
		} // }}}
		
		function _create_primary_entity_selector()
		{
			if($this->params['limit_to_current_site'])
			{
				$es = new entity_selector( $this->site_id );
			}
			else
			{
				$es = new entity_selector();
			}
			$es->add_type( $this->type );
			$es->set_env('site_id',$this->site_id);
			return $es;
		}

		function alter_items(&$items)
		{
			return $items;
		}
		
		function add_crumb()
		{
			if( !empty( $this->items ) && !empty( $this->current_item_id ) && !empty($this->items[$this->current_item_id]) )
			{
				$this->_add_crumb( $this->get_crumb_text($this->items[$this->current_item_id]) );
			}
		}
		function get_crumb_text(&$item)
		{
			return strip_tags( $item->get_value( 'name' ) );
		}

		function get_cleanup_rules()
		{
			$this->cleanup_rules[$this->query_string_frag . '_id'] = array('function' => 'turn_into_int');
			if ($this->use_filters)
			{
				foreach($this->allowable_psearch_fields as $key_frag => $rule_data)
				{
					$this->cleanup_rules['search_' . $key_frag] = $rule_data['cleanup_rule'];
				}
			}
			
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
			$this->show_style_string();
			if(!empty($this->module_title))
				echo '<h'.$this->module_title_level.'>'.$this->module_title.'</h'.$this->module_title_level.'>'."\n";

			if(!empty($this->request['add_item']))
			{
				$this->add_item();
			}
			else
			{
				$add_item_link = trim($this->get_add_item_link());
				$login_logout_link = trim($this->get_login_logout_link());
				
				if($this->use_filters || !empty($add_item_link) || !empty($login_logout_link))
				{
					echo '<div class="persistent">'."\n";
					echo $add_item_link."\n";
					
					if($this->use_filters)
					{
						echo '<div id="filtering">'."\n";
						$this->show_filtering();
						echo '</div>'."\n";
					}
					
					echo $login_logout_link."\n";
					
					echo '</div>'."\n"; // close the persistent items
				}
				
				if(!empty( $this->current_item_id ) )
				{
					$this->_show_item( $this->current_item_id );
					if($this->show_list_with_details && count($this->items) > 1)
						$this->list_items();
					else
						$this->show_back_link();
				}
				
				else
				{
					if($this->jump_to_item_if_only_one_result && $this->total_count == 1)
					{
						$item = reset($this->items);
						if(is_object($item))
						{
							$url_parts = parse_url(get_current_url());
							if ($this->link_to_a_different_page)
							{
								$location = $url_parts['scheme'].'://'.$url_parts['host'].$this->construct_link($item);
							}
							else
							{
								$location = $url_parts['scheme'].'://'.$url_parts['host'].$url_parts['path'].$this->construct_link($item);
							}
							$location = html_entity_decode( $location );
							header('Location: '.$location);
							die();
						}
					}
					$this->list_items();
				}
			}
			echo '</div>'."\n";
		} // }}}

		function show_style_string()
		{
			echo '<div id="'.$this->style_string.'">'."\n";
		}
		
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
				$this->handle_missing_item($id);
			}
		} // }}}
		
		/** 
		 * Extend this to implement intelligent missing item redirection.
		 *
		 * Called upon by {@link Generic3Module::_show_item()}.
		 * 
		 * @param integer $id Reason Entity ID
		 */
		function handle_missing_item($id)
		{
			echo '<div class="notice itemNotAvailable"><h3>Sorry -- this item is not available</h3><p>This might be because...</p><ul><li>the page you are coming from has a bad link</li><li>there is a typo in the web address</li><li>the item you are requesting has been removed</li></ul>';
			if($this->show_list_with_details && !$this->stealth_mode)
			{
				echo '<p>If you think you might have made a mistake typing the web address, look at the items below to see if what you want is listed.</p>';
			}
			echo '<p>If you wish to report a bad link, please contact the site maintainer listed at the bottom of this page.</p></div>'."\n";
			echo "<p>Entity number: $id</p>";
		}
		
		/**
		 * Check a given id to see if it is OK to show
		 *
		 * Called upon by _show_item()
		 * Calls on further_checks_on_entity()
		 *
		 * @todo check to see if the order of the checking is appropriate... it seems a little wonky
		 * @param integer $id Reason Entity ID
		 * @return mixed false if not OK, the entity object if OK
		 */
		function check_id( $id )
		{
			// assume that if it's in the list it's OK
			if(!empty($this->items[$id]))
				return $this->items[$id];
			
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
			elseif(array_key_exists($id,$this->ids))
			{
				$this->ok_ids[] = $id;
				return $e;
			}
			else
			{
				$this->not_ok_ids[] = $id;
				http_response_code(404);
				// 404s, even internal ones, don't need to go into the error log ... commenting this out to reduce error log spam.
				//if(!empty($_SERVER['HTTP_REFERER']))
				//{
				//	$parts = parse_url($_SERVER['HTTP_REFERER']);
				//	if($parts['host'] == HTTP_HOST_NAME) // probably can't do anything about it if the link is offsite...
				//		trigger_error('ID given does not correspond to an appropriate entity. Referer: '.$_SERVER['HTTP_REFERER']);
				//}
				return false;
			}
		}
		
		//Called on by check_id
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
			if(!empty( $this->current_item_id ) && !empty($this->other_items))
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
		
		//called on by init()
		function apply_user_input_to_es() // {{{
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
			if(empty($this->current_item_id) || $this->current_item_id != $item->id() )
			{
				echo '<a href="' . $this->construct_link($item) . '">';
				$this->show_list_item_name( $item );
				echo '</a>';
			}
			else
				$this->show_list_item_name( $item );
			echo '</strong>';
			//if(empty($this->current_item_id))
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
			$item_page = $this->get_page_number_from_id($this->current_item_id);
			if ($item_page)
				$arg_array = array('page'=>$item_page);
			else
				$arg_array = array();
				
			echo '<div class="back"><a href="'.$this->construct_link(NULL,$arg_array).'">'.$this->back_link_text.'</a></div>'."\n";
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
					// only add if both type and id are present
					if (!empty($vals['type']) && !empty($vals['id']))
					{
						$link_frags[ 'filter'.$key ] = $vals['type'].'-'.$vals['id'];
					}
				}
				if(!empty($this->request['search']))
					$link_frags[ 'search' ] = urlencode($this->request['search']);
				
				foreach($this->allowable_psearch_fields as $psearch_frag => $psearch_data)
				{
					if (!empty($this->request['search_' . $psearch_frag]))
					{
						$link_frags['search_' . $psearch_frag] = strtr(urlencode($this->request['search_' . $psearch_frag]),array('%2A'=>'*'));
					}
				}
			}
			
			if (!empty($this->request['page']) && empty($item))
				$link_frags[ 'page' ] = $this->request['page'];
			
			foreach($other_args as $key=>$value)
			{
				$link_frags[$key] = $value;
			}
			$query_frags = array();
			foreach($link_frags as $key=>$value)
			{
				if (!empty($value))
				{
					$query_frags[] = $key.'='.$value;
				}
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
			$this->get_filters_from_url();
			$this->do_filters_rels();
			$this->do_filters_search();
			$this->get_filter_displayer();
		}
		function get_filter_displayer()
		{
			if(empty($this->_fd))
			{
				reason_include_once('minisite_templates/modules/filter_displayers/'.$this->params['filter_displayer']);
				$class = $GLOBALS['_reason_filter_displayers'][$this->params['filter_displayer']];
				
				if(empty($class) && !class_exists($class))
					trigger_error('The filter displayer specified ('.$this->params['filter_displayer'].') is not properly registered in $GLOBALS[\'_reason_filter_displayers\']['.$this->params['filter_displayer'].']. Please set this to the filter class name.', HIGH);
				
				$this->_fd = new $class();
				$this->_fd->set_module_ref($this);
				$this->_fd->set_head_items();
			}
			else
			{
				return $this->_fd;
			}
		}

		function get_filter_entities()
		{
			if(is_null($this->filter_entities))
			{
				$this->filter_entities = array();
				foreach($this->filter_types as $filter_name=>$filter_type)
				{
					$r_id = false;
					if(empty($filter_type['relationship'])) trigger_error($filter_type['type'].' does not have a relationship name specified');
					else
					{
						$r_id = relationship_id_of($filter_type['relationship']);
						if (!$r_id) trigger_error($filter_type['relationship'] . ' is not a valid allowable relationship');
					}
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
							$es = carl_clone($setup_es);
							$es->add_left_relationship( $filter->id(), $r_id );
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
				ksort($this->filter_entities);
			}
			
			return $this->filter_entities;
			
		}
		function get_filters_from_url()
		{
			if(isset($this->request['filters']))
			{
				$this->_redirect_from_old_url($this->request);
			}
			else
			{
				$this->filters = $this->_convert_url_to_filter_array($this->request);
			}
		}

		function _redirect_from_old_url($request)
		{
			foreach( $this->request as $key => $vals)
			{
				
				if( $key == 'filters')
				{
					foreach($vals as $filter_key => $filter)
					$redirect_params['filter'.$filter_key] = $filter['type'].'-'.$filter['id'];
				} 
				else
				{
					$redirect_params[$key] = $vals;
				}
				
			}
			$redirect_link = carl_construct_redirect($redirect_params);
			header('Location: ' . $redirect_link);
			exit;
		}

		function _convert_url_to_filter_array($request)
		{
			$filters = array();
			$i = 1;
			foreach ($request as $key => $value) 
			{
				if (!empty($value) & substr($key, 0, 6) == "filter")
				{
					$key = explode("-",$value);
					$id = array_pop($key);
					$key = implode("-",$key);
					$filter = array('type'=>$key,'id'=>$id);
					$filters[$i] = $filter;
					$i = $i + 1;
				}
			}
			return $filters;

		}

		function do_filters_rels()
		{
			
			$filter_entities = $this->get_filter_entities();

			foreach($this->filters as $key => $values)
			{
				 if(!isset($filter_entities[$values['type']][$values['id']]))
				 {
					header('HTTP/1.0 404 Not Found');
					unset($this->filters[$key]);
				 }
			}

			if($this->filters)
			{
				if (count($this->filters) > $this->params['max_filters'])
				{
					$redirect_link = carl_make_redirect(array('filters' => ''));
					header('Location: ' . $redirect_link);
					exit;
				}
				foreach($this->filters as $key=>$filter)
				{
					if (!empty($filter['type']) && !empty($filter['id']))
					{
						settype($filter['id'], 'integer'); // force an integer to thwart SQL insertion through query string
						$r_id = 0;
						if(empty($this->filter_types[$filter['type']]['relationship']))
						{
							trigger_error($filter['type'].' does not have a relationship name specified');
							unset($this->filters[$key]);
							header('HTTP/1.0 404 Not Found');
						}
						else
						{
							$r_id = relationship_id_of($this->filter_types[$filter['type']]['relationship']);
						}
						if($r_id)
						{
							$this->_add_filter_rel_to_es($filter['id'],$r_id,$this->es);
						}
						else
						{
							trigger_error($filter['type'].' is not a valid allowable relationship');
							unset($this->filters[$key]);
							header('HTTP/1.0 404 Not Found');
						}
					}
					else
					{
						// filter request is malformed - googlebot may have lots of these - we will send a redirect with no filters
						unset($this->filters[$key]);
						header('HTTP/1.0 404 Not Found');
						exit;
					}
				}
			}
		}
		
		function _add_filter_rel_to_es($item_id,$relationship_id,$es)
		{
			$es->add_left_relationship( $item_id,  $relationship_id);
		}
		function do_filters_search()
		{
			if(!empty($this->request['search']))
			{
				$search_term = $this->request['search'];
				$regexp = '/(?:\"(.+?)\"|([^\*\"\s]+))/';
				preg_match_all($regexp,$search_term,$matches);
				
				$search_term_array = array();
				foreach ($matches[1] as $chunk)
				{
					if (!empty($chunk))	$search_term_array[] = trim($chunk);
				}
				
				foreach ($matches[2] as $chunk)
				{
					if (!empty($chunk))	$search_term_array[] = trim($chunk);
				}
				$search_array = array();
				
				foreach ($search_term_array as $chunk)
				{
					$sub_search_array = array();
					foreach($this->search_fields as $field)
					{
						$table_field = '';
						if(false === strpos($field,'.'))
							$table_field = table_of($field,$this->type);
						if(empty($table_field))
							$table_field = $field;
						$sub_search_array[] = $table_field . ' LIKE "%'.strtr(addslashes($chunk),array('*'=>'%')).'%"';
					}
					$search_array[] = '('.implode(' OR ',$sub_search_array).')';
				}
				if (!empty($search_array))
				{
					$this->es->add_relation('('.implode(' AND ', $search_array).')');
				}
			}
			foreach($this->allowable_psearch_fields as $psearch_frag => $psearch_data)
			{
				if (!empty($this->request['search_' . $psearch_frag]))
				{
					$psearch_string = str_replace('*','%',addslashes($this->request['search_' . $psearch_frag]));
					$this->es->add_relation('(' . $psearch_data['field'] . ' LIKE "' . $psearch_string . '")');
				}
			}
		}
		
		//Called on by init()
		function do_pagination()
		{
//			$this->total_count = $this->es->get_one_count();
			if(empty($this->request['page']))
				$this->request['page'] = 1;
//			$this->es->set_start( $this->num_per_page * ( $this->request['page'] - 1 ) );
//			$this->es->set_num( $this->num_per_page );

			$pagination_ids = array();
			$pagination_ids = array_slice($this->position_to_ids, $this->num_per_page * ( $this->request['page'] - 1 ) , $this->num_per_page  );
			$pagination_ids_string = 'entity.id IN ("'.implode('","',$pagination_ids) . '")';
			$this->es->add_relation($pagination_ids_string);
		}
		
		//Called on by list_items
		function show_pagination($class = '')
		{
			if($this->use_pagination && ( $this->show_list_with_details || empty( $this->current_item_id ) ) )
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
			
			
			foreach($this->filters as $key=>$values)
			{
				$this->build_default_links($key);
			}
			
			if(!empty($GLOBALS['_reason_filter_displayers'][$this->params['filter_displayer']]))
			{
				$fd = $this->get_filter_displayer();
				$fd->set_type($this->type);
				$fd->set_filter_types($this->filter_types);
				$fd->set_filters($this->filters);
				$fd->set_textonly($this->parent->textonly);
				$fd->set_search_field_size($this->search_field_size);
				$fd->set_max_filters($this->params['max_filters']);
				if(!empty($this->request['search']))
				{
					$fd->set_search_value($this->request['search']);
				}
				foreach($this->allowable_psearch_fields as $psearch_frag => $psearch_data)
				{
					if (!empty($this->request['search_' . $psearch_frag]))
					{
						$fd->set_power_search_value($psearch_frag, $this->request['search_' . $psearch_frag]);
					}
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
			$keyquot = htmlspecialchars($key,ENT_QUOTES,'UTF-8');
			$this->default_links[$key] = 'filter'.$keyquot.'='.htmlspecialchars($this->filters[$key]['type'],ENT_QUOTES,'UTF-8').'-'.htmlspecialchars($this->filters[$key]['id'],ENT_QUOTES,'UTF-8');
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
				if(empty( $this->current_item_id ) )
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
					$id = $this->current_item_id;
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
		
		
		/**
		*  Make the position_to_ids and id_to_positions arrays
		*  Gets called during init, after the run_ones
		*/
		function refine_ids_and_positions_arrays()
		{
			$this->position_to_ids = array_keys($this->ids);
			
			// We want to make the first element 1 not 0, so we
			// put a dummy element on the begining of the array
			// then remove it
			array_unshift($this->position_to_ids, 'placeholder');
			unset($this->position_to_ids[0]);
			$this->id_to_positions = array_flip($this->position_to_ids);
			$this->total_count = count($this->ids);
		}
		
		/**
		*  Takes an id and returns it's position in the list
		*  of items
		*/
		function get_item_position($item_id)
		{
			if (!empty($this->id_to_positions[$item_id]))
				return $this->id_to_positions[$item_id];
			else
				return false;
		}

		/**
		*  Gets the id of the next item after the given id
		*/
		function get_next_item_id($item_id)
		{
			if ($this->get_item_position($item_id) && !empty($this->position_to_ids[$this->get_item_position($item_id)+1]) )
				return $this->position_to_ids[$this->get_item_position($item_id)+1];
			else
				return false;
		}
		
		/**
		 * Get the next item as an entity
		 */
		function get_next_item($item_id)
		{
			$ret = false;
			if ($next_id = $this->get_next_item_id($item_id))
			{
				$ret = (isset($this->items[$next_id])) ? $this->items[$next_id] : new entity($next_id);
			}
			return $ret;
		}

		/**
		*  Gets the id of the previous item after the given id
		*/
		function get_previous_item_id($item_id)
		{
			if ($this->get_item_position($item_id) && !empty($this->position_to_ids[$this->get_item_position($item_id)-1]) )
				return $this->position_to_ids[$this->get_item_position($item_id)-1];
			else
				return false;
		}
		
		/**
		 * Get the previous item as an entity
		 */
		function get_previous_item($item_id)
		{
			$ret = false;
			if ($prev_id = $this->get_previous_item_id($item_id))
			{
				$ret = (isset($this->items[$prev_id])) ? $this->items[$prev_id] : new entity($prev_id);
			}
			return $ret;
		}
		
		
		/**
		*  Takes an id and returns what page it would appear on
		*  if pagination is used
		*/
		function get_page_number_from_id($item_id)
		{
			if ($this->get_item_position($item_id))
			{
				if ($this->use_pagination)
					return (integer) floor(($this->get_item_position($item_id)-1)/$this->num_per_page)+1;
				else
					return false;
			}
			return false;
		}
		
	}
?>
