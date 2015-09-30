<?php
/**
 * Upgrade assets from 4.0 beta 5 to beta 6
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
<title>Upgrade Reason: Asset changes for 4.0 beta 6</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class assetUpdaterb5b6
{
	var $mode;
	var $reason_user_id;
	var $asset_to_category_details = array (
		'description'=>'Asset to Category',
		'directionality'=>'bidirectional',
		'connections'=>'many_to_many',
		'required'=>'no',
		'is_sortable'=>'yes',
		'display_name'=>'Assign to Categories',
		'display_name_reverse_direction'=>'Assets in this category',
		'description_reverse_direction'=>'Assets in this category');
		
	//type_to_default_view
	
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
		$this->update_page_to_asset_relationship();
		$this->add_asset_to_category_relationship();
	}
	
	function update_page_to_asset_relationship()
	{
		$r_id = relationship_id_of('page_to_asset');
		$q = 'SELECT directionality from allowable_relationship WHERE id = ' . $r_id;
		$results = db_query($q);
		$result = mysql_fetch_assoc($results);
		$directionality = $result['directionality'];
		if ($directionality == 'bidirectional')
		{
			echo '<p>page_to_asset relationship is already bidirectional. No need to update.</p>'."\n";
			return false;
		}
		else
		{
			$q = 'UPDATE allowable_relationship SET directionality="bidirectional" WHERE id='.$r_id;
			if ($this->mode == 'run')
			{
				$result = db_query($q);
				echo '<p>Update page_to_asset relationship to be bidirectional</p>';
			}
			else
			{
				echo '<p>Would update page_to_asset relationship with this query:</p>'."\n";
				echo '<p>'.$q.'</p>'."\n";
			}
		}
		
	}
	
	function add_asset_to_category_relationship()
	{
		if (reason_relationship_name_exists('asset_to_category'))
		{
			echo '<p>asset_to_category already exists. No need to update.</p>'."\n";
			return false;
		}
		if($this->mode == 'run')
		{
			$r_id = create_allowable_relationship(id_of('asset'), id_of('category_type'), 'asset_to_category', $this->asset_to_category_details);
			if($r_id)
			{
				echo '<p>asset_to_category allowable relationship successfully created</p>'."\n";
			}
			else
			{
				echo '<p>Unable to create asset_to_category allowable relationship</p>';
				echo '<p>You might try creating the relationship asset_to_category yourself in the reason administrative interface - it should include the following characteristics:</p>';
				pray ($this->asset_to_category_details);
			}
		}
		else
		{
			echo '<p>Would have created asset_to_category allowable relationship.</p>'."\n";
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
	die('You must have Reason upgrade privileges to run this script');
}

?>
<h2>Reason: update assets for 4.0b6</h2>
<p>What will this update do?</p>
<ul>
<li>Change the page to asset relationship to bi-directional if it is not already.</li>
<li>Create an asset to category relationship if it does not exist.</li>
</ul>
<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?php

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
	$updater = new assetUpdaterb5b6();
	$updater->do_updates($_POST['go'], $reason_user_id);
}

?>
</body>
</html>
