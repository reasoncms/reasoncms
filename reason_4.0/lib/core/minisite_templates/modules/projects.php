<?php 
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 	/**
 	 * Include the parent class and register the module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/generic3.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ProjectModule';

	/**
	 * A minisite module that displays the project entities on the current site
	 *
	 * Not sure if this module is sufficiently generalized.
	 */
	class ProjectModule extends Generic3Module
	{
		var $style_string = 'projects';
		var $description_items = array(
			'description'=>'Description',
			'bug_state'=>'Current State',
			'datetime'=>'Deadline',
			'author'=>'Project Lead',
			'project_scale'=>'Scale',
			'project_initiation_date'=>'Initiation Date'
		);
		var $additional_content_items = array('content'=>'Detailed Info', 'url'=>'URL');
		var $task_description_items = array(
			'description'=>'Description',
			'bug_state'=>'Current State',
			'content'=>'Detailed Info',
			'url'=>'URL');
		var $field_handlers = array('datetime'=>'handle_datetime_field',
			'url'=>'handle_url_field',
			'project_initiation_date'=>'handle_init_datetime_field',
		);
		var $other_items = 'Other Projects';
		var $plural_type_name = 'projects';
		var $orderables = array(
			'datetime'=>'dated.datetime ASC, sortable.sort_order ASC',
			'bug_state'=>'bug.bug_state ASC, dated.datetime ASC',
			'author'=>'chunk.author ASC, dated.datetime ASC',
			'priority'=>'sortable.sort_order ASC, dated.datetime ASC', 
			'project_scale'=>'bug.project_scale DESC, dated.datetime ASC', 
			'project_initiation_date'=>'bug.project_initiation_date ASC, dated.datetime ASC');
		var $orderable_texts = array(
			'datetime'=>'Deadline',
			'bug_state'=>'Current State',	
			'author'=>'Project Lead',	
			'priority'=>'Priority Level',
			'project_scale'=>'Scale', 
			'project_initiation_date'=>'Initiation Date');
		var $default_order_key = 'priority';
		var $use_filters = true;
		var $filter_types = array(	'project_type'=>array(	'type'=>'project_type_type',
															'relationship'=>'project_to_project_type',
															),
								'client'=>array(	'type'=>'office_department_type',
													'relationship'=>'project_to_client_office_dept',
												),
							);
		var $search_fields = array('entity.name','chunk.content','meta.description','chunk.author','bug.assigned_to','bug.bug_state');
		var $make_current_page_link_in_nav_when_on_item = true;
		var $jump_to_item_if_only_one_result = false;
		
		function set_type() // This must always be overloaded, or it will crash crash crash. {{{
		{
			$this->type = id_of( 'project' );
		} // }}}
		function get_cleanup_rules()
		{
			$this->cleanup_rules = parent::get_cleanup_rules();
			$this->cleanup_rules['order_projects'] = array('function' => 'check_against_array', 'extra_args' =>array_keys($this->orderables));
			return $this->cleanup_rules;
		}
		function alter_es() // {{{
		{
			$this->es->add_relation('bug.bug_state != "Done" 
AND bug.bug_state != "Cancelled"');
			if(!empty($this->request['order_projects']))
				$order_key = $this->request['order_projects'];
			else
				$order_key = $this->default_order_key;
			
			$this->es->set_order($this->orderables[$order_key]);
			$this->today = date('Y-m-d');
		} // }}}
		function show_list_item_desc( $item, $elements = NULL )
		{
			if(empty($elements))
				$elements = $this->description_items;
			echo '<ul>'."\n";
			foreach($elements as $key=>$phrase)
			{
				if($item->get_value($key))
				{
					if(!empty($this->field_handlers[$key]))
					{
						$func = $this->field_handlers[$key];
						echo '<li><strong>'.$phrase.':</strong> '.$this->$func($item->get_value($key)).'</li>';
					}
					else
						echo '<li><strong>'.$phrase.':</strong> '.$item->get_value($key).'</li>'."\n";
				}
			}
			echo '</ul>'."\n";
		}
		function handle_datetime_field( $datetime )
		{
			if($datetime == '0000-00-00 00:00:00')
				return 'Not currently defined';
			elseif($datetime < $this->today )
				return prettify_mysql_datetime($datetime).'  -- overdue';
			else
				return prettify_mysql_datetime($datetime);
		}
		function handle_init_datetime_field( $datetime )
		{
			if($datetime == '0000-00-00 00:00:00')
				return 'Not entered';
/*			elseif($datetime < $this->today )
				return prettify_mysql_datetime($datetime).'  -- overdue';*/
			else
				return prettify_mysql_datetime($datetime);
		}
		function handle_url_field( $url )
		{
			return '<a href="'.$url.'" title="View relevant web pages">'.$url.'</a>';
		}
		function show_item_content( $item ) // {{{
		{
			$elements = array_merge($this->description_items, $this->additional_content_items);
			
			echo $this->get_edit_project_link($item->id());
			
			$this->show_list_item_desc($item, $elements);
			
			$es = $es = new entity_selector();
			$es->add_type(id_of('office_department_type'));
			$es->add_right_relationship($item->id(), relationship_id_of('project_to_client_office_dept'));
			$es->set_order('entity.name ASC');
			$clients = $es->run_one();
			
			if(!empty($clients))
			{
				echo '<h4>Clients</h4>'."\n";
				echo '<ul>';
				foreach($clients as $client)
				{
					$url_pieces = array();
					$url_pieces[] = 'filters[1][type]=client';
					$url_pieces[] = 'filters[1][id]='.$client->id();
					if(!empty($this->parent->textonly))
					{
						$url_pieces[] = 'textonly='.$this->parent->textonly;
					}
					echo '<li><a href="?'.implode('&amp;',$url_pieces).'">'.$client->get_value('name').'</a></li>';
				}
				echo '</ul>';
			}
			
			$es = $es = new entity_selector();
			$es->add_type(id_of('project_type_type'));
			$es->add_right_relationship($item->id(), relationship_id_of('project_to_project_type'));
			$es->set_order('entity.name ASC');
			$project_types = $es->run_one();
			
			if(!empty($project_types))
			{
				echo '<h4>';
				if(count($project_types) > 1)
				{
					echo 'Project Types';
				}
				else
				{
					echo 'Project Type';
				}
				echo '</h4>'."\n";
				echo '<ul>';
				foreach($project_types as $project_type)
				{
					$url_pieces = array();
					$url_pieces[] = 'filters[1][type]=project_type';
					$url_pieces[] = 'filters[1][id]='.$project_type->id();
					if(!empty($this->parent->textonly))
					{
						$url_pieces[] = 'textonly='.$this->parent->textonly;
					}
					echo '<li><a href="?'.implode('&amp;',$url_pieces).'">'.$project_type->get_value('name').'</a></li>';
				}
				echo '</ul>';
			}
			
			$es = new entity_selector();
			$es->add_type(id_of('bug'));
			$es->add_left_relationship($item->id(), relationship_id_of('bug_to_project'));
			$es->set_order('bug_state ASC');
			$tasks = $es->run_one();
			if(!empty($tasks))
			{
				echo '<h4>Component Tasks</h4>'."\n";
				echo '<ul>'."\n";
				foreach($tasks as $task)
				{
					echo '<li><strong>'.$task->get_value('name').'</strong>'."\n";
					echo '<ul>'."\n";
					foreach($this->task_description_items as $key=>$phrase)
					{
						if($task->get_value($key))
						{
							if(!empty($this->field_handlers[$key]))
							{
								$func = $this->field_handlers[$key];
								echo '<li><strong>'.$phrase.':</strong> '.$this->$func($task->get_value($key)).'</li>';
							}
							else
								echo '<li><strong>'.$phrase.':</strong> '.$task->get_value($key).'</li>'."\n";
						}
					}
					echo '</ul>'."\n";
					echo '</li>'."\n";
				}
				echo '</ul>'."\n";
			}
			
			
			$es = new entity_selector();
			$es->add_type(id_of('image'));
			$es->add_right_relationship($item->id(), relationship_id_of('project_to_image'));
			$images = $es->run_one();
			if(!empty($images))
			{
				echo '<h4>Images</h4>'."\n";
				echo '<ul>'."\n";
				
				$die = false;
				$popup = true;
				$desc = true;
				$show_text = false;
				foreach($images as $image)
				{
					echo '<li>'."\n";
					show_image( $image, $die, $popup, $desc, $show_text, $this->parent->textonly,false );
					echo '</li>'."\n";
				}
				echo '</ul>'."\n";
			}
			
			$es = new entity_selector();
			$es->add_type(id_of('asset'));
			$es->add_right_relationship($item->id(), relationship_id_of('project_to_asset'));
			$assets = $es->run_one();
			if(!empty($assets))
			{
				echo '<h4>Assets</h4>'."\n";
				$site = new entity( $this->parent->site_id );
				reason_include_once( 'function_libraries/asset_functions.php' );
				echo make_assets_list_markup( $assets, $site );
			}
			echo $this->get_edit_project_link($item->id());
		} // }}}
		function get_edit_project_link($item_id)
		{
			return '<p><a href="' . securest_available_protocol() . '://'.REASON_WEB_ADMIN_PATH.'?site_id='.$this->parent->site_id.'&type_id='.id_of( 'project' ).'&id='.$item_id.'">Edit this project</a></p>'."\n";
		}
		function list_items()
		{
			echo '<p id="projectOrdering">';
			echo 'Order by: ';
			$out = array();
			foreach($this->orderables as $key=>$value)
			{
				if(
					(!empty($this->request['order_projects']) && $key == $this->request['order_projects'])
					||
					(empty($this->request['order_projects']) && $key == $this->default_order_key))
				{
					$out[] = '<strong>'.$this->orderable_texts[$key].'</strong> ';
				}
				else
					$out[] = '<a href="?order_projects='.$key.'">'.$this->orderable_texts[$key].'</a> ';
			}
			echo implode(' | ',$out);
			echo '</p>'."\n";
			parent::list_items();
		}
		function construct_link($item, $other_args = array())
		{
			$link = parent::construct_link($item, $other_args);
			if(!empty($this->request['order_projects']))
				$link .= '&amp;order_projects='.$this->request['order_projects'];
			return $link;
		}
		function _show_item( $id ) // {{{
		{
			if(!empty($this->items[$id]))
			{
				echo '<div id="projectDetails">'."\n";
				$this->show_item_name( $this->items[$id] );
				$this->show_item_content( $this->items[$id] );
				echo '</div>'."\n";
			}
		} // }}}
	}

?>
