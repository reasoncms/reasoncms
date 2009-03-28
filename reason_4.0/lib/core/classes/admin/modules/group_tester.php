<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once ( 'classes/group_helper.php' );
	include_once( DISCO_INC . 'disco.php');
	/**
	 * Thor Data Manager Module
	 */
	
	class GroupTesterModule extends DefaultModule // {{{
	{
		var $group;
		var $acceptable_paramaters = array('username_to_check' => array('function' => 'turn_into_string'));
		
		function GroupTesterModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		/**
		 * Standard Module init function
		 * 
		 * @return void
		 */
		function init() // {{{
		{
			parent::init();
			if(!empty($this->admin_page->id))
			{
				$this->group = new entity( $this->admin_page->id );
			}
			if(empty($this->group) || $this->group->get_value('type') != id_of('group_type'))
			{
				trigger_error('Group Tester Module run on a non-group entity',EMERGENCY);
				die();
			}
			$this->admin_page->title = 'Group tester for ' . $this->group->get_value('name');
		} // }}}
		/**
		 * Run the group tester
		 * 
		 * @return void
		 */
		function run() // {{{
		{
			echo '<p>Use this form to see if a particular username will be part of this group</p>'."\n";
			$tester = new Disco();
			$tester->init(true);
			$tester->add_element('username_to_check','text', array('display_name'=>' '));
			$tester->actions = array('test'=>'Test this username');
			$tester->run();
			
			if (!empty($this->admin_page->request['username_to_check']))
			{
				$username = $this->admin_page->request['username_to_check'];
				$gh = new group_helper();
				$gh->set_group_by_entity($this->group);
				if($gh->is_username_member_of_group($username))
				{
					echo '<p><strong>Yes</strong>, '.$username.' is a member of this group.</p>'."\n";
				}
				else
				{
					echo '<p><strong>No</strong>, '.$username.' is not a member of this group.</p>'."\n";
				}
			}
			echo '<p><a href="'.$this->admin_page->make_link( array( 'cur_module' => 'Editor', 'mode' => '' )).'">Return to editing the group</a>';
		}
		
	} // }}}
?>
