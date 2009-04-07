<?php
/**
 * @package tyr
 * @subpackage thankyou_templates
 * @todo figure out if this file is actually needed in the reason package
 */
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo FULL_ORGANIZATION_NAME; ?>: Thank you</title>
  	
  	<?php if(defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
  	{
  		echo '<link rel="stylesheet" type="text/css" HREF="'.UNIVERSAL_CSS_PATH.'">'."\n";
  	}
  	?>

<body>

	<div style="margin-top:1em; margin-right:3em; margin-left:3em;">

	<h1>Thanks for using this form.</h1>
	<p>The data you entered follows:</p>
	<?php echo $data; ?>
	<hr />
	<p>Use your browser's back button to return to the form.</p>

	</div>

</body>
</html>