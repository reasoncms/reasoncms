<?php
/**
 * Entity Archiver (Script)
 * @author Nicholas Mischler'14, Beloit College
 * @package reason
 * @subpackage scripts
 */

/**
 * Include Necessary Reason Elements
 * Extensions should include the original class in these calls.
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/archiver.php' );

/**
 * Script for running the enitity archiver.
 *
 * The Entity Archiver is intended to be used to check which entities of a given type are outdated 
 * (older than a given number of months), and list those entities out for review (including listing 
 * all of the entity's values for archiving). The script can also delete entities which explicitly 
 * expire (events, news, etc.) and can send an email with script output to a given email address.  
 *
 * The class assumes that it and the script will be called in a command line environment and prints 
 * progress and log data for that environment. This script should not be run in a HTML context, as the 
 * execution can not be altered from default parameters.
 *
 * To edit the Entity Archiver, extend the following class:
 *		- /reason_4.0/lib/core/classes/archiver.php
 * 
 * @author Nicholas Mischler'14, Beloit College
 */
$entity_archiver = new entityArchiver;
$entity_archiver->run();