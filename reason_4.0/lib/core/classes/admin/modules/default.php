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
		
		/**
		 * We do not by default do CSRF protection in the admin interface right now, but we enable it for certain modules.
		 *
		 * This is why:
		 *
		 * 1. Disco has built in CSRF protection. Many admin modules get CSRF protection via disco.
		 * 2. Sharing and borrowing happen in response to GET links and do not get Disco CSRF protection.
		 * 3. OWASP maintains that having a URL in a token is better, as a temporary measure, than not doing it.
		 *
		 * We use a different token than Disco since this is really just a temporary measure.
		 */
		var $check_admin_token = false;
		
		function DefaultModule( &$page )
		{
			$this->admin_page =& $page;
		}
		
		function check_admin_token()
		{
			return $this->check_admin_token;
		}
		
		/**
		 * Some modules (like doAssociate) perform their actions during init so we don't want to run the standard init if CSRF failed.
		 */
		function init_invalid_admin_token()
		{
		
		}
		
		/**
		 * Lets provide a standard message (and a link back if we have a referrer header).
		 */
		function run_invalid_admin_token()
		{
			echo '<p>Your request could not be verified. Please return to the original page and try your request again.</p>';
			if (!empty($_SERVER['HTTP_REFERER']))
			{
				echo '<a href="'.$_SERVER['HTTP_REFERER'].'">Return to original page</a>';
			}
		}
		
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
			$this->admin_page->title = 'Welcome to Reason '. reason_get_version() .'!';
		} // }}}
		
		function set_head_items(&$head_items)
		{
			$this->head_items =& $head_items;
		}
		
		/**
		 * If true, the admin_page will call run_api instead of the normal run methods.
		 *
		 * Typically this method would examine the request to decide if the API should be run.
		 *
		 * @return boolean default false
		 */
		function should_run_api()
		{
			return false;
		}
		
		/**
		 * By default we run an API and do not set any content which should return a 404.
		 */
		function run_api()
		{
			$api = new CarlUtilAPI('html');
			$api->run();
			exit();
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
			if((!defined('REASON_DISABLE_AUTO_UPDATE_CHECK') || !REASON_DISABLE_AUTO_UPDATE_CHECK) && reason_user_has_privs($this->admin_page->user_id, 'upgrade'))
			{
				reason_include_once('classes/version_check.php');
				$vc = new reasonVersionCheck;
				$resp = $vc->check();
				switch($resp['code'])
				{
					case 'version_out_of_date':
						echo '<div class="versionUpdateNotice">'.htmlspecialchars($resp['message'], ENT_QUOTES);
						if(!empty($resp['url']))
							echo ' <a href="'.htmlspecialchars($resp['url'], ENT_QUOTES).'">Link</a>';
						echo '</div>'."\n";
						break;
					case 'no_version_provided':
					case 'version_not_recognized':
						trigger_error('Error checking version: '.$resp['message']);
						break;
				}
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
