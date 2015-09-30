<?php
/**
 * Add new themes to Reason for beta 7
 *
 * New themes: Bedrich
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
<title>Upgrade Reason: Add new themes</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

force_secure_if_available();

$user_netID = reason_require_authentication();

$reason_user_id = get_user_id( $user_netID );

if(empty($reason_user_id))
{
	die('valid Reason user required');
}

if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have upgrade privileges to run this script');
}

?>
<h2>Reason: add new themes</h2>
<p>This update will add the new themes:</p>
<ul>
<li>Bedrich</li>
</ul>
<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?php
class theme_updater_b6_to_b7
{
	var $_testmode = true;
	var $_user_id;
	var $_report = array();
	function run($testmode = true,$user_id)
	{
		$this->_testmode = $testmode;
		if(empty($user_id))
		{
			$this->report[] = 'No user ID provided. Unable to continue.';
			return false;
		}
		$this->_user_id = $user_id;
		
		$this->_add_bedrich_theme();
	}
	function _add_bedrich_theme()
	{
		$theme_id = id_of('bedrich_theme');
		$created_theme = false;
		$created_template = false;
		$created_css = false;
		if(empty($theme_id))
		{
			if($this->_testmode)
			{
				$this->_report[] = 'Would have created the Bedrich theme';
			}
			else
			{
				$theme_id = reason_create_entity( id_of('master_admin'), id_of('theme_type'), $this->_user_id, 'Bedrich', array('unique_name'=>'bedrich_theme'));
				$this->_report[] = 'Created the Bedrich theme (id: '.$theme_id.')';
				$created_theme = true;
			}
		}
		else
		{
			$this->_report[] = 'Bedrich theme exists. No need to create it.';
		}
		
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('minisite_template'));
		$es->add_relation('entity.name = "bedrich"');
		$es->set_num(1);
		$templates = $es->run_one();
		if(empty($templates))
		{
			if($this->_testmode)
			{
				$this->_report[] = 'Would have created the Bedrich template';
			}
			else
			{
				$template_id = reason_create_entity( id_of('master_admin'), id_of('minisite_template'), $this->_user_id, 'bedrich');
				$this->_report[] = 'Created the Bedrich template (id: '.$template_id.')';
				$created_template = true;
			}
		}
		else
		{
			$template = current($templates);
			$template_id = $template->id();
			$this->_report[] = 'bedrich template exists. No need to create it.';
		}
		
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('css'));
		$es->add_relation('url = "css/bedrich/bedrich.css"');
		$es->set_num(1);
		$css = $es->run_one();
		if(empty($css))
		{
			if($this->_testmode)
			{
				$this->_report[] = 'Would have created the Bedrich css';
			}
			else
			{
				$css_id = reason_create_entity( id_of('master_admin'), id_of('css'), $this->_user_id, 'bedrich',array('url'=>'css/bedrich/bedrich.css','css_relative_to_reason_http_base'=>'true'));
				$this->_report[] = 'Created the Bedrich css (id: '.$css_id.')';
				$created_css = true;
			}
		}
		else
		{
			$c = current($css);
			$css_id = $c->id();
			$this->_report[] = 'bedrich css exists. No need to create it.';
		}
		
		if(!empty($theme_id))
		{
			$theme = new entity($theme_id);
			
			if(!empty($css_id) && !$theme->get_left_relationship('theme_to_external_css_url'))
			{
				create_relationship( $theme_id, $css_id, relationship_id_of('theme_to_external_css_url'));
				$this->_report[] = 'attached bedrich css to bedrich theme';
			}
			else
			{
				$this->_report[] = 'bedrich theme already attached to css. No need to attach css.';
			}
			
			if(!empty($template_id) && !$theme->get_left_relationship('theme_to_minisite_template'))
			{
				create_relationship( $theme_id, $template_id, relationship_id_of('theme_to_minisite_template'));
				$this->_report[] = 'attached bedrich template to bedrich theme';
			}
			else
			{
				$this->_report[] = 'berich theme already attached to template. No need to attach template.';
			}
		}
		
	}
	function get_report()
	{
		return $this->_report;
	}
}

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
	{
		echo '<p>Running updater...</p>'."\n";
		$testmode = false;
	}
	else
	{
		echo '<p>Testing updates...</p>'."\n";
		$testmode = true;
	}
	
	$u = new theme_updater_b6_to_b7();
	$u->run($testmode,$reason_user_id);
	pray($u->get_report());
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
