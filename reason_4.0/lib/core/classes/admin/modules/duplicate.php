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
	 * Duplicate Module
	 * 
	 * Copies the passed entity into a new entity, minus its relationships
	 */
	class DuplicateModule extends DefaultModule // {{{
	{
		function DuplicateModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		function init() // {{{
		{
			if (!$this->admin_page->id) return false;
		} // }}}
		
		function run() // {{{
		{
			if (($e = new entity( $this->admin_page->id )) && reason_is_entity($e, true))
			{
				$name = 'Copy of '.$e->get_value('name');
				if ($new_entity_id = duplicate_entity( $this->admin_page->id, false, false, 
					array(	'name'=>$name,
						'unique_name'=>'',
						'last_modified_by'=>$this->admin_page->user_id,
						'state'=>'Pending',
						'new'=>'1' ) ))
				{	
					header( 'Location: '.carl_make_redirect( array('cur_module'=>'Editor', 'id' => $new_entity_id) ) );
				} else {
					echo '<p>Unable to duplicate entity.</p>';
					return false;
				}
			} else {
				echo '<p>Duplicate called with invalid entity ID.</p>';
				return false;
			}
		} // }}}
	}
?>
