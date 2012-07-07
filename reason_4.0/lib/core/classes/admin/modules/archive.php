<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	
	/**
	 * Administrative module that provides access to and reversion of entity history
	 */
	class ArchiveModule extends DefaultModule
	{
		var $content_transform = array(
			'form'=>array('thor_content'=>'specialchars'),
			'minisite_page'=>array('extra_head_content'=>'specialchars'),
		);
		var $current;
		var $_current_user;
		var $_locks = array();
		// basic node functionality
		function ArchiveModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY.'archive.css');
			$this->current = new entity( $this->admin_page->id );
			
			$this->_current_user = new entity( $this->admin_page->user_id );

			$this->admin_page->title = 'History of "'.$this->current->get_value('name').'"';

			$this->ignore_fields = array( 'id', 'last_edited_by', 'last_modified', 'creation_date', 'type', 'created_by', 'new', 'state' );

			// get archive relationship id
			$this->rel_id = reason_get_archive_relationship_id($this->admin_page->type_id);

			$es = new entity_selector();
			$es->add_type( $this->admin_page->type_id );
			$es->add_right_relationship( $this->admin_page->id, $this->rel_id );
			$es->set_order( 'last_modified DESC, entity.id DESC' );
			$archived = $es->run_one(false,'Archived');
			
			$this->_locks[$this->current->id()] = array(); // No problem replacing current entity with itself!
			foreach($archived as $archive_id => $archive_entity)
			{
				$this->_locks[$archive_id] = $this->_get_archive_lock_info($archive_entity);
			}

			$history_top = array( $this->current->id() => $this->current );

			$this->history = $history_top + $archived;
			
		} // }}}
		/**
		 * @param object $archive_entity
		 * @return array fields that are locked and different (e.g. should not be changed)
		 */
		function _get_archive_lock_info($archive_entity)
		{
			$locked_fields = array();
			foreach($archive_entity->get_values() as $field_name=>$field_value)
			{
				if(
					$field_value != $this->current->get_value($field_name)
					&&
					!$this->current->user_can_edit_field($field_name, $this->_current_user)
					
				)
				{
					$locked_fields[] = $field_name;
				}
			}
			return $locked_fields;
		}
		function run() // {{{
		{
		/*
			echo '<a href="'.$this->admin_page->make_link( array( 'wfn' => 'edit','id' => $this->admin_page->id )).'">Back to Editing</a><br />';
			echo '<br />';

			echo '<a href="'.$this->admin_page->make_link( array( 'wfn' => 'archive', 'id' => $this->admin_page->id ) ).'">Main Archive</a> ';
			echo '| <a href="'.$this->admin_page->make_link( array( 'wfn' => 'archive', 'id' => $this->admin_page->id, 'archive_page' => 'full_history' ) ).'">Full History</a> ';
			echo '| <a href="'.$this->admin_page->make_link( array( 'wfn' => 'archive', 'id' => $this->admin_page->id, 'archive_page' => 'compare_all' ) ).'">Compare All</a>';
			echo '<br /><br />';
*/
			$archive_page = 'show_compare';
			if(!empty( $this->admin_page->request['archive_page'] ))
			{
				$func_name = 'show_'. (string) $this->admin_page->request['archive_page'];
				if(method_exists($this,$func_name))
					$archive_page = $func_name;
			}

			$this->$archive_page();

		} // }}}

		// archive node internal pages

		function show_archive_main() // {{{
		{
			$current_entity = true;
			foreach( $this->history AS $id => $entity )
			{
				$user = new entity( $entity->get_value('last_edited_by') );
				if($user->get_values())
					$name = $user->get_value('name');
				else
					$name = 'user ID '.$user->id();
				echo '<a href="'.$this->admin_page->make_link( array( 'cur_module' => 'archive', 'id' => $this->admin_page->id, 'archive_id' => $id, 'archive_page' => 'entity') ).'">'.$this->get_archive_name( $id ).'</a> - modified by '.$name.'<br /><br />';
			}
		} // }}}
		function show_full_history() // {{{
		{
			echo '<hr />';
			$current_entity = true;
			foreach( $this->history AS $id => $entity )
			{
				echo '<strong>'.$this->get_archive_name( $id ).'</strong><br />';
				$this->display_entity( $id );
				echo '<hr />';
			}
		} // }}}
		function show_compare_all() // {{{
		{
			$first = true;
			foreach( $this->history AS $id => $entity )
			{
				if( $first )
				{
					$prev = $id;
					$first = false;
				}
				else
				{
					$this->diff_entities( $prev, $id );
					echo '<hr />';
					$prev = $id;
				}
			}
		} // }}}
		function show_compare_most_recent() // {{{
		{
			reset( $this->history );
			list( $a, ) = each( $this->history );
			list( $b, ) = each( $this->history );

			$this->diff_entities( $a, $b );
		} // }}}
		function show_entity() // {{{
		{
			$id = $this->admin_page->request[ 'archive_id' ];

			echo '<table><tr><td valign="top" align="left">';

			$this->display_entity( $id );

			echo '</td><td valign="top" align="left">';


			foreach( $this->history AS $eid => $entity )
			{
				if( $eid != $id )
				{
					echo '<a href="'.$this->admin_page->make_link( array( 'cur_module' => 'archive', 'id' => $this->admin_page->id, 'archive_page' => 'compare', 'archive_a' => $id, 'archive_b' => $eid ) ).'">Compare with '.$this->get_archive_name( $eid ).'</a><br />';
				}
			}

			
			echo '</td></tr></table>';
			echo '<br />';
			echo '<a href="'.$this->admin_page->make_link( array( 'cur_module' => 'archive', 'id' => $this->admin_page->id, 'archive_page' => 'confirm_reinstate', 'archive_id' => $id) ).'">Make this version current</a> - this will make this version live.  The current version will be archived.<br /><br />';
		} // }}}
		function show_confirm_reinstate() // {{{
		{
			if(!isset($this->history[$this->admin_page->request[ 'archive_id' ]]))
			{
				echo '<p>This version was not found. <a href="'.$this->admin_page->make_link( array() ).'">Return</a></p>'."\n";
			}
			elseif(empty($this->_locks[$this->admin_page->request[ 'archive_id' ]]))
			{
			?>
				Reinstating this version (<?php echo $this->get_archive_name( $this->admin_page->request['archive_id'] ); ?>) will change the current item.  Changes made to the most current will be archived and accessible through this very Archive Manager.  So don't worry.<br />
				<br />
				<a href="<?php echo $this->admin_page->make_link( array( 'id' => $this->admin_page->id, 'archive_page' => 'reinstate', 'archive_id' => $this->admin_page->request[ 'archive_id' ]) ) ?>">Confirm</a> | <a href="<?php echo $this->admin_page->make_link( array() ); ?>">Cancel</a><br />
			<?php
			}
			else
			{
				echo '<p>This version cannot be reinstated becase doing so would change a locked field. <a href="'.$this->admin_page->make_link( array() ).'">Return</a></p>'."\n";
			}
		} // }}}
		function show_reinstate() // {{{
		{
			if(!isset($this->history[$this->admin_page->request[ 'archive_id' ]]))
			{
				echo '<p>This version was not found. <a href="'.$this->admin_page->make_link( array() ).'">Return</a></p>'."\n";
			}
			elseif(empty($this->_locks[$this->admin_page->request[ 'archive_id' ]]))
			{
				$id = $this->admin_page->request[ 'archive_id' ];

			$e = new entity( $id );
			$values = $e->get_values();
			$old_id = $this->admin_page->id;
			$old = new entity($old_id);
			$old_values = $old->get_values();
			
			if (isset($values['id'])) unset($values['id']);
			if (isset($values['last_modified'])) unset($values['last_modified']);
			$values[ 'state' ] = 'Live';

				reason_update_entity( $this->admin_page->id, $this->admin_page->user_id, $values );
			
			// if this is a page, lets check a few things - we may have to run rewrites or clear the nav cache
			if ($this->admin_page->type_id == id_of('minisite_page'))
			{
				// do we need to clear the nav cache?
				if ( ($values['url_fragment'] != $old_values['url_fragment']) ||
					 ($values['name'] != $old_values['name']) ||
					 ($values['link_name'] != $old_values['link_name']) ||
					 ($values['nav_display'] != $old_values['nav_display']) ||
					 ($values['sort_order'] != $old_values['sort_order']) ||
					 ($old_values['state'] != 'Live') )
				{
					reason_include_once('classes/object_cache.php');
					$cache = new ReasonObjectCache($this->admin_page->site_id . '_navigation_cache');
					$cache->clear();
				}
				
				// if the page was formerly pending or the url_fragment has changed, run rewrites.
				if ( ($old_values['state'] == 'Pending') ||
				     ($values['url_fragment'] != $old_values['url_fragment']) )
				{
					reason_include_once('classes/url_manager.php');
					$urlm = new url_manager($this->admin_page->site_id);
					$urlm->update_rewrites();
				}
			}
				header( 'Location: '.unhtmlentities($this->admin_page->make_link( array( 'id' => $this->admin_page->id ) ) ) );
				die();
			}
			else
			{
				echo '<p>This version cannot be reinstated becase doing so would change a locked field. <a href="'.$this->admin_page->make_link( array() ).'">Return</a></p>'."\n";
			}
		} // }}}
		function show_compare() // {{{
		{
			$a = !empty( $this->admin_page->request[ 'archive_a' ] ) ? $this->admin_page->request[ 'archive_a' ] : $this->admin_page->id;
			$b = !empty( $this->admin_page->request[ 'archive_b' ] ) ? $this->admin_page->request[ 'archive_b' ] : '';
			$this->diff_entities( $a, $b );
		} // }}}

		// support methods

		function get_archive_name( $id ) // {{{
		{
			$edited_by_id = $this->history[ $id ]->get_value( 'last_edited_by' );
			if(!empty($edited_by_id))
			{
				$user = new entity( $edited_by_id );
				if($user->get_values())
					$name = $user->get_value('name');
				else
					$name = 'user id '.$user->id();
			}
			else
			{
				$name = '[unknown]';
			}
			if( $id == $this->current->id() )
				return 'Current Version - '.$name;
			else
				return prettify_mysql_timestamp($this->history[ $id ]->get_value('last_modified'), 'n/j/y, g:i a') . ' Version - '.$name;
		} // }}}
		function display_entity( $id, $use_ignore_fields = true ) // {{{
		{
			$entity =& $this->history[ $id ];
			$entity_values = $entity->get_values();
			echo '<table border="1" cellpadding="4">';
			foreach( $entity_values AS $key => $val )
			{
				if( !$use_ignore_fields OR !in_array( $key, $this->ignore_fields ) )
				{
					echo '<tr>';
					echo '<td>'.prettify_string( $key ).'</td>';
					echo '<td>'.$val.'</td>';
					echo '</tr>';
				}
			}
			echo '</table>';
		} // }}}
		function diff_entities( $a_id, $b_id, $use_ignore_fields = true ) // {{{
		{
			$a =& $this->history[ $a_id ];
			if( !empty( $b_id ) )
				$b =& $this->history[ $b_id ];
			
			if( $use_ignore_fields )
				$keys = array_diff( array_keys( $a->get_values() ), $this->ignore_fields );
			else
				$keys = array_keys( $a->get_values() );

			if( empty( $b_id ) )
				$compare_or_comparing = 'Compare';
			else
				$compare_or_comparing = 'Comparing';

			$select_form_a = '<form action="?" class="jumpNavigation" name="archive_a_switch" method="get">'.$compare_or_comparing.'
				<select name="archive_a" class="jumpDestination siteMenu" id="archive_a_switch_select">
					';
			$select_form_b = '<form action="?" class="jumpNavigation" name="archive_b_switch" method="get">with
				<select name="archive_b" class="jumpDestination siteMenu" id="archive_b_switch_select">
					<option value="--"'.(empty( $b_id ) ? ' selected="selected"' : '' ).'>--</option>';
			
			foreach( $this->history AS $h )
			{
				$select_form_a .= '<option value="'.$h->id();
				if( $a->id() == $h->id() ) $select_form_a .= '" selected="selected';
				$select_form_a .= '">'. $this->get_archive_name( $h->id() );
				if(!empty($this->_locks[$h->id()]))
					$select_form_a .= ' (locked)';
				$select_form_a .= "</option>\n";
				
				
				$select_form_b .= '<option value="'.$h->id();
				if( !empty( $b_id ) AND $b->id() == $h->id() ) $select_form_b .= '" selected="selected';
				$select_form_b .= '">'. $this->get_archive_name( $h->id() );
				if(!empty($this->_locks[$h->id()]))
					$select_form_b .= ' (locked)';
				$select_form_a .= "</option>\n";
				
			}
			
			ob_start();
			$this->admin_page->echo_hidden_fields('archive_a');
			$archive_a_fields = ob_get_contents();
			ob_end_clean();
			
			ob_start();
			$this->admin_page->echo_hidden_fields('archive_b');
			$archive_b_fields = ob_get_contents();
			ob_end_clean();
			
			$select_form_a .='</select>'.$archive_a_fields.'<input type="submit" class="jumpNavigationGo" value="go"></form>';
			$select_form_b .= '</select>'.$archive_b_fields.'<input type="submit" class="jumpNavigationGo" value="go"></form>';
				

			echo '<table border="0" cellspacing="0" cellpadding="4">';
			echo '<tr>';
			echo '<th class="listHead" align="left">Field</th>';
			echo '<th class="listHead">'.$select_form_a .'</th>';
			echo '<th class="listHead">'.$select_form_b.'</th>';
			echo '</tr>';
			
			$type = new entity($this->admin_page->type_id);
			if(!empty($this->content_transform[$type->get_value('unique_name')]))
			{
				$transformers = $this->content_transform[$type->get_value('unique_name')];
			}
			
			foreach( $keys AS $key )
			{
				$diff = false;
				if( !empty( $b_id ) )
					if( $a->get_value( $key ) != $b->get_value( $key ) )
						$diff = true;

				if( $diff )
					$class = 'highlightRow';
				else
					$class = 'listRow1';
				
				if(
					( !empty($a) && in_array( $key, $this->_locks[$a->id()] ) )
					||
					( !empty($b) && in_array( $key, $this->_locks[$b->id()] ) )
				)
				{
					$class .= ' lockedRow';
					$lock_img = '<img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="locked" width="12" height="12" />';
				}
				else
				{
					$lock_img = '';
				}
				
				echo '<tr>';
				echo '<td class="'.$class.'" valign="top"><strong>'.$lock_img.prettify_string($key).':</strong></td>';
				if(!empty($transformers[$key]))
				{
					$method = $transformers[$key];
					$a_val = $this->$method($a->get_value( $key ));
					if( !empty( $b_id ) )
						$b_val = $this->$method($b->get_value( $key ));
				}
				else
				{
					$a_val = $a->get_value( $key );
					if( !empty( $b_id ) )
						$b_val = $b->get_value( $key );
				}
				echo '<td class="'.$class.'" valign="top"'.(empty( $b_id ) ? ' colspan="2"' : '').'>'.$a_val.'</td>';
				if( !empty( $b_id ) )
					echo '<td class="'.$class.'" valign="top">'.$b_val.'</td>';
				echo '</tr>';
			}
			// only show the make current link if one of the edits is not the current edit
			if( ($a_id != $this->admin_page->id) OR (!empty( $b_id ) AND $b_id != $this->admin_page->id ) )
			{
				echo '<tr>';
				echo '<td class="listRow1">&nbsp</td>';
				if( $a_id != $this->admin_page->id )
				{
					echo '<td class="listRow1"'.(empty($b_id) ? ' colspan="2"' : '').'>';
					if(empty($this->_locks[$a->id()]))
					{
						echo '<a href="'.$this->admin_page->make_link( array( 'archive_page' => 'confirm_reinstate', 'archive_id' => $a_id) ).'">Make this version current</a>';
					}
					else
					{
						echo '<div class="lockNotice">This version cannot be made current because it would change a value that has been locked.</div>'."\n";
					}
					echo '</td>';
				}
				else
					echo '<td class="listRow1">&nbsp;</td>';
				if( !empty( $b_id ) )
				{
					if( $b_id != $this->admin_page->id )
					{
						echo '<td class="listRow1">';
						if(empty($this->_locks[$b->id()]))
						{
							echo '<a href="'.$this->admin_page->make_link( array( 'archive_page' => 'confirm_reinstate', 	'archive_id' => $b_id) ).'">Make this version current</a>';
						}
						else
						{
							echo '<div class="lockNotice">This version cannot be made current because it would change a value that has been locked.</div>'."\n";
						}
						echo '</td>';
					}
					else
					{
						echo '<td class="listRow1">&nbsp;</td>';
					}
				}
				echo '</tr>';
			}
			echo '</table>';
		} // }}}
		function specialchars($value)
		{
			return nl2br(htmlspecialchars($value,ENT_QUOTES,'UTF-8'));
		}
	}
	
?>
