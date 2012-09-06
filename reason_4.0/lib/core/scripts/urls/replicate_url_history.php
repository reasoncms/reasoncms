<?php
/**
 * A script that will copy a page's URL history
 *
 * This script can be handy if you are replacing one site with another.
 *
 * Even if the new pages have the same URLs as the old pages, if the old pages ever existed
 * at different URLs, Reason's 404 handling mechanism will redirect them to the *old* pages.
 *
 * This script will allow you to take care of that problem by overlaying the old page's
 * URL history with a new history created for the new page. It is as if you had, in rapid
 * succession, moved the new page to all the locations where the old page had ever existed.
 *
 * @package reason
 * @subpackage scripts
 */

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Replicate History</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
<?php
set_time_limit(600);
include_once( 'reason_header.php' );
reason_include_once( 'classes/entity_selector.php' );
connectDB( REASON_DB );
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'function_libraries/admin_actions.php' );
force_secure_if_available();
$current_user = check_authentication();
$user_id = get_user_id ( $current_user );
if (!reason_user_has_privs( $user_id, 'db_maintenance' ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to replicate a page\'s history.</p><p>Only Reason users who have the Administrator role may do that.</p></body></html>');
}

echo '<h1>Replicate a page\'s URL history</h1>'."\n";
echo '<p>This script will overlay an old page\'s history or URLs (even if the old page has been deleted) with a "fake" history of a page that should replace the old page.</p>'."\n";
echo '<p>This is useful for projects where one site or page needs to replace another one, but links to the old page\'s previous locations still exist out on the web.</p>'."\n";
echo '<p>After this script is run, requests for any of the locations where old page once resided will be instead redirected to the new page, as the new page have a more recent entry in the URL history for the location.</p>'."\n";
echo '<form>'."\n";
echo '<label for="old_page">Old Page ID: </label><input type="text" id="old_page" name="old_page" value="'.(isset($_GET['old_page']) ? htmlspecialchars($_GET['old_page'], ENT_QUOTES) : '' ).'" /><br />'."\n";
echo '<label for="new_page">Replacement Page ID: </label><input type="text" id="new_page" name="new_page" value="'.(isset($_GET['new_page']) ? htmlspecialchars($_GET['new_page'], ENT_QUOTES) : '' ).'" /><br />'."\n";
echo '<input type="submit" name="action" value="Replicate History" />'."\n";
echo '</form>'."\n";

if(!empty($_GET['action']))
{
	if(empty($_GET['old_page']))
	{
		echo 'You must enter an old page ID';
	}
	elseif(empty($_GET['new_page']))
	{
		echo 'You must enter a replacement page ID';
	}
	else
	{
		$old_page_id = (integer) $_GET['old_page'];
		$new_page_id = (integer) $_GET['new_page'];
		
		if(empty($old_page_id) || empty($new_page_id))
		{
			echo 'Invalid page id';
			die();
		}
		
		$dbs = new DBSelector();
		$dbs->add_table('URL_history');
		$dbs->add_relation('page_id = "'.addslashes($old_page_id).'"');
		$dbs->set_order('timestamp ASC');
		$rows = $dbs->run();
		//pray($rows);
		echo '<h2>Replicating Page URL History</h2>'."\n";
		echo '<ul>'."\n";
		foreach($rows as $row)
		{
			if(!empty($row['url']))
			{
				$query = 'INSERT INTO URL_history SET ' . 
						'url = "' . addslashes($row['url']) . '", ' .
						'page_id = "' . addslashes($new_page_id) . '", ' .
						'timestamp = "' . addslashes(time()) . '"';
		
				$results = mysql_query( $query );
		
				if( empty( $results ) )
					die( '<br />:: ' . $query . '::' . $results );
				echo '<li><em>Successful insert:</em> '.$query.'</li>'."\n";
				sleep(1);
			}
			else
			{
				echo '<li>Empty URL in history; skipping</li>'."\n";
			}
		}
		echo '</ul>'."\n";
	}
}

?>
</body>
</html>