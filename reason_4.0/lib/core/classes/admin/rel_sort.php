<?php
include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/admin_actions.php');
reason_include_once( 'function_libraries/url_utils.php');
reason_include_once( 'classes/entity_selector.php');
reason_include_once( 'classes/user.php');

/**
* A class which will swap the relationship sort value for adjacent entities both related to an entity.
*
* This class handles the following
*
* - verifies that the user who requested the swap is a reason user and has access to the site where the swap is taking place
* - verifies that the order of the entity which is moving up or down is the same as it was when the request was initiated
* - swaps the order of the entity with the one directly above or below depending upon the direction provided
* - if it is a background request via xmlhttp, returns the string "success" and dies
*
* Sample usage:
* <code>
* 	$rel_sort = new RelationshipSort();
* </code>
*
* @package reason
* @subpackage admin
* @author Nathan White
* 8/8/2006
*
*/

class RelationshipSort
{
	/**
	 * @var int $site_id
	 */
	var $site_id;
	
	/**
	 * @var int $rel_id
	 */
	var $al_relationship_id;
	
	/**
	 * @var int $left_entity_id
	 */
	var $left_entity_id;
	
	/**
	 * @var int $entity_id
	 */
	var $entity_id;
	
	/**
	 * @var string $direction
	 */
	var $direction;
	
	/**
	 * @var string $user_netID
	 */
	var $user_netID;
	
	/**
	 * @var string $background
	 */
	var $background;
	 
	/**
	 * @var array $rel_sort_mapping
	 */
	var $rel_sort_mapping;
	
	/**
	 * initialize request
	 *
	 * @param int $site_id id of the site from where the request originates
	 * @param int $al_relationship_id the id of the allowable relationship
	 * @param int $left_entity_id the id of the entity on the a site of the relationship
	 * @param int $entity_id the id of the entity that is moving up or down
	 * @param int $row_id the row number of the entity that is moving up or down
	 * @param string $direction which direction the entity is moving
	 * @param string $user_netID the netid of the user initiating the request
	 * @param string $background set to 'yes' if the request comes via xmlhttp, 'no' if not
	 */
	function init($site_id, $al_relationship_id, $left_entity_id, $entity_id, $row_id, $direction, $user_netID, $background)
	{
		$this->user_netID = check_against_regexp($user_netID, array('alphanumeric'));
		$this->direction = check_against_array($direction, array('moveup', 'movedown'));
		$this->left_entity_id = turn_into_int($left_entity_id);
		$this->entity_id = turn_into_int($entity_id);
		$this->row_id = turn_into_int($row_id);
		$this->al_relationship_id = turn_into_int($al_relationship_id);
		$this->site_id = turn_into_int($site_id);
		//$this->type_id = turn_into_int($type_id);
		$this->background = check_against_array($background, array('yes', 'no'));
		
		// consider passing this in earlier, or just use 
	}
	
	/**
	 * wrapper for class methods which check the validity of user and site, and to ensure the relationship is sortable
	 * additionally generates the rel_sort_mapping array needed in the run() method.
	 *
	 * @return boolean whether successful or not
	 */
	function validate_request()
	{
		if ($this->check_permission() && $this->check_is_sortable())
		{
			$prep = $this->prep_for_run();
			if (is_array($prep))
			{
				$this->rel_sort_mapping = $prep;
				return true;
			}
			else
			{
				if ($this->background == 'yes') 
				{
					echo 'failed';
					die;
				}
			}
		}
		$this->redirect();
	}
	
	/**
	 * run() makes sure there is something to do, and does it. In the case of an xmlhttp request, run() dies after echoing the string success
	 */
	function run()
	{
		if (isset($this->rel_sort_mapping))
		{
			if (count($this->rel_sort_mapping) > 0)
			{
				foreach ($this->rel_sort_mapping as $rel_id => $rel_sort_order)
				{
					$this->relationship_sort_order_update($rel_id, $rel_sort_order);
				}
				if ($this->background == 'yes') 
				{
					echo 'success';
					die;
				}
			}
			else trigger_error('Error in run() method - could not find any relationships that need to be swapped');
		}
		else trigger_error('Error in run() method - there is no array generated of what needs to be swapped - make sure you run validate_request() before run');
		$this->redirect();
	}
	
	
	/**
	 * prep_for_run returns the array describing which relationships need to be updated, and also verifies that the entity that is
	 * being moved is in the same row position it was prior to the request (gracefully handles double clicks and the like)
	 *
	 * @return mixed array describing new sort orders according to relationship id, or false
	 */
	function prep_for_run()
	{
		$e = new entity($this->entity_id);
		$type_id = $e->get_value('type');
		// performs an appropriate entity selection - populates class variables with specifics for run method.
		$es = new entity_selector($this->site_id);
		$es->add_type($type_id);
		$es->set_sharing( 'owns,borrows' );
		$es->add_field( 'relationship' , 'id' , 'relationship_id' );
		$es->add_right_relationship( $this->left_entity_id , $this->al_relationship_id );
		$es->add_rel_sort_field($this->left_entity_id);
		if ($this->direction == 'moveup') $es->set_order('rel_sort_order DESC');
		else $es->set_order('rel_sort_order ASC');
		$result = $es->run_one();
		$resultcount = count($result);
		$read_next = false;
		$rowcounter = 0;
		$relationship_id_1 = $relationship_id_2 = $new_rel_sort_order = $old_rel_sort_order = null;
		foreach ($result as $k=>$v)
		{
			if ($read_next == true)
			{
				$new_rel_sort_order = $v->get_value('rel_sort_order');
				$relationship_id_2 = $v->get_value('relationship_id');
				break;
			}
			elseif ($k == $this->entity_id)
			{
				if ((($this->direction == 'moveup') && ($this->row_id == ($resultcount - $rowcounter))) ||
				   (($this->direction == 'movedown') && ($this->row_id == ($rowcounter + 1))))
				{
					$old_rel_sort_order = $v->get_value('rel_sort_order');
					$relationship_id_1 = $v->get_value('relationship_id');
					$read_next = true;
				}
				else
				{
					trigger_error('There was a problem modifying the relationship sort order - the entity being moved does not have the same location as when the request was initiated. This can happen from multiple clicks or when multiple people are modifying the sort order.');
					return false;
				}
			}
			else 
			{
				unset($result[$k]);
				$rowcounter++;
			}
		}
		if (is_numeric($relationship_id_1) && is_numeric($relationship_id_2) && is_numeric($new_rel_sort_order) && is_numeric($old_rel_sort_order))
		{
			return array($relationship_id_1 => $new_rel_sort_order, $relationship_id_2 => $old_rel_sort_order);
		}
		else return false;
	}
	
	/**
	 * relationship_sort_order_update sets a relationship id to a rel sort value
	 *
	 * @param int $rel_id the id of the relationship to update
	 * @param int $rel_sort_value the value to set the rel_sort_order column to
	 * @return void
	 */
	function relationship_sort_order_update($rel_id, $rel_sort_value)
	{
		update_relationship($rel_id, array('rel_sort_order' => $rel_sort_value));
	}
	
	/**
	 * check_is_sortable
	 *
	 * @return true if the allowable relationship id corresponds to an allowable relationship that is sortable
	 */
	function check_is_sortable()
	{
		$q = 'SELECT is_sortable, relationship_a, relationship_b FROM allowable_relationship WHERE id = ' . $this->al_relationship_id;
        	$r = db_query( $q , 'error getting relationship info' );
        	$row = mysql_fetch_array( $r , MYSQL_ASSOC );
       		if ($row['is_sortable'] == 'yes') return true;
        	else return false;
	}
	
	/**
	 * check_permission uses the user manager class to validate that the site 
	 * and user are valid, and that the user has access to the site
	 *
	 * @return boolean true if the user and site are valid, and the user has access to the site
	 */ 
	function check_permission()
	{
		$user_manager = new User();
		if ($user_manager->set_site_id($this->site_id))
		{
			if ($user_manager->is_site_user($this->user_netID))
			{
				$user_id = get_user_id($this->user_netID);
				$e1 = new entity($this->entity_id);
				$e2 = new entity($this->left_entity_id);
				if($e1->get_value('state') == 'Pending' || $e2->get_value('state') == 'Pending')
					$priv = 'edit_pending';
				else
					$priv = 'edit';
				if(reason_user_has_privs($user_id,$priv))
				{
					$user = new entity($user_id);
					return $e2->user_can_edit_relationship($this->al_relationship_id, $user, 'right');
				}
			}
		}
		return false;
	}
	
	/**
	 * redirects to lister with relationship sorting $_GET variables stripped
	 *
	 * @return boolean true if the user and site are valid, and the user has access to the site
	 */ 	
	function redirect()
	{
		$redirect_link = carl_make_link(array('do' => '', 'eid' => '', 'rowid' => ''), '', '', false);
		header('Location: ' . $redirect_link);
	}
}
?>
