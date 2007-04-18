<?php 
	reason_include_once( 'minisite_templates/modules/gallery.php' );
	reason_include_once( 'minisite_templates/modules/gallery_vote/vote_form.php' );
	reason_include_once( 'function_libraries/admin_actions.php');
	reason_include_once( 'classes/csv.php');
	
	$GLOBALS[ '_module_class_names' ][ 'gallery_vote' ] = 'GalleryVoteModule';

class GalleryVoteModule extends GalleryModule
{
	var $user_netID; // current user netid
	var $csv_auth; // CSV authentication connection
	var $csv_data; // CSV data connection
	var $rows = 2000; // effectively disables pagination
    
	function get_cleanup_rules()
	{
		$cr = parent::get_cleanup_rules();
		$cr['mode'] = array('function' => 'check_against_array', 'extra_args' => array('gallery', 'vote') );
		return $cr;
	}
			
	function init( $args )
	{
 		if (empty($this->request['mode'])) $this->request['mode'] = 'gallery'; // default mode is gallery
 		
 		$this->parent->add_stylesheet(REASON_HTTP_BASE_PATH.'css/image_gallery/gallery_vote.css');
  		
  		if ($this->request['mode'] == 'vote')
  		{
  			$es = new entity_selector();
			$es->description = 'Selecting images for the gallery';
			$es->add_type( id_of('image') );
			$es = $this->refine_es( $es );
			$this->images = $es->run_one();
			
  			//attempt to populate csv_netID
  			$this->get_authentication();
  		
  			//path to CSV data
       		if (!defined('REASON_CSV_DIR'))
        	{
        		trigger_error('REASON_CSV_DIR path is not defined in settings.php');
        	}
        	else
			{
				//establish csv connection
				$this->csv_connect(REASON_CSV_DIR . 'gallery_vote/');
			}
        }
        else parent::init($args);
	}
	
	function refine_es($es)
	{
		//if ($this->request['mode'] == 'gallery')
		//{
		//	return parent::refine_es($es);
		//}
		//else
		//{
			$es->set_env( 'site' , $this->site_id );
			$es->add_right_relationship($this->parent->cur_page->id(), relationship_id_of('minisite_page_to_image'));
			$es->set_order( 'RAND()' );
			return $es;
		//}
	}
	
	function csv_connect($path)
	{
		$auth_filename = $this->parent->cur_page->id() . '_auth.csv';
		$data_filename = $this->parent->cur_page->id() . '_data.csv';
		$this->csv_auth = new CSV($path . $auth_filename);
		$this->csv_data = new CSV($path . $data_filename);
	}

	function run()
	{
		echo '<div id="galleryVote">';
		echo $this->get_nav();
		if ($this->request['mode'] == 'vote' && defined('REASON_CSV_DIR')) 
		{
			$csv_netIDs = array_map(create_function('$array', 'return "$array[0]";'), $this->csv_auth->csv_to_array());
			if (empty($this->user_netID))
			{
				echo '<h3>Login Required</h3>';
				echo '<p>This page requires you to login using a valid '.SHORT_ORGANIZATION_NAME.' netID.</p>';
				
			}
			elseif (in_array($this->user_netID, $csv_netIDs))
			{
				echo '<h3>Sorry</h3><p>You may only vote once and have already voted.</p>';
			}
			else
			{
				$ballot = new GalleryVoteForm($this->images);		
				$ballot->init();
				$ballot->run();
				if ($ballot->submitted) 
				{
					$result = $this->save_vote($ballot, $csv_netIDs);
				
					switch ($result) {
					case 'saved':
						echo '<h3>Success</h3><p>Your vote has been recorded - thank you for participating.</p>';
						break;
					case 'already_voted':
						echo '<h3>Error</h3><p>Your vote cannot be saved because you have already voted.</p>';
						break;
					case 'error':
						echo '<h3>Error</h3><p>There was an error saving your vote - the Web Services group has been notified. Please try again later.</p>';
						break;
					}
				}
			}
		}
		elseif( $this->request['mode'] == 'vote' && !defined('REASON_CSV_DIR') )
		{
			echo '<h3>Sorry; Voting is not available.</h3><p>The administrator of this web server needs to turn it on -- please contact the administrator of the web site and tell them the information below:</p>
			<p>Image voting needs to have the Reason setting REASON_CSV_DIR defined in order to work. Please go into the Reason settings file and specify the directory for the csv data.</p>';
		}
		else
		{
			parent::run();
		}
		echo '</div>';
	}
	
	function has_content()
	{
		if (count($this->images) > 0) return true;
		else return false;
	}

	/**	
	* Returns the current user's netID, or false if the user is not logged in.
	* @return string user's netID
	*/	
	
	function save_vote($ballot, $csvNetIDs)
	{
		if (in_array($this->user_netID, $csvNetIDs) == false)
		{
			$auth[] = $this->user_netID;
			$data[] = $ballot->get_value('image_choice');
			if ($this->csv_auth->appendRow($auth) && $this->csv_data->appendRow($data)) return 'saved';
			else 
			{	
				trigger_error('error saving vote for user ' . $this->user_netID . ' - gallery_vote module at URL ' . get_current_url());
				return 'error';
			}
		}
		else return 'already_voted';
	}
	
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
				return $this->get_authentication_from_session();
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
			force_secure_if_available();
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
	
	function get_nav()
	{
		$sess_auth = $this->get_authentication_from_session();
		$auth = $this->get_authentication();
		$ret = '<div id="galleryVoteNav"><p>';
		$parts = parse_url(get_current_url());
		$url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?';
		if ((!empty($sess_auth) || !empty($sess)) && ($this->request['mode'] == 'gallery')) // logged in, gallery mode
		{
			if (isset($parts['query'])) $url .= $parts['query'] . '&mode=vote';
			else $url .= 'mode=vote';
			$ret .= '<a href="'.$url.'">Vote</a>';
			$ret .= ' | ' . $this->get_login_logout_link($sess_auth, $auth);
		}
		elseif ((!empty($sess_auth) || !empty($sess)) && ($this->request['mode'] == 'vote')) // logged in, vote mode
		{
			$parts['query'] = (isset($parts['query'])) ? str_replace('&mode=vote', '', $parts['query']) : '';
			$parts['query'] = (isset($parts['query'])) ? str_replace('mode=vote&', '', $parts['query']) : '';
			$parts['query'] = (isset($parts['query'])) ? str_replace('mode=vote', '', $parts['query']) : '';
			$url .= rtrim ($parts['query'], '&');
			$ret .= '<a href="'.$url.'">Return to Gallery</a>';
			$ret .= ' | ' . $this->get_login_logout_link($sess_auth, $auth);
		}
		elseif ($this->request['mode'] == 'vote') // not logged in - vote module
		{
			$parts['query'] = (isset($parts['query'])) ? str_replace('&mode=vote', '', $parts['query']) : '';
			$parts['query'] = (isset($parts['query'])) ? str_replace('mode=vote&', '', $parts['query']) : '';
			$parts['query'] = (isset($parts['query'])) ? str_replace('mode=vote', '', $parts['query']) : '';
			$url .= rtrim($parts['query'], '&');
			$ret .= '<a href="'.$url.'">View Gallery</a> | ';
			$ret .= '<a href="'.REASON_LOGIN_URL.'">Log In</a>';
			$ret .= ' | You must be logged in to vote';
		}
		else
		{
			if (isset($parts['query'])) $url .= rtrim($parts['query'], '&') . '&mode=vote';
			else $url .= 'mode=vote';
			$ret .= '<a href="'.REASON_LOGIN_URL.'?dest_page='.urlencode($url).'">Vote</a>';
			$ret .= ' | You must be logged in to vote';
		}
		$ret .= '</p></div>';
		return $ret;
	}
	
	function get_login_logout_link($sess_auth = '', $auth = '')
	{
		if (empty($sess_auth)) $sess_auth = $this->get_authentication_from_session();
		if (empty($auth)) $auth = $this->get_authentication();
		$ret = '<span class="loginlogout">';
		if(!empty($sess_auth))
		{
			$parts = parse_url(get_current_url());
			$url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?';
			$parts['query'] = (isset($parts['query'])) ? str_replace('&mode=vote', '', $parts['query']) : '';
			$parts['query'] = (isset($parts['query'])) ? str_replace('mode=vote&', '', $parts['query']) : '';
			$parts['query'] = (isset($parts['query'])) ? str_replace('mode=vote', '', $parts['query']) : '';
			$url .= rtrim ($parts['query'], '&');
			$ret .= '<a href="'.REASON_LOGIN_URL.'?logout=true&dest_page='.urlencode($url).'">Log Out</a> (Currently Logged In: '.$sess_auth.')';
		}
		elseif(!empty($auth))
		{
			$ret .= 'Currently Logged In: '.$auth;
		}
		else
		{
			$ret .= '<a href="'.REASON_LOGIN_URL.'">Log In</a>';
		}
		$ret .= '</span>'."\n";
		//return '<p>'.$ret.'</p>';
		return $ret;
	}
}	
	
?>
