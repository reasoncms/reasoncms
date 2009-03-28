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
	 * Cloner Module
	 * A hidden module that only admins can use to clone entities
	 * This will need to be made much more robust if we are to put it out for general consumption
	 * @autho Matt Ryan
	 * @date 2006-05-26
	 */
	class ClonerModule extends DefaultModule // {{{
	{
		function ClonerModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		function run() // {{{
		{
			if(reason_user_has_privs($this->admin_page->user_id, 'duplicate'))
			{
				echo '<h3>Cloning</h3>'."\n";
				if(!empty($this->admin_page->request['clone']) && $this->admin_page->request['clone'] == 'true')
				{
					$new_entity_id = duplicate_entity( $this->admin_page->id, true, false, array('last_modified_by'=>$this->admin_page->user_id,'state'=>'Pending','new'=>'1' ) );
					//echo '<p>new entity successfully cloned from this entity (id #'.$new_entity_id.')<p>';
					echo '<p>Again?</p>';
				}
				else
				{
					echo '<p>This will make a duplicate of the current entity.<p>'."\n";
					echo '<p>Do you want to do that?</p>'."\n";
				}
				echo '<ul><li><a href="'.$this->admin_page->make_link( array( 'clone' => 'true' )).'">Yes</a></li><li><a href="'.$this->admin_page->make_link( array( 'cur_module' => 'Editor' )).'">No</a></li></ul>'."\n";
			}
			else
			{
				echo '<p>Sorry, you do not have cloning privileges</p>'."\n";
			}
		}
	}
?>
