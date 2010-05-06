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
	 *
	 * Part of the reason it isn't ready for prime time is that it only
	 * modifies the database; if the item has any representation on the
	 * filesystem (like images, assets, sites, etc.) it won't duplicate them;
	 * also, strings that need to be different -- like filenames for assets
	 * or base urls for sites -- are not changed, so the database can be put
	 * into a bad state accidentally.
	 * 
	 * It also duplicates *all* relationships for the item, which is not
	 * always desirable -- for example, if you duplicate an image you might
	 * not want a duplicate of that image to all of a sudden appear on all
	 * the sidebars where the image appeared. Or a site which borrowed the
	 * original will all of a sudden also be borrowing its duplicate. It also
	 * doesn't respect allowable relationship rules, so if the are
	 * one-to-many or many-to-one relationships involved, you might end up
	 * with the db in a bad state -- for example, if you duplicate a group
	 * that is attached to a page, the page will now have two groups attached
	 * to it, which is not supposed to happen.
	 * 
	 * So, it's sort of a loose cannon of an admin module. Until somebody has
	 * the time to make it less dangerous, use it with care. :)
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
					echo '<p>Note that this will not duplicate anything on the filesystem, like images, assets, site folders, etc., and that it might duplicate relationships you might not want duplicated. Use this module with care!</p>'."\n";
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
