<?php
/**
 * @package reason
 * @subpackage content_listers
 */
	/**
	 * Include parent class and register viewer with Reason.
	 */
	reason_include_once( 'content_listers/default.php3' );
	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'list_with_duplicate';

	/**
	 * A lister that offers a Duplicate admin function
	 *
	 */
	class list_with_duplicate extends generic_viewer
	{
		function show_admin_live( $row , $options) // {{{
		{
			echo '<td>';
			if(reason_user_has_privs($this->admin_page->user_id,'edit'))
			{
				echo '<strong>';
				$edit_link = $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'id' => $row->id() ) );
				$preview_link = $this->admin_page->make_link(  array( 'cur_module' => 'Preview' , 'id' => $row->id() ) );
				$duplicate_link = $this->admin_page->make_link(  array( 'cur_module' => 'Duplicate' , 'id' => $row->id() ) );
				if (reason_site_can_edit_type($this->admin_page->site_id, $this->admin_page->type_id))
				{
					echo '<a href="' . $preview_link . '">'. 'Preview</a> | <a href="' . $duplicate_link . '">Duplicate</a> | <a href="' . $edit_link . '">Edit</a>';
				}
				else echo '<a href="' . $preview_link . '">'. 'Preview</a>';
				echo '</strong>';
			}
			else
			{
				echo '&nbsp;';
			}
			echo '</td>'."\n";;
		} // }}}
	}
?>
