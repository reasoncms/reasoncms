<?php
/**
 * Script that sends reminders about publications that have not been recently updated
 *
 * Run this script from the command-line (probably as a cron task) like this:
 *
 * /path/to/php -d include_path=/path/to/reason_package/ /path/to/reason_package/reason_4.0/lib/core/scripts/news/tickler.php publication_unique_name 30 email1@example.com,email2@example.com
 *
 * The order of arguments is:
 *
 * 1. Publication unique name or ID
 * 2. Number of days to consider -- if the publication contains posts more recent than this number of days in the past, no email will be sent
 * 3. Email address or addresses (comma-separated) to send reminders to
 *
 * If no email addresses are provided, the script will simply return a message.
 *
 * This script can also be accessed through the browser, like so:
 * http://servername.org/path/to/reason/www/scripts/news/tickler.php?site=site_unique_name&days=30&email=email@example.com
 *
 * @todo abstract into class
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');

/*
 This could be abstracted like this...
class reasonPublicationReminder
{
	protected $pub;
	protected $days;
	protected $
	function set_publication($pub)
	{
		
	}
	function set_days($days)
	{
	}
	function remind($emails = '')
	{
		
	}
	function get_messages()
	{
	
	}
}
*/

header('Content-Type: text/plain');

$args = array();
if(PHP_SAPI == 'cli')
{
	$user = 'causal_agent';
	if(!empty($GLOBALS['argv'][1]))
		$args['pub'] = $GLOBALS['argv'][1];
	else
		die('Please provide a publication id or unique name as the first argument'."\n");
	
	if(!empty($GLOBALS['argv'][2]))
		$args['days'] = $GLOBALS['argv'][2];
	else
		die('Please provide a number of days in the second argument'."\n");
	
	if(!empty($GLOBALS['argv'][3]))
		$args['emails'] = $GLOBALS['argv'][3];
}
else
{
	$user = reason_require_authentication();
	$reason_user_id = get_user_id( $user );
	if (!reason_user_has_privs( $reason_user_id, 'db_maintenance' ) )
	{
		die('Access denied.'."\n");
	}
	if(!empty($_GET['pub']))
		$args['pub'] = $_GET['pub'];
	else
		die('Please provide a publication id or unique name in the "pub" query string parameter'."\n");
	
	if(!empty( $_GET['days'] ))
		$args['days'] = $_GET['days'];
	else
		die('Please provide a days argument'."\n");
	
	if(!empty( $_GET['emails'] ))
		$args['emails'] = $_GET['emails'];
	
}

if(is_numeric($args['pub']))
{
	$id = (integer) $args['pub'];
}
elseif(reason_unique_name_exists($args['pub']))
{
	$id = id_of($args['pub']);
}
else
{
	die('The publication unique name provided ('.$args['pub'].') does not exist'."\n");
}
$pub = new entity($id);
if(!$pub->get_values())
{
	die('The publication provided does not exist'."\n");
}
if($pub->get_value('type') != id_of('publication_type'))
{
	die('The publication provided is not, in fact, a publication'."\n");
}

$days = (integer) $args['days'];
if(empty($days))
	die('Please provide days as an integer (e.g. 1, 2, 3, or 73)'."\n");

$time = strtotime('-'.$days.' days');
if(empty($time))
	die('Something appears to be amiss -- no time value was able to be resolved from the number of days provided'."\n");

$datetime = date('Y-m-d H:i:s', $time);

$es = new entity_selector();
$es->add_type(id_of('news'));
$es->add_left_relationship( $pub->id(), relationship_id_of('news_to_publication'));
$es->add_relation('`datetime` >= "'.$datetime.'"');
$es->set_num(1);
$posts = $es->run_one();

if(empty($posts))
{
	echo 'No new posts on publication id ' . $pub->id() . ' ('.$pub->get_value('name').') since ' . $datetime . '.'."\n";
	if(!empty($args['emails']))
	{
		$message = 'FYI, there are currently no recent posts on the Reason publication "' . $pub->get_value('name') . '."'."\n\n";
		$message .= 'You are signed up to receive notices when this publication has not been updated in the last '.$days.' days.'."\n\n";
		$message .= 'It may be time to add a new post!'."\n\n";
		$message .= 'Click here to add posts to this publication: http://'.REASON_WEB_ADMIN_PATH.'?site_id='.get_owner_site_id( $pub->id() ).'&type_id='.id_of('news')."\n\n";
		$message .= 'If you are no longer responsible for this publication, please contact a Reason administrator to have this email sent to someone else.'."\n\n";
		$message .= 'Thank you!'."\n\n";
		mail($args['emails'], 'Reason Publication Reminder',$message, 'From: <no-reply@carleton.edu>');
		echo 'Message sent to '.$args['emails']."\n";
	}
}
else
{
	echo 'New post on publication id ' . $pub->id() . ' ('.$pub->get_value('name').') since ' . $datetime . '.'."\n";
	if(!empty($args['emails']))
		echo 'No message sent'."\n";
}

?>