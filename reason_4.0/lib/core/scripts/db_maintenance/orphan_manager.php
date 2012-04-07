<?php
/**
 * Finds Reason entities that do not belong to a site
 *
 * @package reason
 * @subpackage scripts
 * @todo Add check to crontab (not whole script, as it is interactive)
 */
	include_once( 'reason_header.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	reason_include_once( 'function_libraries/util.php' );
	
	class reason_orphan_manager
	{
		var $_orphans_grabbed = false;
		var $_orphans = array();
		var $_types = array();
		function set_types($type_ids)
		{
			foreach($type_ids as $id)
			{
				$this->_types[$id] = new entity($id);
			}
		}
		function get_orphans($refresh_cache = false)
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
					if(!empty($non_orphans))
					{
						$e->add_relation('entity.id NOT IN ("'.implode('","',array_keys($non_orphans)).'")');
					}
					$orphans = $e->run_one('', 'All');
					if(!empty($orphans))
					{
						$this->_orphans[$type_id] = $e->run_one('', 'All');
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
		function get_orphans($type_ids = NULL)
		{
			if(empty($this->_om)) $this->init_om();
			if($type_ids)
				$this->_om->set_types($type_ids);
			return $this->_om->get_orphans();
		}
		function move_into_site($orphan_id, $type_id, $owner_site_id)
		{
			if(empty($this->_user_id))
			{
				trigger_error('Must set user id before calling move_into_site()');
				return false;
			}
			if(!get_owner_site_id( $orphan_id ))
			{
				$owns_rel_id = get_owns_relationship_id($type_id);
				
				// If there is an existing entry in the relationship table, delete it
				delete_relationships(array('entity_b' => addslashes($orphan_id), 'type' => addslashes($owns_rel_id)));
				
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
		function get_types_to_sites($type_id = 0)
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
				$es->add_left_relationship($this->_user_id, relationship_id_of('site_to_user'));
				$es->add_left_relationship_field( 'site_to_type' , 'entity' , 'id' , 'type_ids' );
				$es->enable_multivalue_results();
				$es->set_order('entity.name ASC');
				$sites = $es->run_one();
				$this->_types_to_sites = array();
				foreach($sites as $site_id => $site)
				{
					if($site->get_value('type_ids'))
					{
						foreach($site->get_value('type_ids') as $tid)
						{
							$this->_types_to_sites[$tid][$site_id] = $site;
						}
					}
				}
			}
			if(!empty($type_id))
			{
				if(isset($this->_types_to_sites[$type_id]))
				{
					return $this->_types_to_sites[$type_id];
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
	}
	
	connectDB( REASON_DB );
	force_secure_if_available();
	$current_user = reason_require_authentication();
	$user_id = get_user_id ( $current_user );
	if (!reason_user_has_privs( $user_id, 'db_maintenance' ) )
	{
		die('<html><head><title>Reason: Manage Orphans</title></head><body><h1>Sorry.</h1><p>You do not have permission to manage orphans.</p><p>Only Reason users who have database maintenance privileges may do that.</p></body></html>');
	}
	?>
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Reason: Manage Orphans</title>
	<style type="text/css">
		table.orphans td, table.orphans th { padding:0.3em; }
		table.orphans td { border-bottom: 1px solid #999; }
		table.orphans th { border-bottom: 2px solid #444; text-align:left; }
		table.orphans tbody th { border-bottom:1px solid #999; color:#666; font-size:0.85em; }
	</style>
	</head>
	<body>
	<h1>Manage Orphans</h1>
	<?php
	if(empty($_POST['do_it']))
	{
	?>
	<form method="post">
	<p>Orphans are Reason entities that do not belong to a site. This script will find any orphans and give you the chance to move them into another site or delete them.</p>
	Type: <select name="type_id"><option name="" selected="selected">All</option>
	<?php
	
	$es = new entity_selector();
	$es->set_order('entity.name ASC');
	foreach($es->run_one(id_of('type')) as $type)
	{
		echo '<option value="'.$type->id().'">';
		echo strip_tags($type->get_value('name'));
		echo '</option>'."\n";
	}
	?>
	</select>
	<input type="submit" name="do_it" value="Find Orphans" />
	</form>
	<?php
	}
	else
	{
		$omi = new reason_orphan_manager_interactive();
		$omi->set_user_id($user_id);
		if(empty($_POST['type_id']))
		{
			$orphans = $omi->get_orphans();
		}
		else
		{
			$type_id = (integer) $_POST['type_id'];
			$orphans = $omi->get_orphans(array($type_id));
		}
		if(empty($orphans))
		{
			echo '<p>No orphans; nothing to do</p>';
		}
		else
		{
			if(empty($_POST['do_it_2']))
			{
				echo '<form method="post">';
				foreach($orphans as $type_id=>$orphans)
				{
					$type = new entity($type_id);
					echo '<h3>'.$type->get_value('plural_name').' (id# '.$type_id.'; '.count($orphans).' orphans total)</h3>';
					$sites = $omi->get_types_to_sites($type_id);
					$options = '<option value="" selected="selected">Do nothing</option>';
					$options .= '<option value="delete">Delete</option>';
					if(!empty($sites))
						$options .= '<option value="move">Move</option>';
					echo '<table summary="Orphans of type '.htmlspecialchars($type->get_value('name'), ENT_QUOTES).'" class="orphans" cellpadding="0" cellspacing="0" border="0">';
					$header_row = '<tr><th>Do Nothing</th><th>Delete</th><th>Move</th><th>Name</th><th>Entity ID</th></tr>';
					echo '<thead>'.$header_row.'</thead>'."\n";
					echo '<tbody>';
					$i = 0;
					foreach($orphans as $oid=>$orphan)
					{
						if( $i && !( $i % 16 ) )
							echo $header_row."\n";
						$display_name = ($orphan->get_display_name()) ? $orphan->get_display_name() : '&nbsp;';
						echo '<tr><td><input type="radio" name="orphan_'.$oid.'" value="" checked="checked" /></td>';
						echo '<td><input type="radio" name="orphan_'.$oid.'" value="delete" /></td>';
						echo '<td><input type="radio" name="orphan_'.$oid.'" value="move" /></td>';
						echo '<td>'.$display_name.'</td>'."\n";
						echo '<td>'.$oid.'</td></tr>'."\n";
						$i++;
					}
					echo '</tbody></table>'."\n";
					if(!empty($sites))
					{
						echo 'Move '.$type->get_value('plural_name').' to: ';
						echo '<select name="type_site_'.$type_id.'">';
						foreach($sites as $site_id=>$site)
						{
							echo '<option value="'.$site_id.'">'.$site->get_value('name').'</option>';
						}
						echo '</select>'."\n";
					}
					
				}
				
				if(!empty($_POST['type_id']))
					echo '<input type="hidden" name="type_id" value="'.htmlspecialchars($_POST['type_id'], ENT_QUOTES).'" />';
				echo '<input type="hidden" name="do_it" value="ok" />';
				echo '<br /><input type="submit" name="do_it_2" value="Submit" />';
				echo '</form>';
			}
			else
			{
				foreach($orphans as $type_id=>$orphans)
				{
					$sites = $omi->get_types_to_sites($type_id);
					foreach($orphans as $oid=>$orphan)
					{
						if(!empty($_POST['orphan_'.$oid]))
						{
							$action = $_POST['orphan_'.$oid];
							if($action == 'delete')
							{
								$omi->delete_orphan($oid);
								echo 'Deleted orphan id '.$oid.'<br />';
							}
							elseif($action == 'move' && isset($_POST['type_site_'.$type_id]) && array_key_exists($_POST['type_site_'.$type_id],$sites))
							{
								$omi->move_into_site($oid, $type_id, $_POST['type_site_'.$type_id]);
								$site = $sites[$_POST['type_site_'.$type_id]];
								echo 'Moved orphan id '.$oid.' into site: '.$site->get_value('name').'<br />';
							}
						}
					}
				}
				echo '</p><a href="?">Back to script</a></p>';
			}
		}
	}
?>
</body>
</html>
