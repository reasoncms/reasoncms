<?php
	
	reason_include_once( 'content_listers/tree.php3' );
	
	///////////////////////////////////////////////////////////////////////////////
	// MAKE SURE THIS VARIABLE IS SET IF OVERLOADING
	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'page_tree_viewer';
	///////////////////////////////////////////////////////////////////////////////
	
	
	class page_tree_viewer extends tree_viewer
	{
		function is_open( $id )  // {{{
		{
			return true;
		} // }}}
		function show_admin_live( $row , $options) // {{{
		{
			echo '<td align="left"><strong>';
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
