<?php
/**
 * @package loki_1
 * @subpackage loki
 */
/**
 * set dialog
 */
if ( !empty($_REQUEST['dialog']) )
	$inner = "lokiLoadingInner.php?dialog=" . urlencode($_REQUEST['dialog']);
else
	die('Please provide a <em>dialog</em> to be displayed.');

if ( !empty($_REQUEST['window_title']) )
	$window_title = htmlspecialchars($_REQUEST['window_title'], ENT_QUOTES);
else
	die('Please provide a <em>window_title</em> to be displayed.');

?>
<html>
<head>
<link rel='stylesheet' type='text/css' href='../css/modalStyles.css'>
<title><?php echo $window_title; ?></title>
</head>

<body>
<iframe frameborder="0" style="width:100%; height:100%;" src="<?php echo $inner; ?>">
</iframe>
</body>
</html>