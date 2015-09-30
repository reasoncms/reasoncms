<?php
/**
 * Creates the page_to_related_publication allowable relationship
 *
 * This script is part of the 4.0 beta 3 to beta 4 upgrade
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include ('reason_header.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/relationship_finder.php');

force_secure_if_available();

$user_netID = check_authentication();

$reason_user_id = get_user_id( $user_netID );

if(empty($reason_user_id))
{
	die('valid Reason user required');
}

if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have upgrade privileges to run this script');
}

$rel_data = array ('connections' => 'many_to_many',
				   'description' => 'Places a related publication on a page',
				   'directionality' => 'unidirectional',
				   'required' => 'no',
				   'is_sortable' => 'yes',
				   'display_name' => 'Places a related publication on a page',
				   'display_name_reverse_direction' => 'Pages where this publication is a related publication',
				   'description_reverse_direction' => 'Pages where this publication is a related publication');
					   

echo '<h2>Reason Publication Setup</h2>';
if (!isset ($_POST['verify']))
{
        echo '<p>This script creates the page_to_related_publication allowable relationship</p>';
        echo_form();
}
elseif (isset ($_POST['verify']) && ($_POST['verify'] == 'Run'))
{
	$rel_id = create_allowable_relationship(id_of('minisite_page'),id_of('publication_type'),'page_to_related_publication',$rel_data);
	if ($rel_id) echo '<p>Allowable relationship created</p>';
	else 
	{
		$test = relationship_find_and_update('minisite_page', 'publication_type', 'page_to_related_publication',$rel_data);
		if ($test == false) echo '<p>Allowable relationship did not need updating</p>';
	}
}
else
{
	echo_form();
}

function echo_form()
{
	echo '<form name="doit" method="post" src="'.get_current_url().'" />';
	echo '<p><input type="submit" name="verify" value="Run" /></p>';
	echo '</form>';
}

function relationship_find_and_update($a_type, $b_type, $name, $updates = array())
{
	$existing_rel_id = relationship_finder($a_type, $b_type, $name);
	if (!empty($existing_rel_id) && !empty($updates))
	{
		// build criteria clause - only want to update if it is actually needed
		$set_str = $where_str_body = '';
		$where_str_start = " AND (";
		foreach ($updates as $k=>$v)
		{
			$set_str .= (!empty($set_str)) ? ", " : '';
			$where_str_body .= (!empty($where_str_body)) ? ") OR (" : "(";
			$where_str_body .=  $k . ' != "' . reason_sql_string_escape($v) .'"';
			$set_str .= $k .' = "'. reason_sql_string_escape($v) . '"';
		}
		$where_str_end = "))";
		$q = 'UPDATE allowable_relationship SET ' . $set_str . ' WHERE ID='.$existing_rel_id.$where_str_start.$where_str_body.$where_str_end;
		db_query($q, 'could not update the places a blog on a page relationship');
		$num_rows = mysql_affected_rows();
		if (!empty($num_rows))
		{
			echo '<p>updated relationship ' . $name .'</p>';
			pray ($updates);
			return true;
		}
		else return false;
	}
}
?>
