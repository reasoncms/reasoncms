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
		//$this->remove_external_urls_from_url_history();
		$this->repair_external_url_problem();
	}
	
	/**
	 * Find pages that are actually external URLs - if the external url is the most recent timestamp for an entry, keep track of its details.
	 *
	 * Delete all the external URL page_id entries from the table, and then insert corrected ones for the external urls that were being used
	 * as the most recent timestamp. The corrected entries use the parent page_id instead, which is what the external url entries would typically
	 * resolve to. This approach keeps the history working as intended, while removing the external url entries that are no longer put into the
	 * table in Reason 4 Beta 8
	 */
	function repair_external_url_problem()
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
			foreach ($rows as $row) // lets keep the most recent timestamp for each URL - we might want to transform these
			{
				$url = $row['url'];
				$id = $row['id'];
				$urls[$id] = $url;
				$rows_by_id[$id] = $row;
			}
		}
		if (isset($urls))
		{
			$unique_urls = array_unique($urls);
			foreach ($unique_urls as $url) // check URL history table to see if any of these URLs currently resolve to external URL pages
			{
				$query =  'SELECT * FROM URL_history WHERE url ="' . addslashes ( $url ) . '" AND deleted="no" ORDER BY timestamp DESC limit 1';
				$result = db_query($query, 'error in query');
				$latest_row = ($result) ? mysql_fetch_assoc($result) : false;
				if ($latest_row)
				{
					$latest_row_id = $latest_row['id'];
					if (isset($urls[$latest_row_id]))
					{
						$needs_fixin[] = $latest_row_id;
					}
				}
			}
			if (isset($needs_fixin))
			{
				foreach ($needs_fixin as $url_history_id_to_update)
				{
					$d = new DBselector();
					$d->add_table( 'r', 'relationship' );
					$d->add_field( 'r', 'entity_b', 'parent_id' );
					$d->add_relation( 'r.type = ' . relationship_id_of('minisite_page_parent'));
					$d->add_relation( 'r.entity_a = ' . $rows_by_id[$url_history_id_to_update]['page_id'] );
					$result = db_query( $d->get_query() , 'Error getting parent ID.' );
					if( $myrow = mysql_fetch_assoc($result))
					{
						$fixer[$url_history_id_to_update]['deleted'] = $rows_by_id[$url_history_id_to_update]['deleted'];
						$fixer[$url_history_id_to_update]['timestamp'] = $rows_by_id[$url_history_id_to_update]['timestamp'];
						$fixer[$url_history_id_to_update]['url'] = $rows_by_id[$url_history_id_to_update]['url'];
						$fixer[$url_history_id_to_update]['page_id'] = $myrow['parent_id'];
						$fixer[$url_history_id_to_update]['old_page_id'] = $rows_by_id[$url_history_id_to_update]['page_id'];
						$fixer[$url_history_id_to_update]['new_page_id'] = $myrow['parent_id'];
					}
				}
			}
			
			if (isset($fixer)) // lets build the sql to run
			{
				$fixer_sql = 'INSERT INTO `URL_history` (`id`, `url`, `page_id`, `timestamp`, `deleted`) VALUES ';
				$fixcount = 0;
				foreach ($fixer as $row_id => $fields)
				{
					$notfirst = (!isset($notfirst)) ? false : true;
					if ($notfirst) $fixer_sql .= ", ";
					$fixer_sql .= "(".$row_id.", '".$fields['url']."', ".$fields['page_id'].", ".$fields['timestamp'].", '".$fields['deleted']."')";
					$fixcount++;
				}
			}
			$deleter_sql = 'DELETE FROM URL_history WHERE id IN ("'.implode('","',array_keys($rows_by_id)).'")';
			if ($this->mode == 'test')
			{
				echo '<p>Would delete ' . count($rows_by_id) . ' rows with this query:</p>';
				echo $deleter_sql;
			}
			elseif ($this->mode == 'run')
			{
				db_query($deleter_sql, 'Could not delete rows from URL_history');
				echo '<p>Deleted ' . count($rows_by_id) . ' rows with this query:</p>';
				echo $deleter_sql;
			}
			if ($this->mode == 'test' && isset($fixer_sql))
			{
				echo '<p>Would restore ' . $fixcount . ' rows with updated page_ids this query:</p>';
				echo $fixer_sql;
			}
			elseif ($this->mode == 'run' && isset($fixer_sql))
			{
				db_query($fixer_sql, 'Could not restore rows to URL_history');
				echo '<p>Restored ' . $fixcount . ' rows with this query:</p>';
				echo $fixer_sql;
			}
		}
		else
		{
			echo '<p>There are no rows in the URL_history table that resolve to external urls - you may have already run this script</p>';
		}
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
