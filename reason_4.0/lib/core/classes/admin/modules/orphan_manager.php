	<?php
/**
* Finds Reason entities that do not belong to a site
*
* @package reason
* @subpackage admin
*/

/**
* Include the default module
*/
reason_include_once('classes/admin/modules/default.php');
include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'function_libraries/relationship_finder.php' );

class reason_orphan_manager
{
	var $_orphans_grabbed = false;
	var $_orphans = array();
	var $_types = array();
	function set_types($types)
	{
		foreach($types as $id)
		{
			$this->_types[$id] = new entity($id);
		}
	}
	function get_orphans($max = NULL,$refresh_cache = false)
	{
		if($refresh_cache || !$this->_orphans_grabbed )
		{
			$e = new entity_selector();
			$e->limit_tables();
			$e->limit_fields();
			if(empty($this->_types))
			{
				$types = $e->run_one(id_of('type'));
			}
			else
			{
				$types = $this->_types;
			}
			$sites = $e->run_one(id_of('site'),'All');

			foreach($types as $type_id=>$type)
			{
				$e = new entity_selector();
				$e->add_type($type_id);
				$e->limit_tables();
				$e->limit_fields();
				$alias = $e->add_right_relationship_field('owns','entity','id','site_id');
				$field = $alias['site_id']['table'].'.'.$alias['site_id']['field'];
				$e->add_relation($field.' IN ("'.implode('","',array_keys($sites)).'")');
				$non_orphans = $e->run_one('', 'All');
				
				$e = new entity_selector();
				$e->add_type($type_id);
				$e->limit_tables(array('entity'));
				$e->limit_fields();
				if(!empty($non_orphans))
				{
					$e->add_relation('entity.id NOT IN ("'.implode('","',array_keys($non_orphans)).'")');
				}
				$orphans = $e->run_one('', 'All');

				if(!empty($orphans))
				{
					$this->_orphans[$type_id] = $orphans;
					if(!empty($max))
					{
						$total = count($this->_orphans[$type_id]);
						$max = $max - $total;
						if($max < 1)
						{
							$length = count($this->_orphans[$type_id]) + $max; 
							$this->_orphans[$type_id] = array_slice($this->_orphans[$type_id],0,$length, true);
							$this->maxed_type = array('type'=>$type_id,'total'=>$total);
							break;
						}
					}
				} 
			}
		}
		return $this->_orphans;
	}
}

class reason_orphan_manager_interactive
{
	var $_om;
	var $_user_id;
	var $_types_to_sites = array();
	function init_om()
	{
		$this->_om = new reason_orphan_manager();
	}
	function set_user_id($user_id)
	{
		$this->_user_id = $user_id;
	}
	function get_orphans($types = NULL,$max = NULL)
	{
		if(empty($this->_om)) $this->init_om();
		$this->_om->set_types($types);
		if($types)
		{
			if(isset($max))
			{
				return $this->_om->get_orphans($max);
			}
			else
			{
				return $this->_om->get_orphans();
			}
		}		
	}
	function move_into_site($orphan_id, $types, $owner_site_id)
	{
		if(empty($this->_user_id))
		{
			trigger_error('Must set user id before calling move_into_site()');
			return false;
		}
		if(!get_owner_site_id( $orphan_id ))
		{
			$owns_rel_id = get_owns_relationship_id($types);
			
			// If there is an existing entry in the relationship table, delete it
			$q = 'DELETE FROM `relationship` WHERE `entity_b` = "'.reason_sql_string_escape($orphan_id).'" AND `type` = "'.reason_sql_string_escape($owns_rel_id).'"';
			$r = db_query($q, 'Unable to delete old owns relationship');
			
			// create new ownership entry
			create_relationship( $owner_site_id, $orphan_id, $owns_rel_id );
			
		}
		else
		{
			trigger_error($orphan_id.' not actually an orphan');
			return false;
		}
	}
	function delete_orphan($orphan_id)
	{
		if(empty($this->_user_id))
		{
			trigger_error('Must set user id before calling delete_orphan()');
			return false;
		}
		if(!get_owner_site_id( $orphan_id ))
		{
			return reason_expunge_entity($orphan_id, $this->_user_id);
		}
		else
		{
			trigger_error($orphan_id.' not actually an orphan');
			return false;
		}
	}
	function get_types_to_sites($types = 0)
	{
		if(empty($this->_user_id))
		{
			trigger_error('Must set user id before requesting get_types_to_sites()');
			return array();
		}
		if(empty($this->_types_to_sites))
		{
			$es = new entity_selector();
			$es->add_type(id_of('site'));
			$es->add_left_relationship_field( 'site_to_type' , 'entity' , 'id' , 'types' );
			$es->enable_multivalue_results();
			$es->set_order('entity.name ASC');
			$sites = $es->run_one();
			$this->_types_to_sites = array();
			foreach($sites as $site_id => $site)
			{
				if($site->get_value('types'))
				{
					foreach($site->get_value('types') as $tid)
					{
						$this->_types_to_sites[$tid][$site_id] = $site;
					}
				}
			}
		}
		if(!empty($types))
		{
			if(isset($this->_types_to_sites[$types]))
			{
				return $this->_types_to_sites[$types];
			}
			else
			{
				return array();
			}

		}
		else
		{
			return $this->_types_to_sites;
		}
	}
	
	function get_maxed_type()
	{
		if(empty($this->_om)) $this->init_om();
		if(isset($this->_om->maxed_type))
		{
			return $this->_om->maxed_type;
		}
		else
		{
			return false;
		}
	}
}

class OrphanManagerModule extends DefaultModule
{
	
	
	function OrphanManagerModule( &$page )
	{
		$this->admin_page =& $page;
	}
	
	function get_all_types()
	{
		$es = new entity_selector();
		$es->set_order('entity.name ASC');
		$types = $es->run_one(id_of('type'));
		return $types;
	}
	
	function init()
	{
		include_once(DISCO_INC.'disco.php');
		reason_include_once( 'function_libraries/user_functions.php' );
		reason_include_once( 'function_libraries/relationship_finder.php' );		
		
		$this->admin_page->title = 'Manage Orphans';
		if( !reason_user_has_privs( $this->admin_page->user_id, 'db_maintenance' ) )
		{
			return;
		}

		$this->admin_page->show['leftbar'] = false;
		
		$this->admin_page->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/reason_admin/orphan_manager.css');
		$this->admin_page->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/orphan_manager.js');

		$this->omi = new reason_orphan_manager_interactive();
		$this->omi->set_user_id($this->admin_page->user_id);
		
		$this->types = $this->get_all_types();
		$this->set_disco_obj($this->types);

		$this->mode = isset($this->admin_page->request['mode']) ? htmlspecialchars($this->admin_page->request['mode'],ENT_QUOTES) : false;

	}

	function set_disco_obj($types)
	{
		
			$d = new disco();

			$types = array();
			foreach($this->types as $type)
			{
				$types = $types + array($type->get_value('id') => $type->get_value('name'));
			}
			
			$d->add_element('types','select_multiple',array('options' => $types));
			$d->set_display_name('types','Types to search:');
			$d->set_comments('types',form_comment('<a id="select_all" href="#all">Select all</a>'));


			$d->add_element('max','radio_inline_no_sort',array('options'=>array('yes'=>'Yes','no'=>'No')));
			$d->set_display_name('max','Limit number of orphans to find:');
			$d->set_comments('max',form_comment('Searching for all orphaned entities can take a while. Limiting the number to find speeds the search.'));
			$d->set_value('max','yes');

			$d->add_element('max_num','text',array('size' => '5'));
			$d->set_display_name('max_num','Maximum number:');
			$d->set_value('max_num','50');

			$d->add_element('cur_module','hidden');
			$d->set_value('cur_module','OrphanManager');

			$d->add_required('types','max');

			$d->add_element('mode','hidden');
			$d->set_value('mode','manager');

			$d->set_actions(array('run'=>'Find Orphaned Entities'));

			function check_phase_callback(&$disco)
			{
				if($disco->successfully_submitted())
				{
					$disco->show_form = false;
				}
			}
			$d->add_callback('check_phase_callback','where_to');
		
			$this->d = $d;
	}
	
	function show_manager()
	{
		$orphans = $this->orphans;
		if(empty($orphans))
		{
			echo '<p>No orphans; nothing to do</p>';
		}
		else
		{
			$maxed_type = $this->omi->get_maxed_type();
			echo '<form method="post">';
			foreach($orphans as $type_id=>$orphans)
			{
				$type = new entity($type_id);
				$plural_name = strip_tags($type->get_value('plural_name'));				
				$name = strip_tags($type->get_value('name'));
				$count_statement = isset($maxed_type) & $type_id == $maxed_type['type'] ? ' of '.$maxed_type['total'] : '';
				$sites = $this->omi->get_types_to_sites($type_id);
				$header_row = '<tr><th class="option">Do Nothing</th><th class="option">Delete</th><th class="option">Move</th><th>Name</th><th>Entity ID</th><th></th></tr>'."\n";

				echo '<div class="orphan_group">';
				echo '<h3>'.$plural_name.' (id# '.$type_id.'; '.count($orphans).$count_statement.' orphans total) </h3>';
				echo '<table summary="Orphans of type '.$name.'" class="orphans" cellpadding="0" cellspacing="0" border="0">';
				echo '<thead>'.$header_row.'</thead>';
				echo '<tbody>';
				$i = 0;
				$amputee_count = 0; 
				foreach($orphans as $oid=>$orphan)
				{
					$orphan = new entity($oid);
					if($orphan->get_values())
					{
						if( $i && !( $i % 16 ) )
							echo $header_row."\n";
						$display_name = ($orphan->get_value('name')) ? $orphan->get_value('name') : '&nbsp;';
						echo '<tr><td><input type="radio" name="orphan['.$type_id.']['.$oid.']" value="" checked="checked" class="nothing"/></td>';
						echo '<td><input type="radio" name="orphan['.$type_id.']['.$oid.']" value="delete" class="delete" /></td>';
						echo '<td><input type="radio" name="orphan['.$type_id.']['.$oid.']" value="move" class="move"/></td>';
						echo '<td>'.$display_name.'</td>'."\n";
						echo '<td>'.$oid.'</td>';
						echo '<td> <a href="index.php?cur_module=EntityInfo&entity_id_test='.$oid.'">Details...</a></td></tr>'."\n";
						$i++;
					}
					else
					{
						$amputee_count = $amputee_count + 1;
					}
				}
				echo '</tbody></table>'."\n";

				if( $amputee_count > 1 )
				{
					echo '<p>There are '.$amputee_count.' orphaned entities that are amputees. To manage these orphans, first run the <a href="/reason_package/reason_4.0/www/scripts/db_maintenance/amputees.php">Fix Amputees</a> script.</p>';
				} 
				else if( $amputee_count == 1 )
				{
					echo '<p>There is 1 orphaned entity that is an amputee. To manage this orphan, first run the <a href="/reason_package/reason_4.0/www/scripts/db_maintenance/amputees.php">Fix Amputees</a> script.</p>';
				}

				if(!empty($sites))
				{
					echo '<p class="mover">Move '.$type->get_value('plural_name').' to: ';
					echo '<select name="type_site_'.$type_id.'">';
					foreach($sites as $site_id=>$site)
					{
						echo '<option value="'.$site_id.'">'.$site->get_value('name').'</option>';
					}
					echo '</select></class>'."\n";
				}
				echo '</div>';
			}
			
			echo '<p><input type="hidden" name="mode" value="process" />';
			echo '<br /><input type="submit" name="" value="Submit" /> </p>';
			echo '</form>';
		}
	}

	function delete_orphan_page_parent($orphaned_page_id)
	{
		$d = new DBSelector;
		$d->add_table('relationship');
		$d->add_field('relationship','entity_a');
		$d->add_field('relationship','entity_b');
		$d->add_field('relationship','id');
		$d->add_relation('relationship.entity_a ='.$orphaned_page_id);
		$d->add_relation('relationship.entity_b ='.$orphaned_page_id);
		$results = current($d->run());
		$id = $results['id'];
		delete_relationship($id);
	}
	
	function process_confirm()
	{
		foreach( $this->admin_page->request['orphan'] as $type_id => $orphans)
		{
			$type_site = htmlspecialchars($this->admin_page->request['type_site_'.$type_id],ENT_QUOTES);
			$sites = $this->omi->get_types_to_sites($type_id);

			foreach( $orphans as $orphan_id => $action)
			{
				$orphan = new entity($orphan_id);

				if(!$orphan->get_owner())
				{
					if($action == 'move' && isset($type_site) && array_key_exists($type_site,$sites))
					{
						if($orphan->get_value('type')==id_of('minisite_page') && $orphan->has_relation_with_entity($orphan_id))
						{
							$this->delete_orphan_page_parent($orphan_id);
						}
						$this->omi->move_into_site($orphan_id, $type_id, $type_site);
						$site = $sites[$type_site];
						echo 'Moved orphan id '.$orphan_id.' into site: '.$site->get_value('name').'<br />';
					}
					else if($action == 'delete')
					{
						$this->omi->delete_orphan($orphan_id);
						echo 'Deleted orphan id '.$orphan_id.'<br />';
					}
				}
			}
			
		}
	}

	function run()
	{
		
		if( !reason_user_has_privs( $this->admin_page->user_id, 'db_maintenance' ) )
		{
			echo 'You do not have the "db_maintenace" privelege necessary to use this tool.';
			return;
		}

		$this->d->run();
		
		if($this->mode == 'process')
		{	
			$this->process_confirm();
		}
		else
		{
			if($this->d->show_form == false)
			{
				if($this->d->get_value('max')=='yes')
				{
					$this->orphans = $this->omi->get_orphans($this->d->get_value('types'),$this->d->get_value('max_num'));

				}
				else
				{
					$this->orphans = $this->omi->get_orphans($this->d->get_value('types'));
				}
				$this->show_manager();
			}
		}
	}
}



?>
