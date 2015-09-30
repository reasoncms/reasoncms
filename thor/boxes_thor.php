<?php
/**
 * @package thor
 */

/**
 * Include parent class
 */
include_once('paths.php');
include_once( DISCO_INC . 'boxes/stacked.php');

/**
 * This doesn't do much but currently Disco does not provide a way to interact with a box class between instantiation and the head method
 * and so this class exists for now
 */
class BoxThor extends Box
{
	function head()
	{
		echo '<table border="0" cellpadding="6" cellspacing="0" class="thorTable">' . "\n";
	}
}

/**
 * This doesn't do much but currently Disco does not provide a way to interact with a box class between instantiation and the head method
 * and so this class exists for now
 */
class BoxThorTableless extends StackedBox
{
	function head()
	{
		echo '<div id="discoLinear" class="thorTable">'."\n";
	}
}
?>
