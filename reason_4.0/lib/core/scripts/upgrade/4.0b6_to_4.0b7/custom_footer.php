<?php
/**
 * Upgrade the site parent relationship to be many-to-many
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
<title>Upgrade Reason: add custom site footer capability</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/field_to_entity_table_class.php');

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
<h2>Reason: add custom site footer capability</h2>
<p>This update will allow administrators to customize the footer text on an instancewide and a site-by-site basis.</p>
<?php
if(!defined('REASON_DEFAULT_FOOTER_XHTML'))
{
	?>
	<h3>Step 1: Add setting</h3>
	<?php
	if(!empty($_GET['setting']))
	{
		echo '<h4 style="color:#c00;">ALERT: It does not appear that the constant REASON_DEFAULT_FOOTER_XHTML has been defined. Please make sure it is defined in reason_settings.php before proceeding with this upgrade.</h4>'."\n";
	}
	?>
	<p>In your reason settings file, add this php code:</p>
	<textarea rows="15" cols="80" style="width:100%;background-color:#ddd;">
	/**
	 * REASON_DEFAULT_FOOTER_XHTML
	 *
	 * The default text to be used to indicate maintainer/contact information
	 *
	 * There are three strings that will be automagically replaced with the site/page info:
	 * 
	 * [[sitename]] is replaced with the name of the site
	 *
	 * [[maintainer]] is replaced with the name/email of the site maintainer
	 *
	 * [[lastmodified]] is replaced with the date the page was most recently modified
	 */
	define('REASON_DEFAULT_FOOTER_XHTML','<div id="maintainer">[[sitename]] pages maintained by [[maintainer]]</div><div id="lastUpdated">This page was last updated on [[lastmodified]]</div>');
	</textarea>
	<p>Once you have done that, <a href="?setting=1">Proceed to the database upgrade</a>.</p>
	<?php
}
else
{
	
	?>
	<p>REASON_DEFAULT_FOOTER_XHTML setting OK.</p>
	<p>Next step: update the database.</p>
	<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
	<?php
}

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
	
	$es = new entity_selector();
	$es->add_type(id_of('content_table'));
	$es->add_relation('entity.name = "site"');
	$es->set_num(1);
	$tables = $es->run_one();
	
	if(empty($tables))
	{
		echo '<p>Unable to find site table. Major problem.</p>';
	}
	else
	{
		$table = current($tables);
	
		$es = new entity_selector();
		$es->add_type(id_of('field'));
		$es->add_relation('entity.name IN ("use_custom_footer","custom_footer")');
		$es->add_left_relationship($table->id(),relationship_id_of('field_to_entity_table'));
		$es->set_num(2);
		$fields = $es->run_one();
		
		
		$fields_to_create = array('use_custom_footer'=>'enum(\'yes\',\'no\')','custom_footer'=>'text');
		foreach($fields as $field)
		{
			unset($fields_to_create[$field->get_value('name')]);
		}
		
		if(empty($fields_to_create))
		{
			echo '<p>Fields exist; script has already been run.</p>';
		}
		else
		{
			if($_POST['go'] != 'run')
			{
				echo '<p>Would have created these fields:</p>';
				pray($fields_to_create);
			}
			else
			{
				$update_fields = array();
				foreach ($fields_to_create as $key=>$value)
					$update_fields[$key] = array('db_type'=>$value);
				$updater = new FieldToEntityTable('site', $update_fields);
				$updater->update_entity_table();
				$updater->report();
			}
		}
	}
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
