<?php

	/* Major change -- only checks directory service if info cached on the site is old.
	By mryan, Jan. 28 2004  */
	
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'minisite_templates/nav_classes/default.php' );
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MaintainedModule';
	
	function getmicrotime()
	{
		list( $usec, $sec ) = explode( " ", microtime() );
		return ((float)$usec + (float)$sec);
	}

	class MaintainedModule extends DefaultMinisiteModule
	{
		var $last_mod_date_format = 'j F Y';

		function has_content()
		{
			if( $this->parent->cur_page->get_value( 'primary_maintainer' ) OR
				$this->parent->cur_page->get_value( 'last_modified ' ) )
				return true;
			else
				return false;
		}
		// Create a footer for a page with an e-mail link to the maintainer and the date it was last modified
		// [updated by footeb on 7/2/03]
		function run()
		{
			echo '<div id="maintained">'."\n";
			echo '<div id="maintainer">'."\n";
			$this->show_maintainer();
			echo '</div>'."\n";
			echo '<div id="lastUpdated">'."\n";
			$this->show_last_updated();
			echo '</div>'."\n";
			echo '</div>'."\n";
		}
		function show_maintainer()
		{
			// check for a maintainer--only go forward if there is one
			$maintainer = $this->parent->site_info->get_value('primary_maintainer');
			if( !empty($maintainer) )
			{
				// Check to see if it's before or after 7 am, and set the last colleague->ldap sync time appropriately.
				
				if(carl_date('G') < 7) // it's before 7am
				{
					$ldap_last_sync_time = strtotime('7 am yesterday');
				}
				else // it's after 7 am
				{
					$ldap_last_sync_time = strtotime('7 am today');
				}
				
				/*	Either of the following conditions will fire the ldap->reason sync:
					1: the cached info predates the last colleague->ldap sync (presumed to be daily by 7 am.)
					2: the primary maintainer has been changed since the last ldap->reason sync. */
					
				if($this->parent->site_info->get_value('cache_last_updated') <= date('Y-m-d', $ldap_last_sync_time) 
					|| $this->parent->site_info->get_value('username_cache') != $this->parent->site_info->get_value('primary_maintainer') )
				{					
					$dir = new directory_service();
					if ($dir->search_by_attribute('ds_username', $maintainer, array('ds_email','ds_fullname')))
					{
						$email = $dir->get_first_value('ds_email');
						$full_name = $dir->get_first_value('ds_fullname');
					
						$values = array('email_cache'=>$email, 'name_cache'=>$full_name, 'cache_last_updated'=>date('Y-m-d H:i:s'), 'username_cache'=>$maintainer);
						$update_vals = array('ldap_cache'=>$values);
						
						reason_include_once( 'function_libraries/admin_actions.php' );
						
						/* I know this is nonstandard, but it's the only way right now 
						to update the entity without creating an archive and changing 
						the last_updated field on all the sites every day... */
						
						$sqler = new SQLER;
						foreach( $update_vals AS $table => $fields )
						{
							$sqler->update_one( $table, $fields, $this->parent->site_info->id() );
						}
					
					}
				}
				//If info cached on site is new, don't do ldap stuff-just grab off of site info
				else
				{
					$email = $this->parent->site_info->get_value('email_cache');
					$full_name = $this->parent->site_info->get_value('name_cache');
				}
				
				if (!empty($full_name))
				{
					// show footer information
					echo $this->parent->site_info->get_value('name').' pages maintained by ';
					//echo $this->_get_opening_mailto_anchor_tag($maintainer);
					if(!empty($email))
						echo '<a href="mailto:'.htmlspecialchars($email,ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($full_name,ENT_QUOTES,'UTF-8').'</a>';
					else
						echo $full_name;
				}
				else
				{
					trigger_error('Could not identify site maintainer - check to make sure username - ' . $maintainer . ' - is valid');
				}
			}
		}
		function show_last_updated()
		{
			// munge date into a good looking format
			$date = $this->parent->cur_page->get_value('last_modified');

			// ask each module when the entities is contains were most recently modified
			foreach( array_keys($this->parent->_modules) as $key )
			{
				$temp = $this->parent->_modules[$key]->last_modified();
				
				// use the newer date
				if( !empty( $temp ) AND $temp > $date )
					$date = $temp;
			}
			
			echo 'This page was last updated on ' . prettify_mysql_timestamp( $date, $this->last_mod_date_format );
		}
		function get_documentation()
		{
			return '<p>Provides contact information for the primary maintainer of the site and displays the date that the most recently modified item on the page was edited</p>';
		}
	}
?>
