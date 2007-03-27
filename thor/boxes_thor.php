<?php

include_once('paths.php');
include_once( DISCO_INC . 'boxes/boxes.php');

class BoxThor extends Box
{
	function head()
	{
		echo '<table border="0" cellpadding="6" cellspacing="0" class="thorTable">' . "\n";
		echo '<tr><td colspan="2" align="left" style="padding-bottom:3ex; padding-top:2ex;">* = required field</td></tr>' . "\n";
	}
}

?>
