<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'classes/admin/modules/associator.php' );
	reason_include_once( 'function_libraries/images.php' );
	
	/**
	 * An administrative module that displays info about the currently logged-in user
	 */
	class UserInfoModule extends DefaultModule// {{{
	{
		function UserInfoModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$this->admin_page->title = 'Your Information';
			$this->admin_page->set_breadcrumbs( array(''=> 'Your Information' ) );
		} // }}}
		function run() // {{{
		{
			//pray ($this->admin_page->request);
			$user = new entity($this->admin_page->user_id);
			$netid = $user->get_value('name');
			echo '<br />You are logged in as <strong>'.$netid.'</strong><br /><br />';
			$q = 'SELECT count(*) as c FROM entity WHERE last_edited_by = '.$this->admin_page->user_id;
			$r = db_query( $q, 'Unable to grab number of edited items.' );
			$row = mysql_fetch_array( $r, MYSQL_ASSOC );
			mysql_free_result( $r );
			$c = $row['c'];
			echo 'You have last edited '.$c.' item'.(($c != 1) ? 's' : '').'<br /><br />';
		} // }}}
	} // }}}
?>