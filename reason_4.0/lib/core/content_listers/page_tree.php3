<?php
/**
 * @package reason
 * @subpackage content_listers
 */
	/**
	 * Include parent class and register viewer with Reason.
	 */
	reason_include_once( 'content_listers/tree.php3' );
	
	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'page_tree_viewer';
	
	/**
	 * A lister/viewer for types that are hierarchical with only one root per site
	 */
	class page_tree_viewer extends tree_viewer
	{		
		var $columns = array(
						'id' => true,
						'name' => true, 
						'visibility' => 'show_attributes', 
						'last_modified' => 'prettify_mysql_timestamp'
						);

		function do_display()
		{
			//var_dump($this->values);
			parent::do_display();	
		}
		
		function is_open( $id )  // {{{
		{
			return true;
		} // }}}
		
		function show_item_pre($row , &$options)
		{
			if($row->get_value('nav_display') == 'No')
			{
			//	$options['class'] = 'notInNav';
			}
			return parent::show_item_pre($row, $options);
		}
		
		function show_admin_live( $row , $options) // {{{
		{
			if(empty($row))
				return;
			$user = new entity($this->admin_page->user_id);
			
			echo '<td align="left" class="viewerCol_admin"><strong>';
			
			$parts = array();
			if( !$row->get_value('url') && $row->user_can_edit_relationship(get_parent_allowable_relationship_id($row->get_value( 'type' )), $user, 'left') )
			{
				$add_child_link = $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'parent_id' => $row->id() , 'id' => '' ),true );
				$parts[] = '<a href="' . $add_child_link . '">Add Child</a>';
			}
			else
			{
				$parts[] = '<span class="disabled">Add Child</span>';
			}
			
			if(reason_site_can_edit_type($this->admin_page->site_id, $this->admin_page->type_id))
			{
				$edit_link = $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'id' => $row->id() ) );
				$edit_block = '<a href="' . $edit_link . '">Edit</a>';
				if($row->has_lock())
				{
					if(reason_user_has_privs($this->admin_page->user_id,'bypass_locks'))
					{
						$edit_block .= ' <img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px_grey_trans.png" alt="Locks applied" width="12" height="12" />';
					}
					elseif( !$row->user_can_edit($user) )
					{
						$edit_block = ' <img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="Locked" width="12" height="12" />';
					}
				}
				$parts[] = $edit_block;
			}
			
			$preview_link = $this->admin_page->make_link(  array( 'cur_module' => 'Preview' , 'id' => $row->id() ) );
			$parts[] = '<a href="' . $preview_link . '">Preview</a>';
			echo implode(' | ',$parts);
			echo '</strong></td>';
		} // }}}
		
		function show_attributes($row)
		{
			$actions = array();
			
			if($row->get_value('nav_display') == 'No')
			{
				$actions[] = '<span class="smallText" title="Not displayed in site navigation">No nav</span>';
			}
			if($row->get_value('indexable') == 0)
			{
				$actions[] = '<span class="smallText" title="Not indexed by search engines">No search</span>';
			}
			
			$rels = $row->get_left_relationships();
			if (!empty($rels['page_to_access_group']))
			{
				$actions[] = '<span class="smallText" title="This page has an access group attached">Restricted</span>';
			}
			
			return join('&sdot;', $actions);
		}
	}
?>
