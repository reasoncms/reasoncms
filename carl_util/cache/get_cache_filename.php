<?php
/**
 * Simple script for determining what file to zap if you need to refresh a cache
 * @package carl_util
 * @subpackage cache
 */
 
 /**
  * Include dependencies
  */
	include ('paths.php');
	include_once( CARL_UTIL_INC . 'cache/cache.php' );
	
 /**
  * Run the script
  */
	echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
	echo '<title>Discover a Cache File for a particular URL</title></head><body>';
	echo '<h1>Discover a Cache File for a particular URL</h1>';
	echo '<p>You might want to double check before zapping a cache file.</p>';
	echo '<form method="get"><input type="text" name="url"><input type="submit"></form>';
	if(!empty($_REQUEST['url']))
	{
		$pc = new PageCache;
		echo '<p>URL: '.htmlspecialchars($_REQUEST['url'], ENT_COMPAT, 'UTF-8').'</p>';
		echo '<p>Cache Filename: '.$pc->_get_cache_file( $_REQUEST['url'] ).'</p>';
	}
	echo '</body></html>';
?>
