<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	include_once(DISCO_INC.'disco.php');
	
	/**
	 * An administrative module that displays info about the currently logged-in user
	 */
	class ReasonActiveUsersModule extends DefaultModule// {{{
	{
		function ReasonActiveUsersModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$this->admin_page->title = 'Active Users';
		} // }}}
		function run() // {{{
		{
			if(!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data' ))
			{
				echo 'Sorry; you do not have the rights to view this information.';
				return;
			}
			
			// get audiences in REason
			$es = new entity_selector();
			$es->add_type(id_of('audience_type'));
			$audiences = $es->run_one();
			
			$options = array();
			
			foreach ($audiences as $aud)
			{
				$options[$aud->get_value('directory_service_value')] = $aud->get_value('name');
			}
			
			$d = new disco();
			$d->add_element('active_since', 'textdatetime');
			$d->add_element('affiliations', 'checkboxgroup', array('options' => $options));
			$d->set_display_name('affiliations', 'Audiences');
			$d->add_comments('affiliations', form_comment('Leaving these checkboxes blank won\'t filter the results.'));
			
			$d->set_actions(array('run'=>'Run'));
			$d->run();
			
			if($d->get_value('active_since'))
			{	
				$user_ids = $this->_get_active_user_ids($d->get_value('active_since'));
				echo count($user_ids).' Reason users modified at least one item since '.prettify_mysql_datetime($d->get_value('active_since')).'<br />';
								
				if ($d->get_value('affiliations'))
					$affiliations = array_values($d->get_value('affiliations'));	
				else
					$affiliations = array();
				
				
				$users = $this->_get_active_users_from_ids($user_ids, $affiliations);
				echo '<br />'.count($users).' of the above users currently have access to at least one site<br />';

				if(!empty($users))
				{
					echo '<textarea rows="12">'."\n";
					$usernames = array();
					foreach($users as $user)
					{
						$usernames[$user->id()] = $user->get_value('name');
					}
					echo implode(', ',$usernames);
					echo '</textarea>'."\n";
				}
				$emails = $this->_get_email_addresses_from_users($users);
				echo '<br />'.count($emails).' of the users with site access have an email addresses in the directory<br />';
				if(!empty($emails))
				{
					echo '<textarea rows="12">'."\n";
					echo implode(', ',$emails);
					echo '</textarea>'."\n";
				}
			}
		} // }}}
		function _get_active_user_ids($since_datetime)
		{
			if(empty($since_datetime))
			{
				trigger_error('Please provide a date');
				return array();
			}
			
			$q = 'SELECT DISTINCT `last_edited_by` FROM `entity` WHERE `last_modified` >= "'.reason_sql_string_escape($since_datetime).'" AND `type` NOT IN ("'.id_of('comment_type').'","'.id_of('classified_type').'")';
			$r = db_query( $q, 'Unable to get active users' );
			$ids = array();
			while($row = mysql_fetch_array( $r, MYSQL_ASSOC ))
			{
				$ids[] = $row['last_edited_by'];
			}
			mysql_free_result( $r );
			return $ids;
		}
		
		function _get_active_users_from_ids($ids, $affiliations)
		{
			@array_walk($ids,'reason_sql_string_escape');
			
			$es = new entity_selector();
			$es->add_type(id_of('user'));
			$es->enable_multivalue_results();
			$es->add_relation('`entity`.`id` IN ("'.implode('","',$ids).'")');
			$es->add_right_relationship_field('site_to_user','entity','id','site_membership_id'); // This is here so we only grab users who have access to a site
			$users = $es->run_one();
			
			if (!empty($affiliations))
			{
				// Construct a directory service ldap filter query to only include the possible users.
				$dir = new directory_service();
				$filter = $this->_get_ldap_filter($affiliations, $users);
				$dir->search_by_filter($filter);				
				
				$dir_results = $dir->get_records();				
				
				$users = $this->_get_users($users, array_keys($dir_results));
			}
			
			return $users;
		}
		
		function _get_ldap_filter($affiliations, $users)
		{
			$affiliation_chunks = '';
			foreach ($affiliations as $aff)
			{
				$affiliation_chunks .= '(ds_affiliation='.$aff.')';
			}
			$affiliation_clause = '(|'.$affiliation_chunks.')';
			
			$username_chunks = '';
			foreach ($users as $user)
			{
				$username_chunks .= '(ds_username='.$user->get_value('name').')';
			}
			$username_clause = '(|'.$username_chunks.')';
			
			return '(&'.$affiliation_clause.$username_clause.')';
		}
		
		function _get_users($reason_users, $ldap_users)
		{
			$merged = array();
			foreach ($ldap_users as $username)
			{
				$merged[] = new entity(get_user_id($username));
			}
			
			function cmp($a, $b)
			{
				if ($a->get_value('name') == $b->get_value('name')) {
					return 0;
				}
				return ($a->get_value('name') < $b->get_value('name')) ? -1 : 1;
			}
			
			usort($merged, 'cmp');
			return $merged;
		}
		
		function _get_email_addresses_from_users($users)
		{
			$usernames = array();
			foreach($users as $user)
			{
				$usernames[] = $user->get_value('name');
			}
			$dir = new directory_service();
			$dir->search_by_attribute('ds_username',$usernames,array('ds_email'));
			$records = $dir->get_records();
			$emails = array();
			foreach($records as $rec)
			{
				if(isset($rec['ds_username'][0]) && !empty($rec['ds_email'][0]))
				{
					$emails[$rec['ds_username'][0]] = $rec['ds_email'][0];
				}
			}
			
			asort($emails);
			
			return $emails;
		}
	} // }}}
?>