<?php
/**
 * Reason 4 Beta 8 url history tamer
 *
 * @package reason
 * @subpackage scripts
 * 
 * @todo code alter_url_history_table
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
reason_include_once('function_libraries/URL_History.php');

class tameURLHistory
{
	var $mode;
	var $reason_user_id;
	var $processed_ids;
		
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
		if ($this->repair_external_url_problem())
		{
			if ($this->clean_invalid_values())
			{
				if ($this->clean_duplicate_values())
				{
					if ($this->clean_extra_contiguous_values())
					{
						if ($this->clean_expunged())
						{
						//if ($this->check_and_fix_timestamps())
						//{
							$this->alter_url_history_table();
							$finished = true;
						//}
						}
					}
				}
			}
		}
		if (!isset($finished) && $this->mode=='run') echo '<strong><p>You should run this script again it requires multiple runnings to do all its work.</p></strong>';
		elseif (isset($finished)) echo '<strong>Congrats - it appears you are done with this script and your URL history has been tamed.</strong>';
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
				$query =  'SELECT * FROM URL_history WHERE url ="' . reason_sql_string_escape ( $url ) . '" ORDER BY timestamp DESC limit 1';
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
			return true;
		}
		return false;
	}

	function clean_invalid_values()
	{
		$query =  'SELECT * FROM URL_history WHERE url ="" OR url IS NULL 
														   OR url = "/" 
														   OR url LIKE "%' .reason_sql_string_escape("//"). '%"
														   OR url LIKE "%' .reason_sql_string_escape("http:"). '%" 
														   OR url LIKE "%' .reason_sql_string_escape("https:"). '%" 
														   ORDER BY timestamp DESC';
		$result = db_query($query, 'error in query');
		while ($row = mysql_fetch_assoc($result))
		{
			$needs_deletion[] = $row['id'];
		}
		if (isset($needs_deletion))
		{
			$deleter_sql = 'DELETE FROM URL_history WHERE id IN ("'.implode('","',$needs_deletion).'")';
			if ($this->mode == 'test')
			{
				echo '<p>Would delete ' . count($needs_deletion) . ' rows with this query:</p>';
				echo $deleter_sql;
			}
			if ($this->mode == 'run')
			{
				db_query($deleter_sql, 'Could not delete rows from URL_history');
				echo '<p>Deleted ' . count($needs_deletion) . ' rows with this query:</p>';
				echo $deleter_sql;
			}
		}
		else
		{
			echo '<p>There are no rows with no url in the URL_history table that need deletion - you may have already run this script</p>';
			return true;
		}
		return false;
	}
	
	/**
	 * We have many many dups ... this is basically because we've been updating ALL the timestamps
	 */
	function clean_duplicate_values()
	{
		$num_to_process = 500;
		$query = 'SELECT `id`, `page_id`, `url`, `timestamp`, `deleted`, COUNT( * ) 
				FROM `URL_history` 
				GROUP BY `page_id`, `url`, `timestamp`
				HAVING COUNT( * ) >1
				ORDER BY `id` DESC';
					                 
		$result = db_query($query, 'error in query');
		$mycount = mysql_num_rows($result);
		if (mysql_num_rows($result) > 0)
		{
			if ($this->mode == 'test') echo '<p>Would delete a chunk (maybe all) of the ' . $mycount . ' urls that have duplicate entries in the table.</p>';
			elseif ($this->mode == 'run')
			{
				echo '<p>There are ' . $mycount . ' urls that appear to have duplicate entries. Please keep running this script until the number is 0. We
			             only process ' . $num_to_process . ' per run to minimize load on the database</p>';
			    $counter = 0;
			    while ($row = mysql_fetch_assoc($result))
			    {
			    	$counter++;
			    	if ($counter == $num_to_process) break;
			    	else
			    	{
			    		// lets delete the copies from the DB except for the id we selected
			    		$id = $row['id'];
			    		$page_id = $row['page_id'];
			    		$timestamp = $row['timestamp'];
			    		$url = reason_sql_string_escape($row['url']);
			    		$qry = 'SELECT id FROM URL_history where id != '.$id.' AND page_id = '.$page_id.' AND url = "'.$url.'" AND timestamp = '.$timestamp;
			    		$daresult = db_query($qry);
			    		if ($daresult) 
			    		{
			    			while ($myrow = mysql_fetch_assoc($daresult))
			    			{
			    				$todelete[] = $myrow['id'];
			    			}
			    		}
			    	}
			    }
			    if (isset($todelete))
			    {
			    	$deleter_sql = 'DELETE FROM URL_history WHERE id IN ("'.implode('","',$todelete).'")';
			    	db_query($deleter_sql, 'Could not delete rows from URL_history');
			    	echo '<p>Deleted some of the URLs that contain duplicate entries ... more could remain.</p>';
			    }
			    
			}
		}
		else
		{
			echo '<p>There are not duplicates in the URL_history table that need deletion - you may have already run this script</p>';
			return true;
		}
		return false;
		
	}
	
	function clean_extra_contiguous_values()
	{
		$dbs = new DBSelector();
		$dbs->add_table('URL_history', 'URL_history');
		$dbs->add_field('URL_history', 'page_id');
		$dbs->add_field('URL_history', 'id');
		$dbs->add_field('URL_history', 'timestamp');
		$dbs->add_field('URL_history', 'url');
		$dbs->set_order('page_id DESC, timestamp DESC');
		$rows = $dbs->run();
		$page_id = NULL;
		$url = NULL;
		if ($rows) foreach ($rows as $row)
		{
			// if the page id is the same as last page id and url is the same as last url - this one gets queued for deletion
			$last_page_id = $page_id;
			$page_id = $row['page_id'];
			$last_url = $url;
			$url = $row['url'];
			if ( ($last_page_id == $page_id) && ($last_url == $url) )
			{
				$needs_deletion[] = $row['id'];
			}
		}
		if (isset($needs_deletion))
		{
			$deleter_sql = 'DELETE FROM URL_history WHERE id IN ("'.implode('","',$needs_deletion).'")';
			if ($this->mode == 'test')
			{
				echo '<p>Would delete ' . count($needs_deletion) . ' unneeded contiguous rows with this query:</p>';
				echo $deleter_sql;
			}
			if ($this->mode == 'run')
			{
				db_query($deleter_sql, 'Could not delete rows from URL_history');
				echo '<p>Deleted ' . count($needs_deletion) . ' unneeded contiguous rows with this query:</p>';
				echo $deleter_sql;
			}
		}
		else
		{
			echo '<p>There are no unneeded contiguous rows in the URL_history table that need deletion - you may have already run this script</p>';
			return true;
		}
	}
	
	function clean_expunged()
	{
		$es = new entity_selector();
		$es->add_type(id_of('minisite_page'));
		$es->limit_tables();
		$es->limit_fields();
		$result = $es->run_one('', 'All');
		$page_ids = array_keys($result);
		
		$dbs = new DBSelector();
		$dbs->add_table('URL_history', 'URL_history');
		$dbs->add_field('URL_history', 'id');
		$dbs->add_field('URL_history', 'page_id');
		$dbs->add_field('URL_history', 'timestamp');
		$dbs->add_field('URL_history', 'url');
		$dbs->add_relation('URL_history.page_id NOT IN ("'.implode('","',$page_ids).'")');
		$rows = $dbs->run();
		
		foreach ($rows as $row)
		{
			$e = new entity($row['page_id']);
			if (!reason_is_entity($e, 'minisite_page')) $needs_deletion[] = $row['id'];
		}
		if (isset($needs_deletion))
		{
			$deleter_sql = 'DELETE FROM URL_history WHERE id IN ("'.implode('","',$needs_deletion).'")';
			if ($this->mode == 'test')
			{
				echo '<p>Would delete ' . count($needs_deletion) . ' rows that reference expunged entities with this query:</p>';
				echo $deleter_sql;
			}
			if ($this->mode == 'run')
			{
				db_query($deleter_sql, 'Could not delete rows from URL_history');
				echo '<p>Deleted ' . count($needs_deletion) . ' rows that reference expunged entities with this query:</p>';
				echo $deleter_sql;
			}
		}
		else
		{
			echo '<p>There are no rows that reference expunged entities in the URL_history table - you may have already run this script</p>';
			return true;
		}
	}
	
	/**
	 * finds all the pages that are home page owned by a live site and updates their url history
	 *
	 * not currently in use this is really slow and not really needed. it fixes a few edge cases
	 * where the current page is not the latest entry in the url history. But ... the url history
	 * will still resolve correctly even if the URL is not listed as the most current.
	 */
	// function check_and_fix_timestamps()
// 	{
// 		$num_per_run = 50;
// 		$count = 0;
// 		$site_es = new entity_selector();
// 		$site_es->limit_tables();
// 		$site_es->limit_fields();
// 		$site_es->add_type(id_of('site'));
// 		$sites = $site_es->run_one();
// 		if ($sites)
// 		{
// 			$site_ids = array_keys($sites);
// 			
// 			$es = new entity_selector();
// 			$es->limit_tables();
// 			$es->limit_fields();
// 			$es->add_type(id_of('minisite_page'));
// 			$es->add_right_relationship_field( 'owns','entity','id','site_id', $site_ids );
// 			$rel = $es->add_left_relationship_field('minisite_page_parent', 'entity', 'id', 'parent_id');
// 			$es->add_relation('entity.id = ' .$rel['parent_id']['table'].'.'.$rel['parent_id']['field']);
// 			$result = $es->run_one();
// 			if ($result) // result is home pages of live sites
// 			{
// 				if ($this->mode == 'test')
// 				{
// 					$processed_ids = $this->_get_processed_page_ids();
// 					foreach ($result as $id=>$page)
// 					{
// 						if (!in_array($id, $processed_ids))
// 						{
// 							if ($count == $num_per_run) break;
// 							$count++;
// 						}
// 					}
// 					$remain  = (count($result) - count($processed_ids));
// 					if ($remain < 0) $remain = 0;
// 					echo '<p>I would check history for ' . $count . ' home pages. At this point, there are ' . count($processed_ids) . ' home pages checked, and ' . $remain . ' that remain to be checked.</p>';
// 					$this->_output_processed_page_ids($processed_ids);
// 					if ($remain == 0) return true;
// 				}
// 				elseif ($this->mode == 'run')
// 				{
// 					$processed_ids = $this->_get_processed_page_ids();
// 					foreach ($result as $id=>$page)
// 					{
// 						if (!in_array($id, $processed_ids))
// 						{
// 							if ($count == $num_per_run) break;
// 							update_URL_history($id);
// 							$processed_ids[] = $id;
// 							$count++;
// 						}
// 					}
// 					$remain  = (count($result) - count($processed_ids));
// 					if ($remain < 0) $remain = 0;
// 					echo '<p>I checked history for ' . $count . ' home pages this time. In total, there are ' . count($processed_ids) . ' home pages checked, and ' . $remain . ' that remain to be checked.</p>';
// 					$this->_output_processed_page_ids($processed_ids);
// 					if ($remain == 0) return true;
// 				}
// 			}
// 		}
// 	}
	
	function _get_processed_page_ids()
	{
		if (isset($_POST['processed_pages']))
		{
			$page_ids = explode("|", $_POST['processed_pages']);			
		}
		return (isset($page_ids) && !empty($page_ids)) ? $page_ids : array();
	}
	
	function _output_processed_page_ids($page_ids)
	{
		$string = implode("|", $page_ids);
		echo '<input type="hidden" name="processed_pages" value="'.$string.'" />';
	}
	
	function alter_url_history_table()
	{
	
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
<h2>Reason: Clean up the URL history table</h2>
<p>The URL history table is not pretty and has been creating corrupt entries for awhile. Most notably, it has has been creating entries 
for "pages" that are actually just links. In some cases, theses bogus entries could be used instead of the correct redirect. 
The URL_History function library has been updated to no longer create these unneeded entries in the table. This script clears out 
the ones that were already created, and for those that are the most recent timestamp for a URL, and thus in active use, creates updated 
entries that reference the parent page of the external URL, (where the external URLs pages had been resolving to anyway prior to this script).
The script additionally zaps empty entries and those that are duplicates (down to the timestamp.)</p>
<p><strong>What will this update do?</strong></p>
<ul>
<li>Modify all entries from URL_history where the page_id corresponds to a page entity that has a populated url.url field</li>
<li>Remove all entries where the url field is empty or "/", has multiple slashes, or contains the string "http:" or "https:" - these URLS cannot be used to resolve a URL</li>
<li>Remove all entries that are duplicates of other rows - preserve only the one with the highest id number</li>
<li>Remove subsequent entries with the same url and page_id - not needed and should never have been created</li>
<li>Remove rows with page_ids that reference reason pages that do not exist (expunged pages)</li>
<?php //<li>Check all the page ids in URL history - run update_URL_history on each to make sure the current location is the most recent timestamp</li> ?>
<?php //<li>NOT YET IMPLEMENTED - Removed the "deleted column" - which was inaccurate and is no longer needed because of logic changes in check_URL_history()</li> ?>
<?php //<li>NOT YET IMPLEMENTED - Adds a "domain" column and populates it with the REASON_HOST domain </li>?>
</ul>
<?php

// lets construct a link to carry our processed ids if there are any

?>
<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" />
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
</form>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
