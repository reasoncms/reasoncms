<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include the parent class & other dependencies
 */
	reason_include_once( 'minisite_templates/modules/gallery.php' );
	reason_include_once( 'minisite_templates/modules/gallery_vote/vote_form.php' );
	reason_include_once( 'function_libraries/admin_actions.php');
	reason_include_once( 'classes/csv.php');
	
/**
 * Register the module with Reason
 */
	$GLOBALS[ '_module_class_names' ][ 'gallery_vote' ] = 'GalleryVoteModule';

/**
 * A minisite module that enables voting on the photos for logged-in users
 */
class GalleryVoteModule extends GalleryModule
{
	var $user_netID; // current user netid
	var $csv_auth; // CSV authentication connection
	var $csv_data; // CSV data connection
	var $rows = 2000; // effectively disables pagination
	var $modes = array('gallery', 'vote', 'results');
	var $modes_to_nice_names = array('gallery'=>'View Photos', 'vote'=>'Vote', 'results'=>'View Results');
	var $mode_checkers = array('vote'=>'voting_is_available', 'results'=>'results_are_viewable');
	var $mode_requires_login_checkers = array('vote'=>'voting_requires_login','results'=>'results_require_login');
    
	function get_cleanup_rules()
	{
		$cr = parent::get_cleanup_rules();
		$cr['mode'] = array('function' => 'check_against_array', 'extra_args' => $this->modes );
		return $cr;
	}
			
	function init( $args = array() )
	{
 		if (empty($this->request['mode'])) $this->request['mode'] = 'gallery'; // default mode is gallery
		
		if(!$this->get_authentication() && $this->mode_requires_login($this->request['mode']))
		{
			header('Location: '.REASON_LOGIN_URL.'?dest_page='.urlencode(get_current_url()));
			die();
		}
 		
 		$this->parent->add_stylesheet(REASON_HTTP_BASE_PATH.'css/image_gallery/gallery_vote.css');
		
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
  		
  		if ($this->request['mode'] == 'vote' || $this->request['mode'] == 'results')
  		{
  			$es = new entity_selector();
			$es->description = 'Selecting images for the gallery';
			$es->add_type( id_of('image') );
			$es = $this->refine_es( $es );
			$this->images = $es->run_one();
			
  			//attempt to populate csv_netID
  			$this->get_authentication();
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
		if ($this->request['mode'] == 'vote' )
		{
			$this->run_vote_view();
		}
		elseif( $this->request['mode'] == 'results')
		{
			$this->run_results_view();
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
	function mode_is_available($mode)
	{
		if(isset($this->mode_checkers[$mode]))
		{
			$function = $this->mode_checkers[$mode];
			return $this->$function();
		}
		return true;
	}
	function mode_requires_login($mode)
	{
		if(isset($this->mode_requires_login_checkers[$mode]))
		{
			$function = $this->mode_requires_login_checkers[$mode];
			return $this->$function();
		}
		return false;
	}
	function voting_is_available()
	{
		if($this->cur_user_has_voted())
			return false;
		return true;
	}
	function voting_requires_login()
	{
		return true;
	}
	function results_require_login()
	{
		return true;
	}
	
	function get_nav()
	{
		$sess_auth = $this->get_authentication_from_session();
		$auth = $this->get_authentication();
		$ret = '<div id="galleryVoteNav">';
		
		$nav_parts = array();
		foreach($this->modes as $mode)
		{
			if($this->request['mode'] == $mode)
			{
				$nav_parts[] = '<strong>'.$this->modes_to_nice_names[$mode].'</strong>';
			}
			elseif($this->mode_is_available($mode))
			{
				$nav_parts[] = '<a href="'.carl_make_link(array('mode'=>$mode)).'">'.$this->modes_to_nice_names[$mode].'</a>';
			}
		}
		$ret .= implode(' | ',$nav_parts);
		$ret .= ' | ' . $this->get_login_logout_link($sess_auth, $auth);
		$ret .= '</div>'."\n";
		
		return $ret;
	}
	
	function get_login_logout_link($sess_auth = '', $auth = '')
	{
		if (empty($sess_auth)) $sess_auth = $this->get_authentication_from_session();
		if (empty($auth)) $auth = $this->get_authentication();
		$ret = '<span class="loginlogout">';
		if(!empty($sess_auth))
		{
			if($this->mode_requires_login($this->request['mode']))
			{
				$url = carl_make_link( array('mode'=>''), '', '', false );
			}
			else
			{
				$url = get_current_url();
			}
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
	
	function run_results_view()
	{
		echo '<div id="galleryVote">';
		echo '<h3>Results</h3>'."\n";
		$netid = $this->get_authentication();
		if(empty($netid))
		{
			echo '<p>You must be logged in to view results</p>'."\n";
		}
		else
		{
			if($this->user_can_view_results($netid))
			{
				$votes = array();
				$num_votes = 0;
				foreach($this->csv_data->csv_to_array() as $row)
				{
					$num_votes++;
					if(!isset($votes[$row[0]]))
						$votes[$row[0]] = 1;
					else
						$votes[$row[0]]++;
					
					if(isset($this->images[$row[0]]))
						unset($this->images[$row[0]]);
				}
				foreach($this->images as $id=>$item)
				{
					$votes[$id] = 0;
				}
				arsort($votes);
				echo '<p><strong>Total votes:</strong> '.$num_votes.'</p>'."\n";
				echo '<table style="width:100%">'."\n";
				echo '<tr><th>Image</th><th>Votes</th></tr>'."\n";
				reset($votes);
				$max = current($votes);
				foreach($votes as $id=>$num)
				{
					echo '<tr><td style="width:25%">';
					show_image($id);
					echo '</td><td>';
					if($num == 0 || $max == 0)
					{
						echo '<strong>0</strong>';
					}
					else
					{
						echo '<div style="padding:.5em 0;float:left;background-color:#008;color:#fff;text-align:right;width:' . round($num/$max*100, 2) . '%;"><strong style="padding-right:.5em;">'.$num.'</strong></div>';
					}
					echo '</td></tr>'."\n";
				}
				echo '</table>'."\n";
			}
			else
			{
				echo '<p>You must do not have permission to view the results</p>'."\n";
			}
		}
		echo '</div>'."\n";
	}
	function run_vote_view()
	{
		if (defined('REASON_CSV_DIR')) 
		{
			$csv_netIDs = array_map(create_function('$array', 'return "$array[0]";'), $this->csv_auth->csv_to_array());
			if (empty($this->user_netID) && $this->voting_requires_login())
			{
				echo '<h3>Login Required</h3>';
				echo '<p>This page requires you to login using a valid '.SHORT_ORGANIZATION_NAME.' netID.</p>';
				
			}
			elseif ($this->cur_user_has_voted())
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
		else
		{
			trigger_error('REASON_CSV_DIR must be defined for image voting to work');
			echo '<h3>Sorry; Voting is not available.</h3><p>The administrator of this web server needs to turn it on -- please contact the administrator of the web site and tell them the information below:</p>
			<p>Image voting needs to have the Reason setting REASON_CSV_DIR defined in order to work. Please go into the Reason settings file and specify the directory for the csv data.</p>';
		}
	}
	function cur_user_has_voted()
	{
		if(!empty($this->user_netID))
		{
			$auth_log = $this->csv_auth->csv_to_array();
			foreach($auth_log as $row)
			{
				if($row[0] == $this->user_netID)
					return true;
			}
		}
		return false;
	}
	function results_are_viewable()
	{
		if($this->user_can_view_results($this->get_authentication()))
		{
			return true;
		}
	}
	function user_can_view_results($netid)
	{
		$user_reason_id = get_user_id( $netid );
		if(!empty($user_reason_id))
		{
			$es = new entity_selector();
			$es->add_type(id_of('site'));
			$es->add_left_relationship($user_reason_id, relationship_id_of('site_to_user'));
			$es->set_num(1);
			$es->limit_tables();
			$es->limit_fields();
			$es->add_relation('entity.id = "'.$this->site_id.'"');
			$sites = $es->run_one();
			if(!empty($sites))
			{
				return true;
			}
		}
		return false;
	}
}	
	
?>
