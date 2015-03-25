<?php
/**
 * Reason Content Reminder (Script)
 * @author Nicholas Mischler'14, Beloit College
 * @package reason
 * @subpackage scripts
 */
 
/**
 * Include Necessary Reason Elements
 * Extensions should include the original class in these calls.
 */
include_once( 'reason_header.php' );
reason_include_once( 'scripts/reason_content_reminder/classes/default.php' );

/**
 * Script for running the Content Reminder.
 *
 * The Content Reminder is intended to be a cronjob which
 * sends regular emails to Reason users to check and maintain
 * their content. In execution, it is flexible with paramaters
 * to limit who, what, and how much to show. It is also extendable
 * and cutomizable with your own language and settings.
 *
 * It is highly encouraged that developers extend the class 
 * and this script for their own use. 
 *
 * The class assumes that it and this script will be called in a 
 * command line environment and prints progress and log data for 
 * that environment. This script should not be run in a HTML context, 
 * as the execution can not be altered from default parameters.
 
 * To edit the Content Reminder, extend the following class:
 *		- /reason_4.0/lib/core/classes/content_reminder.php
 */
$content_reminder = new contentReminder;
$content_reminder->run();