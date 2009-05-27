<?php
/**
 * Reason 4 Beta 8 url history tamer
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Start script
 */
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade Reason: Tame URL History</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
include_once( CARL_UTIL_INC . 'db/sqler.php' );
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');


class tameURLHistory
{
	var $mode;
	var $reason_user_id;
		
	function do_updates($mode, $reason_user_id)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		
		settype($reason_user_id, 'integer');
		if(empty($reason_user_id))
		{
			trigger_error('$reason_user_id must be a nonempty integer');
			return;
		}
		$this->reason_user_id = $reason_user_id;
		
		// The updates
		$this->remove_external_urls_from_url_history();
	}
	
	function remove_external_urls_from_url_history()
	{
		// first we grab all the minisite page_ids that are actually URLs
		$es = new entity_selector();
		$es->add_type(id_of('minisite_page'));
		$es->limit_tables(array('page_node', 'url'));
		$es->limit_fields();
		// we only want pages that have the url field populated
		$es->add_relation('((url.url != "") AND (url.url IS NOT NULL))');
		$result = $es->run_one();
		if ($result)
		{
			$dbs = new DBSelector();
			$dbs->add_table('URL_history');
			$dbs->add_relation('page_id IN ("'.implode('","',array_keys($result)).'")');
			$rows = $dbs->run();
			if ($rows)
			{
				foreach ($rows as $row)
				{
					$todelete[] = $row['id'];
				}
				if (isset($todelete))
				{
					$qry = 'DELETE FROM URL_history WHERE id IN ("'.implode('","',$todelete).'")';
					if ($this->mode == 'test')
					{
						echo '<p>Would delete ' . count($todelete) . ' rows with this query:</p>';
						echo $qry;
					}
					elseif ($this->mode == 'run')
					{
						db_query($qry, 'Could not delete rows from URL_history');
						echo '<p>Deleted ' . count($todelete) . ' rows with this query:</p>';
						echo $qry;
					}
					return true;
				}
			}
		}
		echo '<p>There do not appear to be any external_urls that need to be deleted from the URL_history table</p>';
	}
}

force_secure_if_available();
$user_netID = reason_require_authentication();
$reason_user_id = get_user_id( $user_netID );
if(empty($reason_user_id))
{
	die('valid Reason user required');
}
if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have Reason upgrade rights to run this script');
}

?>
<h2>Reason: Database Cleanup and Maintenance</h2>
<p>The URL history has been creating entries for "pages" that are actually just links. In some cases, theses bogus entries could 
be used instead of the correct redirect. The URL_History function library has been updated to no longer create these unneeded entries 
in the table, and this script clears out the ones that were already created.</p>
<p><strong>What will this update do?</strong></p>
<ul>
<li>Remove all entries from URL_history where the page_id corresponds to a page entity that has a populated url.url field</li>
</ul>

<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?php

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
	$updater = new tameUrlHistory();
	$updater->do_updates($_POST['go'], $reason_user_id);
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
