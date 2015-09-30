<?php
/**
 * @package reason
 * @subpackage content_listers
 */
	/**
	 * Include parent class and register viewer with Reason.
	 */
	reason_include_once( 'content_listers/default.php3' );
	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'list_no_delete';

	/**
	 * A lister that does not offer the option for deletion
	 *
	 * This class is obsolete, as this is now the default behavior.
	 *
	 * @deprecated
	 */
	class list_no_delete extends generic_viewer
	{
		function show_admin_normal( $row ) // {{{
		{
			echo '<td align="left"><strong>';
			$edit_link = $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'id' => $row->id() ) );

			echo '<a href="' . $edit_link . '">Edit</a>';
			echo '</strong></td>';
		} // }}}
	}
?>
