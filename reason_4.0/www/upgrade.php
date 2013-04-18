<?php
/**
 * @package reason
 */

include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrade_assistant.php');
reason_include_once('function_libraries/user_functions.php');

force_secure_if_available();
$user_netID = reason_require_authentication();
$reason_user_id = get_user_id( $user_netID );

if(empty($reason_user_id))
{
        die('Valid Reason user required.');
}

if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
        die('You must have upgrade privileges to upgrade Reason.');
}


?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Upgrade Reason</title>
<link rel='stylesheet' href='css/reason_setupgrade/reason_setupgrade.css' type='text/css'>
</head>
<body>
<h1>Upgrade Reason</h1>
<?php
$upgrade_steps = array(
	'4.3_to_4.4' => 'Reason 4.3 to 4.4',
	'4.2_to_4.3' => 'Reason 4.2 to 4.3',
	'4.1_to_4.2' => 'Reason 4.1 to 4.2',
	'4.0_to_4.1' => 'Reason 4.0 to 4.1',
);
if(!empty($_GET['upgrade_step']) && isset($upgrade_steps[$_GET['upgrade_step']]))
{
	$step = $_GET['upgrade_step'];
	$rua = new reasonUpgradeAssistant;
	$upgraders = $rua->get_upgraders($step);
	echo '<h2>'.htmlspecialchars($upgrade_steps[$step]).'</h2>'."\n";
	if(!empty($_GET['upgrader']) && ( isset($upgraders[$_GET['upgrader']]) || '_all_' == $_GET['upgrader'] ) )
	{
		if(isset($_POST['mode']) && 'run' == $_POST['mode'])
			$run = true;
		else
			$run = false;
		if(!$run)
			echo '<p class="mode"><em>Testing Mode</em></p>'."\n";
		$chosen_upgrader = $_GET['upgrader'];
		$standalones = array();
		foreach($upgraders as $upgrader_name=>$upgrader)
		{
			$standalone = false;
			if(method_exists($upgrader,'standalone'))
			{
				$standalone = $upgrader->standalone();
				if( '_all_' == $chosen_upgrader && $standalone )
					$standalones[] = $upgrader_name;
			}
			if($chosen_upgrader == $upgrader_name || ( '_all_' == $chosen_upgrader && !$standalone ) )
			{
				$upgrader->user_id($reason_user_id);
				echo '<h3>'.$upgrader->title().'</h3>'."\n";
				if($run)
				{
					echo $upgrader->run();
					if(method_exists($upgrader,'run_again'))
					{
						if($upgrader->run_again())
						{
							echo '<b>This upgrade is not yet complete</b>' . "\n";
							echo'<form id="runnerForm" action="'.get_current_url().'" method="post"><input type="hidden" name="mode" value="run" /><input type="submit" value="Continue Upgrade" /></form>'."\n";
						}
						else
						{
							echo '<b>The upgrade is finished -- no need to run again </b>';
						}
					}
				}
				else
				{
					echo $upgrader->test();
				}
			}
		}
		if($run)
		{
			if(!empty($standalones))
			{
				echo '<h3>There are upgrades that must be run by themselves. Please test and run these separately.</h3>'."\n";
				echo '<ul>'."\n";
				foreach($standalones as $name)
				{
					echo '<li><a href="?upgrade_step='.urlencode($step).'&amp;upgrader='.urlencode($name).'&amp;mode=test">'.$upgraders[$name]->title().'</a></li>'."\n";
				}
				echo '</ul>'."\n";
			}
		}
		else
		{
			echo '<form id="runnerForm" action="'.get_current_url().'" method="post"><input type="hidden" name="mode" value="run" /><input type="submit" value="Run Module(s)" 
/></form>'."\n";
			if(!empty($standalones))
			{
				echo '<h4>Note: There are upgrades that must be run by themselves. Please test and run these separately.</h4>'."\n";
				echo '<ul>'."\n";
				foreach($standalones as $name)
				{
					echo '<li><a href="?upgrade_step='.urlencode($step).'&amp;upgrader='.urlencode($name).'&amp;mode=test">'.$upgraders[$name]->title().'</a></li>'."\n";
				}
				echo '</ul>'."\n";
			}
		}
		$ret_link = carl_construct_link(array('upgrade_step' => $step));
		echo '<hr/><p><a href="'.$ret_link.'">&lt; All ' . htmlspecialchars($upgrade_steps[$step]) .' Upgrades</a></p>';
	}
	else
	{
		$upgrade_info = $rua->get_upgrade_info($step);
		foreach ($upgrade_info as $upgrade_info_item)
		{
			echo $upgrade_info_item->run();
		}
		echo '<h3><a href="?upgrade_step='.urlencode($step).'&amp;upgrader=_all_&amp;mode=test">Test All Upgrades</a></h3>'."\n";
		echo '<h3>Test Individual Upgrades</h3>'."\n";
		echo '<ul>'."\n";
		foreach($upgraders as $name=>$upgrader)
		{
			echo '<li><a href="?upgrade_step='.urlencode($step).'&amp;upgrader='.urlencode($name).'&amp;mode=test">'.$upgrader->title().'</a>';
			// if we have a description lets show it.
			$desc = $upgrader->description();
			if (!empty($desc))
			{
				echo '<ul><li>'.$desc.'</li></ul>';
			}
			echo '</li>'."\n";
		}
		echo '</ul>'."\n";
	}
}
else
{

?>
<p>Each new version of Reason includes a set of scripts that you should 
run to update your database to work with the latest version of the Reason code base. 
The scripts are designed to be used from one release to the next; you cannot necessarily update 
a Reason database across multiple steps after downloading the most current code base.</p>
<p>If you have trouble upgrading and you are using an old version of Reason, try downloading 
the point release after the one you are currently using and upgrade incrementally.</p>
<p><a href="http://apps.carleton.edu/opensource/reason/download/">Reason download page</a></p>
<h2>Reason Upgrade Scripts</h2>
<ul>
<?php
foreach($upgrade_steps as $k => $v)
	echo '<li><a href="?upgrade_step='.urlencode($k).'">'.htmlspecialchars($v).'</a></li>';
?>
<li><a href="./scripts/upgrade/4.0b8_to_4.0b9/index.php">Reason 4.0 Beta 8 to Beta 9 (otherwise known as 4.0 release!)</a></li>
<li><a href="./scripts/upgrade/4.0b7_to_4.0b8/index.php">Reason 4.0 Beta 7 to Beta 8</a></li>
<li><a href="./scripts/upgrade/4.0b6_to_4.0b7/index.php">Reason 4.0 Beta 6 to Beta 7</a></li>
<li><a href="./scripts/upgrade/4.0b5_to_4.0b6/index.php">Reason 4.0 Beta 5 to Beta 6</a></li>
<li><a href="./scripts/upgrade/4.0b4_to_4.0b5/index.php">Reason 4.0 Beta 4 to Beta 5</a></li>
<li><a href="./scripts/upgrade/4.0b3_to_4.0b4/index.php">Reason 4.0 Beta 3 to Beta 4</a></li>
<li><a href="./scripts/upgrade/4.0b1_and_b2_to_4.0b3/index.php">Reason 4.0 Beta 1 or 2 to Beta 3</a></li>
</ul>
<?php

}
?>
</body>
</html>
