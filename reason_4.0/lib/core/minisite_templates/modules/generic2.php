<?php

// Generic module 2.0; improved 3/2004; searching and filtering added 7/2004 -- mr

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'Generic2Module';
	reason_include_once( 'minisite_templates/modules/default.php' );

	class Generic2Module extends DefaultMinisiteModule
	{
		var $type_unique_name = '';
		var $style_string = 'generic';
		var $other_items = 'Other Items';
		var $plural_type_name = 'items';
		var $es; //entity selector
		var $module_title = '';
		var $module_title_level = 3;
		var $items = array();
		var $query_string_frag = 'item';
		var $use_filters = false;
		var $filter_types = array();
		var $filters = array();
		var $filter_entities = array();
		var $search_fields = array('entity.name');
		var $cleanup_rules = array(
			'filters' => array('function' => 'turn_into_array'),
			'search' => array('function' => 'turn_into_string')
		);
		var $default_links = array();
		var $no_items_text = 'There are no items available on this site.';
		var $acceptable_params = array(
			'limit_to_current_site'=>true,
		);
		
		function init( $args ) // {{{
		{
			$error = 'Your class needs to have a type id.  Please overload the set_type() function and '.
					 'include a line such as $this->type = id_of( "something" ) to run this module.';
			parent::init( $args );
			$this->set_type();
			if( empty( $this->type ) )
				trigger_error( $error , E_USER_ERROR );
			if($this->params['limit_to_current_site'])
			{
				$this->es = new entity_selector( $this->parent->site_id );
			}
			else
			{
				$this->es = new entity_selector();
			}
			$this->es->add_type( $this->type );
			$this->alter_es();
			if($this->use_filters)
				$this->do_filtering();
			$this->items = $this->es->run_one();
			if( count( $this->items ) > 1 )
			{
				if( !empty( $this->request[ $this->query_string_frag.'_id' ] ) )
					foreach( $this->items AS $item )
						if( $item->id() == $this->request[ $this->query_string_frag.'_id' ] ) 
						{
							$this->parent->add_crumb( $item->get_value( 'name' ) );
						}
			}
			else
			{
				reset( $this->items );
				$cur = current( $this->items );
				if( $cur )
				{
					$this->parent->add_crumb( $cur->get_value( 'name' ) );
				}
			}
		} // }}}
		function get_cleanup_rules()
		{
			$this->cleanup_rules[$this->query_string_frag . '_id'] = array('function' => 'turn_into_int');
			return $this->cleanup_rules;
		}
		function run() // {{{
		{
			echo '<div id="'.$this->style_string.'">';
			if(!empty($this->module_title))
				echo '<h'.$this->module_title_level.'>'.$this->module_title.'</h'.$this->module_title_level.'>'."\n";
			if($this->use_filters)
			{
				echo '<div id="filtering">'."\n";
				$this->show_filtering();
				echo '</div>'."\n";
			}
			if( count( $this->items ) < 2)
			{
				reset( $this->items );
				$cur = current( $this->items );
				if( $cur )
					$this->_show_item( $cur->id() );
			}
			elseif(!empty( $this->request[ $this->query_string_frag.'_id' ] ) )
			{
				$this->_show_item( $this->request[ $this->query_string_frag.'_id' ] );
			}
			$this->list_items();
			echo '</div>'."\n";
		} // }}}

		function _show_item( $id ) // {{{
		{
			$this->show_item_name( $this->items[$id] );
			$this->show_item_content( $this->items[$id] );
		} // }}}
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
			
			if(!empty($this->items))
			{
				if(count($this->items) != 1) // if there is only one item, it will be shown automatically
				{
					echo '<ul>';
					foreach( $this->items AS $item )
					{
						$this->show_list_item( $item );
					}
					echo '</ul>';
				}
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
			
			echo '</div>'."\n";
		} // }}}

		function set_type() // {{{
		{
			if(!empty($this->type_unique_name))
				$this->type = id_of( $this->type_unique_name );
		} // }}}
		function alter_es() // {{{
		{
		} // }}}
		function show_list_item( $item ) // {{{
		{
			
			echo '<li><strong>';
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
		} // }}}
		function construct_link($item)
		{
			$link = '?'.$this->query_string_frag.'_id=' . $item->id();
			if (!empty($this->parent->textonly))
				$link .= '&amp;textonly=1';
			if($this->use_filters)
			{
				foreach($this->filters as $key=>$vals)
				{
					$link .= '&amp;filters['.$key.'][type]='.$vals['type'];
					$link .= '&amp;filters['.$key.'][id]='.$vals['id'];
				}
				if(!empty($this->request['search']))
					$link .= '&amp;search='.urlencode($this->request['search']);
			}
			return $link;
		}
		function show_list_item_name( $item )
		{
			echo $item->get_value( 'name' );
		}
		function show_list_item_desc( $item )
		{
			if($item->get_value('description'))
				echo '<div>'.$item->get_value('description').'</div>';
		}
		function show_item_name( $item ) // {{{
		{
			echo '<h3>' . $item->get_value( 'name' ) . '</h3>'."\n";
		} // }}}
		function show_item_content( $item ) // {{{
		{
			echo '<div>' . $item->get_value( 'content' ) . '</div>'."\n";
		} // }}}
		function show_back() // {{{
		{
			$link = '?';
			if (!empty($this->parent->textonly))
				$link .= 'textonly=1';
			echo '<a href="'.$link.'">Back to List</a>';
		} // }}}
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
		function show_filtering()
		{
	?><script language="JavaScript">
	<!--
		function MM_jumpMenu(targ,selObj,restore){ //v3.0
		  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
		  if (restore) selObj.selectedIndex=0;
		}
	//-->
	</script>
	<?php
			foreach($this->filter_types as $filter_name=>$filter_type)
			{
				$es = new entity_selector($this->parent->site_id);
				$es->add_type(id_of($filter_type['type']));
				$es->set_order('entity.name ASC');
				$this->filter_entities[$filter_name] = $es->run_one();
			}
			ksort($this->filters);
			foreach($this->filters as $key=>$values)
			{
				$this->build_default_links($key);
			}
			if(!empty($this->request['search']))
				$v = htmlspecialchars( $this->request['search'], ENT_COMPAT, 'UTF-8');
			else
				$v = '';
			//echo '</form>'."\n";
			echo '<form method="get">'."\n";
			foreach($this->filters as $key=>$vals)
			{
				echo '<input type="hidden" name="filters['.$key.'][type]" value="'.$vals['type'].'">';
				echo '<input type="hidden" name="filters['.$key.'][id]" value="'.$vals['id'].'">';
			}
			if (!empty($this->parent->textonly))
				echo '<input type="hidden" name="textonly" value="1">';
			echo 'Search: <input name="search" value="'.$v.'" />'."\n";
			echo ' <input name="go" type="submit" value="Go">'."\n";
			if(!empty($this->request['search']))
			{
				$link = '?';
				if(!empty($this->default_links))
					$link .= implode('&amp;', $this->default_links);
				if (!empty($this->parent->textonly))
					$link .= '&amp;textonly=1';
				echo ' <a href="'.$link.'" title="Remove this search term">Remove</a>'."\n";
			}
			echo '</form>'."\n";
			
			echo '<form method="get">'."\n";
			if(!empty($this->filter_types))
			{
				if(count($this->filter_types) != 1)
					echo 'Browse by '.str_replace('_',' ',implode('/',array_keys($this->filter_types))).':'."\n";
				foreach($this->filters as $key=>$values)
				{
					$this->show_filter_set($key);
				}
				if(!empty($this->filters))
				{
					$filts = $this->filters;
					krsort($filts);
					reset($filts);
					$top_filter_key = key($filts);
				}
				else
					$top_filter_key = 0;
				$next_filter_key = $top_filter_key + 1;
				$this->show_filter_set($next_filter_key);
			}
			echo '</form>'."\n";
			/*if(!empty($this->filter_types)  && (!empty($this->filters) || !empty($this->request['search'])))
			{
				$link = '?';
				if (!empty($this->parent->textonly))
					$link .= '&amp;textonly=1';
				echo '<div><a href="'.$link.'">Clear all</a></div>'."\n";
			}*/
		}
		function show_filter_set($key)
		{
			$other_filter_links = $this->default_links;
			unset($other_filter_links[$key]);
			$combined_other_filter_links = implode('&amp;',$other_filter_links);
		
			echo '<div>';
			echo '<select name="filter_'.$key.'" onChange="MM_jumpMenu(\'parent\',this,0)">'."\n";
			if(empty($this->filters[$key]))
			{
				if(empty($this->filters))
				{
					if(count($this->filter_types) == 1)
						echo '<option value="">Browse by '.current(array_keys($this->filter_types)).':</option>'."\n";
					else
						echo '<option value="">Focus on...</option>'."\n";
				}
				else
					echo '<option value="">Add focus...</option>'."\n";
				echo '<option value=""></option>'."\n";
			}
			foreach($this->filter_types as $filter_name=>$filter_type)
			{
				if(!empty($this->filter_entities[$filter_name]))
				{
					if(count($this->filter_types) != 1)
						echo '<option value="" class="type">'.prettify_string($filter_name).'</option>'."\n";
					foreach($this->filter_entities[$filter_name] as $entity)
					{
						$link = '?';
						if(!empty($other_filter_links))
							$link .= $combined_other_filter_links.'&amp;';
						if(!empty($this->request['search']))
							$link .= 'search='.urlencode($this->request['search']).'&amp;';
						$link .= 'filters['.$key.'][type]='.$filter_name.'&amp;filters['.$key.'][id]='.$entity->id();
						if (!empty($this->parent->textonly))
							$link .= '&amp;textonly=1';
						if(!empty($this->filters[$key]) && $this->filters[$key]['type'] == $filter_name && $this->filters[$key]['id'] == $entity->id())
							$add = ' selected="selected"';
						else
							$add = '';
						echo '<option value="'.$link.'"'.$add.'> - '.$entity->get_value('name').'</option>'."\n";
					}
					echo '<option value=""></option>'."\n";
				}
			}
			echo '</select>'."\n";
			if(!empty($this->filters[$key]))
			{
				$link = '?';
				if(!empty($this->request['search']))
					$link .= 'search='.urlencode($this->request['search']).'&amp;';
				if(!empty($other_filter_links))
					$link .= $combined_other_filter_links;
				echo ' <a href="'.$link.'" title="Remove this filter">Remove</a>'."\n";
			}
			echo '</div>'."\n";
		}
		function build_default_links($key)
		{
			$this->default_links[$key] = 'filters['.$key.'][type]='.$this->filters[$key]['type'].'&amp;filters['.$key.'][id]='.$this->filters[$key]['id'];
	}
	}
?>
