<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * The default administrative module
  *
  * This module defines the administrative module API, such as it is
  * 
  * Note that this module is *not* abstract -- it is actually also the
  * module that provides messages to newly-logged-in users
  *
  * @todo make this class more abstract, and create a new messages module
  */
	class DefaultModule // {{{
	{
		var $page;
		var $head_items;
		
		function DefaultModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$sites = $this->admin_page->get_sites();
			if( empty ( $this->admin_page->request[ 'cur_module' ] ) && empty( $this->admin_page->site_id ) )
			{
				if( count( $sites ) == 1 )
				{
					foreach( $sites AS $site )
						$link = 'index.php?site_id=' . $site->id();
					if( !empty( $this->admin_page->user_id ) )
						$link .= '&user_id=' . $this->admin_page->user_id;
					header( 'Location: '. $link );
					die();
				}
			}
			$this->admin_page->title = 'Reason '.REASON_VERSION;
		} // }}}
		
		function set_head_items(&$head_items)
		{
			$this->head_items =& $head_items;
		}
		
		function run() // {{{
		{
			echo '<div class="oldBrowserAlert">Notice: Reason works with all browsers.  However, it will look and feel quite a lot nicer if you can use it with a modern, standards-based browser such as Internet Explorer 6+, Mozilla 1.5+, Firefox, Netscape 7, Safari, or Opera.</div>'."\n";
			if(!HTTPS_AVAILABLE && reason_user_has_privs($this->admin_page->user_id, 'upgrade'))
			{
				echo '<div id="securityWarning">'."\n";
				echo '<h3>Security Notice</h3>'."\n";
				echo '<p>This instance of Reason is running <strong>without</strong> https/ssl. This means that credentials and other potentially sensitive information are being sent in the clear. To run Reason with greater security -- and to make this notice go away -- 1) make sure your server is set up to run https and 2) change the setting HTTPS_AVAILABLE to true in settings/package_settings.php.</p>'."\n";
				echo '</div>'."\n";
			}
			if(reason_unique_name_exists('whats_new_in_reason_blurb'))
			{
				$intro = new entity(id_of('whats_new_in_reason_blurb'));
				echo "\n".'<div id="whatsNew">'."\n";
				echo '<h3>'.$intro->get_value('name').'</h3>'."\n";
            	            echo '<p><em>Updated '.prettify_mysql_timestamp($intro->get_value('last_modified'), 'j F Y').'</em></p>'."\n";
				echo $intro->get_value('content');
				echo '</div>'."\n";
			}
		} // }}}
	} // }}}
?>
