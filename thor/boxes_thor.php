<?php
/**
 * @package thor
 */

/**
 * Include parent class
 */
include_once('paths.php');
include_once( DISCO_INC . 'boxes/boxes.php');

/**
 * A special box class to handle unique characteristics of thor forms
 *
 * Mostly just adds the class thorTable to the table and adds a note 
 * explaining that fields with asterisks are required.
 *
 * @todo remove the asterisk/required note and move that logic into Thor, which can add it as plasmature element
 */
class BoxThor extends Box
{
	function head()
	{
		echo '<table border="0" cellpadding="6" cellspacing="0" class="thorTable">' . "\n";
		echo '<tr class="required_indicator"><td colspan="2" align="left" style="padding-bottom:2ex; padding-top:1ex;">* = required field</td></tr>' . "\n";
	}
}

?>
