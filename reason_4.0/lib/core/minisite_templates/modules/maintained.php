<?php
/**
 * @package reason
 * @subpackage minisite_templates
 */
	
	/**
	 * Include parent class & dependencies; register module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'minisite_templates/nav_classes/default.php' );
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MaintainedModule';
	
	/**
	 * A minisite module that displays information about who maintains the site & when the page was last updated
	 *
	 * Note that this module uses the Reason site entity to cache the contact information of the primary maintainer.
	 * It does this to avoid potentially expensive directory service lookups on each page hit.
	 *
	 * This cache is renewed each day at 7:00 am, so if the underlying directory information changes during the day,
	 * it won't show up until 7:00 am the next morning.
	 *
	 * @todo Rework to remove reference to the template
	 * @todo Figure out how to reliably report on last modified times if this module is not run last
	 * @todo Use standard Reason caching instead of writing to the site entity
	 * @todo Allow setting of social media view
	 */
	class MaintainedModule extends DefaultMinisiteModule
	{
		var $last_mod_date_format = 'j F Y';
		var $_content;

		function _get_content()
		{
			$format = $this->_get_format();
			if(!empty($format))
			{
				$replacements = array('[[sitename]]'=>'_get_site_name_html','[[maintainer]]'=>'_get_maintainer_html','[[lastmodified]]'=>'_get_last_modified_date_html','[[socialmedialinks]]'=>'_get_social_media_links_html',);
				foreach($replacements as $search=>$function)
				{
					if(strpos($format,$search) === false)
					{
						unset($replacements[$search]);
					}
					else
					{
						$replacements[$search] = $this->$replacements[$search]();
					}
				}
				return str_replace(array_keys($replacements),array_values($replacements),$format);
			}
			return '';
		}
		function has_content()
		{
			if( $this->_get_format() )
				return true;
			else
				return false;
		}
		// Create a footer for a page with an e-mail link to the maintainer and the date it was last modified
		// [updated by footeb on 7/2/03]
		function run()
		{
			$content = $this->_get_content();
			if(!empty($content))
			{
				echo '<div id="maintained">'."\n";
				echo $content;
				echo '</div>'."\n";
			}
		}
		function _get_format()
		{
			$format = '';
			if($this->parent->site_info->get_value('use_custom_footer') == 'yes')
			{
				$format = $this->parent->site_info->get_value('custom_footer');
			}
			else
			{
				if(defined('REASON_DEFAULT_FOOTER_XHTML'))
				{
					$format = REASON_DEFAULT_FOOTER_XHTML;
				}
				else
				{
					trigger_error('REASON_DEFAULT_FOOTER_XHTML needs to be defined in settings/reason_settings.php. Please follow the instructions in the Reason 4 beta 6->beta 7 upgrade script.');
					$format = '<div id="maintainer">[[sitename]] pages maintained by [[maintainer]]</div><div id="lastUpdated">This page was last updated on [[lastmodified]]</div>';
				}
			}
			return $format;
		}
		function _get_site_name_html()
		{
			return $this->parent->site_info->get_value('name');
		}
		function _get_maintainer_html()
		{
			$html = '';
			// check for a maintainer--only go forward if there is one
			$maintainer = $this->parent->site_info->get_value('primary_maintainer');
			if( !empty($maintainer) )
			{
				
				$maintainer_info = $this->_get_maintainer_info($maintainer);
				if (!empty($maintainer_info['full_name']))
				{
					if(!empty($maintainer_info['email']))
						$html = '<a href="mailto:'.htmlspecialchars($maintainer_info['email'],ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($maintainer_info['full_name'],ENT_QUOTES,'UTF-8').'</a>';
					else
						$html = $maintainer_info['full_name'];
				}
				else
				{
					trigger_error('Could not identify site maintainer - check to make sure username - ' . $maintainer . ' - is valid', E_USER_NOTICE);
					$html = $maintainer;
				}
			}
			return $html;
		}
		/**
		 * @todo generalize the method of determining the re-check time
		 * @todo move errors to this function, and only produce one error per site per day
		 * @todo send email instead of erroring if site maintainer can't be found
		 */
		function _get_maintainer_info($maintainer)
		{
			$email = $full_name = '';
		
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
					// lets fall back to the maintainer username if a valid full name is not found for the user
					$full_name = (!carl_empty_html($full_name)) ? $full_name : trim(strip_tags($maintainer));
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
			return array('email'=>$email,'full_name'=>$full_name);
		}
		function _get_last_modified_date_html()
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
			
			return prettify_mysql_timestamp( $date, $this->last_mod_date_format );
		}
		function _get_social_media_links_html()
		{
			reason_include_once('minisite_templates/modules/social_account/models/profile_links.php');
			reason_include_once('minisite_templates/modules/social_account/views/profile_links.php');
			
			/** instantiate and setup the model **/
			$model = new ReasonSocialProfileLinksModel;
			$model->config('site_id', $this->site_id);

			/** instantiate the view **/
			$view = new ReasonSocialProfileLinksView;

			/** setup and run the controller **/
			$controller = new ReasonMVCController($model, $view);
			return $controller->run();
		}
		function get_documentation()
		{
			return '<p>Provides contact information for the primary maintainer of the site and displays the date that the most recently modified item on the page was edited</p>';
		}
	}
?>
