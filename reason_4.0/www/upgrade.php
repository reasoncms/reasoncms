<?php
/**
 * Upgrade.php provides an HTML skin and basic upgrade functionality.
 *
 * If there are no standalone upgrades for a particular upgrade, this interface allows you to to test and run all upgrades at once.
 *
 * If there are standalone upgrades, it alerts you and lets you run them individually.
 *
 * @todo use head items
 * @todo allow for ajax updates for standalone scripts
 * @package reason
 */

/**
 * Load dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrade_assistant.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('classes/head_items.php');

/**
 * Enforce secure connection and ensure reason user has upgrade privileges.
 */
force_secure_if_available();
$user_netID = reason_require_authentication();
$reason_user_id = get_user_id( $user_netID );
$mode = $str = '';

if(empty($reason_user_id))
{
        die('Valid Reason user required.');
}

if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
        die('You must have upgrade privileges to upgrade Reason.');
}

$upgrade_steps = array(
	'4.7_to_4.8' => 'Reason 4.7 to 4.8',
	'4.6_to_4.7' => 'Reason 4.6 to 4.7',
	'4.5_to_4.6' => 'Reason 4.5 to 4.6',
	'4.4_to_4.5' => 'Reason 4.4 to 4.5',
	'4.3_to_4.4' => 'Reason 4.3 to 4.4',
	'4.2_to_4.3' => 'Reason 4.2 to 4.3',
	'4.1_to_4.2' => 'Reason 4.1 to 4.2',
	'4.0_to_4.1' => 'Reason 4.0 to 4.1',
);

$head_items = new HeadItems();
$head_items->add_head_item('title',array(),'Upgrade Reason', true);
$head_items->add_head_item('meta',array('http-equiv'=>'Content-Type','content'=>'text/html; charset=UTF-8' ) );
$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/reason_setupgrade/reason_setupgrade.css');

if(!empty($_GET['upgrade_step']) && isset($upgrade_steps[$_GET['upgrade_step']]))
{
	$step = $_GET['upgrade_step'];
	$requested_upgrader = !empty($_GET['upgrader']) ? $_GET['upgrader'] : NULL;

	$rua = new reasonUpgradeAssistant;
	$active_upgraders = $rua->get_active_upgraders($step, $requested_upgrader);

	$str = '<h2>'.htmlspecialchars($upgrade_steps[$step]).'</h2>'."\n";

	if (!empty($active_upgraders))
	{
		$str .= $rua->get_upgrader_output($active_upgraders, $reason_user_id, $head_items);
		$standalone_upgraders = $rua->get_standalone_upgraders($step);
		if (!empty($standalone_upgraders) && (count($active_upgraders) > 1))
		{
			$str .= '<h4>Note: There are upgrades that must be run by themselves. Please test and run these separately.</h4>'."\n";
			$str .= '<ul>'."\n";
			foreach($standalone_upgraders as $name => $upgrader)
			{
				$str .= '<li><a href="?upgrade_step='.urlencode($step).'&amp;upgrader='.urlencode($name).'">'.$upgrader->title().'</a></li>'."\n";
			}
			$str .= '</ul>'."\n";
		}
		$ret_link = carl_construct_link(array('upgrade_step' => $step));
		$str .= '<hr/><p><a href="'.$ret_link.'">&lt; All ' . htmlspecialchars($upgrade_steps[$step]) .' Upgrades</a></p>';
	}
	else // I am on the summary screen for this upgrade step
	{
		// INFORMATION UPGRADERS - JUST INFO //
		$upgrade_info = $rua->get_upgrade_info($step);
		foreach ($upgrade_info as $upgrade_info_item)
		{
			$str .= $rua->get_upgrader_output($upgrade_info_item);
		}

		// AUTOMATIC UPGRADERS - NO UI - CAN BE RUN AND TESTED AS A GROUP //
		$upgraders = $rua->get_upgraders($step);
		if (!empty($upgraders))
		{
			if (count($upgraders) > 1) $str .= '<h3><a href="?upgrade_step='.urlencode($step).'&amp;upgrader=_all_">Test All Automatic Upgrades</a></h3>'."\n";
			$str .= '<h3>Test Individual Automatic Upgrades</h3>'."\n";
			$str .= '<ul>'."\n";
			foreach($upgraders as $name=>$upgrader)
			{
				$str .= '<li><a href="?upgrade_step='.urlencode($step).'&amp;upgrader='.urlencode($name).'">'.$upgrader->title().'</a>';
				// if we have a description lets show it.
				$desc = $upgrader->description();
				if (!empty($desc))
				{
					$str .= '<ul><li>'.$desc.'</li></ul>';
				}
				$str .= '</li>'."\n";
			}
			$str .= '</ul>'."\n";
		}

		// STANDALONE UPGRADERS //
		$standalone_upgraders = $rua->get_standalone_upgraders($step);
		if (!empty($standalone_upgraders))
		{
			$str .= '<h3>Run Standalone Upgrades</h3>'."\n";
			$str .= '<ul>'."\n";
			foreach($standalone_upgraders as $name=>$upgrader)
			{
				$str .= '<li><a href="?upgrade_step='.urlencode($step).'&amp;upgrader='.urlencode($name).'">'.$upgrader->title().'</a>';
				// if we have a description lets show it.
				$desc = $upgrader->description();
				if (!empty($desc))
				{
					$str .= '<ul><li>'.$desc.'</li></ul>';
				}
				$str .= '</li>'."\n";
			}
			$str .= '</ul>'."\n";
			$ret_link = carl_construct_link(array('upgrade_step' => ''));
			$str .= '<hr/><p><a href="'.$ret_link.'">&lt; All Upgrades</a></p>';
		}
	}
}
else
{
	$str = '<p>Each new version of Reason includes a set of scripts that you should ';
	$str .= 'run to update your database to work with the latest version of the Reason code base. ';
	$str .= 'The scripts are designed to be used from one release to the next; you cannot necessarily update ';
	$str .= 'a Reason database across multiple steps after downloading the most current code base.</p>';
	$str .= '<p>If you have trouble upgrading and you are using an old version of Reason, try downloading ';
	$str .= 'the point release after the one you are currently using and upgrade incrementally.</p>';
	$str .= '<p><a href="http://apps.carleton.edu/opensource/reason/download/">Reason download page</a></p>';
	$str .= '<h2>Reason Upgrade Scripts</h2>';
	$str .= '<ul>';
	foreach($upgrade_steps as $k => $v)
	{
		$str .= '<li><a href="?upgrade_step='.urlencode($k).'">'.htmlspecialchars($v).'</a></li>';
	}
	$str .= '<li><a href="./scripts/upgrade/4.0b8_to_4.0b9/index.php">Reason 4.0 Beta 8 to Beta 9 (otherwise known as 4.0 release!)</a></li>';
	$str .= '<li><a href="./scripts/upgrade/4.0b7_to_4.0b8/index.php">Reason 4.0 Beta 7 to Beta 8</a></li>';
	$str .= '<li><a href="./scripts/upgrade/4.0b6_to_4.0b7/index.php">Reason 4.0 Beta 6 to Beta 7</a></li>';
	$str .= '<li><a href="./scripts/upgrade/4.0b5_to_4.0b6/index.php">Reason 4.0 Beta 5 to Beta 6</a></li>';
	$str .= '<li><a href="./scripts/upgrade/4.0b4_to_4.0b5/index.php">Reason 4.0 Beta 4 to Beta 5</a></li>';
	$str .= '<li><a href="./scripts/upgrade/4.0b3_to_4.0b4/index.php">Reason 4.0 Beta 3 to Beta 4</a></li>';
	$str .= '<li><a href="./scripts/upgrade/4.0b1_and_b2_to_4.0b3/index.php">Reason 4.0 Beta 1 or 2 to Beta 3</a></li>';
	$str .= '</ul>';
}

$output = '<!DOCTYPE html>';
$output .= '<html xmlns="http://www.w3.org/1999/xhtml">';
$output .= '<head>';
$output .= $head_items->get_head_item_markup();
$output .= '</head>';
$output .= '<body>';
$output .= '<div id="reason_upgrade">';
$output .= '<h1>Upgrade Reason</h1>';
$output .= $str;
$output .= '</div>';
$output .= '</body>';
$output .= '</html>';

// output!
echo $output;
