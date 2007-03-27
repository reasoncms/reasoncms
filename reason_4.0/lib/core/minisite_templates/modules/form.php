<?php

	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'classes/group_helper.php' );
	reason_include_once( 'function_libraries/url_utils.php' );
	include_once( THOR_INC .'thor.php' );
	include_once( THOR_INC .'thor_viewer.php' );
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'FormMinisiteModule';

	class FormMinisiteModule extends DefaultMinisiteModule
	{
		var $user_netID;
		var $access_privileges;
		var $cleanup_rules = array (
				'mode' => array('function' => 'check_against_array', 'extra_args' => array ('data_view')),
				'page' => array('function' => 'turn_into_int'),
				'sort_order' => array('function' => 'check_against_array', 'extra_args' => array('desc', 'asc')),
				'sort_field' => array('function' => 'check_against_regexp', 'extra_args' => array('/^[a-z0-9_]*$/i')),
				'export' => array('function' => 'check_against_array', 'extra_args' => array('csv')),
				'filters' => array('function' => 'turn_into_array'),
				'force_login_disable' => array('function' => 'check_against_array', 'extra_args' => array('true')),
				'clear' => array('function' => 'turn_into_string'));
		var $show_login = false; // will be enabled if viewing groups requiring login are defined, or a database backend is present
		
		var $acceptable_params = array('force_login' => false);
				
		function init( $args )
		{
			if( !on_secure_page() ) //force to secure page
            {
            	header( 'Location: '.get_current_url( 'https' ) );
            	exit;
            }
 			parent::init($args);
 			$es = new entity_selector();
 			$es->description = 'Selecting form to display on a minisite page.';
 			$es->add_type( id_of('form') );
 			$es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_form') );
 			$es->set_num(1);
  			$this->forms = $es->run_one();
  			$this->user_netID = $this->get_authentication();
  			
  			foreach ($this->forms as $form)
  			{
  				// check permissions
  				$id = $form->id();
				$groups_viewing = $this->get_groups_by_relationship($id, 'form_to_authorized_viewing_group');
				$groups_results = $this->get_groups_by_relationship($id, 'form_to_authorized_results_group');

				$this->access_privileges['view'] = empty($groups_viewing) ? true : $this->check_privs($groups_viewing, $this->user_netID);
				$this->access_privileges['data'] = empty($groups_results) ? false : $this->check_privs($groups_results, $this->user_netID);

				if (($this->access_privileges['data'] == false))
				{
					// check if they are in e-mail list for db_backed form in which case they are also given access
					$auth_usernames = (explode(',', $form->get_value( 'email_of_recipient' )));
					if ((!empty($this->user_netID)) && (in_array($this->user_netID, $auth_usernames))) $this->access_privileges['data'] = true; 
				}

				//set login visibility and force login when needed
				if (!empty($groups_viewing) && ($this->check_privs($groups_viewing, '') == false)) // not empty or defined as everybody
				{
					if (empty($this->user_netID) && ($this->params['force_login']) && (empty($this->request['mode'])) && (empty($this->request['force_login_disable'])))
					{
						$dest_page = urlencode(get_current_url());
						header('Location: '.REASON_LOGIN_URL. '?dest_page=' . $dest_page . '&msg_uname=form_login_msg');
						exit();
					}
					$this->show_login = true;
				}
			}
			
			$this->parent->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
			if (($this->access_privileges['data']) && (!empty($this->request['mode'])) && ($this->request['mode'] == 'data_view'))
			{
				$this->parent->add_stylesheet( REASON_HTTP_BASE_PATH.'css/hide_nav.css');
			}
		}
		
		function has_content()
		{
			if (empty($this->forms))
			{
				trigger_error('a page with page type "form" was called, but no form is associated with the page at url ' .get_current_url());
				return false;
			}
			foreach ( $this->forms as $form )
			{
				if ( trim($form->get_value('thor_content')) == '' )
				{
					trigger_error('a page with page type "form" was called, but the thor_content of the form is null at url ' .get_current_url());
					return false;
				}
			}
			return true;
		}
		
		function run()
		{
			echo '<div id="form">'."\n";
			// Display thank you message
			if ( array_key_exists('thor_success', $_REQUEST) )
			{
				foreach ( $this->forms as $form )
				{
					echo $form->get_value('thank_you_message');
				}
				$form_url = str_replace( array('?thor_success=true','&thor_success=true'),'',get_current_url() );
				if ($this->access_privileges['data']  && ($form->get_value('db_flag') == 'yes')) $this->show_data_view_link($form_url);
				if ($form->get_value( 'display_return_link' ) != 'no' ) echo '<p>You may <a href="' . $form_url . '">return to the form</a>.</p>';
				if ($form->get_value( 'show_submitted_data' ) == 'yes' ) $this->show_submitted_data();
			}
			// Display/process form
			else
			{
				foreach ( $this->forms as $form )
				{
					// if data view privs
					if ((!empty($this->request['mode'])) && ($this->request['mode'] == 'data_view') && (empty($this->user_netID)))
					{
						echo '<h3>Access to the form data is restricted</h3>';
						echo '<p>You are not currently logged in. If you have access to the form data, the contents will be displayed after you login.</p>';
						$this->show_login = true;
					}
					elseif (($this->access_privileges['data']) && (!empty($this->request['mode'])) && ($this->request['mode'] == 'data_view'))
					{
						$this->show_login = true;
						echo $this->show_options_link();
						$thor = new Thor_Viewer($form->get_value('thor_content'));
						$thor->set_db_conn(THOR_FORM_DB_CONN, 'form_' . $form->id());
						$thor->set_params(isset($this->request['sort_order']) ? $this->request['sort_order'] : '',
										  isset($this->request['sort_field']) ? $this->request['sort_field'] : '', 
										  isset($this->request['export']) ? $this->request['export'] : '',
										  (isset($this->request['filters']) && !isset($this->request['clear'])) ? $this->request['filters'] : '');
						$thor_data = $thor->build_data();
						if ($thor->has_data() == 0)
						{
							echo '<h3>No Data</h3>';
							echo '<p>There is not yet any stored data associated with the form.</p>';
						}
						elseif (!empty($this->request['export']) && ($this->request['export'] == 'csv'))
						{	
							$filename = $form->id() . '_' .date("Y-m-d");
							$thor->gen_csv($thor_data, ',', true, $filename);
						}
						else
						{
							$thor_viewer_html = $thor->gen_table_html($thor_data);
							echo $thor_viewer_html;
						}
					}
					// if viewing privs
					elseif ($this->access_privileges['view'])
					{
						if ($this->access_privileges['data']  && ($form->get_value('db_flag') == 'yes')) $this->show_data_view_link();
						$confirmation_page = get_current_url();
						if(strstr($confirmation_page,'?'))
						{
							$confirmation_page .= '&thor_success=true';
						}
						else
						{
							$confirmation_page .= '?thor_success=true';
						}
	
						$thor = new Thor($form->get_value('thor_content'),
									     $form->get_value('email_of_recipient'),
									     $confirmation_page);
						$thor->set_form_title($form->get_value('name'));
						$thor->set_show_submitted_data($form->get_value('show_submitted_data'));
						if (!empty($this->user_netID)) $thor->set_submitted_by($this->user_netID);
						if (!empty($_SERVER['REMOTE_ADDR'])) $thor->set_submitter_ip($_SERVER['REMOTE_ADDR']);
						if ($form->get_value('db_flag') == 'yes')
						{
							$thor->set_db_conn(THOR_FORM_DB_CONN, 'form_' . $form->id());
						}
						$autofill = $form->get_value('magic_string_autofill');
						
						if (($autofill != 'none') && ($autofill != ''))
						{
							$editable = ($autofill == 'not_editable') ? false : true;
							$transform_array = $this->gen_magic_string_transform_array();
							$thor->magic_transform($transform_array, 'your', $editable);
						}
						echo $thor->get_html();
					}
					else // no privileges
					{
						echo '<h3>Access to this form is restricted</h3>';
						if (empty($this->user_netID))
						{
							echo '<p>You are not currently logged in. If you have access to this form, the contents will be displayed after you login.</p>';
						}
					}
				}
			}
			if ($this->show_login) echo $this->get_login_logout_link();
			echo '</div>'."\n";
			return true;
		}
		
		function show_data_view_link($url = '')
		{
			$url = make_link(array('mode' => 'data_view', 'thor_success' => ''));
			echo '<div class="formDataAdmin">';
			echo '<p><a href="'.$url.'">Switch to data view</a></p>';
			echo '</div>';
		}
		
		function show_options_link()
		{
			$form_view_link_items['mode'] = $form_view_link_items['export'] = $form_view_link_items['sort_field'] = $form_view_link_items['sort_order'] = '';
			$csv_link_items['filters'] = $form_view_link_items['filters'] = '';
			$csv_link_items['export'] = 'csv';
			
			// process filters manually - make_link does not handle arrays by default
			if (!empty($this->request['filters']))
			{
				foreach ($this->request['filters'] as $k=>$v)
				{
					$csv_link_items['filters['.$k.']'] = $v;
					$form_view_link_items['filters['.$k.']'] = '';
				}
			}
			$url = make_link($form_view_link_items);
			$url2 = make_link($csv_link_items);
			
			echo '<div class="formDataAdmin">';
			echo '<p><a href="'.$url.'">Switch to form view</a> | ';
			echo '<a href="'.$url2.'">Export result set as .csv</a></p>';
			echo '</div>';
		}
		
		/**	
		* Helper function to add_item() - Returns the current user's netID, or false if the user is not logged in.
		* Borrowed from blog module - perhaps this should be more core.
		* @return string user's netID
		*/	
		function get_authentication()
		{
			if(empty($this->user_netID))
			{
				if(!empty($_SERVER['REMOTE_USER']))
				{
					$this->user_netID = $_SERVER['REMOTE_USER'];
					return $this->user_netID;
				}
				else
				{
					$this->user_netID = $this->get_authentication_from_session();
					return $this->user_netID;
				}
			}
			else
			{
				return $this->user_netID;
			}
		}
		function get_authentication_from_session()
		{
			$this->session =& get_reason_session();
			if($this->session->exists())
			{
				if(!on_secure_page())
				{
					$url = get_current_url( 'https' );
					header('Location: '.$url);
					exit();
				}
				if( !$this->session->has_started() )
				{
					$this->session->start();
				}
				$this->user_netID = $this->session->get( 'username' );
				return $this->user_netID;
			}
			else
			{
				return false;
			}
		}
		
	   /**	
		* @return array of groups associated with this relationship.
		*/	
		function get_groups_by_relationship($form_id, $rel_unique_name)
		{
			$es = new entity_selector();
			$es->description = 'Getting groups for this relationship';
			$es->add_type( id_of('group_type') );
			$es->add_right_relationship( $form_id, relationship_id_of($rel_unique_name) );
			return $es->run_one();
		}
		
		function check_privs($group_name, $user_netID)
		{
			foreach($group_name as $group)
			{
				$grouphelper = new group_helper();
				$grouphelper->set_group_by_entity($group);
				if ($grouphelper->requires_login() == false) return true;
				elseif (!(empty($user_netID)) && ($grouphelper->has_authorization($user_netID))) return true;
			}
			return false;
		}
		
		function get_login_logout_link()
        {
                $sess_auth = $this->get_authentication_from_session();
                $auth = $this->get_authentication();
                $ret = '<div class="loginlogout">';
                if(!empty($sess_auth))
                {
                	if ($this->params['force_login'])
			{
 	               		$parts = parse_url(get_current_url());
				$url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?force_login_disable=true&';
                        	$parts['query'] = (isset($parts['query'])) ? str_replace('&force_login_disable=true', '', $parts['query']) : '';
                        	$parts['query'] = (isset($parts['query'])) ? str_replace('force_login_disable=true&', '', $parts['query']) : '';
                        	$parts['query'] = (isset($parts['query'])) ? str_replace('force_login_disable=true', '', $parts['query']) : '';
                        	$url .= rtrim ($parts['query'], '&');
                        	$url = urlencode($url);
			}
                        else 
			{
				$url = urlencode(get_current_url());
			}
                        $ret .= 'Logged in: '.$sess_auth.' <a href="'.REASON_LOGIN_URL.'?logout=true&dest_page=' .$url. '">Log Out</a>';
                }
                elseif(!empty($auth))
                {
                        $ret .= 'Logged in as '.$auth;
                }
                else
                {		$ret .= '<a href="'.REASON_LOGIN_URL.'">Log In</a>';
                }
                $ret .= '</div>'."\n";
                return $ret;
        }
        
        function show_submitted_data()
        {
        	if (!session_id())
        	{
        		session_start();
        		$destroy = true;
        	}
        	if (isset($_SESSION['form_confirm']))
        	{	
        		echo '<div class="submitted_data">';
        		echo '<h2>The following data was successfully submitted:</h2>';
        		$tyr = new Tyr();
        		echo $tyr->make_html_table($_SESSION['form_confirm'], false);
        		echo '</div>';
        		unset ($_SESSION['form_confirm']);
        	}
        	if (isset($destroy))
        	{
        		$_SESSION = NULL;
        		if (isset($_COOKIE[session_name()])) 
        		{
        			setcookie(session_name(), '', time()-42000, '/');
				}
        		session_destroy();
        	}
        }

		/**
         * Magic string functions and directory service stuff
         */
        
        function gen_magic_string_transform_array()
        {
        	if (!empty($this->user_netID))
        	{
        		$dir_array = $this->get_directory_data();
        		if (!empty($dir_array))
        		{
					$transform['your_full_name'] = $dir_array['ds_fullname'][0];
					$transform['your_last_name'] = $dir_array['ds_lastname'][0];
					if (!empty($dir_array['edupersonnickname']))
					{
						$transform['your_first_name'] = $dir_array['edupersonnickname'][0];
					}
					elseif (!empty($dir_array['ds_firstname']))
					{
						$transform['your_first_name'] = $dir_array['ds_firstname'][0];
					}
					$transform['your_name'] = $dir_array['ds_fullname'][0];
					if (!empty($dir_array['ds_email']))
					{
						$transform['your_email'] = $dir_array['ds_email'][0];
					}
					if (!empty($dir_array['ou']))
					{
						if (count($dir_array['ou']) > 1)
						{
							$str = '';
							foreach ($dir_array['ou'] as $k=>$v)
							{
								$str .= $v . '; ';
							}
							$transform['your_department'] = substr($str, 0, -2);
						}
						else
						{
							$transform['your_department'] = $dir_array['ou'][0];
						}
					}
					if (!empty($dir_array['title']))
					{
						if (count($dir_array['title']) > 1)
						{
							$str = '';
							foreach ($dir_array['title'] as $k=>$v)
							{
								$str .= $v . '; ';
							}
							$transform['your_title'] = substr($str, 0, -2);
						}
						else
						{
							$transform['your_title'] = $dir_array['title'][0];
						}
					}
					if (!empty($dir_array['homephone']))
					{
						if (is_array($dir_array['homephone'])) $dir_array['homephone'] = $dir_array['homephone'][0];
						if (substr($dir_array['homephone'], 0, 3) == '+1 ')
						{
							$transform['your_home_phone'] = str_replace(' ', '-', substr($dir_array['homephone'], 3));
						}
						else 
						{
							$transform['your_home_phone'] = $dir_array['homephone'];
						}
					}
					if (!empty($dir_array['telephonenumber']))
					{
						if (is_array($dir_array['telephonenumber'])) $dir_array['telephonenumber'] = $dir_array['telephonenumber'][0];
						if (substr($dir_array['telephonenumber'], 0, 3) == '+1 ')
						{
							$transform['your_work_phone'] = str_replace(' ', '-', substr($dir_array['telephonenumber'], 3));
						}
						else 
						{
							$transform['your_work_phone'] = $dir_array['telephonenumber'];
						}
					}
					return $transform;
        		}
        	}
        	return array();
        }
        			
        function get_directory_data()
        {
		$dir = new directory_service();
		$dir->search_by_attribute('ds_username', $this->user_netID, 
			array('ds_firstname','ds_lastname','ds_fullname','ds_phone','ds_email','ou','title','homephone','telephonenumber'));
		return $dir->get_first_record();
	}
	}
?>
