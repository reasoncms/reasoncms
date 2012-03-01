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
		function is_open( $id )  // {{{
		{
			return true;
		} // }}}
		
		function show_item_pre($row , &$options)
		{
			if($row->get_value('nav_display') == 'No')
			{
				$options['class'] = 'notInNav';
			}
			return parent::show_item_pre($row, $options);
		}
		
		function show_admin_live( $row , $options) // {{{
		{
			if(empty($row))
				return;
			echo '<td align="left" class="viewerCol_admin"><strong>';
			$edit_link = $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'id' => $row->id() ) );
			$preview_link = $this->admin_page->make_link(  array( 'cur_module' => 'Preview' , 'id' => $row->id() ) );
			$add_child_link = $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'parent_id' => $row->id() , 'id' => ''/*, 'new_entity' => 1*/ ),true );
			if( !$row->get_value('url') )
			{
				echo '<a href="' . $add_child_link . '">Add Child</a> | ';
			}
			else
			{
				echo '<span class="disabled">Add Child</span> | ';
			}
			echo '<a href="' . $edit_link . '">'. 'Edit</a>';
			echo ' | <a href="' . $preview_link . '">Preview</a>';
			echo '</strong></td>';
		} // }}}
	}
?>
