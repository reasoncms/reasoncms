<?php

include ('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

// try to increase limits in case user chooses a really big chunk
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Reason Upgrade: Add Page to Feed URL Relationship</title>
</head>

<body>
<?php

force_secure();

$user_netID = reason_require_authentication();

echo '<h2>Reason: Add "Page to Feed URL" Relationship</h2>';
if ( (!isset ($_POST['verify'])) && (!isset ($_POST['verify2'])))
{
        echo '<p>This script will add the page_to_feed_url relationship that allows pages to present feeds. It will also delete the page_to_external_url relationship, which is not used anywhere in the code and it just confusing to users.</p>';
		echo_form();
}

if (isset ($_POST['verify']))
{
	$test_mode = true;
	if($_POST['verify'] == 'Run')
		$test_mode = false;
	do_action($test_mode);
}

function echo_form()
{
	echo '<form name="doit" method="post" action="'.get_current_url().'" />';
	echo '<input type="submit" name="verify" value="Run" />';
	echo '<input type="submit" name="verify" value="Test" />';
	echo '</form>';
}

function do_action($test_mode = true)
{
	$rel_id = relationship_id_of('page_to_feed_url');
	if(empty($rel_id))
	{
		echo '<p>page_to_feed_url does not yet exist.</p>';
		if($test_mode)
			echo '<p>Would have created the allowable relationship page_to_feed_url</p>';
		else
		{
			$alrel_id = create_allowable_relationship(id_of('minisite_page'),id_of('external_url'),'page_to_feed_url',array('connections'=>'one_to_many','display_name'=>'Set up feed to display','description_reverse_direction'=>'Page(s) using this URL as the feed they display','description'=>'This relationship allows an external URL to be used as the feed source of an RSS parser/displayer'));
			if(!empty($alrel_id))
			{
				echo '<p>created allowable relationship, id '.$alrel_id.'</p>';
			}
		}
	}
	else
	{
		echo '<p>page_to_feed_url already exists, so it does not need to be created.</p>';
	}
	$rel_id = relationship_id_of('page_to_external_url');
	if(!empty($rel_id))
	{
		echo '<p>page_to_external_url still exists.</p>';
		if($test_mode)
			echo '<p>Would have deleted the allowable relationship page_to_external_url</p>';
		else
		{
			if($GLOBALS['sqler']->delete_one('allowable_relationship',$rel_id))
			{
				echo '<p>deleted allowable relationship, id '.$rel_id.'</p>';
			}
		}
	}
	else
	{
		echo '<p>page_to_external_url no longer exists, so it does not need to be deleted.</p>';
	}
}

?>
</body>
</html>
